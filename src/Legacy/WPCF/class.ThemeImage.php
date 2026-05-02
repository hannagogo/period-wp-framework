<?php
include_once('class.HashAccessor.php');
class ThemeImage extends ClassTemplate {

function __construct($atts = array()) {
 global $wp_custom_functions ;
 $this->param( $wp_custom_functions->parse_args( array(
  'image_dir' => $this->image_dir, 
 ), $atts ) );
 $this->setup();
}

public function setup() {
 $this->_setup_variables();
 $this->_setup_filters();
 $this->_setup_actions();
}


public function remove_slashes($file, $leading=true, $trailing=true) {
 if ($leading) $file = preg_replace('/^\x2f+/', '', $file);
 if ($trailing) $file = preg_replace('/([^\x2f])?\x2f+$/', '$1', $file);
 return $file;
}

private function _setup_variables() {
 $this->general_name = __CLASS__;
 $this->db_column_name = $this->general_name;
 $this->_info = get_custom_functions_data($this->db_column_name, false, false);
 $this->info = unserialize($this->_info);
 $attrs = array();
 foreach ($this->img_attrs as $a) { $attrs[$a] = null ; }
 $this->img_attrs = $attrs;
}

private function _setup_filters() {
 add_filter($this->general_name.'_Suffix', array(&$this, '_filter_suffix'));
 add_filter($this->general_name.'_Handles', array(&$this, '_filter_handles'));
 add_filter(WPCF_PREFIX.'Setting_Page', array(&$this, 'setting_page_html') );
}

private function _setup_actions() {
 add_action( 'admin_head', array(&$this, 'update_info') );
 add_action( 'shutdown', array(&$this, 'save_info'));
}

public function _filter_suffix($array) {
 $a = array();
 foreach (array_merge($this->suffix, (array) $array) as $ext) {
  $ext = preg_replace('/^\x2e/', '', $ext);
  $a[] = strtolower($ext); $a[] = strtoupper($ext);
 }
 return $a;
}

public function _filter_handles($array) {
 global $theme_image_handles;
 return array_merge($theme_iamge_handles, (array) $array);
}

public function update_info() {
 global $custom_language_domain, $wp_custom_functions;
 $nonce = $wp_custom_functions->nonce('refresh_image');

 if ($wp_custom_functions->verify_nonce('refresh_image', $nonce)) {
  $this->refresh_info();
  $wp_custom_functions->set_admin_notices(__("Updated Theme Image information on database.", $custom_language_domain) );
 }
}


public function setting_page_html($html) {
 global $custom_language_domain, $wp_custom_functions;

 $html .= createHTMLElement('div', array('class'=>"wrap", 'id'=>$this->general_name.'_settings_wrap'),
  createHTMLElement( 'h3', null, __('Theme Image', $custom_language_domain) )
   . $wp_custom_functions->setting_form('refresh_image')
   . createHTMLElement('input', array(
     'type'=>"submit",
     'name'=>"submit",
     'id'=>$this->general_name."_settings_submit",
     'class'=>"button button-primary",
     'value'=>__("Refresh image info", $custom_language_domain)
    ) )
   . createHTMLElement('form', 'end')
  ); // END OF WRAP;
 return $html;
}


public function register_handle($handle, $filepath, $get_info=true) {
 global $theme_image_handles;
 $theme_image_handles[$handle] = $get_info ? $this->fetch_image_info($filepath) : array('path' => $filepath);
 $this->info($handle, $theme_image_handles[$handle]);
 return $theme_image_handles[$handle];
}

function fetch_image_info($filepath, $param=null) {
 if (!file_exists($filepath)) {
  $r = $this->search_filepath($filepath);
 }
;
 if (empty($r['path']) || !file_exists($r['path'])) return null;
 $i = getimagesize($r['path']);
 $info = array(
  'path'	 => $r['path'],
  'width'	 => $i[0],
  'height'	 => $i[1],
  'type'	 => $i['mime'],
  'name'	 => basename($r['path']),
  'uri'		 => $r['uri']
 );
 return $info;
}


function save_info() {
 $info_s = serialize($this->info);
 if ($this->_info != $info_s) {
  set_custom_functions_data(array('name'=>$this->db_column_name, 'value'=>$info_s, 'serialize'=>false));
 }
 return $this;
}


function refresh_info() {
 $info = array();
 foreach ($this->info() as $h=>$i) {
  if (isset($i['path']) && $i['path']) {
   $info[$h] = (array) $this->fetch_image_info($i['path']);
  }
 }
 $this->info = $info;
}


public function info($handle=null, $value=null) {
 global $theme_image_handles
 ;
 $handle = (string) $handle;
 if ($handle) {
  if (!empty($value)) {
   $this->info[$handle] = $value;
   $theme_image_handles[$handle] = $value['path'];
   return $value;
  }
  else {
   return isset($this->info[$handle]) ? $this->info[$handle] : null;
  }
  return null;
 }
 return $this->info;
}


public function get_info($handle, $field=null, $force_refresh=false) {
 if ($force_refresh) $this->refresh_info();
 if ($i = $this->info($handle)) {
  if ($field && isset($i[$field])) return $i[$field];
  return $i;
 }
 else {
  return null;
 }
}


function search_filepath($name, $param=null) {
 global $switched, $wp_custom_functions ;

 $stylesheet_dir = get_stylesheet_directory();
 $stylesheet_uri = get_stylesheet_directory_uri();
 $filepath = null;
 $suffix = apply_filters($this->general_name.'_Suffix', null);

 $param = $wp_custom_functions->parse_args( array(
  'image_dir' => $this->param('image_dir')
 ), $param);

 $p = parse_url( $name );
 $p = pathinfo( $p['path'] );
 $file = $p['filename'];
 if (isset($p['extension'])) array_splice($suffix, 0, 0, $p['extension']);
 if (isset($p['dirname']) && $p['dirname'] != '.' && $p['dirname']) {
  $param['image_dir'] = $this->remove_slashes($p['dirname']);
 }
 $dir = $param['image_dir'];
 foreach ($suffix as $ext) {
  $fn = sprintf('%s.%s', $file, $ext); 
  $root = $_SERVER['DOCUMENT_ROOT'];
  if ($rr = realpath($root)) $root = $rr;
  // Theme/Child theme/Switched Blog's theme 
  if (file_exists($filepath = implode(DIRECTORY_SEPARATOR, array($stylesheet_dir, $dir, $fn)))) {
   return array('path'=>$filepath, 'uri'=>preg_replace('{'.$rr.'}', home_url(), $filepath));
  }
  // Currently switched, original theme (maybe child theme)
  if ($switched) {
   $switched_blog_id = $switched;
   restore_current_blog();
   if (file_exists($filepath = implode(DIRECTORY_SEPARATOR, array($stylesheet_dir, $dir, $fn)))) {
    $r = array('path'=>$filepath, 'uri'=>preg_replace('{'.$rr.'}', home_url(), $filepath));
    switch_to_blog($switched_blog_id);
    return $r;
   }
  }
  // Parent theme/Currently switched, original (parent) theme
  if (file_exists($filepath = implode(DIRECTORY_SEPARATOR, array(TEMPLATEPATH, $dir, $fn)))) {
   return array('path'=>$filepath, 'uri'=>preg_replace('{'.$rr.'}', home_url(), $filepath));
  }
 }
// if(is_specific_user_logged_in(1)) my_print_r($stylesheet_dir); 
 return null;
}


public function img($name, $attrs=null) {
 return $this->imageinfo($name, 'tag', $attrs);
}


function imageinfo($name, $type=null, $attrs=null, $param=null) {
 global $wp_custom_functions ;
 $img_attrs = $this->img_attrs;
 unset($img_attrs['width'], $img_attrs['height'], $img_attrs['src']);

 $attrs = $wp_custom_functions->parse_args(
  $wp_custom_functions->parse_args($this->img_attrs, array(
   'alt' => $name,
   'class' => null,
   'id' => $name,
   'width' => null,
   'height' => null,
  ) ), $attrs
 );

 $param = $wp_custom_functions->parse_args( array(
  'image_dir' => $this->param('image_dir')
 ), $param);

 if (!$this->get_info($name)) {
  $this->info($name, $this->fetch_image_info($name, $param));
 }
 $info = $this->info($name);
 if ($info) {
  $info = new HashAccessor($this->get_info($name)); 
  $info->set_accepted_param_keys($this->image_info_param_keys);
  if ($type == 'full') return $info->get_params();
  $file_url = wpcf_url_to_https($info->param('uri'));
  
  if ($type == 'file_url' || $type == 'uri') return $file_url;
  if ($type == 'absolute') { return preg_replace('/^https?\x3a\x2f\x2f.+?(\x2f)/', '$1', $file_url); }
  list($w, $h) = array(
   $attrs['width'] === null ? $info->param('width') : $attrs['width'],
   $attrs['height'] === null ? $info->param('height') : $attrs['height'],
  );
  $wh = sprintf('width="%s" height="%s"', (string) $w, (string) $h);
  if ($type == 'size') return $wh ;
  if ($type == 'type') return $info->param('type') ;
  if ($type == 'width'  || $type == 'x') return $info->param('width');
  if ($type == 'height' || $type == 'y') return $info->param('height');
  $a = array();
  foreach ($attrs as $k => $v) { 
   if ($v) {
    if ($k == 'class') {
     $v = str_replace(',', ' ', $v);
    }
    $a[] = sprintf("%s=\x22%s\x22", $k, $v);
   }
  }
  $str_attr = implode("\x20", $a);
  return sprintf('<img src="%s" %s %s />', $file_url, $wh, $str_attr); 
 }
 return null;
}


/*////// PROPERTIES ///////*/
var $template_path = null; 
var $template_url = null; 
var $valid_suffix = null;
private $names = array();
private $image_info_param_keys = array(
 'path', 'width', 'height', 'name', 'directory', 'uri', 'type'
);
private $info = array();
private $image_dir = 'images';
private $_info = null;
private $suffix = array(
 'jpg', 'gif', 'png', 'tif', 'jpeg', 'tiff'
);
private $img_attrs = array(
 'alt',
 'class',
 'id',
 'crossorigin',
 'ismap',
 'usemap',
 'accesskey',
 'contenteditable',
 'contextmenu',
 'dir',
 'draggable',
 'dropzone',
 'hidden',
 'lang',
 'spellcheck',
 'style',
 'tabindex',
 'title',
 'translate',
 'width', 'height', 'src'
);
} // END OF CLASS
