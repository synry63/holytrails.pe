DDLayout.views.LayoutView = Backbone.View.extend({
	//this is our document view it points to the root element of our page - .wrap
	el:"#js-dd-layout-editor",
	events: {
		'ajaxError': 'handleAjaxError'
	}
	,initialize: function(options)
	{
		var self = this;

		self.options = options;

		self.options.invisibility = false;

		self.scroll_position = 0;

		self.errors_div = jQuery(".js-ddl-message-container");

		self.model.set( "slug", jQuery(".js-edit-layout-slug", self.$el).text() );

		_.bindAll( self, 'beforeRender', 'render', 'afterRender', 'dropCellFails');

		self.render = _.wrap(self.render, function(render, args) {
			self.beforeRender(args);
			render(args);
			self.afterRender(args);
			return self;
		});

		self.$el.data('view', self);

		self.rows_view = null;

		self.listenTo(self.eventDispatcher, "save_layout_to_server", self.saveLayout, self );
		self.listenTo(self.eventDispatcher, 'clear_drop_failed', self.clearDropFailed, self );
		self.listenTo(self.eventDispatcher, 'deselect_element', self.clearDropFailed, self );
		

		self.listenToOnce(self.eventDispatcher, 're_render_all', function(event, model){
			 /// do something once on render
		});

		self.listenTo( self.eventDispatcher, 're_render_all', self.render );

		self.listenTo( self.eventDispatcher, 'ddl-delete-child-layout-cell', self.setChildrenToDelete );

        self.listenTo( self.model, 'cells-collection-remove-cell', self.cell_removed_callback );

        self.listenTo(self.model, 'cells-collection-add-cell', self.layout_cell_added);

		self.listenTo(self.model, 'request', self.ajaxSentRequest, self);

		self.listenTo(self.model, 'sync', self.ajaxSynced, self);

		self.hide_show_containers_button = self.hideContainerEdit();

		self.handleSave();

        self.change_layout_name();

		self.render();

		return self;
	},
    layout_cell_added:function(model, options)
    {
        if( model instanceof DDLayout.models.abstract.Element === false ) return;

        if( 'child-layout' === model.get('cell_type') ){
            this.hide_where_used_box();
        }
    },
    cell_removed_callback:function( model, options){

        if( model instanceof DDLayout.models.abstract.Element === false ) return;

        if( 'child-layout' == model.get('cell_type') ){
            this.show_where_used_box();
        }
    },
    hide_where_used_box:function()
    {
        jQuery('.js-dd-layouts-where-used-editor-wrap').fadeOut(400, function(){

        });
    },
    show_where_used_box:function(){
        jQuery('.js-dd-layouts-where-used-editor-wrap').fadeIn(400, function(){

        });
    },
	beforeRender:function(option)
	{
		var self = this;

		jQuery(window).scroll(function(){
			self.scroll_position =  window.pageYOffset;
		});

		self.show_div_after_self = false;

        self.maybe_display_removed_message();
	},
    maybe_display_removed_message:function(){
        var self = this,
            removed = DDLayout_settings.DDL_JS.removed_cells,
            message = DDLayout_settings.DDL_JS.strings.removed_cells_message;

        if( removed && removed.length ){

            self.errors_div.wpvToolsetMessage({
                text: message,
                type: 'info',
                stay: true,
                stay_for:10000,
                close: true,
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function() {
                    jQuery('html').removeClass('toolset-alert-active');
                    DDLayout_settings.DDL_JS.removed_cells = [];
                    DDLayout_settings.DDL_JS.strings.removed_cells_message = '';
                }
            });
        }
    },
    setSlugDisplay: function( slug )
    {
        this.$el.find('.js-edit-layout-slug').text( slug );
    },
    setTitleDisplay:function( title )
    {
        this.$el.find('.js-layout-title').val( title );
    }
	,render:function( option )
	{
		var self = this,
			options = null;

		DDLayout.preview_manager.reset();

		if( self.model.has("Rows") && self.model.numRows() )
		{

			self._hide_show_button_container_edit();

			options = _.extend({el:'div.js-layout-container', model:self.model.get("Rows"), compound:"", invisibility:self.options.invisibility}, option );

            self.setTitleDisplay( self.model.get('name') );
            self.setSlugDisplay( self.model.get('slug') );

            self.setBreadCrumbText();

			//make sure we garbage collected previuos instances
			if( self.rows_view !== null )
			{
				self.rows_view = null;
			}

			self.rows_view = new DDLayout.views.RowsView( options );

			if( DDLayout.ddl_admin_page === undefined )
			{
				self.show_div_after_self = true;
				jQuery( "> div", self.rows_view.$el ).hide();

			}

			if( self.options.invisibility === true )
			{
				jQuery('.js-layout-container').addClass('containers-toolbars-disabled');
			}
			else
			{
				jQuery('.js-layout-container').removeClass('containers-toolbars-disabled');
			}

		}

		return self;
	},
	afterRender:function(option)
	{
		var self = this;

		if( self.scroll_position > 0 ) window.scrollTo( 0, self.scroll_position );

		if( self.show_div_after_self )
		{
			jQuery( "> div", self.rows_view.$el ).fadeIn(300);
		}
		// set the main layout size.
		if ( self.model.get('width') != DDLayout.MAXIMUM_SPAN ) {
			var width = self.model.get('width') * (DDLayout.CELL_MIN_WIDTH + DDLayout.MARGIN_BETWEEN_CELLS) - DDLayout.MARGIN_BETWEEN_CELLS;
			width += 6; // allow room for shadows.
			jQuery('.js-layout-container > .row-container').css({ width : width });
		}

        self.eventDispatcher.trigger('layout_editor_view_after_render');
		
		// Delete any problem tooltips.
		
		jQuery('.toolset-tooltip').remove();
		
		// Show a popup if we failed to drop the cells.
		
		_.delay( self.dropCellFails, 500);

        jQuery('.debugging').html(JSON.stringify( this.model.toJSON() ) );

        if( option && option.ajax_save && option.ajax_save instanceof Function ){
                var save_params = option.save_params;
                option.ajax_save.call(self, save_params);
        }
	},
	dropCellFails:	function () {

        var self = this, drop_element = jQuery('.js-drop-failed:first', self.rows_view.$el);
        if (drop_element.length) {

            var cell_width = drop_element.data('drop-failed-drop-width');
            var $targets = jQuery('.js-drop-failed', self.rows_view.$el);
            var target_width = $targets.length;

            if (cell_width > target_width) {

                $targets.addClass('drop-failed');

                var message = DDLayout_settings.DDL_JS.strings.no_drop_content;
                message = message.replace(/\%NN\%/g, cell_width);
                message = message.replace(/\%MM\%/g, target_width);

                if (drop_element.data('drop-failed-target-row-divider') > drop_element.data('drop-failed-drop-row-divider')) {
                    message = message.replace(/\%OO\%/, DDLayout_settings.DDL_JS.strings.no_drop_content_wider);
                } else {
                    message = message.replace(/\%OO\%/, '');
                }

                drop_element.pointer({
                    pointerClass: 'wp-toolset-pointer wp-toolset-layouts-pointer',
                    content: '<h3>' + DDLayout_settings.DDL_JS.strings.no_drop_title + '</h3><p>' + message + '</p>',
                    position: {
                        edge: 'bottom'
                    },
                    close: function () {self.clearDropFailed(true)},
                    open: function (event, data) {
                        var pointer = data.pointer;
                        var arrow = pointer.find('.wp-pointer-arrow');
                        arrow.css('left', (drop_element.width() - arrow.outerWidth()) / 2);
                    },
                    pointerWidth: 420,
                    buttons: function( event, t ) {
                        var close  = ( wpPointerL10n ) ? wpPointerL10n.dismiss : 'Dismiss';
                        var button_close = jQuery('<button class="button button-primary-toolset alignright js-wpv-close-this">' + close + '</button>');

                        return button_close.bind( 'click.pointer', function( e ) {
                            e.preventDefault();
                            t.element.pointer('close');
                        });
                    },


                }).pointer('open');
            } else {
                $targets.removeClass('js-drop-failed');
            }

        }
    },
	clearDropFailed:function( no_close )
	{
		
		if (no_close !== true) {
			try {
				jQuery('.drop-failed:first').pointer('close');
			} catch (err) {
				
			}
		}
		
		jQuery('.drop-failed').removeClass('drop-failed');
		
		var cells = this.model.getLayoutCells();
		
		for (var i = 0; i < cells.length; i++) {
			cells[i].unset('drop-failed', {silent:true});
		}
		
	},

	
	setChildrenToDelete:function( event_type, children )
	{
		this.model.setChildrenToDelete( children, event_type );
	},
	handleSave:function()
	{
		var self = this, button_save = jQuery('input[name="save_layout"]');
		button_save.on("click", function(event){
			
			self.clearDropFailed();
			
			self.eventDispatcher.trigger('save_layout_to_server', jQuery(event.target) );
            jQuery(this).prop('disabled', true );
			return false;
		});

	},
    handleBreadCrumbTitleChange:function( el, breadEl)
    {
        var input = el, bread = breadEl;

        input.keyup(function (e) {
            bread.text( input.val() );
        });
    },
    setBreadCrumbText:function ()
    {
       var  breadTitle = jQuery('.js-dd-layouts-breadcrumbs-wrap > .js-layout-title');

        if( breadTitle.is('span') )
        {
            breadTitle.text( this.model.get('name') );
        }
    },
    change_layout_name:function()
    {
        var self = this,
            title = jQuery('input.js-layout-title', self.$el)
            , breadTitle = jQuery('.js-dd-layouts-breadcrumbs-wrap > .js-layout-title');


        if( breadTitle.is('span') )
        {
            self.handleBreadCrumbTitleChange( title, breadTitle );
        }


        jQuery(document).on('focus', title.selector, function(event){
            DDLayout.ddl_admin_page.take_undo_snapshot();
        });

        jQuery(document).on('change', title.selector, function(event){
            DDLayout.ddl_admin_page.add_snapshot_to_undo();

            if( jQuery(this).val() === '' )
            {
                WPV_Toolset.messages.container.wpvToolsetMessage({
                    text: DDLayout_settings.DDL_JS.strings.title_not_empty_string,
                    type: 'error',
                    stay: false,
                    close: false,
                    onOpen: function() {
                        jQuery('html').addClass('toolset-alert-active');
                    },
                    onClose: function() {
                        jQuery('html').removeClass('toolset-alert-active');
                    }
                });

                jQuery(this).val( self.model.get('name') );
            }
            else
            {
                self.model.set('name', jQuery(this).val() );
            }
        });
    },

    show_loader:function(caller){
        var obj = caller ? caller.parent() : jQuery(document.body);

        if( caller && caller.data('close') === 'no' ){

            var right = caller.text() == 'Create' ? '198px' : '174px';

            obj.css('position', 'relative');

            WPV_Toolset.Utils.loader.loadShow( caller, true ).css({
                'position':'absolute',
                'right':right,
                'top':'12px'
            });

        } else {


            if( 'BODY' !== obj.prop("tagName") ){
                obj.css('float', 'right')
            }

            WPV_Toolset.Utils.loader.loadShow( obj, true ).css({
                position:'relative',
                right:'0px',
                top:'5px',
                float:'right'
            });
        }
    },
    hide_loader:function(){
        WPV_Toolset.Utils.loader.loadHide();
    },
    handleChildLayoutData:function(){

        if( this.model.toJSON().has_child === true )
        {
            DDLayout.parents_watcher.trigger( 'created_child_layout', this.model.toJSON() );
        }

        if( this.model.get('children_to_delete') !== null )
        {
            DDLayout.parents_watcher.trigger( 'deleted_child_layout', this.model.toJSON() );
            this.model.set('children_to_delete', null);
        }
    },
	saveLayout:function( caller, callback, target )
	{
		var self = this,
			save_params = {};

        if( callback ){
            self.save_layout_callback = _.once( callback );
        } else {
            self.save_layout_callback = null;
        }

        self.show_loader(caller);

		if( DDLayout.ddl_admin_page.cssEditor.css_did_change )
		{
			save_params.layout_css = self.model.getCssString();
		}

        if( DDLayout.ddl_admin_page.is_slug_edited )
        {
            self.model.set('slug', jQuery('#layout-slug').val() );
            self.eventDispatcher.trigger('layout-model-trigger-save', jQuery('#layout-slug').val() );
        }

        // Text Cell (Visual Editor) preferred editor for new cells
        save_params.preferred_editor = DDLayout.text_cell.editor.get_preferred();

        self.handleChildLayoutData();

        DDLayout.ddl_admin_page.instance_layout_view.eventDispatcher.trigger('re_render_all', {ajax_save:self.saveViaAjax, save_params:save_params});

	},

	saveViaAjax : function( save_params ) {
		var self = this, model = self.model.toJSON();

        delete model.cssString;

		save_params = _.extend({
			action:'save_layout_data',
			layout_id:self.model.get('id'),
			save_layout_nonce:DDLayout_settings.DDL_JS.save_layout_nonce,
			// layout_model: encodeURIComponent( JSON.stringify( self.model.toJSON() ) )
			layout_model:JSON.stringify( model )
		}, save_params);

		self.model.save({},{
			contentType:'application/x-www-form-urlencoded; charset=UTF-8',
			type:'post',
			dataType:'json',
			data:jQuery.param(save_params)
		});
	},

	ajaxSentRequest:function( model, response, xhr )
	{
		//console.log("Request", arguments);
	},

	ajaxSynced:function( model, response, xhr )
	{

		var self = this;

		self.hide_loader();

		if( response.Data.error )
		{
            console.log( 'Error: ', response.Data.error )
			self.errors_div.wpvToolsetMessage({
				text: response.Data.error,
				type: 'error',
				stay: false,
                stay_for:10000,
				close: false,
				onOpen: function() {
					jQuery('html').addClass('toolset-alert-active');
				},
				onClose: function() {
					jQuery('html').removeClass('toolset-alert-active');
				}
			});
		}
		else if( response.Data.message && response.Data.message.layout_changed == true )
		{
			self.errors_div.wpvToolsetMessage({
				text:DDLayout_settings.DDL_JS.strings.save_complete,
				type: 'message',
				stay: false,
                stay_for:10000,
				close: false,
				onOpen: function() {
					jQuery('html').addClass('toolset-alert-active');
				},
				onClose: function() {
					jQuery('html').removeClass('toolset-alert-active');
                    if (self.save_layout_callback && self.save_layout_callback instanceof Function ) {
                        self.save_layout_callback.call(self, model, response);
                    }
				}
			});
		}
		else if( response.Data.css_saved )
		{
			if(_.isObject(response.Data.css_saved) && 'db_ok' in response.Data.css_saved && response.Data.css_saved.db_ok )
			{
                self.errors_div.wpvToolsetMessage({
                    text:DDLayout_settings.DDL_JS.strings.save_and_also_save_css,
                    type:'info',
                    stay:false,
                    close:false,
                    onOpen: function() {
                        jQuery('html').addClass('toolset-alert-active');
                    },
                    onClose: function () {
                        DDLayout.ddl_admin_page.clear_save_required();
                        if (self.save_layout_callback && self.save_layout_callback instanceof Function ) {
                            self.save_layout_callback.call( self, model, response );
                        }
                        jQuery('html').removeClass('toolset-alert-active');
                    }
                });
                DDLayout.ddl_admin_page.clear_save_required();
                if (self.save_layout_callback && self.save_layout_callback instanceof Function ) {
                    self.save_layout_callback.call( self, model, response );
                }
			}
			else
			{
				self.errors_div.wpvToolsetMessage({
					text:DDLayout_settings.DDL_JS.strings.save_and_save_css_problem,
					type:'warning',
					stay:false,
					close:false,
					onOpen: function() {
						jQuery('html').addClass('toolset-alert-active');
					},
					onClose: function () {
						DDLayout.ddl_admin_page.clear_save_required();
						if (self.save_layout_callback && self.save_layout_callback instanceof Function ) {
							self.save_layout_callback.call( self, model, response );
						}
						jQuery('html').removeClass('toolset-alert-active');
					}
				});
			}

			DDLayout.ddl_admin_page.cssEditor.css_did_change = false;
		}
		else if (!response.message.silent)
		{
			DDLayout.ddl_admin_page.clear_save_required();
			if (self.save_layout_callback && self.save_layout_callback instanceof Function ) {
				self.save_layout_callback.call(self, model, response);
			}
		} else {
            self.errors_div.hide();
        }

		if( response && response.Data )
		{
			// reset traking properties in Layout model
            if( response.Data.layout_children_deleted  )
            {
                this.setChildrenToDelete( undefined, null );
            }

            if( response.Data.slug )
            {
                self.setSlugDisplay( response.Data.slug );
            }

		}
		
		DDLayout.ddl_admin_page.update_wpml_state(self.model.get('id'), false);

        WPV_Toolset.Utils.eventDispatcher.trigger('layout_ajaxSynced_completed');
		
	},
    handleAjaxError:function( event ){
        var self = this;

        self.hide_loader();

        self.errors_div.wpvToolsetMessage({
            text: DDLayout_settings.DDL_JS.strings.ajax_error,
            type: 'error',
            stay: false,
            stay_for:10000,
            close: false,
            onOpen: function() {
                jQuery('html').addClass('toolset-alert-active');
            },
            onClose: function() {
                jQuery('html').removeClass('toolset-alert-active');
            }
        });
    },
	getLayoutModelToJs:function()
	{
		return this.model.toJSON();
	},
	getLayoutType:function()
	{
		return this.model.getType();
	},
	hideContainerEdit:function()
	{
		var self = this, button = jQuery("input#hide-containers");

			if( self.options.invisibility )
			{
				button.val(DDLayout_settings.DDL_JS.strings.show_grid_edit);
			}
			else
			{
				button.val(DDLayout_settings.DDL_JS.strings.hide_grid_edit);
			}

			button.on("click", function(event){
				event.preventDefault();

				if( self.options.invisibility )
				{
					jQuery(this).val(DDLayout_settings.DDL_JS.strings.hide_grid_edit);
					self.options.invisibility = false;
				}
				else
				{
					jQuery(this).val(DDLayout_settings.DDL_JS.strings.show_grid_edit);
					self.options.invisibility = true;
				}

				self.render( );
			});

		   return button;
	},
	_hide_show_button_container_edit:function()
	{
		if( this._hasContainers() ){
			this.hide_show_containers_button.show();
		}
		else{
			this.hide_show_containers_button.hide();
		}
	},
	_hasContainers:function()
	{
		return this.model.getLayoutContainers( ).length > 0;
	}
});