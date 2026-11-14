=== Plugin boilerplate for WordPress ===
Contributors: mkrndmane
Tags: boilerplate, starter, settings, block, shortcode
Requires at least: 6.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A starter plugin with a settings page, a custom post type, a shortcode, a block, AJAX and a scheduled email.

== Description ==

Replace this text with a description of your plugin.

The boilerplate ships working examples you can keep, change or delete:

* **Settings page** (Settings → Plugin boilerplate for WordPress) built from one array of fields.
* **Items** custom post type with a category taxonomy and an image column.
* **[myplugin_items]** shortcode with an AJAX "Load more" button.
* **Latest items** block, rendered in PHP.
* **Daily report** email sent by WP-Cron, with {placeholders} in the subject and message.
* Templates your theme can override, and a "View details" popup for private plugins.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from Plugins → Add New → Upload.
2. Activate the plugin on the Plugins screen.
3. Go to Settings → Plugin boilerplate for WordPress.

== Frequently Asked Questions ==

= How do I change a template? =

Copy a file from the plugin's `templates/` folder to `wp-content/themes/<your-theme>/wordpress-plugin-boilerplate/` and edit the copy.

= Is my data deleted when I delete the plugin? =

Only if "Delete data on uninstall" is ticked in the settings.

== Changelog ==

= 2.0.3 =

* Release only when the plugin zip actually changes (#40)
* README and repo description for a public boilerplate (#41)
* Run Plugin Check in CI (#42)
* Community files: contributing, security, issue and PR templates (#43)
* Easy rename script for new plugins (#44)
* Bump Tested up to (#45)

= 2.0.2 =

* Publish /repo-standard as a Claude Code plugin from this repo (#36)
* README: bring an existing plugin or theme up to the standard (#37)
* README: new plugins should start at version 1.0.0 (#38)

= 2.0.1 =

* Namespaced plugin structure with autoloader (#20)
* Common Api base class with shared helpers (#21)
* Settings page built from one array of fields (#22)
* Admin area: menu, assets and View details from readme.txt (#23)
* Front end: assets, example shortcode and overridable templates (#24)
* Example AJAX handler (#25)
* Example cron job (#26)
* Example custom post type (#27)
* Installer and uninstall cleanup (#28)
* Example dynamic Gutenberg block (#29)
* Unit tests with PHPUnit and Brain Monkey (#30)
* License the boilerplate as GPL-3.0-or-later (#31)
* Update README for the new structure (#32)

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
