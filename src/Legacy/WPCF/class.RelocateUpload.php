<?php
class RelocateUpload {

public $general_name = 'relocate_upload';
public $folders = NULL ;
public $folder_name_query_key = NULL ;

function __construct() {
 global $custom_language_domain, $wp_custom_functions
 ;
 if( is_admin() ) {
  add_action( 'wp_ajax_'.$this->general_name, array(&$this, 'ajax_action') );
 }
 $this->option_key =  $this->general_name.'_folders';
 $folders = apply_filters( WPCF_PREFIX.'Option', $this->option_key);
 $this->yearmonth_token = '%YEAR%/%MONTH%/';
 $this->_use_yearmonth = get_option( 'uploads_use_yearmonth_folders' );
 $this->folder_name_query_key = $this->general_name . '_folder';
 $upload_dir = wp_upload_dir();
 $this->default_folder = array(
  'name' => __('Default Location', $custom_language_domain),
  'path' => preg_replace('|^'.SERVER_DOCUMENT_ROOT.'|', '', trailingslashit($upload_dir['basedir']) . ($this->_use_yearmonth ? $this->yearmonth_token : ''))
 );
 $this->folders = array($this->default_folder);
 if (!empty($folders)) {
  $this->folders = array_merge($this->folders, $folders);
 }
 add_action( 'admin_head', array(&$this, 'js' ) );
 add_filter( 'posts_where', array(&$this, 'library_filter' ) );
 add_filter( 'attachment_fields_to_edit', array(&$this, 'attachment_field'), 3, 2);
 add_action( 'admin_menu', array(&$this, 'submenu_page') );
 $this->setting_page_url = admin_url('admin.php?page=' . $this->general_name . '_settings');
 $this->move_js_func_name = 'window.'.$this->general_name.'_request_move';
}


function submenu_page() {
 global $custom_language_domain
 ;
 add_submenu_page(
  apply_filters( WPCF_PREFIX.'Setting_Page_Name', NULL),
  __('Relocate Upload Settings', $custom_language_domain),
  __('Relocate Upload Settings', $custom_language_domain),
  'manage_options',
  $this->general_name . '_settings',
  array(&$this, 'admin_options')
 );
}

// Move folder request handled when called by GET AJAX
function ajax_action() {
 global $wpdb, $custom_language_domain
 ;
 if (!isset($_GET[$this->folder_name_query_key])) exit;

 check_admin_referer($this->general_name.'_request_move');
 
 $result = '';
 
 // Attachment Current Info: PATH, DATE
 $id = $_GET['id'];
 $attachment_path = get_attached_file( $id, TRUE );
 $attachment_record = & $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $id));
 $attachment_date = $attachment_record->post_date;
 $attachment_guid = $attachment_record->guid;

 // Find new path for attachment
 $new_path = $this->folders[$_GET[$this->folder_name_query_key]]['path'] . basename($attachment_path);
 $new_path = untrailingslashit(SERVER_DOCUMENT_ROOT) . $this->replace_yearmonth_token($new_path, $attachment_date);

 // attempt to move the file
 if (file_exists($new_path)) {
  $result = __('[0] Relocation Failed: File already exists in destination directory.', $custom_language_domain);
 }
 else if (rename($attachment_path,$new_path)) {
  $result = __('[2] Relocating Files...', $custom_language_domain);
  $meta = get_post_meta($id, "_wp_attachment_metadata", true);
  // Move Thumbnails too
  if (!empty($meta['sizes'])) {
   foreach($meta['sizes'] as $s=>$size) {
    rename( trailingslashit( dirname($attachment_path) ) . $size['file'], trailingslashit( dirname($new_path) ). $size['file'] );
   }
  }
  // Update the Metadata
  $meta['file'] = $new_path;
  update_post_meta($id, "_wp_attachment_metadata", $meta);
  update_post_meta($id, "_wp_attached_file", $new_path);
  
  // Update the post/attachment GUID field
  $new_guid = str_replace(
   str_replace(SERVER_DOCUMENT_ROOT, "", $attachment_path),
   str_replace(SERVER_DOCUMENT_ROOT, "", $new_path),
   $attachment_guid
  );
  $attachment_record = & $wpdb->get_row($wpdb->prepare("UPDATE $wpdb->posts SET guid='%s' WHERE ID = %d ", $new_guid, $id));

  // let the client know all is well, and what the new guid is
  $result = sprintf( __("[1] File Relocated: %s", $custom_language_domain), $new_guid);
 }

 header("HTTP/1.0 200 OK");
 header('Content-type: text/plain;');
 echo $result;
 exit;
}


function js() {
 global $custom_language_domain
 ;
// get the JS into the admin pages to run the AJAX request
// and to add the media library 'folder' filter
 if (
     strpos($_SERVER['REQUEST_URI'], "/wp-admin/media-upload.php")	 === FALSE
  && strpos($_SERVER['REQUEST_URI'], "/wp-admin/upload.php")		 === FALSE
  && strpos($_SERVER['REQUEST_URI'], "/wp-admin/media-new.php")		 === FALSE
  && strpos($_SERVER['REQUEST_URI'], "/wp-admin/media.php")			 === FALSE

  && strpos($_SERVER['REQUEST_URI'], "/wp-admin/post-new.php")		 === FALSE
  && strpos($_SERVER['REQUEST_URI'], "/wp-admin/post.php")			 === FALSE
 ) {
  return;
 }
 $script = '
 '.$this->move_js_func_name.' = function (e) {
 ee = $(e)
 ee.attr({disabled: true});
 ee.siblings("span").html(" '.__( 'Moving...', $custom_language_domain).'"); 
 $.get(ajaxurl, {
  "'.$this->folder_name_query_key.'" : e.selectedIndex,
  "id" : e.getAttribute("media_id"),
  "action" : "relocate_upload",
  "_wpnonce" : "'. wp_create_nonce($this->general_name.'_request_move') .'"
  '.( defined("DEBUG") && DEBUG=="DEBUG" ? ', "DEBUG":"DEBUG" ':"") .'
 },
  function(data) {
   ee.attr({disabled: false});
   m_item = ee.parents("div#post-body");

   if (data.substring(0,3) == "[1]") {
	ee.siblings("span").html("'.__("Relocation Succeeded.", $custom_language_domain).'");
    m_item.find("input[name=attachment_url]").val(data.substring(6));
   }
   else if (data == "")
	ee.siblings("span").html(" '.__('Relocation Failed.', $custom_language_domain).'");
   else
    ee.siblings("span").html(" " + data);
  }
 );
}
'
; 
 // smuggle the filter menu into place with JS - no proper hook to get it in place
 // compile the HTML
 $i = 0;
 $menu = apply_filters('CF_HTML', 'option', array('value'=>''), __('All Folders', $custom_language_domain));
 foreach (array_splice($this->folders,0,1) as $f) {
  $menu .= apply_filters('CF_HTML', 'option',
   array('value'=>'', 'selected'=>isset($_GET[$this->general_name.'_index']) && $_GET[$this->general_name.'_index'] == $i ? 'selected':''),
   $f['name']
  );
 }

 $script .= '$("select[name=m]").after("<select name='.$this->general_name.'_index>'.str_replace("\x22", '\"', $menu).'</select>")';
 echo apply_filters('CF_Wrap_JavaScript', $script, array('jquery'=>'true'));
}


// add relocate upload library filter
function library_filter($where) {
 if (
  (!isset($_GET[$this->general_name.'_index']) || $_GET[$this->general_name.'_index'] == NULL)
  ||
  (
      strpos($_SERVER['REQUEST_URI'], "/wp-admin/media-upload.php")	 === FALSE
   && strpos($_SERVER['REQUEST_URI'], "/wp-admin/upload.php")		 === FALSE
  )
  ||
  (
   strpos($_SERVER['REQUEST_URI'], "/wp-admin/media-upload.php") && ($_GET['tab']!="library")
  )
 ) {
  return $where;
 }
 
 global $wpdb;

 $folder = 
     $_GET[$this->folder_name_query_key]
  && isset($this->folders[$_GET[$this->folder_name_query_key]])
  && isset($this->folders[$_GET[$this->folder_name_query_key]]['path'])
  ? $this->folders[$_GET[$this->folder_name_query_key]]['path'] : NULL
 ;
 if ($folder) {
  $where .= " AND $wpdb->posts.guid LIKE '%". $folder ."%'";
 }
 return $where;
}


// hook in to the media library to make the extra control
function attachment_field($form_fields, $post) {
 global $custom_language_domain
 ;
 $folders = array();
 $menu = '';
 foreach ($this->folders as $f) {
  if (isset($f['path'])) {
   $f['path'] = $this->replace_yearmonth_token($f['path'], $post->post_date);
  }
  $folders[] = $f;
 }

 // compile menu, set selected item where path matches attachments current path
 foreach ($folders as $f) {
  $menu .= apply_filters('CF_HTML',
   'option',
   array('selected'=>strpos(get_attached_file($post->ID), $f['path'])!==false ? 'selected':''),
   $f['name']
  );
 }

 // Add Selection Menu
 $form_fields[$this->general_name.'_location'] = array(
  'label' => __("Relocate Upload Folder", $custom_language_domain),
  'input' => 'html',
  'html'  => apply_filters('CF_HTML',
   'select',
   array(
    'media_id'=>$post->ID,
	'onchange'=>$this->move_js_func_name.'(this);'
   ),
   $menu
  ) . "<span></span>"
 );
 return $form_fields;
}


// put in the options page
function admin_options() {
 global $custom_language_domain
 ;
 $folder_name_key = $this->general_name.'_folder_name';
 $folder_path_key = $this->general_name.'_folder_path';

 $folders = array();
 if (isset($_POST[$folder_name_key]) && $_POST[$folder_name_key]) {
  $names = $_POST[$folder_name_key];
  $paths = isset($_POST[$folder_path_key]) ? $_POST[$folder_path_key] : array();
  for ($i=0; $i < count($names); $i++) {
   if ($names[$i] != '' && $paths[$i] != '') {
    $path = trailingslashit($_POST[$folder_path_key][$i]);
	$path = preg_replace('/^\x2f?/', '/', $path);
    $folders[] = array('name' => $names[$i], 'path' => $path);
   }
  }
  // save it as a WP option
  apply_filters( WPCF_PREFIX.'Set_Option', $this->option_key, $folders );
  
 }
 $folders = array_merge(array($this->default_folder), apply_filters( WPCF_PREFIX.'Option', $this->option_key ));

 $html = apply_filters('CF_HTML', 'div', 'start', array('class'=>'wrap'))
  . apply_filters('CF_HTML', 'div', array('id'=>'icon-options-general', 'class'=>'icon32'), ' ')
  . apply_filters('CF_HTML', 'h2', NULL, sprintf(
     '%s : %s',
     __('Relocate Upload Settings', $custom_language_domain),
     __('Upload Locations', $custom_language_domain)
	) )
  . apply_filters('CF_HTML', 'form', 'start', 
	 array('action'=>$this->setting_page_url, 'method'=>'POST'), __('Relocate Upload - Locations', $custom_language_domain)
	)
  . apply_filters('CF_HTML', 'p', NULL,
	 sprintf( __('Paths relative to the blog\'s root directory: %s', $custom_language_domain), '<em>'.SERVER_DOCUMENT_ROOT.'</em>')
	)
  ;
 $disabled = array('disabled'=>'true');
 $list = '';
 $folders = array_merge($folders, array(array('name'=>NULL, 'path'=>NULL)));
 foreach($folders as $f) {
  // create directory if needed
  $ym = $this->replace_yearmonth_token( $f['path'], date("Y-m") );
  $new_dir = SERVER_DOCUMENT_ROOT . $ym;
  if (!file_exists($new_dir)) {
   mkdir( $new_dir, 0755, true );
  }
  $bad_folder = empty($disabled) && $f['path'] && !is_writable($new_dir);
  $list .= apply_filters('CF_HTML', 'li', 'start', array('class'=>$bad_folder?'bad_folder':''));
  $list .=
     apply_filters('CF_HTML', 'input', array_merge(array('name'=>$folder_name_key.'[]', 'type'=>'text', 'value'=>$f['name'], ), $disabled))
   . apply_filters('CF_HTML', 'input', array_merge(array('name'=>$folder_path_key.'[]', 'type'=>'text', 'value'=>$f['path'], ), $disabled))
  ;
  if (empty($disabled) && $f['path']) {
   $list .= apply_filters('CF_HTML', 'span', array('class'=>'remove', 'title'=>__('Remove Location', $custom_language_domain) ), '&nbsp;');
  }
  if ($f['path'] == "") {
   $list .= __('New Location', $custom_language_domain);
  }
  $list .= "</li>";
  $disabled = array();
 }
 $html .= apply_filters('CF_HTML', 'ul', array('id'=>'relocate_upload_folder_list'), $list);
 $html .= apply_filters('CF_HTML', 'p', array('class'=>'submit'),
  apply_filters('CF_HTML', 'input', array('type'=>'submit', 'value'=>__('Update', $custom_language_domain), 'class'=>'button-primary'))
 );
 $html .= '</form>';
 $script = apply_filters('CF_Wrap_JavaScript', 
  '$("#relocate_upload_folder_list span.remove").click(function(){ $(this).parent().remove() })',
  array('jquery'=>TRUE)
 );
 echo $html;
 echo $script;
}

// generic token replacement
function replace_yearmonth_token($path, $date) {
 $path=str_replace("%YEAR%", substr($date,0,4),$path);
 $path=str_replace("%MONTH%",substr($date,5,2),$path);
 return $path;
}


// thing is if you have an absolute path to your file, WP will give you an url like
// http://domain.co.uk/path_to/wp-content/uploads//home/useraccount/public_html/another_path_to/media/media_item.gif
// note the double /
//
// as SERVER_DOCUMENT_ROOT = /home/useraccount/public_html
//
// we do a search for (http://.*?/).*?/SERVER_DOCUMENT_ROOT/ and replace with \1
//add_filter( 'wp_get_attachment_url', "wp_get_attachment_url_absolute_path_fix");
//function wp_get_attachment_url_absolute_path_fix($url) {	return preg_replace('#(http://.*?/).*?/'.(SERVER_DOCUMENT_ROOT).'/#','\1',$url); }


}
add_action('after_setup_theme', function() { new RelocateUpload; });