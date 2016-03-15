<?php
/**
 * Base class for 'view' post type, that means Views and WPAs.
 *
 * Contains code common for both, mostly related to "view query mode", a value determining what kind of object
 * it is.
 *
 * @since 1.8
 */
abstract class WPV_View_Base extends WPV_Post_Object_Wrapper {


    /* ************************************************************************* *\
            Constants and static methods
    \* ************************************************************************* */


    /**
     * View post type slug.
     */
    const POST_TYPE = 'view';


    const POSTMETA_DESCRIPTION = '_wpv_description';

    const POSTMETA_LOOP_TEMPLATE_ID = '_view_loop_template';


    /**
     * Determine whether View/WPA with given ID exists.
     *
     * @param int $view_id ID of the View to check.
     *
     * @return bool True if post with given ID exists and if it's a View.
     */
    public static function is_valid( $view_id ) {
        /* Note: This should not cause a redundant database query. Post objects are cached by WP core, so this one was
         * either already loaded or it has to be loaded now and will be reused in the future. */
        return WPV_View_Base::is_wppost_view( WP_Post::get_instance( $view_id ) );
    }


    /**
     * For a given object, determine if it's a valid WP_Post object representing a View/WPA.
     *
     * @param mixed $post Value to check.
     *
     * @return bool True if $post is a valid WP_Post object representing a View/WPA, false otherwise.
     */
    public static function is_wppost_view( $post ) {
        return ( ( $post instanceof WP_Post ) && ( $post->ID > 0 ) && ( WPV_View_Base::POST_TYPE == $post->post_type ) );
    }


    /**
     * Determine if the object is used as a WordPress Archive.
     *
     * We cannot rely only on the value of "view query mode" stored in postmeta, because some filters need to be
     * applied along the way. Current implementation causes a get_post_meta() call.
     *
     * @todo can this be done better, without another query or filters?
     *
     * @param int $view_id ID of the object ('view' post type).
     *
     * @return bool True if it is a WPA, false otherwise.
     */
    public static function is_archive_view( $view_id ) {
        global $WP_Views;
        return $WP_Views->is_archive_view( $view_id );
    }


    /**
     * Create an appropriate wrapper for View or WPA post object.
     *
     * Decides by self::is_archive_view() if it's a WPA.
     *
     * @param int|WP_Post $view Post ID or post object.
     *
     * @return null|WPV_View_Embedded|WPV_WordPress_Archive_Embedded The appropriate wrapper or null on error.
     */
    public static function create( $view ) {
        if( is_integer( $view ) ) {
            $post = WP_Post::get_instance( $view );
        } else {
            $post = $view;
        }

        if( ! WPV_View_Base::is_wppost_view( $post ) ) {
            return null;
        }

        try {
            if ( WPV_View_Base::is_archive_view( $post->ID ) ) {
                return new WPV_WordPress_Archive_Embedded( $post );
            } else {
                return new WPV_View_Embedded( $post );
            }
        } catch( Exception $ex ) {
            return null;
        }
    }


    /**
     * Determine whether given View name is already used as a post slug or post title.
     *
     * @param string $name View name to check.
     *
     * @param int $except_id The View ID to exclude from checking.
     * @return bool True if name is already used, false otherwise.
     *
     * @since 1.9
     */
    public static function is_name_used( $name, $except_id = 0 ) {
        return WPV_Post_Object_Wrapper::is_name_used_base( $name, WPV_View_Base::POST_TYPE, $except_id );
    }



    /* ************************************************************************* *\
            Methods
    \* ************************************************************************* */


    /**
     * Class constructor. Create an instance from View ID or WP_Post object representing a View.
     *
     * Please note that WP_Post object will be validated and an exception is thrown on error.
     * However, if only an ID is provided, no such validation takes place here (in order to avoid potentionally
     * unnecessary database query). So, the ID must be validated before (by WPV_View_Base::is_valid() or by other
     * means), otherwise the behaviour of this object is undefined. Also note that "view query mode" is not checked
     * here. If you are not certain about it's value, use self::create().
     *
     * @param int|WP_Post $view View ID or a WP_Post object.
     *
     * @throws InvalidArgumentException when provided argument is not a WP_Post instance representing a View or an
     * integer that *might* be a View ID.
     */
    public function __construct( $view ) {
        if( $view instanceof WP_Post ) {
            // Let's check that we indeed have a valid post and View post type
            if( WPV_View_Base::is_wppost_view( $view ) ) {
                // Store the data we got;
                $this->object_id = $view->ID;
                $this->post = clone( $view );
            } else {
                throw new InvalidArgumentException( "Invalid WP_Post object provided (not a View): " . print_r( $view, true ) );
            }
        } elseif( is_numeric( $view ) && $view > 0 ) {
            // We assume (!) this is a valid View ID.
            $this->object_id = $view;
        } else {
            throw new InvalidArgumentException( "Invalid argument provided (not a View or ID): " . print_r( $view, true ) );
        }
    }


    /**
     * Get the post object representing this View.
     *
     * @return WP_Post Post object.
     *
     * @throws InvalidArgumentException if the post object cannot be retrieved or is invalid.
     */
    protected function &post() {

        if( null == $this->post ) {
            // Requesting WP_Post object, but we haven't got it yet.
            $post = WP_Post::get_instance( $this->object_id );
            if( WPV_View_Base::is_wppost_view( $post ) ) {
                $this->post = $post;
            } else {
                throw new InvalidArgumentException( 'Invalid View ID' );
            }
        }

        return $this->post;
    }


    /**
     * @var null|array Cache for View settings.
     */
    protected $views_settings_cache = null;


    /**
     * Obtain View settings. Optional caching.
     *
     * The proper way to obtain View settings is through $WP_Views->get_view_settings(), which applies some filters
     * on it. We may not need to apply them more than once.
     *
     * @param bool $use_cached If true, prefer cached version. Otherwise no caching.
     *
     * @return array View settings.
     */
    protected function get_view_settings( $use_cached = false ) {
        if( !$use_cached || ( null == $this->views_settings_cache ) ) {
            global $WP_Views;
            $this->views_settings_cache = $WP_Views->get_view_settings( $this->object_id );
        }

        return $this->views_settings_cache;
    }


    /* ************************************************************************* *\
            Custom getters
    \* ************************************************************************* */


    /**
     * @return string View description.
     */
    protected function _get_description() {
        return esc_html( $this->get_postmeta( WPV_View_Base::POSTMETA_DESCRIPTION ) );
    }


    /**
     * Get cached(!) version of View settings array.
     *
     * Please use this only when you are sure you will not break anything by caching.
     *
     * @return array View settings.
     */
    protected function _get_settings() {
        return $this->get_view_settings( true );
    }


    /**
     * Get "query mode", a value determining what kind of object this is.
     *
     * Allowed values are 'normal', 'archive' and 'layouts-loop'. The value will be an empty string if the query mode
     * is not set for this object (which should never happen, though).
     *
     * @return string
     *
     * @since 1.8
     */
    protected function _get_query_mode() {
        $settings = $this->settings; // to avoid PHP notice
        return wpv_getarr( $settings, 'view-query-mode', '', array( 'normal', 'archive', 'layouts-loop' ) );
    }


    /**
     * @return string Label for the object depending on "view query mode". Empty string when it's invalid.
     */
    protected function _get_query_mode_display_name() {
        switch( $this->query_mode ) {
            case 'normal':
                return __( 'View', 'wpv-views' );
            case 'archive':
            case 'layouts-loop':
                return __( 'WordPress Archive', 'wpv-views' );
            default:
                // should never happen
                return '';
        }
    }


    /**
     * @return bool True if this View/WPA uses a CT as a Loop Template.
     */
    protected function _get_has_loop_template() {
        return ( $this->loop_template_id > 0 );
    }


    /**
     * @return int ID of the CT used as a Loop Template or zero if no such CT exists.
     */
    protected function _get_loop_template_id() {
        return (int) $this->get_postmeta( WPV_View_Base::POSTMETA_LOOP_TEMPLATE_ID );
    }

}