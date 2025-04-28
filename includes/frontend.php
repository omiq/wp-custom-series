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
 * Enqueue frontend assets
 */
function custom_series_enqueue_frontend_assets() {
    // Only enqueue on singular post pages
    if (!is_singular('post')) {
        return;
    }

    // Enqueue frontend styles
    wp_enqueue_style(
        'custom-series-frontend',
        CUSTOM_SERIES_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        CUSTOM_SERIES_VERSION
    );
}
add_action('wp_enqueue_scripts', 'custom_series_enqueue_frontend_assets');

/**
 * Add schema markup for series
 */
function custom_series_add_schema_markup() {
    // Only add schema on singular post pages
    if (!is_singular('post')) {
        return;
    }

    // Get the series name
    $series_name = get_post_meta(get_the_ID(), '_series', true);
    if (empty($series_name)) {
        return;
    }

    // Get all posts in the series
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
    $series_posts = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $series_posts[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'url' => get_permalink()
            );
        }
    }

    wp_reset_postdata();

    // Create schema markup
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'isPartOf' => array(
            '@type' => 'Series',
            'name' => $series_name,
            'numberOfItems' => count($series_posts),
            'itemListElement' => array()
        )
    );

    // Add each post to the series
    foreach ($series_posts as $index => $post) {
        $schema['isPartOf']['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => array(
                '@type' => 'Article',
                'url' => $post['url'],
                'name' => $post['title']
            )
        );
    }

    // Output schema markup
    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
}
add_action('wp_head', 'custom_series_add_schema_markup'); 