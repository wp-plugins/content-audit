<?php
/*
Plugin Name: Content Audit
Plugin URI: http://sillybean.net/code/wordpress/content-audit/
Description: Lets you create a content inventory and notify the responsible parties about their outdated content. 
Version: 1.5.3
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
* Eliminate JS for custom fields in quick edit
* when this is fixed: http://core.trac.wordpress.org/ticket/16392
*/

// when activated, add option and create taxonomy terms
register_activation_hook( __FILE__, 'activate_content_audit_tax' );
register_activation_hook(__FILE__, 'content_audit_activation');
function content_audit_activation() {
	if (!function_exists('register_taxonomy_for_object_type')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate myself
		wp_die("Sorry, but you can't run this plugin, it requires WordPress 3.0.");
	}
	// set defaults
	$options = array();	
	$options['post_types'] = array('page');
	$options['rolenames'] = array('administrator','editor');
	$options['display_switch'] = '0';
	$options['display'] = '0';
	$options['css'] = 'div.content-audit { background: #ffc; }
p.content-notes { font-style: italic; }';
	$options['mark_outdated'] = 0;
	$options['outdate'] = 1;
	$options['outdate_unit'] = 'years';
	$options['notify'] = 0;
	$options['notify_now'] = 0;	
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
// testing only
//register_deactivation_hook( __FILE__, 'content_audit_delete_options' );

// add the pages to the navigation menu
add_action('admin_menu', 'content_audit_add_pages');

function content_audit_add_pages() {
    // Add a new submenu under Settings:
	$opt = add_options_page(__('Content Audit Options', 'content-audit'), __('Content Audit', 'content-audit'), 'manage_options', 'content-audit', 'content_audit_options');
	// Add the boss view under the Dashboard:
	$dash = add_dashboard_page(__('Content Audit Overview', 'content-audit'), __('Content Audit Overview', 'content-audit'), 'manage_options', 'content-audit', 'content_audit_overview');
	// Add CSS to some specific admin pages
	add_action("admin_head-$opt", 'content_audit_css');
	add_action("admin_head-$dash", 'content_audit_css');
	add_action("admin_head-edit.php", 'content_audit_css');
	add_action("admin_head-post.php", 'content_audit_css');
	add_action("admin_head-post-new.php", 'content_audit_css');
//	add_action("admin_head-edit.php", 'content_audit_css');
	add_action("admin_head-index.php", 'content_audit_css');
	// Add jQuery UI CSS to post and media screens
//	add_action( 'admin_print_scripts-edit.php', 'content_audit_scripts' );
	add_action( 'admin_print_scripts-post.php', 'content_audit_scripts' );
	add_action( 'admin_print_scripts-post-new.php', 'content_audit_scripts' );
	add_action( 'admin_print_scripts-media.php', 'content_audit_scripts' );
	// initialize datepicker on post ane media screens
//	add_action( 'admin_footer-edit.php', 'content_audit_admin_footer' );
	add_action( 'admin_footer-post.php', 'content_audit_admin_footer' );
	add_action( 'admin_footer-post-new.php', 'content_audit_admin_footer' );
	add_action( 'admin_footer-media.php', 'content_audit_admin_footer' );
	// add quick/bulk edit js
	add_action( 'admin_print_scripts-edit.php', 'content_audit_enqueue_edit_scripts' );
}

function content_audit_css() {	?>
	<style type="text/css">
	#content_audit_types li, #content_audit_roles li { display: inline; padding-right: 2em; }
	#content_audit_meta label { padding-right: 2em; }
	#content_audit_meta, #content_audit_meta .inside { overflow: auto; }
	#audit-notes { width: 100%; }
	#audit-notes textarea { width: 99%; margin: 0 1em 1em 0; }
	#content_audit_form textarea { display: block; width: 30em; height: 10em; }
	#boss-squares { margin: 2em 0; overflow: auto; }
	#boss-squares li { float: left; height: 8em; padding: .5em 0; text-align: center; background: #f9f9f9; border: 1px solid #dfdfdf; border-radius: 3px; }
	#boss-squares li a { display: block; text-decoration: none; }
	#boss-squares li h3 { font-size: 2em; margin-top: 0; padding-top: 1em; }
	#boss-squares li p { margin-bottom: 0; padding-bottom: 1em; }
	#posts-filter th#ID { width: 4em; }
	#posts-filter th#expiration { width: 6em; }
	table.boss-view { margin-bottom: 2em; }
	table#content-audit-outdated { border: 0; }
	table#content-audit-outdated td.column-title { padding: 8px .5em; }
	table#content-audit-outdated td.column-date { padding: 8px .5em 8px 0; width: 30%; }
	table#content-audit-outdated th.column-date { text-align: left; width: 30%; padding: 0; }
	label.indent { margin-left: 2em; }
	</style>
<?php 
}

function content_audit_datepicker_css() {
    wp_register_style( 'wp-jquery-ui', plugins_url('wp-jquery-ui.css', __FILE__) );
}
add_action( 'admin_init', 'content_audit_datepicker_css' );

function content_audit_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_style( 'wp-jquery-ui' );
}

function content_audit_admin_footer() { ?>
	<script type="text/javascript">
	   jQuery(document).ready(function(){
		  /* for post screens */
	      jQuery('.datepicker').datepicker({
	         dateFormat : 'm/d/y'
	      });
	  	  /* for media screens */
		  jQuery('tr.audit_expiration input.text').datepicker({
	         dateFormat : 'm/d/y'
	      });
	
	   });
	</script><?php
}

function content_audit_enqueue_edit_scripts() {
	wp_enqueue_script( 'content_audit_quickedit', plugins_url( 'quickedit.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), '', true );
}

// i18n
load_plugin_textdomain( 'content-audit', '', plugin_dir_path(__FILE__) . '/languages' );

// load stuff
include_once(dirname (__FILE__)."/content-audit-fields.php");
include_once(dirname (__FILE__)."/content-audit-options.php");
include_once(dirname (__FILE__)."/content-audit-report.php");
include_once(dirname (__FILE__)."/content-audit-schedule.php");
include_once(dirname (__FILE__)."/content-audit-overview.php");