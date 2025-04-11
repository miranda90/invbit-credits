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
        error_log('Invbit Updater - Consultando API de GitHub: ' . $url);
        
        $headers = array(
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        );
        
        // Añadir token de autenticación si existe
        if (!empty($this->authorize_token)) {
            $headers['Authorization'] = 'token ' . $this->authorize_token;
            error_log('Invbit Updater - Usando token de autenticación');
        } else {
            error_log('Invbit Updater - Sin token de autenticación (limitado a 60 peticiones/hora)');
        }
        
        error_log('Invbit Updater - Headers: ' . print_r($headers, true));
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            error_log('Invbit Updater - Error en la solicitud a GitHub: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== $response_code) {
            error_log('Invbit Updater - Error en la respuesta de GitHub (Code ' . $response_code . ')');
            error_log('Invbit Updater - Respuesta: ' . wp_remote_retrieve_body($response));
            
            // Información adicional para errores comunes
            if ($response_code === 403) {
                $headers = wp_remote_retrieve_headers($response);
                $rate_limit = isset($headers['x-ratelimit-limit']) ? $headers['x-ratelimit-limit'] : 'desconocido';
                $rate_remaining = isset($headers['x-ratelimit-remaining']) ? $headers['x-ratelimit-remaining'] : 'desconocido';
                $rate_reset = isset($headers['x-ratelimit-reset']) ? $headers['x-ratelimit-reset'] : 'desconocido';
                
                error_log("Invbit Updater - Información de Rate Limiting:");
                error_log("  Límite: " . $rate_limit);
                error_log("  Restantes: " . $rate_remaining);
                error_log("  Reset: " . ($rate_reset ? date('Y-m-d H:i:s', $rate_reset) : 'desconocido'));
                
                if (isset($headers['x-ratelimit-remaining']) && intval($headers['x-ratelimit-remaining']) === 0) {
                    error_log("Invbit Updater - Has alcanzado el límite de peticiones a GitHub API");
                    // Guardar en transient para evitar nuevas peticiones por un tiempo
                    set_transient('invbit_github_ratelimit_reached', time(), 3600); // 1 hora
                } else {
                    error_log("Invbit Updater - Acceso prohibido a GitHub API. Verifica tu token de acceso.");
                }
            }
            return;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (is_array($response_body)) {
            $this->github_response = $response_body;
            error_log('Invbit Updater - Respuesta de GitHub procesada correctamente. Tag: ' . 
                      ($response_body['tag_name'] ?? 'no disponible'));
        } else {
            error_log('Invbit Updater - Respuesta de GitHub no es un array válido');
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

        error_log('Invbit Credits - Iniciando proceso de actualización (Modo Fusión)');
        error_log('Plugin basename: ' . $this->basename);
        error_log('Plugin slug: ' . $this->slug);
        $temp_destination = $result['destination']; // Carpeta temporal creada por WP
        error_log('Destino de descarga temporal: ' . $temp_destination);

        // Forzar el nombre de la carpeta del plugin destino
        $plugin_folder = WP_PLUGIN_DIR . '/invbit-credits';
        error_log('Carpeta destino final del plugin: ' . $plugin_folder);

        // Encontrar la carpeta principal REAL dentro del archivo descargado
        $source_files = $wp_filesystem->dirlist($temp_destination);
        error_log('Archivos en directorio de descarga temporal: ' . print_r($source_files, true));
        
        // Primero verificar si el directorio temporal ya tiene los archivos del plugin directamente
        if ($this->is_plugin_directory($temp_destination, $wp_filesystem)) {
            error_log('El directorio de descarga temporal contiene directamente los archivos del plugin');
            $github_folder = $temp_destination;
        } else {
            // Si no, buscar una subcarpeta que contenga el plugin
            $github_folder = '';
            if (is_array($source_files) && count($source_files) === 1) {
                $folder_name = key($source_files);
                $potential_folder = $temp_destination . '/' . $folder_name;
                if ($wp_filesystem->is_dir($potential_folder)) {
                    $github_folder = $potential_folder;
                    error_log('Encontrada carpeta fuente única: ' . $github_folder);
                }
            } else {
                // Fallback: Buscar una carpeta que probablemente contenga el plugin
                if(is_array($source_files)) {
                     foreach ($source_files as $file => $info) {
                        if ($info['type'] === 'd') {
                            $potential_folder = $temp_destination . '/' . $file;
                            // Comprobar si esta carpeta contiene el plugin
                            if ($this->is_plugin_directory($potential_folder, $wp_filesystem)) {
                                 $github_folder = $potential_folder;
                                 error_log('Encontrada carpeta fuente (fallback): ' . $github_folder);
                                 break;
                            }
                        }
                    }
                }
            }
        }

        if (empty($github_folder)) {
            error_log('No se pudo identificar la carpeta fuente principal en el directorio de descarga temporal.');
            // Intentar limpiar el directorio temporal aunque fallemos
            if ($wp_filesystem->exists($temp_destination)) {
                $wp_filesystem->delete($temp_destination, true);
            }
            return new WP_Error('source_folder_not_found', 'No se pudo identificar la carpeta del plugin en el archivo descargado.');
        }

        // Verificar si la carpeta fuente tiene archivos de plugin válidos
        if (!$this->is_plugin_directory($github_folder, $wp_filesystem)) {
            error_log('La carpeta fuente identificada (' . $github_folder . ') no contiene archivos de plugin válidos.');
             // Intentar limpiar el directorio temporal aunque fallemos
            if ($wp_filesystem->exists($temp_destination)) {
                $wp_filesystem->delete($temp_destination, true);
            }
            return new WP_Error('plugin_not_found', 'La carpeta fuente descargada no parece ser un plugin válido.');
        }
        error_log('La carpeta fuente (' . $github_folder . ') contiene archivos de plugin válidos.');

        // Asegurarse de que la carpeta destino exista (si no, es una instalación inicial)
        if (!$wp_filesystem->is_dir($plugin_folder)) {
            error_log('La carpeta destino (' . $plugin_folder . ') no existe, creando...');
            if (!$wp_filesystem->mkdir($plugin_folder)) {
                error_log('Error creando carpeta destino: ' . $plugin_folder);
                 // Intentar limpiar el directorio temporal aunque fallemos
                if ($wp_filesystem->exists($temp_destination)) {
                    $wp_filesystem->delete($temp_destination, true);
                }
                return new WP_Error('mkdir_failed', 'No se pudo crear la carpeta destino del plugin.');
            }
        }

        // -- NO LIMPIAMOS LA CARPETA DESTINO --
        error_log('Iniciando copia/fusión desde ' . $github_folder . ' hacia ' . $plugin_folder);
        $copied = $this->copy_directory($github_folder, $plugin_folder, $wp_filesystem);

        if (!$copied) {
            error_log('Fallo al copiar/fusionar el contenido del directorio.');
            // No devolvemos error necesariamente, puede que el plugin quede en estado inconsistente
            // pero al menos no borramos el temporal todavía para posible inspección.
            // Considera devolver un error si la copia es crítica.
        } else {
            error_log('Copia/Fusión de contenido completada.');
        }

        // Limpiar directorio temporal de descarga SIEMPRE que la copia haya ido bien o mal (para no dejar basura)
        if ($wp_filesystem->exists($temp_destination)) {
            error_log('Limpiando directorio temporal de descarga: ' . $temp_destination);
            $wp_filesystem->delete($temp_destination, true);
        }

        // Actualizar el resultado para WordPress (incluso si la copia falló parcialmente)
        $result['destination'] = $plugin_folder;
        $result['destination_name'] = dirname($this->basename); // Nombre correcto: invbit-credits
        error_log('Actualizando resultado: destination=' . $result['destination'] . ', destination_name=' . $result['destination_name']);

        // Reactivar si estaba activo
        if ($this->active) {
            error_log('Reactivando plugin: ' . $this->basename);
            if ($wp_filesystem->exists($plugin_folder . '/' . basename($this->file))) {
                activate_plugin($this->basename);
            } else {
                error_log('ERROR: El archivo principal del plugin no se encontró después de la copia/fusión: ' . $plugin_folder . '/' . basename($this->file));
            }
        }

        error_log('Proceso de actualización (Modo Fusión) completado.');
        return $result; // Devolver $result siempre para que WP continúe
    }
    
    /**
     * Busca recursivamente el directorio del plugin dentro de la estructura descargada
     */
    private function find_plugin_directory($directory, $wp_filesystem) {
        error_log('Buscando en directorio: ' . $directory);
        
        // Verificar si el directorio actual es la carpeta del plugin
        if ($this->is_plugin_directory($directory, $wp_filesystem)) {
            return $directory;
        }
        
        // Obtener todos los subdirectorios
        $files = $wp_filesystem->dirlist($directory);
        
        if (is_array($files)) {
            foreach ($files as $file => $info) {
                // Solo procesar directorios
                if ($info['type'] === 'd') {
                    $full_path = $directory . '/' . $file;
                    
                    // Verificar primero si este directorio es el plugin
                    if ($this->is_plugin_directory($full_path, $wp_filesystem)) {
                        return $full_path;
                    }
                    
                    // Si la ruta contiene 'public/wp-content/plugins/invbit-credits', esta podría ser nuestra carpeta
                    if (strpos($full_path, 'public/wp-content/plugins/' . $this->slug) !== false) {
                        return $full_path;
                    }
                    
                    // Buscar recursivamente en subdirectorios
                    $found = $this->find_plugin_directory($full_path, $wp_filesystem);
                    if ($found) {
                        return $found;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Verifica si un directorio es la carpeta del plugin
     */
    private function is_plugin_directory($directory, $wp_filesystem) {
        // Verificar la existencia de archivos clave que indican que es la carpeta del plugin
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
    
    /**
     * Copia un directorio completo con su contenido (alternativa a move)
     */
    private function copy_directory($source, $destination, $wp_filesystem) {
        error_log('Iniciando copia de directorio de ' . $source . ' a ' . $destination);
        $result = true; // Assume success initially

        // Crear el directorio destino si no existe
        if (!$wp_filesystem->exists($destination)) {
            if (!$wp_filesystem->mkdir($destination)) {
                error_log('Error creando directorio destino: ' . $destination);
                return false; // Failed to create destination
            }
        }

        // Obtener todos los archivos y carpetas
        $files = $wp_filesystem->dirlist($source);
        if ($files === false) {
            error_log('Error listando directorio fuente: ' . $source);
            return false; // Failed to list source
        }
        if (empty($files)) {
            error_log('Directorio fuente está vacío: ' . $source);
            return true; // Empty source is not an error
        }

        foreach ($files as $file => $info) {
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;

            if ($info['type'] === 'd') {
                // Es un directorio, copiar recursivamente
                if (!$this->copy_directory($source_path, $dest_path, $wp_filesystem)) {
                    $result = false; // Propagate failure
                    break; // Stop copying if a sub-directory fails
                }
            } else {
                // Es un archivo, copiarlo
                error_log('Copiando archivo: ' . $source_path . ' a ' . $dest_path);
                if (!$wp_filesystem->copy($source_path, $dest_path, true)) { // Overwrite = true
                    error_log('Error copiando archivo: ' . $source_path . ' a ' . $dest_path);
                    $result = false; // Copy failed
                    break; // Stop copying if a file fails
                }
            }
        }

        error_log('Finalizando copia de directorio (' . ($result ? 'éxito' : 'fallo') . '): ' . $source . ' a ' . $destination);
        return $result;
    }
} 