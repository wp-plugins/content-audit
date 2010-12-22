<?php
/* Custom Fields */

add_action('admin_init', 'content_audit_boxes');
add_action('save_post', 'save_content_audit_meta_data');

function content_audit_boxes() {
	$options = get_option('content_audit');
	foreach ($options['types'] as $content_type => $val) {
		if ($val)
			add_meta_box( 'content_audit_meta', __('Content Audit'), 'content_audit_meta_box', $content_type, 'normal', 'high' );
	}
}

function content_audit_meta_box() {
	global $post, $wpdb; 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('content_audit_nonce', '_content_audit_nonce'); 
?>
	<div id="audit-atts">
		<p><?php _e("This page is:"); ?>
		<?php
		$options = get_option('content_audit');
		$fields = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'content_audit WHERE ID = '.$post->ID, ARRAY_A);
		$fields = maybe_unserialize($fields); ?>
		<label>
			<input type="checkbox" name="content_audit[redundant]" value="1" <?php checked('1', $fields['redundant']); ?> />
			<?php _e("Redundant"); ?>
		</label>
		<label>
			<input type="checkbox" name="content_audit[outdated]" value="1" <?php if (($fields['outdated'] == '1') || ($post->post_modified < strtotime('-'.$options['outdate'].' months'))) { ?>
				 	checked="checked" <?php } ?> />
			<?php _e("Outdated"); ?>
		</label>
		<label>
			<input type="checkbox" name="content_audit[trivial]" value="1" <?php checked('1', $fields['trivial']); ?> />
			<?php _e("Trivial"); ?>
		</label>
		<label>
			<input type="checkbox" name="content_audit[seo]" value="1" <?php checked('1', $fields['seo']); ?> />
			<?php _e("SEO"); ?>
		</label>
		<label>
			<input type="checkbox" name="content_audit[style]" value="1" <?php checked('1', $fields['style']); ?> />
			<?php _e("Style"); ?>
		</label>
		</p> 
	</div>
	
	<div id="audit-notes">
		<label for="content_audit[notes]"><?php _e("Notes"); ?></label> 
		<textarea name="content_audit[notes]"><?php echo wp_specialchars(stripslashes($fields['notes']), 1); ?></textarea>
	</div>
<?php
}

function save_content_audit_meta_data( $post_id ) {
	global $wpdb;

	// check nonces
	check_admin_referer('content_audit_nonce', '_content_audit_nonce');
	
	// check capabilites
	if ( 'page' == $_POST['post_type'] && !current_user_can( 'edit_page', $post_id ) )
		return $post_id;
			
	// save fields	
	// ignore autosaves
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

	// for revisions, save using parent ID
	if (wp_is_post_revision($post_id)) $post_id = wp_is_post_revision($post_id); 
	
//	$wpdb->show_errors();
	$newdata = array(
			'ID' => $post_id, 
			'redundant' => $_POST['content_audit']['redundant'], 
			'outdated' => $_POST['content_audit']['outdated'], 
			'trivial' => $_POST['content_audit']['trivial'], 
			'seo' => $_POST['content_audit']['seo'], 
			'style' => $_POST['content_audit']['style'], 
			'notes' => $_POST['content_audit']['notes']
		);
	$wpdb->replace($wpdb->prefix.'content_audit', $newdata, array('%d', '%d', '%d', '%d', '%d', '%d', '%s'));
//	$wpdb->print_error();
	
}
?>