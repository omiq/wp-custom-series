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

    // We dont set border styles any longer so comment them out
    // $border_color = isset($attributes['borderColor']) ? $attributes['borderColor'] : '';
    // $border_width = isset($attributes['borderWidth']) ? $attributes['borderWidth'] : '';
    // $border_radius = isset($attributes['borderRadius']) ? $attributes['borderRadius'] : '';
    
    // Get typography settings from style object
    $style = isset($attributes['style']) ? $attributes['style'] : array();
    $typography = isset($style['typography']) ? $style['typography'] : array();
    $font_size = isset($attributes['fontSize']) ? $attributes['fontSize'] : '';
    $font_family = isset($attributes['fontFamily']) ? $attributes['fontFamily'] : '';
    $font_weight = isset($attributes['fontWeight']) ? $attributes['fontWeight'] : '';
    $line_height = isset($typography['lineHeight']) ? $typography['lineHeight'] : '';
    $text_transform = isset($attributes['textTransform']) ? $attributes['textTransform'] : '';
    $letter_spacing = isset($attributes['letterSpacing']) ? $attributes['letterSpacing'] : '';
    
    // Debug output
    error_log('Typography settings:');
    error_log('Line Height: ' . print_r($line_height, true));
    error_log('Full typography array: ' . print_r($typography, true));
    
    // Get spacing settings

    /*

    $attributes has the following for margin and padding:
        [spacing] => Array
                (
                    [padding] => Array
                        (
                            [top] => var:preset|spacing|30
                            [bottom] => var:preset|spacing|30
                            [left] => var:preset|spacing|70
                            [right] => var:preset|spacing|70
                        )

                    [margin] => Array
                        (
                            [top] => var:preset|spacing|60
                            [bottom] => var:preset|spacing|60
                            [left] => var:preset|spacing|30
                            [right] => var:preset|spacing|30
                        )

                )

    */

    $spacing = isset($style['spacing']) ? $style['spacing'] : array();
    $margin = isset($spacing['margin']) ? $spacing['margin'] : array();
    $padding = isset($spacing['padding']) ? $spacing['padding'] : array();
    
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

    // Print the attributes array
    // echo '<pre>';
    // print_r($attributes);
    // echo '</pre>';

    // Build style attributes from the attributes array
    $style_attrs = array();
    if (!empty($background_color)) {
        $style_attrs[] = 'background-color: var(--wp--preset--color--' . esc_attr($background_color) . ');';
    }   
    if (!empty($text_color)) {
        $style_attrs[] = 'color: var(--wp--preset--color--' . esc_attr($text_color) . ');';
    }
    if (!empty($border_color)) {
        $style_attrs[] = 'border-color: var(--wp--preset--color--' . esc_attr($border_color) . ');';
    }   
    if (!empty($border_width)) {
        $style_attrs[] = 'border-width: ' . esc_attr($border_width) . ';';
    }
    if (!empty($border_radius)) {
        $style_attrs[] = 'border-radius: ' . esc_attr($border_radius) . ';';
    }               

    // Add typography styles
    if (!empty($font_size)) {
        $style_attrs[] = 'font-size: var(--wp--preset--font-size--' . esc_attr($font_size) . ');';
    }
    if (!empty($font_family)) {
        $style_attrs[] = 'font-family: var(--wp--preset--font-family--' . esc_attr($font_family) . ');';
    }
    if (!empty($font_weight)) {
        $style_attrs[] = 'font-weight: ' . esc_attr($font_weight) . ';';
    }
    if (!empty($line_height)) {
        $style_attrs[] = 'line-height: ' . esc_attr($line_height) . 'em;';
    }
    if (!empty($text_transform)) {
        $style_attrs[] = 'text-transform: ' . esc_attr($text_transform) . ';';
    }
    if (!empty($letter_spacing)) {
        $style_attrs[] = 'letter-spacing: ' . esc_attr($letter_spacing) . 'px;';
    }
    
    // Add spacing styles
    if (!empty($margin['top'])) {
        $style_attrs[] = 'margin-top: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $margin['top'])) . ');';
    }
    if (!empty($margin['right'])) {
        $style_attrs[] = 'margin-right: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $margin['right'])) . ');';
    }
    if (!empty($margin['bottom'])) {
        $style_attrs[] = 'margin-bottom: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $margin['bottom'])) . ');';
    }
    if (!empty($margin['left'])) {
        $style_attrs[] = 'margin-left: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $margin['left'])) . ');';
    }
    if (!empty($padding['top'])) {
        $style_attrs[] = 'padding-top: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $padding['top'])) . ');';
    }
    if (!empty($padding['right'])) {
        $style_attrs[] = 'padding-right: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $padding['right'])) . ');';
    }
    if (!empty($padding['bottom'])) {
        $style_attrs[] = 'padding-bottom: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $padding['bottom'])) . ');';
    }
    if (!empty($padding['left'])) {
        $style_attrs[] = 'padding-left: var(--wp--preset--spacing--' . esc_attr(str_replace('var:preset|spacing|', '', $padding['left'])) . ');';
    }

    // Start the block output
    $classes = 'wp-block-custom-series-block';
    if (!empty($alignment)) {
        $classes .= ' align' . esc_attr($alignment);
    }
    
    // Add typography classes
    if (!empty($font_size)) {
        $classes .= ' has-' . esc_attr($font_size) . '-font-size';
    }
    if (!empty($font_family)) {
        $classes .= ' has-' . esc_attr($font_family) . '-font-family';
    }
    if (!empty($background_color)) {
        $classes .= ' has-' . esc_attr($background_color) . '-background-color has-background';
    }
    if (!empty($text_color)) {
        $classes .= ' has-' . esc_attr($text_color) . '-color has-text-color';
    }
    if (!empty($border_color)) {
        $classes .= ' has-' . esc_attr($border_color) . '-border-color';
    }
    
    $style = !empty($style_attrs) ? ' style="' . implode(' ', $style_attrs) . '"' : '';
    
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
        // Create typography styles for posts container and items
        $typography_style = '';
        if (!empty($line_height)) {
            $typography_style = ' style="line-height: ' . esc_attr($line_height) . 'em;"';
        }
        
        echo '<div class="series-posts"' . $typography_style . '>';
        echo '<ul class="series-posts-list"' . $typography_style . '>';
        
        foreach ($posts as $post) {
            $is_current_post = (int)$current_post_id === (int)$post['id'];
            $current_class = $is_current_post ? ' current-post-in-series' : '';
            
            echo '<li class="series-post-item' . esc_attr($current_class) . '"' . $typography_style . '>';
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