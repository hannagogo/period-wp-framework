<?php
namespace WPCF;
class SiteData extends ClassTemplate {

function __construct() {
 global $custom_language_domain, $wp_custom_functions
 ;
 $this->name = $wp_custom_functions->wpcf_site_option_name()
 ;
 do_action('WPCF_Add_PostTypes', apply_filters('WPCF_New_PostType', array(
  'name'	 => $this->name,
  'label'	 => __('Site Data', $custom_language_domain),
  'menu_position' => 60,
  'feeds' => false,
  'common_meta_box' => array(
   'post_settings'=>array('fields'=>array('css','jquerycode','js')),
  ),
  'singular_name' => __('Site Data Item', $custom_language_domain ),
  'show_submenu_on_dashboard' => array('posts_per_page'=>-1),
 ) ) );
 add_shortcode('wpcf_site_option', array(&$this, 'sc_wpcf_site_data')); // Deprecated.
 add_shortcode('wpcf_site_data', array(&$this, 'sc_wpcf_site_data'));
 add_filter( WPCF_PREFIX.'Site_Data_Modification', 'do_shortcode', 999, 1);
// add_filter( WPCF_PREFIX.'Site_Data_Modification', array(&$this, 'wpcf_site_option_edit_button'), 999, 1);
 add_filter( WPCF_PREFIX.'Site_Option', array(&$this, 'get_wpcf_site_option'), 1, 3 ); // Deprecated.
 add_filter( WPCF_PREFIX.'Site_Data', array(&$this, 'get_wpcf_site_option'), 1, 3 );
 add_filter( WPCF_PREFIX.'Is_Site_Option', array(&$this, 'is_wpcf_site_option'), 1, 1 );
// OLD EDIT DASHBOARD BOX // add_action( 'wp_dashboard_setup', array(&$this, 'wpcf_site_option_edit_box') );
}

public function is_wpcf_site_option($post=NULL) {
 global $wp_custom_functions
 ;
 return get_post($post)->post_type == $this->name
 ;
}

public function sc_wpcf_site_data($args) {
 global $wp_custom_functions
 ;
 $args = $wp_custom_functions->parse_args( array(
  0 => NULL,
  'p' => NULL,
  'name' => NULL,
  'field' => 'post_content',
  'prefix' => '',
  'suffix' => '',
  'filter' => 1
 ), $args );
 $key = $keyname = NULL;
 if (!empty($args[0])) {
  if (preg_match('/[^\d]/', $args[0])) { $keyname = 'name'; }
  else { $keyname = 'p'; }
  $key = $args[0];
 }
 else {
  if (empty($args['p'])) {
   $key = $args['name']; $keyname = 'name';
  }
  else {
   $key = $args['p']; $keyname = 'p';
  }
 }
 $data = $this->get_wpcf_site_option($key, $keyname, $args['field']);
 if ($args['filter']) {
  $data = do_shortcode($data);
 }
 return sprintf('%s%s%s', $args['prefix'], $data, $args['suffix']);
}

public function get_wpcf_site_option($key, $keyname='name', $field='post_content') {
 global $post
 ;
 $post_orig = $post;
 $posts = array();
 if ($key === NULL) return;
 if (empty($field )) $field = 'post_content';
 $posts = get_posts(array(
  'post_type' => $this->name,
  $keyname => $key,
 ));
 if (empty($posts)) return NULL; 
 $post = $posts[0];
 if ($keyname == 'object') {
//  return $post;
 }
 $content = apply_filters(WPCF_PREFIX.'Site_Data_Modification', $post->{$field});
 $post = $post_orig;
 return $content;
}

function wpcf_site_option_edit_box() {
 global $custom_language_domain, $wp_custom_functions
 ;
 wp_add_dashboard_widget('site_option_edit_box', __('Edit Site Data', $custom_language_domain ), array(&$wp_custom_functions, 'wpcf_edit_post_type_widget'), NULL, array($this->name, -1))
 ;
}

function wpcf_site_option_edit_button($content) {
 global $post
 ;
 if (current_user_can('edit_posts')) {
  $content .=
  apply_filters('CF_HTML', 'div', array('class'=>'wpcf_site_data_edit_button_box post_edit_button_box'), 
   apply_filters('CF_HTML', 'a', array('href'=>get_edit_post_link($post->ID), 'class'=>'wpcf_site_data_edit_button post_edit_button'), __('Edit'))
  );
 }
 return $content
 ;
}

}/* //////// END OF CLASS SiteData //////// */
