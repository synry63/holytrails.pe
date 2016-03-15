DDLayout.ContainerDialog = function($)
{
	var self = this;

    _.extend( DDLayout.ContainerDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

	self.init = function() {

		jQuery(document).on('click', '.js-container-dialog-edit-save, .js-container-dialog-edit-add-container', {dialog: self}, function(event) {
			event.preventDefault();
			event.data.dialog._save( jQuery(this) );
		});
	};

	self._save = function(caller)
	{

		var target_container_view = jQuery('#ddl-container-edit').data('container_view');

		if (jQuery('#ddl-container-edit').data('mode') == 'edit-container') {

			DDLayout.ddl_admin_page.save_undo();

			var target_container = target_container_view.model;

			target_container.set('name', jQuery('input[name="ddl-container-edit-container-name"]').val());
			target_container.set( 'additionalCssClasses', jQuery('input.js-edit-css-class', jQuery('#ddl-container-edit')).val() );
			target_container.set('cssId', jQuery('input.js-edit-css-id', jQuery('#ddl-container-edit') ).val());
			target_container.set('tag', jQuery('select.js-ddl-tag-name', jQuery('#ddl-container-edit') ).val());

            var css_saved = self._handle_css_save();

            DDLayout.ddl_admin_page.save_layout_from_dialog( caller, target_container_view, self.cached_element, css_saved, self );
		}

        if ( self.is_save_and_close(caller) )  jQuery.colorbox.close();

		return false;
	};

	self.show = function(mode, container_view)
	{

        self.setCachedElement( container_view.model.toJSON() );

		if (mode == 'edit') {
			jQuery('#ddl-container-edit').data('mode', 'edit-container');
			jQuery('#ddl-container-edit').data('container_view', container_view);

			//console.log( container_view.model );

			jQuery('input[name="ddl-container-edit-container-name"]').val( container_view.model.get('name') );
			jQuery('input.js-edit-css-class', jQuery('#ddl-container-edit')).val( container_view.model.get('additionalCssClasses') );
			jQuery('input.js-edit-css-id', jQuery('#ddl-container-edit') ).val( container_view.model.get('cssId') );
			jQuery('select.js-ddl-tag-name', jQuery('#ddl-container-edit') ).val( container_view.model.get('tag') )

			jQuery('#ddl-container-edit .js-dialog-edit-title').show();
			jQuery('#ddl-container-edit .js-container-dialog-edit-save').show();

			jQuery('#ddl-container-edit .js-dialog-add-title').hide();
			//jQuery('#ddl-container-edit .js-container-dialog-edit-add-container').hide();
            jQuery('.js-edit-dialog-close').css('float', 'left')

			jQuery('#ddl-container-edit #ddl-container-edit-layout-type').parent().hide();

		}

		jQuery.colorbox({
			href: '#ddl-container-edit',
			closeButton:false,
			onComplete: function() {
				jQuery('.js-popup-tabs').tabs(); // Initialize tabs
				jQuery('.js-popup-tabs').tabs( 'option', 'active', 0 ); // Activate the first tab
				//codemirror_init( 'code-css-editor', 'css', $(this) );
			}
		});

		self.init();
	};
};