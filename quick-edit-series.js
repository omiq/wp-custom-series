jQuery(function($) {
    // Extend the inline edit post function to include our custom series field
    var $wp_inline_edit = inlineEditPost.edit;
    inlineEditPost.edit = function(id) {
        $wp_inline_edit.apply(this, arguments);
        
        // Get the post ID
        var post_id = 0;
        if (typeof(id) == 'object') {
            post_id = parseInt(this.getId(id));
        }

        if (post_id > 0) {
            // Find the quick edit row and the post row
            var $edit_row = $('#edit-' + post_id);
            var $post_row = $('#post-' + post_id);
            
            // Get the series value from the post row
            var series = $post_row.find('.column-series').text();
            
            // Set the series value in the quick edit field
            $edit_row.find('input[name="series_field"]').val(series);
        }
    };
});
