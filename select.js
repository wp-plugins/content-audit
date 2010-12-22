// http://www.queness.com/post/328/a-simple-ajax-driven-website-with-jqueryphp
//jQuery(document).ready(getOwner());

function getOwner() {  
// 	var postid = jQuery('#_content_audit_owner').parents('tr').attr('id').replace('page-',''); // will be post- for posts. Split on hyphen instead?
var postid = 4274;    
var data = 'post='+postid;  
    jQuery.ajax({  
        url: "/wp-content/plugins/content-audit/content-audit.php?select=1", 
        type: "GET",          
        data: data,       
        cache: false,  
        success: function (ownerID) {    
			jQuery('#_content_audit_owner').val(ownerID);
         }         
     });  
 }  