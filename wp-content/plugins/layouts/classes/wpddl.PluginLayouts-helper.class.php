<?php
class WPDDL_Plugin_Layouts_Helper{

    public function __construct(){
        add_action('wp_ajax_duplicate_layout', array(&$this, 'duplicate_layout_callback'));
    }

    public function duplicate_layout_callback()
    {

        // Clear any errors that may have been rendered that we don't have control of.
        if (ob_get_length()) {
            ob_clean();
        }
        if( user_can_create_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if ($_POST && wp_verify_nonce($_POST['layout-duplicate-layout-nonce'], 'layout-duplicate-layout-nonce')) {
            global $wpdb, $wpddlayout;

            $result = $wpdb->get_row($wpdb->prepare("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type=%s AND ID=%d AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE, $_POST['layout_id']));
            if ($result) {
                $layout_json = WPDD_Layouts::get_layout_settings($result->ID);
                $layout_array = json_decode($layout_json, true);


                $layout_name_base = __('Copy of ', 'ddl-layouts') . str_replace('\\', '\\\\', $layout_array['name']);
                $layout_name = $layout_name_base;

                $count = 1;
                while ($wpddlayout->does_layout_with_this_name_exist($layout_name)) {
                    $layout_name = $layout_name_base . ' - ' . $count;
                    $count++;
                }

                $postarr = array(
                    'post_title' => $layout_name,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_type' => WPDDL_LAYOUTS_POST_TYPE
                );
                $post_id = wp_insert_post($postarr);

                $post_slug = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE post_type=%s AND ID=%d", WPDDL_LAYOUTS_POST_TYPE, $post_id));

                $layout_array['name'] = $layout_name;
                $layout_array['slug'] = $post_slug;

                WPDD_Layouts::save_layout_settings($post_id, $layout_array);
                
                $wpddlayout->register_strings_for_translation( $post_id );

            }

            $send = $wpddlayout->listing_page->get_send(isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish', false, $post_id, $post_id, '', $_POST);

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }
}