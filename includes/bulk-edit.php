<?php
/**
 * Bulk Edit functionality for Series
 */

// Add bulk edit field to posts list
function custom_series_bulk_edit_field() {
    global $post_type;
    
    // Only show on post type 'post'
    if ($post_type !== 'post') {
        return;
    }
    
    // Get all unique series values
    global $wpdb;
    $series_list = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s ORDER BY meta_value ASC",
        '_series',
        ''
    ));
    
    // Generate a unique ID for the nonce field
    $nonce_id = 'custom_series_bulk_edit_nonce_' . uniqid();
    
    // Add nonce for security
    wp_nonce_field('custom_series_bulk_edit', 'custom_series_bulk_edit_nonce', false, true);
    ?>
    <fieldset class="inline-edit-col-right inline-edit-series">
        <div class="inline-edit-col">
            <label>
                <span class="title"><?php esc_html_e('Series', 'custom-series'); ?></span>
                <select name="series_bulk_edit">
                    <option value=""><?php esc_html_e('— No Change —', 'custom-series'); ?></option>
                    <option value="__new__"><?php esc_html_e('— New Series —', 'custom-series'); ?></option>
                    <?php foreach ($series_list as $series) : ?>
                        <option value="<?php echo esc_attr($series); ?>"><?php echo esc_html($series); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="new-series-input" style="display: none; margin-top: 5px;">
                <input type="text" name="new_series_name" placeholder="<?php esc_attr_e('Enter new series name', 'custom-series'); ?>" />
            </div>
        </div>
    </fieldset>
    <?php
}
// Add to bulk edit
add_action('bulk_edit_custom_box', 'custom_series_bulk_edit_field', 10, 2);
// Add to quick edit
add_action('quick_edit_custom_box', 'custom_series_bulk_edit_field', 10, 2);

// Enqueue JavaScript for bulk edit
function custom_series_bulk_edit_enqueue_scripts() {
    $screen = get_current_screen();
    if ($screen && $screen->base === 'edit' && $screen->post_type === 'post') {
        wp_enqueue_script(
            'custom-series-bulk-edit',
            plugins_url('../assets/js/bulk-edit.js', __FILE__),
            array('jquery'),
            CUSTOM_SERIES_VERSION,
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'custom_series_bulk_edit_enqueue_scripts');

// Handle bulk edit save
function custom_series_bulk_edit_save($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['custom_series_bulk_edit_nonce'])) {
        return;
    }

    // Verify that the nonce is valid
    if (!wp_verify_nonce($_POST['custom_series_bulk_edit_nonce'], 'custom_series_bulk_edit')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Check if series bulk edit is set
    if (isset($_POST['series_bulk_edit'])) {
        $series_value = sanitize_text_field($_POST['series_bulk_edit']);
        
        // Handle new series
        if ($series_value === '__new__' && isset($_POST['new_series_name'])) {
            $new_series = sanitize_text_field($_POST['new_series_name']);
            if (!empty($new_series)) {
                update_post_meta($post_id, '_series', $new_series);
            }
        } 
        // Handle existing series
        elseif (!empty($series_value)) {
            update_post_meta($post_id, '_series', $series_value);
        }
    }
}
add_action('save_post', 'custom_series_bulk_edit_save');

// Add Series column to posts list
function custom_series_add_column($columns) {
    $columns['series'] = __('Series', 'custom-series');
    return $columns;
}
add_filter('manage_posts_columns', 'custom_series_add_column');

// Display Series in the column
function custom_series_column_content($column, $post_id) {
    if ($column === 'series') {
        $series = get_post_meta($post_id, '_series', true);
        if (!empty($series)) {
            echo esc_html($series);
        }
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

// AJAX handler for bulk edit
function custom_series_bulk_edit_ajax() {
    // Check if our nonce is set
    if (!isset($_POST['nonce'])) {
        wp_send_json_error('Nonce not set');
    }

    // Verify that the nonce is valid
    if (!wp_verify_nonce($_POST['nonce'], 'custom_series_bulk_edit')) {
        wp_send_json_error('Invalid nonce');
    }

    // Check the user's permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }

    // Get post IDs
    $post_ids = isset($_POST['post_ids']) ? $_POST['post_ids'] : array();
    if (empty($post_ids)) {
        wp_send_json_error('No posts selected');
    }

    // Get series value
    $series_value = isset($_POST['series_value']) ? sanitize_text_field($_POST['series_value']) : '';
    
    // Update posts
    $updated = 0;
    foreach ($post_ids as $post_id) {
        if (current_user_can('edit_post', $post_id)) {
            if (!empty($series_value)) {
                update_post_meta($post_id, '_series', $series_value);
                $updated++;
            }
        }
    }

    wp_send_json_success(array(
        'updated' => $updated,
        'message' => sprintf(__('%d posts updated', 'custom-series'), $updated)
    ));
}
add_action('wp_ajax_custom_series_bulk_edit', 'custom_series_bulk_edit_ajax'); 