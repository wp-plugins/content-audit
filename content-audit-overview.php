<?php
// displays the overview page content
function content_audit_overview() { ?>
	<div class="wrap">
	<?php 
	$options = get_option('content_audit');
	$printsquares = '';
	$types = array();
	// get post types we're auditing
	$cpts = get_post_types(array( 'public' => true ), 'objects');
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
		$i = 0;
	    
	    foreach ( $terms as $term ) {
			$i++;
			if ($i == $count) $margin = 0;
	    	$printsquares .= '<li style="width: '.$width.'%; margin-right: '.$margin.'%;"><a href="'.admin_url('index.php?page=content-audit#'.$term->slug).'"><h3>' . $term->count . '</h3><p>' . $term->name . '</p></a></li>';
	
			if ($term->count > 0) {
				// then print a table where each row contains an owner/author, 
				// with a column for each content type containing the count for each of their assigned items
				$tables[$term->slug] = '<h3 id="'.$term->slug.'">'. $term->name .'</h3>';
				$tables[$term->slug] .= '<table class="wp-list-table widefat fixed boss-view" cellspacing="0">';
				$tables[$term->slug] .= "<thead> \n <tr> \n <th>". __("Content Owner"). '</th>';
				foreach ($types as $label) { 
					$tables[$term->slug] .= '<th>'. $label .'</th>';
				}
				$tables[$term->slug] .= "\n </tr> \n </thead> \n <tbody> \n";
				
				$j = 0;
				$posts = get_content_audit_posts($term->slug, array_keys($types));
				foreach ($posts as $post) {
					$owner = get_post_meta($post->ID, '_content_audit_owner', true);
					$param = '_content_owner';
					if (empty($owner)) {
						$owner = $post->post_author;
						$param = 'author';
					}
					$userinfo = get_userdata($owner);
					
					if ($j & 1) 
						$class = ' class="alternate"'; 
					else 
						$class = '';
						
					$tables[$term->slug] .= '<tr'. $class .'><td>'. $userinfo->display_name .'</td>';
				
					foreach ($types as $type => $label) { 
						if ($type == 'attachment')
							$url = admin_url('upload.php');
						else
							$url = admin_url('edit.php?post_type='.$type);
						$posts_of_type = get_content_audit_posts($term->slug, $type);

						foreach ($posts_of_type as $key => $thispost) {
							$skipkeys = array();
							$content_owner = get_post_meta($thispost->ID, '_content_audit_owner', true);
							
							// don't count posts against an original author when the content owner is set
							// ... and don't count posts where this user is neither author nor content owner
							if ( ( !empty($content_owner) && (int)$content_owner != (int)$owner ) ||
								( empty($content_owner) && (int)$thispost->post_author != (int)$owner ) )
								$skipkeys[$thispost->ID] = $key;
						}
						foreach ($skipkeys as $id => $skipkey)
							unset($posts_of_type[$skipkey]);

						$num = count($posts_of_type);
						$tables[$term->slug] .= '<td><a href="'. $url .'&'. $param .'='. $owner .'&content_audit='. $term->slug. '">'. $num .'</a></td>';
					} // foreach type
					$tables[$term->slug] .= '</tr>';
			
					$j++;
				}  // foreach post
				$tables[$term->slug] .= '</tbody></table>';
			} // if $term->count > 0
	    }  // foreach term
	
		echo '<ul id="boss-squares">'.$printsquares.'</ul>';
/*
		if (isset($_GET['audit']) && term_exists($_GET['audit'], 'content_audit'))
			echo $tables[$_GET['audit']];
		else
/**/
			echo implode('', $tables);			
	} // if $count > 0
	
	echo '</div> <!-- .wrap -->'; 
}

function get_content_audit_posts($term, $types = 'page', $status = 'publish') {
	$args = array(
		'post_type' => $types,
		'post_status' => $status,
		'tax_query' => array(
			array(
				'taxonomy' => 'content_audit',
				'field' => 'slug',
				'terms' => $term
			)
		)
	);
	return get_posts( $args );
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
    $r = $wpdb->get_results( $query ); 
	return $r;
}
?>