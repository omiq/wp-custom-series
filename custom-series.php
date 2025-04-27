<?php
/**
 * Plugin Name:       Custom Series
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Manage and display posts as part of a series.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-series
 * Domain Path:       /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('CUSTOM_SERIES_VERSION', '1.1.0');
define('CUSTOM_SERIES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_SERIES_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/post-meta.php';
// require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/shortcodes.php'; // File missing
// require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/widgets.php';    // File missing
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/bulk-edit.php';
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/rest-api.php';   // File exists now
// require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/blocks.php'; // Commenting out for now, might conflict

// Register the block type from block.json
function custom_series_register_block() {
    $block_json_path = CUSTOM_SERIES_PLUGIN_DIR . 'blocks/series-block/block.json';
    if (file_exists($block_json_path)) {
        register_block_type_from_metadata($block_json_path);
    }
}
add_action('init', 'custom_series_register_block');

// Enqueue block editor assets
function custom_series_enqueue_block_editor_assets() {
    $asset_file = include(CUSTOM_SERIES_PLUGIN_DIR . 'blocks/series-block/build/index.asset.php');
    wp_enqueue_script(
        'custom-series-block-editor',
        CUSTOM_SERIES_PLUGIN_URL . 'blocks/series-block/build/index.js',
        $asset_file['dependencies'],
        $asset_file['version']
    );
}
add_action('enqueue_block_editor_assets', 'custom_series_enqueue_block_editor_assets');

// Add Series column to posts list
function custom_series_add_column($columns) {
    $columns['series'] = __('Series', 'custom-series');
    return $columns;
}
add_filter('manage_posts_columns', 'custom_series_add_column');

// Display Series value in the column
function custom_series_column_content($column, $post_id) {
    if ($column === 'series') {
        $series = get_post_meta($post_id, '_series', true);
        echo esc_html($series);
    }
}
add_action('manage_posts_custom_column', 'custom_series_column_content', 10, 2);

// Make Series column sortable
function custom_series_sortable_column($columns) {
    $columns['series'] = 'series';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'custom_series_sortable_column');

// Handle sorting by Series
function custom_series_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby === 'series') {
        $query->set('meta_key', '_series');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'custom_series_orderby');

// Remove the redundant enqueue function
// function custom_series_enqueue_block_editor_assets() { ... }
// add_action('enqueue_block_editor_assets', 'custom_series_enqueue_block_editor_assets'); 