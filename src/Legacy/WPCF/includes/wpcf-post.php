<?php
/* ////// Post Utilities ////// */
function wpcf_add_post_types($post_types = array()) {
 if (!is_array($post_types)) return;
 foreach ($post_types as $p) wpcf_add_post_type($p);
 return $post_types;
}

function wpcf_add_post_type(&$post_type) {
 global $wpcf_post_types
 ;
 if (!is_object($post_type) || (get_class($post_type) != 'PostType')) return;
 return $wpcf_post_types[$post_type->name()] = $post_type;
}

function &wpcf_get_post_type($post_type) {
 global $wpcf_post_types
 ;
 return $wpcf_post_types[$post_type];
}

function &wpcf_post_types() { global $wpcf_post_types; return $wpcf_post_types; }


function get_current_post_type($post=NULL, $wp_query=NULL, $object=TRUE) {
 global $typenow, $current_screen
 ;
 $type = NULL ;
 if ($post = get_post($post)) return $post->post_type ;
 if ($wp_query === NULL || !($wp_query instanceof WP_Query)) {
  global $wp_query;
 }

 if ( $typenow ) $type = $typenow; //check the global $typenow - set in admin.php
 elseif ( $current_screen && $current_screen->post_type ) {
  $type = $current_screen->post_type; //check the global $current_screen object - set in sceen.php
 }
 elseif ( isset( $_REQUEST['post_type'] ) && !empty( $_REQUEST['post_type'] ) ) {
  $type = sanitize_key( $_REQUEST['post_type'] );
 }
 if ($type) return $type; // exits here.

 if (
  (isset($wp_query->queried_object->name) && $type = $wp_query->queried_object->name)
  ||
  (isset($wp_query->queried_object->post_type) && $type = $wp_query->queried_object->post_type)
  ||
  (isset($wp_query->query_vars['post_type']) && $type = $wp_query->query_vars['post_type'])
  ||
  (isset($wp_query->query['post_type']) && $type = $wp_query->query['post_type'])
 ) {
  return $object ? get_post_type_object($type) : $type;
 }
 
 return NULL ;
}


function previous__is_specific_post_type($type = 'post', $post = NULL) {
 $post = get_post( $post );
 $types = (array) $type;
 foreach ($types as $t) {
  if ($post) { if ($t == get_current_post_type($post,NULL,FALSE)) return $t; }
  else { if ($t == get_current_post_type(NULL,NULL,FALSE)) return $t; }
 }
 return FALSE ;
}

function is_specific_post_type($type = 'post', $post = NULL) {
 /////// THIS IS NEW VERSION. RETURNS TRUE BUT OTHER VALUE SUCH AS STRING, ETC
 $post = get_post( $post );
 $types = (array) $type;
 $_type_is_array = is_array($type);
 foreach ($types as $t) {
  $_post_type_matched = $post ? ($t == get_current_post_type($post,NULL,FALSE)) : ($t == get_current_post_type(NULL,NULL,FALSE));
  if ($_post_type_matched) {
   return $_type_is_array ? $t : TRUE ;
  }
 }
 return FALSE ;
}

function is_custom_post_type_archive($type=NULL, $wp_query=NULL) {
 if ($wp_query === NULL || !($wp_query instanceof WP_Query)) {
  global $wp_query;
 }
 $is_specific_post_type = is_specific_custom_post_type($type);
 return (is_archive() && $is_specific_post_type) ? $is_specific_post_type : false;
}


function is_specific_custom_post_type($type='', $p=NULL, $post_types_args=array()) {
 $is_specific_post_type = NULL;
 $post_types_args = parse_args(
  array('_builtin'=>false), $post_types_args
 );
 $p = get_post($p);
 if ($type) {
  if (is_specific_post_type($type, $p)) return $type;
 }
 $post_types = get_post_types($post_types_args);
 foreach ($post_types as $pt) {
  if (is_specific_post_type($pt,$p)) {
   $is_specific_post_type = $pt; break;
  }
 }
 return $is_specific_post_type;
}


function is_custom_post_type($type, $p=NULL) {
 if (!empty($p)) return is_specific_custom_post_type($type,$p);
 return in_array($type, get_post_types(array('_builtin'=>false))); 
}


function get_post_id_from_db_value($guid, $column='guid') {
 global $wpdb;
 $query = $wpdb->prepare(
   "SELECT ID FROM `wp_posts` WHERE ".$column." LIKE '%s'", array($guid)
  );
 $result = $wpdb->get_results($query);
 if ((bool) $result && is_array($result) && get_class($result[0])) {
  return $result[0]->ID;
 }
 return NULL;
}


function get_id_by_slug($slug, $post_type='any') {
 $posts = get_posts(array('name'=>$slug, 'post_type'=>$post_type));
 if ($posts) {
  if (1==count($posts)) { return $posts[0]->ID; }
 }
}


function wpcf_get_featured_posts($args=array()) {
 global $wpdb;
 $a = parse_args(
  array(
   'post_type'=>'post',
   'posts_per_page'=>get_option('posts_per_page'),
   'post_status' => 'publish,future',
   'field'=>'post_id', // COULD BE "*"
   'search_value'=>'1',
   'meta_key' => 'featured_post'
  ),
  $args
 )
 ;
 $field = preg_replace('/[^a-z,\x2e\x2d\x5f\s]/', '', $a['field']);
 $query = "SELECT $field FROM $wpdb->postmeta WHERE meta_key='{$a['meta_key']}' AND meta_value='a:1:{i:0;a:1:{i:0;s:1:\"1\";}}' "
          . ($a['posts_per_page'] < 0 ? '' : "LIMIT ".$a['posts_per_page'])
 ;
 $result = $wpdb->get_results($query);
 $r = NULL ;
 if ((bool) $result && is_array($result) && get_class($result[0])) {
  if ($a['field'] == '*') { return $result; }
  foreach ($result as $obj) {
   $r[] = $obj->post_id ;
  }
  return $r;
 }
 return NULL;
}


function wpcf_get_post_id_in_admin_page() {
 $post_id = isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : NULL);
 return $post_id
 ;
}


