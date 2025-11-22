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
    require_once plugin_dir_path(__FILE__) . 'wp-admin/admin-settings.php';

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

        // Check if user is authenticated via WordPress or JWT
        $headers = getallheaders();

        // 🔹 Safely get Authorization from all possible sources
        $auth = $headers['Authorization'] ??
                 $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ??
                 $_SERVER['HTTP_AUTHORIZATION'] ??
                 '';

        // 🔹 If not logged in, check JWT token manually
        if (!is_user_logged_in()) {
            $token = '';

            // ✅ Extract token from "Authorization: Bearer <token>"
            if (!empty($auth) && strpos($auth, 'Bearer ') === 0) {
                $token = trim(str_replace('Bearer ', '', $auth));
            }
           
            // ✅ Fallback: Allow ?token=xyz in query
            if (empty($token) && isset($_GET['token'])) {
                $token = sanitize_text_field($_GET['token']);
            }

            // ✅ Validate JWT manually
            if (!empty($token)) {
                $url = site_url('/wp-json/jwt-auth/v1/token/validate');

				$response = wp_remote_post($url, [
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
					],
				]);

				 if (is_wp_error($response)) {
                    return new WP_Error(
                        'jwt_validate_failed',
                        'Token validation request failed: ' . $response->get_error_message(),
                        ['status' => 500]
                    );
                }

				$body = wp_remote_retrieve_body($response);
				$data = json_decode($body);

				if (!empty($data) && isset($data->code) && $data->code === 'jwt_auth_valid_token') {
                    return $result; // ✅ Valid token
                }
				// ⚠️ Return proper status for invalid/expired token
                $message = $data->message ?? 'Invalid or expired token';
                $code = $data->code ?? 'jwt_auth_invalid_token';

                return new WP_Error($code, $message, ['status' => 403]);
            }

            // ❌ Neither logged in nor valid JWT
            return new WP_Error(
                'rest_forbidden',
                $error_message,
                ['status' => 401]
            );
        }

        return $result;
    });
});
