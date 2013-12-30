<?php

/*
  Plugin name: Ajax File Upload Component
  Plugin URI: http://pross.org.uk/plugins
  Description: Provides an ajax-file-upload resuable component
  version: 0.2
  Author: ckchaudhary
  Author URI: http://emediaidentity.com/
 */

/*
 * How To Use:
 * 
 * 1) Output the file upload component
 * 	The file upload component can be used by using the shortcode [emi_fu_component] anywhere inside your own form/page/post
 * 	Check the function emi_fu_component_shortcode() to see different options for the shortcode.
 * 	A sample use:
 * 	----------------
 * 	<form method='post'>
 * 	    <p><label>Enter your name:<input type='text' name='txt_name' /></label></p>
 * 	    <p><label>Enter your age:<input type='text' name='txt_age' /></label></p>
 * 	    <p><label>Upload your photo:</label> Only png/jpg/gif images upto 2MB in size<br/>
 * 		<?php echo do_shortcode( '[emi_fu_component class="user-dp" mime_types="image/jpeg,image/png,image/gif" attachment="yes"]' );?>
 * 	    </p>
 * 	    <p><label>Upload your resume:</label> Only pdf file upto 2MB in size<br/>
 * 		<?php echo do_shortcode( '[emi_fu_component class="user-resume" mime_types="application/pdf"]' );?>
 * 	    </p>
 * 	    <p><input type='submit' /></p>
 * 	</form>
 * 	-----------------
 * 	The above form will have 2 upload buttons. First one will upload images(jpeg, png, gif),
 * 	while the second one will only upload pdf file.
 * 
 * 2) Process the file upload ajax response
 * 	The pluing by itself does nothing with the uploaded file. 
 * 	You have to bind to 'uploadfinished' event of the upload buttons by yourself to process the file upload response.
 * 	Check the comments in the javascript file for details.
 * 
 * 	Example (in context of the sample use above):
 * 	-----------------
 * 	jQuery(document).ready(function($){
 * 	    //clear up previous image/resume details and display uploading message 
 * 	    //when user selects a file(or a new file)
 * 	    $( ".user-dp, .user-resume" ).on( "uploadstarted", function( event ){
 * 		$(this).next("div.response").html( "<p class='text-info'>uploading..</p>" );
 * 	    });
 * 
 * 	    $( ".user-dp" ).on( "uploadfinished", function( event, response ) {
 * 		if( response.status===true ){
 * 		    //dp uploaded successfuly
 * 		    var html  = "<img src='" + response.message.url + "' height='100' width='100' />";
 * 			html += "<p class='text-success'>Success!</p>";
 * 			html += "<inpu type='hidden' name='user_dp_attachment_id' value='"+ response.message.id +"'/ >";
 * 		    $(this).next("div.response").html( html );
 * 		}
 * 	    });
 * 
 * 	    $( ".user-resume" ).on( "uploadfinished", function( event, response ) {
 * 		if( response.status===true ){
 * 		    //dp uploaded successfuly
 * 		    var html  = "<p class='text-success'>Resume uploaded successfuly!</p>";
 * 			html += "<inpu type='hidden' name='user_resume_url' value='"+ response.message.url +"'/ >";
 * 		    $(this).next("div.response").html( html );
 * 		}
 * 	    });
 * 	});
 * 	-----------------
 */

/*
 * return the maximum size(in bytes) allowed for file upload
 * use the filter to increase/decrease the limit, default is 2MB
 * 
 * @return int : file size in bytes
 */

function emi_fu_max_upload_size() {
	return apply_filters('emi_fu_max_upload_size', 2097000);
}

/*
 * returns the mime types allowed to be uploaded
 * use the filter to add/remove to/from the list
 * 
 * check mime types for file extenstions here
 * http://webdesign.about.com/od/multimedia/a/mime-types-by-content-type.htm
 * 
 * @return array
 */

function emi_fu_type_whitelist() {
	$default_types = array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'application/pdf'
	);

	return apply_filters('emi_fu_type_whitelist', $default_types);
}

add_action('wp_enqueue_scripts', 'emi_fu_add_js');

function emi_fu_add_js() {
	wp_register_script('emi_fu', plugins_url('emi_fu.js', __FILE__), array('jquery', 'jquery-form'));
}

add_shortcode('emi_fu_component', 'emi_fu_component_shortcode');

function emi_fu_component_shortcode($atts) {
	/*
	 * Different options for the shortcode:
	 * [emi_fu_component]	
	 * 			- all mime types returned by emi_fu_type_whitelist() will be allowed to be uploaded
	 * 			- max file size allowed would be determined by emi_fu_max_upload_size()
	 * 			- only the file would be uploaded, 'attachment' wouldn't be created
	 * 
	 * [emi_fu_component class="resume_upload"]	
	 * 			- everything same as above,
	 * 			- the 'button' printed will have an extra css class of 'resume_upload'
	 * 
	 * [emi_fu_component attachment="yes"]	
	 * 			- all mime types returned by emi_fu_type_whitelist() will be allowed to be uploaded
	 * 			- max file size allowed would be determined by emi_fu_max_upload_size()
	 * 			- the file would be uploaded, and an 'attachment' would be created, response will contain the new attachment id
	 * 
	 * [emi_fu_component mime_types="application/pdf,application/msword" attachment="yes"]
	 * 			- only pdf and microsoft word documents would be uploaded, provided these are allowed in emi_fu_type_whitelist()
	 * 			- max file size allowed would be determined by emi_fu_max_upload_size()
	 * 			- the file would be uploaded, and an 'attachment' would be created, response will contain the new attachment id
	 */
	$args = shortcode_atts(array(
		'attachment' => 'no',
		'mime_types' => '',
		'class' => ''
			), $atts);

	if (!is_user_logged_in()) {
		//there's something wrong, non-loggedin user's shouldn't be allowed to upload files!
		return '';
	}

	wp_enqueue_script('jquery-form');
	wp_enqueue_script('emi_fu');
	$data = array(
		'ajaxurl' => admin_url('/admin-ajax.php'),
		'action' => 'emi_fu_upload_file',
		'nonce' => wp_create_nonce('emi_fu_nonce_key')
	);

	wp_localize_script('emi_fu', 'emi_fu', $data);

	$data_attributes = "";
	foreach ($args as $key => $val) {
		if ($val && $key != 'class') {
			$data_attributes .= " data-$key='" . esc_attr($val) . "'";
		}
	}

	$class_attr = 'emi_fu_trigger';
	if ($args['class']) {
		$class_attr .= ' ' . esc_attr($args['class']);
	}

	$html = "";
	$html .= "<div class='emi_fu_wrapper'>";
	$html .= "<button class='" . $class_attr . "' id='emi_fu_trigger-" . rand() . "' $data_attributes >Upload File</button>";
	$html .= "</div>";
	return $html;
}

add_action('wp_ajax_emi_fu_upload_file', 'emi_fu_ajax_upload_file');

function emi_fu_ajax_upload_file() {
	$retval = array('status' => 'false', 'message' => '');
	if (!wp_verify_nonce($_POST['nonce'], 'emi_fu_nonce_key')) {
		$retval['message'] = 'Invalid Request!';
		die(json_encode($retval));
	}

	/*
	 * shortcode [emi_fu_component mime_types="image/gif,application/pdf"] will ensure that given form uploads only pdf files and gif images
	 * provided the extension 'pdf' and 'gif' is there in constant EMIFU_TYPE_WHITELIST
	 * 
	 * likewise you can have multitple forms on the same page, one accepting only pdf files, ohter accepting only images, etc..
	 */
	$permissible_mime_types = emi_fu_type_whitelist();
	if (isset($_POST['mime_types']) && !empty($_POST['mime_types'])) {
		$desired_mime_types = explode(",", $_POST['mime_types']);
		$permissible_mime_types = array_intersect($desired_mime_types, $permissible_mime_types);
		if (empty($permissible_mime_types)) {
			$retval['message'] = 'Uploaded file type not allowed!';
			die(json_encode($retval));
		}
	}
	//validation
	$retval = emi_fu_parse_file_errors($_FILES['fl_emi_fu'], $permissible_mime_types);
	if ($retval['status'] == false) {
		die(json_encode($retval));
	}

	//all is good, lets hand it over to wordpress uploader
	$uploadedfile = $_FILES['fl_emi_fu'];
	$upload_overrides = array('test_form' => false);
	$movefile = wp_handle_upload($uploadedfile, $upload_overrides);
	if ($movefile) {
		$retval['status'] = true;
		$retval['message'] = array('url' => $movefile['url']);
		if (isset($_POST['attachment']) && $_POST['attachment'] == 'yes') {
			$attachment_id = emi_fu_create_attachment($movefile['file']);
			$retval['message']['id'] = $attachment_id;
		}
	} else {
		$retval['message'] = 'File could not be uploaded!';
	}
	die(json_encode($retval));
}

function emi_fu_parse_file_errors($file = '', $mime_types = '') {
	if (!$mime_types) {
		$mime_types = emi_fu_type_whitelist();
	}
	$result = array('status' => true, 'message' => '');

	if ($file['error']) {
		$result['status'] = false;
		$result['message'] = "No file uploaded or there was an upload error!";
		return $result;
	}

	$image_data = getimagesize($file['tmp_name']);
	$max_upload_size = emi_fu_max_upload_size();

	if (!in_array($image_data['mime'], $mime_types)) {
		$mime_types_csv = implode(", ", $mime_types);
		$result['message'] = 'The uploaded file must be either of the following : ' . $mime_types_csv . '!';
	} elseif (($file['size'] > $max_upload_size)) {
		$result['message'] = 'The uploaded file was ' . $file['size'] . ' bytes! It must not exceed ' . $max_upload_size . ' bytes.';
	}

	if ($result['message'] != '') {
		$result['status'] = false;
	}
	return $result;
}

function emi_fu_create_attachment($filename) {
	$wp_filetype = wp_check_filetype(basename($filename), null);
	$wp_upload_dir = wp_upload_dir();
	$attachment = array(
		'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment($attachment, $filename);
	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
	wp_update_attachment_metadata($attach_id, $attach_data);
	return $attach_id;
}
