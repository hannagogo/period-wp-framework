<?php

/* ////// Taxonomy ////// */
/**
* wpcf_insert_terms inserts terms from given taxonomy term list.
*
* @param array $taxonomies
* @return NULL
*/
function wpcf_insert_terms($taxonomies) {
/* //
// Where:
$taxonomies = array(
 'category' => array(
  'term-1' => 'Term 1',
  'term-2' => array('name'=>'Term 2', 'parent'=>1, 'description'=>'Here Goes Description', 'alias_of'=>'slug'),
  'term-3' => array('name'=>'Term 3', 'parent'=>'term-1', 'description'=>'Some other term'),
// :
 )
)
;
// */
 foreach ($taxonomies as $tax=>$terms) {
  foreach ($terms as $slug=>$term) {
   $existing_terms = get_terms( $tax, array('hide_empty'=>FALSE) );
   $term_name = '';
   $term_arg = array('slug'=>$slug);
   ;
   if (is_string($term)) {
    $term_name = $term;
   }
   else if (is_array($term)) {
    $term_name = $term['name'];
	$parent_term_id = 0;
	if (isset($term['parent'])) {
	 if (preg_match('/[a-z-]/', $term['parent'])) {
	  foreach ($existing_terms as $e_t) {
	   if ($e_t->slug == $term['parent']) { $parent_term_id = $e_t->term_id ; }
	  }
	 }
	 else {
	  $parent_term_id = $term['parent'];
	 }
	}
    $term_arg['parent'] = $parent_term_id
	;
    foreach (array('alias_of', 'description') as $k) {
     if (isset($term[$k])) $term_arg[$k] = $term[$k];
    }
   }
   if (!term_exists($term_name, $tax)) {
    wp_insert_term($term_name, $tax, $term_arg);
   }
  }
 }
 return NULL
 ;
}


/**
* wpcf_add_css_classes_to_category_list appends CSS classes contains slug and name corresponds to the category being processed by Walker_Category
* The CSS classes also could be modified by adding functions to the filter 'WPCF_Add_CSS_Classes_To_Category_List'. 
* In that case, $wpcf_add_css_classes_to_category_list_current_term would be the category(term) object.
*
* @param array List of CSS Classes 
* @return ARRAY
*/
function wpcf_add_css_classes_to_category_list() {
 global $wpcf_add_css_classes_to_category_list_current_term
 ;
 $args = func_get_args();
 $css_classes = NULL;
 if (isset($args[0]) && is_array( $args[0] )) {
  $css_classes = $args[0];
 }

 foreach ($css_classes as $c) {
  if (preg_match('/^cat-item-(\d+)$/', $c, $m)) {
   $cat = get_term($m[1], 'category');
   $wpcf_add_css_classes_to_category_list_current_term = $cat;
   $css_classes[] = 'cat-item-slug-'.$cat->slug;
   $css_classes[] = 'cat-item-name-'.esc_attr($cat->name);
   add_filter('WPCF_Add_CSS_Classes_To_Category_List', function($a){return $a;}, 1, 1);
   $css_classes = apply_filters('WPCF_Add_CSS_Classes_To_Category_List', $css_classes);
  }
 }
 return $css_classes
 ;
}
add_filter('category_css_class', 'wpcf_add_css_classes_to_category_list');



function posts_by_taxonomy_terms($args=array()) {
 $args = parse_args(array(
  'taxonomy'	 => 'category',
  'depth'		 => -1,
  'post_type'	 => 'post',
  'hide_empty'	 => true,
  'parent'		 => 0,
  'orderby'		 => '',
  'class'		 => __FUNCTION__,
  'filter'		 => 'the_excerpt',
  'posts_per_page' => -1,
  'show_content' => false,
  'show_excerpt' => true,
 ), $args);
 $terms = get_terms($args['taxonomy'], array('parent'=>$args['parent'],));
 $args['current_depth'] = 0;
 $html = createHTMLElement('ul', 'start', array('class'=>array($args['class'].'_root', $args['class'].'_depth_'.$args['current_depth'])));
 foreach ($terms as $t) {
  $html .= createHTMLElement('li', NULL, posts_by_taxonomy_terms_get_term_posts(parse_args($args, array('parent'=>$t->term_id))));
 }
 $html .= createHTMLElement('ul', 'end');
 return $html;
}

function posts_by_taxonomy_terms_get_term_posts($args) {
 $args['current_depth']++;
 global $post, $wp_query;
 $html5 = apply_filters('WPCF_HTML5_Capable',NULL);
 if ($args['depth'] > 0 && $args['current_depth'] > $args['depth'] ) return;
 $term = get_term_by('id', $args['parent'], $args['taxonomy']);
 $posts_args = array(
  'post_type'		 => $args['post_type'],
  'tax_query'		 => parse_taxonomy_query_string('AND('.$args['taxonomy'].'->id('.$term->term_id.'))', false),
  'posts_per_page'	 => $args['posts_per_page'],
 );
 $h = createHTMLElement('div', 'start',
  array(
   'class'=>array(
    $args['class'].'_term_posts',
    $args['class'].'_term_'.$term->slug.'_posts',
    $args['class'].'_term_'.$term->term_id.'_posts'
   )
  )
 );
 $h .= createHTMLElement('div', array('class'=>array(
  $args['class'].'_term_name',
  $args['class'].'_term_'.$term->slug,
  $args['class'].'_term_'.$term->term_id
 )), $term->name);
 $h .= createHTMLElement('ul', 'start', array('class'=>array($args['class'].'_posts', $args['class'].'_posts_'.$term->slug, $args['class'].'_depth_'.$args['current_depth'])));
 $post_orig = $post;
 $wp_query_orig = $wp_query;
 $wp_query = new WP_Query($posts_args);

// foreach (get_posts($posts_args) as $post) {
 if (have_posts()) while (have_posts()) {
  the_post();
  ob_start();
  the_content(); $content = ob_get_contents();
  the_excerpt(); $excerpt = ob_get_clean();
  
  $h .= createHTMLElement('li', NULL, createHTMLElement(($html5?'article':'div'), array('class'=>array('article')),
   createHTMLElement(($html5?'h1':'div'), array('class'=>'article_title'), the_title(NULL,NULL,FALSE)) .
   ($args['show_excerpt'] ? createHTMLElement('div', array('class'=>'entry'), $excerpt) : 
    ($args['show_content'] ? createHTMLElement('div', array('class'=>'entry'), $content) : '')
   )
  ) );
 }
 $child_terms = get_term_children($term->term_id, $args['taxonomy']);
 if (!empty($child_terms) && !is_wp_error($child_terms)) {
  $h .= createHTMLElement('li', 'start');
  foreach($child_terms as $ct) {
   $t = get_term_by('id', $ct);
   $h .= posts_by_taxonomy_terms_get_term_posts(parse_args($args, array('parent'=>$ct)));
  }
  $h .= createHTMLElement('li', 'end');
 }
 $h .= createHTMLElement('ul', 'end');
 $h .= createHTMLElement('div', 'end');
 $post = $post_orig;
 $wp_query = $wp_query_orig
 ;
 return $h;
}

function add_term_image_to_term_list($content) {
 $args = array();
 global $wp_custom_attributes
 ;
 if (is_array($content)) { // Called as function
  $args = $content;
  $content = $args['term_name'];
 }
 else $args = (array) $wp_custom_attributes; // function as WordPress Filter: e.g. sc_list_categories > wp_list_categories
 
 if ($taxonomy = $args['taxonomy']) {
  $term = get_term_by('name', $content, $taxonomy);
  $image_id = get_term_image_id($term->term_id, $taxonomy);
  if ($image_id) {
   $img = createHTMLElement('span', array('class'=>'term_image'), attachment_image_html($image_id,array('full_image'=>false,'size'=>$args['term_image_size'])));
   if (isset($wp_custom_attributes['term_image_position']) && $wp_custom_attributes['term_image_position'] == 'before') $content = $img . $content;
   else $content = $content . $img;
  }
 }
 return $content;
// return createHTMLElement('span', array('class'=>'term_list_term_image', 'id'=> attachment_image_html(
}


function taxonomy_term_posts($args=NULL) {
 global $wp_custom_functions, $post, $wp_query
 ;
 $html5 = apply_filters('WPCF_HTML5_Capable', NULL);
 $args = $wp_custom_functions->parse_args(array(
  'taxonomy'		 => 'category',
  'terms'			 => NULL,
  'term_title'		 => TRUE,
  'term_description' => FALSE,
  'post_type'		 => 'post',
  'orderby'			 => 'term_order',
  'order'			 => 'ASC',
  'post_order'		 => 'ASC',
  'post_orderby'	 => 'menu_order',
  'hide_empty'		 => FALSE,
  'title_tag'		 => 'h2',
  'post_title_tag'	 => $html5 ? 'h1':'h3',
  'posts_per_page'	 => -1,
  'child_of'		 => 0,
  'include_children' => TRUE,
  'post_content'	 => TRUE,
  'post_excerpt'	 => FALSE,
  'no_title'		 => FALSE,
  'include'			 => NULL,
  'exclude'			 => NULL,
  'term_box_class'	 => 'taxonomy_term_posts_term_box',
  'term_name_class'	 => 'taxonomy_term_posts-term_name',
  'post_title_class' => 'taxonomy_term_posts-post_title',
  'post_content_class' => 'taxonomy_term_posts-post_content',
  'term_description_class' => 'taxonomy_term-term_description',
  'term_description_no_tag_if_empty' => TRUE,
  'recursive'		 => FALSE,
  'format'			 => '%name%%description%%posts%%children%',
  'depth'			 => 0,
 ), $args);

 $get_terms_args = array('orderby'=>NULL, 'order'=>NULL, 'hide_empty'=>NULL, 'child_of'=>NULL);
 if ( $args['recursive'] ) {
  $get_terms_args['parent'] = $args['child_of'];
  unset($get_terms_args['child_of']);
 }

 $post_orig = $post ;
 $wp_query_orig = $wp_query;
 $html = '';

 $terms = get_terms($args['taxonomy'], $wp_custom_functions->parse_args( $get_terms_args, $args) );
 
 $depth = $args['depth'];

 foreach ($terms as $term) {
  if (!term_exists($term->name, $args['taxonomy'])) { continue; }

  $f = array('posts'=>'', 'description'=>'', 'name'=>'', 'children'=>'',);

  $exit_loop = false;
  $term_chk_fields = array($term->term_id, $term->name, $term->slug);
  if ((bool) $args['include']) {
   foreach ($term_chk_fields as $k) {
    if (!in_array($k, (array) $args['include'])) { $exit_loop = true; break; }
   }
  }
  if ((bool) $args['exclude']) {
   foreach ((array) $args['exclude'] as $i) {
    $break_excl = false;
    foreach ($term_chk_fields as $f) {
	 if ($i == $f) { $exit_loop = true; $break_excl = true; break; }
     else { $exit_loop = false; }
	}
	if ($break_excl) break;
   }
  }
  if ($exit_loop) continue;
  $term_title_name_class = array($args['term_name_class'], sprintf($args['term_name_class'].'-%s', $term->slug));
  $f['name'] = $args['term_title'] ? apply_filters('CF_HTML', $args['title_tag'], array('class'=>$term_title_name_class), $term->name) : '';
  if ((bool) $args['term_description']) {
   $f['description'] = apply_filters('CF_HTML', 'div',
    array('class'=>array($args['term_description_class'], $args['term_description_class'].'-'.$term->slug)),
    wpautop($term->description), $args['term_description_no_tag_if_empty']
   );
  }
  
  $posts = '';
  $wp_query = new WP_Query(array(
   'post_type'		 => $args['post_type'],
   'posts_per_page'	 => $args['posts_per_page'],
   'order'			 => $args['post_order'],
   'orderby'		 => $args['post_orderby'],
   'tax_query'		 => array(
    'relation'		 => 'AND',
	 array('taxonomy' => $args['taxonomy'], 'field'=>'id', 'terms'=>array($term->term_id), 'include_children'=>$args['include_children'] ),
   ) )
  );
  
  if (have_posts()) while (have_posts()) {//foreach ($posts as $post) {
   the_post();
   ob_start();
   the_content(); $content = ob_get_contents();
   the_excerpt(); $excerpt = ob_get_clean();
   $post_title_class = array($args['post_title_class']);
   $posts .= apply_filters('CF_HTML', $html5 ? 'article' : 'div', array('class' => get_post_class(), 'id'=>'post-'.$post->ID),
    (
     $args['no_title'] ? '' :
      apply_filters('CF_HTML', $args['post_title_tag'], array('class'=>$post_title_class), the_title(NULL,NULL,FALSE))
    ). 
    apply_filters('CF_HTML', 'div', array('class'=>$args['post_content_class']), 
     ( (bool)$args['post_content'] ? $content : ($args['post_excerpt'] ? $excerpt : '') )
    )
   );
  }
  $f['posts'] .= apply_filters('CF_HTML', 'div', array('class'=>array('taxonomy_term_posts'), 'id'=>sprintf('taxonomy_%s_term_%s_posts',$args['taxonomy'],$term->slug)), $posts, TRUE);
  if ($args['recursive']) {
   $a = $args;
   $a['child_of'] = $term->term_id ;
   unset($a['terms']);
   $a['depth']++;
   ;
   $f['children'] = call_user_func( __FUNCTION__, $a);
  }
if (is_specific_user_logged_in(1)) {
// my_print_r($term);
}
  $formatted = $args['format'];
  foreach (array_keys($f) as $k) {
   $formatted = str_replace(sprintf('%%%s%%', $k), $f[$k], $formatted);
  }
  $term_box_atts = array(
   'class'=>array(
    $args['term_box_class'],
    $args['term_box_class'].'-term_slug-'.$term->slug,
    $args['term_box_class'].'-term_name-'.$term->name,
    $args['term_box_class'].'-term_depth-'.$depth,
	$args['term_box_class'].'-taxonomy_name-'.$args['taxonomy'],
   ), 
   'id'=>$args['term_box_class'].'-term_id_'.$term->term_id,
  );
  $html .= apply_filters('CF_HTML', 'div', $term_box_atts, $formatted, TRUE) ;
 }
 
 $post = $post_orig;
 $wp_query =  $wp_query_orig;
 
 return $html
 ;
}



function wpcf_taxonomy_term_posts_get_term_posts($args) {
 global $wp_custom_functions, $post, $wp_query
 ;
 $args = wpcf_taxonomy_term_posts_arguments($args);

 $html5 = apply_filters('WPCF_HTML5_Capable', NULL);
 $get_terms_args = array('orderby'=>NULL, 'order'=>NULL, 'hide_empty'=>NULL, 'child_of'=>NULL);
 if ( $args['recursive'] ) {
  $get_terms_args['parent'] = $args['child_of'];
  unset($get_terms_args['child_of']);
 }

 $post_orig = $post ;
 $wp_query_orig = $wp_query;

 $terms = get_terms($args['taxonomy'], $wp_custom_functions->parse_args( $get_terms_args, $args) );

 foreach ($terms as $term) {
  if (!term_exists($term->name, $args['taxonomy'])) { continue; }

  $f = array('posts'=>'', 'description'=>'', 'name'=>'', 'children'=>'',);

  $exit_loop = false;
  $term_chk_fields = array($term->term_id, $term->name, $term->slug);
  if ((bool) $args['include']) {
   foreach ($term_chk_fields as $k) {
    if (!in_array($k, (array) $args['include'])) { $exit_loop = true; break; }
   }
  }
  if ((bool) $args['exclude']) {
   foreach ((array) $args['exclude'] as $i) {
    $break_excl = false;
    foreach ($term_chk_fields as $f) {
	 if ($i == $f) { $exit_loop = true; $break_excl = true; break; }
     else { $exit_loop = false; }
	}
	if ($break_excl) break;
   }
  }
  if ($exit_loop) continue;
  $term_title_name_class = array($args['term_name_class'], sprintf($args['term_name_class'].'-%s', $term->slug));
  $f['name'] = apply_filters('CF_HTML', $args['title_tag'], array('class'=>$term_title_name_class), $term->name);
  if ((bool) $args['term_description']) {
   $f['description'] = apply_filters('CF_HTML', 'div',
    array('class'=>array($args['term_description_class'], $args['term_description_class'].'-'.$term->slug)),
    wpautop($term->description), $args['term_description_no_tag_if_empty']
   );
  }
  
  $f['posts'] .= apply_filters('CF_HTML', 'div', 'start', 
   array('class'=>array('taxonomy_term_posts'), 'id'=>sprintf('taxonomy_%s_term_%s_posts',$args['taxonomy'],$term->slug))
  );

  $wp_query = new WP_Query(array(
   'post_type'		 => $args['post_type'],
   'posts_per_page'	 => $args['posts_per_page'],
   'order'			 => $args['post_order'],
   'orderby'		 => $args['post_orderby'],
   'tax_query'		 => array(
    'relation'		 => 'AND',
	 array('taxonomy' => $args['taxonomy'], 'field'=>'id', 'terms'=>array($term->term_id), 'include_children'=>$args['include_children'] ),
   ) )
  );

  if (have_posts()) while (have_posts()) {//foreach ($posts as $post) {
   the_post();
   ob_start();
   the_content(); $content = ob_get_contents();
   the_excerpt(); $excerpt = ob_get_clean();
   $post_title_class = array($args['post_title_class']);
   $f['posts'] .= apply_filters('CF_HTML', $html5 ? 'article' : 'div', array('class' => get_post_class(), 'id'=>'post-'.$post->ID),
    (
     $args['no_title'] ? '' :
      apply_filters('CF_HTML', $args['post_title_li'], array('class'=>$post_title_class), the_title(NULL,NULL,FALSE))
    ). 
    apply_filters('CF_HTML', 'div', array('class'=>$args['post_content_class']), 
     ( (bool)$args['post_content'] ? $content : ($args['post_excerpt'] ? $excerpt : '') )
    )
   );
  }
  $f['posts'] .= apply_filters('CF_HTML', 'div', 'end');
  if ($args['recursive']) {
   $a = $args;
   $a['child_of'] = $term->term_id
   ;
   $f['children'] = ${__FUNCTION__}($a);
  }
  $formatted = $args['format'];
  foreach (array_keys($f) as $k) {
   $formatted = str_replace(sprintf('%%%s%%', $k), $f[$k], $formatted);
  }
 }
 
 $post = $post_orig;
 $wp_query =  $wp_query_orig;
 
 return $formatted;
}


function get_taxonomy_term_description($term, $taxonomy='category') {
 $term = get_term_object($term, $taxonomy);
 if ($term) return $term->description ;
 return NULL ;
}

function get_term_object($term, $taxonomy='category') {
 $term = (array) $term; $term = reset($term);
 $t = NULL ;
 $sup_term = get_term($term, $taxonomy); // $term supposed to be an id
 if (is_wp_error($sup_term) || !$sup_term) { // $term is not an id, $taxonomy does not exist, or else
  if ($t = get_term_by('slug', $term, $taxonomy)) return $t;
  elseif ($t = get_term_by('name', $term, $taxonomy)) return $t;
  return FALSE ;
 }
 else { return $sup_term; }
 return FALSE ;
}


function get_queried_terms($taxonomy=NULL, $return_object=TRUE, $field='slug', $wp_query_object=NULL) {
 $objects = NULL;
 if ( is_wp_query_object($wp_query_object) ) { $wp_query = $wp_query_object; }
 else { global $wp_query; }
 if (!function_exists('_wpcf_ts_set_tax')) {
  function _wpcf_ts_set_tax($o, &$objects, &$fields) {
   if (!isset($objects[$o->taxonomy])) $objects[$o->taxonomy] = array();
   if (is_object($o)) {
    foreach ($objects[$o->taxonomy] as $obj) { if ($obj->term_id == $o->term_id) return; }
	$objects[$o->taxonomy][] = $o ;
   }
   else { return; }
   $f2v = array(
    'id' => 'term_id',
    'name' => 'name',
    'slug' => 'slug',
   );
   foreach( array('id', 'name', 'slug') as $i ) {
    if (!isset($fields[$o->taxonomy])) $fields[$o->taxonomy] = array();
    $fields[$i][$o->taxonomy][] = $o->{$f2v[$i]};
   }
  }
 }

 if ( is_array($wp_query->query_vars) ) {
  // if $wp_query->query_vars['category_name'] is set
  if ( $c = $wp_query->query_vars['category_name'] ) {
   _wpcf_ts_set_tax(get_term_object($c,'category'), $objects, $fields);
  }
  // $wp_query->query_vars[category__in],	 $wp_query->query_vars[category__and],
  // $wp_query->query_vars[tag__in],		 $wp_query->query_vars[tag__and]
  foreach (array('tag', 'category') as $tax) {
   foreach ( array_unique( array_merge( $wp_query->query_vars[$tax.'__in'], $wp_query->query_vars[$tax.'__and'] ) ) as $id ) {
    _wpcf_ts_set_tax(get_term_by('id', $id, $tax), $objects, $fields);
   }
  }
  // $wp_query->query_vars[tag_slug__in], $wp_query->query_vars[tag_slug__and]
  foreach ( array_unique( array_merge( $wp_query->query_vars['tag_slug__in'], $wp_query->query_vars['tag_slug__and'] ) ) as $s ) {
   _wpcf_ts_set_tax(get_term_by('slug', $s, 'tag'), $objects, $fields);
  }
 }
 // $wp_query->tax_query 
 if (!empty( $wp_query->tax_query->queries ) ) {
  foreach ( $wp_query->tax_query->queries as $q ) {
   if (!is_array($q)) continue ;
   if ( !isset($objects[$q['taxonomy']]) ) $objects[$q['taxonomy']] = array();
   foreach( $q['terms'] as $t ) {
    _wpcf_ts_set_tax(get_term_object($t, $q['taxonomy']), $objects, $fields);
   }
  }
 }
 // $wp_query->query
 if ( isset($wp_query->query['term']) && !empty($wp_query->query['term']) && $tax = array_value($wp_query->query, 'taxonomy') ) {
  _wpcf_ts_set_tax(get_term_object($wp_query->query['term'], $tax), $objects, $fields);
 }
 
 // $wp_query->queried_object
 if (
     !empty($wp_query->queried_object)
  && (property_exists($wp_query->queried_object, 'taxonomy') && $wp_query->queried_object->taxonomy)
  && (property_exists($wp_query->queried_object, 'slug') && $wp_query->queried_object->slug)
  ) {
  _wpcf_ts_set_tax(
   get_term_object($wp_query->queried_object->slug, $wp_query->queried_object->taxonomy),
   $objects, $fields
  )
  ;
 }

 $result = $taxonomy ? 
  ( $return_object ? (array) array_value($objects, $taxonomy) : (array) array_value($fields[$field], $taxonomy) )
  :
  ( $return_object ? $objects : $fields[$field] )
 ;
 return  $result
 ;
}


function get_current_taxonomy_term($taxonomy='category'){$terms = get_queried_terms(); return array_value($terms,'taxonomy');}//THIS FUNC IS OBSOLETE.

function get_parent_terms($term, $taxonomy='category') {
 $term = get_term_object($term, $taxonomy);
 $parents = array();
 if ($term) {
  $parents[] = $term;
  while ($term->parent) {
   $term = get_term_by('id', $term->parent, $taxonomy);
   $parents[] = $term;
  }
 }
 return $parents ;
}


function get_term_direct_children( $term, $taxonomy ) { // THIS FUNC IS UNDER DEVELOPMENT.
 if ( !taxonomy_exists($taxonomy) )
  return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));
 $term = get_term_object($term, $taxonomy );
 $terms = _get_term_hierarchy($taxonomy);
 if ( !isset($terms[$term->term_id]) ) return array();
 $children = $terms[$term->term_id]; 
 foreach ( (array) $children as $child ) {
  if ( isset($terms[$child]) ) {
   $children = array_merge($children, get_terms( $taxonomy, 'parent='.$child ));
  }
 }
 return $children;
}

/* // use a builtin function get_post_taxonomies instead. it returns names of attached taxonomies
function wpcf_get_post_taxonomies($post=NULL, $tax_args=NULL, $object=TRUE) {
 $post = get_post($post); if (empty($post)) return ;
 $taxonomies = get_taxonomies($tax_args,'object');
 $object_taxes = array();
 foreach ($taxonomies as $tax) {
  $_tax_found = FALSE;
  foreach ($tax->object_type as $ot) {
   if ($post->post_type == $ot) $object_taxes[] = $tax; break;
  }
 }
 return $object_taxes;
}
*/

function wpcf_get_post_terms($post=NULL, $args=NULL) {
 $post = get_post($post); if (empty($post)) return ;
 $taxonomies = get_post_taxonomies($post);
 $terms = array();
 foreach ($taxonomies as $tax) {
  $terms = array_merge($terms, wp_get_post_terms($post->ID, $tax));
 }
 return $terms
 ;
}



function get_current_category($index = 0) {
 $cat = NULL;
 if( is_category() ) {
  return get_category(get_query_var('cat'));
 }
 else if (is_single() ) {
  $cats = get_the_category();
  for ($i = 0; $i < count($cats); $i++) if ($index == $i) return $cats[$i];
 }
 return NULL;
}

function get_current_categories() {
 if(is_category()) return $cats = get_category(get_query_var('cat'));
 else if (is_single()) return get_the_category();
 return NULL;
}

function cat_is_child_of($a, $cats=NULL) {
 if (!$cats) $cats = get_current_categories();
 if (is_object($a)) $a = $a->cat_ID;
 elseif (is_array($a)) $a = $a[0];
 $a = preg_replace('/[^\d]/', '', $a);
 if (!is_array($cats)) {
  if (cat_is_ancestor_of($a, $cats)) return 1;
  else return;
 } 
 for ($i = 0; $i < count($cats); $i++) if (cat_is_ancestor_of($a,$cats[$i])) return 1;
 return NULL;
}

function cat_is_under_category($c, $p = NULL) {
 global $post;
 !$p && $p = $post;
 !$c && $c = 1;
 $cat = get_current_category($p);
 return post_is_in_descendant_category($c, $p) || $cat->cat_ID == $c;
}

/* post_is_in_descendant_category
 * Tests if any of a post's assigned categories are descendants of target categories
 *
 * @param int|array $cats The target categories. Integer ID or array of integer IDs
 * @param int|object $_post The post. Omit to test the current post in the Loop or main query
 * @return bool True if at least 1 of the post's categories is a descendant of any of the target categories
 * @see get_term_by() You can get a category by name or slug, then pass ID to this function
 * @uses get_term_children() Passes $cats
 * @uses in_category() Passes $_post (can be empty)
 * @version 2.7
 * @link http://codex.wordpress.org/Function_Reference/in_category#Testing_if_a_post_is_in_a_descendant_category
 */
function post_is_in_descendant_category( $cats, $_post = NULL ) {
	foreach ( (array) $cats as $cat ) {
		// get_term_children() accepts integer ID only
		$descendants = get_term_children( (int) $cat, 'category');
		if ( $descendants && in_category( $descendants, $_post ) ) return true;
	}
	return false;
}


function is_specific_category($c = NULL, $post = NULL) {
 if (!$c) return false;
 if (!$post) global $post;
 if (is_category($c) || in_category($c,$post) || post_is_in_descendant_category($c,$post)) return true;
 return false;
}


function is_specific_taxonomy_queried($taxonomy, $boolean=FALSE) {
 global $wp_query, $query_string
 ;
 
 if (isset($wp_query->tax_query) && isset($wp_query->tax_query->queries)) {
  foreach ($wp_query->tax_query->queries as $q) {
   if ($q['taxonomy'] == $taxonomy) return $boolean ? TRUE : $taxonomy
   ;
  }
 }
 return FALSE
 ;
}

function is_specific_taxonomy_term($term, $taxonomy='category', $post=NULL, $ignore_descendant_terms=FALSE, $wp_query_object=NULL) {
 if ($post === NULL && is_archive()) {
  return is_specific_taxonomy_term_archive($term, $taxonomy, $ignore_descendant_terms, $wp_query_object=NULL);
 }
 if ( $post = get_post($post) ) {
  if (!$ignore_descendant_terms) return post_is_in_descendant_taxonomy_term($term,$taxonomy,$post);
  return has_term($term, $taxonomy, $post) ? $term : FALSE ;
 }
 return FALSE
 ;
}


function is_specific_taxonomy_term_archive($term, $taxonomy='category', $ignore_descendant_terms=FALSE, $wp_query_object=NULL) {
 $term = get_term_object($term, $taxonomy);
 if (!$term) return FALSE ;
 
 $terms = ( is_taxonomy_hierarchical($taxonomy) && !$ignore_descendant_terms ) ? 
  array_merge( (array) $term->term_id, get_term_children($term->term_id, $taxonomy) ) // <= id list
  :
  $terms = (array) $term->term_id
 ;
 $queried_terms = get_queried_terms($taxonomy, TRUE, NULL, $wp_query_object);

 foreach ($queried_terms as $t) {
  if ( in_array($t->term_id, $terms)) return $t ;
 }
  return FALSE ;
}


function post_is_in_descendant_taxonomy_term($terms, $taxonomy, $post=NULL) {
 $post = get_post($post);
 foreach ((array) $terms as $t) {
  $t = get_term_object($t,$taxonomy);
  if (!$t || is_wp_error($t)) continue;

  $ts = array($t); 
  if (is_taxonomy_hierarchical($taxonomy)) {
   $children = get_terms($taxonomy, array('child_of'=>$t->term_id));
   $ts = array_merge($ts, $children);
  }

  foreach ($ts as $tt) {
   if (has_term($tt->term_id, $taxonomy, $post)) return $tt;
  }
 }
 return false;
}


function term_is_child_of($parent, $term, $taxonomy='category') {
 if (is_string($taxonomy) && $taxonomy = get_taxonomy($taxonomy) && !$taxonomy) return NULL ;

 $parent = get_term_object($parent, $taxonomy->name);
 $term = get_term_object($term, $taxonomy->name);
 $terms = array();
 
 if (!is_object($parent) || !is_object($term)) {
  return NULL ;
 }
 else {
  $terms = get_term_children($parent->term_id, $taxonomy->name);
  if (is_wp_error($terms)) return NULL ;
 }
 return in_array($term->term_id, $terms);
}


function get_term_parents($id, $taxonomy='category', $link=FALSE, $separator='/', $nicename=FALSE, $visited=array()) {
 $chain = '';
 $parent = &get_term($id, $taxonomy, OBJECT, 'raw');
 if (is_wp_error($parent)) return $parent;
 if ($nicename) $name = $parent->slug;
 else $name = $parent->name;

 if ($parent->parent && ($parent->parent != $parent->term_id) && !in_array($parent->parent, $visited)) {
  $visited[] = $parent->parent;
  $chain .= get_term_parents($parent->parent, $taxonomy, $link, $separator, $nicename, $visited);
 }

 if ($link) {
  $chain .= '<a href="' . get_term_link($parent->term_id, $taxonomy) . '" title="' . esc_attr(sprintf(__("View all posts in %s"), $parent->name)) . '">'.$name.'</a>' . $separator;
 }
 else $chain .= $name.$separator;
 return $chain;
}


function is_taxonomy_term_queried_object($term, $taxonomy) {
 $object =  get_queried_object();
 if (is_object($object) && property_exists($object, 'term_id')) {
  $term = get_term_object($term, $taxonomy);
  if ($term) return $term->term_id == $object->term_id ;
 }
 return FALSE ;
}

function has_specific_taxonomy_term($taxonomy, $terms=0, $post=NULL) {
 $post = get_post( $post );
 if (is_taxonomy_hierarchical($taxonomy)) {
  foreach ((array) $terms as $t) {
   if ($t === 0 || $t === '0') $descendants = get_terms($taxonomy);
   else $descendants = get_term_children((int) $t, $taxonomy);
   if (empty($descendants)) return false;
   else if (has_term($descendants, $taxonomy, $post)) return true;
  }
 }
 else {
  foreach ((array) $terms as $t) {
   if (has_term($t, $taxonomy, $post)) return true;
  }
  return false;
 }
}

