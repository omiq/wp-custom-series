<?php
/*
Plugin Name: Custom Series Plugin
Description: Adds a custom field "Series" to posts and provides a shortcode to list posts in a series.
Version: 1.7
Author: Chris Garrett
*/

// Add the custom field "Series" to the post editing screen
function add_series_meta_box() {
    add_meta_box('series_meta', 'Series', 'series_meta_callback', 'post', 'side', 'default');
}
add_action('add_meta_boxes', 'add_series_meta_box');

function series_meta_callback($post) {
    // Add nonce for security
    wp_nonce_field('series_meta_box', 'series_meta_box_nonce');
    
    $value = get_post_meta($post->ID, '_series', true);
    ?>
    <label for="series_field">Series: </label>
    <input type="text" id="series_field" name="series_field" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function save_series_meta_box_data($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['series_meta_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid
    if (!wp_verify_nonce($_POST['series_meta_box_nonce'], 'series_meta_box')) {
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

    if (!$atts['name'] && is_singular('post')) {
        $atts['name'] = get_post_meta(get_the_ID(), '_series', true);
    }

    if (!$atts['name']) {
        return 'No series specified and no series found for the current post.';
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
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    
    // Save series title and description
    if (isset($_POST['save_series']) && isset($_POST['series_nonce']) && wp_verify_nonce($_POST['series_nonce'], 'save_series_details')) {
        $series = sanitize_text_field($_POST['series']);
        $title = sanitize_text_field($_POST['title']);
        $description = wp_kses_post($_POST['description']);
        update_option('series_' . $series . '_title', $title);
        update_option('series_' . $series . '_description', $description);
        echo '<div class="updated"><p>' . esc_html__('Series details saved.', 'custom-series') . '</p></div>';
    }

    // Get all unique series values where the series is not blank
    $series_list = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s ORDER BY meta_value ASC",
        '_series',
        ''
    ));

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Series Management', 'custom-series'); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Series', 'custom-series'); ?></th>
                    <th scope="col"><?php echo esc_html__('Title', 'custom-series'); ?></th>
                    <th scope="col"><?php echo esc_html__('Description', 'custom-series'); ?></th>
                    <th scope="col"><?php echo esc_html__('Posts', 'custom-series'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($series_list as $series): ?>
                    <tr>
                        <td><?php echo esc_html($series); ?></td>
                        <form method="post">
                            <?php wp_nonce_field('save_series_details', 'series_nonce'); ?>
                            <td><input type="text" name="title" value="<?php echo esc_attr(get_option('series_' . $series . '_title')); ?>" /></td>
                            <td><textarea name="description"><?php echo esc_textarea(get_option('series_' . $series . '_description')); ?></textarea></td>
                            <td>
                                <?php
                                $args = array(
                                    'post_type' => 'post',
                                    'meta_key' => '_series',
                                    'meta_value' => $series,
                                    'orderby' => 'date',
                                    'order' => 'ASC',
                                    'posts_per_page' => -1,
                                );
                                $posts_query = new WP_Query($args);
                                if ($posts_query->have_posts()) {
                                    echo '<ul>';
                                    while ($posts_query->have_posts()) {
                                        $posts_query->the_post();
                                        echo '<li><a href="' . esc_url(get_edit_post_link()) . '">' . esc_html(get_the_title()) . '</a></li>';
                                    }
                                    echo '</ul>';
                                    wp_reset_postdata();
                                } else {
                                    echo esc_html__('No posts found.', 'custom-series');
                                }
                                ?>
                            </td>
                            <td>
                                <input type="hidden" name="series" value="<?php echo esc_attr($series); ?>" />
                                <input type="submit" name="save_series" class="button-primary" value="<?php echo esc_attr__('Save', 'custom-series'); ?>" />
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Include the block
require_once plugin_dir_path(__FILE__) . 'blocks/series-block/block.php';

// Include bulk edit functionality
require_once plugin_dir_path(__FILE__) . 'includes/bulk-edit.php';

// Add block editor styles
function custom_series_block_editor_styles() {
    wp_enqueue_style(
        'custom-series-block-editor',
        plugins_url('blocks/series-block/editor.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'blocks/series-block/editor.css')
    );
}
add_action('enqueue_block_editor_assets', 'custom_series_block_editor_styles');

// Add frontend styles
function custom_series_styles() {
    wp_enqueue_style(
        'custom-series',
        plugins_url('css/style.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'css/style.css')
    );
}
add_action('wp_enqueue_scripts', 'custom_series_styles');
add_action('admin_enqueue_scripts', 'custom_series_styles');

// Register block category
function custom_series_block_category($categories) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'custom-series',
                'title' => __('Custom Series', 'custom-series'),
            ),
        )
    );
}
add_filter('block_categories_all', 'custom_series_block_category', 10, 1);
