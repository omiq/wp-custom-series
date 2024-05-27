<?php
/*
Plugin Name: Custom Series Plugin
Description: Adds a custom field "Series" to posts and provides a shortcode to list posts in a series.
Version: 1.5
Author: Chris Garrett
*/

// Add the custom field "Series" to the post editing screen
function add_series_meta_box() {
    add_meta_box('series_meta', 'Series', 'series_meta_callback', 'post', 'side', 'default');
}
add_action('add_meta_boxes', 'add_series_meta_box');

function series_meta_callback($post) {
    $value = get_post_meta($post->ID, '_series', true);
    echo '<label for="series_field">Series: </label>';
    echo '<input type="text" id="series_field" name="series_field" value="' . esc_attr($value) . '" />';
}

function save_series_meta_box_data($post_id) {
    if (array_key_exists('series_field', $_POST)) {
        update_post_meta($post_id, '_series', sanitize_text_field($_POST['series_field']));
    }
}
add_action('save_post', 'save_series_meta_box_data');

// Add Series column to posts list
function add_series_column($columns) {
    $columns['series'] = 'Series';
    return $columns;
}
add_filter('manage_post_posts_columns', 'add_series_column');

function fill_series_column($column, $post_id) {
    if ($column === 'series') {
        $series = get_post_meta($post_id, '_series', true);
        echo esc_html($series);
    }
}
add_action('manage_post_posts_custom_column', 'fill_series_column', 10, 2);

// Add "Series" field to Quick Edit
function add_series_quick_edit($column_name, $post_type) {
    if ($column_name === 'series' && $post_type === 'post') {
        ?>
        <fieldset class="inline-edit-col-right inline-edit-series">
            <div class="inline-edit-col">
                <label>
                    <span class="title">Series</span>
                    <span class="input-text-wrap"><input type="text" name="series_field" class="ptitle" value=""></span>
                </label>
            </div>
        </fieldset>
        <?php
    }
}
add_action('quick_edit_custom_box', 'add_series_quick_edit', 10, 2);

// Enqueue JavaScript for Quick Edit
function enqueue_quick_edit_js($hook) {
    if ($hook === 'edit.php') {
        wp_enqueue_script('quick_edit_series', plugin_dir_url(__FILE__) . 'quick-edit-series.js', array('jquery', 'inline-edit-post'), '', true);
    }
}
add_action('admin_enqueue_scripts', 'enqueue_quick_edit_js');

// JavaScript for Quick Edit
add_action('admin_footer', 'quick_edit_series_js');
function quick_edit_series_js() {
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            var $wp_inline_edit = inlineEditPost.edit;
            inlineEditPost.edit = function(id) {
                $wp_inline_edit.apply(this, arguments);
                var post_id = 0;
                if (typeof(id) == 'object') {
                    post_id = parseInt(this.getId(id));
                }
                if (post_id > 0) {
                    var $edit_row = $('#edit-' + post_id);
                    var $post_row = $('#post-' + post_id);
                    var series = $post_row.find('.column-series').text();
                    $edit_row.find('input[name="series_field"]').val(series);
                }
            };
        });
    </script>
    <?php
}

// Save Quick Edit data
function save_quick_edit_series($post_id) {
    if (isset($_POST['series_field'])) {
        update_post_meta($post_id, '_series', sanitize_text_field($_POST['series_field']));
    }
}
add_action('save_post', 'save_quick_edit_series');

// Create shortcode to display posts in the same series
function series_shortcode($atts) {
    global $post;
    $atts = shortcode_atts(array(
        'name' => '',
    ), $atts, 'series');

    if (!$atts['name']) {
        return '';
    }

    $series_name = $atts['name'];
    $current_post_id = get_the_ID();
    $series_title = get_option('series_' . $series_name . '_title', '');
    $series_description = get_option('series_' . $series_name . '_description', '');

    $args = array(
        'post_type' => 'post',
        'meta_key' => '_series',
        'meta_value' => $series_name,
        'orderby' => 'date',
        'order' => 'ASC',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $output = '<div class="custom-series">';
        if ($series_title) {
            $output .= '<h2 class="custom-series-title">' . esc_html($series_title) . '</h2>';
        }
        if ($series_description) {
            $output .= '<div class="custom-series-description">' . wp_kses_post($series_description) . '</div>';
        }
        $output .= '<ul class="custom-series-list">';
        while ($query->have_posts()) {
            $query->the_post();
            if (get_the_ID() == $current_post_id) {
                $output .= '<li class="custom-series-list-item current">' . get_the_title() . '</li>';
            } else {
                $output .= '<li class="custom-series-list-item"><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            }
        }
        $output .= '</ul></div>';
        wp_reset_postdata();
        return $output;
    } else {
        return '<div class="custom-series">No posts found in this series.</div>';
    }
}
add_shortcode('series', 'series_shortcode');

// Create admin menu for Series management
function series_admin_menu() {
    add_menu_page('Series Management', 'Series', 'manage_options', 'series-management', 'series_management_page', 'dashicons-editor-ol', 20);
}
add_action('admin_menu', 'series_admin_menu');

// Display Series Management Page
function series_management_page() {
    global $wpdb;
    
    // Save series title and description
    if (isset($_POST['save_series'])) {
        $series = sanitize_text_field($_POST['series']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        update_option('series_' . $series . '_title', $title);
        update_option('series_' . $series . '_description', $description);
        echo '<div class="updated"><p>Series details saved.</p></div>';
    }

    // Get all unique series values where the series is not blank
    $series_list = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_series' AND meta_value != '' ORDER BY meta_value ASC");

    ?>
    <div class="wrap">
        <h1>Series Management</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Series</th>
                    <th scope="col">Title</th>
                    <th scope="col">Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($series_list as $series): ?>
                    <tr>
                        <td><?php echo esc_html($series); ?></td>
                        <form method="post">
                            <td><input type="text" name="title" value="<?php echo esc_attr(get_option('series_' . $series . '_title')); ?>" /></td>
                            <td><textarea name="description"><?php echo esc_textarea(get_option('series_' . $series . '_description')); ?></textarea></td>
                            <td>
                                <input type="hidden" name="series" value="<?php echo esc_attr($series); ?>" />
                                <input type="submit" name="save_series" class="button-primary" value="Save" />
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
