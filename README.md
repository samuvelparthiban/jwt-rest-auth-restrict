=== JWT REST Auth Restrict ===
Contributors: SamuvelParthiban
Tags: jwt, rest-api, authentication, security, woocommerce
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Force JWT authentication for all WordPress REST API requests with configurable allowed routes.

== Description ==

JWT REST Auth Restrict is a WordPress plugin that enhances REST API security by requiring authentication for all endpoints. 
You can whitelist specific routes (like JWT login or WooCommerce product endpoints) from the admin settings page.

Features:
* Forces JWT or login authentication for all REST API requests
* Configurable allowed routes via WordPress admin
* Compatible with WooCommerce REST API
* Easy to extend and customize

== Installation ==

1. Upload the `jwt-rest-auth-restrict` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings → JWT REST Auth** to configure allowed routes.
4. Add comma-separated REST API routes that should remain public (like JWT login endpoints).

== Frequently Asked Questions ==

= Why do some REST API endpoints still work without a token? =

By default, WordPress REST API endpoints (like pages or posts) are public. This plugin blocks access for **unauthenticated users** except for the routes you explicitly allow in settings.

= Can I whitelist WooCommerce endpoints? =

Yes! Just add them as comma-separated routes in the plugin settings, for example:

```
/wp-json/wc/v3/products, /wp-json/wc/v3/products/categories
```

= Is this compatible with JWT Authentication plugin? =

Yes, this plugin works alongside the JWT Authentication plugin to enforce authentication.

== Changelog ==

= 1.0 =
* Initial release
* Added REST API authentication filter
* Added admin settings page for allowed routes
* Compatible with JWT Authentication plugin

== Upgrade Notice ==

1.0 - Initial release. No upgrades yet.

== License ==

This plugin is licensed under the GPLv2 or later.
