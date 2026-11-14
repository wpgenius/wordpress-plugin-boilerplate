=== Plugin boilerplate for WordPress ===
Contributors: mkrndmane
Tags: boilerplate, starter, settings, block, shortcode
Requires at least: 6.1
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A starter plugin with a settings page, a custom post type, a shortcode, a block, AJAX and a scheduled email.

== Description ==

Replace this text with a description of your plugin.

The boilerplate ships working examples you can keep, change or delete:

* **Settings page** (Settings → Plugin boilerplate) built from one array of fields.
* **Items** custom post type with a category taxonomy and an image column.
* **[myplugin_items]** shortcode with an AJAX "Load more" button.
* **Latest items** block, rendered in PHP.
* **Daily report** email sent by WP-Cron, with {placeholders} in the subject and message.
* Templates your theme can override, and a "View details" popup for private plugins.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from Plugins → Add New → Upload.
2. Activate the plugin on the Plugins screen.
3. Go to Settings → Plugin boilerplate.

== Frequently Asked Questions ==

= How do I change a template? =

Copy a file from the plugin's `templates/` folder to `wp-content/themes/<your-theme>/wordpress-plugin-boilerplate/` and edit the copy.

= Is my data deleted when I delete the plugin? =

Only if "Delete data on uninstall" is ticked in the settings.

== Changelog ==

= 2.0.0 =

* Fix repo hygiene and plugin bootstrap issues (#2)
* Add Composer with dev tooling and class autoloading (#5)
* Add WordPress Coding Standards config and EditorConfig (#6)
* Load translations from languages/ and ship a .pot template (#7)
* Add JS/CSS linting with @wordpress/scripts (#8)
* Add CI checks for pull requests (#9)
* Load plugin classes without requiring Composer (#11)
* Refresh .gitignore and .distignore for WordPress plugins (#12)
* Automatic versioned releases with changelog from issues (#13)
* Deploy workflow with DEPLOY_ENABLED switch (FTPS, SSH, WordPress.org) (#14)
* Keep GitHub Actions up to date with Dependabot (#15)
* Require pull requests to link an issue (#16)
* GitHub setup script for repos created from this template (#17)
* README: getting started and repo workflow (#18)
