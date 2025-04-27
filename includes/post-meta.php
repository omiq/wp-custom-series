<?php
/**
 * Handles Post Meta for the Series taxonomy.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds the meta box container.
 */
function custom_series_add_meta_box() {
    // error_log('Custom Series Plugin: custom_series_add_meta_box() running.'); // DEBUG
    add_meta_box(
        'custom_series_meta',                // ID
        __('Series', 'custom-series'),      // Title
        'custom_series_meta_box_callback',  // Callback function
        'post',                             // Screen (post type)
        'side',                             // Context (normal, side, advanced)
        'default'                           // Priority (high, core, default, low)
    );
}
add_action('add_meta_boxes', 'custom_series_add_meta_box');

/**
 * Render Meta Box content.
 *
 * @param WP_Post $post The post object.
 */
function custom_series_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('custom_series_save_meta_box_data', 'custom_series_meta_box_nonce');

    // Get existing value
    $value = get_post_meta($post->ID, '_series', true);

    // Output the field
    echo '<label for="custom_series_field">';
    esc_html_e('Series Name:', 'custom-series');
    echo '</label> ';
    echo '<input type="text" id="custom_series_field" name="custom_series_field" value="' . esc_attr($value) . '" size="25" />';
}

/**
 * Save the meta when the post is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */
function custom_series_save_meta_box_data($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['custom_series_meta_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['custom_series_meta_box_nonce'], 'custom_series_save_meta_box_data')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Check if the field is set.
    if (!isset($_POST['custom_series_field'])) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field($_POST['custom_series_field']);

    // Update the meta field in the database.
    update_post_meta($post_id, '_series', $my_data);
}
add_action('save_post', 'custom_series_save_meta_box_data'); 