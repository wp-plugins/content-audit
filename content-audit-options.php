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
	    <th scope="row"><?php _e("Content Types to Audit"); ?></th>
		    <td>
			    <ul id="content_audit_types">
			    <?php
			    $content_types = get_post_types('', 'objects');
			    $ignored = array('attachment', 'revision', 'nav_menu_item');
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
<!--		  	
		<tr>
	    <th scope="row"><?php _e("Outdated content"); ?></th>
		    <td>
			    <label><?php _e("Automatically mark content as outdated if the last revision is older than:"); ?></label>
			    <select name="content_audit[outdate]">
			    	<option value="3" <?php selected(3, $options['outdate']); ?>><?php _e("3 months"); ?></option>
			    	<option value="6" <?php selected(6, $options['outdate']); ?>><?php _e("6 months"); ?></option>
			    	<option value="9" <?php selected(9, $options['outdate']); ?>><?php _e("9 months"); ?></option>
			    	<option value="12" <?php selected(12, $options['outdate']); ?>><?php _e("1 year"); ?></option>
			    </select>
		    </td>
	    </tr>
-->	
		<tr>
	    <th scope="row"><?php _e("Content Attributes"); ?></th>
		    <td>
			    <a href="edit-tags.php?taxonomy=audit"><?php _e("Edit content audit fields"); ?></a>
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