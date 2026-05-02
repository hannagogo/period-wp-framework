<?php
/* ////// HTML Header ////// */
function start_html($attr = NULL) {
 if (is_string($attr)) { $attr = array('version' => $attr) ; } // backward compatibility
 echo start_html_src($attr); // just a wrapper.
}


function start_html_src($attr = NULL) {
 global $wp_custom_functions;
 $html5 = apply_filters('WPCF_HTML5_Capable',NULL) ;
 $html = '';
 $version = NULL;
 
 $attr = $wp_custom_functions->parse_args(
  array(
   'version' => $html5 ? 'html5' : 'xhtml_transitional',
   'elements' => array()
  ), (array) $attr
 );
 $version = $attr['version'];
 
 $versions = array(
  'html5', 'html4_strict', 'html4_transitional', 'xhtml_strict', 'xhtml_transitional', 'xhtml11_strict'
 );

 ob_start();
  language_attributes(preg_match('/^xhtml/', $version)? 'xhtml' : 'html');
 $lang_attr = ob_get_clean();

 if ($version == 'html5' || (!$version && $html5))
  $html .= '<!doctype html>';

 else if ($version == 'html4_strict')
  $html .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';

 else if ($version == 'html4_transitional')
  $html .= '<!DOCTYPE html PUBLIC  "-//W3C//DTD HTML 4.01 Transitional//EN"' . LF
   . ' "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">';

 else if ($version == 'xhtml11_strict')
  $html .= '<?xml version="1.1" encoding="'.get_bloginfo('charset').'"?>'. LF
   . '<!DOCTYPE html ' . LF
   . 'PUBLIC "-//W3C//DTD XHTML 1.1//EN"'.LF.'"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';

 else if ($version == 'xhtml_strict')
  $html .= '<?xml version="1.0" encoding="'.get_bloginfo('charset').'"?>' . LF
   . '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . LF
   . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

 // Defaults to XHTML 1.0 Transitional
 else $html .= '<?xml version="1.0" encoding="' . get_bloginfo('charset').'"?>' . LF
  . '<!DOCTYPE html' . LF
  . ' PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"' . LF
  . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';

 if ($version == 'html5' || (!$version && $html5)) {
  $html .= '<html ' . $lang_attr . '>' . LF;
  $html .= '<head >'.LF.'<meta charset="' . get_bloginfo( 'charset' ) . '">' . LF;
 }
 else if (preg_match('/^html4/', $version)) {
  $html .= '<html ' . $lang_attr . '>';
  $html .= '<META http-equiv="Content-Type" content="text/html; charset='.get_bloginfo('charset').'">' . LF
   . '<META http-equiv="Content-Style-Type" content="text/css">' . LF
   . '<META http-equiv="Content-Script-Type" content="text/JavaScript">';
 }
 else { // if ($version == preg_match('/^xhtml/', $version)) {
  $html .= '<html xmlns="http://www.w3.org/1999/xhtml" ' . $lang_attr . '>' . LF;
  $html .= '<head>
<meta http-equiv="Content-Type" content="text/html; charset=' . get_bloginfo( 'charset' ) . '" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" />' . LF;
 }
 $html .= implode(LF, $attr['elements']);
 $html .= LF;
 return $html;
}


function wpcf_favicon($echo=FALSE) {
 global $wpcf_theme_image;
 foreach (array('favicon', 'icon') as $n) {
  if ( $icn = $wpcf_theme_image->imageinfo($n, 'full') ) {
   $link = createHTMLElement('link', array('rel'=>'shortcut icon', 'href'=>wpcf_url_to_https($icn['uri']), 'type'=>$icn['type']));
   if (!$echo) echo $link;
   else return $link;
  }
 }
 return NULL ;
}


function site_verification($sites=NULL) {
 $meta_tmpl = array(
  'google'	 => '<meta name="google-site-verification" content="%s" />' . CRLF,
  'bing'	 => '<meta name="msvalidate.01" content="%s" />' . CRLF
 );
 $sites_chk = array();
 foreach ((array) $sites as $s) { $sites_chk[] = strtoupper($s); }
 foreach ($meta_tmpl as $s=>$c) {
  if ($sites && !in_array(strtoupper($s), $sites_chk)) continue;
  $d = get_custom_functions_data(strtoupper($s.'_SITE_VERIFICATION'));
  if (isset($d['ENABLE']) && $d['ENABLE']) { echo sprintf($c, $d['CODE']) ; }
 }
}


function admin_favicon() {
 if (file_exists(TEMPLATEPATH . '/images/admin-icon.png'))
  echo '<link rel="shortcut icon" type="image/x-icon" href="'.get_bloginfo('template_url').'/images/admin-icon.png" />';
}


function wpcf_meta_viewport_tag($args, $echo=FALSE) {
 $args = parse_args(array(
  'width'			 => 'device-width',
  'initial-scale'	 => 0.5,
  'maximum-scale'	 => NULL,
  'user-scalable'	 => 'yes',
  'echo'			 => FALSE,
 ),
 $args);
 $echo = $echo || $args['echo'];
 $c = array();
 foreach ($args as $k=>$v) {
  if ('echo'==$k) continue;
  if (!empty($v)) {
   $c[] = sprintf('%s=%s', $k, $v);
  }
 }
 $meta = apply_filters('CF_HTML', 'meta', array('name'=>'viewport', 'content'=>implode(',', $c)));
 if ($echo) echo $meta;
 else return $meta;
}


function seo_meta_tags($args=NULL) {
/* //
 returns a list of:
  - post_type name and description if is_archive() and post_type archive being displayed
  - term name and description if is term archive
  - associated term names and descriptions if single post being displayed
 // */

 global $wp_custom_functions, $aiosp
 ;
 if (is_home() ) { return; }

 $post_has_aiosp_keywords = $post_has_aiosp_description = NULL ;
 $aiosp_data = array();
 if (isset($aiosp)) {
  if (is_home()) {
   $aioseop_options = get_option('aioseop_options');
   if ( $aiosp_data['keywords'] = $aioseop_options['aiosp_home_keywords'] ) { $post_has_aiosp_keywords = TRUE ; }
   if ( $aiosp_data['description'] = $aioseop_options['aiosp_home_keywords'] ) { $post_has_aiosp_description = TRUE ; }
  }
  elseif ( !is_archive() && $post = get_post() ) {
   if ( $aiosp_data['keywords'] = get_post_meta($post->ID, '_aioseop_keywords', TRUE) ) { $post_has_aiosp_keywords = TRUE ; }
   if ( $aiosp_data['description'] = get_post_meta($post->ID, '_aioseop_description', TRUE) ) { $post_has_aiosp_description = TRUE ; }
  }
 }
 $options = get_option('aioseop_options');
 $home_description = $options['aiosp_home_description'];
 $home_keywords = $options['aiosp_home_keywords'];
 
 $args = $wp_custom_functions->parse_args(array(
  'post_type'		 => TRUE,
  'terms'			 => TRUE,
  'taxonomy'		 => TRUE,
  'term_children'	 => TRUE,
  'tag_cloud'		 => TRUE,
  'keywords_max'	 => 10,
  'description_max'	 => 5,
  'term_depth'		 => 1,
  'description_sep'	 => ';',
  'keyword_sep'		 => ',',
 ), $args);

 $description = $keywords = $term_ids = $objects = array();
 $post_type = get_current_post_type();
 $tag_cloud = '';
 $objects['post_types'] = $objects['terms'] = $objects['taxonomies'] = array();

 $args['post_type'] && $post_type = get_current_post_type()  && $objects['post_types'][] = array_merge($objects['post_types'], (array) $post_type) ;

 foreach ($objects['post_types'] as $pt) {
  $keywords[$pt['name']] = $pt['label'] ;
  if ($pt['description']) $description[$pt['name']] = $pt['description'] ; 
 }

 if (is_archive()) {
  foreach ( (array) get_queried_terms() as $tax=>$ts ) {
   if ( $taxonomy = get_taxonomy($tax) ) $objects['taxonomies'][] = $taxonomy;
   foreach ($ts as $t) {
    $objects['terms'][$t->term_id] = $t;
    if ($args['term_children']) {
     foreach (get_term_children($t->term_id, $t->taxonomy) as $i) { $objects['terms'][$i] = get_term_by('id', $i, $t->taxonomy); }
    }
   }
  }
  if ($args['tag_cloud']) {
   foreach ($objects['terms'] as $t) {
    if (!isset($term_ids[$t->taxonomy])) $term_ids[$t->taxonomy] = array();
    $term_ids[$t->taxonomy][$t->term_id] = $t->term_id ;
   }
   foreach ($term_ids as $tax=>$t) {
    $tag_cloud = wp_tag_cloud(array(
     'taxonomy'=> $tax,
     'include' =>implode(',', $term_ids[$tax]),
     'number'	 => $args['keywords_max'],
     'orderby' =>'count', 'order'=>'DESC', 'echo'	 => FALSE,
    ) )
   ;
    foreach ($term_ids[$tax] as $i) {
     $term = get_term_by('id', $i, $tax);
     if ($term && $desc = $term->description) {
      $description[$term->term_id] = $desc;
     }
    }
    preg_match_all('/class=(?:\x22|\x27)tag-link-(\d+)(?:\x22|\x27)/', $tag_cloud, $ids); 
    if ($ids && is_array($ids) && isset($ids[1]) && !empty($ids[1]) && is_array($ids[1])) {
     $tag_cloud_keywords = array();
     foreach ($ids[1] as $i) { $tag_cloud_keywords[$i] = get_term_by('id', $i, $tax)->name ; }
    }
    $keywords = array_merge($keywords, $tag_cloud_keywords) ; //preg_split('/(?:\r\n|\r|\n|\s+)/', strip_tags($tag_cloud));
   }
  }
 }
 else {
  $post = get_post();
  foreach ( (array) wpcf_get_post_terms($post) as $term ) {
   $objects['terms'][$term->term_id] = $term;
   if (!isset($objects['taxonomies'][$term->taxonomy])) {
    $objects['taxonomies'][$term->taxonomy] = get_taxonomy($term->taxonomy);
   }
  }
 }

 if (!is_archive() || !$args['tag_cloud']) {
  foreach ($objects['terms'] as $t) {
   $keywords[$t->term_id] = $t->name ;
   if ($t->description) $description[$t->term_id] = $t->description ;
  }
 }

 $keywords = apply_filters(WPCF_PREFIX.'Meta_Keywords', $keywords);
 $description = apply_filters(WPCF_PREFIX.'Meta_Description', $description);
//my_print_r($keywords);
//my_print_r($description);
 $meta_tags = '';
 if (!empty($keywords) && !$post_has_aiosp_keywords) {
  $k = array_values($keywords);
  $k = array_splice($k, 0, $args['keywords_max']);
  $meta_tags .= createHTMLElement('meta', array('name'=>'keywords', 'content'=>implode($args['keyword_sep'], $k)));
 }
 if (!empty($description) && !$post_has_aiosp_description) {
  $d = array_values($description);
  $d = array_splice($d, 0, $args['description_max']);
  $meta_tags .= createHTMLElement('meta', array('name'=>'description', 'content'=>implode($args['description_sep'], $d)));
 }
 return $meta_tags;
}


function browser_body_class($classes) {
 global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;

 if ($is_lynx) $classes[] = 'ua-lynx';
 elseif ($is_gecko) $classes[] = 'ua-gecko';
 elseif ($is_opera) $classes[] = 'ua-opera';
 elseif ($is_NS4) $classes[] = 'ua-ns4';
 elseif ($is_safari) $classes[] = 'ua-safari';
 elseif ($is_chrome) $classes[] = 'ua-chrome';
 elseif ($is_IE) $classes[] = 'ua-ie';
 else $classes[] = 'ua-unknown';

 if ($is_iphone) $classes[] = 'ua-iphone';
 
 if ( apply_filters('WPCF_Is_Smartphone', NULL) ) {
  $classes[] = 'device-mobile';
 }
 else {
  $classes[] = 'device-non-mobile';
 }
 return $classes;
}


/* /// The Content / The Excerpt / The Title /// */

/** 
 * Determine if given/current post content has <!--more--> section.
 *
 * @param int|WP_Post|NULL $post Optional. Post ID or post object. Defaults to global $post. Passed to get_post() function. 
 * @param bool $_set_global Optional. Whether to set global $wpcf_post_has_more. Default true.
 * @return bool If the given post / current post has <!--more--> return true.
 */
function wpcf_post_has_more($post=NULL, $_set_global = TRUE) {

 global $wpcf_post_has_more
 ;
 $post = get_post($post);
 $_has_post = FALSE ;
 if ($post) {
  if (preg_match( '/\x3c!--more(.*?)?--\x3e/', $post->post_content)) {
   $_has_post = TRUE ;
  }
  else {
   $_has_post = FALSE ;
  }
 }
 else $_has_post = FALSE ;
 if ($_set_global) {
  $wpcf_post_has_more = $_has_post ;
 }
 return $_has_post ;
}
add_action('the_post', 'wpcf_post_has_more');


function build_post_class($classes = NULL, $sep = '', $post_or_post_id=NULL) {
 global $wp_query;
 $post = get_post($post_or_post_id);
 $post_class = array();
 if ($classes) {
  if (is_string($classes)) {
   $classes = ($sep == '') ? preg_split('/[,\s]/', $classes) : explode($sep, $classes);
  }
  $post_class = (array) $classes;
 }
 
 $post_count = $wp_query->current_post; $post_count++;

 // For Column Use
 $post_column_3 = $post_column_4 = $post_column_5 = $post_column_6 = '';
 foreach (array(3,4,5,6) as $col) {
  $surplus = $post_count % $col;
  if (0 == $surplus) { ${'post_column_'.$col} = 'post_count-'.$col.'_column-'.$col; }
  else {
    ${'post_column_'.$col} = 'post_count-'.$col.'_column-'.$surplus;
  }
 }
 
 if (!(!is_attachment( $post ) && current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail( $post->ID ))) {
  $post_class[] = 'no-post-thumbnail';
 }
 $post_class = array_merge(
  $post_class,
  array(
   'article',
   (is_singular()? 'singular' : 'posts'),
   'post_type_'.$post->post_type,
   'post_count_'.$post_count,
   'post_count_' . (($post_count % 2)? 'odd' : 'even'),
   $post_column_3, $post_column_4, $post_column_5, $post_column_6 ,
   is_object($post) ? $post->post_type.'_name-'.$post->post_name : '',
  )
 );
 if (current_filter() == 'post_class') { return $post_class ; }
 return get_post_class($post_class);
}


function add_segment_links($content, $args) {
 
 global $wp_custom_functions;
 $args = $wp_custom_functions->parse_args( array(
  'prefix' => 'wpcf-html-segment',
  'target' => 'h2',
  'trigger' => 'span',
 ), $args );
}


function wpcf_the_content($post=NULL) {
 if (empty($post)) $post = get_post($post);
 return apply_filters('the_content', $post->post_content);
}


function derefernce_entity($str) {
 return preg_replace('/&amp;/', '&', $str);
}

function apply_shortcode ($content) {
 return do_shortcode($content);
}

function add_nofollow($content) {
 return preg_replace_callback('/(\x3ca .*? ?\x3e.*?\x3c\x2fa\x3e)/', '_add_nofollow_callback', $content);
}

function _add_nofollow_callback($a) {
 $a = $a[0];
 require_once("phpQuery.php");
 $doc = phpQuery::newDocument($a);
 $append = false;
 $href = $doc['a']->attr('href');
 if (preg_match('/^https?:\x2f\x2f(.*?)\x2f/', $href, $m)) {
  if ($m[1] == $_SERVER['HTTP_HOST']) {
   return $a;
  }
  else {
   $rel = $doc['a']->attr('rel');
   if (!preg_match('/nofollow/', $rel)) { 
    $rel .= ($rel? ' ':'') . 'nofollow';
    $doc['a']->attr('rel', $rel);
   }
   return $doc['a']->htmlOuter();
  }
 }
 return $a;
}


function append_to_the_content($content, $order=10) {
 add_filter( 'the_content', function($html) use ($content) { return $html . $content; }, $order );
}

function wpcf_the_title_tag($tag) {
 global $wp_custom_attributes
 ;
 $html5 = apply_filters('CF_HTML', 'WPCF_HTML5_Capable', NULL);
 if (is_array($wp_custom_attributes) && isset($wp_custom_attributes['title_tag'])) {
  return $wp_custom_attributes['title_tag'];
 }
 return $html5 || is_singular() ? 'h1' : 'h2';
}

function wpcf_the_title_class($class) {
 return array_unique(array_merge((array) $class, array('article_title', 'the_title', 'article_h1')));
}


function wpcf_get_title($args) {
 global $wp_query, $post, $custom_functions_domain
 ; 
 $args_tmp = parse_args(array(
  'sep' => NULL,
  'seplocation' => NULL,
 ), $args);
 $args = apply_filters( 'WPCF_Arguments', parse_args(array(
  'wrap' => FALSE,
  'default' => is_singular()? get_the_title() : wp_title(NULL, FALSE),
  'attributes' => NULL,
 ), $args) );
 
 $title = '';
 
 if (is_singular()) {
  $title = apply_filters('WPCF_Get_Post_Meta', $post->ID, 'h1', FALSE);
  if (empty($title)) $title = get_the_title();
  else {
   if (is_array($title) && isset($title[0])) { $title = trim($title[0]); }
  }
 }
 else {
  if (is_post_type_archive()) {
   $title = post_type_archive_title(NULL, FALSE);
  }
  if (is_tax() || is_category() || is_tag()) { 
   $title = $wp_query->get_queried_object()->name ;
  }
  if (is_date()) {
   $date = $wp_query->query;
   $monthnum = isset($date['monthnum']) ? sprintf(apply_filters('WPCF_Title_Format_Date_Month', '%s'), $date['monthnum']) : NULL;
   $year = isset($date['year']) ? sprintf(apply_filters('WPCF_Title_Format_Date_Year', '%s' . ($monthnum ? '/' : '') ), $date['year']) : NULL;
   $title = sprintf(apply_filters('WPCF_Title_Format_Date', '%1$s%2$s'), $year, $monthnum);
  }
  if (is_author()) {
   $title = $wp_query->get_queried_object()->data->display_name ;
  }
 }

 if (empty($title)) $title = $args['default'];

 if ($args['wrap']) {
  return createHTMLElement($args['wrap'], $args['attributes'], $title);
 }
 return $title;
}


/* ////// Content Block Headers ////// */
/* //
Positions are:
[after_header] // ex. [Above]
<div id="content" ... >
[before_wp_title] // ex. [Meta]
 * wp_title
[content_header_end] // ex. [Content]
 * the_content
</div><!-- end of #content -->

// */
function wpcf_page_header($args) { return wpcf_page_part($args); } /* // This Function is obsolete. // */

function wpcf_page_part($args) {
 /* //
  <wpcf_page_header> handles meta content for the document.
 // */
 $header_func = array(
  'post_type_archive'	 => 'wpcf_custom_post_type_archive_header',
  'taxonomy_term'		 => 'wpcf_taxonomy_term_archive_header',
  'category'			 => 'wpcf_category_header',
  'post_type'			 => 'wpcf_custom_post_type_header',
  'post'				 => 'wpcf_post_header',
 );
 if (is_string($args)) { $args = array('position'=>$args); }
 $args = parse_args(array(
  'position' => 'content_header_end',
  'types'	 => array_keys($header_func),
  'echo'	 => 1,
  'post'	 => NULL,
  'filter'	 => 'wpcf_do_shortcode',
 ), $args);
 $args['position'] = wpcf_archive_header_position($args['position']); 
 ob_start();

 foreach ((array)$args['types'] as $k) {
  if (function_exists($header_func[$k])) {
   $header_func[$k]($args['position']);
  }
 }
 
 $content = ob_get_clean();
 
 $filter = $args['filter'];
 $filters = array();
 $default_filter = 'wpcf_do_shortcode';

 if (is_string($filter)) {
  if ('1' === $filter || '0' === $filter) {
   if ($filter) $filters[] = $default_filter;
  }
  else {
   $filters[] = $filter;
  }
 }
 else if (is_array($filter)) {
  $filters = array_merge($filters, $filter);
 }
 foreach ($filters as $f) {
  $content = apply_filters( $f, $content );
 }
 
 if ($args['echo']) echo $content;
 else return $content;
}


function wpcf_post_header($position='content_header_end', $post = NULL) {
 if (!is_singular()) return;
 if (!($post = get_post($post))) return;

 $position = wpcf_archive_header_position($position);
 $ex_position = wpcf_ex_archive_header_position($position);
 foreach(array($post->post_name, $post->ID) as $n) { 
  get_template_part(implode('_', array('post', $n, $position)));
  get_template_part(implode('_', array('post', $n, $ex_position)));
 }
 $meta_boxes = array(
  'above'		 => 'post_header_after_header',
  'after_header' => 'post_header_after_header',
  'meta'		 => 'post_header_above',
  'content_header_start' => 'post_header_above',
  'content'		 => 'post_header',
  'content_header_end' => 'post_header',
 );
 if (isset($position) && isset($meta_boxes[$position])) echo wpcf_get_post_meta_text($meta_boxes[$position]); 
 return NULL
 ;
}


if (!function_exists('archive_header')) {
 function archive_header($order = array('post_type','category','taxonomy_term'), $position='normal') { /* // this func is obsolete. // */ }
}


function wpcf_category_header($position='content_header_end') {
 $position = wpcf_archive_header_position($position);
 $ex_position = wpcf_ex_archive_header_position($position);
 if (is_archive() && is_category()) {
  foreach (array(get_current_category()->cat_ID, get_current_category()->slug) as $n) {
   get_template_part(implode('_', array('category', $n, $position)));
   get_template_part(implode('_', array('category', $n, $ex_position)));
   break;
  }
  if ($position == 'content_header_end' && is_object($c = get_current_category())) {
   echo apply_filters( WPCF_PREFIX.'Taxonomy_Term_Description', NULL ) ;
  }
 }
}

function wpcf_taxonomy_term_archive_header($position='content_header_end') {
 if (!is_tax()) return
 ;
 $position = wpcf_archive_header_position($position);
 $ex_position = wpcf_ex_archive_header_position($position);
 
 global $wp_query;
 $tax = ''; $term = '';
 $tax_queries = array();

 if (isset($wp_query->query_vars['taxonomy'])) $tax_queries[] = $wp_query->query_vars['taxonomy'];
 if (isset($wp_query->tax_query->queries[0]['taxonomy'])) $tax_queries[] = $wp_query->tax_query->queries[0]['taxonomy'];  
 
 foreach ($tax_queries as $t) { if ($t) $tax = $t; break; }
 
 $tax_queries = array();
 if (!empty($tax) && isset($wp_query->query_vars[$tax])) $tax_queries[] = $wp_query->query_vars[$tax];
 if (isset($wp_query->query_vars['term'])) $tax_queries[] = $wp_query->query_vars['term'];
 if (isset($wp_query->tax_query->queries[0]['terms'][0])) $tax_queries[] = $wp_query->tax_query->queries[0]['terms'][0];
 if (isset($wp_query->query[$tax])) $tax_queries[] = $wp_query->query[$tax];
 
 
 foreach ($tax_queries as $t) {
  if ($t) $term = $t; break;
 }
 if (!empty($tax)) {
  if (!empty($term)) {
   get_template_part(implode('_', array('taxonomy',$tax,'term',$term,'archive_header',$position)));
   get_template_part(implode('_', array('taxonomy',$tax,'term',$term,'archive_header',$ex_position)));
  }
  else {
   get_template_part(implode('_', array('taxonomy',$tax,'archive_header',$position)));
   get_template_part(implode('_', array('taxonomy',$tax,'archive_header',$ex_position)));
  }
 }
}

function wpcf_custom_post_type_archive_header($position='content_header_end',$type=NULL) {

 $position = wpcf_archive_header_position($position);
 $ex_position = wpcf_ex_archive_header_position($position);
 if ($post_type = is_custom_post_type_archive($type)) {
  get_template_part(implode('_', array('post_type',$post_type,'archive_header',$position)));
  get_template_part(implode('_', array('post_type',$post_type,'archive_header',$ex_position)));
 }
}

function wpcf_custom_post_type_header($position='content_header_end',$type=NULL) { // common post type post header
 $position = wpcf_archive_header_position($position);
 $ex_position = wpcf_ex_archive_header_position($position);
 if (is_singular() && ($post_type = is_specific_custom_post_type($type))) {
  get_template_part(implode('_', array('post_type',$post_type,'header',$position)));
  get_template_part(implode('_', array('post_type',$post_type,'header',$ex_position)));
 }
}


function wpcf_archive_header_position($pos='content_header_end') {
 $pos = preg_replace('/[^\w]/', '', $pos); 
 $new_pos = wpcf_new_archive_header_position($pos);
 if ( !empty($new_pos) ) return $new_pos ;
 return $pos ;
 ;
}

function wpcf_new_archive_header_position($pos=NULL) {
 $positions_conv = array(
  'above' => 'after_header',
  'meta' => 'content_header_start',
  'content' => 'content_header_end',
 );
 if ( !empty($pos) ) {
  if ( isset($positions_conv[$pos]) ) return $positions_conv[$pos];
  if ( in_array($pos, array_values($positions_conv)) ) return $pos;
  return NULL;
 }
 return $positions_conv;
}


function wpcf_ex_archive_header_position($new_pos) {
 $positions_conv = wpcf_new_archive_header_position();
 foreach ($positions_conv as $k=>$v) {
  if ($v == $new_pos) return $k;
 }
 return NULL
 ;
}

function add_post_header($content) {
 if (is_singular()) {
  return wpcf_get_post_meta_text('post_header') . $content;
 }
 return $content;
}

function post_style($echo = TRUE) {
 global $post, $posts
 ;
 if (is_singular()) {
  $f = NULL;
  if ( file_exists(TEMPLATEPATH . ($_f = '/style_' . $post->ID . '.css')) ) $f = $_f;
  if ( file_exists(TEMPLATEPATH . ($_f = '/style_post-' . $post->ID . '.css')) ) $f = $_f;
  if ($f) {
   $s = createHTMLElement('link', array(
    'rel'	 => 'StyleSheet',
    'type'	 => 'text/css',
    'media'	 => 'screen,print',
    'href'	 => get_bloginfo('template_url') . $f
    )
   );
   if (!$echo) return $f;
   else echo $f;
  }
 }
}


function add_postinfo($content) {
 $post_id = get_post($post)->ID;
 return $content .
  createHTMLElement(
   'footer',
   array('class'=>"postinfo", 'id'=>"postinfo_" . $post_id),
   __('Author') . ' :' .
   createHTMLElement('span', array('class'=>"author"),  get_the_author()).
   __('Category') . ' :' .
   createHTMLElement('span', array('class'=>"categories"), get_the_category_list(', ')) .
   (has_tag())? createHTMLElement('span', array('class'=>'tags'), get_the_tags()) : '' .
   (is_admin_user_logged_in() ? 
     createHTMLElement('span', array('class'=>"edit_link"),
      createHTMLElement('a', array('href'=>get_edit_post_link($post_id), 'target'=>'_blank', 'class'=>'event_edit'), __('Edit') )
	 ) : '' )
  );
}


function h_bloginfo($options = NULL) {
 $options = parse_args( array(
  'separator' => ':',
  'info' => array('name', 'description')
 ), $options);
 $bloginfo = '';
 $sep = $options['separator'];

 foreach ((array) $options['info'] as $i) {
  $bloginfo .= createHTMLElement('span', array('class'=>'h_bloginfo h_blog_'.$i), $sep . wpcf_bloginfo($i) );
 }
 return createHTMLElement(
  'span',
  array('class'=>'h_bloginfo_container'),
  $bloginfo
 );
}


function no_post_excerpt($p=NULL) {
 $p = get_post($p);
 $e = apply_filters('WPCF_Get_Post_Meta', $p->ID, 'no_post_excerpt', FALSE);
 
 if ( is_array($e) && isset($e[0]) ) return (bool) $e[0];
 if ( empty($e) ) return FALSE ;
 return (bool) $e;
}

function the_content_when_no_excerpt($content) {
 global $post;
 if (no_post_excerpt($post)) {
  return apply_filters('the_content', $post->post_content);
 }
 return $content;
}

function trim_excerpt_by_length($content) {
 global $post; 
 if ($m = apply_filters('WPCF_Get_Post_Meta', $post->ID, 'excerpt_length', FALSE)) {
//  while (is_array($m)) $m = $m[0];
  return mb_substr( strip_tags($post->post_excerpt), 0, (int) $m[0] );
 }
 return $content;
}


function sloppy_summary($content=NULL, $length=NULL, $check_shortcode_tags=false, $eof=array('。','.')) {
 if ($content !== NULL) {
  $eof = (array) $eof;
  foreach ($eof as &$char) {
   $char = preg_quote($char);
  }
  $eof_re = implode('|',$eof);
  $c = preg_replace('/('.$eof_re.').*?$/', '$1$2', preg_replace('/[\x0a\x0d]/','', strip_tags($content) ) );
  if ($check_shortcode_tags) {
   $tags = array();
   foreach (array_keys($shortcode_tags) as $tag) {
    $tags[] = preg_replace('/^/', '\x2f?', $tag); 
   }
   $c = preg_replace('/\[(?:'.implode('|', $tags).')\]/', '', $c);
  }
  else {
   $c = preg_replace('/\[.*?\]/', '', $c);
  }
  if ($length) return mb_substr($c, 0, $length);
  return trim($c);
 }
 else {
  $NULL = NULL;
  $post = get_post($NULL);
  return sloppy_summary($post->post_content, $length);
 }
}


function replace_url_to_root_relative($content) {
 $home_url = trailingslashit(get_home_url());
 $home_url_root_relative = root_relative_url($home_url);
 return str_replace($home_url, $home_url_root_relative, $content);
}



/* //////// PAGES //////// */
function page_navigation($a = array()) {
 global $paged, $wp_query;
 $html5 = apply_filters('WPCF_HTML5_Capable',NULL) ;
 $a = array_merge(array(
   'pages'		 => '',
   'range'		 => 2,
   'text_prev'	 => '&lsaquo;',
   'text_next'	 => '&rsaquo;',
   'text_last'	 => '&raquo;',
   'text_first'	 => '&laquo;',
   'text_ellip'	 => '&hellip;'
  ), $a
 );

 $pages = $a['pages'];
 $range = $a['range'];
 $showitems = ($range * 2) + 1;  
 if (empty($paged)) $paged = 1;
 if ($pages == '' && !($pages = $wp_query->max_num_pages)) $pages = 1;

 if (1 != $pages) {
  $c = createHTMLElement(($html5?'nav':'div'), 'start', array('id'=>'page_navigation', 'class'=>'clearfix nav'));
  if ($paged > 2 && $paged > $range + 1 && $showitems < $pages) 
   $c .= createHTMLElement('a', array('href'=>get_pagenum_link(1), 'class'=>'page_nav_jump page_nav_first'), $a['text_first']);
  if ($paged > 1 && $showitems < $pages) 
   $c .= createHTMLElement('a', array('href'=>get_pagenum_link($paged - 1), 'class'=>'page_nav_jump page_nav_prev'), $a['text_prev']);

  for ($i = 1; $i <= $pages; $i++) {
   if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems )) {
    if ($paged == $i) $c .= createHTMLElement('span', array('class'=>'page_nav_number page_nav_current'), $i);
    else $c .= createHTMLElement('a', array('class'=>'page_nav_number', 'href'=>get_pagenum_link($i)), $i);
   }
  }
  
  if ($paged + $range < $pages - 1) $c .= createHTMLElement('span', array('class'=>'page_ellip'), $a['text_ellip']);
  if ($paged + $range < $pages) 
   $c .= createHTMLElement('a', array('href'=>get_pagenum_link($pages), 'class'=>'page_nav_number'), $pages);

  if ($paged < $pages && $showitems < $pages)
   $c .= createHTMLElement('a', array('href'=>get_pagenum_link($paged + 1), 'class'=>'page_nav_jump page_nav_next'), $a['text_next']);
  if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages)
   $c .= createHTMLElement('a', array('href'=>get_pagenum_link($pages), 'class'=>'page_nav_jump page_nav_last'), $a['text_last']);
 
  return $c .= createHTMLElement(($html5?'nav':'div'), 'end') . createHTMLElement('_comment', 'END OF #page_navigation');
 }
}


function page_number($format = '', $display_when_single=FALSE, $wp_query_object=NULL) {
 global $paged, $custom_language_domain;
 if ( is_wp_query_object( $wp_query_object ) ) { $wp_query = $wp_query_object; }
 else { global $wp_query; }
 $paged = get_query_var('paged');
 $paged = $paged ? $paged : 1;
 $pages = $wp_query->max_num_pages;
 if ($pages > 1 || $display_when_single) {
  if (!$format) {
   $format = __('%1$s / %2$s Pages', $custom_language_domain);
  }
  return sprintf($format, $paged, $pages);
 }
 return '';
}


function wp_link_pages_args($args=array()) {
 $html5 = apply_filters('WPCF_HTML5_Capable',NULL);
 return parse_args(array(
  'before'           => '<'.($html5?'nav':'div').' class="wp_link_pages_container">',
  'after'            => '</'.($html5?'nav':'div').'>',
  'link_before'      => '<span class="page_link_item">',
  'link_after'       => '</span>',
  'next_or_number'   => 'number', // or 'next'
  'nextpagelink'     => '<span class="page_link_next">' . __('Next page') . '</span>',
  'previouspagelink' => '<span class="page_link_prev">' . __('Previous page') . '</span>',
  'pagelink'         => '%',
  'echo'             => 0 ), $args
 );
}


function set_conversion_tag_noscript($html,$order=10) {
 append_to_the_content(createHTMLElement('noscript',NULL,$html),$order);
}


function set_posts_per_page($n=NULL) {
 modify_query('posts_per_page', $n);
/*
 global $query_string;
 if ($n === NULL) $n = get_option('posts_per_page');
 $str = '/(posts_per_page=)(-1|[0-9]+?)(?=&|$)/';
 if (preg_match($str, $query_string)) query_posts(preg_replace($str, '$1'.$n, $query_string));
 else query_posts($query_string . '&posts_per_page=' . $n);
*/
}


function wpcf_content_url_to_https($content, $remove_scheme=TRUE, $this_site_only=TRUE) {
 if ($_SERVER['HTTPS']) {
  $home = preg_replace('/^https\x3a/', 'http:', home_url());
  return preg_replace('{(src|href)(=)([\x22\x27])' . $home . '(.*?)([\x22\x27])}', '$1$2$3'.home_url().'$4$5', $content);
 }
 return $content;
}