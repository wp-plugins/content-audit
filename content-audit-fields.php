<?php
/* Custom Taxonomy */

add_action('init', 'create_content_audit_tax');
register_activation_hook( __FILE__, 'activate_content_audit_tax' ); 

function activate_content_audit_tax() {
	create_content_audit_tax();
	$GLOBALS['wp_rewrite']->flush_rules();
}

function create_content_audit_tax() {
	register_taxonomy(
		'content_audit',
		'page',
		array(
			'label' => __('Content Audit', 'content-audit'),
			'hierarchical' => true,
			'show_tagcloud' => false,
		)
	);
	if (wp_count_terms('content_audit') == 0) {
		wp_insert_term(__('Redundant','content-audit'), 'content_audit');
		wp_insert_term(__('Outdated','content-audit'), 'content_audit');
		wp_insert_term(__('Trivial','content-audit'), 'content_audit');
		wp_insert_term(__('Review SEO','content-audit'), 'content_audit');
		wp_insert_term(__('Review Style','content-audit'), 'content_audit');
	}
}

add_action('admin_init', 'content_audit_taxonomies');

function content_audit_taxonomies() {
	$options = get_option('content_audit');
	foreach ($options['types'] as $content_type => $val) {
		if ($val)
			register_taxonomy_for_object_type('content_audit', $content_type);
	}
}

/* Custom Fields */

add_action('admin_init', 'content_audit_boxes');
add_action('save_post', 'save_content_audit_meta_data');

function content_audit_boxes() {
	$options = get_option('content_audit');
	foreach ($options['types'] as $content_type => $val) {
		if ($val) {
			add_meta_box( 'content_audit_meta', __('Content Audit Notes','content-audit'), 'content_audit_notes_meta_box', $content_type, 'normal', 'high' );
			add_meta_box( 'content_audit_owner', __('Content Owner','content-audit'), 'content_audit_owner_meta_box', $content_type, 'side', 'low' );
		}
	}
}

function content_audit_notes_meta_box() {
	global $post; 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('content_audit_notes_nonce', '_content_audit_notes_nonce'); 
?>
<div id="audit-notes">
	<textarea name="_content_audit_notes"><?php echo wp_specialchars(stripslashes(get_post_meta($post->ID, '_content_audit_notes', true)), 1); ?></textarea>
</div>
<?php
}

function content_audit_owner_meta_box() {
	global $post; 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('content_audit_owner_nonce', '_content_audit_owner_nonce'); 
?>
<div id="audit-owner">
	<?php
	$owner = get_post_meta($post->ID, '_content_audit_owner', true);
	wp_dropdown_users( array(
		'selected' => $owner, 
		'name' => '_content_audit_owner', 
		'show_option_none' => _e('Select a user','content-audit'),
	)); ?>
</div>
<?php
}

function save_content_audit_meta_data( $post_id ) {
	
	if (defined('DOING_AJAX') && !DOING_AJAX) {
		// check nonces
		check_admin_referer('content_audit_notes_nonce', '_content_audit_notes_nonce');
		check_admin_referer('content_audit_owner_nonce', '_content_audit_owner_nonce');
	}
	
	// check capabilites
	if ( 'page' == $_POST['post_type'] && !current_user_can( 'edit_page', $post_id ) )
		return $post_id;
			
	// save fields	
	// ignore autosaves
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

	// for revisions, save using parent ID
	if (wp_is_post_revision($post_id)) $post_id = wp_is_post_revision($post_id); 
	
	if (empty($_POST['_content_audit_owner'])) {
		$storedfield = get_post_meta( $post_id, '_content_audit_owner', true );
		delete_post_meta($post_id, '_content_audit_owner', $storedfield);
	}
	else 
		update_post_meta($post_id, '_content_audit_owner', $_POST['_content_audit_owner']);
	
	
	if (empty($_POST['_content_audit_notes'])) {
		$storedfield = get_post_meta( $post_id, '_content_audit_notes', true );
		delete_post_meta($post_id, '_content_audit_notes', $storedfield);
	}
	else 
		update_post_meta($post_id, '_content_audit_notes', $_POST['_content_audit_notes']);
	
}
?>