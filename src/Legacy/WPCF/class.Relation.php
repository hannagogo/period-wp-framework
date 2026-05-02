<?php
class Relation extends ClassTemplate {
public $parents = array();
public $children = array();
public $parent_object = NULL ;
public $child_object = NULL ;
public $parent_id_key = 'custom_parent';
public $parent_name = NULL;
public $child_name = NULL;
public $RELATION = array();
public $RELATION_REV = array();
public $RELATION_OBJECT = array();

function __construct($args=NULL) {
 global $wpdb, $post, $custom_language_domain, $WPCF_Relation
 ;
 if (empty($WPCF_Relation)) { $WPCF_Relation = array(); }
 
 $args_tmp = parse_args( array(
  'parent'  => 'post',
  'child'   => 'post',
  'parent_label' => NULL,
  'child_label' => NULL,
 ), $args )
 ;
 $pn = $args_tmp['parent'];
 $cn = $args_tmp['child'];
 ;
 if (is_array($pn) && array_values('name', $pn)) {
  $a = $pn;
  do_action( 'WPCF_Add_PostTypes', apply_filters( 'WPCF_New_PostType', $a ) );
  $this->param('parent', $a['name']);
 }
 else if ( !post_type_exists($pn) ) {
  do_action( 'WPCF_Add_PostTypes', apply_filters( 'WPCF_New_PostType', array('name'=>$pn ) ) );
 }
 if (is_array($cn) && array_values('name', $cn)) {
  $a = $cn;
  do_action( 'WPCF_Add_PostTypes', apply_filters( 'WPCF_New_PostType', $a ) );
  $this->param('child', $a['name']);
 }
 else if (!post_type_exists($cn)) {
  do_action( 'WPCF_Add_PostTypes', apply_filters( 'WPCF_New_PostType', array('name'=>$cn ) ) );
 }
 $this->parent_object = get_post_type_object($pn); 
 $this->child_object = get_post_type_object($cn); 
 $this->param( 'parent', $this->parent_name = $pn );
 $this->param( 'child', $this->child_name = $cn );

 $this->param('parent_label', $args_tmp['parent_label'] ? $args_tmp['parent_label'] : $this->parent_object->label );
 $this->param('child_label', $args_tmp['child_label'] ? $args_tmp['child_label'] : $this->child_object->label );

 $this->param( parse_args(array(
  'parent_box_name'      => sprintf('custom_parent_box_%s-%s', $pn, $cn ),
  'child_box_name'       => sprintf('custom_child_box_%s-%s', $pn, $cn ),
  'parent_box_title'     => sprintf(
    __('%s in this %s', $custom_language_domain), 
    $this->param('child_label'), $this->param('parent_label')
   ),
  'child_box_title'       =>  sprintf(
    __('%s in the same %s', $custom_language_domain),
    $this->param('child_label'), $this->param('parent_label')
   ),
  'parent_field_name'    => sprintf('wpcf_relation_parent_%s-%s', $args_tmp['parent'], $args_tmp['child'] ),
  'multiple'             => FALSE,
  'parent_id_key'        => $this->parent_id_key
 ), $args) )
 ;
 $this->parent_id_key = $this->param('parent_id_key');
 $_relation_exists = $_child_exists = FALSE ;
 foreach ($WPCF_Relation as $r) {
  if ( $r !== $this ) {
   if ( $r->child_name == $this->child_name ) {
    $_child_exists = TRUE ;
    if ( $r->parent_name == $this->parent_name ) { $_relation_exists = TRUE ; }
   }
  }
 }
 if ( $_relation_exists ) {
  trigger_error(
   sprintf(
    __('Relation with the same definition (Parent: %s; Child: %s) already exists. Only one pair of the same Post Types is allowed. Exitting.', $custom_language_domain),
    $this->parent_object->label,
    $this->child_object->label
   ), E_USER_WARNING
  );
  return $this;
 }
 if ( $_child_exists ) {
  if ( $this->param('multiple') ) {
   $this->parent_id_key = $this->parent_id_key . '_' . $this->parent_name . '-' . $this->child_name ;
  }
  else {
   if (!$this->param('multiple')) {
    trigger_error(
     sprintf(
      __('Relation with the same child (%s) already exists. Use "multiple" => TRUE to automatically expand the database key or specify a key when generate an instance passing "parent_id_key" => [something_you_want] in the argument.', $custom_language_domain),
      $this->child_object->label
     ), E_USER_WARNING
    );
   }
  }
 }
// if (is_specific_user_logged_in(1)) { my_print_r($this->param()); }
// $this->param('parent_box_name', $this->param('parent_box_name') . '_box_' . $this->parent_name);
// $this->param('child_box_name',  $this->param('child_box_name')  . '_box_' . $this->parent_name);
 do_action('WPCF_Add_MetaBox', array(
  'name' => $this->param('parent_box_name'),
  'title'=> $this->param('parent_box_title'),
  'context' => 'side',
  'post_type' => $this->param('parent'),
 ) ); 
 do_action('WPCF_Add_MetaBox', array(
  'name' => $this->param('child_box_name'),
  'title'=>  $this->param('child_box_title'),
  'context' => 'side',
  'post_type' => $this->param('child'),
  'fields' => array(
   $this->parent_id_key => array( 'type' => 'hidden' ), // 'custom_parent'
  )
 ) ); 
 add_filter( 'WPCF_Meta_Box_HTML', array(&$this, 'meta_box_html') );
 $WPCF_Relation[] = $this ;
 
 
 // BUILD RELATION CACHE
 ;
 foreach ( $wpdb->get_results("SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = '".$this->parent_id_key."'") as $meta ) {
  $parent = maybe_unserialize($meta->meta_value);
  if (is_array($parent)) $parent = $parent[0];
  if (!isset($this->RELATION[$parent])) $this->RELATION[$parent] = array();
  $this->RELATION[$parent][] = $meta->post_id;
  $this->RELATION_REV[$meta->post_id] = $parent;
 }
 foreach (
  $wpdb->get_results("SELECT ID, post_status, post_type FROM " . $wpdb->posts
   . " WHERE ID IN (" . implode (',', array_keys($this->RELATION_REV) ) . ')'
  ) as $p) {
  $this->RELATION_OBJECT[$p->ID] = $p;
 }
}


function setup_meta_box() {
 global $post, $custom_language_domain
 ;
 ;
}


function meta_box_html($content) {
 global $post, $custom_language_domain
 ;
 $is_parent_box = is_specific_metabox_in_process($this->param('parent_box_name'));
 $is_child_box  = is_specific_metabox_in_process($this->param('child_box_name'));
 /* // PARENT BOX // */
 if ($is_parent_box) {
  $children = $this->get_children($post->ID);
  if ( !empty($children) ) {
   $list = '';
   foreach ( $children as $p ) {
    $list .= $this->_format_edit_post_link($p);
   }
   $content .= createHTMLElement('ul', array(
    'id'=>$this->param('parent_box_name').'_'.$this->parent_name.'_list',
    'class'=>__CLASS__.$this->param('parent_box_name').'_list'
   ), $list );
  }
  ;
  if (isset($_GET[$this->parent_id_key])) {
  }
  ;
  $content .= createHTMLElement('a', array(
   'id'=>'new_custom_child_'.sprintf('%s-%s',$this->param('parent'),$this->param('child')).'_link',
   'class'=>'new_custom_child_link',
   'href'=>admin_url( sprintf(
    '/post-new.php?%s=%s&post_type=%s',
    $this->parent_id_key, $post->ID, $this->param('child')
   ) )
  ), sprintf( __('Add New %s', $custom_language_domain), $this->param('child_label')) )
  ;
  $content .= wrapJavaScript('$(".new_custom_child_link").button()', array('jquery'=>TRUE, 'jqueryready'=>FALSE) )
  ;
 }

 /* // CHILD BOX // */
 if ($is_child_box) {
  $parent_id = get_multi_post_meta($post->ID, $this->parent_id_key);
  if (empty($parent_id)) {
   $parent_id = array();
   $parent_id[] = (isset($_GET[$this->parent_id_key]) && $_GET[$this->parent_id_key] ? $_GET[$this->parent_id_key] : '');
  }
  $siblings = $parents = '';
  foreach ($parent_id as $id) {
   if ($id == 0) { continue ; }
   // Siblings
   $children = $this->get_children($id);
   if (!empty($children)) {
    foreach ( $children as $p ) {
     $siblings .= $this->_format_edit_post_link($p);
    }
    if (!empty($siblings)) {
     $content .= createHTMLElement('ul', array(
      'id'=>$this->param('child_box_name').'_list_parent_'.$id,
      'class'=>$this->param('child_box_name').'_list'
     ), $siblings);
    }
   }
   // Parents
   $po = get_post($id);
   $p_c = sprintf('%s-%s',$this->param('parent'),$this->param('child'));
   $parents .= createHTMLElement('li', array(),
    createHTMLElement('a', array(
     'id'=>'edit_custom_parent_'.$p_c.'_link',
     'class'=>'edit_custom_parent_link',
     'href'   => admin_url( sprintf('/post.php?post=%s&action=edit', $id) ),
     'target' => '_blank',
    ), $po->post_title)
   )
   ;
   $parents .= createHTMLElement('li', array(),
    createHTMLElement('a', array(
     'id'=>'new_custom_parent_'.$p_c.'_link',
     'class'=>'new_custom_parent_link',
     'href'   => admin_url(
      sprintf('/post-new.php?%s=%s&post_type=%s', $this->parent_id_key, $po->ID, $this->param('child'))
     ),
     'target' => '_blank',
    ), sprintf( __('Add New %s', $custom_language_domain), $this->param('child_label')) )
   )
   ;
  }
  if (!empty($parents)) {
   $parents = createHTMLElement('ul', array(
    'id'=>$this->param('child_box_name').'_list_parents',
    'class'=>$this->param('child_box_name').'_list'
   ), $parents);
   $parents = createHTMLElement('label', NULL, sprintf( __('Edit %s of this %s', $custom_language_domain), $this->param('parent_label'), $this->param('child_label')) ) . $parents;
   $content .= $parents;
   $content .= wrapJavaScript('$(".new_custom_parent_link").button()', array('jquery'=>TRUE, 'jqueryready'=>FALSE) )
  ;

  }
  ;
  $content .= wrapJavaScript('var custom_parent_field = $("#'.$this->param('child_box_name').'_'.$this->parent_id_key.'_0"); if(custom_parent_field && !custom_parent_field.val()) custom_parent_field.val('.$parent_id[0].'); //console.log(custom_parent_field)', array('jquery'=>TRUE, 'jqueryready'=>TRUE));
 }
 return $content;
}

function _format_edit_post_link($post, $wrap='li') {
 global $custom_language_domain
 ;
 if (empty($post)) return;
 $post = get_post($post);
 setup_postdata($post); 
 $a = createHTMLElement('a', array(
  'href'=>get_edit_post_link($post->ID),
  'target'=>'_blank'
 ), $post->post_title )
 . ' '
 . sprintf('[%s]', createHTMLElement('a', array(
    'href'=>get_edit_post_link($post->ID)
   ), __('Edit', $custom_language_domain) ) )
 . ' '
 . sprintf('[%s]', createHTMLElement('a', array(
    'href'=>get_permalink($post->ID), 'target'=>'_blank'
   ), __('View', $custom_language_domain) ) )
 ;
 if ($wrap) { $a = createHTMLElement($wrap, NULL, $a); }
 return $a;
}

public function get_children_meta($parent, $object=TRUE, $post_status='publish') {
 global $wpdb
 ;
 if (is_string($post_status)) $post_status = explode(',', $post_status);
 $post_status = (array) $post_status;
 ;
 if (!$parent = get_post($parent)) { return; } 
 $db_query =
  "SELECT * FROM " . $wpdb->postmeta .
  " WHERE meta_key = '".$this->parent_id_key."'" .
//  " AND (meta_value = '".$parent->ID."' OR (meta_value LIKE '%:\\\"".$parent->ID."\\\";%') )" // This is slow. Altered.
  " AND (meta_value = '".$parent->ID."' OR meta_value = '" . serialize(array((string) $parent->ID)) . "')"
//  " AND meta_value = '".$parent->ID."'"
 ;
 $children = array();
 $children_ids = array();
 foreach ( $wpdb->get_results($db_query) as $p ) {
  $c = get_post($p->post_id);
  if (!in_array($c->post_status, $post_status)) continue;
  if ($c->post_type != $this->param('child')) { continue; }
  if (in_array($p->post_id, $children_ids)) { continue; }
  $children_ids[] = $c->ID ;
  $children[] = $object ? $c : $c->ID
  ;
 }
 unset( $db_query );
 return $children;
}


public function get_children($parent, $object=TRUE, $post_status='publish') {
 global $wpdb
 ;
 if (is_string($post_status)) $post_status = explode(',', $post_status);
 $post_status = (array) $post_status;
 ;
 $parent_id = $parent ;
 if ($parent instanceof WP_Post) $parent_id = $parent->ID ;
 ;
 $children = array();
 
 if (isset($this->RELATION[$parent_id])) {
  foreach ($this->RELATION[$parent_id] as $p) {
//   $q = "SELECT post_status, ID, post_type FROM " . $wpdb->posts . " WHERE ID=" . $p . " LIMIT 1";
//   $posts = $wpdb->get_results($q);
   
   $c = $this->RELATION_OBJECT[$p];
   if (!in_array($c->post_status, $post_status)) continue;
   if ($c->post_type != $this->param('child')) { continue; }   
   $children[] = $object ? get_post($p) : $p
   ;
  }
 }
 return $children;
}

public function get_parents($child, $object=TRUE) {
 if (!$child = get_post($child) ) return;
 $parents = array();
 foreach (get_multi_post_meta($child->ID, $this->parent_id_key) as $p) {
  $parents[] = $object ? get_post($p) : $p;
 }
 return $parents;
}

public function get_parent($child, $object=TRUE) {
 $parents = $this->get_parents($child, $object) ;
 if (isset($parents[0]) && $parents[0]) return $parents[0];
}

public function get_parent_post_type() { return $this->parent_object ; }
public function get_child_post_type() { return $this->child_object ; }

}