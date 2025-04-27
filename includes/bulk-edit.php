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
    
    // Add nonce for security
    wp_nonce_field('custom_series_bulk_edit', 'custom_series_bulk_edit_nonce');
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
add_action('bulk_edit_custom_box', 'custom_series_bulk_edit_field', 10, 2);

// Enqueue JavaScript for bulk edit
function custom_series_bulk_edit_enqueue_scripts() {
    $screen = get_current_screen();
    if ($screen && $screen->base === 'edit' && $screen->post_type === 'post') {
        wp_enqueue_script(
            'custom-series-bulk-edit',
            plugins_url('../assets/js/bulk-edit.js', __FILE__),
            array('jquery', 'wp-data', 'wp-element'),
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