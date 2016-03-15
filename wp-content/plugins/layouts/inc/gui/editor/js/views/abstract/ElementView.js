DDLayout.views.abstract.ElementView = Backbone.View.extend({
	tagName:'div',
	compound:'',
	defaultCssClass:'',
	mouse_caret:0,
	parentDOM:null
	,initialize:function(options)
	{
		var self = this;
        self.$el.data('view', self);

		self.options = options;

		self.parentDOM = options.parentDOM;

		_.bindAll( self, 'beforeRender', 'render', 'afterRender', 'trigger_highlight', 'highlight_element', 'highlight_exec');

		self.render = _.wrap(self.render, function( render, args ) {
			self.beforeRender();
			render( args );
			//execute afterRender after everything else executes
			//_.defer( _.bind( self.afterRender ) );
			self.afterRender();
			return self;
		});

		self.compound = options && options.compound ? options.compound : '';

		self.$el[0].className = 'js-'+ self.model.get('kind').toLowerCase() + " " + self.model.get('kind').toLowerCase();

		return self;
	},
	get_parent_view : function () {
		// get the row view
		var parent = this.get_parent_cells_view().get_parent_view_dom();
		if( parent === null || parent === undefined ) return null;
		return parent.data('view');
	},
	get_parent_cells_view:function ()
	{
		return this.parentDOM.data('view');
	},
	beforeRender:function()
	{
		//
	},
	afterRender:function()
	{
		//
	},
	render:function()
	{
		var self = this;
		var itemEditorCssBaseClass = self.model.get('kind').toLowerCase();
		var visualTemplateID = self.model.get('editorVisualTemplateID').toLowerCase();

		self.$el.addClass(self.defaultCssClass);
		self.$el.addClass( itemEditorCssBaseClass );

		self.template = null;

		if (visualTemplateID) {
			var template = jQuery('#' + visualTemplateID);
			if (template.length) {
				self.template = _.template( template.html() );
			}
		}

		if (!self.template) {
			if ((itemEditorCssBaseClass == 'cell') && jQuery('#'+self.model.get('cell_type')+'-template').length) {
				self.template = _.template( jQuery('#'+self.model.get('cell_type')+'-template').html() );
			} else {
				self.template = _.template( jQuery('#'+itemEditorCssBaseClass+'-template').html() );
			}
		}
		self._doTemplate();

		self._makeElementNameEditable();

		return self;
	},
	/**
	 * This one should be overridden to pass special params to ElementView templates
	 * @private
	 */
	_doTemplate:function()
	{
		var self = this;
		try
		{
			self.$el.html( self.template( _.extend( self.model.toJSON(), {layout:self.model.layout, cid:self.model.cid} ) ) );
		}
		catch( e )
		{
			console.error(e.message);
		}
	},
	_makeElementNameEditable: function () {
		var self = this,
			textSpan = jQuery('.js-element-name', self.$el).first();

			// TOD0: We probably don't need to calculate the span width. I've made pure CSS solution for this issue

			// TUDO: We don't need span and input. We can have input all the time. https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/175143525/comments

		if (textSpan.length === 0) return;

		textSpan.bind('click', function (event) {
			event.stopPropagation();

            if( self instanceof DDLayout.views.ContainerView && self.model.selected_cell === false )
            {
                self.$el.trigger('click');
                DDLayout.ddl_admin_page.key_handler.destroy();
            }

			DDLayout.ddl_admin_page.take_undo_snapshot();

			if (DDLayout.ddl_admin_page.element_name_editable_now.indexOf(event.target) !== -1) {
				return false;
			}
			else {
				jQuery(DDLayout.ddl_admin_page.element_name_editable_now[0]).prev().remove();
				jQuery(DDLayout.ddl_admin_page.element_name_editable_now[0]).show();
				DDLayout.ddl_admin_page.element_name_editable_now.pop();
				DDLayout.ddl_admin_page.element_name_editable_now.push(event.target);
				DDLayout.ddl_admin_page.is_in_editable_state = false;
			}

			jQuery(self.el).find('div.js-row-toolbar').eq(0).trigger("mouseleave");

			var el = jQuery(this),
				parent = el.parent(),
				value = el.text().replace(/"/g, "&quot;"),
				index = el.index(),
				input,
				text_len = value.length,
				text_width = el.textWidth();

			input = jQuery('<input type="text" name="element-name-input" class="element-name-input" value="'+value+'" />');

			if (DDLayout.ddl_admin_page.is_in_editable_state === false) {
				el.css("visibility", "hidden");
				parent.insertAtIndex(index, input);

				input.keydown(function (e) {
					var key = e.keyCode || 0;
					// on enter, just save the new slug, don't save the post
					if (13 == key) {
						jQuery(document).not(input).trigger('mouseup');
						return false;
					}
					if (27 == key) {
						jQuery(document).not(input).trigger('mouseup', {cancel:true, val:value});
						return false;
					}
				}).keyup(function (e) {
						//not sure we need this
					})
					.focus();
				// .val(value);

				DDLayout.ddl_admin_page.is_in_editable_state = true;
			}

			jQuery(document).not(input).on("mouseup", {el: el, input: input, self: self}, DDLayout.AdminPage.manageDeselectElementName);

			input.mousemove(function(event){
				var caret = event.offsetX <= text_width ? Math.floor( event.offsetX / ( text_width / text_len ) ) : text_len;
				self.mouse_caret = Math.floor( caret );
			});
		});
	},
    trigger_highlight:function(event){
        //console.log('trigger_highlight')
        var self = this
        self.eventDispatcher.trigger('trigger_highlight', event);
    },
    highlight_element:function(event){
       // console.log('highlight_element')
        var self = this;
        self.overlay = new DDLayout.ElementsOverlay( event, self );
    },
    highlight_init:function(event){
       // console.log('highlight_init')
        var self = this;
        self.listenToOnce( self.eventDispatcher, 'trigger_highlight', self.highlight_exec );
        _.delay(self.trigger_highlight, 600, event);
    },
    highlight_destroy:function(event){
       // console.log('highlight_destroy')
        var self = this;
        self.stopListening(self.eventDispatcher,'trigger_highlight', self.highlight_exec);
        try{
            self.overlay.destroy();
        } catch( e ){
            return;
        }
    },
    highlight_exec: function(event){
      //  console.log('highlight_exec')
        var self = this;
       self.highlight_element(event);
    }
});


DDLayout.ElementsOverlay = function( event, view ){

    var self = this,
        $js_tpl = jQuery('#ddl-elements-overlay'),
        el = view,
        e = event,
        target = e.target,
        $target = jQuery(target);

    self.el = null;
    self.$el = null;

    self.init = function(){
        var template = $js_tpl.html(), width, height = el.$el.height();
        self.el = _.template( template, view.model );
        self.$el = jQuery( self.el );

        if( el instanceof DDLayout.views.RowView ){
            self.$el.css({
                'top':'-1px'
            });
            width = el.el.offsetWidth;
        } else{
            width = el.$el.width()
        }

        self.$el.height( height );
        self.$el.width( width );

        if( el instanceof DDLayout.views.RowView || el instanceof DDLayout.views.ContainerRowView || el instanceof DDLayout.views.ContainerView){
         //   self.restoreEventPropagation( e );
            $target.parent().css('z-index', "9999999999999998");
        }
        $target.css('z-index', "9999999999999999");
        self.$el.css('z-index', '8888');
        el.$el.append( self.$el );
    };

    self.destroy = function( ){
        self.$el.remove();
        self.$el = null;
        $target = null;
        target = null;
        el = null;
        e = null;
        self = null;
    };

    self.is_remove = function( css_class ){
            return css_class.indexOf('remove') !== -1;
    };

    self.is_edit = function( css_class ){
        return css_class.indexOf('edit') !== -1;
    };

    /**
     * For Row and Container objects to restore cacelled event
     * @param event
     */
    self.restoreEventPropagation = function (event) {

        WPV_Toolset.Utils.restoreEventPropagation( event );

        jQuery( event.target).on('mouseup', function(event){
            event.stopImmediatePropagation();
            jQuery(this).trigger('click')
        })
    };

    self.init();
};






