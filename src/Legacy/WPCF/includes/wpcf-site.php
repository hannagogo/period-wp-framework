<?php
/* ////// The Site ////// */

function wpcf_bloginfo($key=NULL, $trailingslashit = TRUE) {
 if ($key == 'home') $key = 'url';
 $i = do_shortcode(get_bloginfo($key)); 
 if (in_array($key, array('home', 'url', 'stylesheet_directory', 'template_directory', 'siteurl'))) return $trailingslashit ? trailingslashit($i) : $i;
 return $i;
}


function has_multiposts() { return is_plural(); } // this func is obsolete.
function is_plural() {
 global $wp_has_multiposts;
 if ($wp_has_multiposts) return $wp_has_multiposts;
 return !is_singular();
}


/* ////// Multisite ////// */

function get_blogs_by_name($names) {
 $blogs = array();
 foreach ((array) $names as $name) {
  $b = get_id_from_blogname($name);
  if ($b) $blogs[] = $b;
 }
 return $blogs;
}


function switch_to_main_blog() {
 (get_current_blog_id() != 1) && switch_to_blog(1);
}


function switch_to_network_blog($id=NULL) {
 global $current_blog_id, $is_network_site, $switched;
 if ($id === NULL) {
  $id = $current_blog_id;
 }
 $id = absint( $id );
 if ( !((bool) $id) ) { return FALSE; }
 
 $_switch = get_current_blog_id() != $id && $current_blog_id != $id;
 $_restore = ($current_blog_id == $id) || $_switch;
 ;
 if ($_restore) restore_current_blog();
 if ($_switch) {
  switch_to_blog($id);
 }
}


function is_network_blog($blog_id=NULL) {
 global $current_blog_id, $is_network_site;
 if ($blog_id !== NULL) {
  return $blog_id != 1;
 }
 if ($is_network_site !== NULL) {
  return $is_network_site;
 }
 if ($current_blog_id !== NULL) {
  return $current_blog_id != 1;
 }
 return $is_network_site;
}


function is_site_id($id) {
 global $current_blog_id;
 if ($id == $current_blog_id) return true;
 return false;
}


function network_blog_info($key=NULL) {
 global $current_blog_id, $is_network_site;
 if ($is_network_site) switch_to_network_blog($current_blog_id);
 $info = wpcf_bloginfo($key);
 if ($is_network_site) switch_to_main_blog();
 return $info;
}


