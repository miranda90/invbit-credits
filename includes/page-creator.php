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
    $page_slug = INVBIT_CREDITS_SLUG; // Usa la constante en lugar de texto hardcodeado
    $page = get_page_by_path($page_slug);
    
    // Verificar colisiones con otros tipos de post
    $existing_post = get_page_by_path($page_slug, OBJECT, ['post', 'product', 'attachment']);
    if ($existing_post && $existing_post->post_type !== 'page') {
        return false; // Hay colisión con otro tipo de contenido
    }
    
    return ($page) ? $page->ID : false;
}

/**
 * Create or update credits page
 * 
 * @return int|WP_Error ID of created/updated page or error
 */
function invbit_credits_create_or_update_page() {
    // Verificar permisos
    if (!current_user_can(INVBIT_CREDITS_CAPABILITY)) {
        return new WP_Error('insufficient_permissions', __('No tienes permisos para crear o actualizar páginas.', 'invbit-credits'));
    }
    
    $page_id = invbit_credits_page_exists();
    
    // Page data
    $page_data = [
        'post_title'    => 'Diseño web',
        'post_name'     => INVBIT_CREDITS_SLUG,
        'post_content'  => '[invbit_credits]',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => get_current_user_id(),
    ];
    
    if ($page_id) {
        // Verificar si tenemos permisos para editar esta página específica
        if (!current_user_can('edit_page', $page_id)) {
            return new WP_Error('cannot_edit_page', __('No tienes permisos para editar esta página.', 'invbit-credits'));
        }
        
        // Update existing page
        $page_data['ID'] = $page_id;
        $result = wp_update_post($page_data);
    } else {
        // Verificar permisos para crear páginas
        if (!current_user_can('publish_pages')) {
            return new WP_Error('cannot_create_page', __('No tienes permisos para crear páginas.', 'invbit-credits'));
        }
        
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
    // Verificar permisos
    if (!current_user_can(INVBIT_CREDITS_CAPABILITY)) {
        return [
            'success' => false,
            'message' => __('No tienes permisos suficientes.', 'invbit-credits')
        ];
    }
    
    // Verify nonce
    if (!isset($_POST['invbit_credits_nonce']) || !wp_verify_nonce($_POST['invbit_credits_nonce'], 'invbit_credits_save')) {
        return [
            'success' => false,
            'message' => __('Error de seguridad. Por favor, recarga la página.', 'invbit-credits')
        ];
    }
    
    // Prepare data
    $options = [];
    
    // Title
    $options['title'] = isset($_POST['invbit_credits_title']) ? sanitize_text_field($_POST['invbit_credits_title']) : '';
    
    // Description - usar función específica para contenido HTML
    $options['description'] = isset($_POST['invbit_credits_description']) ? wp_kses_post($_POST['invbit_credits_description']) : '';
    
    // Project Type
    $project_types = ['web_autogestionable', 'web_corporativa', 'tienda_online'];
    $options['project_type'] = isset($_POST['invbit_credits_project_type']) && in_array($_POST['invbit_credits_project_type'], $project_types, true) 
        ? sanitize_text_field($_POST['invbit_credits_project_type']) 
        : 'web_corporativa';
    
    // Features
    $options['features'] = [];
    $feature_categories = ['languages', 'frameworks', 'cms', 'tools'];
    
    if (isset($_POST['invbit_credits_features']) && is_array($_POST['invbit_credits_features'])) {
        foreach ($feature_categories as $category) {
            if (isset($_POST['invbit_credits_features'][$category]) && is_array($_POST['invbit_credits_features'][$category])) {
                $options['features'][$category] = array_map('sanitize_key', $_POST['invbit_credits_features'][$category]);
            }
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
        'message' => __('Datos guardados y página actualizada correctamente.', 'invbit-credits'),
        'page_id' => $page_result
    ];
} 