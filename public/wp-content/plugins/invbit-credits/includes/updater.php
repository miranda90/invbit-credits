<?php
/**
 * Plugin Updater
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class Invbit_Plugin_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;
    private $plugin_data;
    private $plugin_name;
    private $slug;
    private $version;

    public function __construct($config = array()) {
        $this->plugin_name = isset($config['plugin_name']) ? $config['plugin_name'] : '';
        $this->version = isset($config['version']) ? $config['version'] : '';
        $this->file = isset($config['plugin_file']) ? $config['plugin_file'] : '';
        $this->slug = isset($config['slug']) ? $config['slug'] : '';
        $this->username = isset($config['github_username']) ? $config['github_username'] : '';
        $this->repository = isset($config['github_repo']) ? $config['github_repo'] : '';
        $this->authorize_token = isset($config['github_api_key']) ? $config['github_api_key'] : '';

        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        if (file_exists(WP_PLUGIN_DIR . '/' . dirname($this->basename) . '/readme.txt')) {
            $this->plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->basename);
        }
        
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        add_filter('plugin_action_links_' . $this->basename, array($this, 'plugin_action_links'));
    }

    public function plugin_row_meta($links, $file) {
        if ($file == $this->basename) {
            $this->get_repository_info();
            
            if (isset($this->github_response['tag_name']) && version_compare($this->github_response['tag_name'], $this->version, '>')) {
                $links[] = '<span style="color: #ff6d6d;">New version available: ' . esc_html($this->github_response['tag_name']) . '</span>';
            } else {
                $links[] = '<span style="color: #58B058;">Está actualizado</span>';
            }
            
            $links[] = '<a href="https://github.com/' . esc_attr($this->username) . '/' . esc_attr($this->repository) . '" target="_blank">View on GitHub</a>';
        }
        
        return $links;
    }
    
    public function plugin_action_links($links) {
        $check_update_link = '<a href="#" id="invbit-check-updates">Comprobar actualizaciones</a>';
        add_action('admin_footer', array($this, 'admin_footer_script'));
        
        $settings_link = '<a href="' . admin_url('tools.php?page=invbit-credits') . '">Configuración</a>';
        
        array_unshift($links, $check_update_link);
        array_unshift($links, $settings_link);
        return $links;
    }
    
    public function admin_footer_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#invbit-check-updates').on('click', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var originalText = $link.text();
                
                $link.text('Checking...').css('opacity', '0.7');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'invbit_check_updates',
                        nonce: '<?php echo wp_create_nonce('invbit_check_updates_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            if (response.data.version !== '<?php echo $this->version; ?>') {
                                location.reload();
                            }
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error checking for updates. Please try again.');
                    },
                    complete: function() {
                        $link.text(originalText).css('opacity', '1');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->get_repository_info();

        if (isset($this->github_response['tag_name']) && version_compare($this->github_response['tag_name'], $this->version, '>')) {
            $plugin = array(
                'slug' => $this->slug,
                'plugin' => $this->basename,
                'new_version' => $this->github_response['tag_name'],
                'url' => $this->plugin_data['PluginURI'],
                'package' => $this->github_response['zipball_url'],
            );

            if (!empty($this->authorize_token)) {
                $plugin['package'] = add_query_arg(array('access_token' => $this->authorize_token), $plugin['package']);
            }

            $transient->response[$this->basename] = (object) $plugin;
        }

        return $transient;
    }

    private function get_repository_info() {
        $url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            ),
        ));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (is_array($response_body)) {
            $this->github_response = $response_body;
        }
    }

    public function plugin_popup($result, $action, $args) {
        if ('plugin_information' !== $action || $args->slug !== $this->slug) {
            return $result;
        }

        $this->get_repository_info();

        if (!empty($this->github_response)) {
            $plugin_data = array(
                'name' => $this->plugin_name,
                'slug' => $this->slug,
                'version' => $this->github_response['tag_name'],
                'author' => $this->plugin_data['AuthorName'] ?? 'Invbit',
                'author_profile' => $this->plugin_data['AuthorURI'] ?? 'https://invbit.com',
                'last_updated' => $this->github_response['published_at'],
                'homepage' => $this->plugin_data['PluginURI'] ?? '',
                'short_description' => $this->plugin_data['Description'] ?? '',
                'sections' => array(
                    'description' => $this->github_response['body'] ?? '',
                    'changelog' => $this->get_changelog(),
                ),
                'download_link' => $this->github_response['zipball_url']
            );

            return (object) $plugin_data;
        }

        return $result;
    }

    private function get_changelog() {
        $changelog = 'No changelog available.';
        
        $readme_file = WP_PLUGIN_DIR . '/' . dirname($this->basename) . '/readme.txt';
        
        if (file_exists($readme_file)) {
            $readme = file_get_contents($readme_file);
            
            if (preg_match('/==\s*Changelog\s*==(.*)$/si', $readme, $matches)) {
                $changelog = $matches[1];
                
                $changelog = preg_replace('/`(.*?)`/', '<code>\\1</code>', $changelog);
                $changelog = preg_replace('/\*(.*?)\*/', '<em>\\1</em>', $changelog);
                $changelog = preg_replace('/=\s(.*?)\s=/', '<h4>\\1</h4>', $changelog);
                $changelog = preg_replace('/\n\s*\n/', "\n<p>", $changelog);
            }
        }
        
        return $changelog;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        if ($this->basename !== $hook_extra['plugin']) {
            return $response;
        }

        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->basename);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }
} 