<?php
/**
 * Plugin Name: Invbit Credits
 * Plugin URI: https://invbit.com
 * Description: Plugin to generate a credits page using shortcode
 * Version: 1.0.4
 * Author: Invbit
 * Author URI: https://invbit.com
 * Text Domain: invbit-credits
 */

if (!defined('ABSPATH')) {
    exit;
}

define('INVBIT_CREDITS_PATH', plugin_dir_path(__FILE__));
define('INVBIT_CREDITS_URL', plugin_dir_url(__FILE__));
define('INVBIT_CREDITS_VERSION', '1.0.4');
define('INVBIT_CREDITS_CAPABILITY', 'manage_options');
define('INVBIT_CREDITS_SLUG', 'diseno-web');
define('INVBIT_CREDITS_PLUGIN_FILE', __FILE__);

require_once INVBIT_CREDITS_PATH . 'includes/shortcode.php';
require_once INVBIT_CREDITS_PATH . 'includes/page-creator.php';
require_once INVBIT_CREDITS_PATH . 'admin/settings-page.php';
require_once INVBIT_CREDITS_PATH . 'includes/updater.php';
require_once INVBIT_CREDITS_PATH . 'admin/update-config.php';

function invbit_init_updater() {
    if (is_admin()) {
        $updater = new GhPluginUpdater(INVBIT_CREDITS_PLUGIN_FILE);
        $updater->init();
    }
}
add_action('init', 'invbit_init_updater');

register_activation_hook(__FILE__, 'invbit_credits_activate');
function invbit_credits_activate() {
    flush_rewrite_rules();
    update_option('invbit_credits_capabilities', INVBIT_CREDITS_CAPABILITY);
}

register_deactivation_hook(__FILE__, 'invbit_credits_deactivate');
function invbit_credits_deactivate() {
    flush_rewrite_rules();
}

add_action('admin_menu', 'invbit_credits_menu');
function invbit_credits_menu() {
    add_submenu_page(
        'tools.php',
        'Invbit Credits',
        'Invbit Credits',
        INVBIT_CREDITS_CAPABILITY,
        'invbit-credits',
        'invbit_credits_settings_page'
    );
}

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
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_editor();
    wp_enqueue_media();
}