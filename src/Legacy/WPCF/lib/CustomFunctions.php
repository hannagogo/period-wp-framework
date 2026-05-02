<?php
if ( defined('CUSTOM_FUNCTIONS_LOADED') || function_exists('add_include_path') ) { return; } else {
define('CUSTOM_FUNCTIONS_LOADED', TRUE);
define('CR', "\x0d"); define('LF', "\x0a"); define('CRLF', CR.LF);
define('UTF8', 'UTF-8');
mb_regex_encoding(UTF8);
mb_internal_encoding(UTF8);

global $CustomFunctions_HTMLElements
;


function array_value($array,$key,$default=NULL) {
 return isset($array[$key])? $array[$key] : $default ;
}


function fix_document_root() {
/* //
Fixes when $_SERVER['DOCUMENT_ROOT'] is not the site's root directory. 
 e.g. $_SERVER['DOCUMENT_ROOT']/example.com/ is site's root; SAKURA Internet shared rental server
// */
 if ( 'support@sakura.ad.jp' == $_SERVER['SERVER_ADMIN'] ) {
  $path =  $_SERVER["DOCUMENT_ROOT"] . '/' . $_SERVER['HTTP_HOST'] ;
  if ( file_exists($path) ) {
   $_SERVER['DOCUMENT_ROOT'] = $path;
   return TRUE
   ;
  }
 }
 return FALSE
 ;
}

function add_include_path($path) {
 return set_include_path(implode(
  PATH_SEPARATOR,
  array(get_include_path(), $path)
 ) );
 return;
}
/* sets full URI of the page */
$_SERVER['FULL_URL'] = // is an alias
$_SERVER['FULL_URI'] = 'http' .
	 ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')? 's' : '') .
	 '://' . array_value($_SERVER, 'HTTP_HOST','') . 
	 ((isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != '80'))? ':' . $_SERVER['SERVER_PORT'] : '') .
	 array_value($_SERVER,'REQUEST_URI','')
//	 $_SERVER['SCRIPT_NAME'] .
//	 (($_SERVER['QUERY_STRING'] > ' ') ? '?' . $_SERVER['QUERY_STRING'] : '')
;

add_include_path(dirname(__FILE__));

function path_to_full_uri ($path = '', $base = '') {
 if (!$base) $base = $_SERVER['FULL_URI'];
 
 $baseinfo = parse_url($base);

 if (preg_match('/^https?\:\/\//', $path) ) return $path;
 elseif ( preg_match('/^\//', $path) ) return $baseinfo['scheme'] . '://' . $baseinfo['host'] . $path;
 else {
  $base_parts = explode('/', $baseinfo['path']); array_pop($base_parts);
  $path_parts = explode('/', $path);
  
  for ($i = 0; $i < count($path_parts); $i++) {
   if (strcmp($path_parts[$i], '..') == 0) {
    array_pop($base_parts);
    continue;
   }
   array_push($base_parts, $path_parts[$i]);
  }
  return (($baseinfo['scheme'])? $baseinfo['scheme'] . '://' : '') .
   $baseinfo['host'] .
   ((strcmp($base_parts[0], '')== 0)? '' : '/') .
   join('/', $base_parts);
 }
}

function root_url($url=NULL) {
 if ($url === NULL) { $url =  $_SERVER['FULL_URI']; }
 return preg_replace( '/^(.*?(?<!\x2f))\x2f(?!\x2f).*?$/', '$1', $url );
}
function root_relative_url($url=NULL) {
 if ($url === NULL) { $url =  $_SERVER['FULL_URI']; }
 return preg_replace( '/^'.preg_quote( root_url($url), '/' ).'/', '',  $url );
}

function qw($str='') { if (is_string($str) && $str) return explode("\x20", preg_replace('/[\s]+/', "\x20", trim($str))); return $str; }

function name_to_dir($str='') {
 if (is_string($str)) {
  $s = DIRECTORY_SEPARATOR;
  return preg_replace('"$s+?$"', "$s", trim($str));
 }
 return;
}

function is_hash($array) {
 if (!is_array($array)) return FALSE ;
 foreach (array_keys($array) as $k) {
  if (gettype($k) == 'string') return TRUE;
 }
 return FALSE;
 /* // Folloing is well known code. It returns true when the array is not sequential: ie. array(3=>'value', 5=>'value'), etc.
 if (!is_array($array)) return FALSE ;
 return array_keys($array) !== range(0, count($array) - 1);
 // */
 }

function array_flatten_recursive($array, $flat = false) { 
 if (!is_array($array) || empty($array)) return (array) $array; 
 $flat = (array) $flat; 
 foreach ($array as $key => $val) { 
   if (is_array($val)) $flat = array_flatten($val, $flat); 
   else $flat[] = $val; 
 } 
 return $flat; 
} 

function array_flatten_iterator(array $arr) {
 return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($arr)), false);
}

function array_flatten(array $array) {
 return array_flatten_iterator($array);	
}

function array_values_empty($array, $recursive = FALSE) {
 $_has_value = FALSE;
 foreach ($array as $i) {
  if ($_has_value) break;
  $_item_has_value = FALSE;
  if ($recursive && is_array($i)) $_item_has_value = !( array_has_empty_values($i, $recursive) );
  else $_item_has_value = !empty($i); 
  $_has_value = $_item_has_value || $_has_value;
 }
 return !$_has_value ;
}



function echo_function_return_value($function_name) {
 if (function_exists($function_name)) {
  $r = $function_name();
  if (!empty($r)) echo $r;
 }
}





function make_tag($a = '', $b = null, $c = null) {
 if ($a === '') return $a;
 $attr = array();
 if (is_string($a)) {
  $attr['element'] = $a;
  $attr['start_or_end'] = $b;
  if (is_hash($c)) foreach(array_keys($c) as $k) $attr[$k] = $c[$k];
 }
 elseif (!is_hash($a) && is_array($a)) list($attr['element'], $attr['start_or_end']) = $a;
 elseif (is_hash($a)) $attr = $a;

 if (!$attr['element']) return;
 if (!$attr['start_or_end']) $attr['start_or_end'] = 'start';
 if ($attr['start_or_end'] != 'start' && !$attr['start_or_end'] == 'empty') $attr['start_or_end'] = 'end';

 $tag_v = $attr['start_or_end'] . '_tag_';

// foreach (array_keys($attr) as $k) $attr[$k] = preg_replace('/[\x22\x27\x3c\x3e]/', '', $attr[$k]);
 $attrs = $attr;
 unset($attrs['start_or_end']);
 unset($attrs['element']);
 
 $start_tag_open_delimiter	 = '<';
 $end_tag_open_delimiter	 = '</';
 $start_tag_close_delimiter	 = '>';
 $end_tag_close_delimiter	 = '>';
 $empty_tag_open_delimiter	 = '<';
 $empty_tag_close_delimiter	 = ' />';
 
 $attrs_str = make_html_attributes($attrs);

 $tag = ${$tag_v . 'open_delimiter'} . $attr['element'] . "\x20";
 if ($attr['start_or_end'] == 'start' || $attr['start_or_end'] == 'empty') $tag .= $attrs_str;
 $tag = preg_replace('/ $/', '', $tag);
 $tag .= ${$tag_v . 'close_delimiter'};

 return $tag;
}


function make_html_attributes($attrs, $quote="\x22", $concat="\x2c") {
 $attrs_str = '';
 foreach ((array) $attrs as $k => $v) {
  if ($k == 'class') $attrs[$k] = implode("\x20", (array) $attrs[$k]);
  if ($k == 'id') $attrs[$k] = implode($concat, (array) $attrs[$k]);
  if (((string) $attrs[$k]) != '') $attrs_str .= sprintf("%s=%s%s%s", $k, $quote, $attrs[$k], $quote) . "\x20";
 }
 return $attrs_str;
}


function createHTMLElement($name, $attr=NULL, $content=NULL, $no_tags_if_empty=FALSE, $ignore_tags=FALSE, $force_strip_tags=FALSE) {
/* //
For SELECT element use html_select_element($attr)
// */

 global $CustomFunctions_HTMLElements ;
 $out = '';
 if (empty($CustomFunctions_HTMLElements)) $CustomFunctions_HTMLElements = array();
 
 $empty_elements = qw('base meta link hr col img br map input _comment _cdata');
 $form_elements = qw('input select option textarea button');

 if (is_string($attr) && ($attr == 'start' || $attr == 'end') ) {
  $out .= make_tag($name, $attr, $content);
  return $out;
 }
 if (in_array($name, $empty_elements) !== false) {
  // parse image file
  if ($name == 'img'
   && $_SERVER['REQUEST_URI']
   && is_hash($attr)
   && (isset($attr['src']) && is_string($attr['src']))
   && (!isset($attr['width']) || !$attr['width'])
   && (!isset($attr['height']) || !$attr['height'])
   && !preg_match('/^https?\x3a\x2f\x2f/', array_value($attr,'src',''))
  ) {
   $img_path = '';
   $cd = preg_replace('/(\x2f)[^\x2f]*?$/', '$1', $_SERVER['REQUEST_URI']); // current directory
   $document_root = $_SERVER['DOCUMENT_ROOT'];

   if (preg_match('/^\x2e\x2f/', $attr['src'])) { // if src starts with "./"
    $img_path = (preg_replace('/^\x2e\x2f(.*?)$/', $document_root . $cd . '$1', $attr['src']));
   }
   elseif (preg_match('/^\x2f/', $attr['src'])) { // if src starts with "/"
    $img_path = preg_replace('/^(\x2f)/', $document_root . '$1', $attr['src']);
   }
   elseif (preg_match('/^\x2e\x2e\x2f/', $attr['src'])) { // if src starts with "../"
    $src = $attr['src'];
    while (preg_match('/^\x2e\x2e\x2f/', $src)) {
     $cd  = preg_replace('/(\x2f)[^\x2f]*?\x2f?$/', '$1', $cd);
	 $src = preg_replace('/^\x2e\x2e\x2f/', '', $src);
	}
	$img_path = $document_root . $cd . $src;
   }
   else $img_path = $document_root . $cd . $src;
   if (file_exists($img_path)) {
    $imgsize = getimagesize($img_path);
	if ($imgsize[0] && $imgsize[1]) { $attr['width'] = $imgsize[0]; $attr['height'] = $imgsize[1]; }
   }
  } // end parsing image files
  elseif ($name == '_comment') $out .= '<!-- ' . $attr . ' -->';
  elseif ($name == '_cdata') $out .= '<![CDATA[ ' . $attr . ']]>';
  elseif ($name == 'input'
    && in_array($attr['type'], array('checkbox','radio'))
    && isset($attr['values'])
    && isset($attr['name']) 
  ) {
   $out .= make_checkbox_radio_elements($attr['name'], $attr);
  }
  else $out .= make_tag($name, 'empty', $attr); 
 } // end of empty element
 else {
  $content = (array) $content;
  $end = make_tag($name, 'end');
  $_multiple = count($content) > 1 ;

  if (count($content) == 0 && !$no_tags_if_empty) $out .= make_tag($name, 'start', $attr) . $end;
  else {
   foreach ($content as $i=>$c) {
	$c_clean = rtrim(trim(strip_tags($c)));
    if (
     $no_tags_if_empty
     &&
     (
      ( empty($c) || (!$ignore_tags && empty($c_clean)) )
      ||
      ( $ignore_tags && empty($c_clean) )
     )
    ) {
     $out .= $force_strip_tags ? $c_clean : $c ;
    }
	else {
	 $a = $attr;
	 if ($_multiple && isset($attr['id']) && !empty($attr['id'])) {
	  $a['id'] = sprintf('%s_%s', $a['id'], ++$i);
	 }
     $out .= make_tag($name, 'start', $a) . $c . $end;
	}
   }
  }
 }
 
 // Store HTML Elements in $CustomFunctions_HTMLElements
 if (!isset($CustomFunctions_HTMLElements['elements'])) $CustomFunctions_HTMLElements['elements'] = array();
 $CustomFunctions_HTMLElements['elements'][] = $out;
 if (!isset($CustomFunctions_HTMLElements[$name])) $CustomFunctions_HTMLElements[$name] = array();
 $CustomFunctions_HTMLElements[$name][] = $out;
 if (isset($attr['id']) && is_string($attr['id'])) {
  if (!empty($attr['id']) && !isset($CustomFunctions_HTMLElements[$attr['id']])) $CustomFunctions_HTMLElements[$attr['id']] = array();
  $CustomFunctions_HTMLElements[$attr['id']][] = $out;
 }
 return $out;
}


/* //
function make_html($e) {
 $args = func_get_args();
 $e = array_shift($args);
 $a = array_shift($args);
 
 $c = NULL;
 if (isset($args[0])) {
  if (is_array($args[0])) { $c = $args[0]; }
  else {
   foreach ($args as $v) { $c .= $v; }
  }
 }
 return createHTMLElement($e, $a, $c);
}

function html_div() {
 $e = 'div';
 return call_user_func_array('make_html', array_merge((array) $e, func_get_args()));
}
// echo make_html('div', array('id'=>'var'), 'a','b','<br>','<p>foo</p>');
// */

function html_option_elements($args = null, $single = false) {
 $args = parse_args(array(
  'values' => array(),
  'labels' => array(), // !!!! THIS OPTION IS ORDER SENSITIVE. BE CAREFUL ABOUT THE ORDER OF [LABEL] WHEN PASSING AN ARRAY (NON ASSOCIATIVE ARRAY.) 
  'value' => NULL,
  'name'  => '',
 ), $args);
 if (empty($args['values'])) return;
 $values = array();
 $labels = array();
 foreach ($args['values'] as $v) {
  $values[$v] = $v;
 }
 if (empty($args['labels'])) $args['labels'] = $args['values'];
 if (is_array($args['labels']) && !is_hash($args['labels']) && (array_keys($args['values']) == array_keys($args['labels']))) {
  foreach ($args['values'] as $i=>$v) {
   $labels[$v] = $args['labels'][$i];
  } 
 }
 else $labels = $args['labels'];
 $options = array();
 $default_value = NULL;
 if ($args['value'] === NULL) {
  if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[$args['name']])) $default_value = $_GET[$args['name']];
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$args['name']])) $default_value = (array) $_POST[$args['name']]; 
 }
 else {
  if ((bool) $args['value']) $default_value = $args['value'];
 }
 foreach ($values as $v) {
  $options[] = createHTMLElement(
   'option',
   array_merge(
    array('value'=>$v),
    ($v == $default_value ? array('selected'=>'selected') : array())
   ), 
   createHTMLElement('span', array('class'=>'label_text'), array_value($labels,$v,''))
  );
 }
 if ($single) return implode(LF, $options);
 return $options;
}

function html_select_element($args = null) {
/* ////
// USAGE: //
echo html_select_element( array(
 'name'=>'somename',
 'id'=>'testselect',
 'optgroups'=>array(
  'Group A'=> array('eenie','meenie','miny','moe'),
  'Group B'=> array('catch','the tiger','by', 'the toe'),
  'Group C'=> array('if he', 'hollers','let him', 'go')
 ),
 'labels'=>array(
  'Group A'=> array('EENIE','MEENIE','MINY','MOE'),
  'Group B'=> array('CATCH','THE TIGER','BY', 'THE TOE'),
  'Group C'=> array('IF HE', 'HOLLERS','LET HIM', 'GO')
 )
) );
echo html_select_element( array(
 'name'=>'somename',
 'id'=>'testselect2',
 'values'=>array( 'eenie','meenie','miny','moe' ),
 'labels'=>array('EENIE','MEENIE','MINY','MOE')
) );
//// */

 $atts = parse_args(array(
  'name' => '',
  'size' => '',
  'multiple' => '',
  'id' => '',
  'class' => '',
  'disabled' => FALSE,
  'tabindex' => ''
 ) + make_associative_array(html_attrs()), $args);
 $params = parse_args(array(
  'name'   => $atts['name'],
  'values' => NULL,
  'optgroups' => NULL,
   // accepts: array("group_name"=> array("val1","val2",..));  'group_name' is used in optgroup attr. label
  'labels' => NULL,
   //'labels' accepts: array("group_name"=> array("Value 1","Value 2",..))
  'value'  => NULL
 ), $args);

 $options = '';
 if (!empty($params['optgroups']) && empty($params['values'])) {
  $_labels_is_hash = FALSE
  ;
  foreach ($params['optgroups'] as $grp_name => $grp) {
   $p = parse_args($params, array(
	'values'=>$grp,
	'labels' => isset($params['labels'][$grp_name]) ? $params['labels'][$grp_name] : NULL
   ));
   $options .= createHTMLElement('optgroup', array('label'=>$grp_name), html_option_elements($p, $single=1));
  }
 }
 else {
  $options = html_option_elements($params,$single=1);
 }
 return createHTMLElement('select', $atts, $options);
}


function make_checkbox_radio_elements($name, $attr) {
 $out = '';
 $ids = $labels = array();
 if (!isset($attr['defaults'])) { 
  if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[$name])) $attr['defaults'] = (array) $_GET[$name];
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$name])) $attr['defaults'] = (array) $_POST[$name];
 }
 $attr = parse_args(array(
  'name'		 => $name,
  'type'		 => NULL,
  'values'		 => array(),
  'defaults'	 => NULL,
  'ids'			 => NULL,
  'labels'		 => NULL,
  'disabled'	 => NULL,
  '_id_base'	 => NULL,
  '_id_parts'	 => array('name','order'), // 'name', 'order', 'value', 'id_base'
  '_wrap_with_label' => FALSE,
  '_use_label'	 => TRUE,
  '_use_id'		 => TRUE,
  '_id_format'	 => '%s_%s',
  '_input_and_label_position_format' => '%s%s', // sprintf format for position of input element and label text
 ), $attr);
/* //
 This makes each radio/checkbox element's ID from given attributes:
 $atts['_use_id'] == false to set no ID. 'id'
 If $atts['ids'] given, uses $atts['ids'][index] or $atts['ids'][value] (in this order)
 
 $atts['_id_parts'] and $atts['_id_format'] are used to build IDs. vspritf() is called and use these attributes.
 'id_parts' must be an array and can include one of these: 'name', 'order', 'value', 'id_base'
 'name' name attributes of the input element
 'order' index of the value
 'value' value of the input
 'id_base' same as $attr['_id_base']
 // */

 $attr['values'] = (array) $attr['values'];
 $attr['labels'] = (array) $attr['labels'];
 $attr['_id_parts'] = (array) $attr['_id_parts'];
 $disable = (array) $attr['disabled'];
 
 $id_parts = array(
  'name' => $attr['name'],
  '_id_base' =>  $attr['_id_base'],
 );
 foreach ($attr['values'] as $i => $v) {
  if ($attr['_use_label']) {
   $labels[$attr['values'][$i]] = isset($attr['labels'][$i]) ? $attr['labels'][$i] : $v ;
  }
  if ($attr['_use_id']) {
   $id = '';
   $id_parts['order'] = $i;
   $id_parts['value'] = $v;
   $parts = array();
   foreach ($attr['_id_parts'] as $p) {
    $parts[] = $id_parts[$p];
   }
   $id_base = vsprintf($attr['_id_format'], $parts);
 
   if (isset($attr['ids'][$i]) && !empty($attr['ids'][$i])) $id = $attr['ids'][$i];
   else if (isset($attr['ids'][$v]) && !empty($attr['ids'][$v])) $id = $attr['ids'][$v];
   else {
	$id = vsprintf($attr['_id_format'], $parts) ;
   }
   $ids[$v] = $id;
  }
 }

 foreach ($attr['values'] as $v) {
  $input_attr = $label_attr = array();
  if (!isset($ids[$v])) $ids[$v] = NULL;
  if (!isset($labels[$v])) $labels[$v] = $v;
  
  $input_attr = array(
   'type' => $attr['type'],
   'name' => $attr['name'] . ($attr['type'] == 'checkbox' ? '[]' : ''),
   'value' => $v,
  );
  $_is_disabled = in_array($v, $disable);
  if ($_is_disabled) {
   $input_attr['disabled'] = 'disabled';
   $input_attr['class'] = 'input_'.$input_attr['type'].'_disabled input_disabled';
   $label_attr['class'] = 'input_'.$input_attr['type'].'_disabled_label input_disabled_label';
  }
  if ($attr['_use_label'] !== FALSE) {
   $label_attr['for'] = $ids[$v];
  }
  if ($attr['_use_id'] !== FALSE) {
   $label_attr['id'] = ($ids[$v] ? $ids[$v] : $v) . '_label';
   $input_attr['id'] = $ids[$v];
  }

  if (isset($attr['defaults']) && !empty($attr['defaults']) && in_array($v, (array) $attr['defaults'])) {
    $input_attr['checked'] = 'checked';
  }
  $input = createHTMLElement('input', $input_attr);
  if ($attr['_use_label']) {
   if ($attr['_wrap_with_label']) {
    $out .= createHTMLElement('label', $label_attr, sprintf( $attr['_input_and_label_position_format'], $input, $labels[$v] ) );
   }
   else {
    $out .= sprintf( $attr['_input_and_label_position_format'], $input, createHTMLElement('label', $label_attr, $labels[$v] ) );
   }
  }
  else { $out .= $input ; }
 }
 return $out;
}

function simple_table_rows($a, $attr=null) {
 $html = '';
 $attr = (array) $attr;
 foreach ( (array) $a as $h => $d ) {
  $row = createHTMLElement('th', $attr, $h) . createHTMLElement('td', $attr, $d) . "\n";
  $html .= createHTMLElement('tr', null, $row ) ."\n";
 }
 return $html;
}


function div_table_col($content=NULL, $col_atts=NULL, $col_content_atts=NULL) {
 $col_class = 'table_col';
 $col_content_class = 'table_col_content';
 $col_atts = (array) $col_atts;
 $col_content_atts = (array) $col_content_atts;
 
 if (!isset( $col_atts['class'] )) $col_atts['class'] = array();
 else $col_atts['class'] = (array) $col_atts['class'];
 if (!isset( $col_content_atts['class'] )) $col_content_atts['class'] = array();
 else $col_content_atts['class'] = (array) $col_content_atts['class'];
 
 $col_atts['class'][] = $col_class;
 $col_content_atts['class'][] = $col_content_class;
 
 return createHTMLElement('div', $col_atts, createHTMLElement('div', $col_content_atts, $content) );
}


function HTMLClassAttribute($class, $add=NULL, $stringify=FALSE ) {
 if (is_string( $class )) $class = preg_split('/\s+/', $class);
 $addn = array();
 foreach ((array) $add as $a ) {
  if (is_array($a)) {
   $a = array_flatten($a);
  }
  else if (is_string($a)) {
   $a = preg_split('/\s+/', $a);
  }
  else { continue; }
  $addn = array_merge($addn, $a);
 }
 $classes = array_merge($class, $addn);
 return $stringify ? implode(' ', $classes) : $classes
 ;
}

function URLToPath($str = '', $docroot=NULL) {
 if ($docroot === NULL) $docroot = $_SERVER['DOCUMENT_ROOT'];
 if ($str) return preg_replace('/^https?\x3a\x2f\x2f[^\x2f]*?(\x2f)/', $docroot.'$1', $str);
 return; 
}


function IMG($url, $param=array(), $check_existence=TRUE) {
 $scheme = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])? 's' : '');
 $host = $_SERVER['SERVER_NAME'];
 $document_root = $_SERVER['DOCUMENT_ROOT'];
 $path = '';
 $attr = array();

 if (preg_match('/^https?\x3a\x2f\x2f/', $url)) {
  if (preg_match('/^https?\x3a\x2f\x2f'.$host.'/', $url)) $path = URLToPath($url);
 }
 elseif (preg_match('/^\x2f/', $url)) {
  if (file_exists($url)) {
   $path = $url;
   $url = preg_replace('/^'.preg_replace('/\x2f/','\x2f',$document_root).'/', $scheme.'://'.$host, $url);
  }
  elseif (file_exists($document_root.$url)) {
   $path = $document_root.$url;
  }
 }
 else if (file_exists(getcwd().DIRECTORY_SEPARATOR.$url)) {
  $path = getcwd().DIRECTORY_SEPARATOR.$url;
 }

 if ($path != '' && file_exists($path)) {
  $imagesize = NULL
  ;
  if (
   (!isset($param['width']) || $param['width'] === NULL)
   ||
   (!isset($param['height']) || $param['height'] === NULL)
  ) {
   $imagesize = getimagesize($path);
  }
  $attr['width'] = (!isset($param['width']) || $param['width'] === NULL) ? $imagesize[0] : $param['width'];
  $attr['height'] = (!isset($param['height']) || $param['height'] === NULL) ? $imagesize[1] : $param['height'];
 }
 else return;
 $attr['src'] = $url;

 foreach (qw('alt longdesc usemap ismap onclick ondblclick onmousedown onmouseup onmouseover onmousemove onmouseout onkeypress onkeydown onkeyup lang xml:lang dir id class style title srcset sizes') as $k) {
  if (isset($param[$k])) $attr[$k] = $param[$k];
 }
 foreach (array_keys($param) as $k) {
  if (preg_match('/^data-/', $k)) { $attr[$k] = $param[$k]; }
 }

 if (isset($param['src']) && $param['src']) return $attr['src'];
 if (isset($param['path']) && $param['path']) return $document_root . $attr['src'];
 if (isset($param['array']) && $param['array']) return $attr;
 if (isset($param['display_url']) && $param['display_url']) $attr['src'] = $param['display_url'];
 if (isset($param['style']) && $param['style']) $attr['style'] = $param['style'];
 return createHTMLElement('img', $attr);
}

function get_image_margin_for_vertical_center(//) 
/* //
Calculates CSS margin-top to align the image vertical-center of specified width-height box
// */
 $box_width,
 $box_height,
 $image_width,
 $image_height,
 $round=0,
 $percent=TRUE,
 $prepend_property='margin-top',
 $append_unit='%'
) {
 foreach (array($image_height, $image_width, $box_height, $box_width) as $l) {
  if (empty($l)) return NULL;
 }
 if ( $image_width / $image_height > $box_width / $box_height ) {
  $margin_top_r = ( ($box_height - ($box_width / $image_width) * $image_height) / 2 ) / $box_width;
  $margin_top = $unit = '';
  ;
  if ($percent) {
   $margin_top = $margin_top_r * 100;
   $unit = '%';
  }
  else {
   $margin_top = $margin_top_r * $box_width ;
   $unit = ($append_unit == '%') ? 'px' : $append_unit;
  }
  $prepend_property = $prepend_property ? preg_replace('/(?:\x3a\s*?)?$/', ':', $prepend_property) : '';
  
  $round !== NULL && $margin_top = round($margin_top, $round);
  return $prepend_property . $margin_top . $unit;
 }
 return NULL
;
}

function html_attrs_coreattrs() { return array('id','class','style','title'); }
function html_attrs_i18n() { return array('lang','dir','xml:lang'); }
function html_attrs_events() {
 return array('onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup');
}
function html_attrs() { return array_merge(html_attrs_coreattrs(), html_attrs_i18n(), html_attrs_events()); }

function remove_html_attrs($html, $attr) {
 foreach ((array) $attr as $a) {
  $html = remove_html_attribute($html, $a);
 }
 return $html;
}

function remove_html_attribute($html, $attrname, $quote=array("\x22","\x27")) {
 $attrname = preg_replace('/[^A-Za-z0-9\x2d\x5f]/', '', (string) $attrname);
 foreach ((array) $quote as $q) {
  $q = char2hex($q);
  $html = preg_replace('/ '.$attrname.'\x3d'.$q.'.*?'.$q.'/i', '', $html);
 }
 return $html;
}


function html_img_centering_box($img, $box_w, $box_h, $width=0, $height=0, $modify_img_attr=FALSE, $force_size_box=FALSE, $box_class='img_centering_box') {
 if ($img) {
  $re_width  = '/width=[\x22\x27]?(\d+)[\x22\x27]?/';
  $re_height = '/height=[\x22\x27]?(\d+)[\x22\x27]?/';
  if ($width==0 || $height==0) {
   if ( preg_match($re_width, $img, $w) && preg_match($re_height, $img, $h) ) {
    $width  = intval($w[1]);
    $height = intval($h[1]);
   }
   elseif (preg_match('/src=[\x22\x27]([^\x22\x27]+?)[\x22\x27]/', $img, $s)) {
    $path = preg_replace('/^(\x2f[^\x2f])/', $_SERVER['DOCUMENT_ROOT'].'$1', $s[1]);
    $host = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if ($h = parse_url($path, PHP_URL_HOST)) {
     if ($host == $h) { ; }
    }
    list($width, $height, $r) = getimagesize($path);
   }
   else {
    return $img;
   }
  }
  if ($width > 0 && $height > 0) {
   $html = $box_style = $percent = $pad_1 = $pad_2 = $css_image_width = '';
   if ($width/$height - $box_w/$box_h > 0) { // space on top and bottom
    $percent = $box_h/$box_w - $height/$width ;
    $pad_1 = 'padding-top';
    $pad_2 = 'padding-bottom';
    $css_image_width = '100%';
   }
   else if ($width/$height - $box_w/$box_h < 0) { // space on sides
    $percent = ( $box_w/$box_h - $width/$height ) / ($box_w/$box_h);
    $pad_1 = 'padding-left';
    $pad_2 = 'padding-right';
    $css_image_width = ( (1 - $percent) * 100 ) . '%';
   }
   if ($percent) {
    $pad = (($percent * 100) / 2);
    $box_style = sprintf('%s:%s%%;%s:%s%%;', $pad_1, $pad, $pad_2, $pad);
   }
   if ($modify_img_attr) {
    $img = preg_replace($re_width, 'width="100%"', $img);
    $img = preg_replace($re_height, 'width="100%"', $img);
   }
   $html = createHTMLElement('span', array(
    'style' =>'display:block;'.$box_style,
    'class' => $box_class
   ), $img);
   return $html ;
  }
  return $img;
 }
}


function wrapJavaScript($js = null, $a = array(), $p = array()) {
 if (!$a) $a = array();
 $a = parse_args(array(
  'tag' => true,
  'cdata' => true,
  'jqueryready' => false,
  'jquery' => false
 ), $a);
 
 $p['type'] = 'text/javascript';
 if ($a['jqueryready']) $js = '$(function(){ ' . LF . $js . LF . '});';
 if ($a['jquery']) $js = '(function($){ ' . LF . $js . LF . '})(jQuery);';
 if ($a['cdata']) $js =
  createHTMLElement('_comment', ' // ') .
  createHTMLElement('_cdata', "\n" . $js . ' // ');
 if ($a['tag']) $js = createHTMLElement('script', $p, $js) . "\n";
 return $js;
}



function decode_numeric_refernce($string, $quote_style = ENT_COMPAT, $charset = "utf-8") {
 $string = html_entity_decode($string, $quote_style, $charset);
 return $string; 
}


function base64_encode_urlsafe($s){
	$s = base64_encode($s);
	return(str_replace(array('+','=','/'),array('_','-','.'),$s));
}
function base64_decode_urlsafe($s){
	$s = (str_replace( array('_','-','.'), array('+','=','/'), $s));
	return(base64_decode($s));
}


function age_by_date($date){
	$year_diff = '';
	$time = strtotime($date);
	if (false === $time) return false;

	$date = date('Y-m-d', $time);
	list($year,$month,$day) = explode('-',$date);
	$year_diff = date("Y") - $year;
	$month_diff = date("m") - $month;
	$day_diff = date("d") - $day;
	if ($day_diff < 0 || $month_diff < 0) $year_diff--;

	return $year_diff;
}


function truncate_html($html, $length, $ellip='...', $refine=TRUE) {
 $text_length = 0;
 $length = floor($length);
 $truncated = '';
 preg_match_all('/<[^\x3c\x3e]*?>/', $html, $tags); // Matching start tags.
 if (empty($tags[0])) { // No tags found.
  return mb_substr($html, 0, $length) . ( mb_strlen($html) > $length ? $ellip : '' );
 };
 foreach ($tags[0] as $tag) {
  $re = '/([^\x3c\x3e]*?)('.str_replace('/', "\x5c\x2f", preg_quote($tag)).')/';
  preg_match($re, $html, $match);
  $text = trim($match[1]);
  $len = mb_strlen($text); 
  if ($text_length < $length) {
   if ($text_length + $len < $length) { $text_length += mb_strlen($text); $truncated .= $text; }
   else {
    $text = mb_substr($text, 0, $length - $text_length) . $ellip;
    $text_length += mb_strlen($text);
    $truncated .= $text;
   }
  }
  if (!($refine && preg_match('/\x3c(?:br)\x20?\x2f?\x3e/', $match[2]))) {
   $truncated .= $match[2];
  }
  if ($refine) {
  
  }
  $html = preg_replace($re, '', $html, 1);
 }
 $html = trim($html); 
 $truncated .= ($text_length < $length ? mb_substr($html, 0, $length - $text_length) : '') ;
 return $truncated;
 // mb_convert_encoding(phpQuery::newDocument($truncated)->htmlOuter(), UTF8, 'HTML-ENTITIES') to refine HTML
}

function remove_html_tag_without_content($content, $tags, $recursive=TRUE) {
 $tags = (array) $tags;
 foreach ($tags as $t) {
  $re = '{<'.$t.'[^>]*?></'.$t.'>}';
  if ($recursive) {
   $content = preg_replace($re, '', $content);
  }
 }
 return $content;
}

function generatePassword($length=9, $strength=0) {
	$vowels = 'aeuy';
	$consonants = 'bdghjmnpqrstvz';
	if ($strength >= 1) {
		$consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength >= 2) {
		$vowels .= "AEUY";
	}
	if ($strength >= 4) {
		$consonants .= '23456789';
	}
	if ($strength >= 8 ) {
		$vowels .= '@#$%';
	}

	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
		if ($alt == 1) {
			$password .= $consonants[(rand() % strlen($consonants))];
			$alt = 0;
		} else {
			$password .= $vowels[(rand() % strlen($vowels))];
			$alt = 1;
		}
	}
	return $password;
}


function parse_args($defaults, $args, $recursive=FALSE) {
 $a = array();
 $defaults = (array) $defaults;
 $args = (array) $args;
 foreach ($defaults as $k => $v) {
  if (isset($args[$k])) {
   if ($recursive && is_array($defaults[$k])) {
    $a[$k] = parse_args($defaults[$k], (array) $args[$k], $recursive);
   }
   else {
    $a[$k] = $args[$k];
   }
  }
  else {
   $a[$k] = $defaults[$k];
  }
 }
 return $a;
}


function get_IE_version() {
 $match = preg_match('/MSIE ([0-9]\.[0-9])/', $_SERVER['HTTP_USER_AGENT'], $reg);
  if ($match == 0) return -1;
  else return floatval($reg[1]);
}


function is_IE6() { return (get_IE_version() == 6); }


function get_location_header_url($h=null) {
 global $http_response_header; // my_print_r(__FUNCTION__); my_print_r($http_response_header);
 if (empty($h)) $h = $http_response_header;
 $lh_re = '/^Location: ?/';
 if (is_array($h)) {
  foreach ($h as $r) {
   if (preg_match($lh_re, $r)) {
    $r = preg_replace($lh_re, '', $r); return $r;
    $url = parse_url($r);
    $queries = array();
	foreach (explode('&', $url['query']) as $kv) {
	 $kv = explode('=', $kv);
	 $queries[$kv[0]] = $queries[$kv[1]];
	}
	return $r;
   }
  }
 }
 return null;
}


function from_same_host($referer=null, $host=null) {
 $s = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] :$_SERVER['SERVER_NAME'];
 if (!$referer) { $referer = $_SERVER['HTTP_REFERER']; }
 if (!$referer) return false;
 
 if (!$host) { $host = $s; }
 $r = parse_url($referer);
 return $r['host'] == $host;
}


function get_cookies($h=null) {
 global $http_response_header;
 if (empty($h)) $h = $http_response_header;
 $ca = array();
 foreach ($h as $i=>$r) {
  if (strpos($r, 'Set-Cookie') === false) continue;
  $c = explode(' ', $r);
  $cc = explode('=', str_replace(';', '', $c[1]));
  if (!($ca[$cc[0]])) $ca[$cc[0]] = $cc[1];
 }
 return $ca;
}


function cookies_array($c=NULL, $_include_empty_value=FALSE) {
 if (empty($c)) return array();
 $cookie = array();
 foreach ($c as $k=>$v) {
  if ((empty($v) && $_include_empty_value) || !empty($v)) $cookie[] = $k.'='.$v;
 }
 return $cookie;
}


function http_request_simple($url, $data, $meta=null, $scheme='http') {
 global $http_post_simple_response;
 $u = parse_url($url);

 $headers = parse_args(array(
  'cookie' => '',
  'referer' => '',
  'user-agent' => '',
  'accept-language' => '',
  'host' => $u['host']
 ), $meta);
 $meta = parse_args(array(
  'method' => 'GET',
 ), $meta);

 if (isset($headers['cookie']) && $headers['cookie']) {
  $headers['cookie'] = http_cookie_simple_build($headers['cookie']);
 }

 $header = '';
 $post_data = array();
 $meta['method'] = strtoupper($meta['method']);
 
 $data = http_build_query((is_object($data) || is_array($data)? $data : (array) $data), "", "&");
 if (!is_string($data)) return null;

 if ($meta['method'] == 'POST') {
  $header
   .= "Content-type: application/x-www-form-urlencoded" . CRLF
   .  "Content-Length: " . strlen($data) . CRLF
  ;
  $post_data = array( 'content' => $data );
 }
 else {
  if ($data) $url .= '?' . $data;
 }

 foreach ($headers as $f=>$h) {
  if ($h) {
   $fieldname = ucfirst(
    preg_replace_callback('/(?<=-)([a-z])/', function($m) { return strtoupper($m[0]); }, $f)
   ) . ': ';
   $h = trim(preg_replace('/^(?:'.$fieldname.'?)*/', $fieldname, $h));
   $h = $h . CRLF;
   $header .=  $h;
  }
 }

 $option = array($scheme => array_merge(
  array(
   'method' => $meta['method'],
   'header' => $header,
  ),
  $post_data
 ) );
 //my_print_r($option);
 $d = file_get_contents($url, false, stream_context_create($option));
 $http_post_simple_response = $http_response_header;
 return $d;
}


function http_post_simple($url, $data, $meta=array(), $scheme = 'http') {
 $meta['method'] = 'POST';
 return http_request_simple($url, $data, $meta, $scheme);
}
function http_get_simple($url, $data, $meta=array(), $scheme = 'http') {
 $meta['method'] = 'POST';
 return http_request_simple($url, $data, $meta, $scheme);
}

function get_http_post_simple_response($parse=NULL) {
 global $http_post_simple_response;
 if ($parse) {
  $_r = array();
  foreach ((array) $http_post_simple_response as $r) {
   $a = explode(': ', $r);
   $_r[$a[0]] = $a[1];
  }
  return $_r;
 }
 return $http_post_simple_response;
}


function http_cookie_simple_build($data) {
 $cookie = '';
 if (!empty($data) && is_hash($data)) {
  $_ca = array();
  foreach ((array) $data as $k=>$v) {
   $_ca = $k.'='.$v;
  }
  $data = $_ca;
 }
 if (is_array($data)) {
  $cookie = implode('; ', $data);
 }
 else $cookie = $data;

 if ($cookie) {
  $cookie = preg_replace('/^(?:Cookie: ?)*/', 'Cookie: ', $cookie);
  $cookie = preg_replace('/(?:\x0d\x0a)+$/' , CRLF, $cookie);
 }
 return $cookie;
}

function get_attachment_filename($response=NULL) {
 if (!$response) $response = get_http_post_simple_response();
 if ($response) {
  foreach ((array) $response as $r) {
   if (preg_match('/^Content-Disposition: attachment; filename=(.+?)$/', $r, $m)) {
    return $m[1];
    break;
   }
  }
 }
 return NULL;
}


function current_url() {
 return('http'. ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
}


function array_to_table($array, $columns = 1, $table_atts = array()) {
 $array_count = 1;
 $table = createHTMLElement('table', 'start', $table_atts);
 if (isset($table_atts['caption']) && $table_atts['caption']) $table .= createHTMLElement('caption', $table_atts['caption']);
 foreach ((array) $array as $k=>$v) {
  if (preg_match('/^_/', $k)) continue;
  if ($array_count % $columns == 1) $table .= createHTMLElement('tr', 'start');
  $table .= createHTMLElement('th', null, $k) . createHTMLElement('td', null, $v);
  if ($array_count % $columns == $columns ) $table .= createHTMLElement('tr', 'end');
  $array_count ++;
 }
 $table .= createHTMLElement('table', 'end');
 return $table;
}



function calc_month($year, $month, $day, $diff, $format = null) {
 $month += $diff;
 $endDay = end_day_of_month($year, $month);
 if($day > $endDay) $day = $endDay;
 $time = mktime(0, 0, 0, $month, $day, $year); // Regularization
 if ($format) return date($format, $time);
 return $time;
}
function end_day_of_month($year, $month) {
 // pass 0 as date number to mktime and get the end of the previous month
 // $month + 1 may result the month number 13, is to be fixed automatically
 return date("d", mktime(0, 0, 0, $month + 1, 0, $year));
}
function calc_day($year, $month, $day, $diff, $format = null) {
 $baseSec = mktime(0, 0, 0, $month, $day, $year); // base date in seconds
 $addSec = $diff * 86400; // number of days in seconds
 if ($format) return date($format,  $baseSec + $addSec);
 return  $baseSec + $addSec;
}


function same_day($time1, $time2=null) {
 return (date('Y-m-d',$time1) == date('Y-m-d',$time2));
}
function in_same_month($time1, $time2=null) {
 return (date('Y-m',$time1) == date('Y-m',$time2));
}


function format_price($price, $atts=null, $html_atts=null) {
 $atts = parse_args(array(
  'unit' => '',
  'unit_prefix' => '￥',
  'unit_suffix' => '円',
  'decimals' => 0,
  'placing' => true,
  'unit_position' => 1, // prefix: -1; none: 0; suffix: 1
 ), $atts );
 $html_atts = parse_args(array(
  'price_class' => 'price',
  'unit_class' => 'price_unit',
  'wrap' => true,
  'wrapper_class' => 'price_box'
 ), $html_atts);

 switch ($atts['unit_position']) {
  case -1:
   if ($atts['unit']) { $atts['unit_prefix'] = $atts['unit']; }
   else { $atts['unit'] = $atts['unit_prefix']; }
  break;
  case  0:
   $atts['unit'] = '';
  break;
  case  1:
   if ($atts['unit']) { $atts['unit_suffix'] = $atts['unit']; }
   else { $atts['unit'] = $atts['unit_suffix']; }
  break;
 }

 $price = preg_replace('/[^\d\x2e]/', '', $price);
 if ($atts['placing']) $price = number_format($price, $atts['decimals']);

 $unit = $atts['unit'] ? createHTMLElement('span', array('class'=>$html_atts['unit_class']), $atts['unit']) : '';

 $formatted = sprintf('%s%s%s', 
  ($atts['unit_position']== -1 ? $unit : ''),
  createHTMLElement('span', array('class'=>$html_atts['price_class']), $price),
  ($atts['unit_position']==  1 ? $unit : '')
 );
 if ($html_atts['wrap']) {
  $formatted = createHTMLElement('span', array('class'=>$html_atts['wrapper_class']), $formatted);
 }
 return $formatted;
}

function format_prices($prices, $atts=NULL, $html_atts=NULL) {
 $p = array();
 foreach((array)$prices as $price) { $p[] = format_price($price, $atts, $html_atts); }
 return $p;
}

if (!function_exists('my_print_r')) {
function my_print_r($a = null, $commentout = null, $verbose = false) {
// if (headers_sent()) {
  echo LF;
  echo($commentout?'<!--':'<pre>'); if ($verbose) var_dump($a); else print_r($a); echo($commentout?'-->':'</pre>');
  echo LF;
// }
}

function make_associative_array($keys, $values=null) {
 $values = (array) $values;
 $a = array();
 foreach ((array) $keys as $k) {
  $a[$k] = isset($values[$k]) ? $values[$k] : null;
 }
 return $a ;
}
}

function has_caller($func, $class=NULL) {
 foreach (debug_backtrace() as $b) {
  if (
   (isset($b['function']) && $b['function'] == $func)
    &&
   ($class ? (isset($b['class']) && $b['class'] == $class) : TRUE)
  ) {
   return TRUE;
  }
 }
 return FALSE;
}

function default_value($value, $fallback, $consider_zero=TRUE) {
 if ($consider_zero && $value === 0) return $value;
 if (empty($value)) return $fallback;
 return $value;
}

function quote_string($string, $quote="\x22") {
 return $quote . str_replace("\x22", "\x5c\x22", $string) . $quote;
}

function remove_anchor($url, $content, $remove_entire_tag=FALSE) {
 $full_url = '';
 $u = array();
 parse_url($url);
 $root = sprintf('%s://%s', $u['scheme'], $u['host']);
 $path = $u['path'];
 if (isset($u['query'])) $full_url .= '?'. $u['query'];
 $re = '/(\x3ca[^>]+)(href=(?:\x27|\x22))(' . str_replace('/', '\x2f', '(?:' . $root . ')?' . preg_quote($path)) . ')(\x22|\x27)(\x20?.*?\x3e)(.*?)(\x3c\x2fa\x3e)/';

 return preg_replace($re, ($remove_entire_tag? '$6' : '$1$5$6$7'), $content);
}


function get_image_dimensions_to_fit_to_box($fit_width, $fit_height, $width, $height, $percent=TRUE, $round=0) {
 if (empty($height) || empty($width)) {
  return NULL;
 }
 $image_ratio = $width / $height;
 $zoom_x = $fit_width / $width;
 $zoom_y = $fit_height / $height;
 $fit_side = $zoom_x <= $zoom_y ? 'x' : 'y';
 if ($percent) {
  return $fit_side == 'x' ? array('100%','') : array('','100%');
 }
 else {
  $ratio = $zoom_x <= $zoom_y ? $zoom_x : $zoom_y;
  return array(round($width * $ratio, $round), round($height * $ratio, $round));
 }
}


function swap_boolean_value($bool, $value=NULL, $strict=FALSE) {
 // Swaps a boolean value to human readable string. If $strict evaluates only ( false | null ) as false
 $value = parse_args(array(0,1),$value);
 $strict = (bool) $strict;
 if (( $strict && $bool !== NULL && $bool !== FALSE) || $bool ) return $value[1];
 else return $value[0];
}

function char2hex($suppose_char, $prefix=TRUE) {
 $hex = '';
 if (preg_match('/^(?:\x5c)(?:x)([0-9a-fA-F]{2})/', $suppose_char, $m)) {
  $hex = $m[1];
 }
 else {
  $hex = dechex(ord($suppose_char[0]));
 }
 if ($hex) {
  $prefix = ($prefix === TRUE ? '\x' : ($prefix ? $prefix : ''));
  return $prefix . $hex;
 }
}

// Thanks to: http://php.net/manual/ja/function.html-entity-decode.php
/* // > Here is the ultimate functions to convert HTML entities to UTF-8 // */
function chr_utf8($code) 
 { 
  if ($code < 0) return false; 
  elseif ($code < 128) return chr($code); 
  elseif ($code < 160) // Remove Windows Illegals Cars 
  { 
   if ($code==128) $code=8364; 
   elseif ($code==129) $code=160; // not affected 
   elseif ($code==130) $code=8218; 
   elseif ($code==131) $code=402; 
   elseif ($code==132) $code=8222; 
   elseif ($code==133) $code=8230; 
   elseif ($code==134) $code=8224; 
   elseif ($code==135) $code=8225; 
   elseif ($code==136) $code=710; 
   elseif ($code==137) $code=8240; 
   elseif ($code==138) $code=352; 
   elseif ($code==139) $code=8249; 
   elseif ($code==140) $code=338; 
   elseif ($code==141) $code=160; // not affected 
   elseif ($code==142) $code=381; 
   elseif ($code==143) $code=160; // not affected 
   elseif ($code==144) $code=160; // not affected 
   elseif ($code==145) $code=8216; 
   elseif ($code==146) $code=8217; 
   elseif ($code==147) $code=8220; 
   elseif ($code==148) $code=8221; 
   elseif ($code==149) $code=8226; 
   elseif ($code==150) $code=8211; 
   elseif ($code==151) $code=8212; 
   elseif ($code==152) $code=732; 
   elseif ($code==153) $code=8482; 
   elseif ($code==154) $code=353; 
   elseif ($code==155) $code=8250; 
   elseif ($code==156) $code=339; 
   elseif ($code==157) $code=160; // not affected 
   elseif ($code==158) $code=382; 
   elseif ($code==159) $code=376; 
  } 
  if ($code < 2048) return chr(192 | ($code >> 6)) . chr(128 | ($code & 63)); 
  elseif ($code < 65536) return chr(224 | ($code >> 12)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63)); 
  else return chr(240 | ($code >> 18)) . chr(128 | (($code >> 12) & 63)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63)); 
 } 

 // Callback for preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $str); 
 function html_entity_replace($matches) 
 { 
  if ($matches[2]) 
  { 
   return chr_utf8(hexdec($matches[3])); 
  } elseif ($matches[1]) 
  { 
   return chr_utf8($matches[3]); 
  } 
  switch ($matches[3]) 
  { 
   case "nbsp": return chr_utf8(160); 
   case "iexcl": return chr_utf8(161); 
   case "cent": return chr_utf8(162); 
   case "pound": return chr_utf8(163); 
   case "curren": return chr_utf8(164); 
   case "yen": return chr_utf8(165); 
   //... etc with all named HTML entities 
  } 
  return false; 
 } 
 
 function htmlentities2utf8 ($string) // Fix of the html_entity_decode() bug with UTF-8 
 { 
  $string = preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $string);
  return $string; 
 } 
/* // END OF ultimate functions to convert HTML entities to UTF-8 // */

 function str_indent($str, $tab="\t") {
  $str = preg_replace('/^/', $tab, $str); 
  $str = preg_replace('/(\n)/', '$1'.$tab, $str); 
  return $str
  ;
 }
 
 function list_directory_entries($dir, $recursive=TRUE, $tab="\t", $full_path=FALSE){
  $dir_list = '';
  $sep = "/";
  $indent = $full_path ? $dir . $sep : $tab;
  $self = __FUNCTION__
  ;
  if ($recursive && is_dir($dir)) {
   $dir_list .=  ($full_path ? $dir . $sep : basename($dir) . $sep) . LF ;
   if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
     if (preg_match('/^\x2e{1,2}$/', $file)) { continue; }
	 if (is_dir($dir . $sep . $file)) {
	  $i = $indent;
	  if ($full_path) $i = preg_replace('{^'.$dir.'/}', '', $i);
	  $dir_list .= str_indent(preg_replace('/[\r\n]$/', '', $self($dir . $sep . $file, $recursive, $indent, $full_path)), $i) . LF;
     }
     else {
       $dir_list .= $indent . $file . LF;
     }
    }
    closedir($dh);
   }
  }
  return $dir_list
  ;
 }
 
 
 function make_javascript_array($array, $return='STRING' /* // could be 'ARRAY' to get array of quoted values // */ ) {
  $array = (array) $array ;
  $array_out = array();
  foreach ($array as $i=>$v) {
   $array_out[$i] = sprintf('"%s"', str_replace('"', '\"', $v));
  }
  if ($return == 'STRING') {
   return sprintf('[%s]', implode(',', $array_out));
  }
  return $array_out
  ;
 }
} // END IF DEFINED 'CUSTOM_FUNCTIONS_LOADED'