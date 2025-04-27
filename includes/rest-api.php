<?php
/**
 * Handles REST API integration for Custom Series.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom meta fields for REST API and block editor
 */
function custom_series_register_meta() {
    register_post_meta('post', '_series', array(
        'type' => 'string',
        'single' => true,
        'show_in_rest' => array(
            'schema' => array(
                'type' => 'string',
                'description' => 'The series name for this post'
            )
        ),
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
}

/**
 * Add meta fields to REST API response
 */
function custom_series_add_meta_to_response($response, $post, $request) {
    $response->data['meta'] = array(
        '_series' => get_post_meta($post->ID, '_series', true)
    );
    return $response;
}
add_filter('rest_prepare_post', 'custom_series_add_meta_to_response', 10, 3);

// Register for REST API
add_action('rest_api_init', 'custom_series_register_meta');

// Register for block editor
add_action('init', 'custom_series_register_meta'); 