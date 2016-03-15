DDLayout.ViewLayoutManager = function( layout_id, layout_name )
{
    var self = this
        , $button = jQuery('.js-view-layout')
        , id = layout_id
        , name = layout_name
        , $preview_link = jQuery('.js-layout-preview-link')
        , admin
        , layout_view
        , layout_model, save_state;

    self.init = function()
    {
           jQuery(document).on('mousedown', $button.selector, function(event){
                event.preventDefault();
               WPV_Toolset.Utils.loader.loadShow( jQuery(this).parent(), true).css({
                   float:'right',
                   position:'relative',
                   top:'4px'
               });

               admin = DDLayout.ddl_admin_page;
               layout_view = admin.instance_layout_view;
               layout_model = layout_view.model;
               save_state = admin._save_state;

               self.load_items(event );
           });

        self.handle_link_open();
    }

    self.load_items = function( event )
    {
        var params = {
            action:'view_layout_from_editor'
            , 'ddl-view-layout-nonce' : DDLayout_settings.DDL_JS['ddl-view-layout-nonce']
            , layout_id : id
        };

        WPV_Toolset.Utils.do_ajax_post(params, {
            success: function (response) {
                WPV_Toolset.Utils.loader.loadHide();
                self.route_actions( response, event );
            },
            error: function (response) {
                WPV_Toolset.Utils.loader.loadHide();
                WPV_Toolset.messages.container.wpvToolsetMessage({

                    text: response.error,
                    type: 'error',
                    stay: true,
                    close: false,
                    onOpen: function() {
                        jQuery('html').addClass('toolset-alert-active');
                    },
                    onClose: function() {
                        jQuery('html').removeClass('toolset-alert-active');
                    }
                });
            }
        });
    };

    self.handle_link_open = function(){
        jQuery(document).on('click', $preview_link.selector,function(e){
            self.handle_post_preview( e, jQuery(this).prop('href') );
        });
    };

    self.handle_post_preview = function(e, href){
        if( save_state.requires_save === false ){
            return true;
        } else {
            e.preventDefault();
            var template = _.template( jQuery('#js-virtual-form-tpl').html()),
                params = {
                    href:href
                },
                form = template(params);

            jQuery(e.target).append(form)

            jQuery('#js-layout-preview-json').val( JSON.stringify( layout_model.toJSON() ) );
            jQuery( '#js-virtual-form-preview' ).submit();
            jQuery( '#js-virtual-form-preview' ).remove();
            return false;
        }
    };

    self.route_actions = function( data, event )
    {
        if (data.hasOwnProperty('Data') && data.Data.length === 1 && data.Data[0].href != '#') {
            self.handleLink(data.Data[0], "#ddl-layout-not-assigned-to-any", data.message, event );
        }
        else if(data.hasOwnProperty('Data') && data.Data.length === 1 && data.Data[0].href == '#'){
            self.handle_message(data.no_preview_message, "#ddl-layout-not-assigned-to-any");
        }
        else if (data.hasOwnProperty('Data') && data.Data.length > 1) {
            self.handle_links(data.Data, "#ddl-layout-assigned-to-many");
        }
        else
        {
            self.handle_message(data.message, "#ddl-layout-not-assigned-to-any");
        }
    };

    self.handle_dialog= function( data, template_id )
    {
        var template = jQuery(template_id).html();

        jQuery("#js-view-layout-dialog-container").html( _.template(template, data ) );

        jQuery.colorbox({
            href: '#js-view-layout-dialog-container',
            inline: true,
            open: true,
            closeButton: false,
            fixed: true,
            top: false,

            onComplete: function () {

            },
            onCleanup: function () {

            }
        });
    };

    self.handle_links = function( data, template_id )
    {
        var links = {links:data, layout_name:name};
        self.handle_dialog( links, template_id );
    };

    self.handle_message = function( data, template_id ){
        var message = {message:data, layout_name:name};
        self.handle_dialog( message, template_id );
    };

    self.handleLink = function( data, template_id, message, event )
    {
        if( data.href == '' )
        {
            self.handle_message( message, template_id );
        }
        else
        {
            if( self.handle_post_preview(event, data.href) === true ){
                window.open( data.href );
            }

        }

    };

    self.init();
};