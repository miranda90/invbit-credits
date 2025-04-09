<?php
/**
 * Functions to create or update credits page
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if credits page exists
 * 
 * @return int|false Page ID if exists, false otherwise
 */
function invbit_credits_page_exists() {
    $page = get_page_by_path('creditos');
    return ($page) ? $page->ID : false;
}

/**
 * Create or update credits page
 * 
 * @return int|WP_Error ID of created/updated page or error
 */
function invbit_credits_create_or_update_page() {
    $page_id = invbit_credits_page_exists();
    
    // Page data
    $page_data = [
        'post_title'    => 'Créditos',
        'post_name'     => 'creditos',
        'post_content'  => '[invbit_credits]',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => get_current_user_id(),
    ];
    
    if ($page_id) {
        // Update existing page
        $page_data['ID'] = $page_id;
        $result = wp_update_post($page_data);
    } else {
        // Create new page
        $result = wp_insert_post($page_data);
    }
    
    return $result;
}

/**
 * Save form data and create/update page
 * 
 * @return array Operation result
 */
function invbit_credits_save_data() {
    // Verify nonce
    if (!isset($_POST['invbit_credits_nonce']) || !wp_verify_nonce($_POST['invbit_credits_nonce'], 'invbit_credits_save')) {
        return [
            'success' => false,
            'message' => 'Error de seguridad. Por favor, recarga la página.'
        ];
    }
    
    // Prepare data
    $options = [];
    
    // Title
    $options['title'] = isset($_POST['invbit_credits_title']) ? sanitize_text_field($_POST['invbit_credits_title']) : '';
    
    // Description
    $options['description'] = isset($_POST['invbit_credits_description']) ? wp_kses_post($_POST['invbit_credits_description']) : '';
    
    // Project Type
    $project_types = ['web_autogestionable', 'web_corporativa', 'tienda_online'];
    $options['project_type'] = isset($_POST['invbit_credits_project_type']) && in_array($_POST['invbit_credits_project_type'], $project_types) 
        ? sanitize_text_field($_POST['invbit_credits_project_type']) 
        : 'web_corporativa';
    
    // Features
    $options['features'] = [];
    $feature_categories = ['languages', 'frameworks', 'cms', 'tools'];
    
    foreach ($feature_categories as $category) {
        if (isset($_POST['invbit_credits_features'][$category]) && is_array($_POST['invbit_credits_features'][$category])) {
            $options['features'][$category] = array_map('sanitize_text_field', $_POST['invbit_credits_features'][$category]);
        }
    }
    
    // Save options
    update_option('invbit_credits_options', $options);
    
    // Create or update page
    $page_result = invbit_credits_create_or_update_page();
    
    if (is_wp_error($page_result)) {
        return [
            'success' => false,
            'message' => $page_result->get_error_message()
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Datos guardados y página actualizada correctamente.',
        'page_id' => $page_result
    ];
} 