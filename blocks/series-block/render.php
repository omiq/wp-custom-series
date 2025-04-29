<?php
/**
 * Server-side rendering for the Series Block
 *
 * @package CustomSeries
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the series block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Returns the series block with posts.
 */
function custom_series_render_block($attributes, $content, $block) {
    // Get attributes
    $series_name = isset($attributes['seriesName']) ? $attributes['seriesName'] : '';
    $show_title = isset($attributes['showTitle']) ? $attributes['showTitle'] : true;
    $alignment = isset($attributes['alignment']) ? $attributes['alignment'] : '';
    $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : '';
    $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '';
    $border_color = isset($attributes['borderColor']) ? $attributes['borderColor'] : '';
    $border_width = isset($attributes['borderWidth']) ? $attributes['borderWidth'] : '';
    $border_radius = isset($attributes['borderRadius']) ? $attributes['borderRadius'] : '';
    
    // Get current post ID
    $current_post_id = get_the_ID();
    
    // Start output buffering
    ob_start();
    
    // Check if we have a series name
    if (empty($series_name)) {
        echo '<div class="wp-block-custom-series-block series-placeholder">';
        echo esc_html__('Select a series to display', 'custom-series');
        echo '</div>';
        return ob_get_clean();
    }
    
    // Get posts in the series with caching
    $cache_key = 'custom_series_posts_' . sanitize_key($series_name);
    $posts = wp_cache_get($cache_key);
    
    if (false === $posts) {
        // Query posts in the series
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_series',
                    'value' => $series_name,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'date' => get_the_date()
                );
            }
        }
        
        wp_reset_postdata();
        
        // Cache the results for 1 hour
        wp_cache_set($cache_key, $posts, '', HOUR_IN_SECONDS);
    }

    // Build style attributes from the attributes array
    $style_attrs = array();
    if (!empty($background_color)) {
        $style_attrs[] = 'background-color: var(--wp--preset--color--' . esc_attr($background_color) . ')';
    }   
    if (!empty($text_color)) {
        $style_attrs[] = 'color: var(--wp--preset--color--' . esc_attr($text_color) . ')';
    }
    if (!empty($border_color)) {
        $style_attrs[] = 'border-color: var(--wp--preset--color--' . esc_attr($border_color) . ')';
    }   
    if (!empty($border_width)) {
        $style_attrs[] = 'border-width: ' . esc_attr($border_width);
    }
    if (!empty($border_radius)) {
        $style_attrs[] = 'border-radius: ' . esc_attr($border_radius);
    }               

    // Start the block output
    $classes = 'wp-block-custom-series-block';
    if (!empty($alignment)) {
        $classes .= ' align' . esc_attr($alignment);
    }
    
    $style = !empty($style_attrs) ? ' style="' . implode('; ', $style_attrs) . '"' : '';
    
    echo '<div class="' . esc_attr($classes) . '"' . $style . '>';
    
    // Show title if enabled
    if ($show_title && !empty($series_name)) {
        echo '<h2 class="series-title">' . esc_html($series_name) . '</h2>';
    }
    
    // Check if we have posts
    if (empty($posts)) {
        echo '<div class="series-placeholder">';
        echo esc_html__('No posts found in this series', 'custom-series');
        echo '</div>';
    } else {
        echo '<div class="series-posts">';
        echo '<ul class="series-posts-list">';
        
        foreach ($posts as $post) {
            $is_current_post = (int)$current_post_id === (int)$post['id'];
            $current_class = $is_current_post ? ' current-post-in-series' : '';
            
            echo '<li class="series-post-item' . esc_attr($current_class) . '">';
            if ($is_current_post) {
                echo '<span class="current-post">' . esc_html($post['title']) . '</span>';
            } else {
                echo '<a href="' . esc_url($post['link']) . '">' . esc_html($post['title']) . '</a>';
            }
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Return the buffered content
    return ob_get_clean();
} 