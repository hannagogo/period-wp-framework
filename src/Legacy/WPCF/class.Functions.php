<?php 
/*  WP Custom Functions Plugin version. */
/*  Optimized for WordPress 4+ and jQuery 1.12+ */
class WPCF_Functions {
/* ////// The Plugin ////// */
public static function include_function() {
 $includes = array(
  'admin',
  'attachment',
  'comment',
  'compatibility',
  'content',
  'database',
  'debug',
  'misc',
  'nav_menu',
  'post_meta',
  'post',
  'query',
  'scripts_and_styles',
  'shortcodes',
  'site',
  'social',
  'taxonomy',
  'template',
  'third_party_plugin_complement',
 );
 if (WP_DEBUG) {
  $includes[] = 'test';
 }
 foreach ($includes as $i) {
  include_once( sprintf('includes/wpcf-%s.php', $i) );
 }
}

public static function plugin_dir() {
 global $wp_custom_functions; return $wp_custom_functions->plugin_dir();
}

public static function plugin_url() {
 global $wp_custom_functions; return $wp_custom_functions->plugin_url();
}

public static function enqueue_theme_setup_function($fn) {
 add_action(WPCF_PREFIX.'Loaded', $fn);
}

public static function filter_exists($name) {
 global $wp_filter ;
 return isset($wp_filter[$name]);
}

} // End of Class