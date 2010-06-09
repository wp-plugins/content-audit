=== Content Audit ===
Contributors: sillybean
Tags: content, audit, review, inventory
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.1

lets you create a content inventory right in the WordPress Edit screens. You can mark content as redundant, outdated, trivial, or in need of a review for SEO or style. The plugin creates a custom taxonomy (like a new set of categories) that's visible only from the admin screens. Since the content status labels work just like categories, you can remove the built-in ones and add your own if you like. You can also assign a content owner (distinct from the original author) and keep notes. The IDs are revealed on the Edit screens so you can keep track of your content even if you change titles and permalinks. The plugin supports the new custom content types in 3.0.

The plugin also creates three new filters on the Edit screens: author, content owner, and content status. This should make it easy to narrow your focus to just a few pages at a time.

You can display the audit details to logged-in editors on the front end if you want, either above or below the content. You can style the audit message.

If you want to see sparklines from Google Analytics, also install the <a href="http://www.ioncannon.net/projects/google-analytics-dashboard-wordpress-widget/">Google Analytics Dashboard plugin</a>. This will give you some idea of how popular an article is, which might influence your decisions.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit the Settings screen to set your status labels (redundant, outdated, trivial) and choose which content types (posts, pages, and/or custom) should be audited.

== Screenshots ==

1. The options screen
1. Edit pages, with the content audit columns and filter dropdowns
1. Edit a page, with the content audit notes, owner, and status boxes

== Changelog ==

= 1.1 =
* Now supports media files.

= 1.01 = 
* Fixed a typo that prevented you from leaving the content owner field blank when editing something
* Moved the Google Analytics Dashboard plugin's sparklines column to the last row of the Edit screen tables, if that plugin is installed

= 1.0 =
* Out of beta!

= 0.9b =
* Changed the way the content status taxonomy is created so that you can actually edit and delete the built-in categories.

= 0.8b =
* First beta.
