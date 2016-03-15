<?php
global $wpddlayout_theme;

$wpddlayout_theme->file_manager_export->check_theme_dir_is_writable(__('You can either make it writable by the server or download the exported layouts and save them yourself.', 'ddl-layouts'));
?>

    <div class="wrap">

        <div id="icon-tools" class="icon32 icon32-posts-dd_layouts"><br></div>
        <h2><?php _e('Export layouts', 'ddl-layouts'); ?></h2>

        <div class="ddl-settings-wrap">

            <?php if ($wpddlayout_theme->file_manager_export->get_dir_message()): ?>
                <div class="ddl-settings">
                    <p class="toolset alert toolset-alert-error">
                        <?php $wpddlayout_theme->file_manager_export->print_dir_message(); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="ddl-settings">
                <div class="ddl-settings-header">
                    <h3><?php _e('Export layouts to theme directory', 'ddl-layouts'); ?></h3>
                </div>

                <div class="ddl-settings-content">

                    <form method="post" action="">
                        <?php wp_nonce_field('wp_nonce_export_layouts_to_theme', 'wp_nonce_export_layouts_to_theme'); ?>
                        <p>
                            <strong><?php _e('Files will be saved in:', 'ddl-layouts'); ?></strong>
                            <code><?php echo $wpddlayout_theme->file_manager_export->get_layouts_theme_dir(); ?></code>
                        </p>

                        <p>
                            <input type="submit" class="button button-secondary" name="export_to_theme_dir"
                                   value="<?php _e('Export', 'ddl-layouts'); ?>"
                                   <?php if (!$wpddlayout_theme->file_manager_export->dir_is_writable()) : ?>disabled<?php endif ?> >
                        </p>
                    </form>

                    <?php
                    if (isset($_POST['export_to_theme_dir'])) {
                    $nonce = $_POST["wp_nonce_export_layouts_to_theme"];

                    if (WPDD_Utils::user_not_admin()) {
                        die(__("You don't have permission to perform this action!", 'ddl-layouts'));
                    }

                    if (wp_verify_nonce($nonce, 'wp_nonce_export_layouts_to_theme')) {

                    $results = $wpddlayout_theme->export_layouts_to_theme($wpddlayout_theme->file_manager_export->get_layouts_theme_dir());

                    ?>

                    <?php if (sizeof($results)): ?>
                    <p>
                        <?php _e('The following layouts have been exported.', 'ddl-layouts'); ?>
                    </p>

                    <ul>
                        <?php foreach ($results as $result): ?>
                            <li>
                                <?php if ($result['file_ok']): ?>
                                    <i class='icon-ok-sign toolset-alert-success'></i>
                                <?php else: ?>
                                    <i class='icon-remove-sign toolset-alert-error'></i>
                                <?php endif; ?>
                                <?php echo $result['title']; ?>
                                <?php echo $result['file_name']; ?>
                                <?php if (!$result['file_ok']): ?>
                                    <p class="toolset-alert-error">
                                        <?php _e('The file is not writable.', 'ddl-layouts'); ?>
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <ul>
                            <?php endif ?>

                            <?php

                            }
                            }
                            ?>

                </div>
                <!-- .ddl-settings-content -->
            </div>
            <!-- .ddl-settings -->

            <div class="ddl-settings">
                <div class="ddl-settings-header">
                    <h3><?php _e('Export and download layouts', 'ddl-layouts'); ?></h3>
                </div>

                <div class="ddl-settings-content">

                    <form method="post" action="">
                        <?php wp_nonce_field('wp_nonce_export_layouts', 'wp_nonce_export_layouts'); ?>
                        <p>
                            <input type="submit" class="button button-secondary" name="export_and_download"
                                   value="<?php _e('Export', 'ddl-layouts'); ?>">
                        </p>
                    </form>

                </div>
                <!-- .ddl-settings-content -->
            </div>
            <!-- .ddl-settings -->

        </div>
        <!-- .ddl-settings-wrap -->

        <div id="icon-tools" class="icon32 icon32-posts-dd_layouts"><br></div>
    </div> <!-- .wrap -->

    <div class="clear"></div>

    <div class="wrap padding-top-30">

    <h2><?php _e('Import layouts', 'ddl-layouts'); ?></h2>

    <div class="ddl-settings-wrap">
        <div class="ddl-settings">

            <div class="ddl-settings-header">
                <h3><?php _e('Import layouts from local file', 'ddl-layouts'); ?></h3>
            </div>

            <div class="ddl-settings-content">

                <h4><?php _e('Settings', 'wpv-views'); ?>:</h4>

                <form method="post" action="" enctype="multipart/form-data" name="import-layouts" id="import-layouts">
                    <ul>
                        <li>
                            <input id="layouts-overwrite" type="checkbox" name="layouts-overwrite"/>
                            <label
                                for="layouts-overwrite"><?php _e('Overwrite any layout if it already exists', 'ddl-layouts'); ?></label>
                        </li>
                        <li>
                            <input id="layouts-delete" type="checkbox" name="layouts-delete"/>
                            <label
                                for="layouts-delete"><?php _e('Delete any existing layouts that are not in the import', 'ddl-layouts'); ?></label>
                        </li>

                        <li>
                            <input id="overwrite-layouts-assignment" type="checkbox"
                                   name="overwrite-layouts-assignment"/>
                            <label
                                for="overwrite-layouts-assignment"><?php _e('Overwrite layout assignments', 'ddl-layouts'); ?></label>
                        </li>
                    </ul>

                    <h4><?php _e('Select a .zip, .ddl or .css file to import from your computer', 'ddl-layouts'); ?>
                        :</h4>

                    <p>
                        <label for="upload-layouts-file"><?php _e('Upload file', 'ddl-layouts'); ?>:</label>
                        <input type="file" id="upload-layouts-file" name="import-file"/>
                    </p>

                    <p class="alignright">
                        <input id="ddl-import" class="button-primary" type="submit"
                               value="<?php _e('Import', 'ddl-layouts'); ?>" name="ddl-import"/>
                    </p>

                    <?php wp_nonce_field('layouts-import-nonce', 'layouts-import-nonce'); ?>
                </form>

                <div class="import-layouts-messages"></div>

            </div>
        </div>
    </div>

<?php
