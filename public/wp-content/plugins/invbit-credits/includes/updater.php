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
            // Volvemos a usar la URL zipball directa de la API
            $download_url = $this->github_response['zipball_url'];
            
            error_log('URL de descarga del plugin: ' . $download_url);
            
            $plugin = array(
                'slug' => $this->slug,
                'plugin' => $this->basename,
                'new_version' => $this->github_response['tag_name'],
                'url' => $this->plugin_data['PluginURI'] ?? '',
                'package' => $download_url,
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

        error_log('Invbit Credits - Iniciando actualización');
        
        $plugin_folder = WP_PLUGIN_DIR . '/invbit-credits';
        $source_files = $wp_filesystem->dirlist($result['destination']);
        $github_folder = false;
        
        foreach ($source_files as $file => $info) {
            if ($info['type'] === 'd') {
                $test_path = $result['destination'] . '/' . $file;
                
                if ($this->is_plugin_directory($test_path, $wp_filesystem)) {
                    $github_folder = $test_path;
                    break;
                }
                
                $nested_path = $test_path . '/public/wp-content/plugins/invbit-credits';
                if ($wp_filesystem->exists($nested_path) && $this->is_plugin_directory($nested_path, $wp_filesystem)) {
                    $github_folder = $nested_path;
                    break;
                }
            }
        }
        
        if ($github_folder === false) {
            $github_folder = $this->find_plugin_directory($result['destination'], $wp_filesystem);
        }
        
        if ($github_folder) {
            if (!$wp_filesystem->exists($plugin_folder)) {
                $wp_filesystem->mkdir($plugin_folder);
            }
            
            $this->copy_directory($github_folder, $plugin_folder, $wp_filesystem);
            
            if ($wp_filesystem->exists($result['destination'])) {
                $wp_filesystem->delete($result['destination'], true);
            }
            
            $result['destination'] = $plugin_folder;
            $this->basename = 'invbit-credits/plugin-main.php';
            
            if ($this->active) {
                activate_plugin($this->basename);
            }
            
            return $result;
        }
        
        return new WP_Error('plugin_not_found', 'No se pudo encontrar la carpeta del plugin en el archivo descargado.');
    }
    
    private function find_plugin_directory($directory, $wp_filesystem) {
        if ($this->is_plugin_directory($directory, $wp_filesystem)) {
            return $directory;
        }
        
        $files = $wp_filesystem->dirlist($directory);
        
        if (is_array($files)) {
            foreach ($files as $file => $info) {
                if ($info['type'] === 'd') {
                    $full_path = $directory . '/' . $file;
                    
                    if ($this->is_plugin_directory($full_path, $wp_filesystem)) {
                        return $full_path;
                    }
                    
                    if (strpos($full_path, 'public/wp-content/plugins/' . $this->slug) !== false) {
                        return $full_path;
                    }
                    
                    $found = $this->find_plugin_directory($full_path, $wp_filesystem);
                    if ($found) {
                        return $found;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function is_plugin_directory($directory, $wp_filesystem) {
        $plugin_files = array(
            'plugin-main.php',
            'includes/shortcode.php',
            'includes/page-creator.php'
        );
        
        foreach ($plugin_files as $file) {
            if (!$wp_filesystem->exists($directory . '/' . $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function copy_directory($source, $destination, $wp_filesystem) {
        if (!$wp_filesystem->exists($destination)) {
            $wp_filesystem->mkdir($destination);
        }
        
        $files = $wp_filesystem->dirlist($source);
        
        foreach ($files as $file => $info) {
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;
            
            if ($info['type'] === 'd') {
                $this->copy_directory($source_path, $dest_path, $wp_filesystem);
            } else {
                $wp_filesystem->copy($source_path, $dest_path, true);
            }
        }
        
        return true;
    }
} 