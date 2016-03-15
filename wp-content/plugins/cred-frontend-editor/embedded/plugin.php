<?php
/*
  Plugin Name: CRED Frontend Editor Embedded
  Plugin URI: http://wp-types.com/home/cred/
  Description: Create Edit Delete Wordpress content (ie. posts, pages, custom posts) from the front end using fully customizable forms
  Version: 1.3.6.1
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  License: GPLv2
 *
 */

/**
 *
 * $HeadURL$
 * $LastChangedDate$
 * $LastChangedRevision$
 * $LastChangedBy$
 *
 */

add_action('plugins_loaded', 'cred_embedded_load_or_deactivate');

function cred_embedded_load_or_deactivate() {
    if (class_exists('CRED_Admin')) {
        add_action('admin_init', 'cred_embedded_deactivate');
        add_action('admin_notices', 'cred_embedded_deactivate_notice');
    } else {
        require_once "cred_embedded.php";
    }
}

/**
 * cred_embedded_deactivate
 *
 * Deactivate this plugin
 *
 * @since 1.3.5
 */
function cred_embedded_deactivate() {
    $plugin = plugin_basename(__FILE__);
    deactivate_plugins($plugin);
}

/**
 * cred_embedded_deactivate_notice
 *
 * Deactivate notice for this plugin
 *
 * @since 1.3.5
 */
function cred_embedded_deactivate_notice() {
    ?>
    <div class="error">
        <p>
            <?php _e('WP CRED Embedded was <strong>deactivated</strong>! You are already running the complete WP CRED plugin, so this one is not needed anymore.', 'cred'); ?>
        </p>
    </div>
    <?php
}