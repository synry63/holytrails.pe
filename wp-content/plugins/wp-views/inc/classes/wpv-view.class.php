<?php

/**
 * Represents a single View.
 *
 * Full version with setters & co.
 *
 * @since 1.9
 */
class WPV_View extends WPV_View_Embedded {


    /**
     * See parent class constructor description.
     *
     * @param int|WP_Post $view View post object or ID.
     */
    public function __construct( $view ) {
        parent::__construct( $view );
    }


    /**
     * Create an instance of WPV_View from View ID or a WP_Post object.
     *
     * See WPV_View_Embedded constructor for details.
     *
     * @param int|WP_Post $view View ID or a WP_Post object.
     *
     * @return null|WPV_View
     */
    public static function create( $view ) {
        try{
            $view = new WPV_View( $view );
            return $view;
        } catch( Exception $e ) {
            return null;
        }
    }


    /* ************************************************************************* *\
            Methods
    \* ************************************************************************* */


    /**
     * Create a duplicate of this View.
     *
     * Clone the View and most of it's postmeta. If there is a Loop Template assigned,
     * duplicate that as well and update references (in the appropriate postmeta,
     * in shortcodes in loop output, etc.) in the duplicated View.
     *
     * @todo more detailed description
     *
     * @param string $new_post_title Title of the new View. Must not be used in any
     *     existing View or WPA.
     *
     * @return bool|int ID of the new View or false on error.
     */
    public function duplicate( $new_post_title ) {

        // Sanitize and validate
        $new_post_title = sanitize_text_field( $new_post_title );
        if( empty( $new_post_title ) ) {
            return false;
        }

        if( WPV_View_Base::is_name_used( $new_post_title ) ) {
            return false;
        }

        // Clone existing View post object
        $new_post = (array) clone( $this->post() );
        $new_post['post_title'] = $new_post_title;

        $keys_to_unset = array( 'ID', 'post_name', 'post_date', 'post_date_gmt' );
        foreach( $keys_to_unset as $key ) {
            unset( $new_post[ $key ] );
        }

        $new_post_id = wp_insert_post( $new_post );

        // Clone existing View postmeta
        $postmeta_keys_to_copy = array( '_wpv_settings', '_wpv_layout_settings', '_wpv_description' );

        $new_postmeta_values = array();
        foreach ( $postmeta_keys_to_copy as $key ) {
            $new_postmeta_values[ $key ] = $this->get_postmeta( $key );
        }

        // If this View has a loop Template, we need to clone it and adjust the layout settings.
        if ( $this->has_loop_template ) {
            $new_postmeta_values = $this->duplicate_loop_template( $new_postmeta_values, $new_post_id, $new_post_title );
        }

        // Update postmeta of the new View.
        foreach ( $new_postmeta_values as $meta_key => $meta_value ) {
            update_post_meta( $new_post_id, $meta_key, $meta_value );
        }

        return $new_post_id;
    }


    /**
     * Duplicate a loop template of a View and update references to it.
     *
     * @todo detailed description
     *
     * @param array $new_postmeta_values Array of postmeta of the View.
     * @param int $new_post_id ID of the View.
     * @param string $new_post_title Post title of the View.
     *
     * @return array Updated array of postmeta values of the View.
     */
    private function duplicate_loop_template( $new_postmeta_values, $new_post_id, $new_post_title ) {

        // This will throw an exception if the original CT can't be accessed
        $original_ct = new WPV_Content_Template( $this->loop_template_id );

        // Clone the Content Template acting as a Loop template
        $cloned_ct = $original_ct->clone_this( sprintf( __( 'Loop item in %s', 'wpv-views' ), $new_post_title ), true );

        if( null == $cloned_ct ) {
            throw new RuntimeException( 'unable to clone loop template' );
        }

        // Cloning was successful.

        // Create reference from new View to new Loop template.
        $new_postmeta_values[ WPV_View_Base::POSTMETA_LOOP_TEMPLATE_ID ] = $cloned_ct->id;

        // Create reference from new Loop template to new View.
        $cloned_ct->loop_output_id = $new_post_id ;

        // Process inline Content templates if there are any.
        // @todo can this be done cleaner?
        $inline_templates = wpv_getarr( $new_postmeta_values['_wpv_layout_settings'], 'included_ct_ids', '' );
        if ( !empty( $inline_templates ) ) {
            $inline_templates = explode( ',', $inline_templates );

            // Go through all inline templates (referenced in original View) and if we find a reference
            // to original Loop template, we will replace it with new one.
            foreach ( $inline_templates as $inline_template_key => $inline_template_id ) {

                if ( $inline_template_id == $this->loop_template_id ) {
                    // Replace with new Loop template.
                    $inline_templates[ $inline_template_key ] = $cloned_ct->id;
                }
            }

            // Update the array of inline Content templates.
            $new_postmeta_values['_wpv_layout_settings']['included_ct_ids'] = implode( ',', $inline_templates );
        }


        // Replace name of the old Loop template with new name in Loop output.
        $loop_output = wpv_getarr( $new_postmeta_values['_wpv_layout_settings'], 'layout_meta_html', '' );
        if ( !empty( $loop_output ) ) {

            // Search and replace Loop template titles
            $new_loop_output = str_replace(
                sprintf( 'view_template="%s"', $original_ct->title ),
                sprintf( 'view_template="%s"', sanitize_text_field( $cloned_ct->title ) ),
                $loop_output
            );

            // Save new value
            $new_postmeta_values['_wpv_layout_settings']['layout_meta_html'] = $new_loop_output;
        }

        return $new_postmeta_values;
    }

}