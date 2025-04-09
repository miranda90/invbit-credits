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
    
    // Validar que el tipo de proyecto existe
    if (!isset($project_type_labels[$options['project_type']])) {
        $options['project_type'] = 'web_corporativa'; // Valor por defecto si es inválido
    }
    
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
    <section class="invbit-credits-container">
        <div class="invbit-credits-row">
            <!-- Left Column: Custom Content -->
            <section class="invbit-credits-column invbit-credits-left">
                <h2 class="invbit-credits-title">
                    <?php 
                        echo sprintf(
                            'Diseño y desarrollo web <span class="invbit-credits-project-type-label">%s</span> para %s', 
                            esc_html($project_type_labels[$options['project_type']]['description']), 
                            esc_html($options['title'])
                        ); 
                    ?>
                </h2>
                
                <section class="invbit-credits-description">
                    <?php echo wp_kses_post($options['description']); ?>
                </section>
                
                <section class="invbit-credits-features">
                    <ul class="invbit-credits-feature-list">
                        <?php foreach ($feature_categories as $category_key => $category) : ?>
                            <?php foreach ($category['items'] as $item) : 
                                $item_key = sanitize_title($item);
                                $is_checked = isset($options['features'][$category_key]) && 
                                              is_array($options['features'][$category_key]) &&
                                              in_array($item_key, $options['features'][$category_key], true);
                                if ($is_checked) :
                                ?>
                                    <li class="invbit-credits-feature-item">
                                        <?php echo esc_html($item); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </section>
            
            <!-- Right Column: Fixed Company Info -->
            <aside class="invbit-credits-column invbit-credits-right">
                <div class="invbit-credits-company">
                    <div class="invbit-credits-logo">
                        <?php 
                        $logo_path = INVBIT_CREDITS_PATH . 'assets/logo.svg';
                        $logo_url = INVBIT_CREDITS_URL . 'assets/logo.svg';
                        if (file_exists($logo_path)) : 
                        ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr__('Logo Empresa', 'invbit-credits'); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="invbit-credits-slogan">
                        <h3><?php echo esc_html__('Un equipo de expertos en diseño web y marketing digital', 'invbit-credits'); ?></h3>
                    </div>
                    <div class="invbit-credits-contact">
                        <p>
                            <a href="<?php echo esc_url('https://invbit.com'); ?>" target="_blank" rel="noopener noreferrer">
                                <svg width="23" height="24" viewBox="0 0 23 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.4201 22.4758C13.4668 22.4754 15.4674 21.8683 17.169 20.731C18.8706 19.5937 20.1968 17.9775 20.9799 16.0865C21.7629 14.1956 21.9677 12.1149 21.5684 10.1076C21.169 8.1003 20.1834 6.25647 18.7362 4.80927C17.289 3.36206 15.4452 2.37646 13.4378 1.9771C11.4305 1.57774 9.34987 1.78254 7.45894 2.56562C5.56801 3.34869 3.95173 4.67487 2.81447 6.37647C1.6772 8.07808 1.07002 10.0787 1.0697 12.1253C1.06949 13.4846 1.33706 14.8307 1.85715 16.0865C2.37723 17.3424 3.13963 18.4835 4.1008 19.4447C5.06196 20.4058 6.20307 21.1682 7.45894 21.6883C8.71481 22.2084 10.0608 22.476 11.4201 22.4758V22.4758Z" stroke="#45C2B1" stroke-width="2.06976" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M1.58911 15.372H20.9556" stroke="#45C2B1" stroke-width="2.06976" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M1.58911 8.87866H20.9556" stroke="#45C2B1" stroke-width="2.06976" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9.18635 2.02005C7.32889 8.72191 7.60235 15.8356 9.9688 22.3751" stroke="#45C2B1" stroke-width="2.06976" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M13.6507 2.02005C14.4621 4.93664 14.8717 7.9503 14.8682 10.9776C14.8764 14.8637 14.2039 18.7211 12.8812 22.3751" stroke="#45C2B1" stroke-width="2.06976" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo esc_html('www.invbit.com'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo esc_url('mailto:info@invbit.com'); ?>">
                                <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22.4942 7.53036V17.755C22.4942 18.4329 22.2248 19.0831 21.7455 19.5625C21.2661 20.0418 20.6159 20.3111 19.938 20.3111H4.60108C3.92314 20.3111 3.27297 20.0418 2.7936 19.5625C2.31423 19.0831 2.04492 18.4329 2.04492 17.755V7.53036" stroke="#E54360" stroke-width="2.04492" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.4942 7.53037C22.4942 6.85243 22.2248 6.20226 21.7455 5.72289C21.2661 5.24352 20.6159 4.97421 19.938 4.97421H4.60108C3.92314 4.97421 3.27297 5.24352 2.7936 5.72289C2.31423 6.20226 2.04492 6.85243 2.04492 7.53037L10.9148 13.0687C11.321 13.3226 11.7905 13.4572 12.2695 13.4572C12.7486 13.4572 13.218 13.3226 13.6243 13.0687L22.4942 7.53037Z" stroke="#E54360" stroke-width="2.04492" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo esc_html('info@invbit.com'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo esc_url('tel:+34881998674'); ?>">
                                <svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_6077_3408)">
                                    <path d="M13.6932 19.7542L13.7034 19.7613C14.5831 20.3214 15.6275 20.5646 16.6641 20.4508C17.7007 20.337 18.6674 19.8729 19.4046 19.1352L20.0449 18.4948C20.1868 18.353 20.2994 18.1846 20.3762 17.9993C20.453 17.814 20.4926 17.6153 20.4926 17.4147C20.4926 17.2141 20.453 17.0154 20.3762 16.8301C20.2994 16.6447 20.1868 16.4763 20.0449 16.3345L17.344 13.6356C17.2022 13.4937 17.0338 13.3812 16.8485 13.3043C16.6631 13.2275 16.4645 13.188 16.2638 13.188C16.0632 13.188 15.8646 13.2275 15.6792 13.3043C15.4939 13.3812 15.3255 13.4937 15.1837 13.6356C14.8973 13.9219 14.509 14.0827 14.104 14.0827C13.6991 14.0827 13.3107 13.9219 13.0244 13.6356L8.70574 9.31599C8.41946 9.02961 8.25863 8.64126 8.25863 8.23633C8.25863 7.8314 8.41946 7.44305 8.70574 7.15667C8.84765 7.01486 8.96022 6.84647 9.03703 6.66113C9.11383 6.47579 9.15336 6.27713 9.15336 6.07651C9.15336 5.87588 9.11383 5.67723 9.03703 5.49189C8.96022 5.30655 8.84765 5.13816 8.70574 4.99634L6.00584 2.29745C5.71946 2.01117 5.33111 1.85034 4.92618 1.85034C4.52125 1.85034 4.1329 2.01117 3.84652 2.29745L3.20514 2.93782C2.46765 3.67508 2.00375 4.64188 1.89009 5.67848C1.77644 6.71508 2.01983 7.75943 2.58005 8.63897L2.58616 8.64915C5.54484 13.0266 9.31524 16.7963 13.6932 19.7542V19.7542Z" stroke="#C2D500" stroke-width="1.86278" stroke-linecap="round" stroke-linejoin="round"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_6077_3408">
                                    <rect width="22.3534" height="22.3534" fill="white" transform="translate(0 0.323303)"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <?php echo esc_html('881 998 674'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </section>
    
    <style>
        :root {
            --plugin-color: #c2d500;
        }
        .invbit-credits-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            container-type: inline-size;
            container-name: invbit-credits;
        }
        .invbit-credits-row {
            --gap: 2rem;
            display: flex;
            flex-direction: column;
            gap: var(--gap);
            @container invbit-credits (width >= 960px) {
                @media (width >= 1280px) {
                    --gap: 6rem;
                    display: grid;
                    grid-template-columns: 1fr 460px;
                }
            }
        }
        .invbit-credits-column {
            flex: 1;
        }
        .invbit-credits-right {
            border: 1px solid hsla(0,0%,95%,1);
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            background-image: url(<?php echo esc_url(INVBIT_CREDITS_URL . 'assets/card-invbit-bubble.svg'); ?>);
            background-size: 400px auto;
            background-position: 0 90%;
            background-repeat: repeat-x;
            padding-bottom: 10rem;
            @container invbit-credits (width >= 960px) {
                padding: 3rem;
                min-height: 34rem;
            }
        }
        .invbit-credits-title {
            --fs: 2rem;
            line-height: 1.2;
            font-size: var(--fs);
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 1rem;
            @container invbit-credits (width >= 960px) {
                @media (width >= 1280px) {
                    --fs: 3rem;
                }
            }
            & span {
                color: var(--primary, var(--mfn-woo-themecolor, var(--plugin-color)));
            }
        }
        .invbit-credits-project-type {
            margin-bottom: 15px;
        }

        .invbit-credits-description {
            --fs: 1rem;
            line-height: 1.6;
            font-size: var(--fs);
            margin-bottom: 1rem;
            @container invbit-credits (width >= 960px) {
                --fs: 1.1rem;
            }
            & strong {
                font-weight: 700;
            }
            & a {
                color: var(--primary, var(--mfn-woo-themecolor, var(--plugin-color)));
                text-decoration: underline;
            }
        }
        .invbit-credits-feature-category h3 {
            margin: 20px 0 10px;
            font-size: 18px;
            color: #23282d;
        }
        ul.invbit-credits-feature-list {
            --mt: 2rem;
            --gap: 1rem 1.2rem;
            list-style: none;
            padding: 0;
            margin: var(--mt) 0 0 0;
            display: flex;
            flex-wrap: wrap;
            gap: var(--gap);
            @container invbit-credits (width >= 960px) {
                --mt: 3rem;
                --gap: 1.5rem 2.5rem;
            }
        }
        .invbit-credits-feature-item {
            --fs: 1rem;
            line-height: 1.2;
            font-size: var(--fs);
            font-weight: 500;
            margin-bottom: 0;
            position: relative;
            display: flex;
            align-items: center;
            @container invbit-credits (width >= 960px) {
                --fs: 1.5rem;
            }
            &::before {
                --size: .5rem;
                content: "";
                position: relative;
                display: inline-flex;
                background: black;
                background-color: var(--primary, var(--mfn-woo-themecolor, var(--plugin-color)));
                width: var(--size);
                height: var(--size);
                border-radius: 50%;
                margin-right: .5rem;
                @container invbit-credits (width >= 960px) {
                    --size: .7rem;
                }
            }
        }

        .invbit-credits-logo {
            margin-bottom: 20px;
        }
        .invbit-credits-logo img {
            --max-w: 150px;
            max-width: var(--max-w);
            height: auto;
            @container invbit-credits (width >= 960px) or (width >= 1280px) {
                --max-w: 200px;
            }
        }
        .invbit-credits-slogan {
            & h3 {
                --fs: 1.4rem;
                margin-bottom: 3rem;
                line-height: 1.2;
                font-size: var(--fs);
                font-weight: bold;
                color: hsla(80, 5%, 11%, 1);
                @container invbit-credits (width >= 960px) {
                    --fs: 2rem;
                }
            }
        }
        .invbit-credits-contact {
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            gap: .8rem;
        }
        .invbit-credits-contact p {
            --fs: 1rem;
            font-size: var(--fs);
            margin: 0;
        }
        .invbit-credits-contact a {
            display: flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            & svg {
                --size: 1.2rem;
                width: var(--size);
                height: var(--size);
            }
        }
        .invbit-credits-contact a:hover {
            text-decoration: underline;
        }
    </style>
    <?php
    // Return buffer content
    return ob_get_clean();
} 