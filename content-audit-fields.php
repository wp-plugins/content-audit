<?php
/* Custom Taxonomy */

add_action('init', 'create_content_audit_tax');

function activate_content_audit_tax() {
	create_content_audit_tax();
	activate_content_audit_terms();
	$GLOBALS['wp_rewrite']->flush_rules();
}

function create_content_audit_tax() {
	register_taxonomy(
		'content_audit',
		'page',
		array(
			'label' => __('Content Audit Attributes', 'content-audit'),
			'hierarchical' => true,
			'show_tagcloud' => false,
			'helps' => 'Enter content attributes separated by commas.',
		)
	);
	
	wp_insert_term(__('Outdated','content-audit'), 'content_audit'); // this one stays; the others can be edited
}

function activate_content_audit_terms() {
	wp_insert_term(__('Redundant','content-audit'), 'content_audit');
	wp_insert_term(__('Trivial','content-audit'), 'content_audit');
	wp_insert_term(__('Review SEO','content-audit'), 'content_audit');
	wp_insert_term(__('Review Style','content-audit'), 'content_audit');
}

add_action('admin_init', 'content_audit_taxonomies');

function content_audit_taxonomies() {
	$options = get_option('content_audit');
	foreach ($options['types'] as $content_type => $val) {
		if ($val && current_user_can($options['roles']))
			register_taxonomy_for_object_type('content_audit', $content_type);
	}
}


add_action('admin_init', 'content_audit_boxes');

/* Custom Fields */

function content_audit_boxes() {
	$options = get_option('content_audit');
	if (current_user_can($options['roles'])) {
		foreach ($options['types'] as $content_type => $val) {
			if ($val) {
				add_meta_box( 'content_audit_meta', __('Content Audit Notes','content-audit'), 'content_audit_notes_meta_box', $content_type, 'normal', 'high' );
				add_meta_box( 'content_audit_owner', __('Content Owner','content-audit'), 'content_audit_owner_meta_box', $content_type, 'side', 'low' );
				if ($content_type == 'attachment') {
					add_filter('attachment_fields_to_edit', 'content_audit_media_fields', 10, 2);
					add_filter('attachment_fields_to_save', 'save_content_audit_media_meta', 10, 2);
				}
			}
		}
		add_action('save_post', 'save_content_audit_meta_data');
	}
	else {
		add_action( 'admin_menu', 'remove_audit_taxonomy_boxes' );
	}
}

function remove_audit_taxonomy_boxes()
{
	$options = get_option('content_audit');
	foreach ($options['types'] as $content_type => $val) {
		if ($val)
			remove_meta_box( 'content_auditdiv', $content_type, 'side' );
	}
}

function content_audit_notes_meta_box() {
	global $post; 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('content_audit_notes_nonce', '_content_audit_notes_nonce'); 
?>
<div id="audit-notes">
	<textarea name="_content_audit_notes"><?php echo esc_textarea(get_post_meta($post->ID, '_content_audit_notes', true)); ?></textarea>
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
	if (empty($owner)) $owner = -1;
	wp_dropdown_users( array(
		'selected' => $owner, 
		'name' => '_content_audit_owner', 
		'show_option_none' => __('Select a user','content-audit'),
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

function save_content_audit_media_meta( $post, $attachment ) {
	// in this filter, $post is an array of things being saved, not the usual $post object
		
	if (isset($attachment['_content_audit_owner'])) 
		update_post_meta($post['ID'], '_content_audit_owner', $attachment['_content_audit_owner']);
	
	if (isset($attachment['audit_notes'])) 
		update_post_meta($post['ID'], '_content_audit_notes', $attachment['audit_notes']);
		
	return $post;
}

function content_audit_media_fields($form_fields, $post) {
	
	$notes = esc_textarea(get_post_meta($post->ID, '_content_audit_notes', true));
	
	$owner = get_post_meta($post->ID, '_content_audit_owner', true);
	if (empty($owner)) $owner = -1;
	
	$owner_dropdown = wp_dropdown_users( array(
		'selected' => $owner, 
		'name' => "attachments[$post->ID][_content_audit_owner]", 
		'show_option_none' => __('Select a user','content-audit'),
		'echo' => 0,
	));
	
	$form_fields['audit_owner'] = array(
			'label' => __('Content Audit Owner'),
			'input' => 'select',
			'select' => $owner_dropdown,
		);
		
	$form_fields['audit_notes'] = array(
			'label' => __('Content Audit Notes'),
			'input' => 'textarea',
		/*	'html' => "<textarea name='attachments[$post->ID][audit_notes]' />$notes</textarea>", */
			'value' => $notes,
		);
		
	return $form_fields;
	
}
?>