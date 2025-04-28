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
    
    // Get all unique series values
    global $wpdb;
    $series_list = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s ORDER BY meta_value ASC",
        '_series',
        ''
    ));
    
    // Output the field
    echo '<div class="custom-series-meta-box">';
    echo '<label for="custom_series_field">';
    esc_html_e('Series Name:', 'custom-series');
    echo '</label> ';
    
    // Dropdown for existing series
    echo '<select id="custom_series_field" name="custom_series_field" class="widefat">';
    echo '<option value="">' . esc_html__('— Select Series —', 'custom-series') . '</option>';
    echo '<option value="__new__">' . esc_html__('— New Series —', 'custom-series') . '</option>';
    
    foreach ($series_list as $series) {
        echo '<option value="' . esc_attr($series) . '"' . selected($value, $series, false) . '>' . esc_html($series) . '</option>';
    }
    
    echo '</select>';
    
    // New series input field (hidden by default)
    echo '<div id="new_series_input" style="display: none; margin-top: 5px;">';
    echo '<input type="text" id="new_series_name" name="new_series_name" placeholder="' . esc_attr__('Enter new series name', 'custom-series') . '" class="widefat" />';
    echo '</div>';
    
    echo '</div>';
    
    // Add JavaScript to show/hide the new series input
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var seriesSelect = $('#custom_series_field');
        var newSeriesInput = $('#new_series_input');
        
        // Function to toggle the new series input
        function toggleNewSeriesInput() {
            if (seriesSelect.val() === '__new__') {
                newSeriesInput.show();
            } else {
                newSeriesInput.hide();
            }
        }
        
        // Initial check
        toggleNewSeriesInput();
        
        // Check on change
        seriesSelect.on('change', toggleNewSeriesInput);
    });
    </script>
    <?php
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

    // Get the series value
    $series_value = sanitize_text_field($_POST['custom_series_field']);
    
    // Handle new series
    if ($series_value === '__new__' && isset($_POST['new_series_name'])) {
        $new_series = sanitize_text_field($_POST['new_series_name']);
        if (!empty($new_series)) {
            update_post_meta($post_id, '_series', $new_series);
        }
    } 
    // Handle existing series or empty value
    else {
        update_post_meta($post_id, '_series', $series_value);
    }
}
add_action('save_post', 'custom_series_save_meta_box_data'); 