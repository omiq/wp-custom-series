<?php
/**
 * Series Block initialization
 */

if (!defined('ABSPATH')) {
    exit;
}

function wp_custom_series_block_init() {
    // Register block script
    wp_register_script(
        'wp-custom-series-block',
        plugins_url('index.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch')
    );

    // Register block style
    wp_register_style(
        'wp-custom-series-block-style',
        plugins_url('style.css', __FILE__),
        array()
    );

    // Register the block
    register_block_type('custom-series/series-block', array(
        'editor_script' => 'wp-custom-series-block',
        'editor_style' => 'wp-custom-series-block-style',
        'style' => 'wp-custom-series-block-style',
        'render_callback' => 'wp_custom_series_render_block'
    ));
}
add_action('init', 'wp_custom_series_block_init'); 