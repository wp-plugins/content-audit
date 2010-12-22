<?php
function content_audit_report() { 
	global $wpdb;
	$options = get_option('content_audit');

	if ( !isset($_GET['post_type']) )
		$post_type = 'post';
	elseif ( in_array( $_GET['post_type'], get_post_types( array('public' => true ) ) ) )
		$post_type = $_GET['post_type'];
	else
		wp_die( __('Invalid post type') );
	$_GET['post_type'] = $post_type;

	$post_type_object = get_post_type_object($post_type);

	if ( !current_user_can($post_type_object->edit_type_cap) )
		wp_die(__('Cheatin&#8217; uh?'));

	// Back-compat for viewing comments of an entry
	if ( $_redirect = intval( max( @$_GET['p'], @$_GET['attachment_id'], @$_GET['page_id'] ) ) ) {
		wp_redirect( admin_url('edit-comments.php?p=' . $_redirect ) );
		exit;
	} else {
		unset( $_redirect );
	}

	if ( 'post' != $post_type ) {
		$parent_file = "edit.php?post_type=$post_type";
		$submenu_file = "edit.php?post_type=$post_type";
		$post_new_file = "post-new.php?post_type=$post_type";
	} else {
		$parent_file = 'edit.php';
		$submenu_file = 'edit.php';
		$post_new_file = 'post-new.php';
	}

	$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
	if ( empty($pagenum) )
		$pagenum = 1;
	$per_page = 'edit_' . $post_type . '_per_page';
	$per_page = (int) get_user_option( $per_page );
	if ( empty( $per_page ) || $per_page < 1 )
		$per_page = 15;
	// @todo filter based on type
	$per_page = apply_filters( 'edit_posts_per_page', $per_page );

	$title = sprintf(__('Audit %s'), $post_type_object->label);

	//wp_enqueue_script('inline-edit-post');

	$user_posts = false;
	if ( !current_user_can($post_type_object->edit_others_cap) ) {
		$user_posts_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(1) FROM $wpdb->posts WHERE post_type = '%s' AND post_status != 'trash' AND post_author = %d", $post_type, $current_user->ID) );
		$user_posts = true;
		if ( $user_posts_count && empty($_GET['post_status']) && empty($_GET['all_posts']) && empty($_GET['author']) )
			$_GET['author'] = $current_user->ID;
	}

	if ( $post_type_object->hierarchical )
		$num_pages = ceil($wp_query->post_count / $per_page);
	else
		$num_pages = $wp_query->max_num_pages;

	query_posts('post_type=' . $post_type . '&paged=' . get_query_var('paged'));
	?>

	<div class="wrap">
	<h2><?php _e("Content Audit Report"); ?></h2>

	<form id="content-audit" action="options.php" method="post">

	<ul class="subsubsub">
	
	<?php
	    $content_types = get_post_types('', 'objects');
	    $ignored = array('attachment', 'revision', 'nav_menu_item');
	    foreach ($content_types as $content_type) {
	    	if (!in_array($content_type->name, $ignored)) 
				$status_links[] .= '<li><a href="tools.php?page=content-audit-report&post_type='.$content_type->name.'">'.$content_type->label.' (#)</a></li>';
	    }
//	$status_links[] .= '<li><a href="#">Export &rarr;</a></li>';
	echo implode( " |</li>\n", $status_links ) . '</li>';
	unset( $status_links );
	echo $numpages.' '.$pagenum;
	?>
	</ul>

<?php if ( have_posts() ) : ?>

	<div class="tablenav">
	<?php
	$page_links = paginate_links( array(
		'base' => $_SERVER['REQUEST_URI'].'%_%',
		'format' => '?page=%#%',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $num_pages,
		'current' => $pagenum
	));
	?>

	<div class="alignleft actions">
	<?php // view filters
		if ( !is_singular() ) {
		?>
		<select name='filter_status'>
		<option<?php selected( $filter_status, 0 ); ?> value='0'><?php _e('Limit to Status...'); ?></option>
		<option<?php selected( $filter_status, 'redundant' ); ?> value='redundant'><?php _e('Redundant'); ?></option>
		<option<?php selected( $filter_status, 'outdated' ); ?> value='outdated'><?php _e('Outdated'); ?></option>
		<option<?php selected( $filter_status, 'trivial' ); ?> value='trivial'><?php _e('Trivial'); ?></option>
		<option<?php selected( $filter_status, 'seo' ); ?> value='seo'><?php _e('SEO'); ?></option>
		<option<?php selected( $filter_status, 'style' ); ?> value='style'><?php _e('Style'); ?></option>
		</select>
	<?php } ?>

	<?php
/*	if ( is_object_in_taxonomy($post_type, 'category') ) {
		$dropdown_options = array('show_option_all' => __('View all categories'), 'hide_empty' => 0, 'hierarchical' => 1,
			'show_count' => 0, 'orderby' => 'name', 'selected' => $cat);
		wp_dropdown_categories($dropdown_options);
	}
*/	do_action('restrict_manage_posts');
	?>
	<input type="submit" id="post-query-submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />
	</div>

	<?php if ( $page_links ) { ?>
	<div class="tablenav-pages"><?php
		$count_posts = $post_type_object->hierarchical ? $wp_query->post_count : $wp_query->found_posts;
		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
							number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
							number_format_i18n( min( $pagenum * $per_page, $count_posts ) ),
							number_format_i18n( $count_posts ),
							$page_links
							);
		echo $page_links_text;
		?></div>
	<?php } ?>

	<div class="view-switch">
		<a href="<?php echo esc_url(add_query_arg('mode', 'list', $_SERVER['REQUEST_URI'])) ?>"><img <?php if ( 'list' == $mode ) echo 'class="current"'; ?> id="view-switch-list" src="<?php echo esc_url( includes_url( 'images/blank.gif' ) ); ?>" width="20" height="20" title="<?php _e('List View') ?>" alt="<?php _e('List View') ?>" /></a>
		<a href="<?php echo esc_url(add_query_arg('mode', 'excerpt', $_SERVER['REQUEST_URI'])) ?>"><img <?php if ( 'excerpt' == $mode ) echo 'class="current"'; ?> id="view-switch-excerpt" src="<?php echo esc_url( includes_url( 'images/blank.gif' ) ); ?>" width="20" height="20" title="<?php _e('Excerpt View') ?>" alt="<?php _e('Excerpt View') ?>" /></a>
	</div>

	<div class="clear"></div>
	</div>

	<div class="clear"></div>

	<?php $atts = explode("\n", $options['atts']); ?>

	<table class="widefat <?php echo $post_type; ?> fixed content-audit" cellspacing="0">
		<thead>
		<tr>
			<th class="manage-column column-cb"><?php _e("ID"); ?></th>
			<th class="manage-column column-title"><?php _e("Title"); ?></th>
			<th class="manage-column column-author"><?php _e("Author"); ?></th>
			<th class="manage-column column-redundant"><?php _e("Redundant"); ?></th>
			<th class="manage-column column-outdated"><?php _e("Outdated"); ?></th>
			<th class="manage-column column-trivial"><?php _e("Trivial"); ?></th>
			<th class="manage-column column-seo"><?php _e("SEO"); ?></th>
			<th class="manage-column column-style"><?php _e("Style"); ?></th>
			<th class="manage-column column-date"><?php _e("Date"); ?></th>
		</tr>
		</thead>

		<tfoot>
		<tr>
			<th class="manage-column column-cb"><?php _e("ID"); ?></th>
			<th class="manage-column column-title"><?php _e("Title"); ?></th>
			<th class="manage-column column-author"><?php _e("Author"); ?></th>
			<th class="manage-column column-redundant"><?php _e("Redundant"); ?></th>
			<th class="manage-column column-outdated"><?php _e("Outdated"); ?></th>
			<th class="manage-column column-trivial"><?php _e("Trivial"); ?></th>
			<th class="manage-column column-seo"><?php _e("SEO"); ?></th>
			<th class="manage-column column-style"><?php _e("Style"); ?></th>
			<th class="manage-column column-date"><?php _e("Date"); ?></th>
		</tr>
		</tfoot>

		<tbody>
			<?php content_audit_report_rows(); ?>
		</tbody>
	</table>

	<div class="tablenav">

	<?php
	if ( $page_links )
		echo "<div class='tablenav-pages'>$page_links_text</div>";
	?>

	<div class="alignleft actions">
	<select name='filter_status2'>
		<option<?php selected( $filter_status2, 0 ); ?> value='0'><?php _e('Limit to Status...'); ?></option>
		<option<?php selected( $filter_status2, 'redundant' ); ?> value='redundant'><?php _e('Redundant'); ?></option>
		<option<?php selected( $filter_status2, 'outdated' ); ?> value='outdated'><?php _e('Outdated'); ?></option>
		<option<?php selected( $filter_status2, 'trivial' ); ?> value='trivial'><?php _e('Trivial'); ?></option>
		<option<?php selected( $filter_status2, 'seo' ); ?> value='seo'><?php _e('SEO'); ?></option>
		<option<?php selected( $filter_status2, 'style' ); ?> value='style'><?php _e('Style'); ?></option>
	</select>
	<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
	<br class="clear" />
	</div>
	<br class="clear" />
	</div>

	<?php else : // have_posts() ?>
		<div class="clear"></div>
		<p><?php
		if ( isset($_GET['post_status']) && 'trash' == $_GET['post_status'] )
			printf( __( 'No %s found in the Trash.' ), $post_type_object->label );
		else
			printf( __( 'No %s found.' ), $post_type_object->label );
		?></p>
	<?php endif; // else no posts ?>

	</form>

	<?php //inline_edit_row( $current_screen ); ?>

	<br class="clear" />
	</div>

<?php
/**/
} // function content_audit_report()

// based on post_rows in wp-admin/includes/template.php
function content_audit_report_rows( $posts = array() ) {
	global $wp_query, $post, $mode;

	add_filter('the_title','esc_html');

	// Create array of post IDs.
	$post_ids = array();

	if ( empty($posts) )
		$posts = &$wp_query->posts;

	foreach ( $posts as $a_post )
		$post_ids[] = $a_post->ID;

	foreach ( $posts as $post )
		content_audit_table_row($post, $mode);
}

// based on _post_row in wp-admin/includes/template.php
function content_audit_table_row($a_post, $mode) {
	global $post, $current_user, $current_screen, $wpdb;
	static $rowclass;

	$global_post = $post;
	$post = $a_post;
	setup_postdata($post);

	$fields = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'content_audit WHERE ID = '.$post->ID, ARRAY_A);
	$fields = maybe_unserialize($fields);
	$options = get_option('content_audit'); 

	$rowclass = 'alternate' == $rowclass ? '' : 'alternate';
	$post_owner = ( $current_user->ID == $post->post_author ? 'self' : 'other' );
	$edit_link = get_edit_post_link( $post->ID );
	$title = _draft_or_post_title();
	$post_type_object = get_post_type_object($post->post_type);
?>
	<tr id='post-<?php echo $post->ID; ?>' class='<?php echo trim( $rowclass . ' author-' . $post_owner . ' status-' . $post->post_status ); ?> iedit' valign="top">
		<th scope="row" class="manage-column column-cb check-column"><?php echo $post->ID; ?></th>
		
			<?php
			// title
				$attributes = 'class="manage-column column-title"' . $style;
			?>
			<td <?php echo $attributes ?>><strong><?php if ( current_user_can($post_type_object->edit_cap, $post->ID) && $post->post_status != 'trash' ) { ?><a class="row-title" href="<?php echo $edit_link; ?>" title="<?php echo esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $title)); ?>"><?php echo $title ?></a><?php } else { echo $title; }; _post_states($post); ?></strong>
			<?php
				if ( 'excerpt' == $mode ) {
					_e("Excerpt: ");
					the_excerpt();
					_e("Notes:");
					wp_specialchars(stripslashes(get_post_meta($post->ID, 'content_audit[notes]', true)), 1);
				}

				$actions = array();
				if ( current_user_can($post_type_object->edit_cap, $post->ID) && 'trash' != $post->post_status ) {
					$actions['edit'] = '<a href="' . get_edit_post_link($post->ID, true) . '" title="' . esc_attr(__('Edit this post')) . '">' . __('Edit') . '</a>';
					$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr(__('Edit this post inline')) . '">' . __('Quick&nbsp;Edit') . '</a>';
				}
				if ( current_user_can($post_type_object->delete_cap, $post->ID) ) {
					if ( 'trash' == $post->post_status )
						$actions['untrash'] = "<a title='" . esc_attr(__('Restore this post from the Trash')) . "' href='" . wp_nonce_url( admin_url( sprintf($post_type_object->_edit_link . '&amp;action=untrash', $post->ID) ), 'untrash-' . $post->post_type . '_' . $post->ID ) . "'>" . __('Restore') . "</a>";
					elseif ( EMPTY_TRASH_DAYS )
						$actions['trash'] = "<a class='submitdelete' title='" . esc_attr(__('Move this post to the Trash')) . "' href='" . get_delete_post_link($post->ID) . "'>" . __('Trash') . "</a>";
					if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
						$actions['delete'] = "<a class='submitdelete' title='" . esc_attr(__('Delete this post permanently')) . "' href='" . wp_nonce_url( admin_url( sprintf($post_type_object->_edit_link . '&amp;action=delete', $post->ID) ), 'delete-' . $post->post_type . '_' . $post->ID ) . "'>" . __('Delete Permanently') . "</a>";
				}
				if ( in_array($post->post_status, array('pending', 'draft')) ) {
					if ( current_user_can($post_type_object->edit_cap, $post->ID) )
						$actions['view'] = '<a href="' . add_query_arg( 'preview', 'true', get_permalink($post->ID) ) . '" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $title)) . '" rel="permalink">' . __('Preview') . '</a>';
				} elseif ( 'trash' != $post->post_status ) {
					$actions['view'] = '<a href="' . get_permalink($post->ID) . '" title="' . esc_attr(sprintf(__('View &#8220;%s&#8221;'), $title)) . '" rel="permalink">' . __('View') . '</a>';
				}
				$actions = apply_filters('post_row_actions', $actions, $post);
				$action_count = count($actions);
				$i = 0;
				echo '<div class="row-actions">';
				foreach ( $actions as $action => $link ) {
					++$i;
					( $i == $action_count ) ? $sep = '' : $sep = ' | ';
					echo "<span class='$action'>$link$sep</span>";
				}
				echo '</div>';

				get_inline_data($post);
			?>
			</td><!-- .title -->
			
			<td class="manage-column column-author"><a href="edit.php?author=<?php the_author_meta('ID'); ?>"><?php the_author() ?></a></td>
			
			<td class="redundant">
				<input type="checkbox" name="content_audit[redundant]" value="1" <?php checked('1', $fields['redundant']); ?> />
			</td>
			<td class="outdated">
				<input type="checkbox" name="content_audit[outdated]" value="1" <?php if (($fields['outdated'] == '1') || ($post->post_modified < strtotime('-'.$options['outdate'].' months'))) { ?>
					 	checked="checked" <?php } ?> />
			</td>
			<td class="trivial">
				<input type="checkbox" name="content_audit[trivial]" value="1" <?php checked('1', $fields['trivial']); ?> />
			</td>
			<td class="seo">
				<input type="checkbox" name="content_audit[seo]" value="1" <?php checked('1', $fields['seo']); ?> />
			</td>
			<td class="style">
				<input type="checkbox" name="content_audit[style]" value="1" <?php checked('1', $fields['style']); ?> />
			</td>
			
		<?php
		// date
			if ( '0000-00-00 00:00:00' == $post->post_date && 'date' == $column_name ) {
				$t_time = $h_time = __('Unpublished');
				$time_diff = 0;
			} else {
				$t_time = get_the_time(__('Y/m/d g:i:s A'));
				$m_time = $post->post_date;
				$time = get_post_time('G', true, $post);

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
				else
					$h_time = mysql2date(__('Y/m/d'), $m_time);
			}

			echo '<td class="manage-column column-date">';
			if ( 'excerpt' == $mode )
				echo apply_filters('post_date_column_time', $t_time, $post, $column_name, $mode);
			else
				echo '<abbr title="' . $t_time . '">' . apply_filters('post_date_column_time', $h_time, $post, $column_name, $mode) . '</abbr>';
			echo '<br />';
			if ( 'publish' == $post->post_status ) {
				_e('Published');
			} elseif ( 'future' == $post->post_status ) {
				if ( $time_diff > 0 )
					echo '<strong class="attention">' . __('Missed schedule') . '</strong>';
				else
					_e('Scheduled');
			} else {
				_e('Last Modified');
			}
			echo '</td>';
		
		?>
		</td><!-- .date -->
	</tr>
<?php
	$post = $global_post;
}

?>
