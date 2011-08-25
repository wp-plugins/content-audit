<?php
// displays the overview page content
function content_audit_overview() { ?>
	<div class="wrap">
	<form method="post" id="content_audit_form" action="options.php">
	<?php 
	$options = get_option('content_audit');
	// get post types we're auditing
	$types = array();
	$termlist = '';
	$args=array(
		'public'   => true,
	);
	$cpts = get_post_types($args, 'objects');
	foreach ($cpts as $cpt) {
		if (isset($options['types'][$cpt->name]) && $options['types'][$cpt->name] == 1)
			$types[$cpt->name] = $cpt->label;
	}
	?>

    <h2><?php _e( 'Content Audit Overview', 'content-audit'); ?></h2>

	<?php
	// for each term in the audit taxonomy, print a box with a big number for the count
	$terms = get_terms( 'content_audit', array('hide_empty' => 0) );
	$count = count($terms);
	if ( $count > 0 ){
		// doing some math to space the boxes out evenly...
		$squares = $count + 1; 
		$width = 100 / $squares;
		$margin = 100 / ( $count * $squares );
		$i = 1;
	    echo '<ul id="boss-squares">';
	    foreach ( $terms as $term ) {
			if ($i == $count)
				$margin = 0;
	    	echo '<li style="width: '.$width.'%; margin-right: '.$margin.'%;"><a href="'.admin_url('index.php?page=content-audit&audit='.$term->slug).'"><h3>' . $term->count . '</h3><p>' . $term->name . '</p></a></li>';
			$i++;
			$termlist .= ','.$term->slug;
	    }
	    echo "</ul>";
	}
	
	// then print a table where each row contains an owner/author, with a column for each content type containing the count for each of their assigned items
	// we'll start with the first term unless they've already chosen something else
	if (!isset($_GET['audit']) || !term_exists($_GET['audit'], 'content_audit'))
		$audit = $terms[0];
	else $audit = get_term_by('slug', $_GET['audit'], 'content_audit');
	?>
	<h2><?php echo $audit->name; ?></h2>
	<table class="wp-list-table widefat fixed boss-view" cellspacing="0">
	<thead>
		<tr>
			<th><?php _e("Content Owner"); ?></th>
			<?php
			foreach ($types as $label) { ?>
				<th><?php echo $label; ?></th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php
		$i = 0;
		$typelist = implode("','", array_keys($types));
		// foreach content owner
		$owners = get_content_audit_meta_values( '_content_audit_owner', $typelist );
		foreach ($owners as $owner) {
				$userinfo = get_userdata($owner);
				$name = $userinfo->display_name;
				if ($name == $userinfo->user_login)
					$name = $userinfo->user_firstname .' '. $userinfo->user_lastname;
				if ($i & 1) $class = ' class="alternate"'; else $class = '';
		?>
		<tr<?php echo $class; ?>>
			<td><?php echo $name; ?></td>
			<?php
			foreach ($types as $type => $label) { 
				$args = array(
				    'post_type' => $type,
				    'post_status' => 'publish',
					'content_audit' => $audit->slug,
					'meta_key'  => '_content_audit_owner',
				    'meta_value' => $owner,
				);
				$num = count( get_posts( $args ) );
				if ($type == 'attachment')
					$url = admin_url('upload.php');
				else
					$url = admin_url('edit.php?post_type='.$type);
				?>
				<td><?php echo '<a href="'.$url.'&content_owner='.$owner.'&content_audit='.$audit->slug.'">'.$num.'</a>'; ?></td>
			<?php } ?>
		</tr>
		<?php
		$i++;
		}
		?>
	</tbody>
	</table>
	</div>
<?php 
}

function get_content_audit_meta_values( $key = '', $types = 'page', $status = 'publish' ) {
    global $wpdb;
	$query = $wpdb->prepare( "
        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s' 
        AND p.post_status = '%s' 
        AND p.post_type IN ('@FOO@')
		AND pm.meta_value > 0
    ", $key, $status);
	// can't let prepare() handle this because it escapes the single quotes in between each post type, ARGH
	$query = str_replace('@FOO@', $types, $query);
    $r = $wpdb->get_col( $query ); 
	return $r;
}
?>