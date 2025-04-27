jQuery(function($) {
    // Only initialize if we're on the posts list page and inlineEditPost exists
    if (typeof inlineEditPost !== 'undefined') {
        var $wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            $wp_inline_edit.apply(this, arguments);
            var post_id = 0;
            if (typeof(id) == 'object') {
                post_id = parseInt(this.getId(id));
            }
            if (post_id > 0) {
                var $edit_row = $('#edit-' + post_id);
                var $post_row = $('#post-' + post_id);
                var series = $post_row.find('.column-series').text();
                $edit_row.find('input[name="series_field"]').val(series);
            }
        };
    }
}); 