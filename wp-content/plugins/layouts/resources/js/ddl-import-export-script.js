var DDLayout = DDLayout || {};

(function($){
    $(function(){
        DDLayout.import_export = new DDLayout.ImportLayouts($);
    });
}(jQuery))

DDLayout.ImportLayouts = function($)
{
    var self = this,
        $button = $('#ddl-import'),
        $message = $('.import-layouts-messages'),
        $file = $('#upload-layouts-file');

    self.init = function()
    {
        self.check_file_is_there();
    };

    self.check_file_is_there = function()
    {
         $(document).on('click', $button.selector, function(event){

                if( $file.val() === '' )
                {
                    event.preventDefault();

                    $message.wpvToolsetMessage({
                        text: DDLayout_settings.DDL_JS.no_file_selected,
                        type: 'warning',
                        stay: true,
                        close: true,
                        onOpen: function() {
                            jQuery('html').addClass('toolset-alert-active');
                        },
                        onClose: function() {
                            jQuery('html').removeClass('toolset-alert-active');
                        }
                    });
                }
                else
                {
                    if( $message.data('has_message') ) $message.wpvToolsetMessage('destroy');
                }
         });
    };

    self.init();
};