/**
 * Frontend JavaScript for the Custom Series plugin
 */
(function($) {
    'use strict';

    // Function to load posts in a series
    function loadSeriesPosts(container) {
        const seriesName = container.data('series');
        if (!seriesName) return;

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
                    let html = '<ul class="series-posts">';
                    
                    // Get current post ID from the page
                    const currentPostId = customSeriesData.currentPostId;
                    
                    posts.forEach(function(post) {
                        // Add current-post-in-series class if this is the current post
                        const isCurrentPost = currentPostId && post.id == currentPostId;
                        const currentClass = isCurrentPost ? ' current-post-in-series' : '';
                        
                        html += `<li class="series-post-item${currentClass}"><a href="${post.link}">${post.title}</a></li>`;
                    });
                    
                    html += '</ul>';
                    container.html(html);
                }
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        $('.series-posts-container').each(function() {
            loadSeriesPosts($(this));
        });
    });

})(jQuery); 