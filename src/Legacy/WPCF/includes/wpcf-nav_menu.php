<?php
function insert_node_to_nav_menu_html($args) {
 extract(parse_args(array(
  'node' => '',
  'nav_menu' => '',
  'regex' => '',
//  'node_element' => 'li',
  'menu_item_id' => 0,
 ), $args));
 return preg_replace(
  '{(<li id="menu-item-'.$menu_item_id.'".*?/a>)(</li>)}',
  '$1'.$node.'$2',
  $nav_menu
 );
}


function wp_nav_menu_in_process($theme_location=NULL) {
 $tree = debug_backtrace(); // if (is_specific_user_logged_in(1)) my_print_r($tree);
 foreach ($tree as $f) {
  if (isset($f['function']) && 'wp_setup_nav_menu_item' == $f['function'] ) { return $f['args']; }
  if (isset($f['class']) && $f['class'] == 'Walker_Nav_Menu') {
   if ($f['args'][1] instanceof WP_Post) {
	if ($theme_location) {
	 if ($f['args'][3]->theme_location == $theme_location) { return $f['args']; }
	 else { return false; }
	}
    return $f['args'];
	// You can get post ID by $f['args'][1]->object_id 
	// $f['args'][3]->theme_location for Theme Location
   }
  }
 }
 return FALSE;
}
