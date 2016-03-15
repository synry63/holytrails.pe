DDLayout.PostTypes_Options = function(adm)
{
	var self = this, admin = adm,
        layout_view = admin.instance_layout_view,
        layout_model = layout_view.model,
        button_generic = jQuery('.js-buttons-change-update'),
        post_types_change_button = jQuery('.js-post-types-options')
        , archives_change_button = jQuery('.js-save-archives-options')
        , others_change_button = jQuery('.js-save-others-options')
        ,  $open_dialog = jQuery('.js-layout-content-assignment-button')
        , $list_where_used = jQuery('.js-list-where-used-item');

	self.init = function( )
	{
        // opens dialog in editor only
        self.openDialog();
        self.handle_re_render();
        self.handle_layout_post_types_change();
        self.handle_show_hide_view_edit_links();
       // layout_view.listenTo(layout_view.eventDispatcher, 'ddl-delete-child-layout-cell', self.re_render_listener );
        layout_view.listenTo(layout_view.model, 'cells-collection-add-cell', self.re_render_listener );
	};

    self.handle_show_hide_view_edit_links = function(){
        jQuery(document).on('mouseenter', $list_where_used.selector,
            function(){
                jQuery( '.js-list-where-used-item-controls', jQuery(this) ).show();
           //     jQuery('.layout-content-assignment ul').css('margin-bottom', '6px')
            }
        ).on('mouseleave', $list_where_used.selector,
            function(){
                jQuery( '.js-list-where-used-item-controls', jQuery(this)).hide();
             //   jQuery('.layout-content-assignment ul').css('margin-bottom', '24px')
            }
        );
    };

    self.handle_re_render = function(event)
    {
        var  $where_ui = jQuery('.js-where-used-ui')
            , $button = jQuery('.js-layout-content-assignment-button')
            , $errors = jQuery('.js-where-used-box-messages');

        self.is_assigned = DDLayout.local_settings.list_where_used !== null;

        if( self.is_assigned === false && layout_model.get('has_child') ){
            $where_ui.hide(400)
            return;
        } else if( self.is_assigned && layout_model.get('has_child') ) {
            $button.prop('disabled', true);
            $errors.wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.this_is_a_parent_layout,
                type: 'warning',
                stay: true,
                close: false,
                onOpen: function() {

                },
                onClose: function() {

                }
            });
        } else if( self.is_assigned && layout_model.get('has_child') === false ){
            if( $errors.wpvToolsetMessage('has_message') ){
                $errors.wpvToolsetMessage('destroy');
            }
            $button.prop('disabled', false);
        } else {
            if( $errors.wpvToolsetMessage('has_message') ){
                $errors.wpvToolsetMessage('destroy');
            }
            $button.prop('disabled', false);
        }

        $where_ui.show(400);
    };

    self.re_render_listener = function(){
        _.defer( self.handle_re_render );
    };

    self.handle_layout_post_types_change = function()
    {
        jQuery(document).on('click', button_generic.selector, function(event){

            DDLayout.changeLayoutUseHelper.eventDispatcher.trigger( 'before_sending_data', event );

            var send = {}, extras = null;

            send[jQuery(this).data('group')] = DDLayout.changeLayoutUseHelper.getLayoutOption(jQuery(this).data('group'));

            extras = DDLayout.changeLayoutUseHelper.get_creation_extras( );

            if( null !== extras ) send.extras = extras;

            self.send_data_to_server( event, send, 'js_change_layout_usage_for_'+jQuery(this).data('group'), jQuery(this).data('group') );

        });
    };

    self.send_data_to_server = function( event, data, action, name )
    {
        var params = {
            action:action,
            'layout-set-change-post-types-nonce': jQuery('#layout-set-change-post-types-nonce').val(),
            layout_id:layout_model.get('id'),
            html:'editor'
        };

        jQuery( event.target ).prop( 'disabled', true).removeClass('button-primary').addClass('button-secondary');

        params = _.extend( params, data );
        params['single_amount_to_show_in_dialog'] = DDLayout.changeLayoutUseHelper.get_current_post_list_handler().get_amount();

        DDLayout.ChangeLayoutUseHelper.manageSpinner.addSpinner( event.target );

        WPV_Toolset.Utils.do_ajax_post(params, {success:function(response){
            DDLayout.ChangeLayoutUseHelper.manageSpinner.removeSpinner();
          //  self._refresh_where_used_ui(false);
            DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('data_sent_to_server', jQuery(event.target).closest('div.js-change-wrap-box'), name, response.message);

            self.handle_re_render();
        }});
    };


    self.layout_has_post_content = function( )
    {
        return layout_model.has_cell_of_type("cell-post-content") || layout_model.has_cell_of_type("cell-content-template") || layout_model.get('has_post_content_cell');
    };

    self.layout_has_loop_cell = function()
    {
        return layout_model.has_cell_of_type("post-loop-cell") || layout_model.has_cell_of_type("post-loop-views-cell");
    }

	self._refresh_where_used_ui = function (include_spinner) {
		DDLayout.ddl_admin_page.initialize_where_used_ui(layout_model.get('id'), include_spinner);
	};

    self.openDialog = function () {
        if ($open_dialog.is('button')) {

            jQuery(document).on('click', $open_dialog.selector, function (event) {
                jQuery('#wpcontent').loaderOverlay('show', {class:'loader-overlay-high-z'});
                $open_dialog.prop('disabled', true);
                var params = {
                    action: 'ddl_load_assign_dialog_editor',
                    'load-assign-dialog-nonce': jQuery(this).data('object').nonce,
                    layout_id: layout_model.get('id')
                };

                WPV_Toolset.Utils.do_ajax_post(params, {
                    success: function (response) {
                        $open_dialog.prop('disabled', false);
                        var dialog_content = jQuery('.js-layout-content-assignment-dialog');

                        dialog_content.html( response.Data );

                        self.close_dialog_cancel(dialog_content);

                        var args = {
                            has_content_cell: self.layout_has_post_content(),
                            has_post_loop_cell: self.layout_has_loop_cell()
                        };


                        jQuery('#wpcontent').loaderOverlay('hide');
                        jQuery.colorbox({
                            href: dialog_content.selector,
                            inline: true,
                            open: true,
                            closeButton: false,
                            fixed: true,
                            top: '50px',
                            width:'750px',
                            onLoad: function () {
                                dialog_content.fadeIn('fast');
                                DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('ddl-dialog-ass-load', dialog_content, layout_model.get('id'), args);

                            },
                            onOpen: function () {
                                DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('ddl-dialog-ass-open', dialog_content, layout_model.get('id'), args);

                            },
                            onComplete: function () {
                                DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('ddl-dialog-ass-complete', dialog_content, layout_model.get('id'), args);

                            },
                            onCleanup: function () {
                                dialog_content.hide();
                                DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('assignment_dialog_close');
                            },
                            onClosed: function () {

                            }
                        });
                    }
                });
            });
        }
    };

    self.close_dialog_cancel = function( dialog )
    {
        var cancel = jQuery('.js-layout-content-assignment-sidebar', dialog );

        jQuery(document).on('click', cancel.selector, function(event){
            jQuery('.js-edit-dialog-close', dialog).trigger('click')
            jQuery('.js-edit-dialog-close', dialog).trigger('click')
        });
    };


	self.init();
};