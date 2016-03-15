var DDLayout = DDLayout || {};

DDLayout._templateSettings = DDLayout._templateSettings || {
    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
    evaluate: /<#([\s\S]+?)#>/g,
    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
};

DDLayout.PostEditorOverrides = function ($) {
    var self = this,
        $editor_wrap = $('#postdivrich'),
        $message_hide_wrap = $('.js-ddl-post-content-message-in-post-editor'),
        $message_show_wrap = null,
        $js_ddl_switch_layout = null,
        $dummy_container = $('<div class="ddl-dummy-container js-ddl-dummy-container" />'),
        $overlay = $('<div class="ddl-post-editor-overlay js-ddl-post-editor-overlay toolset-alert" />'),
        $overlay_non_transparent = $('<div class="ddl-overlay-non-transparent js-ddl-overlay-non-transparent toolset-alert" />'),
        $hide_editor = $('.js-ddl-hide-editor'),
        $hide_overlay = $('.js-ddl-show-editor'),
        post, layout, current_template = DDLayout_settings.DDL_JS.current_template;

    self._has_post_content = DDLayout_settings.DDL_JS.post.has_post_content_cell;

    self.init = function () {
        // _.templateSettings.variable = "ddl";

        if( $('#content_ifr').length > 0 || $('textarea#content.wp-editor-area').length > 0 ){
            self.manage_post_content_cell_in_post_editor();
            WPV_Toolset.Utils.eventDispatcher.listenTo( WPV_Toolset.Utils.eventDispatcher, 'ddl-layout-template-name-changed', self.set_up_from_outer_select);
            WPV_Toolset.Utils.eventDispatcher.listenTo( WPV_Toolset.Utils.eventDispatcher, 'ddl-post-editor-loaded-first', self.set_visibility_on_ready);
        }
    };


    self.set_up_from_outer_select = function( layout_slug ){
        var layouts = DDLayout_settings.DDL_JS.layouts,
            current = _.where(layouts, {slug: layout_slug })[0];

        self.set_layout( current );
        self._has_post_content = current && current.has_post_content_cell;
        if( self._has_post_content ){
            self.remove_overlay();
            $message_hide_wrap.hide();
        } else {
            self.empty_overlay();
            self.hide_editor_on_ready();
        }

    };

    self.get_template = function()
    {
        return current_template;
    };

    self.set_current_template_from_option_value = function( combined_name ){

        if( !combined_name ){

            current_template = combined_name;

        } else {

            var template = combined_name.split(' in ');

            if( template[1] ){
                current_template = template[1];
            }
        }
    };

    self.hide_editor_on_ready = function ( ready ) {
        if( !self.get_layout() ) return;

        var template = $("#js-ddl-post-content-message-in-post-editor-tpl").html()
            , message_template = $('#js-ddl-post-content-message-in-post-editor-html').html()
            , template_data = {}, switch_manager;

        template_data.post = self.get_post();
        template_data.layout = self.get_layout();
        self._has_post_content = template_data.layout.has_post_content_cell;
        $overlay_non_transparent.html( _.template(template, template_data, DDLayout._templateSettings) );

        $message_hide_wrap.html( _.template( message_template, self.get_layout(), DDLayout._templateSettings ) );

        $overlay.addClass("ddl-overlay-for-post-type-"+post.post_type);
        $overlay_non_transparent.addClass("ddl-overlay-non-transparent-for-post-type-"+post.post_type);
        $editor_wrap.css("position", "relative");
        $dummy_container.append( $overlay, $overlay_non_transparent  );
        $editor_wrap.append( $dummy_container);

        self.set_overlay_heigtht();

        $message_show_wrap = $('.js-ddl-post-content-show-post-post-editor-wrap');
        $js_ddl_switch_layout = $('.js-ddl-switch-layout-button');
        switch_manager = new DDLayout.SwitchLayoutManager($, $js_ddl_switch_layout);

        if( ready === true ) {
            WPV_Toolset.Utils.eventDispatcher.trigger( 'ddl-post-editor-loaded-first' );
        } else {
            self.set_visibility_on_ready();
        }

    };

    self.set_overlay_heigtht = function(){
        var check_height = $('#content_ifr')[0] || $('textarea#content.wp-editor-area')[0];

        if( check_height.offsetHeight < $('body')[0].offsetHeight ){
            $dummy_container.height("90.2%");

        } else {
            var $info = $('.ddl-post-content-editor-layout-info'), $show_wrap = $('.ddl-post-content-show-post-post-editor-wrap');
            $dummy_container.height("99%");
            $info.css('top','5%');
            $show_wrap.css('top','5%');
        }

    };

    self.animate_overlay = function(action, speed, callback, args){

        var params = {
            'show': [1, 0.6, 1],
            'hide': [0, 0, 0],
            'slow' : [500, 600, 800],
            'fast': [300, 300, 300],
            'very': [100, 200, 300]
        };

        if( params[action][0] > 0 ){
            $dummy_container.show();
        }

        $dummy_container.animate({
            opacity: params[action][0]
        }, params[speed][0], function () {

            $overlay.animate({
                opacity: params[action][1],
                specialEasing: {
                    background: "easeOutBounce"
                }
            }, params[speed][1]);

            $overlay_non_transparent.animate({
                opacity: params[action][2],
                specialEasing: {
                    background: "easeOutBounce"
                }
            }, params[speed][2], function(){

            });

            if( typeof callback !== 'undefined' && typeof callback == 'function'){
                callback.apply( self, args );
            }

            if( params[action][0] === 0 ){
                $dummy_container.hide();
            }

        });
    };


    self.empty_overlay = function(){
        $dummy_container.empty().hide();
    };

    self.remove_overlay = function(){
        $dummy_container.remove();
    };

    self.set_overlay = function(){
        $overlay = $('<div class="ddl-post-editor-overlay js-ddl-post-editor-overlay toolset-alert" />');
        $overlay_non_transparent = $('<div class="ddl-overlay-non-transparent js-ddl-overlay-non-transparent" />');
        $dummy_container = $('<div class="ddl-dummy-container js-ddl-dummy-container" />');
    };

    self.show_editor = function () {

        $(document).on('click', $hide_overlay.selector, function () {

            self.animate_overlay('hide', 'fast', function(){
                $message_hide_wrap.show(300);
                jQuery.jStorage.set( self.get_post().ID, {'ddl-overlay-hide': true}  );
            });
        });

    };

    self.hide_editor = function () {
        $(document).on('click', $hide_editor.selector, function () {

            $message_hide_wrap.fadeOut(300, function () {
                self.animate_overlay('show', 'very');
                jQuery.jStorage.set( self.get_post().ID, {'ddl-overlay-hide': false}  );
            });
        });
    }

    self.manage_post_content_cell_in_post_editor = function () {
        self.set_post( DDLayout_settings.DDL_JS.post );

        self.set_layout( DDLayout_settings.DDL_JS.layout );
        self.show_editor();
        self.hide_editor();

        if ( self._has_post_content === false ) {
            _.defer(self.hide_editor_on_ready, true);
        }

    };

    self.set_visibility_on_ready = function(){

        if( self.get_settings() === false ){
            self.animate_overlay('show', 'slow');
        } else{
            $message_hide_wrap.fadeIn(300, function(){
                $dummy_container.hide();
            });
        }
    };

    self.get_settings = function()
    {
        var settings = jQuery.jStorage.get( self.get_post().ID );

        settings = settings && settings['ddl-overlay-hide'] === true ? true : false;

        return settings;
    };

    self.set_post = function( p ){
        post = p;
    };

    self.get_post = function(){
        return post;
    };

    self.set_layout = function( l ){
        layout = l;
    };

    self.get_layout = function(){
        return layout;
    };

    self.init();

};

DDLayout.SwitchLayoutManager = function($, $button ){
    var self = this,
        current = DDLayout.post_editor_overrides.get_layout(),
        post = DDLayout.post_editor_overrides.get_post(),
        $message = null,
        layouts = null,
        $combined = $('#js-combined-layout-template-name'), $select;

    //  self = _.extend(self, Backbone.Events);

    self.trigger = $button;

    self.init = function(){
        //_.templateSettings.variable = "ddl";

        self.trigger.on('click', self.open_dialog);

        WPV_Toolset.Utils.loader = new WPV_Toolset.Utils.Loader();
    };

    self.open_dialog = function(event){
        event.preventDefault();

        layouts = DDLayout_settings.DDL_JS.layouts;

        var template = $("#js-ddl-post-content-switch-layout-dialog-html").html();
        ////console.log('current to tpl', current.name, current.slug );
        $("#js-ddl-post-content-switch-layout-dialog-wrap").html( _.template( template, {layouts : layouts, current : current, post : post}, DDLayout._templateSettings ) );

        jQuery.colorbox({
            href: '#js-ddl-post-content-switch-layout-dialog-wrap',
            inline: true,
            open: true,
            closeButton:false,
            fixed: true,
            top: false,
            width:"400px",
            onComplete: function() {
                self.init_select2_box();
                self.update_layout();
            },
            onCleanup: function() {
                $select.select2('close');
            }
        });
    };

    self.init_select2_box = function(){
        $select = $('.js-ddl-switch-layout');

        function format(state) {
            if( state.css == 'cell-content-template')
            {
                return '<div class="div-option-icon cell-content-template"><i class="item-type-icon icon-views-logo ont-color-orange ont-icon-16"></i>' + state.text + '';
            } else if( state.css == 'cell-post-content'){
                return '<div class="div-option-icon cell-post-content"><i class="item-type-icon icon-file-text"></i>' + state.text + '';
            } else if( state.css == 'cell-content-template-no-body' ){
                return '<div class="div-option-icon cell-content-template"><i class="item-type-icon ont-color-orange disabled-icon icon-views-logo ont-icon-16"></i>' + state.text + '';
            }
            else {
                return '<div class="div-option-icon no-icon">' + state.text;
            }

        }

        $select.select2({
            formatResult: format,
            formatSelection: format,
            width: '80%',
            height:'30px',
            escapeMarkup: function(m) { return m; }
        });
    };

    self.update_layout = function()
    {
        var $save = $('.js-switch-layout-button-save');

        $message = $('.switch-layout-message-container');

        $save.on('click', function(event){
            event.preventDefault();
            var  value = $select.find('option:selected').val()
                , selected = _.where(layouts, {slug: value })[0];

            if( value === current.slug )
            {
                $message.wpvToolsetMessage({
                    text:DDLayout_settings.DDL_JS.message_same + ' ' + post.post_title,
                    type: 'message',
                    stay: false,
                    close: false,
                    onOpen: function() {
                        jQuery('html').addClass('toolset-alert-active');
                    },
                    onClose: function() {
                        jQuery('html').removeClass('toolset-alert-active');
                    }
                });

                return;
            }
            else{
                $message.wpvToolsetMessage('destroy');
                current = selected;


                if( post.post_type === 'page' )
                {
                    self.set_combined_new_val();
                }
                else{
                    //js-layout-template-name
                    self.set_select_layout_new_val();
                }


                jQuery.colorbox.close()
                //self.open_dialog_confirm(event);
            }
        });
    };

    self.set_select_layout_new_val = function(){
        var $layout_select = jQuery('#js-layout-template-name');
        $layout_select.val(current.slug);
        $layout_select.trigger('change');
    };

    self.get_combined_name = function()
    {
        if( !DDLayout.post_editor_overrides.get_template() || DDLayout.post_editor_overrides.get_template() === '' ){
            DDLayout.post_editor_overrides.set_current_template_from_option_value( $combined.find(':selected').val() );
        }

        return current.slug +' in ' +  DDLayout.post_editor_overrides.get_template();
    };

    self.set_combined_new_val = function(){
        $combined.val( self.get_combined_name() );
        $combined.trigger('change', self.get_combined_name() );
    };


    // deprecated
    self.open_dialog_confirm = function(event){
        event.preventDefault();

        var template = $("#js-ddl-post-content-switch-layout-dialog-confirm-html").html();

        $("#js-ddl-post-content-switch-layout-dialog-confirm-wrap").html( _.template( template, {current : current, post : post}, DDLayout._templateSettings ) );

        jQuery.colorbox({
            href: '#js-ddl-post-content-switch-layout-dialog-confirm-wrap',
            inline: true,
            open: true,
            closeButton:false,
            fixed: true,
            top: false,
            onComplete: function() {

            },
            onCleanup: function() {

            }
        });

    };

    self.init();
};



(function ($) {
    $(function () {
        DDLayout.post_editor_overrides = {};
        DDLayout.PostEditorOverrides.call(DDLayout.post_editor_overrides, $);
    });
}(jQuery));