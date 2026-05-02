<?php
/* ////// Short Codes ////// */
if ( ! function_exists( 'shortcode_exists' ) ) {
 function shortcode_exists( $shortcode = false ) {
  global $shortcode_tags;
  if ( ! $shortcode ) return false;
  if ( array_key_exists( $shortcode, $shortcode_tags ) ) return true;
  return false;
 }
}


add_filter('wpcf_do_shortcode', function($content) { return do_shortcode($content);});

function remove_sc_quotes($str) {
 $quote = '(?:\x26(?:quot|(?:\x23(?:34|39)))\x3b|\x34|\x39)'; //(?:&(?:quot|(?:#(?:34|39)(?#"(?#&#34;)|'(?#&#39;))));|"|') 
 $quoted = "/^$quote(.*?)$quote$/";
 if (is_string($str)) $str = replace_entity_references( preg_replace($quoted, '\\1', $str) );
 elseif (is_array($str)) {
  foreach (array_keys($str) as $k) {
   $str[$k] = replace_entity_references( preg_replace($quoted, '\\1', $str[$k]) );
  }
 }
 return $str;
}
function replace_entity_references($str) {
 return preg_replace('/&amp;/', '&', $str);
}



function sc_list_posts($atts) {
 global $post, $wp_custom_attributes, $wp_custom_functions, $wp_query, $more ;
 $html5 = apply_filters('WPCF_HTML5_Capable',NULL);
 $current_blog_id = $GLOBALS["blog_id"];
 
 $id_base = 'list_posts';
 $class_base = '_container';
 $atts_tmp = $wp_custom_functions->parse_args(array('id'=>'list_posts'), $atts);
 
 if (isset($atts['allow_duplicate_id']) && !$atts['allow_duplicate_id']) { $atts_tmp['container_id'] = isolate_id($atts_tmp['container_id']); }
 $atts_tmp['class'] = ($atts_tmp['id'])? $atts_tmp['id'] . '_post_item' : preg_replace('/(list_post)s?$/', '$1', $atts_tmp['id']) . '_item';
 $atts_tmp['container_class'] = $atts_tmp['id'] . '_container';
 $atts_tmp['container_id'] = $atts_tmp['id'] . '_container';
 
 $post_orig = $post; // keep original $post
 $wp_custom_attributes = $wp_custom_functions->parse_args(array(
  'use_wp_filter'	 => FALSE,
  'posts_per_page'	 => get_option('posts_per_page'),
  'order'			 => 'DESC', // 'ASC'
  'orderby'			 => 'date',
    // accepts: 'author', 'category','content', 'date', 'ID', 'menu_order', 'mime_type',
    //          'modified', 'name', 'parent', 'password', 'rand', 'status', 'title', 'type'
  'exclude'			 => NULL, // deprecated. use post__not_in
  'include'			 => NULL, // deprecated. use post__in
  'post_type'		 => '',
  'tax_query'		 => '',
   // * Like: 'AND(movie_janner->slug(action,comedy)&actor->id(103,115,2);operator->NOT_IN&some_other_tax->slug(term1,term2))'
  'blog_id'			 => $current_blog_id,
  'p'				 => '',
  'post__in'		 => '',
  'post__not_in'	 => '',
  'category'		 => '',
  'category_name'	 => '',
  'remove_filters'	 => '', // hook_name=>([priority]=>filter_name)&the_content=>(10=>my_filter,9999=>my_another_filter)
  'apply_filters'	 => '', // 
  'class'			 => $atts_tmp['class'],
  'container_id'	 => $atts_tmp['container_id'],
  'container_class'	 => $atts_tmp['container_class'],
  'id'				 => $atts_tmp['id'],
  'title_li'		 => $html5 ? 'h1' : 'div',
  'title_class'		 => array('article_title'),
  'no_title'		 => FALSE,
  'no_title_link'	 => FALSE,
  'article_tag'		 => $html5 ? 'article' : 'div',
  'post_content'	 => FALSE,
  'post_excerpt'	 => TRUE,
  'post_thumbnail'	 => FALSE,
  'post_thumbnail_number'	 => 0, // 0: Post Thumbnail (default); 1 or more: Alternate Post Image;
  'post_thumbnail_fallback'	 => TRUE,
  'post_thumbnail_size'		 => 'thumbnail',
  'post_thumbnail_link'		 => TRUE, // FALSE to link to the originai image
  'use_more'		 => FALSE, // Uses <!--more--> short tag
  'morelinktext'	 => '...',
  'morelinktitle'	 => FALSE,
  'morelinktitle_truncate' => 0,
  'truncate'		 => '',
  'strip_teaser'	 => NULL,
  'more_file'		 => NULL,
  'ks_mobilize'		 => 'post_content=0,post_excerpt=1,post_thumbnail=0,posts_per_page=5',
  'clearfix'		 => 1,
  'empty_fallback'	 => TRUE,
  'random'			 => FALSE,
  'allow_duplicate_id' => FALSE,
  'posts' => NULL,
 ), $atts);

 foreach ($wp_custom_attributes as $k=>$v) {
//  if ($v === "0") $v = 0;
 }
 $wp_custom_attributes['shortcode_function'] = __FUNCTION__;
 $wp_custom_attributes['function'] = __FUNCTION__;

 extract($wp_custom_attributes); 

 if ($p) {
  if (!$include) $include = $p;
  $post__in = explode(',', $include);
 }
 if ($exclude) {
  $post__not_in = explode(',', $exclude);
 }
 if (!$post_type) {
  // * if $include (assumed $p, Post IDs) specified then involve any post_types
  if ($include) $post_type = 'any';
  else $post_type = 'post';
 }
 $post_type = explode(',', $post_type);
 
 if (function_exists('is_ktai') && is_ktai()) {
  $ks_atts = explode(',', $ks_mobilize);
  foreach ((array) $ks_atts as $k => $v) {
   $ks_mobilize[$k] = $v;
   ${$k} = $v;
  }
 }

 if ($current_blog_id != $blog_id) switch_to_blog($blog_id);

 remove_shortcode('list_posts'); // prevent infinite loop

 $tax_query = parse_taxonomy_query_string($tax_query);
 $cat = $category?array('cat'=>$category) : array();
 $args = array_merge($cat, compact('category', 'posts_per_page', 'order', 'orderby', 'post__not_in', 'post__in', 'category_name', 'post_type'), $tax_query);

 $pts_orig = array();
 $modify_pt_size = FALSE
 ;
//  hook_name=>([priority]=>filter_name)&the_content=>(10=>my_filter,9999=>my_another_filter)
 if ($remove_filters) {
 }
 if ( ($post_thumbnail === FALSE && $post_thumbnail_size) || $post_thumbnail) {
  $size = get_custom_image_size($post_thumbnail_size);
  if ($post_thumbnail_size != 'post-thumbnail') {
   $modify_pt_size = TRUE ;
   $pts_orig = array(get_option('post-thumbnail_size_w'), get_option('post-thumbnail_size_h'), get_option('post-thumbnail_crop'));
   $size_opts = array(get_option($size.'_size_w'), get_option($size.'_size_h'), get_option($size.'_crop')); 
   set_post_thumbnail_size($size_opts[0], $size_opts[1], $size_opts[2]);
  }
 }
 
 $ul_classes = explode(',', $container_class);
 foreach ($post_type as $p) { $ul_classes[] = $p.'_container'; }
 $ul_classes[] = 'list_posts_container';

 $article_end = createHTMLElement($article_tag, 'end');
 $formed_posts = array();
 
 if ($use_wp_filter) {
  $wp_query_orig = $wp_query;
  $wp_query = new WP_Query($args);
 
  if (have_posts()) while (have_posts()) {
   the_post();
   if ($use_more) { $more = 0; }
   $_post_is_original_post = $post_orig->ID == $post->ID ;
   $the_content = $the_excerpt = $the_title = $post_thumbnail = $morelink = $more_text = $c = '';

   $c .= createHTMLElement( $article_tag, 'start',
    array('id'=>$class.'_article_'.$post->ID, 'class'=>array('article', $class.'_article','list_posts_article'))
   );
   
   $the_title .= the_title(NULL,NULL,FALSE);
   $more_text .= $morelinktitle ? ($morelinktitle_truncate ? mb_substr($the_title, 0, $morelinktitle_truncate) : $the_title) : '' ;
   $more_text .= $morelinktext ? $morelinktext : '';
   ;
   if ($_post_is_original_post) {
    $the_content = $post->post_content ;
    $the_excerpt = $post->post_excerpt ;
   }
   else {
    ob_start();
    if ($post_content) {
	 the_content(); $the_content = ob_get_contents();
     if ($truncate) $the_content = force_balance_tags(mb_substr($the_content, 0, $truncate));
	}
	if ($post_excerpt) {
     if ($use_more) { the_content($more_text, $strip_teaser, $more_file); }
     else { the_excerpt(); }
     $the_excerpt = ob_get_clean();
     if ($truncate) $the_excerpt = force_balance_tags(mb_substr($the_excerpt, 0, $truncate));
    }
   }
  
   if (!$use_more) $morelink .= createHTMLElement(
    'a', array('href'=>get_permalink($post->ID), 'class'=>array('morelink', $id.'_morelink', $class.'_morelink')), $more_text
   );
   
   $c .= createHTMLElement('div',
    array(
     'id'=>$class.'_post_content_' . $post->ID,
	 'class'=>array($class.'_post_content', 'list_posts_post_content', ((bool) $the_excerpt ? 'list_posts_post_excerpt' : ''))
    ),
	( $no_title ? '' : $the_title )
	. ( $post_content ? $the_content : '' )
	. ( $post_excerpt ? $the_excerpt : '' )
   )
   . $morelink
   . $article_end
   ;
   $formed_posts[$post->ID] = $c;
  }
  $wp_query = $wp_query_orig;
 }
 else { // WITHOUT WP FILTER
  $the_content_tn_priority = has_filter('the_content', 'add_post_thumbnail') ;
  $the_excerpt_tn_priority = has_filter('the_excerpt', 'add_post_thumbnail') ;
  $handle_the_content_tn = ( !$post_thumbnail && $the_content_tn_priority !== FALSE ) || !$post_thumbnail ;
  $handle_the_excerpt_tn = ( !$post_thumbnail && $the_excerpt_tn_priority !== FALSE ) || !$post_thumbnail ;
  if ($handle_the_content_tn) remove_filter('the_content', 'add_post_thumbnail');
  if ($handle_the_excerpt_tn) remove_filter('the_excerpt', 'add_post_thumbnail');

  foreach (get_posts($args) as $post) {
   setup_postdata($post);
   $_post_is_original_post = $post_orig->ID == $post->ID ;
   $c = createHTMLElement( $article_tag, 'start',
    array('id'=>$class.'_article_'.$post->ID, 'class'=>array('article', $class.'_article','list_posts_article'))
   );
   
   $the_title = '';
   if (!$no_title) {
	$t = apply_filters('the_title', $post->post_title);
	/* //
	 if ($no_title_link) { $t = preg_replace('/<\x2f?a.*?>/', '', $t); } // */
	 
    $title_class = array_unique( array_merge( (array) $title_class, array('article_title') ) );
    $c .= createHTMLElement( $title_li, array('class'=>$title_class), $t );
   }
   $content_body = '';
   if ($post_content) {
    if ($_post_is_original_post) { $content_body .= $post->post_content ; }
    else {
	 $the_content = apply_filters('the_content', $post->post_content);
     $content_body .= ($truncate) ? force_balance_tags( mb_substr($the_content, 0, $truncate) ) : $the_content ;
    }
   }
   else if ($post_excerpt) { $content_body .= $_post_is_original_post ? $post->post_excerpt : apply_filters('the_excerpt', $post->post_excerpt); }
   else if ($post_thumbnail) {
    $post_image_args = array(
     'size'	=> $post_thumbnail_size,
     'url'	=> $post_thumbnail_link ? get_permalink( $post->ID ) : NULL
    );
	$img_ids = array();
	if ($post_thumbnail_number == 0) $img_ids[] = get_post_thumbnail_id($post->ID); 
    else {
	 $n = explode(',', $post_thumbnail_number);
     $alt_img = apply_filters('WPCF_Get_Post_Meta', $post->ID, 'alternate_post_image', FALSE);
	 while ( isset($alt_img[0]) && is_array($alt_img[0]) ) $alt_img = $alt_img[0] ;
	 foreach ($n as $i) {
	  if (in_array($i, $n)) $img_ids[] = $alt_img[$i];
	 }
    }
	$images = '';
	foreach ($img_ids as $i) {
	 $images .= attachment_image_html($i, $post_image_args);
	}
	$content_body .= $images;
   }

   if ($content_body) $c .= createHTMLElement('div',
    array(
     'id'=>$class.'_post_content_' . $post->ID,
     'class'=>array($class.'_post_content', 'list_posts_post_content')
    ), $content_body
   );
 
   if ($morelinktext || $morelinktitle) $c .= createHTMLElement(
    'a', array('href'=>get_permalink($post->ID), 'class'=>array('morelink', $id.'_morelink', $class.'_morelink')),
    ($morelinktitle ?  ( $morelinktitle_truncate > 0 ? force_balance_tags( mb_substr($the_title, 0, $morelinktitle_truncate) ) : $the_title ) : '' )
    . ( $morelinktext? $morelinktext : '' )
   );
   $c .= $article_end;
   $formed_posts[$post->ID] = $c;
  }  
  if ($handle_the_content_tn) add_filter('the_content', 'add_post_thumbnail', $the_content_tn_priority);
  if ($handle_the_excerpt_tn) add_filter('the_excerpt', 'add_post_thumbnail', $the_excerpt_tn_priority);
 }

 $content = createHTMLElement('ul', 'start', array('id' => $container_id, 'class'	=> $ul_classes ) );
 foreach ($formed_posts as $i => $p) {
  $content .= createHTMLElement('li', array('id'=>$class.'_li_'.$i, 'class'=>explode(',', $class.'_li,list_posts_li')), $p );
 }
 $content .= '</ul> '; $content = str_replace(']]>', ']]&gt;', $content);

 if ($current_blog_id != $blog_id) restore_current_blog();
 if ($modify_pt_size) set_post_thumbnail_size($pts_orig[0], $pts_orig[1], $pts_orig[2]);

 add_shortcode('list_posts', __FUNCTION__);
 
 $wp_custom_attributes = NULL ;
 $post = $post_orig;
 return LF . $content . LF . LF;
}
add_shortcode("list_posts", "sc_list_posts");

function sc_posts($atts) {
 global $post, $wp_custom_attributes, $wp_custom_functions, $wp_query, $more, $_wp_additional_image_sizes ;
 $html5 = apply_filters('WPCF_HTML5_Capable',NULL);
 $current_blog_id = $GLOBALS["blog_id"];
 
 $id_base = 'list_posts';
 $class_base = '_container';
 $atts_tmp = $wp_custom_functions->parse_args(array(
  'id'=>$id_base,
  'container_tag'=>'ul',
  'post_content' => FALSE
 ), $atts);
 $atts_tmp['container_tag'] = strtolower($atts_tmp['container_tag']);
 
 if (isset($atts['allow_duplicate_id']) && !$atts['allow_duplicate_id']) { $atts_tmp['container_id'] = isolate_id($atts_tmp['container_id']); }
 $atts_tmp['class'] = ($atts_tmp['id'])? $atts_tmp['id'] . '_post_item' : preg_replace('/(list_post)s?$/', '$1', $atts_tmp['id']) . '_item';
 $atts_tmp['container_class'] = $atts_tmp['id'] . '_container';
 $atts_tmp['container_id'] = $atts_tmp['id'] . '_container';
 
 $post_orig = $post; // keep original $post
 $wp_custom_attributes = $wp_custom_functions->parse_args(array(
  'posts_per_page'	 => get_option('posts_per_page'),
  'order'			 => 'DESC', // 'ASC'
  'orderby'			 => 'date',
    // accepts: 'author', 'category','content', 'date', 'ID', 'menu_order', 'mime_type',
    //          'modified', 'name', 'parent', 'password', 'rand', 'status', 'title', 'type'
    //          'meta_value_num', 'post__in', 'meta_value'
  'meta_key'		 => '', // must be with 'orderby'=>'meta_value' / 'meta_value__in' / 'meta_value__not_in' / 'meta_value__and'
  'meta_value'		 => NULL,
  'meta_value__in'	 => NULL,
  'meta_value__not_in'=> NULL,
  'meta_value__and'	 => NULL,
  'random'			 => FALSE,
  'post_type'		 => '',
  'name'			 => '',
  'post_status'		 => 'publish', 
  'pagename'		 => '',
  'tax_query'		 => '',
    // Like: 'AND(movie_janner->slug(action,comedy)&actor->id(103,115,2);operator->NOT_IN&some_other_tax->slug(term1,term2))'
  'blog_id'			 => $current_blog_id,
  'p'				 => '', // alias of post__in
  'post__in'		 => '', // include post by ids
  'post__not_in'	 => '', // exclude post by ids
  'category'		 => '',
  'category_name'	 => '',
  'taxonomy'		 => '',
  'terms'			 => '',
  'tax_query_relation'=> 'AND',
  'tax_query_field'	 => 'slug',
  'remove_filters'	 => '', // hook_name=>([priority]=>filter_name)&the_content=>(10=>my_filter,9999=>my_another_filter)
  'class'			 => $atts_tmp['class'],
  'container_id'	 => $atts_tmp['container_id'],
  'container_class'	 => $atts_tmp['container_class'],
  'id'				 => $atts_tmp['id'],
  'title_tag'		 => $html5 ? 'h1' : 'div', // Use filter the_title
  'title_class'		 => array('article_title'),
  'no_title'		 => FALSE,
  'no_title_link'	 => FALSE,
  'remove_a_tag'	 => FALSE,
  'article_tag'		 => $html5 ? 'article' : 'div',
  'container_tag'	 => $atts_tmp['container_tag'],
  'item_tag'		 => ($atts_tmp['container_tag'] == 'ul' || $atts_tmp['container_tag'] == 'ol') ? 'li' : 'div',
  'post_content'	 => FALSE,
  'post_excerpt'	 => $atts_tmp['post_content']? FALSE : TRUE,
  'post_thumbnail'	 => FALSE,
  'post_thumbnail_number'	 => 0, // 0: Post Thumbnail (default); 1 or more: Alternate Post Image;
  'post_thumbnail_fallback'	 => TRUE,
  'post_thumbnail_size'		 => 'thumbnail',
  'post_thumbnail_link'		 => TRUE, // FALSE to link to the originai image
  'post_thumbnail_position'	 => 0, // 0/prepend: prepend, 1/append: append
  'use_more'		 => FALSE, // Uses <!--more--> short tag
  'morelinktext'	 => $atts_tmp['post_content'] ? '' : '...',
  'morelinktitle'	 => FALSE,
  'morelinktitle_truncate' => 0,
  'hide_more_when_nomore' => TRUE,
  'truncate'		 => '',
  'strip_teaser'	 => NULL,
  'handle_post_scripts' => FALSE, // In Progress
  'handle_post_styles' => FALSE, // In Progress
  'more_file'		 => NULL,
  'empty_fallback'	 => TRUE,
  'allow_duplicate_id' => FALSE,
  'context'			 => NULL, // USE FOR PASSING CUSTOM DATA
  'post_count_suffix'=> 'post_count_',
  'post_count_start' => 1,
  'column_count'	 => 1,
  'column_count_suffix'=> "column_count_",
  'columns'			 => 1,
  'link_to'			 => 'permalink', // or "fragment" for inner page link
  'fragment_id'		 => '', // Prefix for inner link
  'no_link'			 => FALSE,
 ), $atts);
 extract($wp_custom_attributes); 
 $wp_custom_attributes['_shortcode_function'] = __FUNCTION__;
 $wp_custom_attributes['_function'] = __FUNCTION__;
 $post_status = explode(',', $post_status);
 
 if ($p && empty($post__in)) {
  $post__in = explode(',', $p);
 }
 else if (!empty($post__in)) {
  $post__in = explode(',', $post__in);
 }

 if (!$post_type) {
  // * if $include (assumed $p, Post IDs) specified then involve any post_types
  if ($post__in) $post_type = 'any';
  else $post_type = 'post';
 }
 $post_type = explode(',', $post_type);
 
 if (function_exists('is_ktai') && is_ktai()) {
  $ks_atts = explode(',', $ks_mobilize);
  foreach ((array) $ks_atts as $k => $v) {
   $ks_mobilize[$k] = $v;
   ${$k} = $v;
  }
 }

 if ($current_blog_id != $blog_id) switch_to_blog($blog_id);

 remove_shortcode('posts'); // ////// Prevent infinite loop ////// //

 $tax_query = parse_taxonomy_query_string($tax_query); //if (is_specific_user_logged_in(1)) my_print_r($tax_query);
 $cat = $category?array('cat'=>$category) : array();
 if ($taxonomy && $terms && empty($tax_query)) {
  $terms = explode(',', $terms);
  $tax_query = array('tax_query' => array(
   'relation' => $tax_query_relation,
   array(
    'taxonomy' => $taxonomy,
    'field' => $tax_query_field,
    'terms' => $terms,
   ),
  ) );
 }
 
/* // This part works if the meta value was stored in WP's standard format
 $meta_query = NULL ;
 if ($meta_key) {
  $meta_query = array();
  if ($meta_value) {
	$meta_query[] = array(
	 'key'     => $meta_key,
	 'value'   => $meta_value,
	 'compare' => '='
    );
  }
  if ($meta_value__in) {
   $values = explode(',', $meta_value__in);
   $meta_query['relation'] = 'OR';
   foreach ($values as $v) {
    $meta_query[] = array(
	 'key' => $meta_key,
	 'value' => $v
	);
   }
  }
  if ($meta_value__not_in) {
   $values = explode(',', $meta_value__not_in);
   foreach ($values as $v) {
    $meta_query[] = array(
	 'key' => $meta_key,
	 'value' => $v,
	 'compare' => 'NOT LIKE',
	);
   }
  }
  if ($meta_value__and) {
   $values = explode(',', $meta_value__and);
   $meta_query['relation'] = 'AND';
   foreach ($values as $v) {
    $meta_query[] = array(
	 'key' => $meta_key,
	 'value' => $v,
	);
   }
  }
 }
 // */ 
 $args = array_merge($cat, compact('name', 'category', 'posts_per_page', 'post__not_in', 'post__in', 'order', 'orderby', 'category_name', 'post_type', 'post_status'), $tax_query);
 if ((bool) $args['post__in']) { unset($args['post__not_in']); }
 else if ((bool) $args['post__not_in']) { unset($args['post__in']); }
 
 $the_content_tn_priority = has_filter('the_content', 'add_post_thumbnail') ;
 $the_excerpt_tn_priority = has_filter('the_excerpt', 'add_post_thumbnail') ;
 remove_filter('the_content', 'add_post_thumbnail');
 remove_filter('the_excerpt', 'add_post_thumbnail');

 $post_thumbnail_only = !$post_content && !$post_excerpt && $post_thumbnail;
 ;
 if ( $post_thumbnail ) {
  $post_thumbnail_size = get_custom_image_size($post_thumbnail_size);
  $position = array(
   'prepend', 'append', 'prepend'=>'prepend', 'append'=>'append'
  );
  $post_thumbnail_position = $position[$post_thumbnail_position] ? $position[$post_thumbnail_position] : 'prepend';
 }

 $ul_classes = explode(',', $container_class);
 foreach ($post_type as $p) { $ul_classes[] = $p.'_container'; }
 $ul_classes[] = $id_base.'_container';

 $article_end = createHTMLElement($article_tag, 'end');
 $formed_posts = array();
 
 $wp_query_orig = $wp_query;
 $wp_query = new WP_Query($args);

 $post_count = $post_count_start;
 $_in_the_loop_orig = in_the_loop();// if (is_specific_user_logged_in(1)) my_print_r( $wp_query->query );

 if (have_posts()) while (have_posts()) {
  the_post(); 
  if ($meta_key) {
   $meta_query_passed = FALSE;

   $meta_values = apply_filters('WPCF_Get_Post_Meta', $post->ID, $meta_key, FALSE);
   if ($meta_value) {
    if (in_array($meta_value, $meta_values)) $meta_query_passed = TRUE ;
   }
   else if ($meta_value__in) {
    $values = explode(',', $meta_value__in);
    foreach ($values as $v) {
	 if (in_array($v, $meta_values)) {
	  $meta_query_passed = TRUE;
	  break;
	 }
	}
   }
   else if ($meta_value__not_in) {
    $values = explode(',', $meta_value__not_in);
    foreach ($values as $v) {
	 if (in_array($v, $meta_values)) {
	  $meta_query_passed = FALSE ;
	  break;
	 }
	 else {
	  $meta_query_passed = TRUE;
	 }
    }
   }
   else if ($meta_value__and) {
    $values = explode(',', $meta_value__and);
    $meta_query['relation'] = 'AND';
    foreach ($values as $v) {
	 if (in_array($v, $meta_values)) {
	  $meta_query_passed = TRUE;
	 }
	 else {
	  $meta_query_passed = FALSE;
	  break ;
	 }
    }
   }
   if (!$meta_query_passed) {
    continue ;
   }
  }
  if ($use_more) {
   $more = 0;
   $post_excerpt = TRUE; $post_content = FALSE;
//   if ($post_excerpt !== TRUE) { $post_excerpt = (bool) $post_excerpt; }
//   if ($post_content !== FALSE) { $post_content = (bool) $post_content; }
  }
  $_post_is_original_post = $post instanceof WP_Post && $post_orig->ID == $post->ID ;
  $the_content = $the_excerpt = $the_title = $morelink = $more_text = $c = $post_thumbnail_html = $img = $the_post_content = '';

  $c .= createHTMLElement( $article_tag, 'start',
   array(
    'id'=>$class.'_article_'.$post->ID,
    'class'=>apply_filters('WPCF_Post_Class', array(
     'article', $class.'_article', $id_base.'_article',
     url_make_css_easy($post->post_name, $class.'-post_name-'),
     url_make_css_easy(get_permalink($post->id), $class.'-url-'),
	 has_post_thumbnail() ? 'has-post-thumbnail' : 'no-post-thumbnail'
    ) )
   )
  );
  
  $_link_to_fragment = 
   ($link_to == 'fragment') 
   &&
   ('' !== $fragment_id)
  ;
  $link =
   ((bool)$no_link) ? ''
   : 
   (($_link_to_fragment)? '#'.$fragment_id.'_post_content_'.$post->ID : get_permalink())
  ;
  if (!$no_title) {
   $the_title = the_title(NULL,NULL,FALSE);
   if ($no_title_link || $no_link) {
    $re = $remove_a_tag ? '/<\x2f?a.*?>/' : '/ href=\x22.*?\x22/' ;
    $the_title = preg_replace($re, '', $the_title);
   }
   if ($_link_to_fragment) { $the_title = preg_replace('/(href=\x22).*?(\x22)/', '$1'.$link.'$2', $the_title); }
  }
  
  $more_text .= $morelinktitle ? ($morelinktitle_truncate ? truncate_html($the_title, $morelinktitle_truncate, $morelinktext) : $the_title) : '' ;
  $more_text .= $morelinktext ? $morelinktext : '';
  ;
  if ($_post_is_original_post && $_in_the_loop_orig) {
   $the_content = $post->post_content ;
   $the_excerpt = $post->post_excerpt ;
  }
  else {
   if ($post_content || $use_more) {
    $the_content = apply_filters( 'the_content', get_the_content( $more_text, $strip_teaser, $more_file ) );
   }
   if ($post_excerpt) {
    $the_excerpt = apply_filters( 'the_excerpt', get_the_excerpt() );
   }
  }

  if ($truncate) {
   $the_content = force_balance_tags(truncate_html($the_content, $truncate, $strip_teaser));
   $the_excerpt = force_balance_tags(truncate_html($the_excerpt, $truncate, $strip_teaser));
  }
  if ( !$use_more && $more_text ) $morelink .= createHTMLElement(
   'a', array('href'=>$link, 'class'=>array('morelink', $id.'_morelink', $class.'_morelink')), $more_text
  );
  
  if ($post_thumbnail) {
   
  }
  
  if ($post_content || $use_more) {
   $the_post_content = $the_content;
  }
  else if ($post_excerpt && !$use_more) {
   $the_post_content = $the_excerpt ;
  }
  else if ($post_thumbnail_only) {
  }

  $c .= createHTMLElement('div',
   array(
    'id'=>$class.'_post_content_' . $post->ID,
    'class'=>array(
     $class.'_post_content',
     $id_base.'_post_content',
     ((bool) $the_excerpt ? $id_base.'_post_excerpt' : ''),
     $post_count_suffix.$post_count,
     $id_base . '_' . $post_count_suffix . $post_count,
     $class . '_' . $post_count_suffix . $post_count,
    )
   ),
   $the_title
   . 
   ($post_thumbnail ?
    add_post_image($the_post_content, array('size'=>$post_thumbnail_size, 'href'=>$link), NULL, $post_thumbnail_position)
    :
    $the_post_content
   )
  )
  . $morelink
  . $article_end
  ;
  $post_classes = array();
  if ($columns >= 3) {
   $post_classes[] = 'columns_'.$columns;
   foreach (apply_filters('WPCF_Post_Class',NULL) as $post_class) {
	if (in_array(
	     $post_class, array('article', 'posts', 'hentry', 'format-standard', 'status-publish')
	   )) {
	 continue;
	}
	if (preg_match('/post_count-\d+_column-\d+/', $post_class)) { 
	 if (preg_match('/post_count-'.$columns.'_column-\d+/', $post_class)) {
	  $post_classes[] = $post_class ;
	 }
	}
	else {
	 $post_classes[] = $post_class;
	}
   }
  }
  $item_classes = array_merge(
   array(
    $class,
    $class.'_li',
    $id_base.'_li',
    $class . '_li_' . $post_count,
    $id_base . '_li_' . $post_count,
   ),
   $post_classes
  );

  if ($column_count > 1) {
   $col = (($post_count - $post_count_start ) % $column_count) + 1;
   $item_classes[] = $class . '_' . $column_count_suffix . $col;
   $item_classes[] = $id_base . '_' . $column_count_suffix . $col;
  }
  $formed_posts[$post->ID] = createHTMLElement($item_tag,
   array(
    'id'   => $class.'_li_'.$post->ID,
    'class'=> $item_classes
   ),
   $c
  )
  ;
  $post_count++;

 } // END THE_LOOP

 $wp_query = $wp_query_orig;
 
 $ul_classes[] = 'post_count_'.($post_count-1);
 
 $content = createHTMLElement(
  $container_tag,
  array('id' => $container_id, 'class'	=> $ul_classes ),
  implode(LF, $formed_posts)
 );
 ;
 $content = str_replace(']]>', ']]&gt;', $content);

 // ////// Restore defaults ////// //
 if ($current_blog_id != $blog_id) restore_current_blog();
 add_shortcode('posts', __FUNCTION__);
 if ($the_content_tn_priority) add_filter('the_content', 'add_post_thumbnail', $the_content_tn_priority);
 if ($the_excerpt_tn_priority) add_filter('the_excerpt', 'add_post_thumbnail', $the_excerpt_tn_priority);
 $post = $post_orig;
 $wp_custom_attributes = NULL ;

 return LF . $content . LF . LF;
}
add_shortcode("posts", "sc_posts");


function sc_featured_posts($args) {
 $a = apply_filters('WPCF_Parse_Arguments', array(
  'post_type'=>'any',
  'posts_per_page'=>get_option('posts_per_page'),
  'post_status' => 'publish,future',
  'meta_key' => 'featured_post',
 ), $args);
 $post_ids = wpcf_get_featured_posts(array_merge($args, array('field'=>'post_id')));

 if (!empty($post_ids)) {
  $shortcode_args = array_merge($a, $args);
  $shortcode_args['post__in'] = implode(',', $post_ids);
  $arg_str = '';
  foreach ($shortcode_args as $sa_k=>$sa_v) {
   if (in_array($sa_k, array('meta_key'))) {
    continue;
   }
   $arg_str .= ' ' . $sa_k . '=' . $sa_v;
  }
  return do_shortcode('[posts'.$arg_str.']');
 }
}
add_shortcode('featured_posts', 'sc_featured_posts', 1);


function sc_posts_in_process($id=NULL) {
 global $wp_custom_attributes
 ;
 if ( !(
  is_array($wp_custom_attributes)
   && isset($wp_custom_attributes['_shortcode_function'])
   && $wp_custom_attributes['_shortcode_function'] == 'sc_posts'
 ) ) return FALSE
 ;
 $_sc_post_id = isset($wp_custom_attributes['id']) ? $wp_custom_attributes['id'] : NULL;
 
 if ($id) {
  $id = (array) $id;
  return $_sc_post_id && in_array($_sc_post_id, $id);
 }
 return $_sc_post_id;
}




function sc_list_thumbnails($atts) {
}
add_shortcode('list_thumbnails', 'sc_list_thumbnails');


function sc_include($attrs) {
 global $wp_custom_functions;
 extract($wp_custom_functions->parse_args(array(
  "virtual"	 => '',
  "webpage"	 => '',
  "file"	 => '',
  "php"		 => '',
  "post"	 => '',
  "args"	 => '',
  'part'	 => '',
  'part_dir' => '/parts'
 ), $attrs));

 $filepath = NULL;
 $params = array();
 if ($args) {
  foreach (explode('&', remove_sc_quotes($args)) as $a) {
   list($k, $v) = explode('=', $a);
   $params[$k] = $v;
  }
 }
 
 foreach (array('virtual', 'file', 'post', 'php', 'post', 'webpage') as $v) {
  ${$v} = remove_sc_quotes(${$v});
  if (${$v}) {
   if ($v == 'virtual') { virtual(${$v}); return; }
   if ($v == 'file' || $v == 'php' || $v == 'webpage') {
    $filepath = (file_exists(${$v}) || preg_match('"^https?://"', ${$v}))? ${$v} : NULL;

    if (!$filepath) {
     ${$v} = preg_replace('"^/?"', '/', ${$v});
     if (!file_exists($filepath = TEMPLATEPATH . ${$v})) {
	  $filepath = NULL;
      if (!file_exists($filepath = $_SERVER['DOCUMENT_ROOT'] . ${$v})) $filepath = NULL;
	  if (!file_exists($filepath = TEMPLATEPATH . $part_dir . '/' . ${$v})) $filepath = NULL;
	 }
	}
    if ($filepath) {
	 if ($v == 'php') { include($filepath); return; }
	 else {
	  if ($v == 'webpage') {
	   $iframe_params = '';
	   foreach (array('class','width','height','scrolling','id') as $k) {
	    if ($params[$k]) $iframe_params .= ' ' . $k . '="' . $params[$k] . '"';
	   };
	   return '<iframe  src="' . $filepath . '"' . $iframe_params . '></iframe>';
	  }
	  return file_get_contents($filepath);
	 }
	}
   }
  }
 }
 if ($post = remove_sc_quotes($post)) {
  $the_post = get_post($post);
  $c = apply_filters('the_content', $the_post->post_content);
  return $c;
 }
 return;
}
add_shortcode("include", "sc_include");


function sc_permalink($atts, $content) {
 global $wp_custom_functions;
 extract( $wp_custom_functions->parse_args(array(
  'id'		 => get_the_ID(),
  'post_name'=> '',
  'target'	 => '',
  'class'	 => '',
  'rel'		 => '',
  'title'	 => '',
  'category' => '',
  'tax_query'=> '',
  'segment'	 => '',
 ), $atts) );

 $attrs = array();
 $content = trim($content);
 $class = explode(',', $class);
 if (in_array('permalink', $class)) array_push($class, 'permalink');
 foreach (qw('id target rel title class') as $p)  $attrs[$p] = ${$p};
 $c = NULL;
 foreach (array('id', 'slug') as $f) {
  $c = get_term_by($f, $category, 'category');
  if ($c) {
   $category = $c->term_id ;
   break ;
  }
 }

 if ($post_name && $post_by_name=get_posts('name='.$post_name) && count($post_by_name)>0) $id = $post_by_name[0]->ID;

 $attrs['href'] = (($category)? get_category_link($category) : get_permalink($id)) . ($segment ? '#'.$segment : '');

 if ( !empty($content) ) return createHTMLElement('a', $attrs, $content);
 else return get_permalink($id);
}
add_shortcode('permalink', 'sc_permalink');

$func_tdu = function() { return get_template_directory_uri(); }
;
add_shortcode('templatepath', $func_tdu);
add_shortcode('templatedir', $func_tdu);
add_shortcode('template_url', $func_tdu);

function sc_print_meta($atts) {
 global $wp_custom_functions;
 extract($wp_custom_functions->parse_args(array(
  'id'		 => get_the_ID(),
  'name'	 => NULL,
  'order'	 => NULL,
  'concat'	 => "\t"
 ), $atts));

 list($name, $order, $concat) = remove_sc_quotes(array($name, $order, $concat));

 if (empty($name)) return;
 $meta = get_post_meta($id, $name, false);
 if ($order > 0) return $meta[$order - 1];
 return implode($concat, $meta);
 return;
}
add_shortcode('printmeta','sc_print_meta');


function sc_get_category_link($atts) {
 global $wp_custom_functions;
 extract($wp_custom_functions->parse_args(array(
  'id'		 => get_the_ID(),
  'attr'	 => array()
 ), $atts));
 if (!$id) return;
 $c = get_category($id);
 $attr['href'] = get_category_link($c->cat_ID);
 return LF . createHTMLElement('a', $attr, $c->name) . LF . LF;
}
add_shortcode('getCategoryLink','sc_get_category_link');


function sc_get_bloginfo($atts) {
 global $wp_custom_functions;
 extract($wp_custom_functions->parse_args(array(
  'key'	 => 'name',
  'cat'	 => NULL
 ), $atts));
 if ((isset($atts[0]) && !empty($atts[0])) && (!isset($atts['key']) || empty($atts['key']))) {
  $key = $atts[0];
 }
 $key = (array) explode(',', $key);
 $bloginfo = array();
 foreach ($key as $k) $bloginfo[] = wpcf_bloginfo($k);
 return implode($cat, $bloginfo);
}
add_shortcode('bloginfo','sc_get_bloginfo');


function sc_html($atts, $content) {
 global $wp_custom_functions;

 if (!isset($atts['e']) && isset($atts[0])) {
  $atts['e'] = $atts[0];
 }
 $content = do_shortcode(trim($content));

 if (!isset($atts['e']) || !isset($atts[0])) {
  return $content;
 }

 $a = array();
 $e = $atts['e'];

 foreach (html_attrs() as $k) $a[$k] = NULL;
 unset($a['e']);

 $attrs = $wp_custom_functions->parse_args($a, $atts);

 $attrs['class'] = preg_replace('/,/', ' ', remove_sc_quotes($attrs['class']));
 return createHTMLElement($atts['e'], $attrs, $content);
}
add_shortcode('html', 'sc_html', 1);


function sc_a($atts, $content) {
 if ($atts['class']) $atts['class'] = preg_replace('/,/', ' ', remove_sc_quotes($atts['class']));
 $content = trim($content);
 return createHTMLElement('a', $atts, (($atts['filter'])? apply_filters($atts['filter'], $content) : $content));
}
add_shortcode('a', 'sc_a');


function sc_div($atts, $content) {
 if ($atts['class']) $atts['class'] = preg_replace('/,/', ' ', remove_sc_quotes($atts['class']));
 $content = trim($content);
 return LF . createHTMLElement('div', $atts, (($atts['filter'])? apply_filters($atts['filter'], $content) : $content)) . LF . LF;
}
add_shortcode('div', 'sc_div');


function sc_htmlcomment ($atts, $content) {
 return LF . createHTMLElement('_comment', $content) . LF . LF; 
}
add_shortcode('htmlcomment', 'sc_htmlcomment');


function sc_cdata ($atts, $content) {
 return createHTMLElement('_cdata', $content); 
}
add_shortcode('cdata', 'sc_cdata');


function sc_nav_menu($atts = array()) {
 global $wp_custom_functions, $wp_custom_attributes;
 $wp_custom_attributes['shortcode_function'] = __FUNCTION__;
 $wp_custom_attributes['function'] = __FUNCTION__;

 $html5 = apply_filters('WPCF_HTML5_Capable',NULL);
 $args = $wp_custom_functions->parse_args(array(
  'theme_location'	 => '',
  'menu'			 => (isset($atts[0]) && !isset($atts['menu']) ? $atts[0] : ''), 
  'container'		 => $html5 ? 'nav' : 'div', 
  'container_class'	 => '', 
  'container_id'	 => '', 
  'menu_class'		 => 'menu', 
  'menu_id'			 => '',
  'fallback_cb'		 => 'wp_page_menu',
  'before'			 => '',
  'after'			 => '',
  'link_before'		 => '',
  'link_after'		 => '',
  'depth'			 => 0,
  'walker'			 => '',
  'anchor_class'	 => '',
  'clearfix'		 => 1,
  'clearfix_class'	 => 'clearfix',
 ), $atts);
 $args['echo'] = false;
 $menu = wp_nav_menu($args);
 if ($args['clearfix'] && ! preg_match('/'.$args['clearfix_class'].'/', $args['container_class'])) {
  $menu = preg_replace('/(<'.$args['container'].'[^>]*?class=\x22)(.*?)(\x22)/', '$1$2 '.$args['clearfix_class'].'$3', $menu);
 }
 if ($classname = $args['anchor_class']) {
  if (is_array($classname)) $classname = implode(' ', $classname);
  $menu = preg_replace('/(<a )/', '$1 class="'.$classname.'" ', $menu);
 }
 $menu_li = explode('</li>', $menu);
 foreach ($menu_li as &$li) {
  $hrefre = '/(<a .*?href=(?:\x22|\x27))(.*?)(\x22|\x27)/';
  preg_match($hrefre, $li, $m);
  if (!isset($m[2]) || !$m[2]) continue;
  $href = $m[2];
  $href = str_replace(wpcf_bloginfo('home'), '', $href);
  $href = str_replace('/', '_', $href);
  $href = preg_replace('/[%#:]/', '-', $href);
  $href = preg_replace('/^_?(.*?)_?$/', '$1', $href);
  $cn = 'menu-item-'.$href;
  $li = preg_replace('/(<li .*?)(class=(?:\x22|\x27))(.*?)(\x22|\x27)/', '$1$2$3 '.$cn.'$4', $li);
 }
 return implode('</li>', $menu_li);
// return $menu;
}
add_shortcode('nav_menu', 'sc_nav_menu');


function sc_list_bookmarks($atts) {
 $defaults = array(
 'orderby'		 => 'name', //'id','url','name','target','description','owner','rating','updated','rel','notes','rss','length','rand'
  // or 'order' to use mylinkorder
 'order'		 => 'ASC',
 'limit'		 => -1,
 'category'		 => '',
 'category_name' => '',
 'exclude_category' => '',
 'exclude'		 => '',
 'include'		 => '',
 'show_private'	 => 0,
 'hide_invisible' => 1,
 'show_name'	 => 1,
 'show_images'	 => 1,
 'show_rating'	 => 0,
 'show_updated'	 => 0,
 'show_description' => 1,
 'echo'			 => 0,
 'categorize'	 => 1,
 'title_li'		 => __('Bookmarks'),
 'title_before'	 => '<h2>',
 'title_after'	 => '</h2>',
 'link_before'	 => '',
 'link_after'	 => '',
 'title_element'	 => '',
 'category_orderby' => 'name',
 'category_order' => 'ASC',
 'class'		 => 'linkcat',
 'category_before' => '<div id="%id" class="%class">',
 'category_after'	 => '</div>',
 'categorize' => 1,
 'before'		 => '<li>',
 'after'		 => '</li>',
 'between'		 => "\n",
 'ul_class'		 => 'bookmarks',
 'ul_id'		 => '',
 'image_width'	 => 0,
 'image_height'	 => 0,
 'stretch_image' => 0,
 'break_after_image' => 1,
 );
 if (isset($atts['title_element']) && $e = $atts['title_element']) {
  $atts['title_before'] = '<'.$e.'>';
  $atts['title_after']  = '</'.$e.'>';
 }
 $args = shortcode_atts($defaults, $atts);
 $args['echo'] = 0;
 if (isset($args['unescape']) && $args['unescape']) {
  foreach(array('title_before', 'title_after', 'category_before', 'catetory_after') as $k) {
   $args[$k] = str_replace('&gt;', '>');
   $args[$k] = str_replace('&lt;', '<');
   $args[$k] = str_replace('&amp;', '&');
   $args[$k] = str_replace('&nbsp;', ' ');
   $args[$k] = str_replace('&quot;', '"');
  }
 }
 $bookmarks = '';
 if (function_exists('mylinkorder_list_bookmarks')) {
  $bookmarks = mylinkorder_list_bookmarks($args);
 }
 else $bookmarks = wp_list_bookmarks($args);
 
 if ($args['break_after_image']) $bookmarks = preg_replace('/(<img .*?>)/', '$1<br />', $bookmarks);
 if ($args['image_width'] || $args['image_height']) {
  // if set get image dimensions and calculate width and height; only if file exists in localhost
  $dim_1 = ''; $dim_2 = ''; $dim_1_length = 0;
  if ($dim_1_length = $args['image_width']) list($dim_1, $dim_2) = array('width', 'height');
  else if ($dim_1_length = $args['image_height']) list($dim_1, $dim_2) = array('height', 'width');
  $args['stretch_image'] = ($args['stretch_image']) ? 1 : 0;
  $bookmarks = preg_replace_callback(
   '/<img.*?src="(.*?)".*?>/',
   function ($matches) use ($args, $dim_1, $dim_2, $dim_1_length, $dim_2_length) {
    $u=parse_url($matches[1]); 
    $img=IMG($u["path"],array("array"=>1));
    if(!empty($img) && (($args['stretch_image'] && $img[$dim_1] < $dim_1_length) || ($img[$dim_1] > $dim_1_length))) {
     $img[$dim_2]=round($dim_1_length/$img[$dim_1]*$img[$dim_2]);$img[$dim_1]=$dim_1_length;
     return createHTMLElement("img",$img);
    }
    else return $matches[0];
   },
   $bookmarks
  );
 }
 if (!$args['title_li']) {
  return createHTMLElement('ul', array('class'=>$args['ul_class'], 'id'=>$args['ul_id']), $bookmarks);
 }
 return $bookmarks;
}
add_shortcode('list_bookmarks', 'sc_list_bookmarks');


function sc_list_categories($atts) {
 global $wp_custom_functions;
 $defaults = array(
	'show_option_all'    => '',
	'orderby'            => 'name', // 'order' to take effect of the plugin 'My Category Order'
	'order'              => 'ASC',
	'show_last_update'   => 0,
	'style'              => 'list',
	'show_count'         => 0,
	'hide_empty'         => 1,
	'use_desc_for_title' => 1,
	'child_of'           => 0,
	'feed'               => '',
	'feed_type'          => '',
	'feed_image'         => '',
	'exclude'            => '',
	'exclude_tree'       => '',
	'include'            => '',
	'hierarchical'       => true,
	'title_li'           => __( 'Categories' ),
	'number'             => NULL,
	'echo'               => 1,
	'depth'              => 0,
	'current_category'   => 0,
	'pad_counts'         => 0,
	'taxonomy'           => 'category',
	'walker'             => 'Walker_Category',
	'container'			 => 'ul',
	'container_id'		 => '',
	'id'				 => NULL,
	'container_class'	 => 'list_categories',
	'class'	 => NULL,
	'term_image'		 => 0,
	'term_image_position'=> 'before',
	'term_image_size'	 => 'thumbnail',
 );
 $args = $wp_custom_functions->parse_args($defaults, $atts);
 global $wp_custom_attributes;
 $args['echo'] = 0;
 $wp_custom_attributes = $args;
 if ($args['id'] && !$args['container_id']) {
  $args['container_id'] = $args['id'];
 }
 if ($args['class'] && 'list_categories' == $args['container_class']) {
  $args['container_class'] = $args['class'];
 }
 $wrapper_attrs = array(
  'class' => explode(',',$args['container_class']),
  'id'	  => $args['container_id']
 );
 if ($wrapper_attrs['id'] === '') unset($wrapper_attrs['id']);
 if ($args['term_image']) {
  add_filter('list_cats', 'add_term_image_to_term_list');
 }
 
 if (($args['container'] != 'ul') && ($args['container'] != 'ol')) {
  $d = createHTMLElement('ul', $wrapper_attrs, wp_list_categories($args));
  if (array_key_exists('id', $wrapper_attrs)) unset($wrapper_attrs['id']);
 }
 else $d = wp_list_categories($args);

 if ($args['term_image']) {
  remove_filter('list_cats', 'add_term_image_to_term_list');
 }
 
 $wp_custom_attributes = NULL;
 return createHTMLElement($args['container'], $wrapper_attrs, $d);
}
add_shortcode('list_categories', 'sc_list_categories');


function sc_jquery_code($atts, $content) {
 return wrapJavaScript($content, $atts);
}
add_shortcode('jquerycode', 'sc_jquery_code');


function sc_postinfo ($atts) {
 global $wp_custom_functions;
 
 if ($a = array_value($atts, 0)) {
  $atts['key'] = $a;
 }
 $atts = $wp_custom_functions->parse_args(array(
  'key' => 'ID'
 ), $atts);
 global $post;
 return $post->{$atts['key']};
}
add_shortcode('postinfo', 'sc_postinfo');


function sc_image_caption($atts,$content) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'container_class'=>'',
  'container_id'=>'',
 ), $atts);
 $re_image = '(?:<a .*?>)?<img .*?\x2f>(?:<\x2fa>)?';
 $re_white_space = '(?:\xc2\xa0)|(?:&nbsp;)';
 $str = '';

 $content = preg_replace(sprintf('/(%s)/',$re_white_space),'', $content);
 preg_match_all(sprintf('/(%s)/',$re_image), $content, $m);
 if (!empty($m) && !empty($m[1])) {
  foreach ($m[1] as $img) {
   $str .= createHTMLElement('div', array('class'=>'image-caption-img'), $img);
  }
 }
 $str .= createHTMLElement('div', array('class'=>'image-caption-caption'), preg_replace(sprintf('/%s/',$re_image), '', $content));
 $str = preg_replace('/(\x2fdiv>)(?:'.$re_white_space.'|(?:\s))?<br[ \x2f]?>(?:'.$re_white_space.'|(?:\s))?(<div)/', '$1$2', $str);
 return createHTMLElement('div',
  array(
   'class'=>array_merge(array('image-caption-box','image_count_'.count($m[1])),explode(',',$atts['container_class'])),
   'id'=>$atts['container_id']
  ),
 $str);
}
add_shortcode('image_caption', 'sc_image_caption');


function sc_bloglist($atts) {
 global $wp_custom_functions;
 $html = '';
 $blog_info = array();
 $atts = $wp_custom_functions->parse_args(array(
  'blogs'	 => '',
  'fields'	 => 'name,description',
  'id'		 => '',
  'target'	 => '_blank'
 ), $atts);
 
 $fields = (array) explode(',', $atts['fields']);
 $id_prefix = $atts['id'] ? $atts['id'].'_' : '';
 
 foreach (get_blogs_by_name(explode(',', $atts['blogs'])) as $id) {
  switch_to_blog($id);
  $h = '';
  $blog_info[$id] = array(
   'href' => wpcf_bloginfo('url')
  );
  $item_id = $id_prefix.'blog_'.$id.'_info';
  foreach ($fields as $f) {
   if ($f == 'url') $blog_info[$id]['url'] = $blog_info[$id]['href'];
   else {
    $blog_info[$id][$f] = wpcf_bloginfo($f);
   }
   $h .= createHTMLElement('span', array('id'=>$item_id.'_'.$f, 'class'=>$id_prefix.'info_'.$f), $blog_info[$id][$f]) . LF;
  }
  $html .= createHTMLElement('li', array('id'=>$item_id, 'class'=>$id_prefix.'blog_info'),
   createHTMLElement('a', array('href'=>$blog_info[$id]['href'], 'target'=>$atts['target']), $h)
  ) . LF;
  restore_current_blog();
 }
 return createHTMLElement('ul', array('id'=>$atts['id']), $html);
}
add_shortcode('bloglist', 'sc_bloglist');


function sc_display_rate($atts) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'rate' => 0,
  'number' => 5,
  'max' => 5,
  'id' => 'rate_'.uniqid()
 ), $atts);
 return createHTMLElement('div', array('class'=>'raty_rate', 'id'=>$atts['id']), NULL) 
  . wrapJavaScript('$("#'.$atts['id'].'").raty_apply({readOnly:true,score:"'.$atts['rate'].'"});', array('jqueryready'=>TRUE, 'jquery'=>TRUE));
}
function display_rate($atts = NULL) { return sc_display_rate($atts); }
add_shortcode('display_rate', 'sc_display_rate');


/* ////// Image Gallery ////// */
function sc_custom_gallery($atts) { //if (is_specific_user_logged_in(1)) my_print_r($atts);
 global $post, $wp_custom_functions
 ;
 // refer to : http://wordpress.stackexchange.com/questions/88957/ordering-of-gallery-images-without-using-shortcode-in-theme
 $query_args = array(
  'category'		 => NULL,
  'category_name'	 => NULL,
  'category__and'	 => NULL,
  'category__in'	 => NULL,
  'category__not_in' => NULL,
  'tag'				 => NULL,
//  'orderby'			 => 'menu_order',
      // 'author' | 'category' | 'content' | 'date' | 'ID' | 'menu_order' | 'mime_type' |
      // 'modified' | 'name' | 'parent' | 'password' | 'rand' | 'status' | 'title' | 'type' | 'RAND'
//  'order'			 => 'DESC', // 'ASC'
  'order'			 => 'menu_order ID', 
  'orderby'			 => 'post__in', //required to order results based on order specified the "include" param
  'posts_per_page'	 => -1,
  'post_type'		 => 'attachment',
  'post_parent'		 => $post->ID,
  'post_status'		 => 'inherit', 
  'post_mime_type'	 => 'image', 
 );

 $itemtag = isset($atts['itemtag'])? $atts['itemtag'] : 
  ( !isset($atts['container_tag']) || ( isset($atts['container_tag']) && in_array($atts['container_tag'], array('ul', 'ol') ) ) ?
   'li'
   :
   'div'
  )
 ;
 $atts = $wp_custom_functions->parse_args(array_merge($query_args, array(
  'size'	 => 'thumbnail',
  'link'	 => 'file',
  'id'		 => 'gallery_post-' . $post->ID,
  'class'	 => 'gallery',
  'caption'	 => '1',
  'image_link'		 => 'full',
  'container_tag'	 => 'ul',
  'itemtag'			 => $itemtag,
  'captiontag'		 => 'p',
  'include'			 => '',
  'exclude'			 => '',
  'taxonomy'		 => NULL,
   // Like: 'taxonomy=AND(movie_janner->slug(action,comedy)&actor->id(103,115,206);operator->NOT_IN&some_other_tax->slug(term1,term2))'
   // Or: taxonomy=taxonomy_name->id(1,2) taxonomy=taxonomy_name->slug(term1,term2)
   // 'meta_key' => '', 'meta_value' => '', 'offset' => 0 // future use
  'script'			 => '',
  'media_category'	 => 'media_category', // shorhand for 'taxonomy.'  In progress.
  'media_category_name' => '', // same as above
  'clear_width_height' => FALSE, // for stretch image
  'width' => NULL,
  'height' => NULL,
 ) ), $atts); 
 $a = array();
 $taxonomy = NULL;
 $atts['class'] = explode(',', $atts['class']);
 $atts['class'][] = WPCF_PREFIX.'custom_gallery';
 if ($atts['taxonomy']) {
  $taxonomy = $atts['taxonomy'];
  $atts = array_merge($atts, parse_taxonomy_query_string($atts['taxonomy']));
  unset($atts['taxonomy']);
 }
 if ($atts['post_parent'] == 'any' || $atts['post_parent'] == 'ANY') unset($atts['post_parent']);
 $content = '';
 $query_args =  $wp_custom_functions->parse_args($query_args, $atts);
 foreach ($query_args as $k=>$v) { if ($v === NULL) { unset($query_args[$k]); } }

 $attachments = get_posts($query_args); ////////////
//  if (is_specific_user_logged_in(1)) my_print_r($attachments);
 if (count($attachments) == 0) return;
 if (is_specific_user_logged_in(1)) {
  // my_print_r($query_args);
 }
 $count = 0;
 $exclude = (array) explode(',', $atts['exclude']);
 foreach ($attachments as $a) { 
  $count++;
  if (in_array($a->ID, $exclude)) continue;
  if ($atts['posts_per_page'] != -1 && ((boolean) $atts['posts_per_page']) && $atts['posts_per_page'] < $count) break;
  $html = '';
  if ($src = wp_get_attachment_image_src($a->ID, $atts['size'])) $html = wp_get_attachment_image($a->ID, $atts['size']);
  else {
   $src = wp_get_attachment_image_src($a->ID);
   $html = wp_get_attachment_image($a->ID);
  }
  
  if ($atts['clear_width_height']) {
   $html = preg_replace('/\x20(?:width|height)="\d+?"/', '', $html);
  }
  foreach (array('width', 'height') as $wh_prop) {
   if ($atts[$wh_prop] !== NULL) {
	$html = preg_replace('/'.$wh_prop.'="\d+?"/', $wh_prop.'="'.$atts[$wh_prop].'"', $html);
   }
  }
  if ($atts['image_link']) {
   $src_link = wp_get_attachment_image_src($a->ID, $atts['image_link']);
   $html = createHTMLElement('a',
	array('href'=>$src_link[0], 'class'=>array_map(function($c){return $c."-item-link";}, $atts['class']), 'id'=>'attachment-link-'.$a->ID, 'rel'=>$atts['class'][0].'-attachment-'.$post->ID), $html
   );
  }

  if ($atts['caption']) {
   $caption_atts = array(
    'id' => 'attachment_' . $a->ID,
	'width' => $src[1],
    'caption' => $a->post_excerpt
   );
   $sc_caption_atts = '';
   foreach ($caption_atts as $k => $v) $sc_caption_atts .= ' ' . $k . '="' . $v . '"';
   $html = do_shortcode('[caption' . $sc_caption_atts . ']' . $html . '[/caption]');
  }
  $content .= LF
   . createHTMLElement($atts['itemtag'],
	  array('class'=>array_merge(
	   array_map(function($c){return $c."-attachment-wrap";}, $atts['class']),
	   array_map(function($c){return $c."-attachment";}, $atts['class']),
	   array_map(function($c)use($post){return $c."-attachment-".$post->ID;}, $atts['class'])
	  ) ),
      $html
     )
   . LF
  ;
 }
 return LF . createHTMLElement($atts['container_tag'], array('id' => $atts['id'], 'class' => $atts['class']), $content) . LF;
}
add_shortcode('customgallery', 'sc_custom_gallery');







/* Social */
function social_share_box($atts=NULL) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args( array(
  'sites' => array('facebook', 'twitter', 'googleplusone')
 ), $atts );
 $f = array(
  'facebook' => 'facebook_like_button',
  'twitter' => 'twitter_button',
  'googleplusone' => 'googleplusone'
 );
 $share = '';
 foreach ((array) $atts['sites'] as $s) { 
  if (isset($f[$s]) && function_exists($f[$s])) $share .= $f[$s]();
 }
 return createHTMLElement( 'div', array('id'=>'social_box'), $share );
}
function sc_social_share_box($atts) {
 if (isset($atts['sites']) && is_string($atts['sites'])) { $atts['sites'] = explode(',', $atts['sites']); }
 return social_share_box($atts);
}
add_shortcode('social_share', 'sc_social_share_box');

function googleplusone($atts=NULL) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'size' => NULL, // small, mediam, tall, [none]
  'annotation' => 'inline', // baloon
  'width' => 240,
  'lang' => get_option("WPLANG")
 ), $atts);
 
 $a = array(
  'class' => "g-plusone",
 );
 if ($atts['size']) $a['data-size'] = $atts['size'];
 if ($atts['annotation'] == "inline") {
  $a['data-annotation'] = $atts['annotation'];
  $a['data-width'] = $atts['width'];
 }
 $aa = array();
 foreach ($a as $a_=>$v_) {
  $aa[] = sprintf('%s="%s"', $a_, $v_);
 }
 return '<div '.implode(' ', $aa).'></div>' .
 wrapJavaScript('window.___gcfg={lang:"'.$atts['lang'].'"};(function(){var a=document.createElement("script");a.type="text/javascript";a.async=true;a.src="https://apis.google.com/js/plusone.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})()');
}
function sc_googleplusone($atts) {
 return googleplusone($atts);
}
add_shortcode('googoeplusone', 'sc_googleplusone');
function sc_old_googlePlusOne($atts) {
 $atts = shortcode_atts(array(
  'size' => 'medium'
 ), $atts);
 return createHTMLElement('g:plusone', array('size'=>$atts['size']));
}


function sc_facebookLike($atts) {
 global $post, $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'type'=>'xfbml', // iframe
  'url' => get_permalink($post->ID)
 ), $atts);
 
 switch ($atts['type']) {
  case 'iframe' :
   return '<iframe src="http://www.facebook.com/plugins/like.php?href=' . urlencode($atts['url']) . 
   '&amp;send=false&amp;layout=button_count&amp;width=96&amp;show_faces=true&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:96px; height:21px;" allowTransparency="true"></iframe>';

  case 'xfbml' :
   return '<div id="fb-root"></div><script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script><fb:like href="' .
    urlencode($atts['url']) . '" send="false" layout="button_count" width="96" show_faces="true"></fb:like>';
 }
}
add_shortcode('facebooklike', 'sc_facebookLike');


function facebook_like_button($atts=NULL) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args( array(
  'send_button'	 => false,
  'layout'		 => '', //'button_count', 'box_count'
  'width'		 => '240',
  'href'		 => $_SERVER['REQUEST_URI'],
  'show_faces'	 => false,
  'font'		 => 'arial', // 'lucida grande', 'segoe ui', 'tahoma', 'trebuchet ms', 'verdana'
  'color'		 => 'light',
  'verb'		 => 'like', // 'recommend'
 ), $atts);

/* <script>(function(d, s, id) {
  var js,fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script> */

 $a = array(
  'class'		 => 'fb-like',
  'data-href'	 => $atts['href'],
  'data-show-faces' => $atts['show_faces'] ? 'true' : 'false',
  'data-width'	 => $atts['width'],
  'data-font'	 => $atts['font'],
  'data-colorscheme' => $atts['color'],
  'data-layout'	 => $atts['layout'],
  'data-send'	 => $atts['send_button'] ? 'true' : 'false',
  'data-action'	 => $atts['verb'],
 );
 $aa = array();
 foreach ($a as $a_=>$v_) {
  $aa[] = sprintf('%s="%s"', $a_, $v_);
 }
 return 
  '<div id="fb-root"></div>'
  . wrapJavaScript('(function(e,a,f){var c,b=e.getElementsByTagName(a)[0];if(e.getElementById(f)){return}c=e.createElement(a);c.id=f;c.src="//connect.facebook.net/ja_JP/all.js#xfbml=1";b.parentNode.insertBefore(c,b)}(document,"script","facebook-jssdk"));')
  . '<div ' . implode(' ', $aa) . ' ></div>';
}


function twitter_button() {
 return createHTMLElement('a', array('href'=>"https://twitter.com/share", 'class'=>"twitter-share-button", 'data-lang'=>"ja"), 'ツイート')
  . wrapJavaScript("!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document,'script','twitter-wjs');");
}


function sc_tweet($atts) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'count'=> 'vertical', // horizontal, none
  'lang' => 'ja',
  'text' => 'Tweet'
 ), $atts);

 return createHTMLElement('a', array(
  'href' => 'http://twitter.com/share',
  'class'=> 'twitter-share-button',
  'data-count'=>$atts['count']),
  $atts['text']
 ) . '<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>';
}
add_shortcode('tweet', 'sc_tweet');


function sc_totop($atts, $content) {
 $text = isset($atts['text']) && isset($atts['text']) ? $atts['text'] : $content;
 return do_shortcode('[anchor segment="content"]'.$text.'[/anchor]');
/*
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'text' => 'TOP'
 ), $atts);
 return createHTMLElement('p', array('class'=>'totop'), createHTMLElement('a', array('href'=>'#content'), $content?$content:$atts['text']));
//*/
}
add_shortcode('totop', 'sc_totop');


function sc_segment_anchor($atts, $content) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'class' => NULL,
  'id' => NULL,
  'segment' => 'page'
 ), $atts);
 return createHTMLElement('p',
  array('class'=>$atts['class'], 'id'=>$atts['id']),
  createHTMLElement('a', array('href'=>'#'.$atts['segment']), $content)
 );
}
add_shortcode('anchor', 'sc_segment_anchor');


function sc_media_player($atts, $content) {
 global $playerCount, $wp_custom_functions;
 if (!$playerCount) $playerCount = 1;
 if (file_exists($f = $_SERVER['DOCUMENT_ROOT'] . '/lib/php/JSON/JSON.php')) require_once($f); 
 $json = new Services_JSON; 

 $formats_video = qw('mp4 m4v mpeg4 3gp flv');
 $formats_sound = qw('m4a mp3 aac oga');
 $formats = array_merge($formats_video, $formats_sound);

 $skins = qw('qwcarbon classic lightrv5 metal metall newtube nikitaskin');
 
 $defaults = array(
  'auto' => 0,
  'media' => '',
  'attachment_id' => '',
  'width' => 360,
  'height' => 270,
  'playlist' => 0, // playlist size, also used as boolean
  'playlist_position' => 'bottom',
  'title' => '',
  'skin' => 'newtube',
  'poster_image' => ''
 );
 
 $atts = $wp_custom_functions->parse_args($defaults, $atts);
 extract($atts);

 $skin = (array_search($skin, $skins))? $skin : $defaults['skin'];
 list($poster_image_video, $poster_image_sound, $poster_image_youtube)
  = array(WP_PLUGIN_URL.'/images/mediaplayer_poster_dimg_video.png', WP_PLUGIN_URL.'/images/mediaplayer_poster_dimg_sound.png', WP_PLUGIN_URL.'/images/mediaplayer_poster_dimg_youtube.png');
 $params = array(
  'flashplayer' => WP_PLUGIN_URL.'/js/jwplayer/player.swf',
  'height' => $height,
  'width' => $width,
  'skin' => WP_PLUGIN_URL.'/js/jwplayer/skins/' . $skin . '.zip'
 );
 if ($playlist) {
  if (($playlist === 1) || (preg_match('/\D/', $playlist))) { $playlist = round($height / 2); }
  $params['playlist.size'] = $playlist;
  $params['playlist.position'] = $playlist_position;
  $params['height'] = $params['height'] + $playlist;
 }
 
 if ($media || ($attachment_id && !$media)) {
  if ($attachment_id) {
   $media = array();
   $attachment_id = (array) explode(',', $attachment_id);
   foreach ($attachment_id as $aid) $media[] = wp_get_attachment_url($aid);
  }
  else $media = (array) explode(',', $media);
  
  $media_list = array();
  
  if ($title) $title = explode(',', $title);
  if ($poster_image) $poster_image = explode(',', $poster_image);
  
  for ($i = 0; $i < count($media); $i++) {
   preg_match('/([^\x2f]*?)\x2e([^\x2e\x2f]*?)$/', $media[$i], $matches);
   list($suffix, $filename) = array($matches[2], $matches[1]);
   
   $is_youtube = preg_match('/^http\x3a\/\/www\x2eyoutube\x2ecom\/watch\?v=.*?$/', $media[$i]);

   if ((array_search($suffix, $formats) === false) && !$is_youtube) continue;
   
   $media_list[$i] = array(
    'file'  => $media[$i],
	'title' => ($title[$i] ? $title[$i] : $filename),
	'image' => ($poster_image[$i] ?
     $poster_image[$i]
     :
     ((array_search($suffix, $formats_video) !== false)?
      $poster_image_video
      :
      (($is_youtube) ?
       $poster_image_youtube
        :
       $poster_image_sound
	  ))
    )
   );
  }

  if (count($media_list)) {
   $content = createHTMLElement('script', array('type'=>'text/javascript', 'src'=>WP_PLUGIN_URL.'/js/jwplayer/jwplayer.js'), '');

   if ($playlist) {
    $player_script = '$(\'<div id="media_player_container_' . $playerCount . '"></div>\').insertAfter($(\'#media_player_script_' . $playerCount . '\'));';
	$params['playlist'] = $media_list;
    $player_script .= 'jwplayer("media_player_container_' . $playerCount . '").setup(' . $json->encode($params) . ')';
	$content .= wrapJavaScript($player_script, array('jquery'=>true, 'jqueryready'=>true), array('id'=>'media_player_script_' . $playerCount));
	$playerCount++;
   }
   else {
    foreach($media_list as $m) {
     $player_script = '$(\'<div id="media_player_container_' . $playerCount . '"></div>\').insertAfter($(\'#media_player_script_' . $playerCount . '\'));';
	 $m = array_merge($m, $params);
     $player_script .= 'jwplayer("media_player_container_' . $playerCount . '").setup(' . $json->encode($m) . ')';
	 $content .= wrapJavaScript($player_script, array('jquery'=>true, 'jqueryready'=>true), array('id'=>'media_player_script_' . $playerCount));
	 $playerCount++;
    }
   }
   return $content;
  }
 }
 return;
}
add_shortcode('media_player', 'sc_media_player');


function sc_code($atts, $content) {
 global $wp_custom_functions;
 $atts = $wp_custom_functions->parse_args(array(
  'lang' => '',
  'code' => ''
 ), $atts);
 
 if (!$atts['code'] && !$content) return;
 
 if ($content) {
  return preg_replace('/<pre/', '<pre name="code" class="' . $atts['lang'] . '"', $content);
 }
 else {
  return '<pre name="code" class="' . $atts['lang'] . '">' . $atts['code'] . '</pre>';
 }
}
add_shortcode('code', 'sc_code');


function sc_remote_site_content_summary($atts) {
 if (!isset($atts['title_tag']) && isset($atts['title_li'])) $atts['title_tag'] = $atts['title_li'];
 if (!class_exists('RemoteSiteContentSummary')) return createHTMLElement('_comment', 'class RemoteSiteContentSummary does not exist.');
 $rcs = new RemoteSiteContentSummary($atts);
 if (isset($atts['exclude_match'])) {
  $a = array();
  foreach (preg_split('/, ?/', $atts['exclude_match']) as $i) {
   $j = preg_split('/: ?/', $i, 2);
   $a[$j[0]] = $j[1];
  }
  $atts['exclude_match'] = $a;
 }
 return $rcs->sc($atts);

/* // Attributes:
 $atts_tmp = parse_args(array('class'=>'list_site_summary', 'id'=>''), $atts);
 $atts_tmp['container_class'] = $atts_tmp['class'].'_container';
 $atts_tmp['title_class'] = $atts_tmp['class'].'_title';
 $atts = shortcode_atts(array(
  'feedurl'			 => '',
  'preset'			 => '',
  'preset_index'	 => 0,
  'keywords'		 => '',
  'refresh'			 => 60*60,
  'posts_per_page'	 => 5,
  'item_tag'		 => apply_filters('WPCF_HTML5_Capable',NULL) ? 'article' : 'div',
  'item_class'		 => $atts_tmp['class'] . '_item',
  'title_tag'		 => apply_filters('WPCF_HTML5_Capable',NULL) ? 'h1' : 'div',
  'title_class'		 =>  $atts_tmp['title_class'],
  'link_title'		 => 1,
  'show_description' => '',
  'description_container' => 'p',
  'truncate'		 => '',
  'remove_tags'		 => false,
  'morelinktext'	 => '...',
  'morelink'		 => TRUE, // wheather make more link
  'target'			 => '_blank',
  'show_channel'	 => '',
  'channel_title'	 => '',
  'channel_tag'		 => 'h3',
  'showdate'		 => '',
  'resize_image'	 => FALSE, // Under Dev.
  'resize_image_width' => NULL, // Under Dev.
  'container_class'	 => $atts_tmp['container_class'],
  'container_id'	 => ($atts_tmp['id'] ? $atts_tmp['id'] : $atts_tmp['class']) . '_container',
  'exclude_match'	 => array('field'=>'/RegEx/'),
   'strip_tags'		 => FALSE,
   'default_filter'	 => 1,
   'allow_duplicate_id' => FALSE,
 ), $atts);
*/
}
add_shortcode('site_summary', 'sc_remote_site_content_summary');


function parse_taxonomy_query_string($string = NULL, $return_tax_query_key=true) {
// parses taxonomy query in shortcode;
// [{shortcode} taxonomy=AND(movie_janner->slug(action,comedy)&actor->id(103,115,206);operator->NOT_IN)]
//  as taxonomy_name->field(value,value2)&taxonomy_name_2->field(id1,id2,id3)
 $args = array();
 if (!$string) return $args;
 if ($string = preg_replace('/-(?:(?:&gt;)|>)/', '=>', $string)) {
  if (preg_match('/^(AND|OR)\x28(.*?)\x29$/', $string, $matches)) {
   $args['tax_query'] = array('relation'=>$matches[1]);
   foreach(preg_split('/\x26(?:amp;)?/',$matches[2]) as $t) {
    if ($t) {
     $a = array();
     foreach(explode(';',$t) as $p) {
      $p = explode('=>',$p);
      if (isset($p[1]) && preg_match('/^(.*?)\x28(.*?)\x29$/', $p[1], $m)) {
       $a['taxonomy'] = $p[0];
       $a['field'] = $m[1];
       $a['terms'] = explode(',',$m[2]);
      }
      else {
       $a[$p[0]] = ($p[0] == 'operator' && isset($p[1])) ? str_replace('_', ' ', $p[1]) : '';
      }
     }
     $args['tax_query'][] = $a;
    }
   }
  }
  else {
   if (preg_match('/^(.*?)(?:[-=])\x3e(.*?)\x28(.*?)\x29?$/', $string, $matches)) {
    list($string,$tax,$field,$value) = $matches;
    $args['tax_query'] = array('relation'=>'AND');
	$args['tax_query'][] = array('field'=>$field, 'taxonomy'=>$tax, 'terms'=>explode(',', $value)); //$value; split(',',$term);
   }
  }
 }
 return $return_tax_query_key ? $args : $args['tax_query'];
}


function parse_filter_setting_string($string=NULL) {
// parse filter settings in shortcode
// hook_name=>(99=>filter_name,100=>filter2_name)&the_content=>(10=>my_filter,10=>filter3_name,9999=>my_another_filter)';
 if ((bool) $string) return array();
 $string = explode('&', $string);
 $filters = array();
 foreach ($string as $d) {
  preg_match('/^(.+?)=>\x28(.*?)\x29/', $d, $match);
  if (isset($match[1]) && isset($match[2])) {
   $hook = $match[1]; $filter = $match[2];
   if (!isset($filters[$hook])) $filters[$hook] = array();
   foreach (explode(',', $match[2]) as $m2) {
    list($i, $f) = explode('=>', $m2);
    if (!isset($filters[$hook][$i])) $filters[$hook][$i] = array();
    $filters[$hook][$i][] = $f;
   }
  }
 }
 return $filters;
}

add_shortcode('WPCF_EXPIRE', function($atts, $content) {
 $d = NULL;

 if (current_user_can('edit_posts') && isset($atts['preview']) && $atts['preview']) {
  return '';
 }

 if (isset($atts[0])) {
  $d = 0;
 }
 else if (isset($atts['expire'])) {
  $d = 'expire';
 }

 $due = strtotime($atts[$d]);

 if ($due !== FALSE) {
  if ($due < time()) {
   $content = '';
  }
 }
 else {
  $content .= '<!-- !!!! STRING '.$atts[$d].' DOES NOT LOOK LIKE DATE STRING !!!! -->';
 }
 return $content;
});


add_shortcode('WPCF_UNVEIL', function($atts, $content) {
 $d = NULL;

 if (current_user_can('edit_posts') && isset($atts['preview']) && $atts['preview']) {
  return $content;
 }

 if (isset($atts[0])) {
  $d = 0;
 }
 else if (isset($atts['due'])) {
  $d = 'due';
 }

 $due = strtotime($atts[$d]); 

 if ($due !== FALSE) {
  if ($due > time()) {
   $content = '';
  }
 }
 else {
  $content .= '<!-- !!!! STRING '. $atts[$d] .' DOES NOT LOOK LIKE DATE STRING !!!! -->';
 }

 return $content;
});



add_shortcode('WPCF_EMPTY', function() { return ''; });



function wpcf_get_custom_attribute($key, $func='sc_posts') {
 global $wp_custom_attributes
 ;
 if (
     is_array($wp_custom_attributes)
  && isset( $wp_custom_attributes['_function'] )
  && (!empty($func) && isset($wp_custom_attributes['_function']) && $wp_custom_attributes['_function'] == $func)
  && isset( $wp_custom_attributes[$key] )
 ) {
  return $wp_custom_attributes[$key];
 }
 return NULL
 ;
}

/* // wpcf_print_scripts: prints queued scripts. USAGE: [wpcf_print_scripts handle-1 handle-2 ] // */
add_shortcode('wpcf_print_scripts',
 function($args) {
  // if (is_specific_user_logged_in(1)) my_print_r($args);
  ob_start();
  wp_print_scripts($args);
  $s = ob_get_contents();
  ob_end_clean();
  return $s;
 }
);
/* /// END Short Codes /// */

