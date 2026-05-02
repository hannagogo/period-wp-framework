<?php
namespace WPCF
;
/*
Plugin Name: WPCF
Plugin URI: 
Description: A WordPress Theme Programming Framework
Version: 21.10.1
Author: Omi Kikuchi
Author URI: http://design-arts.jp/
License: GPL2
*/
global $mobile_detect
;
set_include_path(implode(
 PATH_SEPARATOR,
 array(
  get_include_path(),
//  $_SERVER['DOCUMENT_ROOT'] . '/lib/php/',
  trailingslashit( plugin_dir_path( __FILE__ ) ) . 'lib',
  trailingslashit( plugin_dir_path( __FILE__ ) ) . 'lib/PEAR',
 )
) );

if ((include_once 'class.CustomFunctions.php') == false) exit('library class.CustomFunctions.php not found. Exitting.');
if ((include_once 'ClassTemplate.php') == false) exit('library ClassTemplate.php not found. Exitting.');
if ((include_once 'CustomFunctions.php') == false) exit('library CustomFunctions.php not found. Exitting.');
if ((include_once 'Mobile-Detect/Mobile_Detect.php') == false) exit('library Mobile_Detect.php not found. Exitting.');
if (class_exists('Mobile_Detect')) { $mobile_detect = new Mobile_Detect; }

class WP_Custom_Functions extends ClassTemplate {

public function __construct($attr = NULL) {
 global $wpdb;
 $this->param( parse_args( array(
  'classes'			 => $this->classes,
  'wp_hook_delay'	 => $this->wp_hook_delay,
  'wpcf_site_option_name' => 'wpcf_site_options',
 ), $attr) );
 new CustomFunctions();
 $this->plugin_dir = trailingslashit( plugin_dir_path( __FILE__ ) ) ;
 $this->plugin_url = trailingslashit( plugins_url() ) . 'WP-Custom-Functions';
 $this->setting_page_name = $this->general_name . '_settings';
 $functions_file = trailingslashit($this->plugin_dir) . 'wpcf_functions.php';
 $this->wpcf_site_option_name = $this->param('wpcf_site_option_name');
 if (!defined('WP_CUSTOM_FUNCTIONS_FILE_LOADED') && file_exists($functions_file)) {
  require_once($functions_file);
  wpcf_include();
 }
 define('WPCF_PREFIX', 'WPCF_');
 define(WPCF_PREFIX.'CUSTOM_FUNCTIONS_TABLE_NAME', $wpdb->prefix . $this->general_name);

 $this->_setup_filters()->_setup_actions()->_setup_textdomain()->_initialize_option_names();
 $this->param(
  'option_names',
  apply_filters(WPCF_PREFIX.'Option_Names', isset($attr['option_names']) ? $attr['option_names'] : NULL)
 );

 $this->prepare_db_table()
  ->_load_classes($this->param('classes'))
  ->_initialize_variables()
 ;
 $this->param( 'USE_ROOT_RELATIVE_URL', $this->get_option('USE_ROOT_RELATIVE_URL') );
 $this->_after_options_set();
 if (!ini_get('date.timezone') && $this->get_option('DATE_TIMEZONE') && in_array($this->get_option('DATE_TIMEZONE'), $this->timezones)) ini_set('date.timezone', $this->get_option('DATE_TIMEZONE'));
 define("WPCF_LOADED", TRUE); 
 add_action('WPCF_Initialize', array(&$this,'_setup_site_option') );
}


public function __destruct() {
}

public function plugin_dir() { return $this->plugin_dir; }

public function plugin_url() { return $this->plugin_url; }

public function setup() {
 global $custom_language_domain;
 add_include_path(TEMPLATEPATH);
 add_include_path(STYLESHEETPATH);
 $this->_setup_constants()
  ->_setup_variables()
  ->_setup_wp_filters()
  ->_setup_options()
 ;
// new MediaCategory();
 new CustomOption(array('name'=>'site_name_short', 'label'=>__('Shortened Site Name', $custom_language_domain) ) );
 return $this;
}

private function _setup_options() {
 global $wpdb
 ;
 $names = array();
 foreach ($this->param('option_names') as $n=>$v) {
  $names[] = "\x27$n\x27";
 }
 $prefix = preg_replace('/^(?:'.$wpdb->prefix.')?/', '', WPCF_CUSTOM_FUNCTIONS_TABLE_NAME);
 $n = $prefix.'_name';

 $options = $wpdb->get_results(
    "SELECT custom_functions_name, custom_functions_value, custom_functions_time, custom_functions_id FROM "
  . WPCF_CUSTOM_FUNCTIONS_TABLE_NAME
  . " WHERE " . $n
  . " IN (" . implode(',', $names) . ")"
 );

 foreach ($options as $o) {
  foreach (array('name', 'time', 'value', 'id') as $suf) {
   $v = $o->{'custom_functions_'.$suf};
   if ($suf == 'time') $v = strtotime($v);
   $this->_options[$o->{$n}][$suf] = $v; // PRESERVE ORIGINAL
  }
  $this->options[$o->{$n}]['value'] = maybe_unserialize($this->_options[$o->{$n}]['value']);
 }
 // */
/* //
  foreach ($this->param('option_names') as $n=>$v) {
  $this->_options[$n] = get_custom_functions_data($n, true, false); // PRESERVE ORIGINAL
  $this->options[$n]['value'] = maybe_unserialize($this->_options[$n]['value']);
//$this->options[$n]['value'] = ($dd = @unserialize($d)) !== false ? $dd : $d; // general code
 }
 // */
// my_print_r(array($this->_options,$this->options));
 return $this;
}

private function _setup_actions() {
 global $custom_language_domain
 ;
 add_action( 'after_setup_theme', array(&$this, 'setup') );

 add_action( 'admin_init',			 function() { do_action(WPCF_PREFIX."Admin_Initialize"); } );
 add_action( 'init',				 function() { do_action(WPCF_PREFIX."Initialize"); } );
 add_action( 'wp_loaded',			 function() { do_action(WPCF_PREFIX."Loaded"); } );
 add_action( 'parse_query',			 function() { do_action(WPCF_PREFIX."Query"); } );
 add_action( 'template_redirect',	 function() { do_action("WPCF"); } );
 add_action( 'admin_enqueue_scripts',function() { do_action(WPCF_PREFIX."Admin"); } );
 add_action( 'admin_menu',			 function() { do_action(WPCF_PREFIX."Admin_Menu"); } );
 add_action( 'wp_head',				 function() { do_action(WPCF_PREFIX."Head"); } );
 add_action( 'admin_head',			 function() { do_action(WPCF_PREFIX."Admin_Head"); } );
 add_action( 'admin_head-nav-menus.php', array(&$this, 'set_custom_post_type_archives_nav_menu') );
 add_action( 'wp_dashboard_setup', array(&$this, 'wpcf_page_edit_box') );
 
 add_action( WPCF_PREFIX.'Initialize', 'fix_document_root');
 add_action( WPCF_PREFIX.'Initialize', array(&$this, 'setup_scripts_and_styles'), 1 );
// add_action( WPCF_PREFIX.'Initialize', array(&$this, 'set_common_meta_box_args') );
 add_action( WPCF_PREFIX.'Initialize', 'setup_common_meta_box');
 add_action( WPCF_PREFIX.'Initialize', 'flushRules', 10000 );
 add_action( WPCF_PREFIX.'Initialize', array(&$this, 'setup_image_sizes') );
 add_action( WPCF_PREFIX.'Initialize', 'activate_update_services_for_multisite');

 add_action( WPCF_PREFIX."Query", array(&$this, 'setup_wpcf_script') );
 add_action( 'WPCF', 'enqueue_scripts_and_styles', $this->param('wp_hook_delay') );
 add_action( WPCF_PREFIX.'Admin', 'enqueue_scripts_and_styles', $this->param('wp_hook_delay') );

 add_action( WPCF_PREFIX.'Head', function() { do_action(WPCF_PREFIX."Echo_Function", "wpcf_favicon"); } );
 add_action( WPCF_PREFIX.'Head', 'document_scripts_and_styles' );
 add_action( WPCF_PREFIX.'Head', 'post_style' );
 add_action( WPCF_PREFIX.'Head', 'site_verification' );

 add_action( WPCF_PREFIX.'Admin_Head', array(&$this, 'update_settings') );
 add_action( WPCF_PREFIX.'Admin_Head', 'admin_scripts_and_styles' );
 add_action( WPCF_PREFIX.'Admin_Head', 'admin_favicon');

 add_action( WPCF_PREFIX.'Admin_Menu', array(&$this, 'setup_menu') );
 
 add_action( WPCF_PREFIX.'Flush_Rules', 'flushRules' );
 add_action( WPCF_PREFIX.'Add_PostTypes', 'wpcf_add_post_types' );
 add_action( WPCF_PREFIX.'Set_MetaBox', 'setup_common_meta_box', 2 );
 add_action( WPCF_PREFIX.'Add_MetaBox', function($args) { new MetaBox($args); } );
 add_action( WPCF_PREFIX.'Add_Taxonomy', function($args) { new Taxonomy($args); } );
 add_action( WPCF_PREFIX.'Register_File', 'register_file', 1, 6 );
 add_action( WPCF_PREFIX.'Set_Image_Preload', 'set_preloadable_image' );
 add_action( WPCF_PREFIX.'Set_Conversion_Tag_Noscript', function($html, $order=10) { set_conversion_tag_noscript(trim($html),$order); } );
 add_action( WPCF_PREFIX.'Add_Comment_Field', 
  function($args) {
   global $my_custom_comment_fields;
   $my_custom_comment_fields[] = new CustomCommentField($args);
  } 
 );
 add_action( WPCF_PREFIX.'Switch_Blog', 'switch_to_network_blog', 1, 1 );
 add_action( WPCF_PREFIX.'Switch_To_Main_Blog', 'switch_to_main_blog' );
 
 add_action( WPCF_PREFIX.'Echo_Function', 'echo_function_return_value', 1, 1 );

 foreach ( array(
  'Set_JS_Handle'		 => 'enqueue_js_library_handle',
  'Set_CSS_Handle'		 => 'enqueue_css_handle',
  'Set_CSS_Code'		 => 'enqueue_css_code',
  'Set_JS_Code'			 => 'enqueue_javascript_code',
  'Set_JQuery_Code'		 => 'enqueue_jquery_code',
  'Set_Admin_JS_Handle'	 => 'enqueue_admin_js_library_handle',
  'Set_Admin_CSS_Handle' => 'enqueue_admin_css_handle',
  'Set_Admin_JQuery_Code'=> 'enqueue_admin_jquery_code',
  'Set_Admin_CSS_Code'	 => 'enqueue_admin_css_code',
 ) as $t => $fn) {
  add_action( WPCF_PREFIX.$t, function($i) use ($fn) { call_user_func($fn, $i); }, 10, 1 );
 }
 
 add_action( 'wp_footer', function() { if (function_exists('yoast_analytics')) yoast_analytics(); } );
 add_action( 'shutdown', array(&$this, 'save_options') );

 return $this;
}


private function _setup_filters() {
 $default_priority = $i = 10;
 add_filter( WPCF_PREFIX.'Setting_Page_Name', array(&$this, 'setting_page_name'));
 add_filter( WPCF_PREFIX.'Posted_Settings', array(&$this, '_filter_posted_settings') );
 add_filter( WPCF_PREFIX.'Setting_Fields', array(&$this, '_filter_setting_fields') );
 add_filter( WPCF_PREFIX.'Option_Names', array(&$this, '_filter_option_names') );
 add_filter( WPCF_PREFIX.'Image_Sizes', array(&$this, '_filter_image_sizes') );
 add_filter( WPCF_PREFIX.'Setting_Page', array(&$this, '_filter_setting_page') );
 add_filter( WPCF_PREFIX.'Setting_Page', array(&$this, 'styled_size_refresh_html') );
 add_filter( WPCF_PREFIX.'Start_HTML', function($html="", $atts=NULL) { return start_html_src($atts) . $html; }, $i, 2 );
 add_filter( WPCF_PREFIX.'Option', 'get_custom_functions_data');
 add_filter( WPCF_PREFIX.'Set_Option', 'set_custom_functions_data', $i, 4 );
 add_filter( WPCF_PREFIX.'Parse_Arguments', array(&$this, 'parse_args'), $i, 3 );
 add_filter( WPCF_PREFIX.'Arguments', function($a) { return func_get_arg(0); } ); /* // To modify arguments flexibly. // */

 /* Filters to invoke functions  */
  /* Representation */
 add_filter( WPCF_PREFIX.'Set_JQueryUI', 'wpcf_set_jqueryui', $i, 1 );
 add_filter( WPCF_PREFIX.'Modify_JQueryUI_Version', 
  function($v=NULL) {
   if (is_admin()) { return "1.11.4"; }
   else {
    return WPCF_JQUERYUI_VERSION; 
   }
  }, $i, 1 
 );
  /* System */
 add_filter( WPCF_PREFIX.'Filter_Exists', 'filter_exists', $i, 1 );
 add_filter( WPCF_PREFIX.'Custom_Option', function($args){ return new CustomOption($args); }, $i, 1 );
 add_filter( WPCF_PREFIX.'Template_Part', array(&$this, 'get_template_part'), $i, 3 );
 add_filter( WPCF_PREFIX.'Meta_Box_HTML', function($content) { return $content; }, $i, 1 );
 add_filter( WPCF_PREFIX.'Add_Rewrite_Rules', function($content) { return $content; }, $i, 1 );
 add_filter( WPCF_PREFIX.'Link_Pages_Arguments', 'wp_link_pages_args', $i, 1 );
 add_filter( WPCF_PREFIX.'Modify_Query', 'wpcf_modify_query', $i, 3 );

  /* Constructer */
 add_filter( WPCF_PREFIX.'EventSchedule', function($a) { return new EventSchedule($a); }, $i, 1);
  /* The Site */
 add_filter( WPCF_PREFIX.'Blog_Info', 'wpcf_bloginfo', $i, 1 );
 add_filter( WPCF_PREFIX.'Heading_Bloginfo', 'h_bloginfo' );
 add_filter( WPCF_PREFIX.'HTML5_Capable', 'is_html5_capable');
 add_filter( WPCF_PREFIX.'Is_Smartphone', 'is_smartphone', $i, 1 );
 add_filter( WPCF_PREFIX.'Is_Tablet', 'is_tablet', $i, 0 );
 add_filter( WPCF_PREFIX.'Theme_Image', 'theme_image', $i, 2 );
 add_filter( WPCF_PREFIX.'Queued_Handle', 
  function() {
   $a = func_get_args();
   if (is_array($a[0]) && is_NULL($a[1])) $a = $a[0];
   return is_queued_handle($a[0], $a[1]); 
  }, 1, 2
 );
 add_filter( WPCF_PREFIX.'Breadcrumb', function($args=NULL) { $bc = new Breadcrumb($args); return $bc->display($args); },1,1 );
 add_filter( WPCF_PREFIX.'Is_Plural', 'is_plural' );
 add_filter( WPCF_PREFIX.'Get_Post_Meta', 'get_multi_post_meta', $i, 3 );
 add_filter( WPCF_PREFIX.'Get_Post_Custom', 'get_multi_post_custom', $i, 3 );
 add_filter( WPCF_PREFIX.'Is_Network_Blog', 'is_network_blog', $i, 1 );
 add_filter( WPCF_PREFIX.'Network_Blog_Info', 'network_blog_info', $i, 1 );
 add_filter( WPCF_PREFIX.'Current_URL', function() { return home_url(add_query_arg(array())); }, $i, 1 );
 add_filter( WPCF_PREFIX.'Upload_Image_Size_Limit',
  function() { return apply_filters(WPCF_PREFIX."Option", "UPLOAD_IMAGE_SIZE_LIMIT"); }, $i, 1
 );
 add_filter( WPCF_PREFIX.'Existing_MetaBox_Values', 'wpcf_get_existing_meta_values', $i, 1 );
 add_filter( WPCF_PREFIX.'Is_HTTPS', 'wpcf_is_https', $i, 1 );
 add_filter( WPCF_PREFIX.'Get_Modified_Time_Query_String', 'get_modified_time', $i, 3 );
  /* Admin Page */
 add_filter( WPCF_PREFIX.'Admin_Show_Library_MetaBox_In_Edit_Page', function(){ return false; }, $i, 1 );
 add_filter( WPCF_PREFIX.'Admin_Show_Deprecated_In_Edit_Page', function(){ return false; }, $i, 1 );
  /* Nav Menu */
 add_filter( WPCF_PREFIX.'WP_Nav_Menu_In_Process', 'wp_nav_menu_in_process', $i, 1 );
 add_filter( WPCF_PREFIX.'WP_Nav_Menu_Use_Image', function() { return array("location"=>array(),"name"=>array()); }, $i, 1 );
 add_filter( WPCF_PREFIX.'WP_Nav_Menu_Use_Image_Class', function() { return "nav-menu-use-image"; }, $i, 1 );
 
  /* HTML */
 add_filter( WPCF_PREFIX.'Alter_Image_Size', 'replace_image_with_another_size', $i, 3 );
  /* Attachments */
 add_filter( WPCF_PREFIX.'Attachment_Image', 'attachment_image_html', 1, 2 );
 add_filter( WPCF_PREFIX.'Attachment_Image_Info', 'get_attachment_info', 1, 3 );
 add_filter( WPCF_PREFIX.'Attached_Files', 'wpcf_get_attached_files', $i, 2 );
 add_filter( WPCF_PREFIX.'Post_Thumbnail', 'post_thumbnail_html', 1, 2 );
 add_filter( WPCF_PREFIX.'Image_Size', 'get_custom_image_size', $i, 2 );
 add_filter( WPCF_PREFIX.'Add_Post_Image', 'add_post_image', 1, 3 ); // $content, $args, $post
 add_filter( WPCF_PREFIX.'Post_Thubmnail_Size', 'modify_post_thumbnail_size',$i,2 );
 add_filter( WPCF_PREFIX.'Swap_Attachment_Image', 'wpcf_swap_attachment_image', $i, 2 );
  /* Taxonomy */
 add_filter( WPCF_PREFIX.'In_Category',
  function($cat, $post=NULL) { return is_specific_taxonomy_term($cat, "category", $post); }, $i, 2
 );
 add_filter( WPCF_PREFIX.'In_Descendant_Term', 'post_is_in_descendant_taxonomy_term', $i, 3 );
 add_filter( WPCF_PREFIX.'In_Taxonomy_Term', 'is_specific_taxonomy_term', $i, 5 );
 add_filter( WPCF_PREFIX.'Queried_Terms', 'get_queried_terms', $i, 4 );
 add_filter( WPCF_PREFIX.'Term_Image_ID',
  function($term_id, $taxonomy="category", $field="term_id") {
   return get_term_image_id($term_id, $taxonomy, $field);
  }, 1, 3
 );
 add_filter( WPCF_PREFIX.'Has_Taxonomy_Term', 'has_specific_taxonomy_term', 1, 3 );
 add_filter( WPCF_PREFIX.'Tax_Query', 'parse_taxonomy_query_string', 1, 2 );
 add_filter( WPCF_PREFIX.'Taxonomy_Term_Posts', 'taxonomy_term_posts', $i, 1 );
 add_filter( WPCF_PREFIX.'Taxonomy_Queried', 'is_specific_taxonomy_queried', $i, 2 );
 add_filter( WPCF_PREFIX.'Insert_Terms', 'wpcf_insert_terms', $i, 1 );
 add_filter( WPCF_PREFIX.'Add_Admin_Taxonomy_Expander_Button', function($a) { return (array) $a; }, $i, 1 );
 add_filter( WPCF_PREFIX.'Add_Admin_Taxonomy_Term_Search_Box', function($a) { return (array) $a; }, $i, 1 );
  /* Users */
 add_filter( WPCF_PREFIX.'Is_Logged_In', 'is_specific_user_logged_in', 1, 1 );
  /* Post & Post Types */
 add_filter( WPCF_PREFIX.'PostType', 'wpcf_get_post_type');
 add_filter( WPCF_PREFIX.'PostTypes', function() { global $wpcf_post_types; return $wpcf_post_types; } );
 add_filter( WPCF_PREFIX.'Is_PostType', 'is_specific_post_type', $i, 2 );
 add_filter( WPCF_PREFIX.'New_PostType', function($args) { return new PostType($args); } );
 add_filter( WPCF_PREFIX.'Current_PostType', 'get_current_post_type', 1, 3 );
 add_filter( WPCF_PREFIX.'Is_Custom_PostType', 'is_custom_post_type_archive', $i, 2 );
 add_filter( WPCF_PREFIX.'Featured_Posts', 'wpcf_get_featured_posts', $i, 1 );
 add_filter( WPCF_PREFIX.'PostID_in_Admin', 'wpcf_get_post_id_in_admin_page', $i, 1 );
  /* Contents */
 add_filter( WPCF_PREFIX.'Meta_Viewport_Tag', 'wpcf_meta_viewport_tag', $i, 1 );
 add_filter( WPCF_PREFIX.'Meta_Keywords_And_Description', 'seo_meta_tags' );
 add_filter( WPCF_PREFIX.'Page_Navigation', 'page_navigation' );
// add_filter( WPCF_PREFIX.'New_MediaCategory', function($name,$args=NULL) { return new MediaCategory($name, $args); } );
 add_filter( WPCF_PREFIX.'Insert_NavMenu_Node', 'insert_node_to_nav_menu_html' );
 add_filter( WPCF_PREFIX.'Social_Share_Box', 'social_share_box' );
 add_filter( WPCF_PREFIX.'Page_Header', 'wpcf_page_part', $i, 1 ); // Wrapped with Page_Part. Obsolete.
 add_filter( WPCF_PREFIX.'Page_Part', 'wpcf_page_part', $i, 1 );
 add_filter( WPCF_PREFIX.'Taxonomy_Term_Description', 'get_taxonomy_term_description', $i, 2);
 add_filter( WPCF_PREFIX.'Post_Meta_Text', 'wpcf_get_post_meta_text', $i, 2 );
 add_filter( WPCF_PREFIX.'Link_Pages', function($args=NULL) { return wp_link_pages(wp_link_pages_args($args)); } );
 add_filter( WPCF_PREFIX.'Related_Posts', 'get_yarpp_related_posts' );
 add_filter( WPCF_PREFIX.'H1', 'wpcf_get_title', $i, 1 );
 add_filter( WPCF_PREFIX.'WP_Title', 'wpcf_get_title', $i, 1 );
 add_filter( WPCF_PREFIX.'WP_Title_Separator', function($sep=NULL) { return $sep !== NULL ? (string) $sep : " : "; }, $i, 1 );
 add_filter( WPCF_PREFIX.'Post_Class', 'build_post_class', $i, 3 );
 add_filter( WPCF_PREFIX.'Post_Meta_Table', 'post_meta_table', 1, 5 );
 add_filter( WPCF_PREFIX.'Blog_Info_Box', 'h_bloginfo', $i, 1 );
 add_filter( WPCF_PREFIX.'No_Post_Excerpt', 'no_post_excerpt', $i, 1);
 add_filter( WPCF_PREFIX.'Page_Number', 'page_number', $i, 3);
 add_filter( WPCF_PREFIX.'Format_Meta_Box', 'format_custom_meta_box', $i, 2);
 add_filter( WPCF_PREFIX.'The_Title_Tag', 'wpcf_the_title_tag', $i, 1);
 add_filter( WPCF_PREFIX.'The_Title_Class', 'wpcf_the_title_class', $i, 1);
 add_filter( WPCF_PREFIX.'The_Content', 'wpcf_the_content', $i, 1);
 add_filter( WPCF_PREFIX.'ScPosts_In_Process', 'sc_posts_in_process', $i, 1);
 add_filter( WPCF_PREFIX.'Title_Format_Date_Year', function($f) { return $f; }, $i, 1);
 add_filter( WPCF_PREFIX.'Title_Format_Date_Month', function($f) { return $f; }, $i, 1);
 add_filter( WPCF_PREFIX.'Title_Format_Date', function($f) { return $f; }, $i, 1);
 add_filter( WPCF_PREFIX.'Content_URL_To_HTTPS', 'wpcf_content_url_to_https', $i, 1);
  /* Comments */
 add_filter( WPCF_PREFIX.'Custom_Comment_Format', '_return_argument', 1, 1 );
 add_filter( WPCF_PREFIX.'Comments', 'build_comments', $i, 1 );

  /* Customize Preset Filters */
 add_filter( 'rewrite_rules_array', function($rules) { return array('([0-9]{1,})(?:/[^/]+)?/?$'=>'index.php?p=$matches[1]') + $rules; } );
 add_filter( 'post_class', array(&$this, '_filter_post_class'), $i, 3 );
 add_filter( 'body_class', array(&$this, '_filter_body_class') );
 add_filter( 'nav_menu_css_class', array(&$this, '_filter_nav_menu_css_class'), $i, 2 );
 add_filter( 'wp_get_nav_menu_items', array( &$this,'_filter_custom_post_type_archives_menu'), $i, 3 );

 add_filter( 'tiny_mce_before_init', 'extend_valid_html5' );
 add_filter( 'tiny_mce_before_init', 'tiny_mce_custom_block_elements' );
 add_filter( 'tiny_mce_before_init', 'TinyMceInitCustom' );
 add_filter( 'teeny_mce_before_init', 'TinyMceInitCustom' );

 add_action( 'the_title', 'apply_shortcode');
 add_action( 'wp_title', 'apply_shortcode');

 add_filter( 'the_content', 'fix_cdata', 9999 );
// add_filter( 'the_content', 'add_post_header' ); // is wrong. use below instead.
 add_filter( 'the_excerpt', 'trim_excerpt_by_length' );
 add_filter( 'the_excerpt', 'the_content_when_no_excerpt' );

 add_filter( 'comment_text',     'custom_comment_fields' );
 add_filter( 'get_comment_text', 'custom_comment_fields' );

 add_filter( 'intermediate_image_sizes', 'add_image_sizes' );
 add_filter( 'image_size_names_choose', 'additional_image_size_names_choose', 10 );

 return $this;
}


public function _after_options_set() {
 if ($this->param('USE_ROOT_RELATIVE_URL')) {
//  add_filter( 'the_permalink', 'root_relative_url' );
//  add_filter( 'wp_get_attachment_url', 'root_relative_url' );
 
 }
}


public function save_options() {
 $time = time(); $d = array();
 foreach ($this->options as $n=>$v) {
  if (!$this->option_is_single_value($n)) $v['value'] = serialize($v['value']);
  if (!isset($this->_options[$n]) || ($this->_options[$n]['value'] != $v['value']) ) {
   set_custom_functions_data(array('name'=>$n, 'value'=>$v['value'], 'time'=>$time, 'serialize'=>false));
  }
 }
}


public function get_option($name, $field='value') {
 $this->refresh();
 if (isset($this->options[$name]) && isset($this->options[$name][$field])) return $this->options[$name][$field];
 if ($v = get_custom_functions_data($name)) {
  $this->set_option($name, $v);
  return $v;
 }
 return NULL;
}

public function get_option_title($name) {
 $names = $this->param('option_names');
 return isset($names[$name]) ? $names[$name] : '';
}

public function set_option($name, $value=NULL) {
 $this->options[$name]['value'] = $value;
}

public function setup_scripts_and_styles() {
 register_custom_scripts_and_styles();
 set_common_js_values();
 enqueue_jquery_code('$("body").removeClass("no-js")');
 $this->enqueue_admin_scripts_and_styles();
}

public function setup_wpcf_script() {
 set_wpcf_common_js_values();
}

public function setup_menu() {
 $page_name =  $this->general_name.'_settings';
 add_menu_page(
  __('Custom Functions Settings', $this->language_domain),
  __('Custom Functions Settings', $this->language_domain),
  'manage_options',
  $page_name,
  array(&$this, 'menu_html'),
  trailingslashit( $this->plugin_url() ) . 'images/icn_menu_g.png'
 );

}




public function refresh() {
 $this->param('option_names', apply_filters(WPCF_PREFIX.'Option_Names', NULL));
}

public function add_option($name, $title=NULL) {
 $title = (string) $title ? $title : $name = (string) $name;
 add_filter(WPCF_PREFIX.'Option_Names', function($a) { return array_merge($a, array("'.$name.'"=>"'.$title.'")); });
 $this->refresh();
}

public function nonce($key, $field=NULL) {
 $a = array(
  'action' => sprintf('%s_%s', $this->general_name, $key),
  'name' => sprintf('%s_%s_nonce', $this->general_name, $key),
 );
 if (in_array($field, array_keys($a))) return $a[$field];
 return $a;
}

public function verify_nonce($key, $nonce=NULL) {
 if (!$nonce) $nonce = $this->nonce($key);
 return isset($_POST[$nonce['name']]) && wp_verify_nonce($_POST[$nonce['name']], $nonce['action']);
}

public function update_settings() {
 $nonce = $this->nonce('settings');
 if ($this->verify_nonce('settings', $nonce)) {
  $posted = apply_filters(WPCF_PREFIX.'Posted_Settings', $_POST);
  foreach ( $this->param('option_names') as $n=>$v ) {
   if (isset($posted[$n]) && $this->get_option($n) != $posted[$n]) {
    $this->set_option($n, $posted[$n]);
   }
  }
  $this->set_admin_notices(__('Saved Custom Functions settings.', $this->language_domain) );
 }
 $nonce = $this->nonce('styled_size_refresh');
 if ($this->verify_nonce('styled_size_refresh', $nonce)) {
  setup_image_sizes(array('refresh_style'=>true));
  $this->set_admin_notices(__('Refreshed Styled Sizes from style.css.', $this->language_domain) );
 }
}


public function set_admin_notices($msg, $priority=10) {
 $msg = createHTMLElement('p', NULL, preg_replace('/(?:\x5c)?(?:\x27)/', "\x5c\x27", $msg));
 add_action(WPCF_PREFIX.'Admin_Notices', function($notices) use ($msg) { return $notices . $msg; }, $priority );
}

private function admin_notices() {
 global $wp_filter;

 $f = WPCF_PREFIX.'Admin_Notices';
 if (isset($wp_filter[$f]) && count($wp_filter[$f]) > 0) {
  return createHTMLElement('div', array('class'=>'updated'), apply_filters($f, NULL));
 }
 return '';
}


public function menu_html() {
 global $theme_image;
 if ( !current_user_can( 'manage_options' ) )  {
  wp_die( __( 'You do not have permissions to access this page.' ), $this->language_domain );
 }
 $this->refresh();

 $html = $this->setting_form('settings')
  . createHTMLElement('h3', NULL, __('Site Settings', $this->language_domain))
  . createHTMLElement('table', 'start')
  . simple_table_rows(apply_filters(WPCF_PREFIX.'Setting_Fields', NULL))
  . createHTMLElement('table', 'end')
  . $this->setting_form_button( array(
    'id'=>$this->general_name."_settings_submit",
    'class'=>"button button-primary",
    'value'=>__("Save Options", $this->language_domain)
   ) )
  . createHTMLElement('form', 'end');

 echo createHTMLElement('div', 'start', array('class'=>"wrap", 'id'=>$this->general_name.'_settings_wrap') )
  . createHTMLElement('h2', NULL, __('Custom Functions Settings', $this->language_domain))
  . $this->admin_notices() // Admin Notices
  . apply_filters(WPCF_PREFIX.'Setting_Page', $html)
  . '</div>'; // END OF WRAP;
}


public function setting_form($nonce_name, $start_or_end = 'start') {
 if ($start_or_end == 'start') {
  $nonce = $this->nonce($nonce_name);
  return createHTMLElement('form', 'start', array(
   'name'=> sprintf('%s_%s_form', $this->general_name, $nonce_name),
   'method'=>"post",
   'action'=> str_replace( '%7E', '~', $_SERVER['REQUEST_URI'])
  ) ) .
  wp_nonce_field($nonce['action'], $nonce['name'], true, false)
  ;
 }
 else return createHTMLElement('form', 'end');
}

public function setting_form_button($option=NULL) {
 $option = parse_args( array(
  'name' => 'settings_form',
  'value' => __("Save Options", $this->language_domain),
  'class' => 'button button-primary',
  'id' => NULL
 ), $option );
 return createHTMLElement('input', array(
  'type'	 =>"submit", 'name'=>"submit",
  'id'		 => $option['id'] ? $option['id'] : sprintf('%s_%s_submit', $this->general_name, $option['name']),
  'class'	 => $option['class'],
  'value'	 => $option['value']
 ) );
}

public function styled_size_refresh_html($html) {
 return $html
  . createHTMLElement('h3', NULL, __('Styled Size for Intermediate Image Sizes', $this->language_domain) )
  . $this->setting_form('styled_size_refresh')
  . $this->setting_form_button( array(
     'name' => 'styled_size_refresh',
     'value' => __('Refresh Sizes from style.css', $this->language_domain)
    ) )
  . $this->setting_form(NULL,NULL);
}



function set_custom_post_type_archives_nav_menu() {
 global $custom_language_domain
 ;
 add_meta_box( 'custom_post_type_archives',
  __('Custom Post Type Archives', $custom_language_domain),
  array( &$this, 'custom_post_type_archives_navmenu_metabox_html' ),
  'nav-menus', 'side', 'default'
 );
}



function custom_post_type_archives_navmenu_metabox_html() {
 global $custom_language_domain
 ;
 $post_types = get_post_types( array( 'show_in_nav_menus' => TRUE, 'has_archive' => TRUE ), 'object' );
 $objects = array();
 if (!empty( $post_types )) {
  foreach ( $post_types as $i=>$post_type ) {
   if ($post_type->name == $this->wpcf_site_option_name || !$post_type->has_archive) {
    continue;
   }
   $j = new stdClass();
   $j->menu_item_parent = 0;
   $j->target = $j->attr_title = $j->xfn = NULL;
   $j->db_id = 0;
   $j->url = get_post_type_archive_link($post_type->name);
   $j->classes = array();
   $j->type = 'post_type';
   $j->object_id = $j->title = $post_type->labels->name . ' ' . __( 'Archive', $custom_language_domain );
   $j->object = 'custom_post_type-archive';
   $objects[] = $j;
  }
  $walker = new Walker_Nav_Menu_Checklist( array() );
   echo  '<div id="custom_post_type-archive" class="posttypediv">'
       . '<div id="tabs-panel-custom_post_type-archive" class="tabs-panel tabs-panel-active">'
       . '<ul id="custom_post_type-archive-checklist" class="categorychecklist form-no-clear">'
       .  walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', $objects), 0, (object) array( 'walker' => $walker) )
       . '</ul>'
       . '</div><!-- /.tabs-panel -->'
       . '</div>'
       . '<p class="button-controls">'
       . '<span class="add-to-menu">'
       . '<img class="waiting" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" alt="" />'
       . '<input type="submit" class="button-secondary submit-add-to-menu" value="' . __('Add to Menu', $custom_language_domain ) . '" name="add-custom_post_type-archive-menu-item" id="submit-custom_post_type-archive" />'
       . '</span>'
       . '</p>'
  ;
  echo apply_filters('CF_Wrap_JavaScript', '
$(".waiting").hide()
$.fn.extend({
 addPostTypeArchiveLink : function( processMethod ) {
  if ( 0 == $("#menu-to-edit").length ) return false

  return this.each(function() {
   var t = $(this)
     , menuItems = new Array()
     , checkboxes = t.find(".categorychecklist li input:checked")

   if ( !checkboxes.length ) return false
   if (!processMethod) return false

   t.find("img.waiting").show()

   $(checkboxes).each(function(){
    var t = $(this), li = t.closest("li")
    menuItems.push({
     "menu-item-type": "custom",
     "menu-item-url": li.children(".menu-item-url").val(),
     "menu-item-target": li.children(".menu-item-target").val(),
     "menu-item-attr_title": li.children(".menu-item-attr_title").val(),
     "menu-item-xfn": li.children(".menu-item-xfn").val(),
     "menu-item-description": "",
     "menu-item-title": t.val()
    });
   });
   wpNavMenu.addItemToMenu(menuItems, processMethod, function(){
    checkboxes.removeAttr("checked")
    t.find("img.waiting").hide()
   })
  })
 }
})

$("#submit-custom_post_type-archive").on("click", function(e) {
 var target = $(e.target);

 if ( target.attr("id") == "submit-custom_post_type-archive" ) {
  wpNavMenu.registerChange()
  $("#tabs-panel-custom_post_type-archive").addPostTypeArchiveLink( wpNavMenu.addMenuItemToBottom );
  return false
 }
})
', array('jquery'=>TRUE, 'jqueryready'=>TRUE));
 }
}


public function get_template_part( $slug, $name = null, $include = false ) {
 // is get_template_part altrenative.
 // see http://wordpress.stackexchange.com/questions/62232/is-there-a-variable-for-a-template-parts-name
 do_action( "get_template_part_{$slug}", $slug, $name );
 $templates = array();
 if ( isset($name) ) $templates[] = "{$slug}-{$name}.php";
 $templates[] = "{$slug}.php";
 return locate_template($templates, $include, false);
}



public function _filter_custom_post_type_archives_menu( $items, $menu, $args ) {
 foreach( $items as &$item ) {
  if( $item->object != 'custom_post_type-archive' ) continue;
  $item->url = get_post_type_archive_link( $item->type );

  if( get_query_var( 'post_type' ) == $item->type ) {
   $item->classes[] = 'current-menu-item';
   $item->current = true;
  }
 }
 return $items;
}

public function option_is_single_value($name) {
$v = $this->get_option($name); 
 if (in_array(gettype($v), $this->types_to_serialize)) return false;
 return true;
}

public function _filter_setting_fields($data) {
 global $wp_custom_style_handles;
 $d = array(); $data = (array) $data;
 foreach ($this->param('option_names') as $n => $v) {
  /// CASE ADMIN_LANGUAGE
  if ($n == 'ADMIN_LANGUAGE') {
   $d[ $this->get_option_title($n) ] = html_select_element(array(
    'id'	 => 'select_'.strtolower($n),
    'name'	 => $n,
	'values' => get_available_languages(),
    'value'  => $this->get_option($n)
   ) );
  }
  /// CASE VERIFICATION CODE
  else if ($n == 'GOOGLE_SITE_VERIFICATION' || $n == 'BING_SITE_VERIFICATION') {
   $ov = $this->get_option($n);
   $d[ $this->get_option_title($n) ] = createHTMLElement('input', array(
	'type'=>'text', 'name'=>$n.'_CODE', 'size'=>48,
    'value'=>isset($ov['CODE']) ? $ov['CODE'] : '',
   ) ) . createHTMLElement('input', array(
    'type'	 => 'checkbox',	 'value' => 1, 'name' => $n.'_ENABLE', 'id' => $n.'_ENABLE',
    'checked'=> isset($ov['ENABLE']) && $ov['ENABLE'] ? 'checked' : ''
   ) ) . createHTMLElement('label', array('for'=>$n.'_ENABLE'), __('Generate META tag', $this->language_domain) )
   ;
   continue;
  }
  /// CASE SITE BANNERS & THUMBNAIL
  else if ($n == 'SITE_THUMBNAIL' || $n == 'SITE_BANNER' || $n == 'SITE_BANNER_2') {
   $ov = $this->get_option($n);
   $d[ $this->get_option_title($n) ] = createHTMLElement('input', array('type'=>'hidden', 'name'=>$n.'_ID', 'value'=>isset($ov['ID'])? $ov['ID'] : '') ) .
	createHTMLElement('input', array('type'=>'text', 'name'=>$n.'_URI', 'value'=>(isset($ov['ID']) ? $ov['ID'] : ''), 'size'=>48) ) .
	(isset($ov['ID']) ? 
	 createHTMLElement('div', array('class'=>'site_image', 'id'=>strtolower($n).'_id'),
	  attachment_image_html($ov['ID'])
     ) : ''
    ) . 
    (isset($ov['URI']) ?
	 createHTMLElement('div', array('class'=>'site_image', 'id'=>strtolower($n).'_uri'),
      IMG($ov['URI'])
     ) : ''
    ) ;
   continue;
  }
  /// CASE JQUERY-UI 
  else if ($n == 'JQUERY_UI_THEME') {
   $d[ $this->get_option_title($n) ] = html_select_element(array(
    'id'	 => 'select_'.strtolower($n),
    'name'	 => $n,
	'values' => array_values(
	 preg_grep(
	  '/^jquery[\x2e-]ui[\x2e-]theme\x2e[^\x2e]+?\x2etheme$/', 
	  $wp_custom_style_handles
	 )
	),
    'value'=>$this->get_option($n)
   ) );
  }
  else if ($n == 'USE_ROOT_RELATIVE_URL') {
   $ov = $this->get_option($n);
   $d[ $this->get_option_title($n) ] = createHTMLElement('input', array(
    'type'	 => 'checkbox',	 'value' => 1, 'name' => $n, 'id' => $n,
    'checked'=> isset($ov) && $ov ? 'checked' : ''
   ) ) . createHTMLElement('label', array('for'=>$n), __('Use root relative URL path instead of full URL', $this->language_domain) )
   ;
   continue;
  }
  else if ($n == 'DATE_TIMEZONE') {
   array_multisort($this->timezones);
   $select = array();
   $d[ $this->get_option_title($n) ] = html_select_element(array(
	'name'	 => $n,
	'values' => &$this->timezones ,
	'value'	 => ( $v = $this->get_option($n) ) ? $v : 'Asia/Tokyo',
   ))
   ;
  }
  /// OTHER SINGLE VALUE OPTIONS
  else if ($this->option_is_single_value($n)) {
   $d[ $this->get_option_title($n) ] = createHTMLElement('input', array(
	'type'=>'text', 'name'=>$n, 'value'=>$this->get_option($n), 'size'=>'72'
   ) )
   ;
  }
  else {
  }
 };

 if (empty($data)) return $d;
 return $d + (array) $data;
}


public function _filter_posted_settings($posted) {
 $a = array();
 $keys = array_keys($posted);
 foreach ($this->param('option_names') as $k=>$v) {
  $child_value_keys = array();
  foreach ($keys as $key) {
   if (preg_match('/^'.$k.'_([^_]+?)$/', $key, $m)) { 
    $child_value_keys[] = $m[1];
   }
  }
  if (!empty($child_value_keys)) {
   $a[$k] = array();
   foreach ($child_value_keys as $key) {
    $a[$k][$key] = isset($posted[$k.'_'.$key]) ? $posted[$k.'_'.$key] : '';
   }
  }
  elseif (isset($posted[$k]) && !in_array(gettype($this->get_option($k)), $this->types_to_serialize) ) {
   $a[$k] = $posted[$k]; continue;
  }
  else { 
   $a[$k] = '';
  }
  continue;
 }
 return $a;
}


public function _filter_option_names($array) {
 $names = array();
 if (is_null($this->param('option_names'))) $names = $this->option_names;
 else $names = $this->param('option_names');
 return array_merge( $names, (array) $array );
}

function _filter_image_sizes($array) {
 return $this->image_sizes + (array) $array;
}

function _filter_setting_page($html) {
 return $html;
}

public function _filter_post_class($classes,$class,$post_id=NULL) { // if (is_specific_user_logged_in(1)) var_dump(func_get_args());
 return build_post_class($classes, NULL, $post_id);
}

public function _x_filter_post_class($classes, $class, $post_id=NULL) {
 global $wp_query;
 $post = get_post($post_id);
 $post_count = $wp_query->current_post; $post_count++;
 $post_class = array(
  'post_count_' . $post_count,
  'post_count_' . (($post_count % 2)? 'odd' : 'even'),
  'article',
  (is_singular()? 'singular' : 'posts'),
  is_object($post) ? $post->post_type.'_name-'.$post->post_name : ''
 );
 return array_unique(array_merge($classes, $post_class));
}

public function _filter_body_class($classes) {
 global $post, $wp_query
 ;
 $classes = browser_body_class($classes);
 if (!is_array($classes)) $classes = (array) $classes;
 $classes[] = 'no-js';
 if (is_singular()) {
  $classes[] = 'post-slug_'.$post->post_name ;
  $classes[] = 'post-name_'.$post->post_name ;
 }
 return $classes;
}

public function _filter_nav_menu_css_class( $classes, $item ) {
 $u = $item->url;
 $class_easy = url_make_css_easy($u, 'menu-item-url-');
 $uo = urldecode($u);

 $nav_menu_args = apply_filters('WPCF_WP_Nav_Menu_In_Process', NULL);
 if (is_array($nav_menu_args) && isset($nav_menu_args[3]) && is_object($nav_menu_args[3])) {
  $nav_menu_use_image = apply_filters('WPCF_WP_Nav_Menu_Use_Image', NULL);
  $nav_menu_use_image_class = apply_filters('WPCF_WP_Nav_Menu_Use_Image_Class', NULL);

  if ( in_array($nav_menu_args[3]->theme_location, (array) $nav_menu_use_image['location'] ) ) {
   if (!in_array($nav_menu_use_image_class, $classes)) $classes[] = $nav_menu_use_image_class ;
   $classes[] = $nav_menu_use_image_class . '-by-location';
  }
  if ( in_array($nav_menu_args[3]->menu, (array) $nav_menu_use_image['name'] ) ) {
   if (!in_array($nav_menu_use_image_class, $classes)) $classes[] = $nav_menu_use_image_class ;
   $classes[] = $nav_menu_use_image_class . '-by-name';
  }
 }

 return array_merge($classes, array($class_easy, $u, ($u != $uo) ? $uo : NULL));
}

private function _load_classes($classes = NULL) {
 if ($classes === NULL) $classes = $this->param('classes');
 $classes = (array) $classes;
 foreach($classes as $class) {
  $file = trailingslashit($this->plugin_dir) . 'class.' . $class . '.php';
  if (file_exists($file)) require_once($file);
 }
 return $this;
}


private function _initialize_variables() {
 foreach ( array('javascript_files', 'javascript_codes', 'js_libraries', 'jquery_codes', 'css_files', 'css_handles', 'css_codes', 'admin_javascript_files', 'admin_javascript_codes', 'admin_js_libraries', 'admin_jquery_codes', 'admin_css_files', 'admin_css_handles', 'admin_css_codes', 'preloadable_images', 'wpcf_post_types', 'wpcf_image_sizes', 'wp_custom_script_handles', 'wp_custom_style_handles', 'theme_image_handles', 'my_custom_comment_fields', 'wpcf_id_counts') as $name ) {
  global ${$name}; ${$name} = array();
 }
 return $this;
}


private function _initialize_option_names() {
 global $custom_language_domain;
 $custom_language_domain = $this->general_name;
 if (empty($this->option_names)) {
  $this->option_names = array(
   'ADMIN_LANGUAGE'			 => __('Admin Page Language', $custom_language_domain), 
   'GOOGLE_SITE_VERIFICATION'=> __('Google Site Verification Code', $custom_language_domain),
   'BING_SITE_VERIFICATION'	 => __('Bing Site Verification Code', $custom_language_domain),
   'JQUERY_UI_THEME'		 => __('JQuery-UI Theme', $custom_language_domain),
   'SITE_THUMBNAIL'			 => __('Site Thumbnail', $custom_language_domain),
   'SITE_BANNER'			 => __('Site Banner', $custom_language_domain),
   'SITE_BANNER_2'			 => __('Site Banner 2', $custom_language_domain),
   'SITENAME_SHORT'			 => __('Shortened Site Name', $custom_language_domain),
   'USE_ROOT_RELATIVE_URL'	 => __('URL format', $custom_language_domain),
   'DATE_TIMEZONE'			 => __('Timezone Settings (in PHP)', $custom_language_domain),
   'UPLOAD_IMAGE_SIZE_LIMIT' => __('Upload Image Size Limit (Longer Side in Pixels)', $custom_language_domain),
   'GOOGLE_RECAPTCHA_SITE_KEY' => __('Google reCAPTCHA API key', $custom_language_domain),
   'GOOGLE_RECAPTCHA_SECRET_KEY' => __('Google reCAPTCHA Secret key', $custom_language_domain),
  );
 }
 return $this ;
}


private function _setup_textdomain() {
 load_plugin_textdomain($this->general_name, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
 return $this;
}


private function _setup_constants() {
 if (!defined('LF')) define('LF', "\x0a");
 if (!defined('CRLF')) define('CRLF', "\x0d\x0a")
 ;
 global $wp_query
 ;

 define('SERVER_DOCUMENT_ROOT', $GLOBALS['_SERVER']['DOCUMENT_ROOT']);

 define(WPCF_PREFIX.'PLUGIN_URL', $this->plugin_url);
 define(WPCF_PREFIX.'PLUGIN_DIR', $this->plugin_dir);
 define(WPCF_PREFIX.'JS_LIBRARY_URL_ROOT', WPCF_PLUGIN_URL.'/js');
 define(WPCF_PREFIX.'CSS_LIBRARY_URL_ROOT', WPCF_PLUGIN_URL.'/css');

 define('TEMPLATE_URL', get_bloginfo('template_url'));
 define('TEMPLATE_URI', TEMPLATE_URL); // For backward compatibility
 define('TEMPLATE_URL_RELATIVE', preg_replace('{https?:\x2f\x2f' . $_SERVER['SERVER_NAME'] . '}','',TEMPLATE_URL));

 define('STYLESHEET_URL', get_stylesheet_directory_uri());
 define('STYLESHEET_URI', STYLESHEET_URL); // For backward compatibility
 define('STYLESHEET_URL_RELATIVE', preg_replace('{https?:\x2f\x2f' . $_SERVER['SERVER_NAME'] . '}','', STYLESHEET_URL));

 define('WPURL', get_bloginfo('wpurl'));
 
 define(WPCF_PREFIX.'JQUERYUI_VERSION', '1.12.1');
 
// Deprecated as of 2015.4.24
// define('JS_LIBRARY_URI_ROOT', preg_replace('/\x2f$/','',($J = get_custom_functions_data('JS_LIBRARY_URI')) ? $J : set_custom_functions_data('JS_LIBRARY_URI', '/JavaScript', NULL, false)));
// define('CSS_LIBRARY_URI_ROOT', preg_replace('/\x2f$/','',($C = get_custom_functions_data('CSS_LIBRARY_URI')) ? $C : set_custom_functions_data('CSS_LIBRARY_URI', '/StyleSheet', NULL, false)));

 return $this;
}


private function _setup_variables() {
 global
  $wpcf_theme_image, $img, $current_blog_id, $is_network_site, 
  $html5_capable, $is_IE, $custom_language_domain, $library_handles,
  $mobile_detect,
  $wpcf_original_query
 ;
 $wpcf_theme_image = new ThemeImage();
 $img = $wpcf_theme_image; // Just for compatibility. DO NOT USE $img
 $current_blog_id = $GLOBALS['blog_id'];
 $is_network_site = $current_blog_id != 1;
 $this->language_domain = $custom_language_domain;
 $html5_capable = !$is_IE;
 add_action('wp', function() {
  global $wpcf_original_query, $wp_query;
  $wpcf_original_query = $wp_query;
 })
 ;
 return $this;
}


private function _setup_wp_filters() {
 remove_filter('the_content', 'wpautop'); remove_filter('the_excerpt', 'wpautop'); // disable WPAUTOP
 add_filter('postmeta_form_limit',
  function($limit) { return defined('CUSTOM_FIELD_NAMES_LIMIT') ? CUSTOM_FIELD_NAMES_LIMIT : 200; }
 );
 add_filter('widget_text', 'do_shortcode');
 add_theme_support('post-thumbnails'); add_theme_support('menus'); add_post_type_support( 'page', 'excerpt' ); 
 add_theme_support('automatic-feed-links'); add_theme_support('editor-style');
 add_editor_style();

 remove_action( 'wp_head', 'feed_links_extra', 3 );
 remove_action( 'wp_head', 'feed_links', 2 );
 remove_action( 'wp_head', 'rsd_link' );
 remove_action( 'wp_head', 'wlwmanifest_link' );
 remove_action( 'wp_head', 'index_rel_link' );
 remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
 remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
 remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
 remove_action( 'wp_head', 'wp_generator' ); 
 return $this;
}


public function setup_image_sizes() {
 global $wpcf_image_sizes
 ;
// set_custom_functions_data('ImageSizes',array());
// set_custom_functions_data('IMAGE_SIZES',array());
 
 $wpcf_image_sizes = apply_filters(WPCF_PREFIX.'Image_Sizes', NULL);
 setup_image_sizes();
}



public function enqueue_admin_scripts_and_styles() {
 $ui_theme = $this->get_option('JQUERY_UI_THEME'); empty($ui_theme) && $ui_theme = 'jquery-ui.smoothness.theme';
 ;
 enqueue_admin_css_handle( array('wpcf-admin',  $ui_theme, 'theme-admin-style') );
 enqueue_admin_js_library_handle(array('javascript.utility','jquery.utility','jquery.autosize','wpcf-admin','jquery.multisortable'));
 $data_for_js = '
window.CustomPostType = $("#post_type") ? $("#post_type").val() : undefined
window.PostID = $("#post_ID") ? $("#post_ID").val() : undefined
window.UserID = $("#user-id") ? $("#user-id").val() : undefined
window.Shortlink = $("#shortlink") ? $("#shortlink").val() : undefined
';
 enqueue_admin_jquery_code(array(
'$("#wp-admin-bar-view a, #site-heading a, #updated a, #wp-admin-bar-site-name a, #edit-slug-box a").attr("target","_blank")'.LF.$data_for_js
//  '$("input[name=ALTERNATE_POST_THUMBNAIL_NUMBER]").css({"width":"2em"}).stepper({"type":"int","limit":[0,null]})'
 ) );
// enqueue_jquery_code($data_for_js);
 return $this;
}


public function prepare_db_table($id = WPCF_CUSTOM_FUNCTIONS_TABLE_NAME){
 global $wpdb;
 ;
 $table_name = preg_replace('/^(?:'.$wpdb->prefix.')?/', $wpdb->prefix, $id);
 $prefix = preg_replace('/^(?:'.$wpdb->prefix.')?/', '', $id);
 //  my_print_r($wpdb->get_var("show tables like '". $table_name . "'") == $table_name, 1);
 if ($wpdb->get_var("show tables like '". $table_name . "'") != $table_name) {
  $sql_query = "CREATE TABLE `".$table_name."` (
   `".$prefix."_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
   `".$prefix."_name` TEXT NOT NULL,
   `".$prefix."_value` TEXT NOT NULL,
   `".$prefix."_time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
  );";
  $wpdb->query($sql_query);
 }
 return $this;
}


public function menu() {
}


public function _setup_site_option() {
 global $custom_language_domain;
 $this->sitedata = new SiteData();
}

public function wpcf_site_option_name() {
 return $this->wpcf_site_option_name ;
}

function wpcf_page_edit_box() {
 global $custom_language_domain;
 wp_add_dashboard_widget('page_edit_box', sprintf( __('Edit %s', $custom_language_domain), __('Pages', $custom_language_domain) ), array(&$this, 'wpcf_edit_post_type_widget'), NULL, array('page', -1))
 ;
}

function wpcf_edit_post_type_widget() {
 global $wp_custom_functions
 ;
 $args_orig = func_get_args();
 isset($args_orig[1]) && isset($args_orig[1]['args']) && $args = $args_orig[1]['args'];
 isset($args_orig[1]) && isset($args_orig[1]['id']) && $id = $args_orig[1]['id'];
 $args = $wp_custom_functions->parse_args(array('post', get_option('posts_per_page')), $args);
 $posts = get_posts(array('post_type'=>$args[0], 'posts_per_page'=>$args[1]) );

 if (!empty($posts)) {
  echo apply_filters('CF_HTML', 'ul', array('id'=>$id),
   apply_filters('CF_HTML', 'li', NULL, array_map(
    function($p) {
     return apply_filters("CF_HTML", "a",
      array("href"=> admin_url("/post.php?post=".$p->ID."&action=edit")),
      strip_tags(apply_filters("the_title", $p->post_title))
     );
    }, $posts
   ) )
  );
 }
}

public function parse_args($defaults, $args) {
 return apply_filters( 'WPCF_Arguments', parse_args($defaults, $args) );
}

private function option_names_l10n() { // just for POEdit
 foreach ($this->option_names as $k=>$v) __($v, $custom_language_domain);
}

public function test($a) {
 $a = (array) $a;
 return $a+array('foo'=>'bar');
}

public function test2($a) {
 $a = (array) $a;
 return $a+array('test2'=>'test2 value');
}

/* ////// Accessors ////// */
public function setting_page_name() { return $this->setting_page_name; }

/* ////// Actions ////// */

/* ////// Properties ////// */
private $wpcf_site_option_name;
private $plugin_dir;
private $plugin_url;
private $wp_hook_delay = 99;
private $general_name = 'custom_functions';
private $language_domain;
private $options = array();
private $_options = array();
private $sitedata;
private $types_to_serialize = array(
 "boolean",
 "integer",
 "double",
 "array",
 "object",
 "resource",
);
private $option_names = array();
private $classes = array(
 'BreadCrumb',
 'ImageInfo',
// 'MediaCategory',
// 'MediaUpload',
 'MetaBox',
 'PostType',
 'RemoteSiteContentSummary',
 'ScheduleCalendar',
 'EventSchedule',
 'Taxonomy',
 'TermSearch',
 'ThemeImage',
 'WelcartUtility',
 'CustomCommentField',
 'CustomOption',
 'Relation',
 'PageHeader',
 'SiteData',
 'RelocateUpload',
);
private $image_sizes = array(
// '54px'		 => array(54,54,0),
// '72px'		 => array(72,72,0),
// '96px'		 => array(96,96,0),
 '128px'	 => array(128,128,0),
// '135px'	 => array(135,135,0),
// '170px'	 => array(170,170,0),
// '180px'	 => array(180,180,0),
// '240px'	 => array(240,240,0),
// '270px'	 => array(270,270,0),
// '320px'	 => array(320,320,0),
 '360px'	 => array(360,360,0),
// '427px'	 => array(427,427,0),
// '480px'	 => array(480,480,0),
// '640px'	 => array(640,640,0),
 '720px'	 => array(720,720,0),
// '854px'	 => array(854,854,0),
// '960px'	 => array(960,960,0),
 '1080px'	 => array(1080,1080,0),
// '1440px'	 => array(1440,1440,0),
// '1920px'	 => array(1920,1920,0),
// '72px-crop'	 => array(72,72,1),
// '96px-crop'	 => array(96,96,1),
// '128px-crop'	 => array(128,128,1),
// '180px-crop'	 => array(180,180,TRUE),
// '240px-crop'	 => array(240,240,TRUE),
// '270px-crop'	 => array(270,270,TRUE),
// '320px-crop'	 => array(320,320,TRUE),
 '360px-crop'	 => array(360,360,TRUE),
// '427px-crop'	 => array(427,427,TRUE),
// '480px-crop'	 => array(480,480,TRUE),
);

private $timezones = array(
'Pacific/Midway','US/Samoa','US/Hawaii','US/Alaska','US/Pacific','America/Tijuana','US/Arizona','US/Mountain','America/Chihuahua','America/Mazatlan','America/Mexico_City','America/Monterrey','Canada/Saskatchewan','US/Central','US/Eastern','US/East-Indiana','America/Bogota','America/Lima','America/Caracas','Canada/Atlantic','America/La_Paz','America/Santiago','Canada/Newfoundland','America/Buenos_Aires','Greenland','Atlantic/Stanley','Atlantic/Azores','Atlantic/Cape_Verde','Africa/Casablanca','Europe/Dublin','Europe/Lisbon','Europe/London','Africa/Monrovia','Europe/Amsterdam','Europe/Belgrade','Europe/Berlin','Europe/Bratislava','Europe/Brussels','Europe/Budapest','Europe/Copenhagen','Europe/Ljubljana','Europe/Madrid','Europe/Paris','Europe/Prague','Europe/Rome','Europe/Sarajevo','Europe/Skopje','Europe/Stockholm','Europe/Vienna','Europe/Warsaw','Europe/Zagreb','Europe/Athens','Europe/Bucharest','Africa/Cairo','Africa/Harare','Europe/Helsinki','Europe/Istanbul','Asia/Jerusalem','Europe/Kiev','Europe/Minsk','Europe/Riga','Europe/Sofia','Europe/Tallinn','Europe/Vilnius','Asia/Baghdad','Asia/Kuwait','Africa/Nairobi','Asia/Riyadh','Asia/Tehran','Europe/Moscow','Asia/Baku','Europe/Volgograd','Asia/Muscat','Asia/Tbilisi','Asia/Yerevan','Asia/Kabul','Asia/Karachi','Asia/Tashkent','Asia/Kolkata','Asia/Kathmandu','Asia/Yekaterinburg','Asia/Almaty','Asia/Dhaka','Asia/Novosibirsk','Asia/Bangkok','Asia/Jakarta','Asia/Krasnoyarsk','Asia/Chongqing','Asia/Hong_Kong','Asia/Kuala_Lumpur','Australia/Perth','Asia/Singapore','Asia/Taipei','Asia/Ulaanbaatar','Asia/Urumqi','Asia/Irkutsk','Asia/Seoul','Asia/Tokyo','Australia/Adelaide','Australia/Darwin','Asia/Yakutsk','Australia/Brisbane','Australia/Canberra','Pacific/Guam','Australia/Hobart','Australia/Melbourne','Pacific/Port_Moresby','Australia/Sydney','Asia/Vladivostok','Asia/Magadan','Pacific/Auckland','Pacific/Fiji');

}// END OF CLASS: WP_Custom_Functions

global $wp_custom_functions;
$wp_custom_functions = new WP_Custom_Functions ;

/*  Copyright 2013 Omi Kikuchi (email : omi@design-arts.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
