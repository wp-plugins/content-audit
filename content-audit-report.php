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
	// preserve the original column headings and remove (unset) default columns
	if (isset($defaults['comments'])) {
		$original['comments'] = $defaults['comments'];
		unset($defaults['comments']);
	}
	$original['date'] = $defaults['date'];
	unset($defaults['date']);
	$original['cb'] = $defaults['cb'];
	unset($defaults['cb']);
	if (isset($defaults['categories'])) {
		$original['categories'] = $defaults['categories'];
		unset($defaults['categories']);
	}
	if (isset($defaults['tags'])) {
		$original['tags'] = $defaults['tags'];
		unset($defaults['tags']);
	}
	if (isset($defaults['analytics'])) {
		$original['analytics'] = $defaults['analytics'];
		unset($defaults['analytics']);
	}
	// insert content owner and status taxonomy columns
	$defaults['content_owner'] = __('Content Owner', 'content-audit');
    $defaults['content_status'] = __('Content Status', 'content-audit');
	// restore default columns
	if (isset($original['categories'])) $defaults['categories'] = $original['categories'];
	if (isset($original['tags'])) $defaults['tags'] = $original['tags'];
	if (isset($original['comments'])) $defaults['comments'] = $original['comments'];
	$defaults['date'] = $original['date'];
	if (isset($original['analytics'])) $defaults['analytics'] = $original['analytics'];
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
			$termlist[] .= '<a href="edit.php?'.$type.'content_audit='.$term->slug.'">'.$term->name.'</a>';
		}
		if (!empty($termlist)) echo implode(', ', $termlist);

		if (isset($_GET['mode']) && ($_GET['mode'] == 'excerpt'))
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
	
	if (isset($options['types'][$type]) && $options['types'][$type] == '1') {
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
	
	if (isset($options['types'][$type]) && $options['types'][$type] == '1') {
		wp_dropdown_users(
			array(
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
	wp_dropdown_users(
		array(
			'who' => 'authors',
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
		global $post;
		$owner = get_post_meta($post->ID, '_content_audit_owner', true);		
		?>
	<fieldset class="inline-edit-col-right">
	    <div class="inline-edit-col">
		<label class="inline-edit-status alignleft">
			<span class="title"><?php _e("Content Owner", 'content-audit'); ?></span>
			<?php
			
			wp_dropdown_users(
				array(
					'who' => 'authors',
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
	if (!empty($options['display_switch']) && (current_user_can($options['roles']))) {
		$out = content_audit_notes(false);		
		if ($options['display'] == 'above') return $out.$content;
		elseif ($options['display'] == 'below') return $content.$out;
		else return $content;
	}
	else return $content;
}

add_filter('the_content', 'content_audit_front_end_display');

// template tag: content_audit_notes($echo);
function content_audit_notes($echo = true) {
	global $post;
	$out = '<p class="content-status">'.get_the_term_list($post->ID, 'content_audit', __('Content status: ', 'content-audit'), ', ','').'</p>';
	$ownerID = get_post_meta($post->ID, "_content_audit_owner", true);
	if (!empty($ownerID)) {
		$out .= '<p class="content-owner">'.__("Assigned to: ", 'content-audit').get_the_author_meta('display_name', $ownerID).'</p>';
	}
	$out .= '<p class="content-notes">'.get_post_meta($post->ID, "_content_audit_notes", true).'</p>';
	$out = '<div class="content-audit">'.$out.'</div>';
	if ($echo) echo $out;
	else return $out;	
}

// Prints the CSS for the front end
function content_audit_front_end_css() {
	$options = get_option('content_audit');
	if (!empty($options['display']) && (current_user_can($options['roles']))) {	
		echo '<style type="text/css">'.$options['css'].'</style>';
	}
}
add_action('wp_head', 'content_audit_front_end_css');

// Dashboard Widget
function content_audit_dashboard_widget() {
	$options = get_option('content_audit');
	foreach ($options['types'] as $type => $val) {
		if ($val == '1') {
			$oldposts = get_posts('numberposts=5&post_type='.$type.'&content_audit=outdated&order=ASC&orderby=modified');
			$obj = get_post_type_object( $type );
			echo '<table class="widefat fixed" id="content-audit-outdated"><thead><tr><th>'.$obj->label.'</th><th  class="column-date">'.__('Last Modified', 'content-audit').'</th></tr></thead><tbody>';
			foreach ($oldposts as $apost) {
				echo '<tr class="author-self"><td class="column-title"><a href="'.get_permalink($apost->ID).'">'.$apost->post_title.'</a></td>';
				echo '<td class="column-date">'. mysql2date(get_option('date_format'), $apost->post_modified).'</td></tr>';
			}
			echo '<tr><td class="column-title" colspan="2"><a href="edit.php?post_type='.$type.'&content_audit=outdated">'.__('See all...', 'content-audit').'</a></td></tr></tbody></table>';
		}
	}
}

function content_audit_dashboard_widget_setup() {
    wp_add_dashboard_widget( 'dashboard_audit_widget_id', __('Outdated Content', 'content-audit'), 'content_audit_dashboard_widget');
}

add_action('wp_dashboard_setup', 'content_audit_dashboard_widget_setup');

?>
