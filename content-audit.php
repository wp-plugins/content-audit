<?php
/*
Plugin Name: Content Audit
Plugin URI: http://sillybean.net/code/wordpress/content-audit/
Description: Lets you create a content inventory. This version uses individual db fields and custom Edit screen columns.
Version: 0.4.5
Author: Stephanie Leary
Author URI: http://sillybean.net/

Changelog:
= 1.0 = 
* First release

Copyright 2010  Stephanie Leary  (email : steph@sillybean.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/*

TODO:
* Loop through all option-checked post types for adding the column filters
	
version 2: 
* Export to Excel?
* Create Dashboard widget
* incorporate checkboxes and notes into Quick Edit
* support for sparklines from Google Analytics: http://www.ioncannon.net/projects/google-analytics-dashboard-wordpress-widget/
*/


// when activated, add option
register_activation_hook(__FILE__, 'content_audit_activation');
function content_audit_activation() {
	// set defaults
	$options = array();	
	$options['types'] = array();
	$options['atts'] = array('Redundant', 'Outdated', 'Trivial', 'SEO', 'Style');
	$options['outdate'] = 12;	
	// set the new option
	add_option('content_audit', $options, '', 'yes');
}

// register options
add_action('admin_init', 'register_content_audit_options' );
function register_content_audit_options(){
	register_setting( 'content_audit', 'content_audit' );
}

// when uninstalled, remove option
register_uninstall_hook( __FILE__, 'content_audit_delete_options' );
function content_audit_delete_options() {
	delete_option('content_audit');
}

// add settings link to plugin list
add_filter('plugin_action_links', 'content_audit_plugin_actions', 10, 2);
function content_audit_plugin_actions($links, $file) {
 	if ($file == 'content-audit/content-audit.php' && function_exists("admin_url")) {
		$settings_link = '<a href="' . admin_url('options-general.php?page=content-audit') . '">' . __('Settings', 'content-audit') . '</a>';
		array_unshift($links, $settings_link); 
	}
	return $links;
}

// add the pages to the navigation menu
add_action('admin_menu', 'content_audit_add_pages');

function content_audit_add_pages() {
    // Add a new submenu under Options:
	$css = add_options_page('Content Audit Options', 'Content Audit', 'manage_options', 'content-audit', 'content_audit_options');
	add_action("admin_head-$css", 'content_audit_css');
	add_action("admin_head-post.php", 'content_audit_css');
	// Add a new submenu under Tools:
//	$css = add_management_page('Content Audit Report', 'Content Audit Report', 'manage_options', 'content-audit-report', 'content_audit_report');
//	add_action("admin_head-$css", 'content_audit_css');
}

function content_audit_css() { 
	$options = get_option('content_audit');
	$atts = explode("\n", $options['atts']);
	?>
	<style type="text/css">
	#content_audit_types li { display: inline; padding-right: 2em; }
	#content_audit_meta label { padding-right: 2em; }
	#content_audit_meta, #content_audit_meta .inside { overflow: auto; }
	#audit-atts { width: 30%; float: left; clear: left; line-height: 1.2; }
	#audit-atts label { display: block; }
	#audit-notes { width: 69%; float: right; clear: right; }
	#audit-notes textarea { width: 95%; margin: 0 1em 1em 0; }
	#content_audit_form textarea { display: block; width: 30em; height: 10em; }
	.column-title { width: 50em; }
	.content-audit tbody th.check-column { padding: 3px 7px; width: 5em !important; }
	</style>
<?php 
}

// i18n
if (!defined('WP_PLUGIN_DIR'))
	define('WP_PLUGIN_DIR', dirname(dirname(__FILE__))); 
$lang_dir = basename(dirname(__FILE__)). '/languages';
load_plugin_textdomain( 'content_audit', 'WP_PLUGIN_DIR'.$lang_dir, $lang_dir );

// load stuff
include_once(dirname (__FILE__)."/content-audit-fields.php");
include_once(dirname (__FILE__)."/content-audit-options.php");
include_once(dirname (__FILE__)."/content-audit-report.php");
?>