<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('GhPluginUpdater')) {
    class GhPluginUpdater
    {
        private $file;
        private $plugin_data;
        private $basename;
        private $active = false;
        private $github_response;
        private $github_token;
        private $github_username;
        private $github_repo;

        public function __construct($file)
        {
            $this->file = $file;
            $this->basename = plugin_basename($file);
            $this->github_token = get_option('invbit_github_token', '');
            $this->github_username = get_option('invbit_github_username', 'miranda90');
            $this->github_repo = get_option('invbit_github_repo', 'invbit-credits');
        }

        public function init(): void
        {
            add_filter('pre_set_site_transient_update_plugins', [$this, 'modify_transient'], 10, 1);
            add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
            add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
            add_filter('http_request_args', [$this, 'set_header_token'], 10, 2);

            // Force check for updates
            delete_site_transient('update_plugins');
            wp_update_plugins();
        }

        private function get_repository_info(): void
        {
            if (!empty($this->github_response)) {
                return;
            }

            $request_uri = sprintf(
                'https://api.github.com/repos/%s/%s/releases/latest',
                $this->github_username,
                $this->github_repo
            );

            $args = [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version')
                ]
            ];

            if (!empty($this->github_token)) {
                $args['headers']['Authorization'] = 'token ' . $this->github_token;
            }
            
            $response = wp_remote_get($request_uri, $args);
            
            if (is_wp_error($response)) {
                error_log('GitHub API Error: ' . $response->get_error_message());
                return;
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                error_log('GitHub API Error: Response code ' . $code);
                return;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data) || !isset($data['tag_name'])) {
                error_log('GitHub API Error: Invalid response data');
                return;
            }

            $this->github_response = $data;
        }

        public function modify_transient($transient)
        {
            if (empty($transient->checked)) {
                return $transient;
            }

            $this->get_repository_info();
            if (empty($this->github_response)) {
                return $transient;
            }

            $this->get_plugin_data();
            
            $current_version = $this->plugin_data['Version'];
            $latest_version = ltrim($this->github_response['tag_name'], 'v');

            if (version_compare($latest_version, $current_version, '>')) {
                $plugin = [
                    'slug' => dirname($this->basename),
                    'plugin' => $this->basename,
                    'new_version' => $latest_version,
                    'url' => $this->github_response['html_url'],
                    'package' => $this->github_response['zipball_url'],
                    'icons' => [],
                    'banners' => [],
                    'banners_rtl' => [],
                    'tested' => '',
                    'requires_php' => '',
                    'compatibility' => new stdClass(),
                ];

                if (!empty($this->github_token)) {
                    $plugin['package'] = add_query_arg('access_token', $this->github_token, $plugin['package']);
                }

                $transient->response[$this->basename] = (object) $plugin;
            }

            return $transient;
        }

        private function get_plugin_data()
        {
            if (empty($this->plugin_data)) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                $this->plugin_data = get_plugin_data($this->file);
            }
            return $this->plugin_data;
        }

        public function plugin_popup($result, $action, $args)
        {
            if ($action !== 'plugin_information') {
                return $result;
            }

            if (!isset($args->slug) || $args->slug !== dirname($this->basename)) {
                return $result;
            }

            $this->get_repository_info();
            $this->get_plugin_data();

            return (object) [
                'name' => $this->plugin_data['Name'],
                'slug' => dirname($this->basename),
                'version' => $this->github_response['tag_name'],
                'author' => $this->plugin_data['AuthorName'],
                'author_profile' => $this->plugin_data['AuthorURI'],
                'last_updated' => $this->github_response['published_at'],
                'homepage' => $this->plugin_data['PluginURI'],
                'short_description' => $this->plugin_data['Description'],
                'sections' => [
                    'Description' => $this->plugin_data['Description'],
                    'Updates' => $this->github_response['body']
                ],
                'download_link' => $this->github_response['zipball_url']
            ];
        }

        public function after_install($response, $hook_extra, $result)
        {
            global $wp_filesystem;

            $plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->basename);
            $wp_filesystem->move($result['destination'], $plugin_folder);
            $result['destination'] = $plugin_folder;

            if (is_plugin_active($this->basename)) {
                activate_plugin($this->basename);
            }

            return $result;
        }

        public function set_header_token($args, $url)
        {
            if (strpos($url, 'api.github.com') !== false) {
                if (!empty($this->github_token)) {
                    $args['headers']['Authorization'] = 'token ' . $this->github_token;
                }
            }
            return $args;
        }
    }
} 