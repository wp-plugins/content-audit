<?php

add_action('admin_init', 'content_audit_column_setup');

function content_audit_column_setup() {
	$options = get_option('content_audit');
	if ( current_user_can($options['roles']) ) {
		foreach ($options['types'] as $type => $val) {
			
			switch ($type) {
				case 'post': if (!empty($val)) add_filter('manage_posts_columns', 'content_audit_columns');
					break;
				case 'page': if (!empty($val)) add_filter('manage_pages_columns', 'content_audit_columns');
					break;
				case 'attachment': if (!empty($val)) add_filter('manage_media_columns', 'content_audit_columns');
					break;
				default: add_filter('manage_'.$type.'_posts_columns', 'content_audit_columns');
			}
		
			// fill in the columns
			// (these three will cover all content types)
			add_action('manage_posts_custom_column', 'content_audit_custom_column', 10, 2);
			add_action('manage_pages_custom_column', 'content_audit_custom_column', 10, 2);
			add_action('manage_media_custom_column', 'content_audit_custom_column', 10, 2);
		
			// add filter dropdowns
			add_action('restrict_manage_posts', 'content_audit_restrict_content_authors');
			add_action('restrict_manage_posts', 'content_audit_restrict_content_owners');
			add_action('restrict_manage_posts', 'content_audit_restrict_content_status');
				
			// modify edit screens' query when dropdown option is chosen
			add_filter('posts_where', 'content_audit_posts_where');
		
			// Add author field to quick edit
//			add_action('quick_edit_custom_box', 'add_quickedit_content_owner');
		}
	}	
}

// rearrange the columns on the Edit screens
function content_audit_columns($defaults) {
	// preserve the original column headings
	$original['comments'] = $defaults['comments'];
	$original['date'] = $defaults['date'];
	$original['cb'] = $defaults['cb'];
	$original['cats'] = $defaults['categories'];
	$original['tags'] = $defaults['tags'];
	$original['analytics'] = $defaults['analytics'];
	// remove default columns
	unset($defaults['comments']);
	unset($defaults['date']);
	unset($defaults['cb']);
	unset($defaults['categories']);
	unset($defaults['tags']);
	unset($defaults['analytics']);
	// insert content owner and status taxonomy columns
	$defaults['content_owner'] = __('Content Owner', 'content-audit');
    $defaults['content_status'] = __('Content Status', 'content-audit');
	// restore default columns
	if (!empty($original['cats'])) $defaults['categories'] = $original['cats'];
	if (!empty($original['tags'])) $defaults['tags'] = $original['tags'];
	$defaults['comments'] = $original['comments'];
	$defaults['date'] = $original['date'];
	if (!empty($original['analytics'])) $defaults['analytics'] = $original['analytics'];
	// restore checkbox, add ID as the second column, then add the rest
    return array('cb' => $original['cb'], 'ID' => __('ID')) + $defaults;
}

// print the contents of the new Content Audit columns
function content_audit_custom_column($column_name, $id) {
	global $post;
	if ($column_name == 'content_status') {
		$terms = wp_get_object_terms($post->ID, 'content_audit', array('fields' => 'all'));
		foreach ($terms as $term) {
			if (!empty($_GET['post_type'])) $type = 'post_type='.$_GET['post_type'].'&';
			else $type = '';
			$termlist[] = '<a href="edit.php?'.$type.'content_audit='.$term->slug.'">'.$term->name.'</a>';
		}
		echo implode(', ', $termlist);

		if ($_GET['mode'] == 'excerpt')
			echo '<p>'.get_post_meta($post->ID, "_content_audit_notes", true).'</p>';
	}
	elseif ($column_name == 'ID') {
		echo $post->ID;
	}
	elseif ($column_name == 'content_owner') {
		$ownerID = get_post_meta($post->ID, "_content_audit_owner", true);
		if (!empty($ownerID) && $ownerID > 0) {
			if (!empty($_GET['post_type'])) $type = 'post_type='.$_GET['post_type'].'&';
			else $type = '';
			echo '<a href="edit.php?'.$type.'content_owner='.$ownerID.'">'.get_the_author_meta('display_name', $ownerID ).'</a>';
		}
	}
}

// print the dropdown box to filter posts by content status
function content_audit_restrict_content_status() {
	$options = get_option('content_audit');
	if (isset($_GET['content_audit'])) $content_status = $_GET['content_audit'];
	else $content_status = ''; 
	if (isset($_GET['post_type'])) $type = $_GET['post_type'];
	else $type = 'post';
	
	if ($options['types'][$type] == '1') {
		?>
		<select name="content_audit" id="content_audit" class="postform">
		<option value="0"><?php _e("Show all statuses", 'content-audit'); ?></option>
	
		<?php
		$terms = get_terms( 'content_audit', '' );
		foreach ($terms as $term) { ?>
			<option value="<?php echo $term->slug; ?>" <?php selected($term->slug, $content_status) ?>><?php echo $term->name; ?></option>
<?php	}
		?>
		</select>
	<?php
	}
}

// print the dropdown box to filter posts by content owner
function content_audit_restrict_content_owners() {
	global $user_ID;
	$options = get_option('content_audit');
	if (isset($_GET['content_owner'])) $owner = $_GET['content_owner'];
	else $owner = '0'; 
	if (isset($_GET['post_type'])) $type = $_GET['post_type'];
	else $type = 'post';
	
	if ($options['types'][$type] == '1') {
		$editable_ids = get_editable_user_ids( $user_ID );
		wp_dropdown_users(
			array(
				'include' => $editable_ids,
				'show_option_all' => __('Show all owners', 'content-audit'),
				'name' => 'content_owner',
				'selected' => isset($_GET['content_owner']) ? $_GET['content_owner'] : 0
			)
		);
	}
}

// print a dropdown to filter posts by author
function content_audit_restrict_content_authors()
{
	global $user_ID; 
	$editable_ids = get_editable_user_ids( $user_ID );
	wp_dropdown_users(
		array(
			'include' => $editable_ids,
			'show_option_all' => __('Show all authors', 'content-audit'),
			'name' => 'author',
			'selected' => isset($_GET['author']) ? $_GET['author'] : 0
		)
	);
}

// amend the db query based on content owner dropdown selection
function content_audit_posts_where($where)
{
	global $wpdb;
	if (isset($_GET['content_owner']) && !empty($_GET['content_owner'])) { 
		$where .= " AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_content_audit_owner' AND meta_value='{$_GET['content_owner']}' )";
	}	
	return $where;
}

// Outputs the new custom Quick Edit field HTML
function add_quickedit_content_owner($column_name, $type) { 
	if ($column_name == 'content_owner') {
		global $post, $user_ID;
		$owner = get_post_meta($post->ID, '_content_audit_owner', true);		
		?>
	<fieldset class="inline-edit-col-right">
	    <div class="inline-edit-col">
		<label class="inline-edit-status alignleft">
			<span class="title"><?php _e("Content Owner", 'content-audit'); ?></span>
			<?php
			$editable_ids = get_editable_user_ids( $user_ID );
			wp_dropdown_users(
				array(
					'include' => $editable_ids,
					'show_option_all' => __('None', 'content-audit'),
					'name' => '_content_audit_owner',
					'selected' => $owner
				)
			);			
			?>
			</label>
		</div>
	</fieldset>		
<?php }
}

// Prints the content status, notes, and owner on the front end
function content_audit_front_end_display($content) {
	$options = get_option('content_audit');
	if (!empty($options['display']) && (current_user_can($options['roles']))) {
		global $post;
		$out = '<p class="content-status">'.get_the_term_list($post->ID, 'content_audit', __('Content status: ', 'content-audit'), ', ','').'</p>';
		$ownerID = get_post_meta($post->ID, "_content_audit_owner", true);
		if (!empty($ownerID)) {
			$out .= '<p class="content-owner">'.__("Assigned to: ", 'content-audit').get_the_author_meta('display_name', $ownerID).'</p>';
		}
		$out .= '<p class="content-notes">'.get_post_meta($post->ID, "_content_audit_notes", true).'</p>';
		$out = '<div class="content-audit">'.$out.'</div>';
		
		$css = '<style type="text/css">'.$options['css'].'</style>';
		
		if ($options['display'] == 'above') return $out.$content;
		else return $content.$out;
	}
	else return $content;
}

add_filter('the_content', 'content_audit_front_end_display');

// Prints the CSS for the front end
function content_audit_front_end_css() {
	$options = get_option('content_audit');
	if (!empty($options['display']) && (current_user_can($options['roles']))) {	
		echo '<style type="text/css">'.$options['css'].'</style>';
	}
}
add_action('wp_head', 'content_audit_front_end_css');

?>
