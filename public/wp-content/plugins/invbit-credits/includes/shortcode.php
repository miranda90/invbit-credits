<?php
/**
 * Shortcode registration and processing
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

// Register shortcode
add_shortcode('invbit_credits', 'invbit_credits_shortcode');

/**
 * Process [invbit_credits] shortcode
 */
function invbit_credits_shortcode($atts) {
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
    
    // Project type labels
    $project_type_labels = [
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
    
    // Feature categories structure
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
    
    // Start output buffer
    ob_start();
    ?>
    <div class="invbit-credits-container">
        <div class="invbit-credits-row">
            <!-- Left Column: Custom Content -->
            <div class="invbit-credits-column invbit-credits-left">
                <h2 class="invbit-credits-title"><?= sprintf('Diseño y desarrollo web %s para %s', $project_type_labels[$options['project_type']]['description'], $options['title']); ?></h2>
                
                <?php if (isset($project_type_labels[$options['project_type']])) : ?>
                <div class="invbit-credits-project-type">
                    <span class="invbit-credits-project-type-label">
                        <?php echo esc_html($project_type_labels[$options['project_type']]); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="invbit-credits-description">
                    <?php echo wp_kses_post($options['description']); ?>
                </div>
                
                <div class="invbit-credits-features">
                    <?php foreach ($feature_categories as $category_key => $category) : ?>
                        <div class="invbit-credits-feature-category">
                            <h3><?php echo esc_html($category['title']); ?></h3>
                            <ul class="invbit-credits-feature-list">
                                <?php foreach ($category['items'] as $item) : 
                                    $item_key = sanitize_title($item);
                                    $is_checked = isset($options['features'][$category_key]) && 
                                                  in_array($item_key, $options['features'][$category_key]);
                                    if ($is_checked) :
                                ?>
                                    <li class="invbit-credits-feature-item">
                                        <span class="invbit-credits-feature-check">✓</span>
                                        <?php echo esc_html($item); ?>
                                    </li>
                                <?php endif; endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Right Column: Fixed Company Info -->
            <div class="invbit-credits-column invbit-credits-right">
                <div class="invbit-credits-company">
                    <div class="invbit-credits-logo">
                        <img src="<?php echo esc_url(INVBIT_CREDITS_URL . 'assets/logo.svg'); ?>" alt="Logo Empresa">
                    </div>
                    <div class="invbit-credits-slogan">
                        <p>Tu proyecto, nuestro compromiso.</p>
                    </div>
                    <div class="invbit-credits-contact">
                        <p><strong>Web:</strong> <a href="https://tuempresa.com" target="_blank">https://tuempresa.com</a></p>
                        <p><strong>Email:</strong> <a href="mailto:contacto@tuempresa.com">contacto@tuempresa.com</a></p>
                        <p><strong>Teléfono:</strong> +34 600 123 456</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .invbit-credits-container {
            max-width: 1200px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .invbit-credits-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
        .invbit-credits-column {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            margin: 15px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            border-radius: 4px;
        }
        .invbit-credits-title {
            margin-top: 0;
            color: #23282d;
            font-size: 24px;
            font-weight: 600;
        }
        .invbit-credits-project-type {
            margin-bottom: 15px;
        }
        .invbit-credits-project-type-label {
            display: inline-block;
            padding: 5px 12px;
            background-color: #c2d500;
            color: #1d1e1b;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        .invbit-credits-description {
            margin-bottom: 20px;
            color: #444;
            line-height: 1.6;
        }
        .invbit-credits-feature-category h3 {
            margin: 20px 0 10px;
            font-size: 18px;
            color: #23282d;
        }
        .invbit-credits-feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .invbit-credits-feature-item {
            padding: 5px 0;
            font-size: 16px;
            color: #444;
        }
        .invbit-credits-feature-check {
            color: #46b450;
            margin-right: 10px;
            font-weight: bold;
        }
        .invbit-credits-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .invbit-credits-logo img {
            max-width: 200px;
            height: auto;
        }
        .invbit-credits-slogan {
            text-align: center;
            font-style: italic;
            margin-bottom: 20px;
            font-size: 18px;
            color: #555;
        }
        .invbit-credits-contact {
            line-height: 1.6;
        }
        .invbit-credits-contact p {
            margin: 5px 0;
        }
        .invbit-credits-contact a {
            color: #0073aa;
            text-decoration: none;
        }
        .invbit-credits-contact a:hover {
            text-decoration: underline;
        }
        
        @media screen and (max-width: 768px) {
            .invbit-credits-row {
                flex-direction: column;
            }
            .invbit-credits-column {
                margin-bottom: 20px;
            }
        }
    </style>
    <?php
    // Return buffer content
    return ob_get_clean();
} 