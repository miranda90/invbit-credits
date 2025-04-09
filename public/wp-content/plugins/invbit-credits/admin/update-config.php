<?php
/**
 * Update Configuration
 */

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
    echo '<h3>Update Information</h3>';
    echo '<p>Current version: <strong>' . esc_html(INVBIT_CREDITS_VERSION) . '</strong></p>';
    
    echo '<button id="check-updates" class="button button-secondary">Comprobar actualizaciones</button>';
    echo '<span id="update-status"></span>';
    
    echo '</div>';
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#check-updates').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#update-status');
            
            $button.prop('disabled', true);
            $status.html('<span class="spinner is-active" style="float:none;margin:0 10px;"></span> Checking for updates...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'invbit_check_updates',
                    nonce: '<?php echo wp_create_nonce('invbit_check_updates_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span style="color:green;">' + response.data.message + '</span>');
                    } else {
                        $status.html('<span style="color:red;">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color:red;">Error checking for updates.</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
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
    
    delete_site_transient('update_plugins');
    wp_update_plugins();
    
    $update_plugins = get_site_transient('update_plugins');
    $plugin_file = plugin_basename(INVBIT_CREDITS_PATH . 'plugin-main.php');
    
    if (isset($update_plugins->response[$plugin_file])) {
        $new_version = $update_plugins->response[$plugin_file]->new_version;
        wp_send_json_success(array(
            'message' => "New version available! Version $new_version ready to install.",
            'version' => $new_version,
            'has_update' => true
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'You are using the latest version available.',
            'version' => INVBIT_CREDITS_VERSION,
            'has_update' => false
        ));
    }
}
add_action('wp_ajax_invbit_check_updates', 'invbit_ajax_check_updates'); 