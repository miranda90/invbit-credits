<?php
/**
 * Plugin Name: Invbit Credits
 * Plugin URI: https://invbit.com
 * Description: Plugin para generar una página de créditos mediante shortcode
 * Version: 1.0.4
 * Author: Invbit
 * Author URI: https://invbit.com
 * Text Domain: invbit-credits
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('INVBIT_CREDITS_PATH', plugin_dir_path(__FILE__));
define('INVBIT_CREDITS_URL', plugin_dir_url(__FILE__));
define('INVBIT_CREDITS_VERSION', '1.0.2');
define('INVBIT_CREDITS_CAPABILITY', 'manage_options');
define('INVBIT_CREDITS_SLUG', 'diseno-web');

// Include files
require_once INVBIT_CREDITS_PATH . 'includes/shortcode.php';
require_once INVBIT_CREDITS_PATH . 'includes/page-creator.php';
require_once INVBIT_CREDITS_PATH . 'admin/settings-page.php';

require_once INVBIT_CREDITS_PATH . 'includes/updater.php';
require_once INVBIT_CREDITS_PATH . 'admin/update-config.php';

if (class_exists('Invbit_Plugin_Updater')) {
    $github_username = get_option('invbit_github_username', 'miranda90');
    $github_repo = get_option('invbit_github_repo', 'invbit-credits');
    $github_token = get_option('invbit_github_token', '');
    
    $updater = new Invbit_Plugin_Updater(array(
        'slug' => 'invbit-credits',
        'plugin_name' => 'Invbit Credits',
        'plugin_file' => plugin_basename(__FILE__),
        'version' => INVBIT_CREDITS_VERSION,
        'github_username' => $github_username,
        'github_repo' => $github_repo,
        'github_api_key' => $github_token,
    ));
}

// Plugin activation
register_activation_hook(__FILE__, 'invbit_credits_activate');
function invbit_credits_activate() {
    flush_rewrite_rules();
    update_option('invbit_credits_capabilities', INVBIT_CREDITS_CAPABILITY);
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'invbit_credits_deactivate');
function invbit_credits_deactivate() {
    flush_rewrite_rules();
}

// Admin menu in Tools submenu
add_action('admin_menu', 'invbit_credits_menu');
function invbit_credits_menu() {
    add_submenu_page(
        'tools.php',
        'Créditos Invbit',
        'Créditos Invbit',
        INVBIT_CREDITS_CAPABILITY,
        'invbit-credits',
        'invbit_credits_settings_page'
    );
}

// Admin assets
add_action('admin_enqueue_scripts', 'invbit_credits_admin_assets');
function invbit_credits_admin_assets($hook) {
    if ('tools_page_invbit-credits' !== $hook) {
        return;
    }
    
    $admin_css = INVBIT_CREDITS_URL . 'assets/admin-style.css';
    if (file_exists(INVBIT_CREDITS_PATH . 'assets/admin-style.css')) {
        wp_enqueue_style(
            'invbit-credits-admin-style',
            $admin_css,
            [],
            INVBIT_CREDITS_VERSION
        );
    }
    
    // Load WordPress styles
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_editor();
    wp_enqueue_media();
} 