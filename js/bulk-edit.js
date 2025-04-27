jQuery(function($) {
    // Show/hide new series input based on dropdown selection
    $(document).on('change', 'select[name="series_bulk_edit"]', function() {
        var $newSeriesInput = $('.new-series-input');
        if ($(this).val() === '__new__') {
            $newSeriesInput.show();
        } else {
            $newSeriesInput.hide();
        }
    });

    // Initialize bulk edit form
    var $wp_inline_edit = inlineEditPost.edit;
    inlineEditPost.edit = function(id) {
        // Call the original function
        $wp_inline_edit.apply(this, arguments);
        
        // Get the post ID
        var post_id = 0;
        if (typeof(id) == 'object') {
            post_id = parseInt(this.getId(id));
        } else {
            post_id = parseInt(id);
        }
        
        if (post_id > 0) {
            // Find the edit row and the post row
            var $edit_row = $('#edit-' + post_id);
            var $post_row = $('#post-' + post_id);
            
            // Get the series value from the post row
            var series = $post_row.find('.column-series').text();
            
            // Set the series value in the bulk edit field
            var $seriesSelect = $edit_row.find('select[name="series_bulk_edit"]');
            var $newSeriesInput = $edit_row.find('input[name="new_series_name"]');
            
            // Check if the series exists in the dropdown
            var seriesExists = false;
            $seriesSelect.find('option').each(function() {
                if ($(this).val() === series) {
                    seriesExists = true;
                    return false; // Break the loop
                }
            });
            
            if (seriesExists) {
                $seriesSelect.val(series);
                $newSeriesInput.hide();
            } else if (series) {
                // If series exists but not in dropdown, set to new and fill input
                $seriesSelect.val('__new__');
                $newSeriesInput.val(series).show();
            } else {
                // No series
                $seriesSelect.val('');
                $newSeriesInput.hide();
            }
        }
    };
}); 