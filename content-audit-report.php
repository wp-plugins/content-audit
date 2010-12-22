<?php

add_action('admin_init', 'content_audit_column_setup');

function content_audit_column_setup() {
	$options = get_option('content_audit');
	foreach ($options['types'] as $type => $val) {
	 	if	(($type == 'post') && (!empty($val))) {
			add_filter('manage_posts_columns', 'content_audit_columns');
		}
		elseif (($type == 'page') && (!empty($val))) {
			add_filter('manage_pages_columns', 'content_audit_columns');
		}
		else {
			add_filter('manage_'.$type.'_posts_columns', 'content_audit_columns');
		}
		
		// fill in the columns
		// (these two will cover all content types)
		add_action('manage_posts_custom_column', 'content_audit_custom_column', 10, 2);
		add_action('manage_pages_custom_column', 'content_audit_custom_column', 10, 2);
		
		// add filter dropdowns
		add_action('restrict_manage_posts', 'content_audit_restrict_content_owners');
		add_action('restrict_manage_posts', 'content_audit_restrict_content_status');
		// modify edit screens' query when dropdown option is chosen
		add_filter('posts_where', 'content_audit_posts_where');
	}	
}

// rearrange the columns on the Edit screens
function content_audit_columns($defaults) {
	// preserve the original column headings
	$comments = $defaults['comments'];
	$date = $defaults['date'];
	$cb = $defaults['cb'];
	$cats = $defaults['categories'];
	$tags = $defaults['tags'];
	// remove default checkbox, date and comments
	unset($defaults['comments']);
	unset($defaults['date']);
	unset($defaults['cb']);
	unset($defaults['categories']);
	unset($defaults['tags']);
	// insert content owner column
	$defaults['content_owner'] = __('Content Owner');
	// insert content audit taxonomy column
    $defaults['content_status'] = __('Content Status');
	// restore default date and comments
	if (!empty($cats)) $defaults['categories'] = $cats;
	if (!empty($tags)) $defaults['tags'] = $tags;
	$defaults['comments'] = $comments;
	$defaults['date'] = $date;
	// restore checkbox, add ID as the second column, then add the rest
    return array('cb' => $cb, 'ID' => __('ID'))+$defaults;
}

// print the contents of the new Content Audit column
function content_audit_custom_column($column_name, $id) {
	global $post;
	if ($column_name == 'content_status') {
		$terms = wp_get_object_terms($post->ID, 'audit', array('fields' => 'all'));
		foreach ($terms as $term) {
			if (!empty($_GET['post_type'])) $type = 'post_type='.$_GET['post_type'].'&';
			else $type = '';
			$termlist[] = '<a href="edit.php?'.$type.'audit='.$term->slug.'">'.$term->name.'</a>';
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
		if (!empty($ownerID)) {
			if (!empty($_GET['post_type'])) $type = 'post_type='.$_GET['post_type'].'&';
			else $type = '';
			echo '<a href="edit.php?'.$type.'owner='.$ownerID.'">'.get_the_author_meta('display_name', $ownerID ).'</a>';
		}
	}
}

// print the dropdown box to filter posts by content status
function content_audit_restrict_content_status() {
	$options = get_option('content_audit');
	if (isset($_GET['audit'])) $content_status = $_GET['audit'];
	else $content_status = ''; 
	if (isset($_GET['post_type'])) $type = $_GET['post_type'];
	else $type = 'post';
	
	if ($options['types'][$type] == '1') {
		?>
		<select name="audit" id="audit" class="postform">
		<option value="0"><?php _e("Content status"); ?></option>
	
		<?php
		$terms = get_terms( 'audit', '' );
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
	$options = get_option('content_audit');
	if (isset($_GET['owner'])) $owner = $_GET['owner'];
	else $owner = '0'; 
	if (isset($_GET['post_type'])) $type = $_GET['post_type'];
	else $type = 'post';
	
	if ($options['types'][$type] == '1') {
		?>
		<select name="owner" id="owner" class="postform">
		<option value="0"><?php _e("Content owner"); ?></option>
	
		<?php
		$users = get_users_of_blog();
		foreach ($users as $user) { ?>
			<option value="<?php echo $user->ID; ?>" <?php selected($user->ID, $owner) ?>><?php echo $user->display_name; ?></option>
<?php	}
		?>
		</select>
	<?php
	}
}

// amend the db query based on content owner dropdown selection
function content_audit_posts_where($where)
{
	global $wpdb;
	if (isset($_GET['owner'])) { 
		$where .= " AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_content_audit_owner' AND meta_value='{$_GET['owner']}' )";
	}
	
	return $where;
}

add_action('quick_edit_custom_box', 'add_quickedit_content_owner');
// Outputs the new custom Quick Edit field HTML
function add_quickedit_content_owner($column_name, $type) { 
	if ($column_name == 'content_owner') {
		global $post;
		$owner = get_post_meta($post->ID, '_content_audit_owner', true);		
		?>
	<fieldset class="inline-edit-col-right">
	    <div class="inline-edit-col">
		<label class="inline-edit-status alignleft">
			<span class="title"><?php _e("Content Owner"); if (empty($owner)) echo "empty"; echo $post->ID; ?></span>
			<select name="_content_audit_owner" id="_content_audit_owner" class="postform">
			<option value="0"><?php _e("None"); ?></option>
			<?php
			$users = get_users_of_blog();
			foreach ($users as $user) { ?>
				<option value="<?php echo $user->ID; ?>" <?php selected($user->ID, $owner) ?>>
					<?php echo $user->display_name; ?></option>
	<?php	}
			?>
			</select>
			</label>
		</div>
	</fieldset>
<?php }
}
?>
