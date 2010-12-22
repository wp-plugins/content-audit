<?php

add_filter('manage_posts_columns', 'content_audit_columns');
add_filter("manage_edit-page_columns", "content_audit_columns");
add_filter("manage_edit-course_columns", "content_audit_columns");
/*
$options = get_option('content_audit');
foreach ($options['types'] as $type) {
	if ($type == 'post')
		add_filter('manage_posts_columns', 'content_audit_columns');
	else
		add_filter("manage_edit-".$type."_columns", "content_audit_columns");
		
	//$content_types = get_post_type($type);
	add_action('manage_'.$type.'s_custom_column', 'content_audit_custom_column', 10, 2);
}
*/

add_action('manage_posts_custom_column', 'content_audit_custom_column', 10, 2);
add_action('manage_pages_custom_column', 'content_audit_custom_column', 10, 2);
add_action('manage_courses_custom_column', 'content_audit_custom_column', 10, 2);

// rearrange the columns on the Edit screens
function content_audit_columns($defaults) {
	// preserve the original column headings
	$comments = $defaults['comments'];
	$date = $defaults['date'];
	// remove default date and comments
	unset($defaults['comments']);
	unset($defaults['date']);
	// insert content audit column
    $defaults['content_audit'] = __('Content Audit');
	// restore default date and comments
	$defaults['comments'] = $comments;
	$defaults['date'] = $date;
    return $defaults;
}

// print the contents of the new Content Audit column
function content_audit_custom_column($column_name, $id) {
	global $post;
	$options = get_option('content_audit');
	$atts = explode("\n", $options['atts']);
	foreach ($atts as $att) {
		$att = trim($att);
		$attr = strtolower(esc_attr($att)); 
		if (get_post_meta($post->ID, "_content_audit_$attr", true) == 1)
			$printatts[] = $att;
	}
	echo implode(', ', $printatts);
	if ($_GET['mode'] == 'excerpt')
		echo '<p>'.get_post_meta($post->ID, "_content_audit_notes", true).'</p>';
}

add_filter('posts_where', 'content_audit_posts_where');
add_action('restrict_manage_posts', 'content_audit_restrict_manage_posts');

// print the dropdown box to filter posts by audit field
function content_audit_restrict_manage_posts() {
	$options = get_option('content_audit');
	if (isset($_GET['audit_field'])) $field = $_GET['audit_field'];
	else $field = ''; 
	if (isset($_GET['post_type'])) $type = $_GET['post_type'];
	else $type = 'post';
	
	if ($options['types'][$type] == '1') {
		?>
		<select name="audit_field" id="audit_field" class="postform">
		<option value="0">View all audit fields</option>
	
		<?php
	
		$atts = explode("\n", $options['atts']);
		foreach ($atts as $att) {
			$att = trim($att);
			$attr = strtolower(esc_attr($att)); 
			if ($attr != 'notes') { ?>
				<option value="<?php echo $attr; ?>" <?php selected($attr, $field) ?>><?php echo $att; ?></option>
			<?php	}
		}
		?>
		</select>
	<?php
	}
}

// amend the db query based on audit field dropdown selection
function content_audit_posts_where($where)
{
	global $wpdb;
	$options = get_option('content_audit');
	if (isset($_GET['audit_field'])) {
		$field = $_GET['audit_field']; 
		$where .= " AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_content_audit_$field' AND meta_value='1' )";
	}
	return $where;
}
?>
