var DDLayout = DDLayout || {};
var wpv_current_edit_page = 'layouts';
DDLayout.Dialogs = DDLayout.Dialogs || {};

// An abstract dialog to inherit common properties and methods from
DDLayout.Dialogs.Prototype = function($){

};

DDLayout.Dialogs.Prototype.prototype.cached_element = null;

DDLayout.Dialogs.Prototype.prototype.setCachedElement = function( element ){
    this.cached_element = element;
};

DDLayout.Dialogs.Prototype.prototype.getCachedElement = function( ){
    return this.cached_element;
};
DDLayout.Dialogs.Prototype.prototype._handle_css_save = function()
{
    var css_editor = DDLayout.ddl_admin_page.cssEditor;

    try{
        return css_editor.setLayoutCss();
    }
    catch( e )
    {
        return false;
    }
};
DDLayout.Dialogs.Prototype.prototype.is_save_and_close = function( caller ){

    return jQuery(caller).data('close') === 'yes' ? true : false;

};
DDLayout.Dialogs.Prototype.prototype.init_buttons = function ($editWindow, mode, cellSettings) {

    var $save_button,
        $save_and_close_button,
        $cancel = jQuery('.js-edit-dialog-close')
        , has_settings = cellSettings.hasSettings === false ? false : true;

    $cancel.css('float', 'left');

    if ('edit' === mode) {

        $save_button = $editWindow.find('.js-dialog-edit-save');
        $save_and_close_button = $editWindow.find('.js-save-dialog-settings-and-close');

        $save_button.html($save_button.data('update-text'));

        if( has_settings === false ) {
            $save_button.hide();
        }

       /* _.delay( function(){

        }, 200);*/
        $save_and_close_button.html($save_and_close_button.data('update-text')).removeClass('hidden').show();

    } else {

        $save_button = $editWindow.find('.js-dialog-edit-save');
        $save_button.html($save_button.data('create-text'));
        $save_and_close_button = $editWindow.find('.js-save-dialog-settings-and-close');

        if( has_settings === false ) {
            $save_button.hide();
        }

       /* _.delay( function(){

        }, 200);*/
        $save_and_close_button.html( $save_and_close_button.data('create-text') ).removeClass('hidden').show();

         WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'layout_ajaxSynced_completed', function(){
            $save_button.html($save_button.data('update-text'));
            $save_and_close_button.html($save_and_close_button.data('update-text'))
        });
    }

    return $save_button;
};


DDLayout.Dialogs.Prototype.prototype.add_error_message_wrap_to_dialog_footer = function () {
    if (jQuery('.js-dialogs-error-footer').is('div') === false) {
        var $div = jQuery('<div class="dialogs-error-footer js-dialogs-error-footer">'),
            $cancel = jQuery('.js-dialog-footer .js-edit-dialog-close');

        $div.css({
            float: 'left',
            width: '60%',
            textAlign: 'left'
        });

        $div.on('wpv-message-open', function(event){

        });

        $cancel.after($div);

        DDLayout.Dialogs.Prototype.footer_error_wrap = $div;
    }
};

DDLayout.Dialogs.Prototype.prototype.display_footer_message = function( params ){
    
    var params = _.extend({
        message:'',
        stay_for:1200,
        stay:false,
        type:'info'
    }, params);

    jQuery('.js-dialog-footer .js-dialogs-error-footer').wpvToolsetMessage({
        text: params.message,
        type: params.type,
        stay: params.stay,
        close: false,
        stay_for:params.stay_for,
        onOpen: function() {

        },
        onClose: function() {

        }
    });
};

DDLayout.Dialogs.Prototype.prototype.close_footer_message = function(){
    if( jQuery('.js-dialog-footer .js-dialogs-error-footer').wpvToolsetMessage('has_message') ){
        jQuery('.js-dialog-footer .js-dialogs-error-footer').wpvToolsetMessage('wpvMessageRemove');
        return true;
    }
    return false;
};


DDLayout.Dialogs.Prototype.prototype.set_cell_model = function(){

    if( typeof DDLayout.ddl_admin_page == 'undefined' ) return;

    var target_cell_view = null;

    if (this.is_new_cell()) {
        target_cell_view = DDLayout.ddl_admin_page.get_new_target_cell();
    } else {
        target_cell_view = jQuery('#ddl-default-edit').data('cell_view');
    }

    var cell = target_cell_view ? target_cell_view.model : null;

    if( cell ){
        var id = cell.get('id');
        jQuery('input[name="ddl-layout-unique_id"]').val(id);
    }
};

DDLayout.Dialogs.Prototype.prototype.is_new_cell = function () {
    return jQuery('#ddl-default-edit').data('mode') == 'new-cell';
};

// a wrapper for all generics for Layouts dialogs
DDLayout.Dialogs.DDL_Dialog = function($){

        var self = this, floats = [];

    _.extend( DDLayout.Dialogs.DDL_Dialog.prototype, new DDLayout.Dialogs.Prototype(jQuery, 'DDL_Dialog') );

    self.init = function(){
        color_box_settings();
        select2_overrides();
        color_box_overrides();
        jquery_ui_tabs_overrides();
    };

    function fix_cancel_button_helper(){
        // cancel button always on the left
        jQuery( '.js-ddl-dialog-element-select').parent().find('.js-edit-dialog-close').css({
            float:'right'
        });
    };

    function fix_view_button_helper(){
        $('#ddl-default-edit .js-ddl-create-edit-view').remove();
    }

    var jquery_ui_tabs_overrides = function(){
        jQuery(document).on('tabsactivate', function(event, ui){
            jQuery( event.target ).trigger( 'activate_tab', {
                tabIndex: ui.newTab.index()
            });
        });

        jQuery(document).on('tabsbeforeactivate', function(event, ui){
            jQuery( event.target ).trigger( 'before-activate_tab', {
                tabIndex: ui.newTab.index(),
                cssClassEl: jQuery('input.js-edit-css-class', event.target),
                cssIdEl: jQuery('input.js-edit-css-id', event.target),
                textArea: jQuery('.js-ddl-css-editor-area', event.target)
            });
        });
    };

    var select2_overrides = function(){

        if( typeof $.fn.select2 === 'undefined' ) return;

        $.extend($.fn.select2.defaults, { // override Select2 defaults
            'width': 250
            ,selectOnBlur:true
//		dropdownAutoWidth: true
        });
    };

    var color_box_settings = function(){
        // call chose box type dialog

        var inline = typeof wpcfAccess !== 'undefined' && adminpage === 'post-php' ? false : true;

        $.extend($.colorbox.settings, {
            transition: 'fade',
            opacity: 0.3,
            speed: 150,
            fadeOut : 0,
            inline : inline,
            fixed: true,
            top: '50px',
            trapFocus: false,
          /*  width:'100%'
           height:'95%',*/
            maxWidth: '1024px'
          /*  maxHeight: '1200px'*/
        });

        $(window).resize(function(){

            var is_toolset_in_iframe = iframe_fixes( window.innerWidth), maxWidth = '1024px',  maxHeight = '1200px';

            if( is_toolset_in_iframe ){
                $.colorbox.resize({
                    width: window.innerWidth > parseInt(maxWidth) ? maxWidth : window.innerWidth - 24,
                    height: window.innerHeight > parseInt(maxHeight) ? maxHeight : $.colorbox.settings.height,
                    maxWidth: '1024px',
                    maxHeight: '1200px'
                });
            }
        });

    };

    var is_toolset_iframe_dialog = function(){
        var $frame = $(document).find('iframe');

        if( $frame.length && $frame.prop('id') === 'ddl-layout-toolset-iframe' ){
            return $frame;
        } else {
            return null;
        }
    };

    var fix_views_frame_width = function( $doc ){
        if( $doc ){
            if( window.innerWidth < parseInt($.colorbox.settings.maxWidth) ){
                $('.wpv-setting', $doc).each(function(i){
                        $(this).css('float', 'none');
                });
                $('label', $doc).each(function(i){
                        if( $(this).css('float') == 'left' ){
                            floats.push( $(this).css('float', 'none') );
                        }
                });

            } else {
                $('.wpv-setting', $doc).each(function(){
                    $(this).css('float', 'right');
                });

                _.each(floats, function(float){
                    float.css('float', 'left');
                });
            }
        }
    };

    var fix_dialog_footer = function( $doc, new_width ){
        var $footer = $('body').find('.js-dialog-footer');

        if( $doc && $footer ){
            if(  $footer.width() > new_width ){
                $footer.find('button').css('float', 'left');
            }
            else {
                $footer.find('button').css('float', 'right');
            }
        }
    };

    var iframe_fixes = function( new_width ){
        if( typeof DDLayout.ToolsetInIfame === 'undefined' ){
            return false;
        }
        var $frame = is_toolset_iframe_dialog(), $doc;

        if( $frame ){
            $doc = DDLayout.ToolsetInIfame.getIframeWindow( $frame[0] );
            $doc = jQuery($doc.document);
            fix_views_frame_width( $doc, new_width );
            fix_dialog_footer( $doc, new_width );
            return true;
        }

        return false;
    };

    var color_box_overrides = function(){

        jQuery(document).on('cbox_load', function(event){

                if( $('#ddl-default-edit .js-ddl-create-edit-view').length > 1 ){

                    $( $('#ddl-default-edit .js-ddl-create-edit-view')[1]).remove();

                }
                if( jQuery('.js-ddl-create-edit-view').length > 0 ){
                    jQuery('.js-ddl-create-edit-view').hide();
                }
        });

        jQuery(document).on('cbox_complete', function(event) {
            
            _.defer( fix_cancel_button_helper );

            if( DDLayout.ddl_admin_page !== undefined )
                DDLayout.ddl_admin_page.is_colorbox_opened = true;


            jQuery(document).trigger('ddl-editor-dialog-complete');

           /* jQuery('#cboxWrapper .js-select2').each(function(i){
                    if( jQuery(this).is('input') ){
                        jQuery(this).select2({
                            width: 'resolve',
                            data: {
                                id: "",
                                text: ""
                            }
                        });
                    } else if( jQuery(this).is('select') ){
                        jQuery(this).select2({
                            width: 'resolve'
                        });
                    }
            });*/

            jQuery('#cboxWrapper .js-select2').select2({
                width: 'resolve'
            });

            // Fix for Select2 and Colorbox incopatibility issue
            jQuery(document).on('mousedown.colorbox','#cboxLoadedContent, #cboxOverlay', function(e){
                if ( jQuery(e.target).parents('.js-select2').length === 0 ) {
                    jQuery('select.js-select2').select2('close');
                }
                if( jQuery(e.target).parents('.js-select2-tokenizer').length === 0 )
                {
                    jQuery('input.js-select2-tokenizer').select2('close');
                }
            });


            var append_message = _.once( self.add_error_message_wrap_to_dialog_footer );
            append_message();
            self.set_cell_model();
        });

        jQuery(document).on('cbox_cleanup', function() {

            // Unbind keyup.colorbox event on colorbox close
            jQuery(document).off('keyup.colorbox');

            // Unbind select2 workaround
            jQuery(document).off('mousedown.colorbox');

            // Destroy select2 obj
            jQuery('.js-select2').select2('destroy');
            jQuery('.js-select2-tokenizer').select2('destroy');

        });

        jQuery(document).on('cbox_closed', function(event) {
            if( DDLayout.ddl_admin_page !== undefined )
                DDLayout.ddl_admin_page.is_colorbox_opened = false;
            jQuery(this).trigger('color_box_closes', event);
            WPV_Toolset.Utils.eventDispatcher.trigger('color_box_closed', event, this );
        });


        jQuery(document).on('color_box_closes', function(event){
            // make sure there is only one editable target at a time in the editor, whatever kind it is
            jQuery('#ddl-row-edit').data('row_view', undefined);
            jQuery('#ddl-default-edit').data('cell_view', undefined);
            jQuery('#ddl-container-edit').data('container_view', undefined);
            jQuery('#ddl-theme-section-row-edit').data('row_view',  undefined);
        });


        jQuery(document).on('click', '.js-edit-dialog-close', function(event){
            event.preventDefault();
            event.stopImmediatePropagation();
            $.colorbox.close();

            return false;
        });
    };

    self.init();
};

(function($){
    jQuery(function() {
        DDLayout.Dialogs.dialog_generic = new DDLayout.Dialogs.DDL_Dialog( $ );
    });
}(jQuery));