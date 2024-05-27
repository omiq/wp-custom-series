<?php
/*
Plugin Name: Custom Series Plugin
Description: Adds a custom field "Series" to posts and provides a shortcode to list posts in a series.
Version: 1.0
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

// Add "Series" field to Quick Edit
function add_series_quick_edit($column_name, $post_type) {
    if ($column_name === 'title' && $post_type === 'post') {
        echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
        echo '<label><span class="title">Series</span><span class="input-text-wrap"><input type="text" name="series_field" class="ptitle" value=""></span></label>';
        echo '</div></fieldset>';
    }
}
add_action('quick_edit_custom_box', 'add_series_quick_edit', 10, 2);

function save_quick_edit_series($post_id) {
    if (isset($_POST['series_field'])) {
        update_post_meta($post_id, '_series', sanitize_text_field($_POST['series_field']));
    }
}
add_action('save_post', 'save_quick_edit_series');

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

// Create shortcode to display posts in the same series
function series_shortcode($atts) {
    global $post;
    $atts = shortcode_atts(array(
        'name' => '',
    ), $atts, 'series');

    if (!$atts['name']) {
        return '';
    }

    $args = array(
        'post_type' => 'post',
        'meta_key' => '_series',
        'meta_value' => $atts['name'],
        'orderby' => 'date',
        'order' => 'ASC',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $output = '<ul>';
        while ($query->have_posts()) {
            $query->the_post();
            if (get_the_ID() == $post->ID) {
                $output .= '<li>' . get_the_title() . '</li>';
            } else {
                $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            }
        }
        $output .= '</ul>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'No posts found in this series.';
    }
}
add_shortcode('series', 'series_shortcode');
