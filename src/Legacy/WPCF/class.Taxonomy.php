<?php
class Taxonomy {
var $atts = array();
var $rules = array();
var $name = '';
var $default_term_option_name_format = '%s_default_%s';

function __construct($atts = null) {
 global $wp_custom_functions ;
 if (!isset($atts['name']) || empty($atts['name'])) return ;

 $this->atts = array_merge(
  (array) $atts,
  $wp_custom_functions->parse_args( array(
   'name'					 => null,
   'post_type'				 => 'post',
   'title_sep'				 => ' &raquo; ',
   'sep_location'			 => 'right',
   'show_hierarchy_in_title' => false,
   'term_format'			 => '%s',
   'tax_format'				 => '%s',
   'post_type_name_format'	 => '%s',
   'exclusive_selection'	 => FALSE,
   'add_expander_button'	 => FALSE,
   'add_taxonomy_term_search_box'=> FALSE,
   'hide_pop-tab'			 => FALSE,
   'hide_adder'				 => FALSE,
   'manage_column_args'		 => array('', ',', ''), // $before, $sep, $after in get_the_term_list()
   'limit_admin_box_height'	 => TRUE,
   'limit_admin_box_height'	 => TRUE,
  ),
  (array) $atts )
 );
 return $this->setup();
}



function setup($args = null) {
 $this->atts['taxonomy_args'] = $this->_taxonomy_args($args);
 $name = $this->name = $this->name();
 register_taxonomy($name, $this->atts['post_type'], array_merge($this->atts['taxonomy_args'],array('meta_box_cb'=>NULL)));
 if ( isset($this->atts['manage_columns']) && (bool) $this->atts['manage_columns'] ) {
  $this->atts['manage_columns'] = array($name=>$args['label']);
  add_action('manage_posts_custom_column', array(&$this, '_taxonomy_column'), 10, 2);
  add_filter('manage_edit-'.$this->atts['post_type'].'_columns', array(&$this,'_edit_columns'));
 }
 add_filter('generate_rewrite_rules', array($this, 'add_rewrite_rules'));
 add_filter('term_link', array(&$this, 'set_term_link'), 10, 3);
 add_filter('wp_title', array(&$this, 'post_type_taxonomy_term_archive_title'));
 add_filter('body_class', array(&$this, '_filter_body_class'));
 add_filter('WPCF_Add_Admin_Taxonomy_Expander_Button', array(&$this, 'add_expander_button'));
 add_filter('WPCF_Add_Admin_Taxonomy_Term_Search_Box', array(&$this, 'add_expander_button'));
// $this->setup_rewrite_rules();
 $this->set_exclusive_selection();
 $this->set_admin_box_style();
 return $this;
}


function set_exclusive_selection() {
 if ($this->atts['exclusive_selection']) {
  do_action('WPCF_Set_Admin_JQuery_Code','
$("#'.$this->name.'checklist input").each(function(){$(this).attr("type","radio")})
');
 }
}

function add_expander_button($tax_list) {
 if (!is_array($tax_list)) {
  $tax_list = (array) $tax_list;
 }
 if ($this->atts['add_expander_button']) {
  $tax_list[] = $this->name ;
 }
 return $tax_list;
}

function add_taxonomy_term_search_box($tax_list) {
 if ($this->atts['add_taxonomy_term_search_box']) {
  $tax_list[] = $this->name ;
 }
 return $tax_list;
}

function set_admin_box_style() {
 $admin_box_style = '';
 if ($this->atts['hide_pop-tab']) {
  if ($this->atts['add_expander_button']) {
   $admin_box_style .= "\n"
    . '#%WPCF_Taxonomy_Name%-tabs .tabs, #%WPCF_Taxonomy_Name%-tabs .hide-if-no-js { display: none; }'
   ;
  }
  else {
   $admin_box_style .= "\n".'#%WPCF_Taxonomy_Name%-tabs { display: none; }'
   ;
  }
 }
 
 
 if ($this->atts['hide_adder']) {
  $admin_box_style .= "\n".'#%WPCF_Taxonomy_Name%-adder { display: none; }'
  ;
 }
 
 
 if ($this->atts['limit_admin_box_height']) {
 }
 else {
  $admin_box_style .= "\n"
   . '#taxonomy-%WPCF_Taxonomy_Name%.categorydiv div.tabs-panel { max-height: 100% }'
  ; 
 }
 do_action('WPCF_Set_Admin_CSS_Code', str_replace('%WPCF_Taxonomy_Name%', $this->name, $admin_box_style) );
}

function _taxonomy_column($column_name, $post_id ) {
 if ($this->name() == $column_name) {
  
  $taxes = get_the_term_list(
   $post_id, 
   $this->name(), 
   $this->atts['manage_column_args'][0], 
   $this->atts['manage_column_args'][1], 
   $this->atts['manage_column_args'][2]
  );
  if ($taxes) { echo $taxes; }
  else echo __('None');
 }
}



function _edit_columns($columns) { 
 foreach($this->atts['manage_columns'] as $k => $v) $columns[$k] = $v;
 return $columns;
}




function _taxonomy_args($args = array()) {
 global $custom_language_domain, $wp_custom_functions;
 $domain = $custom_language_domain;
 $args = array_merge($this->atts, (array)$args);
 $h = isset($args['hierarchical']) ? $args['hierarchical'] : false;
 $general_name = $h ? __('Categories') : __('Tags');
 $general_name_singular = $h ? __('Category') : __('Tag');
 $default_args = $wp_custom_functions->parse_args(array(
  'name' => null,
  'label' => isset($args['name']) ? $args['name'] : NULL, // A plural descriptive name for the taxonomy marked for translation.
 ), $args);
 $label = $default_args['label'];
 $labels = $wp_custom_functions->parse_args(array(
  'name' => $label, // Fancy Name.
   // general name for the taxonomy, usually plural.
   // The same as and overridden by $tax->label. 
  'singular_name'	 => $label,
  'search_items'	 => sprintf(__('Search %s', $domain), $label),
  'popular_items'	 => sprintf(__('Popular %s', $domain), $label),
  'all_items'		 => sprintf(__('All %s', $domain), $label),
  'parent_item'		 =>  sprintf(__('Parent %s', $domain), $label),
  'parent_item_colon' => sprintf(__('Parent %s:', $domain), $label),
  'edit_item'		 => sprintf(__('Edit %s', $domain), $label),
  'view_item'		 => sprintf(__('View %s', $domain), $label),
  'update_item'		 => sprintf(__('Update %s', $domain), $label),
  'add_new_item'	 => sprintf(__('Add New %s', $domain), $label),
  'new_item_name'	 => sprintf(__('New %s', $domain), $label),
  'separate_items_with_commas' => $h ? null : sprintf(__( 'Separate %ss with commas', $domain), $label),
  'add_or_remove_items' => $h ? null : sprintf(__( 'Add or remove %ss', $domain), $label),
  'choose_from_most_used' => $h ? null : sprintf(__( 'Choose from the most used %s', $domain ), $label),
  'menu_name'		 => $label
 ), isset($args['labels']) ? (array) $args['labels'] : array());

 $args = $wp_custom_functions->parse_args(array(
  'name' => $default_args['name'],
  'label' => $default_args['label'],
  'labels' => $labels,
  'public' => true, // Should this taxonomy be exposed in the admin UI.
  'show_in_nav_menus' => true,
   // true makes this taxonomy available for selection in navigation menus.
  'show_ui' => true, // Whether to generate a default UI for managing this taxonomy.
  'show_tagcloud' => true, // Whether to allow the Tag Cloud widget to use this taxonomy.
  'hierarchical' => true, // Is this taxonomy hierarchical (have descendants) like
   // categories or not hierarchical like tags.
   //  'update_count_callback' => function() { ; },
  'query_var' => $default_args['name'],
   // False to disable the query_var, set as string to use custom query_var
   // instead of default which is $taxonomy, the taxonomy's "name".
   /* Note: The query_var is used for direct queries through WP_Query like new WP_Query(array('people'=>$person_name)) and URL queries like /?people=$person_name. Setting query_var to false will disable these methods, but you can still fetch posts with an explicit WP_Query taxonomy query like WP_Query(array('taxonomy'=>'people', 'term'=>$person_name)). */
  'rewrite' => true
   // Set to false to prevent automatic URL rewriting a.k.a. "pretty permalinks".
   /* Pass an $args array to override default URL settings for permalinks as outlined below.
'slug' - Used as pretty permalink text (i.e. /tag/) - defaults to $taxonomy (taxonomy's name slug)
'with_front' - allowing permalinks to be prepended with front base - defaults to true
'hierarchical' - true or false allow hierarchical urls (implemented in Version 3.1)
Note: You may need to flush the rewrite rules after changing this. You can do it manually by going to the Permalink Settings page and re-saving the rules -- you don't need to change them -- or by calling $wp_rewrite->flush_rules(). You should only flush the rules once after the taxonomy has been created, not every time the plugin/theme loads. */
/*   'capabilities' => array('manage_terms','edit_terms','delete_terms','assign_terms') */
 ), $args); 

 return $args;
}



function setup_rewrite_rules($rules = array()) {
 $post_types = '('.implode('|', get_taxonomy($this->atts['name'])->object_type).')';
 $index = 'index.php';
 $tax = '('.$this->name().')';
 $feeds = '(feed|rdf|rss|rss2|atom)';
 $anything = '(?:[^/]+/)*';
 $term = '([^/]+)';
 $page = 'page/([0-9]{1,})';
 $end_slash_or_none = '/?$';

 $this->rules[implode('/', array($post_types, $tax, $feeds.$end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&feed=$matches[3]';
 $this->rules[implode('/', array($post_types, $tax, 'feed', $feeds.$end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&feed=$matches[3]';

 $this->rules[implode('/', array($post_types, $tax, $anything.$term, $feeds.$end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&feed=$matches[4]';
 $this->rules[implode('/', array($post_types, $tax, $anything.$term, 'feed', $feeds.$end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&feed=$matches[4]';

 $this->rules[implode('/', array($post_types, $tax, $page.$end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&paged=$matches[3]';
 $this->rules[implode('/', array($post_types, $tax, $anything.$term, $page.$end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&paged=$matches[4]';

 $this->rules[implode('/', array($post_types, $tax, '?$'))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]';
 $this->rules[implode('/', array($post_types, $tax, $anything.$term . $end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&paged=1';
 $this->rules[implode('/', array($post_types, $tax, $anything.$term, $page . $end_slash_or_none))]
  = $index . '?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&paged=$matches[4]';

 return $this->rules;
}

function add_rewrite_rules($wp_rewrite) {
 $wp_rewrite->rules = array_merge($this->setup_rewrite_rules(), $wp_rewrite->rules);
// $wp_rewrite->flush_rules();

 return $wp_rewrite->rules;
}



function set_term_link($term_link, $term, $taxonomy) {
 if (get_option('no_taxonomy_structure')) return $term_link;
 if ($taxonomy != $this->name()) return $term_link;
 $t = get_taxonomy($taxonomy);
 if($t->_builtin || empty($t)) return $term_link;

 $wp_home = rtrim(get_option('home'), '/');
 $post_type = (isset($this->atts['post_type']) && $this->atts['post_type'])? $this->atts['post_type'] : $t->object_type[0];
 $slug = get_post_type_object($post_type)->rewrite['slug'];
 $term_link = str_replace($wp_home, implode('/', array($wp_home, $slug)), $term_link); 
 $str = rtrim(preg_replace("/%[a-z]*%/", "", get_option("permalink_structure")), '/');
 return str_replace($str, "", $term_link);
//$term_link = str_replace( $term->slug.'/', $this->get_taxonomy_parents( $term->term_id,$taxonomy->name, false, '/', true ), $term_link );
 return $term_link;
}


function post_type_taxonomy_term_archive_title($title) {
 global $wp_query;
 if (is_tax($this->name()) && $wp_query->query_vars['post_type'] == $this->atts['post_type']) {
  $title_array = array();
  $post_type_obj = get_post_type_object($this->atts['post_type']);
  $tax = get_taxonomy($this->name());
  $term = get_queried_object();

  $title_array[] = // createHTMLElement('span', array('class'=>'post_type_archive_title'),
   apply_filters('post_type_archive_title', $post_type_obj->labels->name )
  // )
  ;
  $title_array[] = // createHTMLElement('span', array('class'=>'single_term_title'),
   single_term_title( $tax->labels->name, false, false )
  //)
  ;
  
  $seplocation = $this->atts['sep_location'];
  $t_sep = $this->atts['title_sep'];

  // FROM WP_TITLE
  $prefix = (!empty($title))? $t_sep : '';
  if ( 'right' == $seplocation ) { // sep on right, so reverse the order
   $title_array = array_reverse( $title_array );
   $title = implode($t_sep, $title_array) . $prefix;
  } else {
   $title = $prefix . implode($t_sep, $title_array);
  }
 }
 return $title;
}


function name() { return $this->atts['name']; }


public function _filter_body_class($classes) {
 global $post, $wp_query
 ;
 $classes = browser_body_class($classes);
 if (!is_array($classes)) $classes = (array) $classes;
 $tax = $this->name();
 if (is_specific_taxonomy_queried($tax)) {
  $classes[] = 'taxonomy-archive_' . $tax;
  $term = $wp_query->query[$tax] ;
  $classes[] = 'taxonomy-archive_' . $tax . '_' . $term;
  
 }
 return $classes;
}


function _test2($data = null) {
// if (!current_user_can('edit_posts')) return;
 $d = $_POST;
// $d['nonce_value'] = $_POST[$this->atts['nonce']['name']];
// echo('<pre>');print_r($this->_taxonomy_args());echo('</pre>');return;
 $testfile = TEMPLATEPATH . '/_test';
 foreach ($data as $k => $v ) file_put_contents($testfile, $k.'=>'.$v.','."\n", FILE_APPEND);
 file_put_contents($testfile, implode(", ",$ks)."\n", FILE_APPEND); 
 file_put_contents($testfile, " by ".get_class($this)."\n\n", FILE_APPEND);
 if ($data) file_put_contents($testfile, $data."\n", FILE_APPEND); 
}

} /// END OF CLASS



/* Default Setting //*/
function add_default_term_setting_item() {
 global $custom_language_domain
 ;
 $post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), false );
 if ( $post_types ) {
  foreach ( $post_types as $post_type_slug => $post_type ) {
   $post_type_taxonomies = get_object_taxonomies( $post_type_slug, false );
   if ( $post_type_taxonomies ) {
    foreach ( $post_type_taxonomies as $tax_slug => $taxonomy ) {
     if ( ! ( $post_type_slug == 'post' && $tax_slug == 'category' ) && $taxonomy->show_ui ) {
      add_settings_field(
	   $post_type_slug . '_default_' . $tax_slug,
	   sprintf(__('Default %2$s term for %1$s', $custom_language_domain), $post_type->label, $taxonomy->label),
	   'default_term_setting_field',
	   'writing', 'default', array( 'post_type' => $post_type_slug, 'taxonomy' => $taxonomy )
	  );
     }
    }
   }
  }
 }
}
add_action( 'load-options-writing.php', 'add_default_term_setting_item' );


function default_term_setting_field( $args ) {
 global $custom_language_domain
 ;
 $option_name = sprintf('%s_default_%s', $args['post_type'], $args['taxonomy']->name);
 $default_term = get_option( $option_name );
 $terms = get_terms( $args['taxonomy']->name, 'hide_empty=0' );
 if ( $terms ) :
?>
 <select name="<?php echo $option_name; ?>">
  <option value="0"><?php echo __('(Do no set default term)', $custom_language_domain); ?></option>
<?php foreach ( $terms as $term ) : ?>
  <option value="<?php echo esc_attr( $term->term_id ); ?>"<?php echo $term->term_id == $default_term ? ' selected="selected"' : ''; ?>><?php echo esc_html( $term->name ); ?></option>
<?php endforeach; ?>
 </select>
<?php
 else:
?>
 <p><?php echo sprintf(__('%s is not registered.', $custom_language_domain), esc_html( $args['taxonomy']->label )); ?></p>
<?php
 endif;
}


function allow_default_term_setting( $whitelist_options ) {
 $post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), false );
 if ( $post_types ) {
  foreach ( $post_types as $post_type_slug => $post_type ) {
   $post_type_taxonomies = get_object_taxonomies( $post_type_slug, false );
   if ( $post_type_taxonomies ) {
    foreach ( $post_type_taxonomies as $tax_slug => $taxonomy ) {
     if ( ! ( $post_type_slug == 'post' && $tax_slug == 'category' ) && $taxonomy->show_ui ) {
      $whitelist_options['writing'][] = sprintf('%s_default_%s', $post_type_slug, $tax_slug);
     }
    }
   }
  }
 }
 return $whitelist_options;
}
add_filter( 'whitelist_options', 'allow_default_term_setting' );


function add_post_type_default_term( $post_id, $post ) {
 if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' ) { return; }
 $taxonomies = get_object_taxonomies( $post, false );
 if ( $taxonomies ) {
  foreach ( $taxonomies as $tax_slug => $taxonomy ) {
   $default = get_option( sprintf('%s_default_%s', $post->post_type, $tax_slug) );
   if ( ! ( $post->post_type == 'post' && $tax_slug == 'category' ) && $taxonomy->show_ui && $default && ! ( $terms = get_the_terms( $post_id, $tax_slug ) ) ) {
    if ( $taxonomy->hierarchical ) {
     $term = get_term( $default, $tax_slug );
     if ( $term ) {
      wp_set_post_terms( $post_id, array_filter( array( $default ) ), $tax_slug );
     }
    } else {
     $term = get_term( $default, $tax_slug );
     if ( $term ) {
      wp_set_post_terms( $post_id, $term->name, $tax_slug );
     }
    }
   }
  }
 }
}
add_action( 'wp_insert_post', 'add_post_type_default_term', 10, 2 );
