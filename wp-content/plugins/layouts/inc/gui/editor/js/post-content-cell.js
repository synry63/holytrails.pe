// post-content-cell.js

// Handles both post content and Views Content Template cells.

DDLayout.PostContentCell = function($)
{
    var self = this;

    self.init = function() {

        self._cell_content = null;
        self._preview = {};

    };

    self.get_display_mode = function () {
        if (jQuery('#ddl-default-edit input[name="ddl-layout-page"]:checked').length) {
            return jQuery('#ddl-default-edit input[name="ddl-layout-page"]:checked').val();
        } else {
            return '';
        }
    };

    self.get_selected_post = function () {
        return jQuery('#ddl-default-edit #ddl-layout-selected_post').val();
    }


    self.get_preview = function ( content, current_text, specific_text, loading_text, preview_image, thiz){
        var width = thiz.model.get('width');
		
		if (preview_image) {
			preview_image = '<img src="' + preview_image + '" height="130px">';
		}
        
        if (content.page == 'current_page') {
            var image_size = 10;
            return '<div class="ddl-post-content-current-page-preview"><p>'+ current_text +'</p>'+
            preview_image+
            '</div>';
        } else {
            var post_id = content.selected_post;
            var divclass = 'js-post_content-' + post_id;
            if ( typeof(self._preview[post_id]) !== 'undefined' && self._preview[post_id] != null){            
                var out = '<div class="ddl-post-content-current-page-preview '+ divclass +'">'+ self._preview[post_id] +'</div>';
                return out;
            }
            var out = '<div class="'+ divclass +'">'+ loading_text +'</div>';
            if (typeof(self._preview[post_id]) == 'undefined') {
                self._preview[post_id] = null;

                var data = {
                    action : 'ddl_post_content_get_post_content',
                    post_id: post_id,
                    wpnonce : jQuery('#ddl_layout_view_nonce').attr('value')
                };
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: data,
                    cache: false,
                    dataType: 'json',
                    success: function(data) {
                        //cache view id data
                        self._preview[post_id] = '<p>' + specific_text.replace('%s', '<strong>' + data.title + '</strong>')+ '</p>' + preview_image;
                        jQuery('.' + divclass).html(self._preview[post_id]);
                        DDLayout.ddl_admin_page.render_all();
                    }
                });
            }

            return out;
        }
    }

    self.init();
};

jQuery(document).ready(function($){
	DDLayout.post_content_cell = new DDLayout.PostContentCell($);
});