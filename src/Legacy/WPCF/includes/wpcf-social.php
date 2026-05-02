<?php
/* ////// Other Site Related ////// */
function search_keywords($referer = NULL) {
 if (!$referer) $referer = $_SERVER['HTTP_HTTP_REFERER'];
 preg_match('/^https?\x2f\x2f(.*?)\x2f.*?\x3f(.*?)$/', $referer, $m);
}


function wpcf_embed_movie($url, $args=NULL) {
 global $wp_custom_functions
 ;
 $args = $wp_custom_functions->parse_args(array(
  'width'      => 640,
  'height'     => 400,
  'responsive' => TRUE,
  'id'         => NULL,
  'class'      => NULL,
  'wrapper_tag'=> 'div',
  'player'     => TRUE,
  'poster'     => '',
  'a_attr'     => array('target'=>'_blank'),
 ), $args );

 $video_id = $video_html = '';
 $a_attr = array();
 $classes = array('WPCF_Movie_Container');
 if ($args['player']) {
  $classes[] = 'WPCF_Movie_Player';
  if ($args['responsive']) {
   $classes[] = 'Movie_Player Responsive video-container';
  }
 }
 else {
  $classes[] = 'WPCF_Movie_Poster WPCF_Movie_Thumbnail';
  $a_attr = array_merge(array('href'=>$url), ($args['a_attr'] ? $args['a_attr'] : array()));
 }
 $w = $args['width'];
 $h = $args['height'];

 $ratio = $w / $h ;
 
 if (preg_match('/[?&]v=([0-9A-Za-z_-]+)/', $url, $m)
  || preg_match('/https?\x3a(?:\x2f){2,2}youtu.be\x2f([0-9A-Za-z_-]+)\x2f?$/', $url, $m)
 ) {
  // YouTube
  $video_id = $m[1];
  $classes[] = $args['class'] ? $args['class'] : 'YouTube-movie';
  if ($args['player']) {
   $classes[] = 'WPCF_Movie_Player';
   $video_html = apply_filters('CF_HTML', 'iframe', 
    array(
     'width'=>$w, 'height'=>$h,
     'src'=>'https://www.youtube.com/embed/'.$video_id,
     'frameborder'=>0, 'allowfullscreen'=>'allowfullscreen'
    ), ''
   );
  }
  else {
   $video_html = apply_filters('CF_HTML', 'a', $a_attr,
    $args['poster'] ? 
     $args['poster']
     :
     apply_filters('CF_HTML', 'img', array('width'=>'100%', 'src'=>sprintf('http://i.ytimg.com/vi/%s/mqdefault.jpg', $video_id)))
   );
  }
  $classes = apply_filters('wpcf_embed_movie_classes', $classes);
 }
 else if (preg_match('/(sm\d+)(?:[^\d]+)?$/', $url, $m)) {
  // Nicovideo
  $video_id = $m[1];
  $classes[] = $args['class'] ? $args['class'] : 'Nicovideo-movie';
  if ($args['player']) {
   $video_html = sprintf('<script type="text/javascript" src="http://ext.nicovideo.jp/thumb_watch/%s?w=%s&h=%s"></script>', $m[1], $w, $h);
  }
  else {
   $video_html = apply_filters('CF_HTML', 'a', $a_attr,
    $args['poster'] ? 
     $args['poster']
     :
     apply_filters('CF_HTML', 'img', array('width'=>'100%', 'src'=>'http://tn-skr3.smilevideo.jp/smile?i='. str_replace('sm','', $video_id).'.L'))
   );
   
  }
 }
 $atts = array('class'=>$classes);
 if ($args['id']) $atts['id'] = $args['id'];
 return apply_filters('CF_HTML', $args['wrapper_tag'], $atts, $video_html);
 ;
}
add_filter('WPCF_Embed_Movie', 'wpcf_embed_movie', 10, 2);

function sc_wpcf_embed_movie($args) {
 if (!isset($args[0]) && !isset($args['url'])) return ;
 $url = isset($args[0]) ? $args[0] : $args['url'];
 return wpcf_embed_movie($url, $args);
}
add_shortcode('wpcf_embed_movie', 'sc_wpcf_embed_movie');

function wpcf_get_video_id($url) {
 // YOUTUBE
 preg_match('/[?&]v=([0-9A-Za-z_-]+)/', $url, $m)
 // YOUTUBE SHORT
  || preg_match('/https?\x3a(?:\x2f){2,2}youtu.be\x2f([0-9A-Za-z_-]+)\x2f?$/', $url, $m)
 // NICOVIDEO
  || preg_match('/(sm\d+)(?:[^\d]+)?$/', $url, $m)
 ;
 $video_id = $m[1];
 if ($video_id) { 
  return $video_id;
 }
 else return NULL;
}
//i.ytimg.com/vi/yjFlV4NAiZw/hqdefault.jpg
//http://tn-skr3.smilevideo.jp/smile?i=31088994.L

function wpcf_get_video_resoruces($url) {
 $r = array();
 // YOUTUBE
 if (preg_match('/[?&]v=([0-9A-Za-z_-]+)/', $url, $m)
     ||
     preg_match('/https?\x3a(?:\x2f){2,2}youtu.be\x2f([0-9A-Za-z_-]+)\x2f?$/', $url, $m)
    ) {
  $r['id'] = $m[1];
  $r['poster'] = sprintf('//i.ytimg.com/vi/%s/hqdefault.jpg', $r['id']);
  $r['host'] = 'youtube.com';
 }
 // NICOVIDEO
 else if (preg_match('/(sm\d+)(?:[^\d]+)?$/', $url, $m)) {
  $r['id'] = $m[1];
  $r['poster'] = sprintf('http://tn-skr3.smilevideo.jp/smile?i=%s.L', preg_replace('/sm(\d+)/', '$1', $r['id']));
  $r['host'] = 'nicovideo.jp';
 }
 ;
 if (!empty($r)) { 
  return $r;
 }
 else return NULL;
}


function wpcf_recaptcha($key=NULL) {
 if (empty($key)) {
  $key = apply_filters('WPCF_Option', 'GOOGLE_RECAPTCHA_SITE_KEY');
 }
 if ($key) {
  return '<div class="g-recaptcha" data-sitekey="'.$key.'"></div>';
 }
}
add_shortcode('wpcf_recaptcha', 'wpcf_recaptcha', 1, 1);