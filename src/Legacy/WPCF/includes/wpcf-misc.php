<?php
/* ////// Users ////// */
function is_specific_user_logged_in($userlist) {
 $u = wp_get_current_user(); if (in_array($u->ID, (array) $userlist)) return true;
 return false;
}


/* ////// Environment ////// */
function is_smartphone() {
 global $mobile_detect
 ;
 return $mobile_detect->isMobile();
/* //
 	return preg_match( '/android.+mobile/i', $_SERVER['HTTP_USER_AGENT'] ) ||
		preg_match( '/iphone/i', $_SERVER['HTTP_USER_AGENT'] );
// */
}

function is_tablet() {
 global $mobile_detect
 ;
 return $mobile_detect->isTablet();
}


function get_modified_time($filepath, $query_key='ver', $format='Y-m-d-h-i-s') {
 return file_exists($filepath) ?
  ($query_key ? '?'.$query_key.'=' : '') . date($format, filemtime($filepath))
  :
  '';
}
/* ////// Misc ////// */

function isolate_id($base, $increment=1, $append_first=FALSE, $append_count=TRUE, $concat='_') {
 global $wpcf_id_counts
 ;
 $base = (string) $base;
 $increment = (int) $increment;
 $append_count = (bool) $append_count;
 $id = $base;
 if (!isset($wpcf_id_counts[$base])) {
  $wpcf_id_counts[$base] = 0;
  if (FALSE === (bool) $append_first) return $base;
 }
 $wpcf_id_counts[$base] += $increment;
 $suffix = $concat.$wpcf_id_counts[$base];
 if ($append_count) $id .= $suffix;
 return $id;
}



function url_make_css_easy($u, $prefix='') {
 $classes = array();
 $url = parse_url($u);
 $class = '';
 $table = array(
  '\x2e' => '_', // . (dots)
  '\x25' => '-_-', // % (percent signs)
  '\x2f' => '-', // / (slashes)
  '\x26' => '--', // & (ampersands)
  '\x3d' => '-', // = (equal signs)
 )
 ;
 if ($url) {
  $class .= $prefix;
  if (isset($url['host']) && $url['host'] != $_SERVER['HTTP_HOST']) {
   $class .= str_replace('.', '_', $url['host']);
  }
  $path = $query = $fragment = '';
  if (isset($url['path'])) { $path = $url['path']; }
  if (isset($url['query'])) { $query = $url['query']; }
  if (isset($url['fragment'])) { $fragment = $url['fragment']; }
  foreach ($table as $c => $a) {
   if ($path) {
    $path = preg_replace('/'.$c.'/', $a, $path);
   }
   if ($query) {
    $query = preg_replace('/'.$c.'/', $a, $query);
   }
   if ($fragment) {
    $fragment = preg_replace('/'.$c.'/', $a, $fragment);
   }
  }
  $query ? $query = '___' . $query : '';
  $fragment ? $fragment = '____' . $fragment : ''; 
  $class .= $path . $query . $fragment ;
 }
 $class = preg_replace('/-$/','', $class);
 return $class;
}


function wpcf_mdstring($path, $format='Y-m-d-h-i-s', $key='ver', $_use_const=TRUE) {
 $cond = ($_use_const && defined('WPCF_APPEND_MDSTRING')) ? WPCF_APPEND_MDSTRING : TRUE
 ;
 $mdstr = (file_exists($path) && $cond) ? '?' . $key . '=' . date($format, filemtime($path)) : ''
 ; 
 return $mdstr;
}

function _return_argument($a) {return $a;} // Simply returns single given argument. For filter action that passes through the argument.


