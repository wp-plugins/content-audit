=== Content Audit ===
Contributors: sillybean
Tags: content, audit, review, inventory
Donate Link: http://sillybean.net/code/wordpress/content-audit/
Requires at least: 3.1
Tested up to: 3.5
Stable tag: 1.4.2

Lets you create a content inventory right in the WordPress Edit screens. You can mark content as redundant, outdated, trivial, or in need of a review for SEO or style. The plugin creates a custom taxonomy (like a new set of categories) that's visible only from the admin screens. Since the content status labels work just like categories, you can remove the built-in ones and add your own if you like. You can also assign a content owner (distinct from the original author) and keep notes. The IDs are revealed on the Edit screens so you can keep track of your content even if you change titles and permalinks. The plugin supports the new custom content types in 3.0.

The plugin also creates three new filters on the Edit screens: author, content owner, and content status. This should make it easy to narrow your focus to just a few pages at a time.

You can display the audit details to logged-in editors on the front end if you want, either above or below the content. You can style the audit message.

If you want to see sparklines from Google Analytics, also install the <a href="http://www.ioncannon.net/projects/google-analytics-dashboard-wordpress-widget/">Google Analytics Dashboard plugin</a>. This will give you some idea of how popular an article is, which might influence your decisions.

== Translations ==

If you would like to send me a translation, please write to me through <a href="http://sillybean.net/about/contact/">my contact page</a>. Let me know which plugin you've translated and how you would like to be credited. I will write you back so you can attach the files in your reply.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit the Settings screen to set your status labels (redundant, outdated, trivial) and choose which content types (posts, pages, and/or custom) should be audited.

== Screenshots ==

1. The options screen
1. Edit pages, with the content audit columns and filter dropdowns
1. Edit a page, with the content audit notes, owner, and status boxes
1. The overview screen under the Dashboard

== Upgrade Notice ==

= 1.3 =
This version requires at least WP 3.1, and fixes compatibility problems with 3.2. Authors are now prevented from auditing their own posts when the auditor role option is set to Administrator or Editor. You can now choose whether to send email notifications immediately.
= 1.4 =
New per-post expiration dates. New Overview screen (the "boss view") under Dashboard. Supports custom roles.

== Changelog ==

= 1.4.2 =
* Fixed various notices and warnings.
= 1.4.1 =
* Fixed disappearing columns after Quick Edit.
= 1.4 =
* New Overview screen (the "boss view") under Dashboard. Shows counts for each content audit attribute (outdated, trivial, etc.) and lists how many of each content type belong to the various content owners.
* New option to set an expiration date for individual posts/pages/etc., at which time the content will be marked as outdated.
* Supports custom roles.
* Added "audited" status to the default list, to be used when the audit for that item is complete. This can be removed.
= 1.3.1 =
* Bugfix: The auto-outdate feature was using 'months' no matter what unit of time you chose. This is fixed.
* Authors or contributors who can't audit content can now see the audit notes, owner, and attributes on their own posts.
* Improvements to the Dashboard widget. 
= 1.3 =
* Authors are now prevented from auditing their own posts when the auditor role option is set to Administrator or Editor.
* You can now choose whether to send email notifications immediately.
* Bugfix: All the default attributes are now created when the plugin is first activated. (Only Outdated appeared before.)
* Bugfix: Auditing media files no longer prevents you from editing titles and descriptions.
* Bugfix: Audit fields are shown for media files ONLY when you have chosen to audit media.
* Various warnings and notices cleaned up (thanks to <a href="http://www.linkedin.com/in/davidmdoolin">David Doolin</a>).
* Compatibility fixes for WP 3.2.
= 1.2.1 =
* Bugfix: The option to show the status and notes to logged-in users will now respect the checkbox
* Bugfix: You should now be able to delete all the built-in status categories except Outdated (which is used by the auto-outdate feature).
= 1.2 =
* New feature: Automatically mark content as outdated after a certain period of time
* New feature: Email content owners (or original authors) a summary of outdated content
* Bugfix: You should no longer see notices for pages that do not have a content status (or anywhere else, for that matter).
= 1.1.2 =
* Bugfix: the default attributes (redundant, outdated, etc.) were not created properly when the plugin was installed.
= 1.1.1 =
* Fixed a bug that prevented the audit columns from appearing on the Edit Pages screens
= 1.1 =
* Allows you to audit media files.
= 1.01 = 
* Fixed a typo that prevented you from leaving the content owner field blank when editing something
* Moved the Google Analytics Dashboard plugin's sparklines column to the last row of the Edit screen tables, if that plugin is installed
= 1.0 =
* Out of beta!
= 0.9b =
* Changed the way the content status taxonomy is created so that you can actually edit and delete the built-in categories.
= 0.8b =
* First beta.