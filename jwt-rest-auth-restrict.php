<?php
/**
 * Plugin Name: JWT REST Auth Restrict
 * Description: Force JWT authentication for all REST API requests with configurable allowed routes and custom error message.
 * Version: 1.2
 * Author: Samuvel Parthiban
 */

if (!defined('ABSPATH')) {
    exit;
}

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

/**
 * ✅ Check if JWT plugin is active
 */
function jwt_rest_auth_dependency_active() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $jwt_plugin_found = false;
    $all_plugins = get_plugins();

    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (strpos($plugin_file, 'jwt-authentication-for-wp-rest-api') !== false) {
            $jwt_plugin_found = $plugin_file;
            break;
        }
    }

    return $jwt_plugin_found && is_plugin_active($jwt_plugin_found);
}

/**
 * ✅ Main initializer (runs after all plugins are loaded)
 */
add_action('plugins_loaded', function() {

    if (!jwt_rest_auth_dependency_active()) {

        // Prevent misleading “Plugin activated.” message
        unset($_GET['activate']);

        // Admin notice for missing dependency
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>JWT REST Auth Restrict</strong> requires the <strong>JWT Authentication for WP REST API</strong> plugin to be installed and active. The plugin has been deactivated.';
            echo '</p></div>';
        });

        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));
        return;
    }

    // ✅ Include admin page only when JWT plugin active
    require_once plugin_dir_path(__FILE__) . 'admin/admin-settings.php';

    /**
     * ✅ REST Authentication Restriction Logic
     */
    add_filter('rest_authentication_errors', function($result) {
        if (!empty($result)) {
            return $result;
        }

        // Allow admin-ajax.php
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return $result;
        }

        // Current REST request URI
        $current_route = $_SERVER['REQUEST_URI'];

        // Detect subfolder installs
        $site_url = parse_url(site_url(), PHP_URL_PATH);
        $current_route_no_site = $current_route;

        if ($site_url && $site_url !== '/') {
            $current_route_no_site = preg_replace('#^' . preg_quote($site_url, '#') . '#', '', $current_route);
            if ($current_route_no_site === '') {
                $current_route_no_site = '/';
            }
        }

        // Allowed routes
        $allowed_routes = get_option('jwt_allowed_routes', [
            '/wp-json/jwt-auth/v1/token',
            '/wp-json/jwt-auth/v1/token/validate'
        ]);

        foreach ($allowed_routes as $route) {
            if (strpos($current_route_no_site, $route) === 0) {
                return $result;
            }
        }

        // Custom error message
        $error_message = get_option('jwt_rest_error_message', 'You must be logged in to access this REST API.');

        // Restrict unauthenticated users
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                $error_message,
                ['status' => 401]
            );
        }

        return $result;
    });
});
