var DDLayout = DDLayout || {};

DDLayout._templateSettings = DDLayout._templateSettings || {
    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
    evaluate: /<#([\s\S]+?)#>/g,
    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
};

DDLayout.CreateLayoutForPages = function($)
{
    var self = this
        , $button_standard = $('.js-create-layout-for-page')
        , $button_custom = $('.js-create-layout-for-post-custom');

    self.post = null;

    self.init = function(){
       // _.templateSettings.variable = "ddl";
        self.set_post();
        self.call_create_for_standard();
        self.handle_custom_post_types_dialog();
     //   $(document).on('ddl-editor-dialog-complete', self.handle_dialog_open_overrides );
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'ddl-create-dialog-opened', self.handle_dialog_open_overrides);
        // don't show button untill everything is complete
        _.defer(function(){
                $('.create-layout-for-page-wrap').show();
        });
    };

    self.call_create_for_standard = function()
    {
        $button_standard.on('click', $button_standard, function(event){
            event.preventDefault();
            self.open_create_dialog('one');
        });
    };

    self.open_create_dialog = function( who )
    {
        var undefined, data;

        if( who === undefined ) {
            data = undefined;
        } else {
            data = {
                who:who
            };
            data = _.extend( data, self.post );
        }
        DDLayout.new_layout_dialog.show_create_new_layout_dialog( undefined, null, null, data );
    };

    self.handle_dialog_open_overrides = function(dialog)
    {
        var $title = $('.js-new-layout-title');

            $title.val( DDLayout_settings_create.DDL_JS.new_layout_title_text );
            $('.js-create-new-layout').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');

    };

    self.set_post = function()
    {
        self.post = DDLayout_settings_create.DDL_JS.post;
    };

    self.openAssignToPostTypesDialog = function()
    {
        var template = $("#js-ddl-create-layout-for-post-types-selection").html();

        $("#js-ddl-create-layout-for-post-types-selection-wrap").html( _.template( template, self.post, DDLayout._templateSettings ) );

        $button_custom.on('click', $button_custom, function(event){
            event.preventDefault();

            jQuery.colorbox({
                href: '#js-ddl-create-layout-for-post-types-selection-wrap',
                inline: true,
                open: true,
                closeButton:false,
                fixed: true,
                top: false,

                onComplete: function() {
                    self.handle_continue_to_creation();
                },
                onCleanup: function() {

                }
            });
        });
    };

    self.handle_custom_post_types_dialog = function () {

        if( $button_custom.is('a') === false ) return;

        if ( $button_custom.is('a') ) {
            self.openAssignToPostTypesDialog();
        }
    };

    self.handle_continue_to_creation = function(){
        var $continue = $('.js-ddl-continue-to-layout-creation');

        $continue.one('click', function(event){
            event.preventDefault();
            var $radio = $('input[name="create_layout_for_post_type"]:checked'),
                who = $radio.val();

            self.open_create_dialog( who );
        });
    };



    self.init();
};

(function($){
    $(function(){
        var create_for_pages = {};
        DDLayout.CreateLayoutForPages.call(create_for_pages, $);
    });
}(jQuery));