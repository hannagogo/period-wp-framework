<?php

class ArchivePageParts extends ClassTemplate {
var $param = array();
public function __construct() {
 add_action( 'admin_menu', array(&$this, 'add_menu') );
}
public function add_menu() {
 global $userdata, $custom_language_domain;
 $this->param('edit_page_suffix', 'edit_post_type_archive_parts');
 $post_types = get_post_types();
 $capability = 'install_plugins'; //get the required user capability
 foreach( $post_types as $post_type_name ) {
  if ($post_type_name == 'reply' || $post_type_name == 'topic') continue; //ignore bbpress
  add_submenu_page(
   'edit.php'.($post_type_name == 'post'? '' : '?post_type='.$post_type_name),
   __('Edit Archive Page Parts', $custom_language_domain),
   __('Archive Parts', $custom_language_domain),
   $capability,
   $this->param('edit_page_suffix').'_'.$post_type_name,
   array(&$this, 'edit_page_html')
  );
 }
}

public function edit_page_html() {
 global $wp_custom_functions;
 
 $pt = preg_replace(sprintf('/%s_/', $this->param('edit_page_suffix')), '', $_GET['page']);
 $post_type = get_post_type_object($pt);
 
 echo createHTMLElement('div', 'start', array('class'=>'wrap'));
 echo'<div class="icon32" id="icon-edit"><br></div>';
 echo createHTMLElement('h2', NULL, __('Edit Archive Page Parts', $custom_language_domain));
 
 
 echo createHTMLElement('textarea', array('id'=>'edit_1'));
 wp_editor( '' ); 
 echo wrapJavaScript('jQuery(document).ready(function() {if ( typeof tinyMCE != "undefined" ) { tinyMCE.execCommand("mceAddControl", false, "edit_1"); /*tinyMCEID.push("'.$label_for.'");*/ }} );', array('jquery'=>TRUE, 'jqueryready'=>TRUE));
 echo createHTMLElement('div', 'end');
}
}

//add_action('WPCF_Initialize', function() { new ArchivePageParts(); });