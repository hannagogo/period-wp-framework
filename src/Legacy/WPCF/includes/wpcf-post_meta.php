<?php
function get_multi_post_meta($post_id, $key, $single=FALSE, $index=NULL) {
 $meta = (array) get_post_meta($post_id, $key, FALSE);
 $a = array();
 if ( count($meta) == 1 ) {
  /* //
   Beaware in a case like below this returns a multi dimensional array (array of arrays)
   array(
    0 => array( v1, v2, ... )
	1 => array( w1, w2, ... ) // Occurs only with CHECKBOXES, RADIOS, SELECTS. 
	2 => array( x1, x2, ... ) // ditto 
   )
  // */
  while (isset($meta[0]) && is_array($meta[0])) $meta = $meta[0];
  $a = array_merge($a, (array) $meta);
 }
 else {
  if ($single) {
   foreach ($meta as $m) {
    while (isset($m[0]) && is_array($m[0])) $m = $m[0];
	$a = array_merge($a, (array) $m[0]);
   }
  }
  else { $a = $meta; }
 }
 return $single ?
  implode('', $a)
  :
  ($index !== NULL ? $a[$index] : $a)
 ;
}


function get_multi_post_custom($post_id) {
 $post_custom = get_post_custom($post_id);
 $meta = array();
 foreach ($post_custom as $key=>$value) {
  $meta[$key] = get_multi_post_meta($post_id, $key);
 }
 return $meta
 ;
}


function query_post_meta($key, $value, $like=FALSE, $consider_serialized=TRUE, $operator="=", $concat="OR") {
 global $wpdb;
 $key = (string) $key;
 $value = (string) $value;
 $q_serialized = serialize(array($value)) ;
 if (empty($operator)) $operator = '=';

 $q = "SELECT * FROM $wpdb->postmeta WHERE"
	. "   (meta_key = '".$key."' AND meta_value ".$operator." '".$value ."')"
 ;
 if ($consider_serialized) {
  $q .= $concat . " (meta_key = '".$key."' AND meta_value ".$operator." '%s')";
 }

 $query = $wpdb->prepare($q, $q_serialized);
//my_print_r($query);
//my_print_r($wpdb->get_results($query));
 return $wpdb->get_results($query);
}


function query_post_ids_by_post_meta($key, $value, $post_type='post', $operator="=", $concat="OR", $posts_per_page=-1) {
 $ids = array();
 foreach ( query_post_meta($key, $value, false, true, $operator, $concat) as $meta) {
  if (!in_array($meta->post_id, $ids)) $ids[] = $meta->post_id;
 }
 return $ids;
}
function query_posts_by_post_meta($key, $value, $post_type='post', $operator="=", $concat="OR", $posts_per_page=-1) {
 $ids = query_post_ids_by_post_meta($key, $value, $post_type, $operator, $concat, $posts_per_page);
 if (!empty($ids)) {
  $posts = get_posts(array('post_type'=>$post_type, 'post__in'=>$ids, 'posts_per_page'=>$posts_per_page));
  return $posts;
 }
 return array();
}



function post_meta_table($args=NULL) {
 global $wp_custom_functions;
 $args = $wp_custom_functions->parse_args( array(
   'post'		 => NULL,
   'fields'		 => array(),
   'columns'	 => 1,
   'omit_fields' => NULL,
   'omit_boxes'	 => NULL,
   'hide_hidden' => TRUE,
   'concat'		 => '<br />',
   'linebreak'	 => TRUE,
   'show_th'	 => TRUE,
  ), $args
 );
 extract($args);

 $post = get_post($post);
 $meta_values = wpcf_get_post_meta_values($args);
 $omit_fields = apply_filters( WPCF_PREFIX.'Post_Meta_Table_Omit_Keys', (array) $omit_fields );
 $count = 0;
 $attr_class = 'post_meta_table';
 $attr_id = $attr_class.'_'.$post->ID;
 if ($columns <= 0) $columns = 1;
 $lines = array( createHTMLElement( 'table', 'start', array('class'=>$attr_class, 'id'=>$attr_id) ) );
 if (empty($fields)) $fields = array_keys($meta_values);

 foreach ( $fields as $k ) {
  if (in_array($k, $omit_fields)) continue ;
  $v = $meta_values[$k];
  if ($count % $columns == 0) { $lines[] = '<tr>'; }
  if ($show_th) {
   $lines[] = createHTMLElement( 'th',
    array('class'=>array($attr_class.'_th', $attr_id.'_th'), 'id'=>$attr_id.'_th_'.$k),
    isset($v['label']) ? $v['label'] : $k
   );
  }
  unset($v['label']);
  $lines[] = createHTMLElement( 'td',
   array('class'=>array($attr_class.'_th', $attr_id.'_td'), 'id'=>$attr_id.'_td_'.$k),
  implode($concat, $v) );
  if ($count % $columns == $columns - 1) { $lines[] = '</tr>'; }
  $count++;
 }
 $lines[] = createHTMLElement('table', 'end');
 return implode($linebreak ? LF : '', $lines);
}


function wpcf_get_post_meta_values($args=NULL) {
 global $wp_custom_functions;
 $args = $wp_custom_functions->parse_args(array(
  'post'		 => NULL,
  'single'		 => FALSE,
  'omit_boxes'	 => NULL, // box names
  'include'		 => NULL,
 ), $args );
 extract($args);
 $post = get_post($post);
 if ($omit_boxes === NULL) $omit_boxes = array('post_settings', 'post_meta_text');
 $omit_boxes = apply_filters( WPCF_PREFIX.'Get_Post_Meta_Values_Omit_Boxes', $omit_boxes );
 $omit_boxes = (array) $omit_boxes;
 $post_type = apply_filters('WPCF_PostType', $post->post_type);

 if ($post_type instanceof PostType) {
  $values = array();
  foreach ($post_type->get_meta_boxes() as $meta_box) {
   if ($meta_box instanceof MetaBox) {
    if (in_array($meta_box->name(), $omit_boxes) || (is_array($include) && !in_array($meta_box->name(), $include)) ) { continue; }
    foreach ($meta_box->get_field_names() as $field) {
     $values[$field] = array_merge( array('label'=>$meta_box->get_field_label($field)), get_multi_post_meta($post->ID, $field, $single) );
    }
   }
  }
  return $values ;
 }
 else {
  return get_post_custom($post->ID);
 }
}


function wpcf_get_existing_meta_values($args) {
 global $wpdb, $wp_custom_functions
;
 if (is_string($args)) {
  $k = $args;
  $args = array(
  'key' => $k,
  '_only_values' => TRUE,
  'exclude_revision' => TRUE,
  '_hide_empty' => TRUE,
  );
 }
 $args = $wp_custom_functions->parse_args(
  array(
   'key'   => NULL,
   'order' => TRUE,
   'exclude_revision' => FALSE,
   '_only_values' => FALSE,
   '_hide_empty' => TRUE,
  ), $args
 );
// post_type=revision 
// extract($args);
 $list = array();
 if (empty($args['key'])) {
  return $list ;
 }

 $q = "SELECT * FROM $wpdb->postmeta WHERE meta_key='%s'";
 if ($args['exclude_revision']) {
//  $q .= " AND post_type<>'revision'";
 }
// $values = $wpdb->get_results($q); 
 $values = $wpdb->get_results($wpdb->prepare($q, $args['key']));
 foreach ((array) $values as $v) {
  $meta_value = maybe_unserialize($v->meta_value);
  if ($args['_hide_empty'] && empty($meta_value)) continue ;
  foreach ((array) $meta_value as $mv) { // if (is_specific_user_logged_in(1)) { my_print_r($mv); }
   if (is_array($mv) && isset($mv[0])) {
    $mv = isset($mv[0]);
   }
   if (!isset($list[$mv])) { $list[$mv] = 1; }
   else { $list[$mv]++; }
  }
 }
 if ($args['order']) asort($list);
 return $args['_only_values'] ? array_keys($list) : $list;
}


function sc_post_meta_table($args) {
 global $wp_custom_functions, $post;
 $args = $wp_custom_functions->parse_args(array(
  'fields'=>NULL, 'columns'=>1, 'omit_fields'=>NULL, 'concat'=>'<br />', 'linebreak'=>1, 'hide_hidden'=>1, 'show_th'=>1
 ), $args);
 foreach (array('fields', 'omit_fields') as $f) {
  if (!is_array($args[$f])) {
   if ($args[$f] === NULL) continue ;
   $args[$f] = explode(',', $args[$f]);
  }
 }
 return post_meta_table($args);
}
add_shortcode('post_meta_table', 'sc_post_meta_table');


function get_posts_by_meta_value($args=NULL) {
 global $wpdb
 ;
 $args = (array) $args;
 $_arguments = func_get_args();
 if (is_string($_arguments[0])) {
  if (count($_arguments) == 1) $args['value'] = $_arguments[0];
  if (count($_arguments) >= 2) {
   $args['key'] = $_arguments[0];
   $args['value'] = $_arguments[1];
   if (isset($_arguments[2])) $args['strict'] = (bool) $_arguments[2];
   if (isset($_arguments[3])) $args['object'] = (bool) $_arguments[3];
   
  }
 }
 elseif (!is_hash($args)) {
  $args['value'] = $args;
 }
 if (isset($args['meta_key']) && !isset($args['key'])) $args['key'] = $args['meta_key'];
 if (isset($args['meta_value']) && !isset($args['value'])) $args['value'] = $args['meta_value'];
 $args = parse_args(array(
  'key'		 => NULL,
  'value'	 => array(), // as: array('key'=>array('val1','val2'))
  'strict'	 => false,
  'object' => TRUE,
 ), $args);

 if (empty($args['value'])) return;
 $args['key'] = (string) $args['key'];
 $args['value'] = (array) $args['value'];
 $q = $_q_key = '';
 $_q_value = "meta_value like '%%%s%%'";
 $q_parts = $q_values = array();
 
 $q = "SELECT * FROM $wpdb->postmeta WHERE ";
 if ($args['key']) $_q_key = "meta_key = '%s' AND ";
 foreach ($args['value'] as $v) {
  $v = (string) $v;
  $q_parts[] = $_q_key . $_q_value;
  if ($args['key']) $q_values[] = $args['key'];
  $q_values[] = $v;
  if ($args['strict']) {
   $q_parts[] = $_q_key . $_q_value;
   if ($args['key']) $q_values[] = $args['key'];
   $q_values[] = serialize(array($v));
  }
 }

 $q .= implode(' OR ', array_map(function($i) { return "($i)"; }, $q_parts));
// my_print_r($args);
 $query = $wpdb->prepare($q, $q_values);
 $post_ids = $elected = array();

 foreach ($wpdb->get_results($query) as $meta) {
  $id = $meta->post_id;
  if (!in_array($id, $post_ids)) {
   $post_ids[] = $id;
   if ($args['object']) $elected[] = get_post($id);
  }
 }; // my_print_r($elected);
 return $args['object'] ? $elected : $post_ids
 ;
}


function wpcf_meta_value_is_empty() {
 $values = apply_filters('WPCF_Get_Post_Meta', $post->ID, $k, FALSE);
 $_has_value = FALSE;
 foreach ($values as &$_value) {
  $_value = apply_filters('WPCF_Meta_Value_Empty_Evaluation', $_value);
 }
 unset($_value);
 return array_values_empty($values) ;
}
add_filter('WPCF_Meta_Value_Empty_Evaluation', function($v) { return $v; });


function wpcf_get_post_meta_text($box = NULL, $filter = TRUE, $post = NULL, $filter_name = '') {
 // $box supposed to be one of the followings: 'post_header', 'post_header_above'
 if (!$box) return NULL ;
 if (!($post = get_post($post))) return NULL ;
 $meta = apply_filters('WPCF_Get_Post_Meta', $post->ID, $box, FALSE);
 $meta = isset($meta[0])? $meta[0] : '';
 if ($filter) {
  foreach ((array) $filter_name as $f) {
   if (filter_exists($f)) $meta = apply_filters($f, $meta);
  }
 }
 return $meta;
}


