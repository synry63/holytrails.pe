<div class="js-change-wrap-box">
    <?php
    $layout = WPDD_Layouts::get_layout_settings_raw_not_cached($current);
    $has_post_content = is_object($layout) && property_exists($layout, 'has_post_content_cell') ? $layout->has_post_content_cell : false;
    $disabled = $has_post_content === false && $do_not_show === false ? ' disabled ' : '';
    $class = $disabled ? 'post-types-list-in-layout-editor-alerted' : '';

    if ($do_not_show === false):?>
       <!-- <p>
            <?php _e('What content will this layout be for?', 'ddl-layouts'); ?>
        </p> -->

        <h2 class="js-change-layout-use-section-title change-layout-use-section-title-outer"><span  class="change-layout-use-section-title js-collapse-group-individual"><?php _e('Template for multiple pages:', 'ddl-layouts'); ?></span>
            <i class="icon-caret-down js-collapse-group-in-dialog change-layout-use-section-title-icon-collapse" data-has_right_cell="<?php echo $has_post_content ?>"></i>
        </h2>

    <?php else: ?>
       <!-- <p>
            <?php _e('Use this layout for these post types:', 'ddl-layouts'); ?>
        </p>-->

    <?php endif; ?>
    <ul class="hidden post-types-list-in-layout-editor js-post-types-list-in-layout-editor js-change-layout-use-section change-layout-use-section <?php echo $class;?>">


        <?php if ($do_not_show === false):?>
        <li>
            <div class="alert-no-post-content toolset-alert <?php if ($has_post_content): ?>hidden<?php endif; ?>">
               <!-- <i class="icon-remove js-remove-alert-message remove-alert-message"></i> -->
                <?php echo sprintf(
                    __("This layout doesn't have a Content Template cell, so you cannot use it for an entire post type. %s",
                        'ddl-layouts'),
                        '<p><a href="#" id="dismiss-post-content-' . $current . '" class="dismiss-alert-message-post-content js-dismiss-alert-message-post-content">Ignore and use anyway</a></p>')
                     ?>
            </div>
        </li>
        <?php endif; ?>

        <?php $active_post_types = array();?>
        <?php foreach ($types as $type): ?>
            <?php
            $active_post_types[] = $type->name;
            $checked = $this->post_type_is_in_layout($type->name, $current) ? 'checked' : '';
            $unique_id = uniqid($id_string, true);
            $posts_used = $this->get_post_types_posts_used($type->name);

            $data = array(
                'batched' => $this->get_post_type_was_batched( $current, $type->name ),
                'assigned' => $checked,
                'count' => $posts_used->count,
                'total' => $posts_used->total
            );
            ?>
            <li class="js-checkboxes-elements-wrap assign-checkboxes-elements-wrap">
                <label for="post-type-<?php echo $unique_id . $type->name; ?>">
                    <input <?php echo $disabled; ?> type="checkbox" <?php echo $checked; ?> name="<?php echo WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME; ?>"
                                                    class="js-ddl-post-type-checkbox<?php echo $id_string ? '-' . $id_string : ''; ?> js-ddl-post-content-checkbox"
                                                    value="<?php echo $type->name; ?>"
                                                    data-object="<?php echo htmlspecialchars( wp_json_encode( $data ) ); ?>"
                                                    id="post-type-<?php echo $unique_id . $type->name; ?>">
                    <?php echo $type->labels->menu_name; ?>
                </label>

                <?php
                        $do_not_show_at_all = '';
                        if( $posts_used->count === 0 || $type->name == 'attachment' /* attachments are always assigned in batch */ ){
                            $do_not_show_at_all = 'do_not_show_at_all';
                       }

                ?>
                <label for="post-type-apply-all-<?php echo $unique_id . $type->name; ?>" class="post-types-apply-to-all-selection-label <?php echo $do_not_show_at_all;?>">
                    <?php
                        printf(__( '%d %s %s already assigned to a layout.', 'ddl-layouts'),  $posts_used->count, $posts_used->count > 1 ? $type->label : $type->labels->singular_name, $posts_used->count > 1 ? 'are' : 'is' );
                    ?>
                    <input type="checkbox" checked name="<?php echo WPDD_Layouts_PostTypesManager::POST_TYPES_APPLY_ALL_OPTION_NAME; ?>"
                           class="js-ddl-post-type-apply-all-checkbox<?php echo $id_string ? '-' . $id_string : ''; ?> js-ddl-post-content-apply-all-checkbox <?php echo $do_not_show_at_all;?>"
                           value="<?php echo $type->name; ?>"
                           data-object="<?php echo htmlspecialchars( wp_json_encode( $data ) ); ?>"
                           id="post-type-apply-all-<?php echo $unique_id . $type->name; ?>"
                           <?php disabled( $type->name == 'attachment' ); /* attachments are checked by default and cannot be unchecked */ ?> 
                           >
                    <?php
                    if( $checked ){
                        _e( 'Update all to use this layout.', 'ddl-layouts');
                    }
                    else{
                        _e( 'Update to use this layout.', 'ddl-layouts');
                    }

                    ?>
                 </label>


                <?php if ($show_ui === false): ?>
                    <?php //$this->print_apply_to_all_link_in_layout_editor($type, $checked, $current); ?>
                <?php endif; ?>
            </li>

        <?php endforeach; ?>
        
        <?php
        //Show Assigned but deleted or deactivated post types.
        $assigned_post_type = $this->get_post_types_options();
        
        if ( isset($assigned_post_type['layout_'.$current]) && is_array($assigned_post_type['layout_'.$current]) && count($assigned_post_type['layout_'.$current]) > 0  ){
            for ( $i = 0, $count_all_pt = count($assigned_post_type['layout_'.$current]); $i < $count_all_pt; $i++){
                if ( !in_array($assigned_post_type['layout_'.$current][$i], $active_post_types) ){
                $post_type_name = $assigned_post_type['layout_'.$current][$i];
                $unique_id = uniqid($id_string, true);
                $data = array();
                    ?>
                    <li class="js-checkboxes-elements-wrap assign-checkboxes-elements-wrap">
                    <label for="post-type-<?php echo $unique_id . $post_type_name; ?>">
                    <input type="checkbox" checked="checked" name="<?php echo WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME; ?>"
                                                    class="js-ddl-post-type-checkbox<?php echo $id_string ? '-' . $id_string : ''; ?> js-ddl-post-content-checkbox"
                                                    value="<?php echo $post_type_name; ?>"
                                                    data-object="<?php echo htmlspecialchars( wp_json_encode( $data ) ); ?>"
                                                    id="post-type-<?php echo $unique_id . $post_type_name; ?>">
                    <?php echo $post_type_name . ' (' . __( 'This post type was removed or deactivated.', 'ddl-layouts'). ')'; ?>
                    </label>
                    </li>
                    <?php
                }
            }
        }       
        ?>
        
        <?php if ($do_not_show === false): ?>
        <li class="save-archives-options-wrap"> <input disabled id="js-save-post-types-options-<?php echo $current;?>" name="save_post_types_options" data-group="<?php echo WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME; ?>" class="button button-secondary button-large js-post-types-options js-buttons-change-update buttons-change-update" value="<?php _e('Update', 'ddl-layouts');?>" type="submit"></li>
        <?php endif; ?>
    </ul>



</div>