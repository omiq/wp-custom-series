/**
 * Bulk Edit JavaScript for Custom Series
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle new series toggle
        $('.inline-edit-series select[name="series_bulk_edit"]').on('change', function() {
            const $newSeriesInput = $(this).closest('.inline-edit-col').find('.new-series-input');
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

        // Handle bulk edit
        $('#bulk_edit').on('click', function() {
            // Get all selected posts
            const selectedPosts = $('.check-column input[type="checkbox"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedPosts.length === 0) {
                alert('Please select at least one post to edit.');
                return;
            }

            // Get the series value
            const seriesValue = $('select[name="series_bulk_edit"]').val();
            const newSeriesName = $('input[name="new_series_name"]').val();

            // Find the nonce field by its prefix
            let nonceValue = '';
            $('input[id^="custom_series_bulk_edit_nonce_"]').each(function() {
                nonceValue = $(this).val();
                return false; // Break the loop after finding the first match
            });

            if (!nonceValue) {
                alert('Security verification failed. Please refresh the page and try again.');
                return;
            }

            // Prepare data for AJAX
            const data = {
                action: 'custom_series_bulk_edit',
                nonce: nonceValue,
                post_ids: selectedPosts,
                series_value: seriesValue,
                new_series_name: newSeriesName
            };

            // Send AJAX request
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    // Reload the page to show updated series
                    location.reload();
                } else {
                    alert('Error updating series: ' + response.data);
                }
            });
        });
    });
})(jQuery); 