jQuery(document).ready(function($){
    if( $('.emi_fu_trigger').length < 0 )
	return;
    var html  = "<div style='display:none'>";
	html +=	    "<form id='frm_emi_fu_uploader' method='POST' enctype='multipart/form-data' action='"+emi_fu.ajaxurl+"'>";
	html +=		"<input type='hidden' name='nonce'  value='"+ emi_fu.nonce  +"' />";
	html +=		"<input type='hidden' name='action' value='"+ emi_fu.action +"' />";
	html +=		"<input type='submit' />"
	html +=	    "</form>";
	html += "</div>";
    
    $('body').append(html);
    
    $('.emi_fu_trigger').click(function(){
	if( emi_fu.doingajax===true ){
	    alert('Please wait, another upload has not finished yet!');
	    return false;
	}
	emi_fu.trigger = $(this);
	$('#frm_emi_fu_uploader').append("<input type='file' name='fl_emi_fu' />");
	$('input[name="fl_emi_fu"]').change(function(){
	    emi_fu_file_selected();
	});
	$('input[name="fl_emi_fu"]').trigger('click');
	return false;
    });
    
    function emi_fu_file_selected(){
	//you can bind your code to following event to change the style of button while upload is in progress,
	//or display some message
	jQuery( emi_fu.trigger ).trigger('uploadstarted');
	
	//clear all 'additional' fields
	var form = jQuery('#frm_emi_fu_uploader');
	jQuery(form).find('input.additional').remove();
	
	//now add all 'additional' fields
	var data = jQuery( emi_fu.trigger ).data();
	for (var key in data) {
	    if (data.hasOwnProperty(key)) {
		jQuery(form).append( "<input class='additional' type='hidden' name='" + key + "' value='"+ data[key] +"' />" );
	    }
	}
	
	emi_fu.doingajax = true;
	jQuery(form).submit();
    }
    
    var emi_fu_ajaxform_options = { 
	dataType : 'json',
	success:    function(response) { 
	    emi_fu.doingajax = false;
	    $('#frm_emi_fu_uploader').find('input[type="file"]').remove();
	    console.log( response );
	    /*
	     * You should bind your code to following event to process the result of upload 
	     * and to dislay sucess/error messages.
	     * 
	     * The 'response' object needn't be passed through jQuery.pareseJSON as jquery-form already does that.
	     * Based on whether upload was successful or not, 
	     * the structure of 'response' object would be either of the following:
	     * 
	     * 1. Successfully uploaded
	     *	    {
	     *		status : true,
	     *		message : {
	     *		    url : 'htp://domain.com/wp-content/uploads/../../file.png'
	     *		    id	: 45//if attachment was created
	     *		}
	     *	    }
	     *	    
	     * 2. Upload failed
	     *	    {
	     *		status : false,
	     *		message : 'the error message'
	     *	    }
	     */
	    jQuery( emi_fu.trigger ).trigger('uploadfinished', response);
	},
	error: function( jqXHR, textStatus, errorThrown ){
	    emi_fu.doingajax = true;
	    $('#frm_emi_fu_uploader').find('input[type="file"]').remove();
	    jQuery( emi_fu.trigger ).trigger('ajaxrequesterror', [jqXHR, textStatus, errorThrown] );
	}
    };
    $('#frm_emi_fu_uploader').ajaxForm(emi_fu_ajaxform_options);
});