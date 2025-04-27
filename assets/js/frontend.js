/**
 * Frontend JavaScript for the Custom Series plugin
 */
(function($) {
    'use strict';

    // Function to load posts in a series
    function loadSeriesPosts() {
        $('.series-posts-container').each(function() {
            const container = $(this);
            const seriesName = container.data('series');
            
            if (!seriesName) {
                container.html('<p>Please select a series in the block settings.</p>');
                return;
            }

            // Show loading state
            container.html('<p>Loading posts...</p>');

            // Fetch posts via AJAX
            $.ajax({
                url: customSeriesData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'custom_series_get_posts',
                    nonce: customSeriesData.nonce,
                    series: seriesName
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const posts = response.data;
                        
                        if (posts.length > 0) {
                            let html = '<ul class="series-posts-list">';
                            
                            posts.forEach(function(post) {
                                html += `<li><a href="${post.link}">${post.title}</a> <span class="post-date">${post.date}</span></li>`;
                            });
                            
                            html += '</ul>';
                            container.html(html);
                        } else {
                            container.html('<p>No posts found in this series.</p>');
                        }
                    } else {
                        container.html('<p>Error loading posts.</p>');
                        console.error('Error response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    container.html('<p>Error loading posts.</p>');
                    console.error('AJAX error:', status, error);
                }
            });
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        loadSeriesPosts();
    });

})(jQuery); 