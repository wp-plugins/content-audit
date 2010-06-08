<?php
// displays the options page content
function content_audit_options() { ?>	
    <div class="wrap">
	<form method="post" id="content_audit_form" action="options.php">
		<?php settings_fields('content_audit');
		$options = get_option('content_audit'); ?>

    <h2><?php _e( 'Content Audit Options', 'content-audit'); ?></h2>
    
    <table class="form-table">
	    <tr>
	    <th scope="row"><?php _e("Audited content types", 'content-audit'); ?></th>
		    <td>
			    <ul id="content_audit_types">
			    <?php
			    $content_types = get_post_types('', 'objects');
			    $ignored = array('revision', 'nav_menu_item');
			    foreach ($content_types as $content_type) {
			    	if (!in_array($content_type->name, $ignored)) { ?>
			    		<li>
			    		<label>
			    		<input type="checkbox" name="content_audit[types][<?php echo $content_type->name; ?>]" value="1" <?php checked('1', $options['types'][$content_type->name]); ?> />
			    		<?php echo $content_type->label; ?></label>
			    		</li>
			    	<?php }
			    }
			    ?>
			    </ul>
		    </td>
	    </tr>

	    <tr>
	    <th scope="row"><?php _e("Users allowed to audit", 'content-audit'); ?></th>
		    <td>
			<select name="content_audit[roles]" id="content_audit[roles]">
				<option value="edit_dashboard" <?php selected('edit_dashboard', $options['roles']); ?>><?php _e('Administrators', 'content-audit'); ?></option>
				<option value="edit_pages" <?php selected('edit_pages', $options['roles']); ?>><?php _e('Editors', 'content-audit'); ?></option>
				<option value="publish_posts" <?php selected('publish_posts', $options['roles']); ?>><?php _e('Authors', 'content-audit'); ?></option>
				<option value="edit_posts" <?php selected('edit_posts', $options['roles']); ?>><?php _e('Contributors', 'content-audit'); ?></option>
				<option value="read" <?php selected('read', $options['roles']); ?>><?php _e('Subscribers', 'content-audit'); ?></option>
			</select>
		    </td>
	    </tr>

<!--		  	
		<tr>
	    <th scope="row"><?php _e("Outdated content", 'content-audit'); ?></th>
		    <td>
			    <label><?php _e("Automatically mark content as outdated if the last revision is older than:", 'content-audit'); ?></label>
			    <select name="content_audit[outdate]">
			    	<option value="3" <?php selected(3, $options['outdate']); ?>><?php _e("3 months", 'content-audit'); ?></option>
			    	<option value="6" <?php selected(6, $options['outdate']); ?>><?php _e("6 months", 'content-audit'); ?></option>
			    	<option value="9" <?php selected(9, $options['outdate']); ?>><?php _e("9 months", 'content-audit'); ?></option>
			    	<option value="12" <?php selected(12, $options['outdate']); ?>><?php _e("1 year", 'content-audit'); ?></option>
			    </select>
		    </td>
	    </tr>
-->	
		<tr>
	    <th scope="row"><?php _e("Front end display"); ?></th>
		    <td>
			    <?php _e('Display content status, notes, and owner to logged-in auditors ', 'content-audit'); ?>
				<select name="content_audit[display]">
					<option value="0" <?php selected(0, $options['display']); ?>><?php _e("nowhere"); ?></option>
					<option value="above" <?php selected('above', $options['display']); ?>><?php _e("above content"); ?></option>
					<option value="below" <?php selected('below', $options['display']); ?>><?php _e("below content"); ?></option>
				</select>
				<?php _e("."); ?>
		    </td>
	    </tr>
		<tr>
			<th scope="row"><?php _e("CSS for front end display", 'content-audit'); ?></th>
		    <td>
				<textarea name="content_audit[css]"><?php echo $options['css']; ?></textarea>
		    </td>
	    </tr>

	
		<tr>
	    <th scope="row"><?php _e('Content status labels', 'content-audit'); ?></th>
		    <td>
			    <a href="edit-tags.php?taxonomy=content_audit"><?php _e('Edit content audit status labels', 'content-audit'); ?></a>
		    </td>
	    </tr>
    </table>
    
	<p class="submit">
	<input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options', 'content-audit'); ?>" />
	</p>
	</form>
	</div>
<?php 
} // end function content_audit_options() 
?>