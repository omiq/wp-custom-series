<?php
/**
 * Register blocks for the Custom Series plugin
 */

function custom_series_register_blocks() {
    // Register the block using block.json
    register_block_type(__DIR__ . '/../blocks/series-block', array(
        'render_callback' => 'custom_series_render_block'
    ));
}
add_action('init', 'custom_series_register_blocks');

/**
 * Render callback for the series block
 */
function custom_series_render_block($attributes) {
    // Get the series name
    $series_name = !empty($attributes['seriesName']) ? $attributes['seriesName'] : get_post_meta(get_the_ID(), '_series', true);
    
    if (empty($series_name)) {
        return '<p>' . esc_html__('No series specified.', 'custom-series') . '</p>';
    }

    // Get series posts
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'meta_key' => '_series',
        'meta_value' => $series_name,
        'orderby' => 'date',
        'order' => 'ASC'
    );

    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p>' . esc_html__('No posts found in this series.', 'custom-series') . '</p>';
    }

    // Build the output
    $style = sprintf(
        'background-color: %s; color: %s; padding: %s %s %s %s; margin: %s %s %s %s; border: %dpx solid %s; border-radius: %dpx;',
        esc_attr($attributes['backgroundColor']),
        esc_attr($attributes['textColor']),
        esc_attr($attributes['padding']['top']),
        esc_attr($attributes['padding']['right']),
        esc_attr($attributes['padding']['bottom']),
        esc_attr($attributes['padding']['left']),
        esc_attr($attributes['margin']['top']),
        esc_attr($attributes['margin']['right']),
        esc_attr($attributes['margin']['bottom']),
        esc_attr($attributes['margin']['left']),
        esc_attr($attributes['borderWidth']),
        esc_attr($attributes['borderColor']),
        esc_attr($attributes['borderRadius'])
    );

    $title_style = !empty($attributes['titleColor']) ? sprintf('color: %s;', esc_attr($attributes['titleColor'])) : '';
    $alignment_class = !empty($attributes['alignment']) ? 'align-' . esc_attr($attributes['alignment']) : '';

    $output = sprintf('<div class="wp-block-custom-series-series-block %s" style="%s">', $alignment_class, $style);
    
    if ($attributes['showTitle']) {
        $output .= sprintf('<h2 class="series-title" style="%s">%s</h2>', $title_style, esc_html($series_name));
    }

    $output .= '<ul class="series-posts">';
    
    while ($query->have_posts()) {
        $query->the_post();
        $current = get_the_ID() === get_queried_object_id();
        $output .= sprintf(
            '<li class="series-post%s"><a href="%s">%s</a></li>',
            $current ? ' current' : '',
            esc_url(get_permalink()),
            esc_html(get_the_title())
        );
    }
    
    $output .= '</ul></div>';
    
    wp_reset_postdata();
    return $output;
} 