<?php

if (!defined('ABSPATH')) {
    exit;
}

function invbit_add_update_section() {
    add_settings_section(
        'invbit_updater_section',
        'Update Configuration',
        'invbit_updater_section_callback',
        'invbit-credits'
    );

    add_settings_field(
        'invbit_github_username',
        'GitHub Username',
        'invbit_github_username_callback',
        'invbit-credits',
        'invbit_updater_section'
    );

    add_settings_field(
        'invbit_github_repo',
        'Repository',
        'invbit_github_repo_callback',
        'invbit-credits',
        'invbit_updater_section'
    );

    add_settings_field(
        'invbit_github_token',
        'API Token (optional)',
        'invbit_github_token_callback',
        'invbit-credits',
        'invbit_updater_section'
    );

    register_setting('invbit-credits', 'invbit_github_username', 'sanitize_text_field');
    register_setting('invbit-credits', 'invbit_github_repo', 'sanitize_text_field');
    register_setting('invbit-credits', 'invbit_github_token', 'sanitize_text_field');
}
add_action('admin_init', 'invbit_add_update_section');

function invbit_updater_section_callback() {
    echo '<div class="invbit-update-section">';
    echo '<p><strong>Configure repository data for automatic updates.</strong></p>';
    echo '<p>Updates will be performed from GitHub when you publish new versions in your repository.</p>';
    echo '<p>To configure automatic updates:</p>';
    echo '<ol>';
    echo '<li>Enter your GitHub username</li>';
    echo '<li>Enter the repository name (must match exactly with GitHub)</li>';
    echo '<li>If the repository is private, add a personal access token</li>';
    echo '</ol>';
    echo '<p>⭐ New versions must be published as "releases" in GitHub with the version number as tag (example: "1.0.3").</p>';
    echo '</div>';
}

function invbit_github_username_callback() {
    $github_username = get_option('invbit_github_username', 'miranda90');
    echo '<input type="text" id="invbit_github_username" name="invbit_github_username" value="' . esc_attr($github_username) . '" class="regular-text">';
    echo '<p class="description"><strong>GitHub username</strong> where the repository is hosted.<br>Example: <code>miranda90</code></p>';
}

function invbit_github_repo_callback() {
    $github_repo = get_option('invbit_github_repo', 'invbit-credits');
    echo '<input type="text" id="invbit_github_repo" name="invbit_github_repo" value="' . esc_attr($github_repo) . '" class="regular-text">';
    echo '<p class="description"><strong>Repository name</strong> containing the plugin.<br>Example: <code>invbit-credits</code></p>';
}

function invbit_github_token_callback() {
    $github_token = get_option('invbit_github_token', '');
    echo '<input type="password" id="invbit_github_token" name="invbit_github_token" value="' . esc_attr($github_token) . '" class="regular-text">';
    echo '<p class="description">GitHub token for private repositories (optional). Leave blank for public repositories.</p>';
}

function invbit_display_update_info() {
    echo '<div class="update-info">';
    echo '<h3>Version Information</h3>';
    echo '<p>Current version: <strong>' . esc_html(INVBIT_CREDITS_VERSION) . '</strong></p>';
    
    echo '<div class="invbit-update-actions">';
    echo '<button id="check-updates" class="button button-primary">Check for updates</button>';
    echo '<button id="force-update" class="button button-secondary" style="margin-left:10px;">Force update</button>';
    echo '</div>';
    
    echo '<div id="update-status" class="invbit-update-status" style="margin-top:15px;"></div>';
    
    $update_info = get_transient('github_plugin_invbit_credits');
    if ($update_info && isset($update_info['has_update']) && $update_info['has_update']) {
        echo '<div class="invbit-update-available" style="margin-top:20px; padding:15px; background:#f0f9e8; border-left:4px solid #46b450; box-shadow:0 1px 1px rgba(0,0,0,.04);">';
        echo '<h3 style="margin-top:0;">New version available!</h3>';
        echo '<p>Current version: <strong>' . esc_html(INVBIT_CREDITS_VERSION) . '</strong></p>';
        echo '<p>Latest version: <strong>' . esc_html($update_info['latest_version']) . '</strong></p>';
        echo '<p>An update is ready to be installed. You can update this plugin from the <a href="' . admin_url('plugins.php') . '">Plugins</a> page.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        function clearBrowserCache() {
            if (window.caches) {
                caches.keys().then(function(names) {
                    for (let name of names) caches.delete(name);
                });
            }
        }
        
        function checkForUpdates(forceUpdate) {
            var $button = forceUpdate ? $('#force-update') : $('#check-updates');
            var $status = $('#update-status');
            
            $button.prop('disabled', true);
            $status.html('<span class="spinner is-active" style="float:none;margin:0 10px;"></span> Checking for updates...');
            
            var timestamp = new Date().getTime();
            
            $.ajax({
                url: ajaxurl + '?nocache=' + timestamp,
                type: 'POST',
                cache: false,
                data: {
                    action: 'invbit_check_updates',
                    nonce: '<?php echo wp_create_nonce('invbit_check_updates_nonce'); ?>',
                    force_update: forceUpdate ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.has_update) {
                            $status.html('<span style="color:green;font-weight:bold;">' + response.data.message + '</span>');
                            
                            setTimeout(function() {
                                alert('A new version has been detected! The page will reload to show the available update.');
                                clearBrowserCache();
                                window.location.reload(true);
                            }, 1500);
                        } else {
                            $status.html('<span style="color:blue;">' + response.data.message + '</span>');
                        }
                    } else {
                        $status.html('<span style="color:red;">' + (response.data ? response.data.message : 'Unknown error') + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error);
                    $status.html('<span style="color:red;">Error checking for updates: ' + error + '</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        }
        
        $('#check-updates').on('click', function(e) {
            e.preventDefault();
            checkForUpdates(false);
        });
        
        $('#force-update').on('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to force the update check? This will clear all caches.')) {
                checkForUpdates(true);
            }
        });
        
        $(document).on('wp-plugin-update-error', function(event, response) {
            console.log('Captured update error:', response);
            if (response && response.errorMessage) {
                alert('Error during update: ' + response.errorMessage);
            }
        });
        
        window.onerror = function(message, source, lineno, colno, error) {
            if (message.includes("Cannot read properties of undefined (reading 'attr')") ||
                message.includes("Cannot read property 'attr' of undefined")) {
                console.log('Intercepted update error');
                alert('An error occurred during the update. Please try the update manually or contact support.');
                return true; 
            }
            return false; 
        };
    });
    </script>
    <?php
}

function invbit_ajax_check_updates() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'invbit_check_updates_nonce')) {
        wp_send_json_error(array(
            'message' => 'Security error. Please reload the page and try again.'
        ));
    }
    
    if (!current_user_can(INVBIT_CREDITS_CAPABILITY)) {
        wp_send_json_error(array(
            'message' => 'You do not have permission to perform this action.'
        ));
    }
    
    $force_update = isset($_POST['force_update']) && $_POST['force_update'] == 1;
    
    if ($force_update) {
        wp_clear_scheduled_hook('wp_update_plugins');
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');
        delete_site_transient('update_core');
        delete_transient('github_plugin_invbit_credits');
        delete_transient('github_repo_info_invbit_credits');
        delete_transient('update_plugins');
        delete_transient('update_themes');
        delete_transient('update_core');
        wp_clean_plugins_cache(true);
        wp_clean_themes_cache(true);
        wp_clean_update_cache();
    } else {
        delete_site_transient('update_plugins');
        delete_transient('github_plugin_invbit_credits');
        delete_transient('github_repo_info_invbit_credits');
        wp_clean_plugins_cache();
    }
    
    wp_update_plugins();
    
    $current_version = ltrim(INVBIT_CREDITS_VERSION, 'v');
    
    $github_username = get_option('invbit_github_username', 'miranda90');
    $github_repo = get_option('invbit_github_repo', 'invbit-credits');
    $github_token = get_option('invbit_github_token', '');
    
    $api_url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $github_username, $github_repo);
    $headers = array(
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . ' - Plugin Invbit Credits' . ($force_update ? ' (AJAX Forced Check)' : ''),
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    );
    
    if (!empty($github_token)) {
        error_log('Invbit Credits - Usando token de autenticación de GitHub');
        $headers['Authorization'] = 'token ' . $github_token;
    } else {
        error_log('Invbit Credits - No se ha configurado token de GitHub (limitado a 60 peticiones/hora)');
    }
    
    $api_url .= '?t=' . time();
    error_log('Invbit Credits - Consultando API: ' . $api_url);
    error_log('Invbit Credits - Headers: ' . print_r($headers, true));
    
    $response = wp_remote_get($api_url, array(
        'headers' => $headers,
        'timeout' => 30,
        'sslverify' => true,
        'blocking' => true,
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Error connecting to GitHub: ' . $response->get_error_message(),
        ));
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if (200 !== $response_code) {
        wp_send_json_error(array(
            'message' => 'Error in GitHub response (Code ' . $response_code . ')',
        ));
        return;
    }
    
    $github_data = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($github_data) || !isset($github_data['tag_name'])) {
        wp_send_json_error(array(
            'message' => 'Invalid GitHub response format',
        ));
        return;
    }
    
    $remote_version = ltrim($github_data['tag_name'], 'v');
    $has_update = version_compare($remote_version, $current_version, '>');
    
    $download_url = '';
    if (isset($github_data['assets']) && !empty($github_data['assets'])) {
        foreach ($github_data['assets'] as $asset) {
            if (isset($asset['name']) && strpos($asset['name'], '.zip') !== false) {
                $download_url = $asset['browser_download_url'];
                break;
            }
        }
    }
    
    if (empty($download_url)) {
        $download_url = sprintf(
            'https://github.com/%s/%s/archive/refs/tags/%s.zip',
            $github_username,
            $github_repo,
            $github_data['tag_name']
        );
    }
    
    $update_info = array(
        'current_version' => $current_version,
        'latest_version' => $remote_version,
        'has_update' => $has_update,
        'last_check' => time(),
        'download_url' => $download_url,
    );
    
    $cache_time = $force_update ? 5 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS;
    set_transient('github_plugin_invbit_credits', $update_info, $cache_time);
    
    if ($has_update) {
        $update_plugins = get_site_transient('update_plugins');
        $plugin_file = plugin_basename(INVBIT_CREDITS_PATH . 'plugin-main.php');
        
        if (is_object($update_plugins)) {
            $update_plugins->response[$plugin_file] = (object) array(
                'slug' => 'invbit-credits',
                'plugin' => $plugin_file,
                'new_version' => $remote_version,
                'package' => $download_url,
                'tested' => '6.4.2',
                'url' => 'https://github.com/' . $github_username . '/' . $github_repo,
            );
            
            $update_plugins->checked[$plugin_file] = $current_version;
            
            set_site_transient('update_plugins', $update_plugins);
        }
    }
    
    if ($has_update) {
        wp_send_json_success(array(
            'message' => sprintf('New version %s available! Your current version is %s', 
                $remote_version, 
                $current_version
            ),
            'version' => $remote_version,
            'has_update' => true,
            'current_version' => $current_version,
            'force_refresh' => true,
            'forced_check' => $force_update
        ));
    } else {
        wp_send_json_success(array(
            'message' => sprintf('You are using the latest version (%s)', 
                $current_version
            ),
            'version' => $current_version,
            'has_update' => false,
            'force_refresh' => true,
            'forced_check' => $force_update
        ));
    }
}
add_action('wp_ajax_invbit_check_updates', 'invbit_ajax_check_updates');

function invbit_init_update_display() {
    add_action('admin_init', 'invbit_add_update_section');
    
    $screen = get_current_screen();
    if (isset($screen->id) && $screen->id === 'tools_page_invbit-credits') {
        if (isset($_GET['tab']) && $_GET['tab'] === 'updates') {
            invbit_silent_check_update();
        }
    }
}
add_action('current_screen', 'invbit_init_update_display');

function invbit_silent_check_update() {
    if (!current_user_can(INVBIT_CREDITS_CAPABILITY)) {
        return;
    }
    
    $last_check = get_transient('invbit_last_update_check');
    if (!$last_check || (time() - $last_check > HOUR_IN_SECONDS)) {
        delete_site_transient('update_plugins');
        delete_transient('github_plugin_invbit_credits');
        delete_transient('github_repo_info_invbit_credits');
        wp_clean_plugins_cache();
        
        wp_update_plugins();
        
        set_transient('invbit_last_update_check', time(), HOUR_IN_SECONDS);
    }
} 