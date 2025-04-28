<?php
/**
 * Plugin Name:       Custom Series
 * Plugin URI:        https://hivewp.com/custom-series/
 * Description:       Manage, interlink, and display posts as part of a series.
 * Version:           1.1.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Chris Garrett
 * Author URI:        https://hivewp.com
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
define('CUSTOM_SERIES_VERSION', '1.1.1');
define('CUSTOM_SERIES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_SERIES_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/post-meta.php';
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/bulk-edit.php';
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/rest-api.php';
require_once CUSTOM_SERIES_PLUGIN_DIR . 'includes/frontend.php';
require_once CUSTOM_SERIES_PLUGIN_DIR . 'blocks/series-block/render.php';

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

// Note: Column functions have been moved to includes/bulk-edit.php 

// Register the block render callback
function custom_series_register_block_render_callback() {
    register_block_type('custom-series/series-block', array(
        'render_callback' => 'custom_series_render_block'
    ));
}
add_action('init', 'custom_series_register_block_render_callback');

// Activation hook
register_activation_hook(__FILE__, 'custom_series_activate');
function custom_series_activate() {
    // Activation code here
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'custom_series_deactivate');
function custom_series_deactivate() {
    // Deactivation code here
    flush_rewrite_rules();
} 