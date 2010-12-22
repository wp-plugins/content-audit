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
	global $post; 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('content_audit_nonce', '_content_audit_nonce'); 
	$options = get_option('content_audit');
	$atts = explode("\n", $options['atts']);
?>

<div id="audit-atts">
	<?php
	foreach ($atts as $att) {
		$att = trim($att);
		$attr = strtolower(esc_attr($att)); ?>
		<label>
			<input type="checkbox" name="_content_audit_<?php echo $attr; ?>" value="1" <?php checked('1', get_post_meta($post->ID, "_content_audit_$attr", true)); ?> />
			<?php echo $att; ?>
		</label>
	<?php
	}
	?>
</div>

<div id="audit-notes">
	<label for="_content_audit_notes">Notes</label> 
	<textarea name="_content_audit_notes"><?php echo wp_specialchars(stripslashes(get_post_meta($post->ID, '_content_audit_notes', true)), 1); ?></textarea>
</div>
<?php
}

function save_content_audit_meta_data( $post_id ) {

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
	
	$options = get_option('content_audit');
	$atts = explode("\n", $options['atts']);
	
	foreach ($atts as $att) {
		$att = trim($att);
		$attr = strtolower(esc_attr($att));
		
		if (empty($_POST["_content_audit_$attr"])) {
			$storedfield = get_post_meta( $post_id, "_content_audit_$attr", true );
			delete_post_meta($post_id, "_content_audit_$attr", $storedfield);
		}
		else 
			update_post_meta($post_id, "_content_audit_$attr", $_POST["_content_audit_$attr"]);	
	}

	if (empty($_POST['_content_audit_notes'])) {
		$storedfield = get_post_meta( $post_id, '_content_audit_notes', true );
		delete_post_meta($post_id, '_content_audit_notes', $storedfield);
	}
	else 
		update_post_meta($post_id, '_content_audit_notes', $_POST['_content_audit_notes']);
	
}
?>