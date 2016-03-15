/**
* views_shortcode_gui.js
*
* Contains helper functions for the popup GUI used to set Views shortcode attributes
*
* @since 1.7
* @package Views
*/

var WPViews = WPViews || {};

WPViews.ShortcodesGUI = function( $ ) {
	var self = this;
	
	// Parametric search
	self.ps_view_id = 0;
	self.ps_orig_id = 0;
	
	self.suggest_cache = {};
	self.shortcode_gui_insert = true;
	self.shortcode_gui_insert_count = 0;
	
	self.numeric_natural_pattern = /^[0-9]+$/;
	self.url_patern = /^(https?):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
	
	/**
     * Temporary dialog content to be displayed while the actual content is loading.
     *
     * It contains a simple spinner in the centre. I decided to implement styling directly, it will not be reused and
     * it would only bloat views-admin.css (jan).
     *
     * @type {HTMLElement}
     * @since 1.9
     */
    self.shortcodeDialogSpinnerContent = $(
        '<div style="min-height: 150px;">' +
            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
                '<div class="wpv-spinner ajax-loader"></div>' +
                '<p>' + wpv_shortcodes_gui_texts.loading_options + '</p>' +
            '</div>' +
        '</div>'
    );

	self.init = function() {
		if ( ! $('#js-wpv-shortcode-gui-dialog-container').length ) {
			$( 'body' ).append( '<div id="js-wpv-shortcode-gui-dialog-container" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container"></div>' );
			$( "#js-wpv-shortcode-gui-dialog-container" ).dialog({
				autoOpen: false,
				modal: true,
				minWidth: 450,
				show: { 
					effect: "blind", 
					duration: 800 
				},
				buttons:[
					{
						class: 'button-secondary js-wpv-shortcode-gui-close',
						text: wpv_shortcodes_gui_texts.wpv_close,
						click: function() {
							$( this ).dialog( "close" );
						}
					},
					{
						class: 'button-primary js-wpv-shortcode-gui-insert',
						text: wpv_shortcodes_gui_texts.wpv_insert_shortcode,
						click: function() {
							self.wpv_insert_shortcode();
						}
					}
				]
			});
			
			$( 'body' ).append( '<div id="js-wpv-view-form-gui-dialog-container" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container"></div>' );
			$( "#js-wpv-view-form-gui-dialog-container" ).dialog({
				autoOpen: false,
				modal: true,
				minWidth: 450,
				show: { 
					effect: "blind", 
					duration: 800 
				},
				buttons:[
					{
						class: 'button-secondary',
						text: wpv_shortcodes_gui_texts.wpv_close,
						click: function() {
							$( this ).dialog( "close" );
						}
					},
					{
						class: 'button-secondary js-wpv-insert-view-form-prev',
						text: wpv_shortcodes_gui_texts.wpv_previous,
						click: function() {
							self.wpv_insert_view_dialog_prev();
						}
					},
					{
						class: 'button-primary js-wpv-insert-view-form-action',
						text: wpv_shortcodes_gui_texts.wpv_insert_view_shortcode,
						click: function() {
							self.wpv_insert_view_form_action();
						}
					}
				]
			});
		}
	};
	
	//-----------------------------------------
	// Parametric search
	//-----------------------------------------
	
	self.wpv_insert_view_form_popup = function( view_id, view_title, orig_id, nonce ) {
		self.ps_view_id = view_id;
		self.ps_orig_id = orig_id;
		
		//
        // Build AJAX url for displaying the dialog
        //
        var url = ajaxurl + '?';
		//url_extra_data = '';
		//ajaxurl + '?_wpnonce=' + nonce + '&action=wpv_view_form_popup&view_id=' + view_id + '&orig_id=' + orig_id,
		
        url += '_wpnonce=' + nonce;
        url += '&action=wpv_view_form_popup';
        url += '&view_id=' + view_id;
		url += '&orig_id=' + orig_id;
		url += '&view_title=' + view_title;
		
		//url_extra_data = self.filter_dialog_ajax_data( shortcode );
		//url += url_extra_data;

        //
        // Calculate height
        //
        var dialog_height = $(window).height() - 100;
		
		// Show the "empty" dialog with a spinner while loading dialog content
        var dialog = $('#js-wpv-view-form-gui-dialog-container').dialog('open').dialog({
            title: view_title,
            minWidth: 650,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
			position: { my: "center top+50", at: "center top", of: window }
        });

		$( '.js-wpv-insert-view-form-prev' ).hide();
        dialog.html( self.shortcodeDialogSpinnerContent );
		
		//
        // Do AJAX call
        //
        $.ajax({
            url: url,
            success: function( data ) {
                dialog.html( data );
            }
        });
	};
	
	self.wpv_insert_view_form_action = function() {
		var form_name = $( '#js-wpv-view-form-gui-dialog-view-title' ).val(),
		display = $( '.js-wpv-insert-view-form-display:checked' ).val(),
		target = $( '.js-wpv-insert-view-form-target:checked' ).val(),
		set_target = $( '.js-wpv-insert-view-form-target-set:checked' ).val(),
		set_target_id = $( '.js-wpv-insert-view-form-target-set-existing-id' ).val(),
		results_helper_container = $( '.js-wpv-insert-form-workflow-help-box' ),
		results_helper_container_after = $( '.js-wpv-insert-form-workflow-help-box-after' );
		
		if ( display == 'both' ) {
			window.icl_editor.insert('[wpv-view name="' + form_name + '"]');
			if ( 
				results_helper_container.length > 0 
				&& results_helper_container.hasClass( 'js-wpv-insert-form-workflow-help-box-for-' + self.ps_view_id ) 
			) {
				results_helper_container.fadeOut( 'fast' );
			}
			if ( 
				results_helper_container_after.length > 0 
				&& results_helper_container_after.hasClass( 'js-wpv-insert-form-workflow-help-box-for-after-' + self.ps_view_id ) 
			) {
				results_helper_container_after.show();
			}
			$( '#js-wpv-view-form-gui-dialog-container' ).dialog('close');
		} else if ( display == 'results' ) {
			window.icl_editor.insert('[wpv-view name="' + form_name + '" view_display="layout"]');
			if ( 
				results_helper_container.length > 0 
				&& results_helper_container.hasClass( 'js-wpv-insert-form-workflow-help-box-for-' + self.ps_view_id ) 
			) {
				results_helper_container.fadeOut( 'fast' );
			}
			if ( 
				results_helper_container_after.length > 0 
				&& results_helper_container_after.hasClass( 'js-wpv-insert-form-workflow-help-box-for-after-' + self.ps_view_id ) 
			) {
				results_helper_container_after.show();
			}
			$( '#js-wpv-view-form-gui-dialog-container' ).dialog('close');
		} else if ( display == 'form' ) {
			if ( $( '.js-wpv-insert-view-form-action' ).hasClass( 'js-wpv-insert-view-form-dialog-steptwo' ) ) {
				if ( target == 'self' ) {
					window.icl_editor.insert('[wpv-form-view name="' + form_name + '" target_id="self"]');
					if ( results_helper_container.length > 0 ) {
						var results_shortcode = '<code>[wpv-view name="' + form_name + '" view_display=layout"]</code>';
						results_helper_container.find( '.js-wpv-insert-view-form-results-helper-name' ).html( form_name );
						results_helper_container.find( '.js-wpv-insert-view-form-results-helper-shortcode' ).html( results_shortcode );
						results_helper_container.addClass( 'js-wpv-insert-form-workflow-help-box-for-' + self.ps_view_id ).fadeIn( 'fast' );
					}
				} else {
					window.icl_editor.insert('[wpv-form-view name="' + form_name + '" target_id="' + set_target_id + '"]');
				}
				$( '.js-wpv-insert-view-form-action' ).removeClass( 'js-wpv-insert-view-form-dialog-steptwo' );
				$( '#js-wpv-view-form-gui-dialog-container' ).dialog('close');
			} else {
				$( '.js-wpv-insert-view-form-action' ).addClass( 'js-wpv-insert-view-form-dialog-steptwo' );
				$( '.js-wpv-insert-view-form-action .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_insert_view_shortcode );
				$( '.js-wpv-insert-view-form-prev' ).show();
				$( '.js-wpv-insert-view-form-display-container' ).hide();
				$( '.js-wpv-insert-view-form-target-container' ).show();
				if ( target == 'self' ) {
					$( '.js-wpv-insert-view-form-action' ).addClass( 'button-primary' ).removeClass( 'button-secondary' ).prop( 'disabled', false );
				} else {
					if ( set_target == 'existing' && set_target_id != '' ) {
						$( '.js-wpv-insert-view-form-target-set-actions' ).show();
					}
					$( '.js-wpv-insert-view-form-action' ).removeClass( 'button-primary' ).addClass( 'button-secondary' ).prop( 'disabled', true );
				}
			}
		}
	};
	
	/**
	* Suggest for parametric search target
	*/
	
	$( document ).on( 'focus', '.js-wpv-insert-view-form-target-set-existing-title:not(.js-wpv-shortcode-gui-suggest-inited)', function() {
		var thiz = $( this );
		thiz
			.addClass( 'js-wpv-shortcode-gui-suggest-inited' )
			.suggest(ajaxurl + '?action=wpv_suggest_form_targets', {
				resultsClass: 'ac_results wpv-suggest-results',
				onSelect: function() {
					var t_value = this.value,
					t_split_point = t_value.lastIndexOf(' ['),
					t_title = t_value.substr( 0, t_split_point ),
					t_extra = t_value.substr( t_split_point ).split('#'),
					t_id = t_extra[1].replace(']', '');
					$( '.js-wpv-filter-form-help' ).hide();
					$('.js-wpv-insert-view-form-target-set-existing-title').val( t_title );
					t_edit_link = $('.js-wpv-insert-view-form-target-set-existing-link').data( 'editurl' );
					t_view_id = $('.js-wpv-insert-view-form-target-set-existing-link').data( 'viewid' );
					t_orig_id = $('.js-wpv-insert-view-form-target-set-existing-link').data('origid');
					$( '.js-wpv-insert-view-form-target-set-existing-link' ).attr( 'href', t_edit_link + t_id + '&action=edit&completeview=' + t_view_id + '&origid=' + t_orig_id );
					$( '.js-wpv-insert-view-form-target-set-existing-id' ).val( t_id ).trigger( 'change' );
					$( '.js-wpv-insert-view-form-target-set-actions' ).show();
				}
			});
	});
	
	/*
	* Adjust the action button text copy based on the action to perform
	*/
	
	$( document ).on( 'change', '.js-wpv-insert-view-form-display', function() {
		var display = $( '#js-wpv-view-form-gui-dialog-container .js-wpv-insert-view-form-display:checked' ).val();
		if ( display == 'form' ) {
			$( '.js-wpv-insert-view-form-action .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_next );
		} else {
			$( '.js-wpv-insert-view-form-action .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_insert_view_shortcode );
		}
	});
	
	/*
	* Control the back button in the two-step setup
	*/
	
	self.wpv_insert_view_dialog_prev = function() {
		$( '.js-wpv-insert-view-form-target-container' ).hide();
		$( '.js-wpv-insert-view-form-display-container' ).show();
		$( '.js-wpv-insert-view-form-action' )
			.removeClass( 'js-wpv-insert-view-form-dialog-steptwo button-secondary' )
			.addClass( 'button-primary' )
			.prop( 'disabled', false );
		$( '.js-wpv-insert-view-form-action .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_next );
		$( '.js-wpv-insert-view-form-prev' ).hide();
	};
	
	/*
	* Adjust the GUI when inserting just the form, based on the target options - target this or other page
	*/
	
	$( document ).on( 'change', '.js-wpv-insert-view-form-target', function() {
		var target = $( '.js-wpv-insert-view-form-target:checked' ).val(),
		set_target = $( '.js-wpv-insert-view-form-target-set:checked' ).val();
		if ( target == 'self' ) {
			$( '.js-wpv-insert-view-form-target-set-container' ).hide();
			$( '.js-wpv-insert-view-form-action' )
				.addClass( 'button-primary' )
				.prop( 'disabled', false );
		} else if ( target == 'other' ) {
			$( '.js-wpv-insert-view-form-target-set-container' ).fadeIn( 'fast' );
			if ( 
				set_target == 'existing' 
				&& $( '.js-wpv-insert-view-form-target-set-existing-id' ).val() != '' 
			) {
				$( '.js-wpv-insert-view-form-target-set-actions' ).show();
			}
			$( '.js-wpv-insert-view-form-action' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		}
	});
	
	$( document ).on( 'click', '.js-wpv-insert-view-form-target-set-discard', function( e ) {
		e.preventDefault();
		$( '.js-wpv-insert-view-form-action' )
			.addClass( 'button-primary' )
			.removeClass( 'button-secondary' )
			.prop( 'disabled', false );
		$( '.js-wpv-insert-view-form-target-set-actions' ).hide();
	});
	
	$( document ).on( 'click', '.js-wpv-insert-view-form-target-set-existing-link', function() {
		$( '.js-wpv-insert-view-form-action' )
			.addClass( 'button-primary' )
			.removeClass( 'button-secondary' )
			.prop( 'disabled', false );
		$( '.js-wpv-insert-view-form-target-set-actions' ).hide();
	});
	
	/*
	* Adjust the GUI when inserting just the form and targeting another page, based on the target options - target existing or new page
	*/
	
	$( document ).on( 'change', '.js-wpv-insert-view-form-target-set', function() {
		var set_target = $( '.js-wpv-insert-view-form-target-set:checked' ).val();
		if ( set_target == 'create' ) {
			$( '.js-wpv-insert-view-form-target-set-existing-extra' ).hide();
			$( '.js-wpv-insert-view-form-target-set-create-extra' ).fadeIn( 'fast' );
			$( '.js-wpv-insert-view-form-action' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else if ( set_target == 'existing' ) {
			$( '.js-wpv-insert-view-form-target-set-create-extra' ).hide();
			$( '.js-wpv-insert-view-form-target-set-existing-extra' ).fadeIn( 'fast' );
			$( '.js-wpv-insert-view-form-action' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
			if ( $( '.js-wpv-insert-view-form-target-set-existing-id' ).val() != '' ) {
				$( '.js-wpv-insert-view-form-target-set-actions' ).show();
			}
		}
	});
	
	/*
	* Adjust values when editing the target page title - clean data and mark this as unfinished
	*/
	
	$( document ).on('change input cut paste', '.js-wpv-insert-view-form-target-set-existing-title', function() {
		$( '.js-wpv-insert-view-form-target-set-actions' ).hide();
		$( '.js-wpv-insert-view-form-target-set-existing-link' ).attr( 'data-targetid', '' );
		$('.js-wpv-insert-view-form-target-set-existing-id')
			.val( '' )
			.trigger( 'manchange' );
	});
	
	/*
	* Disable the insert button when doing any change in the existing title textfield
	*
	* We use a custom event 'manchange' as in "manual change"
	*/
	
	$( document ).on( 'manchange', '.js-wpv-insert-view-form-target-set-existing-id', function() {
		$( '.js-wpv-insert-view-form-action' )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' )
			.prop( 'disabled', true );
	});
	
	/*
	* Adjust GUI when creating a target page, based on the title value
	*/
	
	$( document ).on( 'change input cut paste', '.js-wpv-insert-view-form-target-set-create-title', function() {
		if ( $( '.js-wpv-insert-view-form-target-set-create-title' ).val() == '' ) {
			$( '.js-wpv-insert-view-form-target-set-create-action' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$( '.js-wpv-insert-view-form-target-set-create-action' )
				.prop( 'disabled', false )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' );
		}
	});
	
	/*
	* AJAX action to create a new target page
	*/

	$( document ).on( 'click', '.js-wpv-insert-view-form-target-set-create-action', function() {
		var thiz = $( this ),
		thiz_existing_radio = $( '.js-wpv-insert-view-form-target-set[value="existing"]' ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertAfter( thiz ).show();
		data = {
			action: 'wpv_create_form_target_page',
			post_title: $( '.js-wpv-insert-view-form-target-set-create-title' ).val(),
			_wpnonce: thiz.data( 'nonce' )
		};
		$.ajax({
			url:ajaxurl,
			data:data,
			success:function( response ) {
				decoded_response = $.parseJSON( response );
				if ( decoded_response.result == 'error' ) {
					
				} else {
					$( '.js-wpv-insert-view-form-target-set-existing-title' ).val( decoded_response.page_title );
					$( '.js-wpv-insert-view-form-target-set-existing-id' ).val( decoded_response.page_id );
					t_edit_link = $('.js-wpv-insert-view-form-target-set-existing-link').data( 'editurl' );
					$('.js-wpv-insert-view-form-target-set-existing-link')
							.attr( 'href', t_edit_link + decoded_response.page_id + '&action=edit&completeview=' + self.ps_view_id + '&origid=' + self.ps_orig_id );
					thiz_existing_radio
						.prop( 'checked', true )
						.trigger( 'change' );
					$( '.js-wpv-insert-view-form-target-set-actions' ).show();
				}
			},
			error: function ( ajaxContext ) {
				
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});
	});
	
	// Close the finished help boxes
	
	$( document ).on( 'click', '.js-wpv-insert-form-workflow-help-box-close', function( e ) {
		e.preventDefault();
		$( this ).closest( '.js-wpv-insert-form-workflow-help-box, .js-wpv-insert-form-workflow-help-box-after' ).hide();
	});
	
	//-----------------------------------------
	// Generic shortcodes API GUI
	//-----------------------------------------

	/**
	 * Display a dialog for inserting a specific Views shortcode.
	 *
     * todo explain parameters
     * @param shortcode
     * @param {string} title Dialog title.
     * @param params
     * @param nonce
     * @param object
     *
	 * @since 1.9
	 */
	self.wpv_insert_popup = function( shortcode, title, params, nonce, object ) {

        //
        // Build AJAX url for displaying the dialog
        //
        var url = ajaxurl + '?',
		url_extra_data = '';
		
        url += '_wpnonce=' + nonce;
        url += '&action=wpv_shortcode_gui_dialog_create';
        url += '&shortcode=' + shortcode;
        url += '&post_id=' + parseInt($(object).data('post-id'));
		
		url_extra_data = self.filter_dialog_ajax_data( shortcode );
		
		url += url_extra_data;

        //
        // Calculate height
        //
        var dialog_height = $(window).height() - 100;


        // Show the "empty" dialog with a spinner while loading dialog content
        var dialog = $('#js-wpv-shortcode-gui-dialog-container').dialog('open').dialog({
            title: title,
            width: 770,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
			position: { my: "center top+50", at: "center top", of: window }
        });
		
		self.manage_dialog_button_labels();

        dialog.html( self.shortcodeDialogSpinnerContent );
		
        //
        // Do AJAX call
        //
        $.ajax({
            url: url,
            success: function (data) {

                dialog.html(data);

                $('.js-wpv-shortcode-gui-tabs')
                    .tabs({
						beforeActivate: function( event, ui ) {
							var valid = self.validate_shortcode_attributes( ui.oldPanel );
							if ( ! valid ) {
								event.preventDefault();
								ui.oldTab.focus();
							}
						}
					})
                    .addClass('ui-tabs-vertical ui-helper-clearfix')
                    .removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
				$('#js-wpv-shortcode-gui-dialog-tabs ul, #js-wpv-shortcode-gui-dialog-tabs li').removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');

                //
                // After open dialog
                //
                self.after_open_dialog(shortcode, title, params, nonce, object);

                //
                // Custom combo management
                //
                $('.js-wpv-shortcode-gui-attribute-custom-combo').each(function () {
                    var combo_parent = $(this).closest('.js-wpv-shortcode-gui-attribute-wrapper'),
                        combo_target = $('.js-wpv-shortcode-gui-attribute-custom-combo-target', combo_parent);
                    if ($('[value=custom-combo]:checked', combo_parent).length) {
                        $combo_target.show();
                    }
                    $('[type=radio]', combo_parent).on('change', function () {
                        var thiz_radio = $(this);
                        if (
                            thiz_radio.is(':checked')
                            && 'custom-combo' == thiz_radio.val()
                        ) {
                            combo_target.slideDown('fast');
                        } else {
                            combo_target.slideUp('fast');
                        }
                    });
                });
            }
        });
	};
	
	/**
	* filter_dialog_ajax_data
	*
	* Filter the empty extra string added to the request to create the dialog GUI, so we can pass additional parameters for some shortcodes.
	*
	* @param shortcode The shortcode to which the dialog is being created.
	*
	* @return ajax_extra_data
	*
	* @since 1.9
	*/
	
	self.filter_dialog_ajax_data = function( shortcode ) {
		var ajax_extra_data = '';
		switch( shortcode ) {
			case 'wpv-post-body':
				if ( 
					typeof WPViews.ct_edit_screen != 'undefined' 
					&& typeof WPViews.ct_edit_screen.ct_data != 'undefined'
					&& typeof WPViews.ct_edit_screen.ct_data.id != 'undefined'
				) {
					ajax_extra_data = '&wpv_suggest_wpv_post_body_view_template_exclude=' + WPViews.ct_edit_screen.ct_data.id;
				}
				break;
		}
		return ajax_extra_data;
	};


	/**
	* after_open_dialog
	*
	* @since 1.9
	*/
	self.after_open_dialog = function( shortcode, title, params, nonce, object ) {
		self.manage_fixed_initial_params( params );
		self.manage_special_cases( shortcode );
		self.manage_suggest_cache();
	};
	
	/**
	* manage_dialog_button_labels
	*
	* Adjusts the dialog button labels for usage on Fields and Views or Loop Wizard scenarios.
	*
	* @since 1.9
	*/
	
	self.manage_dialog_button_labels = function() {
		if ( self.shortcode_gui_insert ) {
			$( '.js-wpv-shortcode-gui-close .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_close );
			$( '.js-wpv-shortcode-gui-insert .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_insert_shortcode );
		} else {
			$( '.js-wpv-shortcode-gui-close .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_cancel );
			$( '.js-wpv-shortcode-gui-insert .ui-button-text' ).html( wpv_shortcodes_gui_texts.wpv_save_settings );
		}
	};
	
	/**
	* manage_fixed_initial_params
	*
	* @since 1.9
	*/
	
	self.manage_fixed_initial_params = function( params ) {
		for ( var item in params ) {
			$( '.wpv-dialog' ).prepend( '<span class="wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper" data-attribute="' + item + '" data-type="param"><input type="hidden" name="' + item + '" value="' + params[ item ].value + '" disabled="disabled" /></span>' );
		}
	};
	
	/**
	* manage_special_cases
	*
	* @since 1.9
	*/
	
	self.manage_special_cases = function( shortcode ) {
		switch ( shortcode ) {
			case 'wpv-post-author':
				self.manage_wpv_post_author_format_show_relation();
				break;
			case 'wpv-post-taxonomy':
				self.manage_wpv_post_taxonomy_format_show_relation();
				break;
			case 'wpv-post-featured-image':
				self.manage_wpv_post_featured_image_output_show_class();
				break;
		}
	};
	
	/**
	* manage_suggest_cache
	*
	* Populate suggest fields from cache if available
	*
	* @since 1.9
	*/
	
	self.manage_suggest_cache = function() {
		$( '.js-wpv-shortcode-gui-suggest' ).each( function() {
			var thiz_inner = $( this ),
			action_inner = '';
			if ( thiz_inner.data('action') != '' ) {
				action_inner = thiz_inner.data('action');
				if ( self.suggest_cache.hasOwnProperty( action_inner ) ) {
					thiz_inner
						.val( self.suggest_cache[action_inner] )
						.trigger( 'change' );
				}
			}
		});
	};
	
	/**
	* Init suggest on suggest attributes
	*
	* @since 1.9
	*/
	
	$( document ).on( 'focus', '.js-wpv-shortcode-gui-suggest:not(.js-wpv-shortcode-gui-suggest-inited)', function() {
		var thiz = $( this ),
		action = '';
		if ( thiz.data('action') != '' ) {
			action = thiz.data('action');
			ajax_extra_data = self.filter_suggest_ajax_data( action );
			thiz
				.addClass( 'js-wpv-shortcode-gui-suggest-inited' )
				.suggest(ajaxurl + '?action=' + action + ajax_extra_data, {
					resultsClass: 'ac_results wpv-suggest-results',
					onSelect: function() {
						self.suggest_cache[action] = this.value;
					}
				});
		}
	});
	
	/**
	* filter_suggest_ajax_data
	*
	* Filter the empty extra string added to the suggest request, so we can pass additional parameters for some shortcodes.
	*
	* @param action The suggest action to perform.
	*
	* @return ajax_extra_data
	*
	* @since 1.9
	*/
	
	self.filter_suggest_ajax_data = function( action ) {
		var ajax_extra_data = '';
		switch( action ) {
			case 'wpv_suggest_wpv_post_body_view_template':
				if ( 
					typeof WPViews.ct_edit_screen != 'undefined' 
					&& typeof WPViews.ct_edit_screen.ct_data != 'undefined'
					&& typeof WPViews.ct_edit_screen.ct_data.id != 'undefined'
				) {
					ajax_extra_data = '&wpv_suggest_wpv_post_body_view_template_exclude=' + WPViews.ct_edit_screen.ct_data.id;
				}
				break;
		}
		return ajax_extra_data;
	};
	
	/**
	* Manage post selector GUI
	*
	* @since 1.9
	*/
	
	$( document ).on( 'change', 'input.js-wpv-shortcode-gui-post-selector', function() {
		var thiz = $( this ),
		checked = thiz.val();
		$('.js-wpv-shortcode-gui-post-selector-has-related').each( function() {
			var thiz_inner = $( this );
			if ( $( 'input.js-wpv-shortcode-gui-post-selector:checked', thiz_inner ).val() == checked ) {
				$( '.js-wpv-shortcode-gui-post-selector-is-related', thiz_inner ).slideDown( 'fast' );
			} else {
				$( '.js-wpv-shortcode-gui-post-selector-is-related', thiz_inner ).slideUp( 'fast' );
			}
		});
	});
	
	/**
	* Manage placeholders: should be removed when focusing on a textfield, added back on blur
	*
	* @since 1.9
	*/
	
	$( document )
		.on( 'focus', '.js-wpv-shortcode-gui-attribute-has-placeholder', function() {
			var thiz = $( this );
			thiz.attr( 'placeholder', '' );
		})
		.on( 'blur', '.js-wpv-shortcode-gui-attribute-has-placeholder', function() {
			var thiz = $( this );
			if ( thiz.data( 'placeholder' ) ) {
				thiz.attr( 'placeholder', thiz.data( 'placeholder' ) );
			}
		});
	
	/**
	* validate_shortcode_attributes
	*
	* Validate method
	*
	* @since 1.9
	*/
	
	self.validate_shortcode_attributes = function( evaluate_container ) {
		self.clear_validate_messages();
		var valid = true,
		error_container = $( '#js-wpv-shortcode-gui-dialog-container' ).find( '.js-wpv-filter-toolset-messages' );
		valid = self.manage_required_attributes( evaluate_container );
		evaluate_container.find( 'input:text' ).each( function() {
			var thiz = $( this ),
			thiz_val = thiz.val(),
			thiz_type = thiz.data( 'type' ),
			thiz_message = '',
			thiz_valid = true;
			if ( ! thiz.hasClass( 'js-toolset-shortcode-gui-invalid-attr' ) ) {
				switch ( thiz_type ) {
					case 'number':
						if ( 
							self.numeric_natural_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_number_invalid;
						}
						break;
					case 'url':
						if ( 
							self.url_patern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_url_invalid;
						}
						break;
				}
				if ( ! thiz_valid ) {
					valid = false;
					error_container
						.wpvToolsetMessage({
							text: thiz_message,
							type: 'error',
							inline: false,
							stay: true
						});
					// Hack to allow more than one error message per filter
					error_container
						.data( 'message-box', null )
						.data( 'has_message', false );
				}
			}
		});
		// Special case: post selector tab
		if (
			$( '.js-wpv-shortcode-gui-post-selector:checked', evaluate_container ).length > 0 
			&& 'post_id' == $( '.js-wpv-shortcode-gui-post-selector:checked', evaluate_container ).val() 
		) {
			var post_selection = $( '[name=specific_post_id]', evaluate_container ),
			post_selection_id = post_selection.val(),
			post_selection_valid = true,
			post_selection_message = '';
			if ( '' == post_selection_id ) {
				post_selection_valid = false;
				post_selection.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				post_selection_message = wpv_shortcodes_gui_texts.attr_empty;
			} else if ( self.numeric_natural_pattern.test( post_selection_id ) == false ) {
				post_selection_valid = false;
				post_selection.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				post_selection_message = wpv_shortcodes_gui_texts.attr_number_invalid;
			}
			if ( ! post_selection_valid ) {
				valid = false;
				error_container
					.wpvToolsetMessage({
						text: post_selection_message,
						type: 'error',
						inline: false,
						stay: true
					});
				// Hack to allow more than one error message per filter
				error_container
					.data( 'message-box', null )
					.data( 'has_message', false );
			}
		}
		return valid;
	};
	
	$( document ).on( 'change keyup input cut paste', '#js-wpv-shortcode-gui-dialog-container input, #js-wpv-shortcode-gui-dialog-container select', function() {
		$( this ).removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
		$( '#js-wpv-shortcode-gui-dialog-container' )
			.find('.toolset-alert-error').not( '.js-wpv-permanent-alert-error' )
			.each( function() {
				$( this ).remove();
			});
	});
	
	self.clear_validate_messages = function() {
		$( '#js-wpv-shortcode-gui-dialog-container' )
			.find('.toolset-alert-error').not( '.js-wpv-permanent-alert-error' )
			.each( function() {
				$( this ).remove();
			});
	};
	
	/**
	* manage_required_attributes
	*
	* @since 1.9
	*/
	
	self.manage_required_attributes = function( evaluate_container ) {
		var valid = true,
		error_container = $( '#js-wpv-shortcode-gui-dialog-container' ).find( '.js-wpv-filter-toolset-messages' );
		evaluate_container.find( '.js-shortcode-gui-field.js-wpv-shortcode-gui-required' ).each( function() {
			var thiz = $( this ),
			thiz_valid = true,
			thiz_parent = thiz.closest('.js-wpv-shortcode-gui-attribute-custom-combo');
			if ( thiz_parent.length ) {
				if ( 
					$( '[value=custom-combo]:checked', thiz_parent ).length 
					&& thiz.val() == ''
				) {
					thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
					thiz_valid = false;
				}
			} else {
				if ( thiz.val() == '' ) {
					thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
					thiz_valid = false;
				}
			}
			if ( ! thiz_valid ) {
				valid = false;
				error_container
					.wpvToolsetMessage({
						text: wpv_shortcodes_gui_texts.attr_empty,
						type: 'error',
						inline: false,
						stay: true
					});
				// Hack to allow more than one error message per filter
				error_container
					.data( 'message-box', null )
					.data( 'has_message', false );
			}
		});
		return valid;
	};

	/**
	* wpv_insert_shortcode
	*
	* Insert shortcode to active editor
	*
	* @since 1.9
	*/

	self.wpv_insert_shortcode = function() {
		var shortcode_name = $('.js-wpv-shortcode-gui-shortcode-name').val(),
		shortcode_attribute_key,
		shortcode_attribute_value,
		shortcode_attribute_default_value,
		shortcode_attribute_string = '',
		shortcode_attribute_values = {},
		shortcode_content = '',
		shortcode_to_insert = '',
		shortcode_valid = self.validate_shortcode_attributes( $( '#js-wpv-shortcode-gui-dialog-container' ) );
		if ( ! shortcode_valid ) {
			return;
		}
		$( '.js-wpv-shortcode-gui-attribute-wrapper', '#js-wpv-shortcode-gui-dialog-container' ).each( function() {
			var thiz_attribute_wrapper = $( this ),
			shortcode_attribute_key = thiz_attribute_wrapper.data('attribute');
			switch ( thiz_attribute_wrapper.data('type') ) {
				case 'post':
					shortcode_attribute_value = $( '.js-wpv-shortcode-gui-post-selector:checked', thiz_attribute_wrapper ).val();
					switch( shortcode_attribute_value ) {
						case 'current':
							shortcode_attribute_value = false;
							break;
						case 'parent':
							if ( shortcode_attribute_value ) {
								shortcode_attribute_value = '$' + shortcode_attribute_value;
							}
							break;
						case 'related':
							shortcode_attribute_value = $( '[name=related_post]:checked', thiz_attribute_wrapper ).val();
							if ( shortcode_attribute_value ) {
								shortcode_attribute_value = '$' + shortcode_attribute_value;
							}
							break;
						case 'post_id':
							shortcode_attribute_value = $( '[name=specific_post_id]', thiz_attribute_wrapper ).val();
						default:
					}
					break;
				case 'select':
					shortcode_attribute_value = $('option:checked', thiz_attribute_wrapper ).val();
					break;
				case 'radio':
					shortcode_attribute_value = $('input:checked', thiz_attribute_wrapper ).val();
					if ( 'custom-combo' == shortcode_attribute_value ) {
						shortcode_attribute_value = $('.js-wpv-shortcode-gui-attribute-custom-combo-target', $('input:checked', thiz_attribute_wrapper ).closest('.js-wpv-shortcode-gui-attribute-custom-combo')).val();
					}
					break;
				case 'checkbox':
					shortcode_attribute_value = $('input:checked', thiz_attribute_wrapper ).val();
					break;
				default:
					shortcode_attribute_value = $('input', thiz_attribute_wrapper ).val();
			}
			
			shortcode_attribute_default_value = thiz_attribute_wrapper.data('default');
			/**
			* Fix true/false from data attribute for shortcode_attribute_default_value
			*/
			if ( 'boolean' == typeof shortcode_attribute_default_value ) {
				shortcode_attribute_default_value = shortcode_attribute_default_value ? 'true' :'false';
			}
			/**
			* Filter value
			*/
			shortcode_attribute_value = self.filter_computed_attribute_value( shortcode_name, shortcode_attribute_key, shortcode_attribute_value );
			/**
			* Add to the shortcode_attribute_string string
			*/
			if ( 
				shortcode_attribute_value 
				&& shortcode_attribute_value != shortcode_attribute_default_value 
			) {
				shortcode_attribute_string += ' ' + shortcode_attribute_key + '="' + shortcode_attribute_value + '"';
				shortcode_attribute_values[shortcode_attribute_key] = shortcode_attribute_value;
			}
		});
		shortcode_to_insert = '[' + shortcode_name + shortcode_attribute_string + ']';
		/**
		* Shortcodes with content
		*/
		if ( $( '.js-wpv-shortcode-gui-content' ).length > 0 ) {
			shortcode_content = $( '.js-wpv-shortcode-gui-content' ).val();
			/**
			* Filter shortcode content
			*/
			shortcode_content = self.filter_computed_content( shortcode_name, shortcode_content, shortcode_attribute_values );
			shortcode_to_insert += shortcode_content;
			shortcode_to_insert += '[/' + shortcode_name + ']';
		}
		/**
		* Close, insert if needed and fire custom event
		*/
		$('#js-wpv-shortcode-gui-dialog-container').dialog('close');
		if ( self.shortcode_gui_insert ) {
			window.icl_editor.insert( shortcode_to_insert );
		}
		$( document ).trigger( 'js_event_wpv_shortcode_inserted', [ shortcode_name, shortcode_content, shortcode_attribute_values, shortcode_to_insert ] );
	};
	
	$( document ).on( 'js_event_wpv_shortcode_inserted', function() {
		self.shortcode_gui_insert_count = self.shortcode_gui_insert_count + 1;
	});
	
	//--------------------------------
	// Special cases
	//--------------------------------
	
	/**
	* wpv-post-author management
	* Handle the change in format that shows/hides the show attribute
	*
	* @since 1.9
	*/
	
	$( document ).on( 'change', '#wpv-post-author-format .js-shortcode-gui-field', function() {
		self.manage_wpv_post_author_format_show_relation();
	});
	
	self.manage_wpv_post_author_format_show_relation = function() {
		if ( $( '#wpv-post-author-format' ).length ) {
			if ( 'meta' == $( '.js-shortcode-gui-field:checked', '#wpv-post-author-format' ).val() ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-meta', '#wpv-post-author-display-options' ).slideDown( 'fast' );
			} else {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-meta', '#wpv-post-author-display-options' ).hide();
			}
		}
	};
	
	/**
	* wpv-post-taxonomy management
	* Handle the change in format that shows/hides the show attribute
	*
	* @since 1.9
	*/
	
	$( document ).on( 'change', '#wpv-post-taxonomy-format .js-shortcode-gui-field', function() {
		self.manage_wpv_post_taxonomy_format_show_relation();
	});
	
	self.manage_wpv_post_taxonomy_format_show_relation = function() {
		if ( $( '#wpv-post-taxonomy-format' ).length ) {
			if ( 'link' == $( '.js-shortcode-gui-field:checked', '#wpv-post-taxonomy-format' ).val() ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-show', '#wpv-post-taxonomy-display-options' ).slideDown( 'fast' );
			} else {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-show', '#wpv-post-taxonomy-display-options' ).slideUp( 'fast' );
			}
		}
	};
	
	/**
	* wpv-post-featured-image management
	* Handle the change in output that shows/hides the class attribute
	*
	* @since 1.9
	*/
	
	$( document ).on( 'change', '#wpv-post-featured-image-output.js-shortcode-gui-field', function() {
		self.manage_wpv_post_featured_image_output_show_class();
	});
	
	self.manage_wpv_post_featured_image_output_show_class = function() {
		if ( $( '#wpv-post-featured-image-output' ).length ) {
			if ( 'img' == $( '#wpv-post-featured-image-output.js-shortcode-gui-field' ).val() ) {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-class', '#wpv-post-featured-image-display-options' ).slideDown( 'fast' );
			} else {
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-class', '#wpv-post-featured-image-display-options' ).slideUp( 'fast' );
			}
		}
	};
	
	/**
	* filter_computed_attribute_value
	*
	* @since 1.9
	*/
	
	self.filter_computed_attribute_value = function( shortcode, attribute, value ) {
		switch ( shortcode ) {
			case 'wpv-post-author':
				if (
					'meta' == attribute
					&& 'meta' != $( '.js-shortcode-gui-field:checked', '#wpv-post-author-format' ).val() 
				) {
					value = false;
				}
				break;
			case 'wpv-post-taxonomy':
				if (
					'show' == attribute 
					&& 'link' != $( '.js-shortcode-gui-field:checked', '#wpv-post-taxonomy-format' ).val()
				) {
					value = false;
				}
				break;
			case 'wpv-post-featured-image':
				if (
					'class' == attribute
					&& 'img' != $( '#wpv-post-featured-image-output.js-shortcode-gui-field' ).val()
				) {
					value = false;
				}
		}
		return value;
	};
	
	/**
	* filter_computed_content
	*
	* @since 1.9
	*/
	
	self.filter_computed_content = function( shortcode, content, values ) {
		switch ( shortcode ) {
			case 'wpv-for-each':
				if ( values.hasOwnProperty( 'field' ) ) {
					content = '[wpv-post-field name="' + values.field + '"]';
				}
				break;
		}
		return content;
	};

	self.init(); // call the init method

};

jQuery( document ).ready( function( $ ) {
	WPViews.shortcodes_gui = new WPViews.ShortcodesGUI( $ );
});

var wpcfFieldsEditorCallback_redirect = null;

function wpcfFieldsEditorCallback_set_redirect(function_name, params) {
	wpcfFieldsEditorCallback_redirect = {'function' : function_name, 'params' : params};
}

