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
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Process form submission
    $submit_message = '';
    if (isset($_POST['invbit_credits_submit'])) {
        $result = invbit_credits_save_data();
        $submit_message = $result['message'];
        $submit_status = $result['success'] ? 'success' : 'error';
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
            <div class="notice notice-<?php echo $submit_status; ?> is-dismissible">
                <p><?php echo esc_html($submit_message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="invbit-credits-admin">
            <form method="post" action="">
                <?php wp_nonce_field('invbit_credits_save', 'invbit_credits_nonce'); ?>
                
                <div class="invbit-credits-admin-section">
                    <h2>Configuración de la Página de Créditos</h2>
                    <p>Personaliza el contenido que se mostrará en tu página de créditos.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="invbit_credits_title">Nombre del cliente</label>
                            </th>
                            <td>
                                <input type="text" name="invbit_credits_title" id="invbit_credits_title" 
                                       value="<?php echo esc_attr($options['title']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="invbit_credits_description">Descripción</label>
                            </th>
                            <td>
                                <?php
                                $editor_settings = [
                                    'textarea_name' => 'invbit_credits_description',
                                    'textarea_rows' => 7,
                                    'media_buttons' => true,
                                ];
                                wp_editor($options['description'], 'invbit_credits_description', $editor_settings);
                                ?>
                                <p class="description">Describe el proyecto o los créditos que quieres mostrar.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="invbit-credits-admin-section">
                    <h2>¿Qué tipo de proyecto es?</h2>
                    <p>Selecciona el tipo de proyecto que representa:</p>
                    
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
                    <h2>Características Técnicas</h2>
                    <p>Selecciona las características técnicas utilizadas en el proyecto:</p>
                    
                    <div class="invbit-credits-features-grid">
                        <?php foreach ($feature_categories as $category_key => $category) : ?>
                            <div class="invbit-credits-feature-category">
                                <h3><?php echo esc_html($category['title']); ?></h3>
                                <div class="invbit-credits-feature-items">
                                    <?php foreach ($category['items'] as $item) : 
                                        $item_key = sanitize_title($item);
                                        $is_checked = isset($options['features'][$category_key]) && 
                                                      in_array($item_key, $options['features'][$category_key]);
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
                            La página de Créditos ya existe. 
                            <a href="<?php echo esc_url(get_permalink($page_exists)); ?>" target="_blank">Ver página</a> | 
                            <a href="<?php echo esc_url(get_edit_post_link($page_exists)); ?>">Editar página</a>
                        </p>
                        <input type="submit" name="invbit_credits_submit" class="button button-primary" 
                               value="Actualizar Página de Créditos">
                    <?php else : ?>
                        <p class="description">La página de Créditos aún no existe.</p>
                        <input type="submit" name="invbit_credits_submit" class="button button-primary" 
                               value="Crear Página de Créditos">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php
} 