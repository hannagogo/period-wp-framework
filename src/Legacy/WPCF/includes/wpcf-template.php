<?php
function theme_image($handle, $attr=NULL) {
 global $wpcf_theme_image;
 return $wpcf_theme_image->img($handle, $attr);
}

function sc_theme_image($attr) {
 if (isset($attr[0]) && !isset($attr['handle'])) $attr['handle'] = $attr[0];
 if (!isset($attr['handle'])) return NULL ;
 return theme_image($attr['handle'], $attr);
}
add_shortcode('theme_image', 'sc_theme_image');
add_shortcode('wpcf_theme_image', 'sc_theme_image');


function theme_image_info($name, $var='tag', $attr=NULL) {
 global $wpcf_theme_image;
 return $wpcf_theme_image->imageinfo($name, $var, $attr);
}


function wpcf_get_current_template_part($determine=NULL) {
 $o = debug_backtrace();
 $path = NULL;
 foreach ($o as $i=>$r) {
  if ( isset($r['function']) ) {
   if ( ( $r['function'] == 'load_template' ||  $r['function'] == 'include') && isset($r['args']) && is_array($r['args']) && isset($r['args'][0]) && is_string($r['args'][0]) ) { $path = $r['args'][0]; break; }
  }
 }
 if ($path) {
  if ( preg_match('/(.*?)\x2ephp$/', basename($path), $m ) ) {
   $t = $m[1];
   if ($determine) {
    if ($determine == $t) return $t;
    else return FALSE;
   }
   return $t;
  }
 }
 return FALSE
 ;
}

function wpcf_get_sidebar_content($id) {
 ob_start(); dynamic_sidebar($id); $d = ob_get_contents(); ob_get_clean(); return $d; 
}



