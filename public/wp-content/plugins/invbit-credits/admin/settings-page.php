<?php
/**
 * Admin settings page
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render settings page
 */
function invbit_credits_settings_page() {
    // Check permissions
    if (!current_user_can(INVBIT_CREDITS_CAPABILITY)) {
        wp_die(__('Lo sentimos, no tienes permisos para acceder a esta página.', 'invbit-credits'));
        return;
    }
    
    // Process form submission
    $submit_message = '';
    $submit_status = '';
    if (isset($_POST['invbit_credits_submit'])) {
        if (isset($_POST['invbit_credits_nonce']) && wp_verify_nonce($_POST['invbit_credits_nonce'], 'invbit_credits_save')) {
            $result = invbit_credits_save_data();
            $submit_message = $result['message'];
            $submit_status = $result['success'] ? 'success' : 'error';
        } else {
            $submit_message = __('Error de seguridad al procesar el formulario. Por favor, recarga la página.', 'invbit-credits');
            $submit_status = 'error';
        }
    }
    
    // Check if page exists
    $page_exists = invbit_credits_page_exists();
    
    // Get saved options
    $options = get_option('invbit_credits_options', []);
    
    // Default values
    $defaults = [
        'title' => 'Créditos del Proyecto',
        'description' => '',
        'project_type' => 'web_corporativa',
        'features' => []
    ];
    
    // Merge with defaults
    $options = wp_parse_args($options, $defaults);
    
    // Project types
    $project_types = [
        'web_autogestionable' => [
            'label' => 'Web autogestionable',
            'description' => 'autogestionable'
        ],
        'web_corporativa' => [
            'label' => 'Web corporativa',
            'description' => 'corporativa'
        ],
        'tienda_online' => [
            'label' => 'Tienda online',
            'description' => 'tienda online'
        ]
    ];
    
    // Define feature categories
    $feature_categories = [
        'languages' => [
            'title' => 'Lenguajes',
            'items' => ['HTML5', 'CSS3', 'JavaScript', 'TypeScript']
        ],
        'frameworks' => [
            'title' => 'Frameworks y Librerías',
            'items' => ['TailwindCSS', 'Bootstrap', 'React', 'VueJS', 'Laravel']
        ],
        'cms' => [
            'title' => 'CMS y E-commerce',
            'items' => ['WordPress', 'Diseño a medida', 'Desarrollo a medida', 'Prestashop', 'WooCommerce']
        ],
        'tools' => [
            'title' => 'Herramientas y Otros',
            'items' => ['Git', 'Figma', 'Webpack', 'APIs REST', 'APIs SOAP']
        ]
    ];
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if (!empty($submit_message)) : ?>
            <div class="notice notice-<?php echo esc_attr($submit_status); ?> is-dismissible">
                <p><?php echo esc_html($submit_message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="invbit-credits-admin">
            <form method="post" action="">
                <?php wp_nonce_field('invbit_credits_save', 'invbit_credits_nonce'); ?>
                
                <div class="invbit-credits-admin-section">
                    <h2><?php echo esc_html__('Configuración de la Página de Créditos', 'invbit-credits'); ?></h2>
                    <p><?php echo esc_html__('Personaliza el contenido que se mostrará en tu página de créditos.', 'invbit-credits'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="invbit_credits_title"><?php echo esc_html__('Nombre del cliente', 'invbit-credits'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="invbit_credits_title" id="invbit_credits_title" 
                                       value="<?php echo esc_attr($options['title']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="invbit_credits_description"><?php echo esc_html__('Descripción', 'invbit-credits'); ?></label>
                            </th>
                            <td>
                                <?php
                                $editor_settings = [
                                    'textarea_name' => 'invbit_credits_description',
                                    'textarea_rows' => 7,
                                    'media_buttons' => true,
                                    'wpautop' => true,
                                    'teeny' => false,
                                ];
                                wp_editor(wp_kses_post($options['description']), 'invbit_credits_description', $editor_settings);
                                ?>
                                <p class="description"><?php echo esc_html__('Describe el proyecto o los créditos que quieres mostrar.', 'invbit-credits'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="invbit-credits-admin-section">
                    <h2><?php echo esc_html__('¿Qué tipo de proyecto es?', 'invbit-credits'); ?></h2>
                    <p><?php echo esc_html__('Selecciona el tipo de proyecto que representa:', 'invbit-credits'); ?></p>
                    
                    <div class="invbit-credits-project-types">
                        <?php foreach ($project_types as $type_key => $type_label) : ?>
                            <label class="invbit-credits-project-type-label">
                                <input type="radio" 
                                       name="invbit_credits_project_type" 
                                       value="<?php echo esc_attr($type_key); ?>"
                                       <?php checked($options['project_type'], $type_key); ?>>
                                <?php echo esc_html($type_label['label']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="invbit-credits-admin-section">
                    <h2><?php echo esc_html__('Características Técnicas', 'invbit-credits'); ?></h2>
                    <p><?php echo esc_html__('Selecciona las características técnicas utilizadas en el proyecto:', 'invbit-credits'); ?></p>
                    
                    <div class="invbit-credits-features-grid">
                        <?php foreach ($feature_categories as $category_key => $category) : ?>
                            <div class="invbit-credits-feature-category">
                                <h3><?php echo esc_html($category['title']); ?></h3>
                                <div class="invbit-credits-feature-items">
                                    <?php foreach ($category['items'] as $item) : 
                                        $item_key = sanitize_key($item);
                                        $is_checked = isset($options['features'][$category_key]) && 
                                                      is_array($options['features'][$category_key]) &&
                                                      in_array($item_key, $options['features'][$category_key], true);
                                    ?>
                                        <label class="invbit-credits-feature-item">
                                            <input type="checkbox" 
                                                   name="invbit_credits_features[<?php echo esc_attr($category_key); ?>][]" 
                                                   value="<?php echo esc_attr($item_key); ?>"
                                                   <?php checked($is_checked, true); ?>>
                                            <?php echo esc_html($item); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="invbit-credits-admin-actions">
                    <?php if ($page_exists) : ?>
                        <p class="description">
                            <?php echo esc_html__('La página de Créditos ya existe.', 'invbit-credits'); ?> 
                            <a href="<?php echo esc_url(get_permalink($page_exists)); ?>" target="_blank"><?php echo esc_html__('Ver página', 'invbit-credits'); ?></a> | 
                            <a href="<?php echo esc_url(get_edit_post_link($page_exists)); ?>"><?php echo esc_html__('Editar página', 'invbit-credits'); ?></a>
                        </p>
                        <input type="submit" name="invbit_credits_submit" class="button button-primary" 
                               value="<?php echo esc_attr__('Actualizar Página de Créditos', 'invbit-credits'); ?>">
                    <?php else : ?>
                        <p class="description"><?php echo esc_html__('La página de Créditos aún no existe.', 'invbit-credits'); ?></p>
                        <input type="submit" name="invbit_credits_submit" class="button button-primary" 
                               value="<?php echo esc_attr__('Crear Página de Créditos', 'invbit-credits'); ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php
} 