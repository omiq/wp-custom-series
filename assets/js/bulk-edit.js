(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle new series toggle
        $('.inline-edit-series select[name="series_bulk_edit"]').on('change', function() {
            const $newSeriesInput = $('.new-series-input');
            if ($(this).val() === '__new__') {
                $newSeriesInput.show();
            } else {
                $newSeriesInput.hide();
            }
        });

        // Handle quick edit
        $('.editinline').on('click', function(e) {
            const postId = $(this).closest('tr').attr('id').replace('post-', '');
            const series = $(`#post-${postId} .column-series`).text().trim();
            
            // Set current series in dropdown
            const $select = $('.inline-edit-series select[name="series_bulk_edit"]');
            $select.val(series);
            
            // Hide new series input
            $('.new-series-input').hide();
        });
    });
})(jQuery); 