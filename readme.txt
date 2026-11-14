=== Plugin boilerplate for WordPress ===
Contributors: mkrndmane
Tags: boilerplate, starter, settings, block, shortcode
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A starter plugin with a settings page, a custom post type, a shortcode, a block, AJAX and a scheduled email.

== Description ==

Replace this text with a description of your plugin.

The boilerplate ships working examples you can keep, change or delete:

* **Settings page** (Settings → Plugin boilerplate for WordPress) built from one array of fields.
* **Items** custom post type with a category taxonomy and an image column.
* **[myplugin_items]** shortcode with a "Load more" button that reads a REST API route.
* **"Send a test report"** button on the settings page (admin-ajax).
* **Latest items** block, rendered in PHP.
* **Items with live filter** block: a search box that filters the list as you type (Interactivity API).
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

== Screenshots ==

1. The demo page: the shortcode with a Load more button that reads a REST API route.
2. The settings page, built from one array of fields.
3. The Latest items block and its settings in the block editor.
4. The Items with live filter block, filtering as you type.

== Changelog ==

= 2.0.6 =

* Rename script writes the wrong main file name into PHPStan and test config (#59)
* Require CI to pass before a PR can merge (#60)
* Fail the release when GitHub label or issue lookups fail (#61)
* Uninstall leaves trashed items, auto-drafts and item categories behind (#62)
* Multisite: deactivate on every site and set up new sites (#63)
* Item and category URLs use generic slugs that collide between plugins (#64)
* Load more fails on cached pages because the public REST call sends a nonce (#65)
* Shortcode and blocks accept more items per page than the REST route (#66)
* Test on PHP 8.5 (#67)
* README: say that rulesets on private repos need a paid GitHub plan (#68)

= 2.0.5 =

* README screenshots of the settings page and blocks (#52)
* Interactive block example (Interactivity API) (#53)
* repo-standard skill: describe the new release rule (#54)
* repo-standard skill: PHPStan and Plugin Check in the tooling group (#55)
* repo-standard skill: community files (#56)
* repo-standard skill: Playground demo and Tested up to (#57)

= 2.0.4 =

* Static analysis with PHPStan (#49)
* REST API endpoint example (#50)

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
