<?php 
class BreadCrumb extends ClassTemplate {
function __construct($args=null) {
 global $wp_custom_functions, $custom_language_domain;
 $this->param( $wp_custom_functions->parse_args(array(
  'title_blog'				 => get_bloginfo('title'),
  'separator'				 => '&gt;',
  'tag_page_prefix'			 => '',
  'archive_category_prefix'	 => '',
  'archive_date_prefix'		 => '',
  'singleblogpost_prefix'	 => '',
  'tag_page_prefix'			 => '',
  'id'						 => 'navi_breadcrumb',
  'display_archive_title'	 => TRUE,
  'display_taxonomy_archive_title'	 => TRUE,
  'display_builtin_archive_title'	 => FALSE,
  'display_custom_post_type_archive_title'	 => TRUE,
  'archive_title'			 => __('Archive', $custom_language_domain),
  '404_title'				 => __('Not Found', $custom_language_domain),
  'search_title'			 => __('Search Results', $custom_language_domain),
  'current_class'			 => 'current_page',
  'show_frontpage'			 => TRUE,
  'show_paged_first'		 => false,
  'page_title'				 => __('Page %s', $custom_language_domain),
  'taxonomy'				 => null,
  'taxonomy_orderby'		 => 'term_order'
 ), $args) );
 $this->is_top = is_home() || is_front_page();
 $this->is_termsearch = get_query_var('is_termsearch');
}

function display($o = array()) { echo $this->bc($o); }

function bc ($args = null) {
/* // TODO
- to add path of taxonomy term specifically or arbitrarily
- to parse referrer for the purpose of adding the path

// */
  
 global $wp_custom_functions, $wp_query
 ;
 $args = $wp_custom_functions->parse_args(array_merge( $this->param(), array('display'=>0) ), $args);
 $sep = createHTMLElement('span', array('class'=>$args['id'].'_separator'), $args['separator']);
 $sep_re = implode('', array_map(
  function($s) { return '\x'.dechex(ord($s)); },
  str_split($sep)
 ) );
 $d = createHTMLElement( (is_html5_capable()?'nav':'div'), 'start', array('id'=>$args['id'], 'class'=>'nav') );
 $d .= createHTMLElement('ul', 'start', array('class'=>'clearfix'));

 /* ROOT */
 $d .= createHTMLElement('li', null,
  createHTMLElement('a', array('href'=>get_bloginfo('url')), $this->param('title_blog')) . 
  (!$this->is_top || ($args['show_frontpage'] && get_option('show_on_front') == 'page') ?
   ($this->is_top ? $sep . apply_filters('the_title', get_post(get_option('page_on_front'))->post_title) : '')
   : '')
 );

 if (!$this->is_top) :
  $obj = get_queried_object();
  $archive_link = '';

  if (is_search() && $args['display_archive_title']) {
   $d .= $sep . createHTMLElement('li', null, $args['search_title']);
  }
  elseif (is_404() && $args['display_archive_title']) {
   $d .= $sep . createHTMLElement('li', null, $args['404_title']);
  }
  elseif (is_singular() || is_single()) { // single post, page, attachment or custom-post-type-post
   $post_type = get_post_type_object($obj->post_type);
   if ($args['display_builtin_archive_title'] || (is_custom_post_type($post_type->name) && $args['display_archive_title'])) {
    $d .= createHTMLElement('li', null,
     $sep . createHTMLElement('a', array('href'=>get_post_type_archive_link($post_type->name)), $post_type->label)
    );
   }
   if ($post_type->hierarchical) { // page, attachment or custom-post-type-post (hierarchical)
    $ancestors = array_reverse(get_post_ancestors($obj->ID));
    $ancestors[] = $obj->ID;
    foreach ($ancestors as $a) {
     $is_current_post = ($a == end($ancestors));
     $d .= createHTMLElement('li', null, $sep .
      createHTMLElement('a',
       array('href'=> get_permalink($a), 'class'=>($is_current_post? $args['current_class'] : '')),
       get_the_title($a)
      )
     );
    }
   }
   else { // single post or custom-post-type-post (non-hierarchical)
    $d .= createHTMLElement('li', null,
     $sep . createHTMLElement('a', array('href'=>get_permalink($obj->ID)), $obj->post_title)
    );
   }
  }
  elseif ($this->is_termsearch) {
   $d .= createHTMLElement('li', null, $sep . wp_title(null,null,false));
  }
  elseif (is_archive()) { // archives
   if (is_tax() || is_category() || is_tag()) { // taxonomy-term archives (incl. category, tag)
    $tax = $obj->taxonomy;
    $term = $obj;
/*	if (is_category() || is_tag()) {
	 $terms[] = $obj;
	} */
	if ($this->param('display_taxonomy_archive_title')) {
     $d .= createHTMLElement('li', null, $sep . get_taxonomy($obj->taxonomy)->label);
	}
    $terms = array($term);
    while ($term->parent) {
     $term = get_term_by('id', $term->parent, $tax);
	 $terms[] = $term;
    }
    foreach (array_reverse($terms) as $t) {
     $d .= createHTMLElement('li', null,
      $sep . createHTMLElement('a', array('href'=>get_term_link($t)), $t->name)
     );
    }
   }
   elseif (is_author()) {
    if ($args['display_object_title']) {
     $d .= createHTMLElement('li', null, $sep . __('Author', $custom_language_domain));
	}
	$d .= createHTMLElement('li', null, $sep . get_the_author_link());
   }
   elseif (is_date()) {
	$date = $wp_query->query;
	$path = array();
	if ( is_day() || is_month() || is_year() )	 { $path[] = get_year_link($date['year']); }
	if ( is_day() || is_month() )				 { $path[] = get_month_link($date['monthnum']); }
	if ( is_day() )								 { $path[] = get_day_link($date['day']); }
	foreach ($path as $p) { $d .= $sep . createHTMLElement('li', null, $p); }
   }
   else { // supposed to be custom-post-type-archives
	$d .= createHTMLElement('li', null,
     $sep . createHTMLElement('a', array('href'=>get_post_type_archive_link($obj->name)), $obj->label)
	);
   }
   if (property_exists($wp_query, 'query_vars') && $wp_query->query_vars['paged'] && $this->param('show_paged_first')) {
	$d .= createHTMLElement('li', null, $sep . sprintf($this->param('page_title'), $wp_query->query_vars['paged']) );
   }
  } // END IF ARCHIVE
 endif ; // $this->is_top
 $d .= createHTMLElement('ul', 'end');
 $d .= createHTMLElement((is_html5_capable()?'nav':'div'), 'end'); 
 if ($args['display']) echo $d;
 else return $d;
}

private $is_termsearch = false;
private $accepted_param_keys = array(
  'title_blog',
  'separator',
  'tag_page_prefix',
  'archive_category_prefix',
  'archive_date_prefix',
  'singleblogpost_prefix',
  'tag_page_prefix',
  'id',
  'display_archive_title',
  'display_builtin_archive_title',
  'display_custom_post_type_archive_title',
  'archive_title',
  '404_title',
  'search_title',
  'current_class',
  'show_frontpage',
  'taxonomy',
  'taxonomy_orderby',
);

} // END OF CLASS: BreadCrumb


























////////////////////////////// EOF ////////////////////////////////////