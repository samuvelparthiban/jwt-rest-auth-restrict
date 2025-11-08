<?php
if (!defined('ABSPATH')) exit;

// Add menu page
add_action('admin_menu', function() {
    add_options_page(
        'JWT REST Auth Restrict',
        'JWT REST Auth',
        'manage_options',
        'jwt-rest-auth-restrict',
        'jwt_rest_auth_settings_page'
    );
});

// Register settings
add_action('admin_init', function() {
    register_setting('jwt_rest_auth_settings_group', 'jwt_allowed_routes');
    register_setting('jwt_rest_auth_settings_group', 'jwt_rest_error_message');
});

// Admin page HTML
function jwt_rest_auth_settings_page() {
    ?>
    <div class="wrap">
        <h1>JWT REST Auth Restrict</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('jwt_rest_auth_settings_group');
            do_settings_sections('jwt_rest_auth_settings_group');

            // Allowed routes
            $routes = get_option('jwt_allowed_routes', [
                '/wp-json/jwt-auth/v1/token',
                '/wp-json/jwt-auth/v1/token/validate'
            ]);
            $routes_text = implode(',', $routes);

            // Custom error message
            $error_message = get_option('jwt_rest_error_message', 'You must be logged in to access this REST API.');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Allowed REST API Routes (comma separated)</th>
                    <td>
                        <input type="text" name="jwt_allowed_routes" value="<?php echo esc_attr($routes_text); ?>" size="80">
                        <p class="description">Add the routes you want to allow without authentication, separated by commas.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">REST API Forbidden Message</th>
                    <td>
                        <input type="text" name="jwt_rest_error_message" value="<?php echo esc_attr($error_message); ?>" size="80">
                        <p class="description">Message shown when an unauthenticated user tries to access a restricted REST API endpoint.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Save comma-separated routes as array
add_filter('pre_update_option_jwt_allowed_routes', function($value, $old_value) {
    $arr = array_map('trim', explode(',', $value));
    return $arr;
}, 10, 2);
