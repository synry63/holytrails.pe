var DDLayout = DDLayout || {};

jQuery(function($)  {
    DDLayout.new_layout_dialog = new DDLayout.NewLayoutDialog($);
    if (typeof ddl_layouts_create_new_layout_trigger != 'undefined' && ddl_layouts_create_new_layout_trigger) {
        DDLayout.new_layout_dialog.show_create_new_layout_dialog(null, null, null);
    }
});

DDLayout.NewLayoutDialog = function($)
{
    var self = this
    // Change the WP menu for "Add a menu" to open the popup instead of redirecting
        ,new_layout_menu_link = jQuery('a[href="admin.php?page=dd_layouts&new_layout=true"]');
    new_layout_menu_link.addClass('js-layout-add-new-top');

    self.init = function() {

        self.colorbox_overrides_handler();

        self.create_new_layout_pop_up();

        self.handle_title_change();

        self.handle_create_new_layout();

        self.handle_preset();

        self.handle_check_parents();

        self.postTypesHandler = new DDLayout.NewLayoutDialogPostTypesHandler($);
    };

    self.colorbox_overrides_handler = function(){
        $.extend($.colorbox.settings, { // override some Colorbox defaults
            transition: 'fade',
            opacity: 0.3,
            speed: 150,
            fadeOut : 0,
            onComplete: function() {

            },
            onCleanup: function() {
            }
        });

        // close dialog
        $(document).on('click','.js-new-layout-dialog-close',function(e) {
            e.preventDefault();
            $.colorbox.close();
            return false;
        });

        // Trigger save button click when ENTER key is pressed
        $(document).on('cbox_complete', function() {
            $(document).on('keyup.colorbox', function(e) {
                var keyCode = parseInt((e.keyCode ? e.keyCode : e.which),10);
                if ((typeof keyCode != 'undefined') && (keyCode === 13)) {
                    if (!$('#cboxWrapper .js-create-new-layout').is(':disabled')) {
                        $('#cboxWrapper .js-create-new-layout').trigger('click');
                    }
                }
            });
        });

        // Unbind keyup.colorbox event on colorbox close
        $(document).on('cbox_cleanup', function() {
            $(document).off('keyup.colorbox');
        });
    };

    self.handle_preset = function(){
        $('.js-presets-list-item').on('click', function(event) {
            $('.js-presets-list-item')
                .data('selected', false)
                .removeClass('selected');

            $(this)
                .data('selected', true)
                .addClass('selected');
        });

        // add preset-container classes to the preset preview.
        $('.presets-list .row-fluid [class*="span-preset"]').each(function() {
            if ($(this).find('.row-fluid').length) {
                // it contains rows so it's a container.
                $(this).addClass('preset-container');
            }
        });

        $('.js-dd-layout-type').on('change', function (event) {
            var width = jQuery('.js-create-new-layout').data('width');
            self._show_presets(width);
        });
    };

    function show_layout_create_error_message(spinnerContainer, message) {
        spinnerContainer.remove();
        $('.js-create-new-layout')
            .addClass('button-primary')
            .removeClass('button-secondary')
            .prop('disabled', false);

        $('.js-ddl-message-container').wpvToolsetMessage({
            text: message,
            type: 'error',
            stay: true
        });
    }

    self.get_creation_extras = function(){
        var handler = DDLayout.new_layout_dialog.postTypesHandler
            , action = handler.get_creation_extra_action();

        if( action === 'none' ){
            return null;
        }

        else if( action === 'one' ) {
            var post_id = handler.get_post_id();

            if( post_id === null ) return null;

            return {
                action: action,
                post_id : post_id
            };
        }

        else if( action === 'all' ) {
            var post_types = _.intersection( handler.get_post_types_to_batch(), DDLayout.new_layout_dialog.postTypesHandler.getPostTypesArray() );

            if( post_types.length > 0 )
            {
                return {
                    action: action,
                    post_types : post_types
                };
            } else {
                return {
                    action: action
                };
            }
        }
    };

    self.handle_create_new_layout = function(){
        // handle creating a new layout

        $(document).on('click', '.js-create-new-layout', function(e){
            e.preventDefault();
            $(this).parent().css('position', 'relative');
            var spinnerContainer = $('<div class="spinner ajax-loader">').appendTo( $(this).parent() ).show().css({
                float:'right',
                position:'absolute',
                top:'12px',
                'right':'175px'
            });

            var title = $('.js-new-layout-title').val();
            var layout_type = $('input[name="dd-layout-type"]:checked').val();
            var $layout_preset = $('.js-presets-list-item:visible').filter(function() {
                return $(this).data('selected');
            });
            var layout_parent = $('.js-create-new-layout').data('parent_layout_id');

            if (typeof layout_parent == 'undefined') {
                layout_parent = 0;
            }

            if (typeof $('.js-new-layout-parent').val() !== 'undefined'){
                layout_parent = $('.js-new-layout-parent').val();
            }
            var save_parent = 0;
            if ( $('.js-make-this-default-parent').prop('checked') ){
                save_parent = 1;
            }

            var columns = $('.js-create-new-layout').data('width');

            var extras = self.get_creation_extras();

            var data = {
                action: 'ddl_create_layout',
                title: _.escape( title ),
                layout_type: layout_type,
                layout_preset: $layout_preset.data('file'),
                layout_parent: layout_parent,
                columns: columns,
                post_types:DDLayout.new_layout_dialog.postTypesHandler.getPostTypesArray(),
                save_parent: save_parent,
                wpnonce : $('#wp_nonce_create_layout').attr('value')
            };

            if( null !== extras ){
                data.extras = extras;
            }

            $('.js-create-new-layout')
                .addClass('button-secondary')
                .removeClass('button-primary');
            $(this).prop('disabled',true);

            $.post(ajaxurl, data, function(response) {
                if ( (typeof(response) !== 'undefined') ) {
                    var temp_res = jQuery.parseJSON(response);

                    if ( temp_res.error == 'error' ){

                        show_layout_create_error_message(spinnerContainer, temp_res.error_message);
                    }
                    else if (typeof temp_res.id !== 'undefined' && temp_res.id !== 0) {
                        var url = $('.js-layout-new-redirect').val();

                        $(location).attr('href',url + temp_res.id + '&new=true');
                    }
                    else {
                        console.log( "Error: WordPress AJAX returned ", response );
                        show_layout_create_error_message(spinnerContainer, ddl_create_layout_error);
                    }
                } else {
                    $('<span class="updated">error</span>').insertAfter($('.js-create-new-layout')).hide().fadeIn(500).delay(1500).fadeOut(500, function(){
                        $(this).remove();
                    });

                }
            })
                .fail(function(jqXHR, textStatus, errorThrown){
                    show_layout_create_error_message(spinnerContainer, ddl_create_layout_error);
                    console.log( "Error: ", textStatus, errorThrown );
                })
                .always(function() {

                });
        });
    };

    self.handle_title_change = function(){
        // handle the title change in the new layout popup.

        $(document).on('change keyup input cut paste', '.js-new-layout-title', function(){
            $('.js-error-container').find('.toolset-alert').remove();
            var  $newLayoutButton = $('.js-create-new-layout');
            if(
                self.postTypesHandler.get_creation_extra_action() == 'all'
            ) {

                if ( $('input.js-new-layout-title').val() != '' && self.postTypesHandler.getPostTypesArray().length > 0 ) {
                    $newLayoutButton
                        .prop('disabled', false)
                        .addClass('button-primary')
                        .removeClass('button-secondary');
                } else {
                    $newLayoutButton
                        .prop('disabled', true)
                        .removeClass('button-primary')
                        .addClass('button-secondary');
                }

            } else {
                if ( $('input.js-new-layout-title').val() != '' ) {
                    $newLayoutButton
                        .prop('disabled', false)
                        .addClass('button-primary')
                        .removeClass('button-secondary');
                } else {
                    $newLayoutButton
                        .prop('disabled', true)
                        .removeClass('button-primary')
                        .addClass('button-secondary');
                }
            }

        });
    };

    self.handle_check_parents = function(){
        function change_default_parent(){
            $('.js-make-this-default-parent').prop('checked', false);
            if ( $('.js-new-layout-parent').val() == '' ) {
                $('.js-make-this-default-parent-label').html($('.js-make-this-default-parent').data('no-parent-text'));
            } else {
                $('.js-make-this-default-parent-label').html($('.js-make-this-default-parent').data('default-text'));
            }
        }
        change_default_parent();
        $(document).on('change', '.js-new-layout-parent', function(){
            change_default_parent();
        });
    };

    self.create_new_layout_pop_up = function(){
        // Create the new layout popup.
        if ($('.js-create-layout-form-dialog').length > 0) {
            $(document).on('click', '.js-layout-add-new-top', function(e, parent_layout_id) {
                e.preventDefault();

                var params = {
                    action:'print_post_type_checkboxes_js',
                    'wp_nonce_create_layout':$('#wp_nonce_create_layout').attr('value')
                };

                WPV_Toolset.Utils.do_ajax_post( params, {success:function(response){
                    var html = response.message;
                    $('.js-ddl-post-types-dropdown-list').html( html  );
                    self.postTypesHandler = null;
                    self.postTypesHandler = new DDLayout.NewLayoutDialogPostTypesHandler($);
                    self.show_create_new_layout_dialog(parent_layout_id, null, null, null);

                }});

            });
        }
    };

    self.show_create_new_layout_dialog = function(parent_layout_id, width, parent_layout_type, create_from_single_data ) {
        if (!width) {
            width = 12;
        }

        jQuery.colorbox({
            inline:true,
            closeButton:false,
            href: '.js-create-layout-form-dialog',
            open:true,
            onOpen:function(){

            },
            onComplete: function() {
                if( null === self.postTypesHandler ){
                    self.postTypesHandler = new DDLayout.NewLayoutDialogPostTypesHandler($);
                }
                self.postTypesHandler.set_dialog( '.js-create-layout-form-dialog' );
                // set initial states
                jQuery('.js-create-new-layout')
                    .prop('disabled', true)
                    .removeClass('button-primary')
                    .addClass('button-secondary');
                jQuery('input.js-new-layout-title')
                    .val('')
                    .focus();
                jQuery('.js-error-container').empty();

                jQuery('.js-create-new-layout').data('parent_layout_id', parent_layout_id);
                jQuery('.js-create-new-layout').data('width', width);

                // Replace 'select parent' to parent name
                if ( parent_layout_id > 0 ){
                    jQuery('.js-set-parent-layout-row').show();
                    jQuery('.js-set-parent-layout-fieldset').html('<b>'+jQuery('.js-layout-title').val()+'</b>');
                }

                if (parent_layout_type && parent_layout_type == 'fluid') {
                    jQuery('input[name="dd-layout-type"]').each( function () {
                        if(jQuery(this).val() == 'fluid') {
                            jQuery(this).prop('checked', true);
                        } else {
                            jQuery(this).prop('checked', false);
                        }
                    })
                    jQuery('input[name="dd-layout-type"]').prop('disabled', true);
                    jQuery('.js-diabled-fixed-rows-info').show();
                } else {
                    jQuery('input[name="dd-layout-type"]').prop('disabled', false);
                    jQuery('.js-diabled-fixed-rows-info').hide();
                }

                self.postTypesHandler.setInitialState( jQuery('.js-create-layout-form-dialog'), create_from_single_data );

                self._show_presets(width);

                WPV_Toolset.Utils.eventDispatcher.trigger('ddl-create-dialog-opened', self);

            },
            onClosed : function() {
                self.postTypesHandler = null;
            }
        });

    }

    self._show_presets = function (width) {
        if (jQuery('.js-dd-layout-type:checked').val() == 'fluid') {
            width = 12; // Force width of 12 for fluid layouts
        }

        var any_visible = false;
        jQuery('.js-presets-list-item').each( function () {
            if (jQuery(this).data('width') == width) {
                jQuery(this).show();
                any_visible = true;
            } else {
                jQuery(this).hide();
            }
        });

        if (any_visible) {
            jQuery('.js-preset-layouts-items').show();

            // Make sure one is selected
            var $layout_preset = $('.js-presets-list-item:visible').filter(function() {
                return $(this).data('selected');
            });
            if (!$layout_preset.length) {
                $('.js-presets-list-item:visible:first').trigger('click');
            }

        } else {
            jQuery('.js-preset-layouts-items').hide();
        }
    };

    self.init();
};

DDLayout.NewLayoutDialogPostTypesHandler = function($)
{

    var self = this,
        open = true,
        dropdown_list = $('.ddl-post-types-dropdown-list'),
        $dont_assign = $('#js-dont-assign-to')
        , $assign = $('#js-assign-to')
        , $assign_to_one = $('#js-assign-only-to')
        , creation_data = null
        , $message_container = $('.js-ddl-for-post-types-messages')
        , dialog = null;


    DDLayout.NewLayoutDialogPostTypesHandler._checked = [];

    self._deselect = $('.js-dont-assign-post-type');

    self._open_close = $('.js-ddl-for-post-types-open');

    self._apply_to_all = $('.js-apply-layout-for-all-posts');

    self.set_dialog = function(d){
        dialog = d;
    };

    self.get_dialog = function(){
        return dialog;
    };

    self.init = function()
    {
        self.handle_show_hide_post_types();
        self.manage_check_box_change();
        self.manage_batch_selection();
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'color_box_closed', self.handle_dialog_close)
    };

    self.handle_radio_initial_state = function( ){

        self.hide_select_bulk_assign_option_by_default();

        if( typeof creation_data === 'undefined' || !creation_data  )
        {
            self.dont_assign_state();
        }
        else if( _.isObject(creation_data) && creation_data.who === 'all' ){
            self.assign_state();
            self.disable_ui();
            var event = jQuery.Event( "change" );
            event.creation_data = creation_data;
            $assign.trigger(event);
        }
        else if( _.isObject(creation_data) && creation_data.who === 'one' )
        {
            self.assign_to_one_state();
            self.disable_ui();
        }

    };

    self.disable_ui = function()
    {
        self._deselect.prop('disabled', true);
        $('input[name="post_types"]').prop('disabled', true );
    };

    self.add_message = function()
    {
        $message_container.show();
    };

    self.remove_message = function(){
        $message_container.hide();
    };

    self.handle_show_hide_post_types = function(event, open_message)
    {
        var $uls = jQuery('.js-change-layout-use-section', dropdown_list );
        $uls.removeClass('hidden');

        if( open === false )
        {
            //  $('i.icon-caret-down').removeClass('icon-caret-down').addClass('icon-caret-up');
            dropdown_list.slideDown(function(){
                open = true;
            });
        }
        else if( open === true )
        {
            // $('i.icon-caret-up').removeClass('icon-caret-up').addClass('icon-caret-down')
            dropdown_list.slideUp(function(){
                open = false;
                self.enable_disable_button(false);
            });
        }

        if( open_message === true )
        {
            self.add_message();
        }else{
            self.remove_message();
        }


    };

    self.manage_check_box_change = function()
    {
        $('input[name="post_types"]').on('change', function(event){

            if( jQuery(this).is(':checked') === true )
            {
                DDLayout.NewLayoutDialogPostTypesHandler._checked.push( jQuery(this).val() );
                self.assign_state();
                self.make_select_bulk_assign_option_visible( $(this), true );
            }
            else if( jQuery(this).is(':checked') === false )
            {
                DDLayout.NewLayoutDialogPostTypesHandler._checked = _.without( DDLayout.NewLayoutDialogPostTypesHandler._checked, jQuery(this).val() );
                self.make_select_bulk_assign_option_visible( $(this), false );

                if( DDLayout.NewLayoutDialogPostTypesHandler._checked.length === 0 )
                {
                    self.dont_assign_state();
                    open = true;
                    self.handle_show_hide_post_types();
                }
            }
            self.enable_disable_button(true);
        });
    };

    self.make_select_bulk_assign_option_visible = function(target, what)
    {
        var $el = target.parent().next('label');

        if( $el.hasClass('do_not_show_at_all') ) return;

        if ( what ) {
            $el.fadeIn('slow');
        }
        else{
            $el.fadeOut('slow');
        }
    };

    self.hide_select_bulk_assign_option_by_default = function()
    {
        $('input[name="post_types"]').each(function(){
            $(this).parent().next('label').hide();
        });
    };

    self.assign_state = function(){
        $dont_assign.prop('checked', false );
        $assign.prop('checked', true);
        $assign_to_one.prop('checked', false);
    };

    self.dont_assign_state = function()
    {
        $dont_assign.prop('checked', true );
        $assign.prop('checked', false);
        $assign_to_one.prop('checked', false);
    };

    self.assign_to_one_state = function()
    {
        $dont_assign.prop('checked', false );
        $assign.prop('checked', false);
        $assign_to_one.prop('checked', true);
    }

    self.manage_batch_selection = function()
    {
        var open_message = false;

        self._deselect.on('change', function(event){
            event.stopImmediatePropagation();

            var check = +$(this).val();

            if( check === 0 )
            {
                $('input[name="post_types"]').each(function(i){
                    $(this).prop( {checked: false} );
                    $(this).parent().next('label').hide();
                    DDLayout.NewLayoutDialogPostTypesHandler._checked = [];
                });
                open = true;
                open_message = false;
                self.enable_disable_button(false);
            }
            else if( check === 1 )
            {
                open = false;
                open_message = false;

                if( typeof event.creation_data !== 'undefined' )
                {
                    var post_type = event.creation_data.post_type;

                    $('input[name="post_types"]').each(function(index, element){

                        if( $(this).val() === post_type )
                        {
                            $(element).prop({checked: true});
                            self.make_select_bulk_assign_option_visible($(element),true);
                            DDLayout.NewLayoutDialogPostTypesHandler._checked.push( post_type );
                        }

                    });
                }
                self.enable_disable_button(true);
            }
            else if( check === 2 )
            {
                open = true;
                open_message = typeof event.creation_data === 'undefined';
                self.enable_disable_button(false);
            }

            self.handle_show_hide_post_types( event, open_message);
        });
    };

    self.enable_disable_button = function( checkboxes ){

        if( checkboxes ){
            if( jQuery('.js-new-layout-title').val() != '' && self.getPostTypesArray().length > 0 ){
                jQuery('.js-create-new-layout').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
            } else{

                jQuery('.js-create-new-layout').prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
            }
        } else {
            if( jQuery('.js-new-layout-title').val() == '' ){
                jQuery('.js-create-new-layout').prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
            } else{
                jQuery('.js-create-new-layout').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
            }
        }

    };

    self.setInitialState = function( parent, data )
    {
        var dialog = parent;

        creation_data = data;

        self.handle_radio_initial_state( creation_data );

        dialog.find('input.js-ddl-post-type-checkbox').each(function(i){
            if( typeof creation_data === 'undefined' || !creation_data || creation_data.who === 'one' )
            {
                $(this).prop('checked', false );
                $(this).parent().siblings('span.js-alret-icon-hide-post').each(function(){
                    $(this).remove();
                });
            }
        });
    };

    self.getPostTypesArray = function()
    {
        return DDLayout.NewLayoutDialogPostTypesHandler._checked;
    };

    self.get_post_types_to_batch = function()
    {
        var $bulk_types = $('.js-ddl-post-content-apply-all-checkbox', self.get_dialog() ), $checked = $bulk_types.filter(function(){ return $(this).is(':checked')}), ret = [];

        $checked.each(function(){
            ret.push( $(this).val() );
        });

        return ret;
    };

    self.get_creation_extra_action = function()
    {
        var $checked = self._deselect.filter(function(){return $(this).is(':checked')}),
            value = $checked.val(),
            check = {
                '0': 'none',
                '1' : 'all',
                '2' : 'one'
            };

        return check[value];
    };

    self.get_post_id = function()
    {
        if( $('#js-associate-post-upon-creation').is('input')  === false ) return null;

        return $('#js-associate-post-upon-creation').val();
    };

    self.handle_dialog_close = function()
    {
        open = true;
        self.handle_show_hide_post_types(  );
    };

    self.init();
};
