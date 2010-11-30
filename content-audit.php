<?php
/*
Plugin Name: Content Audit
Plugin URI: http://sillybean.net/code/wordpress/content-audit/
Description: Lets you create a content inventory and notify the responsible parties about their outdated content. 
Version: 1.2b6
Author: Stephanie Leary
Author URI: http://sillybean.net/

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
* In quick edit, use JS to select current content owner (if any)
*/

// when activated, add option
register_activation_hook(__FILE__, 'content_audit_activation');
function content_audit_activation() {
	if (!function_exists('register_taxonomy_for_object_type')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate myself
		wp_die("Sorry, but you can't run this plugin, it requires WordPress 3.0.");
	}
	// set defaults
	$options = array();	
	$options['types'] = array('page' => 1);
	$options['roles'] = 'edit_pages';
	$options['display_switch'] = '0';
	$options['display'] = '0';
	$options['css'] = 'div.content-audit { background: #ffc; }
p.content-notes { font-style: italic; }';
	$options['mark_outdated'] = 0;
	$options['outdate'] = 1;
	$options['outdate_unit'] = 'years';
	$options['notify'] = 0;
	$options['notify_authors'] = 0;	
	$options['interval'] = 'monthly';	
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
	$css = add_options_page(__('Content Audit Options', 'content-audit'), __('Content Audit', 'content-audit'), 'manage_options', 'content-audit', 'content_audit_options');
	// Add CSS to some specific admin pages
	add_action("admin_head-$css", 'content_audit_css');
	add_action("admin_head-post.php", 'content_audit_css');
	add_action("admin_head-edit.php", 'content_audit_css');
	add_action("admin_head-index.php", 'content_audit_css');
}

function content_audit_css() {	?>
	<style type="text/css">
	#content_audit_types li { display: inline; padding-right: 2em; }
	#content_audit_meta label { padding-right: 2em; }
	#content_audit_meta, #content_audit_meta .inside { overflow: auto; }
	#audit-notes { width: 100%; }
	#audit-notes textarea { width: 95%; margin: 0 1em 1em 0; }
	#content_audit_form textarea { display: block; width: 30em; height: 10em; }
	#posts-filter th#ID { width: 4em; }
	table#content-audit-outdated { border: 0; }
	table#content-audit-outdated td.column-title { padding: 8px .5em; }
	table#content-audit-outdated td.column-date { padding: 8px .5em 8px 0; width: 30%; }
	table#content-audit-outdated th.column-date { text-align: left; width: 30%; padding: 0; }
	</style>
<?php 
}

// i18n
if (!defined('WP_PLUGIN_DIR'))
	define('WP_PLUGIN_DIR', dirname(dirname(__FILE__))); 
$lang_dir = basename(dirname(__FILE__)). '/languages';
load_plugin_textdomain( 'content-audit', 'WP_PLUGIN_DIR'.$lang_dir, $lang_dir );

// load stuff
include_once(dirname (__FILE__)."/content-audit-fields.php");
include_once(dirname (__FILE__)."/content-audit-options.php");
include_once(dirname (__FILE__)."/content-audit-report.php");
include_once(dirname (__FILE__)."/content-audit-schedule.php");
?>