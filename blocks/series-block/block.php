<?php
/**
 * Register and render the Series block
 */

function register_series_block() {
    // Register the block script
    wp_register_script(
        'custom-series-block',
        plugins_url('index.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render'),
        filemtime(plugin_dir_path(__FILE__) . 'index.js')
    );

    // Register the block
    register_block_type('custom-series/series-block', array(
        'editor_script' => 'custom-series-block',
        'render_callback' => 'render_series_block',
        'attributes' => array(
            'seriesName' => array(
                'type' => 'string',
                'default' => ''
            ),
            'alignment' => array(
                'type' => 'string',
                'default' => 'none'
            ),
            'showTitle' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showDescription' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'padding' => array(
                'type' => 'object',
                'default' => array(
                    'top' => '20px',
                    'right' => '20px',
                    'bottom' => '20px',
                    'left' => '20px'
                )
            ),
            'margin' => array(
                'type' => 'object',
                'default' => array(
                    'top' => '2em',
                    'right' => '0',
                    'bottom' => '2em',
                    'left' => '0'
                )
            ),
            'backgroundColor' => array(
                'type' => 'string',
                'default' => '#f9f9f9'
            ),
            'textColor' => array(
                'type' => 'string',
                'default' => ''
            ),
            'titleColor' => array(
                'type' => 'string',
                'default' => ''
            ),
            'borderWidth' => array(
                'type' => 'number',
                'default' => 0
            ),
            'borderColor' => array(
                'type' => 'string',
                'default' => '#ddd'
            ),
            'borderRadius' => array(
                'type' => 'number',
                'default' => 4
            )
        )
    ));
}
add_action('init', 'register_series_block');

/**
 * Render callback for the Series block
 */
function render_series_block($attributes) {
    // Get the series content using the existing shortcode function
    $series_content = series_shortcode(array(
        'name' => isset($attributes['seriesName']) ? $attributes['seriesName'] : ''
    ));
    
    // If no content, return empty
    if (empty($series_content)) {
        return '';
    }
    
    // Extract the title and description if they exist
    $title = '';
    $description = '';
    $list_content = $series_content;
    
    // Check if we need to extract title and description
    if (isset($attributes['showTitle']) && !$attributes['showTitle']) {
        // Remove title if it exists
        $list_content = preg_replace('/<h2 class="custom-series-title">.*?<\/h2>/s', '', $list_content);
    }
    
    if (isset($attributes['showDescription']) && !$attributes['showDescription']) {
        // Remove description if it exists
        $list_content = preg_replace('/<div class="custom-series-description">.*?<\/div>/s', '', $list_content);
    }
    
    // Build inline styles based on attributes
    $style = '';
    
    // Alignment
    if (isset($attributes['alignment']) && $attributes['alignment'] !== 'none') {
        $style .= 'text-align: ' . esc_attr($attributes['alignment']) . ';';
    }
    
    // Padding
    if (isset($attributes['padding'])) {
        $style .= 'padding: ' . 
            esc_attr($attributes['padding']['top']) . ' ' . 
            esc_attr($attributes['padding']['right']) . ' ' . 
            esc_attr($attributes['padding']['bottom']) . ' ' . 
            esc_attr($attributes['padding']['left']) . ';';
    }
    
    // Margin
    if (isset($attributes['margin'])) {
        $style .= 'margin: ' . 
            esc_attr($attributes['margin']['top']) . ' ' . 
            esc_attr($attributes['margin']['right']) . ' ' . 
            esc_attr($attributes['margin']['bottom']) . ' ' . 
            esc_attr($attributes['margin']['left']) . ';';
    }
    
    // Background color
    if (isset($attributes['backgroundColor'])) {
        $style .= 'background-color: ' . esc_attr($attributes['backgroundColor']) . ';';
    }
    
    // Text color
    if (isset($attributes['textColor']) && !empty($attributes['textColor'])) {
        $style .= 'color: ' . esc_attr($attributes['textColor']) . ';';
    }
    
    // Border
    if (isset($attributes['borderWidth']) && $attributes['borderWidth'] > 0) {
        $style .= 'border: ' . esc_attr($attributes['borderWidth']) . 'px solid ' . esc_attr($attributes['borderColor']) . ';';
    }
    
    // Border radius
    if (isset($attributes['borderRadius'])) {
        $style .= 'border-radius: ' . esc_attr($attributes['borderRadius']) . 'px;';
    }
    
    // Title color
    $title_style = '';
    if (isset($attributes['titleColor']) && !empty($attributes['titleColor'])) {
        $title_style = 'color: ' . esc_attr($attributes['titleColor']) . ';';
    }
    
    // Apply title color to the title if it exists
    if (!empty($title_style)) {
        $list_content = preg_replace(
            '/<h2 class="custom-series-title">/',
            '<h2 class="custom-series-title" style="' . $title_style . '">',
            $list_content
        );
    }
    
    // Wrap the content in a div with the inline styles
    $output = '<div class="custom-series" style="' . $style . '">' . $list_content . '</div>';
    
    return $output;
} 