if (typeof DDLayout == 'undefined') {
    DDLayout = {};
}

jQuery(document).ready(function($) {

    DDLayout.template_selector = new DDLayout.templateSelector($);

});

DDLayout.templateSelector = function($)
{
    var self = this;

    self.init = function() {

        if (jQuery('#page_template').length > 0) {
            // Combine the layout selection and template selection on
            // the "page" post edit.
            jQuery('#wpddl_template').hide();

            // Add drag and drop layout selector under the template selector.
            var html = '<div class="js-dd-layout-selector">';
            html += jQuery('.js-dd-layout-selector').html();
            html += '</div>';

            jQuery('.js-dd-layout-selector').remove();

            jQuery('#page_template').after(html);

            self._initialize_combined_combo();

            self._hide_separate_combos();
        } else {
            jQuery('#js-layout-template-name').on('change', self._handle_layout_change);

            self._handle_layout_change(null); // set intial state
        }

        self._show_template_warning();
        
        self._show_template_default_message();

        self.manage_if_layout_is_loop( jQuery('#js-layout-template-name option:selected') );

    };
    
    self.wpddl_special_dropdown_option = function(){
        alert('aaa');
        return false;
    }
    
    self._show_template_default_message = function(){
        /*$btn_default_msg = $('.js-wpddl-default-template-message');
        if ( $btn_default_msg.val() == 0 ){
            jQuery('#s2id_js-combined-layout-template-name').hide();
            jQuery('#s2id_js-combined-layout-template-name').after( '<span class="js-wpddl-default-page-template-text"><b>' +jQuery('#js-layout-template-name option:selected').text() +'</b></span><br>'+
            '<a href="#" class="js-select-another-page-template">'+ $btn_default_msg.data('message') +'</a>');
            jQuery('.js-select-another-page-template').on('click', self._show_layout_change_dropdown);
        }*/
    }
    
    self._show_layout_change_dropdown = function(){
        /*jQuery('#s2id_js-combined-layout-template-name').show();
        jQuery('.js-select-another-page-template').off('click', self._show_layout_change_dropdown);
        jQuery('.js-wpddl-default-page-template-text,.js-select-another-page-template').remove();
        return false;*/
    }

    self._handle_layout_change = function (event) {

        WPV_Toolset.Utils.eventDispatcher.trigger( 'ddl-layout-template-name-changed', jQuery('#js-layout-template-name').val() );

        var $editLayoutTempalte = jQuery('.js-edit-layout-template');
        
        var layout_id = jQuery('#js-layout-template-name option:selected').data('id');
        if (layout_id != '0') {
            $editLayoutTempalte.show();
            $editLayoutTempalte.attr('href', $editLayoutTempalte.data('href') + layout_id);
            self._disable_content_template(true);
        } else {
            jQuery('.js-edit-layout-template').hide();
            self._disable_content_template(false);
        }

        self._show_template_warning();

        if( event !== null )
        {            
            self.manage_if_layout_is_loop( jQuery('#js-layout-template-name option:selected') )
            self._bind_unsaved_warning();
        }else{
            self._current_layout = jQuery('#js-layout-template-name option:selected');
        }
        if ( jQuery('#js-layout-template-name option:selected').val() == self._current_layout.val() ){
             jQuery(window).unbind('beforeunload.layout-dropdown');
        }
    }
    
    jQuery('#publish').on('click', function(){
         jQuery(window).unbind('beforeunload.layout-dropdown');
    });
    
    self._bind_unsaved_warning = function(){
         jQuery(window).bind('beforeunload.layout-dropdown', function(){
             return postL10n.saveAlert;
         });
    }
    
    self._show_template_warning = function () {
        var warning = jQuery('#js-layout-template-name option:selected').data('ddl-warning');

        if (jQuery('#page_template').length > 0) {
            var template = jQuery('#page_template').val();
            if (jQuery.inArray(template, DDLayout_settings_editor.layout_templates) == -1) {
                // Don't show the warning because a template without layouts is selected.
                warning = '';
            }
        }

        jQuery('.js-layout-support-warning').html(warning);

        if (warning) {
            jQuery('.js-layout-support-warning').show();
        } else {
            jQuery('.js-layout-support-warning').hide();
        }
    }

    self._initialize_combined_combo = function () { 
        var selected_template = jQuery('#page_template').val();
        var force_layout = jQuery('#js-layout-template-name').find(":selected").data('force-layout');
        var selected_layout = jQuery('#js-layout-template-name').find(":selected").val();
        var $combinedLayoutTemplate = jQuery('#js-combined-layout-template-name');
        var $editLayoutTempalte = jQuery('.js-edit-layout-template');
        var show_more_link = false;
        var is_really_selected = jQuery('#js-layout-template-name').find(":selected").length && jQuery('#js-layout-template-name').find(":selected").data('object') && jQuery('#js-layout-template-name').find(":selected").data('object').is_really_selected;
        self.selected_combined = selected_template;
        var default_template = $('.js-wpddl-default-template-message').val();

        if( is_really_selected && selected_template === 'default' ){
            selected_template = 'page.php';
            jQuery('#page_template').val(selected_template);
        }

        jQuery('#page_template option').each( function () {
            var template = jQuery(this).val();
            var text = jQuery(this).text();

            if (jQuery.inArray(template, DDLayout_settings_editor.layout_templates) == -1) {
                // A template without a layout
                if ( !force_layout && selected_template == template ) {
                    $combinedLayoutTemplate.append('<option selected="selected" value="' + template + '">' + text + '</option>');
                    $editLayoutTempalte.hide();
                } else {
                    $combinedLayoutTemplate.append('<option value="' + template + '">' + text + '</option>');
                }
            } else {
                // A template with a layout

                if( adminpage === 'post-new-php' )
                {
                    if (force_layout && template == default_template ) {
                        // The layout is one that's been assigned to this post type.
                        // Select the first template that supports layouts
                        selected_template = template;
                        force_layout = false
                    }
                    var selected = selected_template;

                    jQuery('#page_template').val(selected); // Initialize the WP template selector.
                }
                else if( adminpage === 'post-php' )
                {
                    var selected = selected_template;
                }

                jQuery('#js-layout-template-name option').each( function () {

                    var selected_combined = selected_layout + ' in ' + selected;
                    self.selected_combined = selected_combined;
                    var combined = jQuery(this).val() + ' in ' + template;
                    var id = jQuery(this).data('id');
                    var warning = jQuery(this).data('ddl-warning');
                    var is_hidden = '';
                    var title = jQuery(this).text();

                    if (DDLayout_settings_editor.layout_templates.length > 1) {
                        title += ' in ' + text;
                    } else {
                        title = 'Layout - ' + title;
                    }
                    if ( template != default_template ){
                        is_hidden = 'class="option_hidden"';
                        show_more_link = true;
                    }

                    if ( combined == selected_combined ) {
                        $combinedLayoutTemplate.append('<option selected="selected" value="' + combined + '" data-id="' + id + '" data-ddl-warning="' + warning + '">' + title + '</option>');
                        $editLayoutTempalte.show();
                        $editLayoutTempalte.attr('href', $editLayoutTempalte.data('href') + id);
                    } else {
                        $combinedLayoutTemplate.append('<option '+ is_hidden +' value="' + combined + '" data-id="' + id + '" data-ddl-warning="' + warning + '">' + title + '</option>');
                    }
                });
            }
        });
        
        if ( show_more_link ){
            $combinedLayoutTemplate.append('<option class="wpddl-special-dropdown-option js-wpddl-special-dropdown-option" value="show-all-templates">' + $('.js-wpddl-default-template-message').data('message') + '</option>');
            ///$('.js-wpddl-special-dropdown-option').on('change', self.wpddl_special_dropdown_option);
        }

        $combinedLayoutTemplate.on('change', self._handle_combined_combo_change);
        $combinedLayoutTemplate.select2({
            'width': '100%',
            formatResult: self._format_select,
            formatSelection: self._format_select
        });
        jQuery('#js-combined-layout-template-name span').hide();

        if (jQuery('.js-layout-support-missing').length) {
            jQuery('.js-layout-support-missing').insertAfter($editLayoutTempalte);
        }

        self._handle_combined_combo_change(null);
    };

    self._format_select = function (state) {
        var data = state.text.split(' in ');

        if (data.length == 2) {
            if ( $('.js-wpddl-special-dropdown-option').text() !== ''){
                return '<i class="icon-layouts-logo ont-icon-16"></i>' + data[0];
            }else{
                return '<i class="icon-layouts-logo ont-icon-16"></i>' + data[0] + ' <span class="select-layout-template">in ' + data[1] + '<span>';
            }
        } else {
            return state.text.replace('Layout -', '<i class="icon-layouts-logo ont-icon-16"></i>');
        }
    }

    self._handle_combined_combo_change = function ( event ) {

        var selected = jQuery('#js-combined-layout-template-name option:selected').val();
        var data = selected.split(' in ');
        var $editLayoutTempalte = jQuery('.js-edit-layout-template');

        WPV_Toolset.Utils.eventDispatcher.trigger( 'ddl-layout-template-name-changed', data[0] );
        
        if ( selected == 'show-all-templates'){
            $('.js-wpddl-special-dropdown-option').remove();
            jQuery('#js-combined-layout-template-name').select2("destroy");

            jQuery('#js-combined-layout-template-name').select2({
                'width': '100%',
                formatResult: self._format_select,
                formatSelection: self._format_select}
            );
            jQuery('#js-combined-layout-template-name option').removeClass('option_hidden');
            $('#js-combined-layout-template-name').select2('val', self.selected_combined);
            $('#js-combined-layout-template-name').select2('open');
            $('.select2-results').scrollTop();
            
            return;
        }
        if (data.length == 2) {
            // Layout template
            jQuery('#page_template').val(data[1]);
            jQuery('#js-layout-template-name').val(data[0]);

            self._disable_content_template(true);

            if (data[0] != '0') {
                $editLayoutTempalte.show();
                $editLayoutTempalte.attr('href', $editLayoutTempalte.data('href') + jQuery('#js-combined-layout-template-name option:selected').data('id'));
            } else {
                jQuery('.js-edit-layout-template').hide();
            }
        } else {
            // WP template
            jQuery('#page_template').val(selected);
            jQuery('#js-layout-template-name').val('0');
            jQuery('.js-edit-layout-template').hide();

            self._disable_content_template(false);
        }


        if( event !== null )
        {
            self.manage_if_layout_is_loop( jQuery('#js-layout-template-name option:selected') )
        }

        self._show_template_warning();
    };

    self._disable_content_template = function (state) {

        if (state) {

            jQuery('select#views_template').hide();

            if (!jQuery('.js-ct-disable').length) {
                jQuery('<p class="toolset-alert toolset-alert-warning js-ct-disable">' +
                    DDLayout_settings_editor.strings.content_template_diabled +
                    '</p>').insertAfter('select#views_template');
            }
        } else {
            jQuery('select#views_template').show();

            if (jQuery('.js-ct-disable').length) {
                jQuery('.js-ct-disable').remove();
            }
        }
    }

    self._hide_separate_combos = function () {
        jQuery('#page_template').hide();
        jQuery('#js-layout-template-name').hide();
        jQuery('#page_template').prevUntil('#parent_id').each(function () {
            var html = jQuery(this).html();
            if (html.indexOf(ddl_old_template_text) != -1) {
                jQuery(this).hide();
            }
        });
    };

    self.manage_if_layout_is_loop = function( option )
    {
        var data = option.data('object')
            , box = jQuery('.js-dd-layout-selector')
            , error_container = jQuery('.js-display-errors');

        if( !data || typeof data === 'undefined' || data === null ){

            if( error_container.data('has_message' ) ){
                error_container.wpvToolsetMessage('destroy');
            }

            return;
        }

        if( data.layout_has_loop )
        {

            error_container.wpvToolsetMessage({
                text:   DDLayout_settings_editor.strings.layout_has_loop_cell,
                type:'warning',
                stay:true,
                close:true,
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function () {
                    jQuery('html').removeClass('toolset-alert-active');
                }
            });
        }
        else{
            error_container.wpvToolsetMessage('destroy');
        }
    }

    self.init();
};