<?php
class ImageInfo {
var $template_path = null; 
var $template_url = null; 
var $valid_suffix = null;
private $names = array();
private $data = array();
private $names_orig = null;
private $data_orig = null;
private $names_column_name = null;
private $info_column_name = null;

function __construct($atts = array()) {
 global $wp_custom_functions;
 $args = $wp_custom_functions->parse_args(
  array(
   'template_path'		 => TEMPLATEPATH,
   'template_url'		 => get_bloginfo('template_url'),
   'image_directory'	 => 'images', 
   'search_path'		 => 'images',
   'valid_suffix'		 => array('jpg','gif','png','jpeg','tif','tiff')
  ), $atts
 );
 $this->template_path	 = $args['template_path'] ;
 $this->template_url	 = $args['template_url'] ;
 $this->image_directory = $args['image_directory'] ;
 $this->valid_suffix = $args['valid_suffix'];
 $this->fetch();
 $this->names_column_name = __CLASS__.'_IMAGE_NAMES';
 $this->info_column_name  = __CLASS__.'_IMAGE_INFO';
}


function get($filepath) {
 $this->get_data();
 $this->get_names();
 $info = &$this->data;
 $names = &$this->names;

 if (empty($info[$filepath])) {
  $info[$filepath] = getimagesize($filepath);
  $names[] = $filepath;
 }
 return $info[$filepath];
}


function __destruct() {
 $this->save();
}


function fetch() {
 $this->get_names(true);
 $this->get_data(true);
}


function save() {
 $names_s = serialize($this->names);
 $info_s = serialize($this->data);
 if ($names_s != $this->names_orig) {
  set_custom_functions_data($this->names_column_name, $names_s);
 }
 if ($info_s != $this->data_orig) {
  set_custom_functions_data($this->info_column_name, $info_s);
 }
}


function get_names($force_refresh=false, $unserialize=true) {
 if ($force_refresh || empty($this->names)) {
  $this->names_orig = get_custom_functions_data($this->names_column_name, false, false);
  $this->names = unserialize($this->names_orig);
 }
 return $unserialize ? $this->names : $this->names_orig;
}


function get_data($force_refresh=false, $unserialize=true) {
 if ($force_refresh || empty($this->data)) {
  $this->data_orig = get_custom_functions_data($this->info_column_name, false, false);
  $this->data = unserialize($this->data_orig);
 }
 return $unserialize ? $this->data : $this->data_orig;
}


private function _build_path($d) {
 $path = func_get_args();
 if (is_array($path[0]) && count($path) == 1) { $path = $path[0]; } 
 $root = array_splice($path, 0, 1);
 $p = trailingslashit($root[0]);
 foreach ($path as $d) {
  $p .= trailingslashit( preg_replace('/^\x2f*/', '', $d) );
 }
 return $p;
}

function imageinfo($name, $type = null, $attrs = array(), $param=null) {
 global $wp_custom_functions;
 $fiepath = '';
 $template_url = '';
 $template_path = '';
 $param = $wp_custom_functions->parse_args(array(
  'image_directory' => $this->image_directory,
 ), $param);

 $dir = $param['image_directory'];
 $stylesheet_directory = get_stylesheet_directory();
 $stylesheet_directory_uri = get_stylesheet_directory_uri();

 if ( file_exists($filepath = $this->_build_path($this->template_path, $dir, $name)) ) {
  // Theme or Parent theme
  $template_path = $this->template_path;
  $template_url = $this->template_url;
 }
 else {
  if (file_exists($filepath = $this->_build_path( $stylesheet_directory, $dir, $name)) ) {
   // Child theme or switched blog's theme
   $template_url = $stylesheet_directory_uri;
   $template_path = $stylesheet_directory;
  }
  else {
   // currently switched && Original theme or Original child theme
   global $switched;
   $switched_blog_id = null;
   $sw = false;
   if ($switched) {
    $sw = $switched;
    $switched_blog_id = get_current_blog_id();
    restore_current_blog();
   }
   if (file_exists($filepath = $this->_build_path($stylesheet_directory, $dir, $name)) ) {
    $template_url = $stylesheet_directory_uri;
    $template_path = $stylesheet_directory;
    if ($sw) switch_to_blog($switched_blog_id);
   }
   else $filepath = null;
  }
 }
 
 if ($filepath) {
  $file_url = $this->_build_path( $template_url, $dir, $name );
  $attrs = array_merge(array('alt'=>$name), $attrs);
  $img = $this->get($filepath);
  if (is_array($img)) {
   if ($type == 'size') return $img[3];
   if ($type == 'width' || $type == 'x') return $img[0];
   if ($type == 'height' || $type == 'y') return $img[1];
   $str_attr = null;
   if (count($attrs) > 0) {
    $str_attr = implode("\x20",
     array_flatten(array_map(
      function($attrs) {
       $r = array();
       foreach (array_keys($attrs) as $k) {
        if ($k == 0 && $attrs[$k] == "") { continue; };
        array_push($r, implode("=", array($k, "\x22".$attrs[$k]."\x22")));
       };
       return $r;
      },
      array($attrs)
     ))
    );
   }
   if ($type == 'file_url') { return $file_url; }
   if ($type == 'absolute') { return preg_replace('/^https?\x3a\x2f\x2f.*?(\x2f)/', '$1', $file_url); }
    return '<img src="' . $file_url . '" ' . $img[3] . " " . $str_attr . ' />'; 
  }
 }
}
} /// END OF CLASS : ImageInfo

