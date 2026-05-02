<?php 
class TermSearch extends ClassTemplate {

public $rules = array();
public $is_termsearch = FALSE ;
private $tax_query = array();
private $query = array();
protected $param = array();
private $taxonomy = '';
private $terms = array();
private $term_search_root = 'term-search';
private $term_search_query_key = 'term_search';
private $tax_search_root_base = 'taxonomy-';
private $tax_search_query_key_base = 'taxonomy_';
private $refine_query = FALSE;
private $user_error = '';
public $wp_query_original = NULL;

/* // Usage Of TermSearch

// Setup
new TermSearch();

// Search Form
echo do_shortcode('[term_search_form terms=some_taxonomy(term_name_1+term_name_2)+some_other_taxonomy(some_other_term_1+some_other_term_2)]')

// Search Top
do_shortcode('[term_search_top anchor_text="Search Top"]')

// */

public function __construct($args = null) {
 global $wp_query;
 $this->param( apply_filters('WPCF_Arguments', parse_args(array(
  'post_type'			 => 'any',
  'posts_per_page'		 => get_option('posts_per_page'),
  'show_taxonomy_name'	 => ':',
  'term_separator'		 => ' / ',
  'taxonomy_separator'	 => ', ',
  'title_term_field'	 => 'name',
  'field'				 => 'slug',
  'search_top_uri'		 => (array_value($_GET, 'termsearch') && $u = array_value($_GET, 'search_top_uri'))? $u : $_SERVER['FULL_URI'],
  'relation'			 => 'AND',
 ), (array) $args) ) );
 $this->taxonomies = $this->taxonomies($this->param());
 if ($this->wp_query_original === NULL) $this->wp_query_original = $wp_query;
 

 add_filter( 'rewrite_rules_array', array(&$this, 'add_rules') );
 add_action( 'wp', array(&$this, 'query_posts') );
 add_filter( 'wp_title', array(&$this, 'termsearch_title') );
 add_shortcode( 'term_search_form', array(&$this, 'sc_term_search_form') );
 add_shortcode( 'term_search_top', array(&$this, 'sc_search_top') );
 add_action( 'WPCF_Initialize', 'flushRules' );
 add_filter( WPCF_PREFIX.'Is_Taxonomy_Term_Originally_Queried', array(&$this, 'is_taxonomy_term_originally_queried'), 10, 2 );
 add_filter( WPCF_PREFIX.'TermSearch_Original_WP_Query', array(&$this, 'get_original_wp_query') );
 enable_query_vars( $this->term_search_query_key );
 foreach ($this->taxonomies as $tax) enable_query_vars('taxonomy_'.$tax);
 return $this;
}


private function taxonomies($args = null) {
 $args = apply_filters('WPCF_Arguments',  parse_args(array(
  'public' => true,
  // name => null,
  // object_type => null,
  // label => null,
  // singular_label => null,
  // show_ui => null,
  // show_tagcloud => null,
  // public => null,
  // update_count_callback => null,
  // rewrite => null,
  // query_var => null,
  // manage_cap => null,
  // edit_cap => null,
  // delete_cap => null,
  // assign_cap => null,
  // _builtin => true,
 ), $args) ); 
 $taxes = get_taxonomies($args);
 unset($taxes['']);
 return array_values($taxes);
}


function add_rules($rules){
 $this->rules['.*?/?'.$this->term_search_root.'/([^/]*?)(?:/page/(\d+))?/?$'] = 'index.php?'.$this->term_search_query_key.'=$matches[1]&paged=$matches[2]';

 foreach ($this->taxonomies() as $tax) {
  if (empty($tax)) continue;
  $this->rules['.*?/?'.$this->tax_search_root_base.$tax.'/([^/]+?)/?(?:page/(\d+))?/?$']
   = 'index.php?'.$this->tax_search_query_key_base.$tax.'=$matches[1]&paged=$matches[2]';
 }
 return $rules = $this->rules + $rules;
}

public function is_termsearch($bool=NULL) {
 return  $bool === NULL ? $this->is_termsearch : $this->is_termsearch = (bool) $bool;
}


public function is_taxonomy_term_originally_queried($term, $taxonomy) {
 $t = get_term_object($term, $taxonomy);
 if (!$t) return FALSE;
 foreach ( get_queried_terms($taxonomy, TRUE, 'slug', $this->get_original_wp_query()) as $term ) {
  if ( $term->term_id == $t->term_id) return TRUE
  ;
 }
 return FALSE ;
}

public function get_original_wp_query() {
 return $this->wp_query_original;
}

public function sc_term_search_form($args) {
 global $custom_language_domain;
 $args = apply_filters('WPCF_Arguments', parse_args(array(
  'terms'			 => '',
  'wrapper_class'	 => 'term_search_box',
  'noselection_label'=> __( 'Please select a value.', $custom_language_domain ),
  'submit'			 => __('Search', $custom_language_domain),
  'field'			 => 'slug',
  'size'			 => NULL,
  'title'			 => __('Refine Search', $custom_language_domain),
  'title_li'		 => 'h3',
  'type'			 => 'select', // RADIO | CHECKBOX
 ), $args) );
 
 $html = '';
 if ($args['terms'] && preg_match_all('/(?:([^\x28\x29]+?)\x28(.*?)\x29)\+?+/', $args['terms'], $terms_matches)) {
  $terms = array();

  if ($terms_matches) {
   foreach ($terms_matches[1] as $i=>$tm) {
    if (!isset($terms[$tm])) $terms[$tm] = array();
	$terms[$tm][] = explode('+', $terms_matches[2][$i]);
   }

  }
  $dropdown = $args['size'] === NULL ;
  foreach($terms as $tax=>$slug_groups) {
   foreach($slug_groups as $slugs) {
    $default = NULL;
	$taxes = array_value($this->queried_terms(), $tax);
	if (empty($taxes)) $taxes = array();
    foreach ($taxes as $qt) {
	 if (in_array($qt->{$this->param('field')}, $slugs)) {
      $default = $qt->slug; break;
	 }
    }
    $option_args = array(
     'name'  => $tax.'[]',
     'values' => $dropdown ? array('0') : array(),
     'labels' => $dropdown ? array($args['noselection_label']) : array(),
     'value' => $default,
     'size' => $args['size'],
    );
    foreach ($slugs as $s) {
	 if ($term = get_term_by($this->param('field'), $s, $tax)) {
      $option_args['values'][] = $term->slug ;
      $option_args['labels'][] = $term->name ;
	 }
    }
	if ('select' == $args['type']) {
     $html .= html_select_element($option_args);
	}
	else if ('radio' == $args['type'] || 'checkbox' == $args['type']) {
	 $html .= apply_filters('CF_HTML', 'input', array('type'=>$args['type']));
	}
   }
  }
  $html = createHTMLElement('form',
// array('action'=>'/'.$this->term_search_root.'/', 'method'=>'GET'),
   array('method'=>'GET'),
     $html
   . createHTMLElement('input', array('type'=>'submit', 'value'=>$args['submit'], 'class'=>'termsearch_submit'))
   . createHTMLElement('input', array('type'=>'hidden', 'name'=>'termsearch', 'value'=>1))
   . createHTMLElement('input', array('type'=>'hidden', 'name'=>'search_top_uri', 'value'=>$this->param('search_top_uri')) )
  ); 
 }
 return $html;
}


public function sc_search_top($args) {
 global $wp_custom_functions, $custom_language_domain;
 $args = apply_filters('WPCF_Arguments',  $wp_custom_functions->parse_args(array(
  'link' => TRUE,
  'anchor_text' => __('Return to search top.', $custom_language_domain),
  'atts' => array(),
  'href' => $this->param('search_top_uri'),
 ), $args) );
 $args['atts'] = array_merge($args['atts'], array('href'=>$args['href']));
 if ($args['link']) return createHTMLElement('a', $args['atts'], $args['anchor_text']);
 return $this->param('search_top_uri');
}


function parse_query($args = null) {
 global $wp_query;
 $query = array();
 $queries = array();
 $this->tax_query = array();
 if ($qv = get_query_var($this->term_search_query_key)) $queries = explode(',', $qv);
 if (array_value($_GET, 'termsearch')) { // QUERIED FROM FORM
  $this->refine_query = TRUE;
  foreach ($this->taxonomies() as $t) {
   if ($a = array_value($_GET, $t)) {
    $aa = array();
	foreach ($a as $i) { $it = get_term_by($this->param('field'), $i, $t); if (empty($it)) continue; $aa[] = $i; }
    $q = implode('+', $aa ); // THIS IS REDUNDANT. (CONCATATING EXISTING ARRAY. BUT IS GOING TO BE SPLITTED AGAIN.)
	$q = 'taxonomy-' . $t . '-' . $q;
	$queries[] = $q;
   }
  } 
 }

 if (!empty($queries)) {
  $this->is_termsearch(TRUE);
  foreach ($queries as $q) {
   $q = preg_replace('/^'.$this->tax_search_root_base.'/', '', $q);
   foreach ($this->taxonomies() as $t) {
    preg_match( '/('.$t.')-(.+?)$/', $q, $m ); 
    if ($m) {
	 $mm = explode('+', $m[2]);
	 if ( $i = array_value( $query, $m[1] ) && is_array($i) ) {
      $query[$m[1]] = array_merge( $i, $mm );
	 }
	 else { $query[$m[1]] = $mm; }
    }
   }
  }

  foreach ($query as $tax=>$terms) {
   foreach ($terms as $t)
    $this->tax_query[] = array('taxonomy'=>$tax, 'field'=>$this->param('field'), 'terms'=> $t);
  }
 }
 else {
  foreach ($this->taxonomies() as $t) {
   if ($qv = get_query_var('taxonomy_'.$t)) { $this->terms[$t] = explode('+', $qv);
    if (!empty($this->terms[$t]) && !$this->taxonomy) {
     $this->taxonomy = $t; break;
    }
   }
  }
  if (!empty($this->taxonomy)) {
   foreach ($this->terms[$this->taxonomy] as $t) {
    if (!empty($t)) {
     $this->is_termsearch = true;
     $this->tax_query[] = array('taxonomy'=>$this->taxonomy, 'field'=>$this->param('field'), 'terms'=>$t);
    }
   }
  }
 }
 set_query_var('is_termsearch', $this->is_termsearch);
 $this->tax_query['relation'] = $this->param("relation") ;
 $args = array_merge(
  $this->query_args($args), 
  array(
   'tax_query' => $this->tax_query,
   'paged'=>get_query_var('paged')
  )
 );
 return $args;
}


function query_posts($args = NULL) {
 global $wp_custom_functions, $wp_query;
 $termsearch_args = $wp_custom_functions->parse_args($this->parse_query(), $args);
 $query_vars = $wp_query->query_vars;
 foreach ($termsearch_args as $k => $v) {
  if (!empty($v)) {
   if ($a = array_value($query_vars, $k) && is_array($a)) { 
    $query_vars[$k] = array_merge($query_vars[$k], $termsearch_args[$k]);
   }
   else { $query_vars[$k] = $termsearch_args[$k]; }
  }
 }

 if ($this->is_termsearch) query_posts($query_vars);
}


function queried_terms($tax=null, $field='slug') {
 if (empty($this->tax_query)) $this->parse_query();
 $taxes = array();
 foreach ($this->tax_query as $q) {
  if (is_array($q) && !empty($q['taxonomy'])) {
   $taxes[$q['taxonomy']][] = get_term_by($q['field'], $q['terms'], $q['taxonomy']);
  }
 }
 return $taxes;
}


function termsearch_title($title) {
 if ($this->is_termsearch) {
  $title = '';
  foreach ($this->queried_terms() as $tax=>$terms) {
   if ((bool) $this->param('show_taxonomy_name')) $title .=  get_taxonomy($tax)->label . $this->param('show_taxonomy_name');
   $title .= implode($this->param('term_separator'), array_map(function($t){ return $t->name; }, $terms));
   $title .= $this->param('taxonomy_separator');
  }
  $title = preg_replace('/'.preg_quote($this->param('taxonomy_separator')).'$/', '', $title);
 }
 return $title;
}


function query_args($args) {
 $default_args = array(
  'post_type'		 => 'post',
  'author'			 => null,
  'author_name'		 => null,
  'cat'				 => 0,		 // use category id.
  'category_name'	 => null,	 // use category slug (NOT name).
  'category__and'	 => null,	 // use category id.
  'category__in'	 => null,	 // use category id.
  'category__not_in' => null,	 // use category id.
  'tag'				 => null,	 // use tag slug.
  'tag_id'			 => null,	 // use tag id.
  'tag__and'		 => null,	 // use tag ids.
  'tag__in'			 => null,	 // use tag ids.
  'tag__not_in'		 => null,	 // use tag ids.
  'tag_slug__and'	 => null,	 // use tag slugs.
  'tag_slug__in'	 => null,	 // use tag slugs.
  'p'				 => 0,		 // use post id.
  'name'			 => null,	 // use post slug.
  'page_id'			 => 0,		 // use page id.
  'pagename'		 => null,	 // use page slug.
  'post_parent'		 => 0,		 // use page id. Return just the child Pages.
  'post__in'		 => null,	 // use post ids. Specify posts to retrieve.
  'post__not_in'	 => null,	 // use post ids. Specify post NOT to retrieve.
  'post_status'		 => 'publish',
  'posts_per_page'	 => 10,
  'posts_per_archive_page' => null,	 // number of posts to show per page - on archive pages only. Over-rides showposts and posts_per_page on pages where is_archive() or is_search() would be true
//  'nopaging'		 => false, // show all posts or use pagination. 
  'paged'			 => 1,
  'offset'			 => 0,
  'order'			 => 'ASC',
  'orderby'			 => 'date',
  'year'			 => null,	 // 4 digit year (e.g. 2011).
  'monthnum'		 => null,	 // Month number (from 1 to 12).
  'w'				 => null,	 // Week of the year (from 0 to 53). Uses the MySQL WEEK command. The mode is dependent on the "start_of_week" option.
  'day'				 => null,	 // Day of the month (from 1 to 31).
  'hour'			 => null,	 // Hour (from 0 to 23).
  'minute'			 => null,	 // Minute (from 0 to 60).
  'second'			 => null,	 // Second (0 to 60).
  'meta_key'		 => null,	 // Custom field key.
  'meta_value'		 => null,	 // Custom field value.
  'meta_value_num'	 => null,	 // Custom field value.
  'meta_compare'	 => '=',	 // Operator to test the 'meta_value'.
								 // Possible values are '!=', '>', '>=', '<', or '<='.
  'meta_query'		 => null,	
	 /*
	 // key (string) - Custom field key.
	 // value (string|array) - Custom field value (Note: Array support is limited to 
	 // a compare value of 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN')
	 // compare (string) - Operator to test. Possible values are
	 // '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
	 // 'BETWEEN', 'NOT BETWEEN', 'NOT EXISTS' (in WP 3.5). Default value is '='.
	 // type (string) - Custom field type. Possible values are 'NUMERIC', 'BINARY',
	 // 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'.
	 // Default value is 'CHAR'.
	 */
 );
 global $wp_custom_functions ;
 return $wp_custom_functions->parse_args($wp_custom_functions->parse_args($default_args, $this->param()), (array) $args);
}


} // end of class.TermSearch
