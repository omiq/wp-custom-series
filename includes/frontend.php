<?php
/**
 * Frontend functionality for the Custom Series plugin
 *
 * @package CustomSeries
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend scripts and styles
 */
function custom_series_enqueue_frontend_assets() {
    // Only enqueue on pages that have the series block
    if (has_block('custom-series/series-block')) {
        wp_enqueue_style(
            'custom-series-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/frontend.css',
            array(),
            CUSTOM_SERIES_VERSION
        );

        wp_enqueue_script(
            'custom-series-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/frontend.js',
            array('jquery'),
            CUSTOM_SERIES_VERSION,
            true
        );

        wp_localize_script(
            'custom-series-frontend',
            'customSeriesData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_series_nonce')
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'custom_series_enqueue_frontend_assets');

/**
 * AJAX handler to fetch posts in a series
 */
function custom_series_get_posts_in_series() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_series_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    // Get series name
    $series_name = isset($_POST['series']) ? sanitize_text_field($_POST['series']) : '';
    if (empty($series_name)) {
        wp_send_json_error('Series name is required');
    }

    // Debug log
    error_log('Custom Series: Fetching posts for series: ' . $series_name);

    // Query posts in the series
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => 100,
        'meta_query' => array(
            array(
                'key' => '_series',
                'value' => $series_name,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    $posts = array();

    // Debug log
    error_log('Custom Series: Found ' . $query->found_posts . ' posts in series');

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $posts[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'link' => get_permalink(),
                'date' => get_the_date()
            );
        }
    }

    wp_reset_postdata();
    wp_send_json_success($posts);
}
add_action('wp_ajax_custom_series_get_posts', 'custom_series_get_posts_in_series');
add_action('wp_ajax_nopriv_custom_series_get_posts', 'custom_series_get_posts_in_series'); 