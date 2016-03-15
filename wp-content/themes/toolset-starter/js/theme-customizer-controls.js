/* this file is loaded in the customizer controls frame and not in the customizer preview frame */
;(function ($) {

	// better tab for editor
	function betterTab(cm) {
		if (cm.somethingSelected()) {
			cm.indentSelection("add");
		} else {
			cm.replaceSelection(cm.getOption("indentWithTabs")? "\t":
				Array(cm.getOption("indentUnit") + 1).join(" "), "end", "+input");
		}
	}


	/**
	 * Custom CSS
	 */
	var textareaCustomCSS,
		changeTrigger;

	// update default textarea and run the default wordpress change script after 1.2 seconds of not typing
	function inputChanged() {
		clearTimeout( changeTrigger );
		textareaCustomCSS.trigger( 'change' );
	}

	// remove static custom css
	function removeStaticCSS() {
		var staticCSS = $( '#customize-preview iframe' ).contents().find( '#ref_customizer-css' );
		if( staticCSS.length ) {
			staticCSS.remove();
		}
	}

	$( window ).load( function() {
		textareaCustomCSS =  $( '#textarea-custom-css' );

		if( textareaCustomCSS.length ) {
			textareaCustomCSS.hide();

			var editor = CodeMirror.fromTextArea(
				document.getElementById( 'textarea-custom-css' ),
				{
					mode: "css",
					lineNumbers: true,
					lineWrapping: true,
					extraKeys: { Tab: betterTab }
				}
			);

			// remove padding to make the editor as width as possible
			$( '#accordion-section-section_custom_css > ul.accordion-section-content' ).css( {
				'padding': '0'
			} );

			// refresh editor when opening custom css the first time
			$( '#accordion-section-section_custom_css ' ).on( 'click', function() {
				editor.refresh();
				$( '#accordion-section-section_custom_css ' ).off( 'click' );
			} );

			// be sure options are updated when hovering save
			$( 'body' ).on( 'hover', '#save', function() {
				inputChanged();
			} );

			editor.on( 'change', function() {
				textareaCustomCSS.val( editor.getValue() + ' ' );
				clearTimeout( changeTrigger );
				changeTrigger = setTimeout( inputChanged, 1000 );
			} );

			// load the custom css ounce
			textareaCustomCSS.val( editor.getValue() + ' ' );
			changeTrigger = setTimeout( inputChanged, 1000 );

			// remove static css
			setTimeout( removeStaticCSS, 3000 );
		}
	} );

	/**
	 * Advanced Settings
	 */
	$( window ).load( function() {
		var customizePreview = $( '#customize-preview' ),
			settingThemeCSSWoo = $( '#customize-control-ref_wc_styles input' ),
			settingThemeCSS = $( '#customize-control-ref_theme_styles input' ),
			settingPrimaryColor = $( '#customize-control-ref_color_styles input' ),
			settingAll =  $( '#customize-control-ref_wc_styles, #customize-control-ref_theme_styles, #customize-control-ref_color_styles' ).find( 'input' ),
			reloadAfterRefresh;


		// used when a css file is loaded dynamic
		function afterDynamicCSSLoad() {
			// fire all resize events
			customizePreview.css( 'height', customizePreview.height() - 1 + 'px' );
			setTimeout( function() {
				$( '#customize-preview' ).css( 'height', '100%' );
			}, 1000 );

			// adjust primary color
			settingPrimaryColor.trigger( 'change' );
		}

		/**
		 * Setting "Load theme CSS"
		 */
		settingThemeCSS.on( 'change', function( event, stopRefreshColors ) {
			var iframe = $( '#customize-preview iframe' ).contents(),
				iframeCSSTheme = iframe.find( '#theme-css' ),
				iframeCSSBootstrap = iframe.find( '#bootstrap_css-css');
			if( settingThemeCSS.is(':checked') ) {
				if( ! iframeCSSTheme.length ) {
					iframe.find( '#main-css' ).after( '<link id="theme-css" type="text/css" rel="stylesheet" href="' + toolset.theme_css + '"></link>');
				} else {
					iframeCSSTheme.attr('media','all');
				}

				if( iframeCSSBootstrap.length ) {
					iframeCSSBootstrap.attr('media','print');
				}
			} else {
				if( ! iframeCSSBootstrap.length ) {
					iframe.find( '#main-css' ).after( '<link id="bootstrap_css-css" type="text/css" rel="stylesheet" href="' + toolset.theme_css_bootstrap + '"></link>');
				} else {
					iframeCSSBootstrap.attr('media','all');
				}
				if( iframeCSSTheme.length ) {
					iframeCSSTheme.attr('media','print');
				}
			}

			if( stopRefreshColors != 'noColorRefresh' ) {
				afterDynamicCSSLoad();
			}
		} );


		/**
		 * Setting "Load theme CSS"
		 */
		settingThemeCSSWoo.on( 'change', function() {
			var iframe = $( '#customize-preview iframe' ).contents(),
				iframeCSSWoo = iframe.find( '#ref_woocommerce-css' );
			if( settingThemeCSSWoo.is(':checked') ) {
				if( ! iframeCSSWoo.length ) {
					if( iframe.find( '#theme-css' ).length ) {
						iframe.find( '#theme-css' ).after( '<link id="ref_woocommerce-css" type="text/css" rel="stylesheet" href="' + toolset.theme_css_woo + '"></link>');
					} else {
						iframe.find( '#main-css' ).after( '<link id="ref_woocommerce-css" type="text/css" rel="stylesheet" href="' + toolset.theme_css_woo + '"></link>');
					}
				} else {
					iframeCSSWoo.attr('media','all');
				}
			} else {
				if( iframeCSSWoo.length ) {
					iframeCSSWoo.attr('media','print');
				}
			}

			afterDynamicCSSLoad();
		} );
		
		/**
		 * Setting "Enable Primary Color setting"
		 */
		$( '<div id="primary_color_control_overlay" />' ).appendTo( '#customize-control-primary_color' );

		var body = $( 'body' ),
			primaryColorControl = $( '#customize-control-primary_color .customize-control-content' ),
			primaryColorControlOverlay = $( '#primary_color_control_overlay' );

		// check if primary color is active (is not when php version is lower than 5.3)
		// and if setting is disabled -> it's already running
		if( primaryColorControl.length && ! settingPrimaryColor.attr( 'disabled' ) ) {

			var	primaryColorInput = primaryColorControl.find( 'input.wp-color-picker' ),
				primaryColorMsgEnabled = $( '#primary_color_enabled' ),
				primaryColorMsgDisabled = $( '#primary_color_disabled' ),
				settingThemeCSSInt, settingThemeCSSWooInt,
				cacheColor, cacheCSS, cacheCSSThemeWoo, cacheCSSTheme, cacheCSSWoo;


			/**
			 * Change Primary Color CSS
			 * depending on selected color and css settings
			 */
			function changePrimaryColor() {
				if( settingPrimaryColor.is(':checked') ) {
					var cssInsert;

					if( settingThemeCSSInt == 1 && settingThemeCSSWooInt == 1 ) {
						cssInsert = cacheCSSThemeWoo;
					} else if( settingThemeCSSInt == 1) {
						cssInsert = cacheCSSTheme;
					} else if( settingThemeCSSWooInt == 1 ) {
						cssInsert = cacheCSSWoo;
					} else {
						cssInsert = cacheCSS;
					}

					$( '#customize-preview iframe' ).contents().find( '#custom-ref-style-life' ).html( cssInsert );
				}
			}

			/**
			 * Function when primary color is changed
			 */
			primaryColorInput.on( 'change', function() {

				body.addClass( 'primary-color-change' );

				primaryColorControlOverlay.css( {
					'height' : primaryColorControl.height() + 'px',
					'margin-top': -primaryColorControl.height() + 'px'
				} );

				settingAll.attr( 'disabled', true );

				cacheColor = primaryColorInput.val();

				$.ajax( {
					url     : toolset.ajaxurl,
					type    : 'POST',
					data    : ( {
						action                         : 'ref_dynamic_css',
						customize_ajax_ref_color_styles: cacheColor,
						customize_ajax_ref_theme_styles: settingThemeCSSInt,
						customize_ajax_ref_wc_styles   : settingThemeCSSWooInt
					} ),
					dataType: 'json',
					success : function ( css ) {
						cacheCSS = css.cacheCSS;
						cacheCSSTheme = css.cacheCSSTheme;
						cacheCSSWoo = css.cacheCSSWoo;
						cacheCSSThemeWoo = css.cacheCSSThemeWoo;

						changePrimaryColor();
						body.removeClass( 'primary-color-change' );
						settingAll.removeAttr( 'disabled' );
					}
				} );

			} );

			/**
			 * Function when css setting is changed
			 */
			settingPrimaryColor.on( 'change', function() {
				if( settingPrimaryColor.is(':checked') ) {
					primaryColorControl.show();
					primaryColorMsgEnabled.show();
					primaryColorMsgDisabled.hide();

					settingThemeCSSInt = settingThemeCSS.is(':checked') ? 1 : 0;
					settingThemeCSSWooInt = settingThemeCSSWoo.is(':checked') ? 1 : 0;

					// if color not change
					if( cacheColor == primaryColorInput.val() ) {
						changePrimaryColor();
					} else {
						primaryColorInput.trigger( 'change' );
					}

				} else {
					primaryColorControl.hide();
					primaryColorMsgEnabled.hide();
					primaryColorMsgDisabled.show();

					$( '#customize-preview iframe' ).contents().find( '#custom-ref-style-life' ).html('');

				}
			} );

			settingPrimaryColor.trigger( 'change' );


			/**
			 * Some Magic...
			 * apply all postMessage settings after a refresh setting
			 */
			document.addEventListener( 'animationstart', reloadChangesViaPostMessage, false );
			document.addEventListener( 'MSAnimationStart', reloadChangesViaPostMessage, false );
			document.addEventListener( 'webkitAnimationStart', reloadChangesViaPostMessage, false);

			function reloadChangesViaPostMessage() {
				if( reloadAfterRefresh != 'no' ) {
					cacheColor = 0;
					settingThemeCSS.trigger( 'change', [ 'noColorRefresh' ] );
					settingThemeCSSWoo.trigger( 'change' );
					removeStaticCSS();
				} reloadAfterRefresh = 'no';

				setTimeout( function() { reloadAfterRefresh = 1 }, 1000 );
			}
		}
	} );

})(jQuery);