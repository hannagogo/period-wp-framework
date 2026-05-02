<?php

class CustomCommentField extends ClassTemplate {
var $args			 = array();
var $html_args		 = array();
var $form_html		 = '';
var $fields_html	 = '';
var $name			 = '';

public function __construct($args) {
 global $wp_custom_functions, $custom_language_domain;

 $this->html_args = $wp_custom_functions->parse_args( array(
  'name'	 => '',
  'tagname'	 => 'input',
  'type'	 => 'text',
  'value'	 => '',
  'values'	 => NULL,
  'label'	 => '',
  'labels'	 => NULL,
  'cols'	 => '',
  'rows'	 => '',
  'size'	 => '',
  'class'	 => '',
  'required' => FALSE,
  'id'		 => array_value($args, 'id', array_value($args, 'name')),
  'maxlength'=> ''
 ), $args );

 /* // SET THE NAME // */
 $this->name = $this->html_args['name'];

 $this->args = new HashAccessor(
  array_merge(
   $this->_html_atts(),
   $wp_custom_functions->parse_args( array(
    'form'		 => $this->form_html = $this->form_html(),
    'required'	 => false,
	'format'	 => createHTMLElement('dt', array('class'=>'comment_custom_field_key'), '%1$s')
					. createHTMLElement('dd', array('class'=>'comment_custom_field_value'), '%2$s'),
    'condition'	 => TRUE,
    'display'	 => TRUE,
	'concat'	 => ',',
	'required_error' => sprintf(
     __("Please fill the required field (%s).", $custom_language_domain), $this->html_args['label']
    )
   ), $args )
  )
 );
 if ( $this->name ) {
  $this->setup();
 }
}


private function _html_atts($args=NULL) {
 $args = parse_args( $this->html_args, $args );
 foreach ($args as $k=>$v) {
  if ( in_array( $k, array( 'values', 'labels') ) ) {
   if ($v && is_string($v)) { $args[$k] = explode(',', $v); }
   else { $args[$k] = (array) $v; }
  }
  else {
   $args[$k] = (string) $v;
  }
 }
 if ( !empty($args['values']) && empty($args['labels']) ) {
  $args['labels'] = $args['values'];
 }
 return $args;
}


private function setup() {
 $this->_setup_fields();
 $this->_setup_actions();
 $this->_setup_filters();
}


public function condition() {
 $c = $this->args->param('condition');
 $condition = ((is_string($c) && function_exists($c)) || is_callable($c)) ? $c() : $c
 ;
 return $condition;
}


private function _setup_fields() {
}

private function _setup_filters() {
 add_filter( 'comment_form_field_comment', array( &$this, '_filter_form_html') );
 add_filter( 'preprocess_comment', array( &$this, 'check_required' ) );
 add_filter( 'WPCF_Custom_Comment_Format', array( &$this, 'comment_html' ) );
}

private function _setup_actions() {
 add_action( 'comment_post', array( &$this, 'save' ) );
 add_action( 'edit_comment', array( &$this, 'save' ) );
 add_action( 'add_meta_boxes_comment', array( &$this, 'edit_field' ) );
}


public function form_html($defaults = NULL) {
 $required = $this->html_args['required'];
 $is_edit_page = (bool) $defaults;
 $form_html = createHTMLElement('p',
  array('class'=>'comment-form-'.$this->html_args['id']),
  createHTMLElement('label',
   array('for'=>$this->html_args['id']),
   $this->html_args['label'] . ( $required ? '<span class="required">*</span>' : '' )
  ) . $this->form_fields_html($defaults)
  . ($required && !$is_edit_page ?
     createHTMLElement('input', array('type'=>'hidden', 'name'=>'required_field[]', 'value'=>$this->name) ) : ''
    )
 );
 return $form_html;
}


public function form_fields_html($defaults = NULL) {
 $fields_html = '';
 $attrs = array();
 foreach (array('type','name','class') as $a) {
  $attrs[$a] = $this->html_args[$a];
 }
 if ( $this->html_args['tagname'] == 'input' ) {
  if ( in_array( $this->html_args['type'], array( 'radio', 'checkbox' ) ) ) {
   if ( $this->html_args['type'] == 'checkbox' ) $attrs['name'] = $attrs['name'] . '[]' ;
   foreach ($this->html_args['values'] as $k => $v) {
	$attrs['value'] = $v ;
    $attrs['id'] = $this->html_args['name'].'-'.$k ;
	$l = ($l = array_value($this->html_args['labels'], $v, array_value($this->html_args['labels'], $k, $v)))
     ? $l :  $v;
	if (NULL !== $defaults) {
	 $attrs['checked'] = in_array( $v, (array) $defaults );
	}
	$fields_html .= createHTMLElement( $this->html_args['tagname'], $attrs )
	 . createHTMLElement('label', array('for'=>$attrs['id']), $l); 
   }
  }
  else {
   foreach (array('size', 'maxlength') as $a) { $attrs[$a] = $this->html_args[$a]; }
   if (NULL !== $defaults) { $attrs['value'] = $defaults; }
   $fields_html = createHTMLElement($this->html_args['tagname'], $attrs);
  }
 }
 else if ( $this->html_args['tagname'] == 'textarea' ) {
  foreach (array('rows', 'cols') as $a) { $attrs[$a] = $this->html_args[$a]; }
  $field_html = createHTMLElement('textarea', $attrs, $this->html_args['value'] );
 } 
 else if ( $this->html_args['tagname'] == 'select' ) {
  foreach (array('values', 'labels') as $a) { $attrs[$a] = $this->html_args[$a]; }
  if (NULL !== $defaults) { $attrs['value'] = $defaults; }
  $fields_html = html_select_element($attrs);
 }
 return $fields_html;
}


public function check_required($data) {
 $requireds = array_value( $_POST, 'required_field' ); set_custom_functions_data('test_comment_meta_2', $requireds);
 if ( !empty($requireds) && in_array( $this->name, $requireds ) && !isset( $_POST[$this->name] ) ) {
  wp_die( $this->args->param('required_error') );
 }
 return $data;
}


public function _filter_form_html($html) {
 if ( $this->condition() ) { $html .= $this->args->param('form'); }
 return $html;
}


public function save($comment_id) {
 if ( !$comment = get_comment( $comment_id ) ) return false;
 $posted = $_POST[$this->name];
 if ("" == get_comment_meta( $comment_id, $this->name ) ) {
  add_comment_meta( $comment_id, $this->name, $posted, true );
 }
 else if ( $posted != get_comment_meta( $comment_id, $this->name ) ) {
  update_comment_meta( $comment_id, $this->name, $posted );
 }
 else if ( "" == $posted ) { delete_comment_meta( $comment_id, $this->name ); }
 return false;
}  


public function edit_field() {
 global $comment;
 $comment_ID = $comment->comment_ID;
 $nonce = 'post_reviews_date' . '_noncename' ;
 $data = get_comment_meta( $comment_ID, $this->name, true );
 
 $html = $this->form_html($data);
 $html .= createHTMLElement('input',
  array('type'=>'hidden', 'name'=>$nonce, 'id'=>$nonce, 'value'=>wp_create_nonce( __CLASS__ . '-' . __FUNCTION__ . '-' . $this->name )
 ) );
 echo $html;
}


public function comment_html($html) {
 if ( !$this->condition() || !$this->args->param('display') ) return $html;
 global $comment;
 $values = (array) get_comment_meta( $comment->comment_ID, $this->name, FALSE );
 foreach ($values as $i => $v) {
  $values[$i] = createHTMLElement('span', array('class'=>'custom_comment_value_'.$this->name), $v);
 }
 $value = implode( $this->args->param('concat'), $values );
 $html .= sprintf($this->args->param('format'), $this->args->param('label'), $value);
 return $html;
}











} /* // END OF CLASS // */

 /* //
 USAGE:
  1. First, make a custom comment class instance with a hook WPCF_Add_Comment_Field.
     The hook invokes __constract() and builds the instance.
  Examples:
  do_action( 'WPCF_Add_Comment_Field', array(
   'name'    => 'make_public',
   'label'   => 'MAKE PUBLIC',
   'type'    => 'radio',
   'required'=> 1,
   'values'  => array('MAKE PUBLIC','DO NOT MAKE PUBLIC'),
   'condition' => 'is_test_post',
     // STRING is treated as function name (so is the anonymous function.
     //  It returns a string (lambda***) )
  ) );
  do_action( 'WPCF_Add_Comment_Field', array(
   'name'    => 'favorite_fruit',
   'label'   => 'Your Favorite Fruit',
   'tagname' => 'select',
   'values'  => array('Apple','Orange','Grapes'),
   'required'=> 1,
   'condition' => 'is_test_post',
  ) );
  do_action( 'WPCF_Add_Comment_Field', array(
   'name'    => 'favorite_animal',
   'label'   => 'Your Favorite Animal',
   'type'    => 'checkbox',
   'values'  => array('Lion','Giraff','Tiger'),
   'required'=> 1,
   'condition'=> 'is_test_post',
  ) );
 
 REF: CustomCommentFields::comment_html()
  This function simply appends a set of HTML-formatted LABEL-VALUE pair to given HTML.
  By default, definition list ( <dt></dt><dd></dd> pair ) is created.
  
  !!!! NOTE !!!!
  In default case (above), <dl></dl> tag will NOT be created.
  So, add_filter('WPCF_Custom_Comment_Format', 'wrap_with_dl');
  AFTER instances is created.
  
  Also It is hooked to WPCF_Custom_Comment_Format
  Thus, the other instance of this class also adds an action to this hook (only if condition() is TRUE)
  The result can be get by applying filter (apply_filters("WPCF_Custom_Comment_Format", NULL))
  Function custom_comment_fields() do this work and by default custom_comment_fields
  is hooked to "comment_text" and "get_comment_text." See wp_custom_functions.php

  2. In comments.php (or any comment template)
  echo build_comments(array(
   'elements'
      => array('navigation', 'comments', 'navigation', 'form'), // order of the comment elements
   'comment_form_args'
      => array( ), // ARRAY for 'comment_form'
   'wp_list_comments_args'
      => array('callback'=>'custom_comment_format') // ARRAY for 'wp_list_comments'
   'password_required_message'
      => __( 'This post is password protected. Enter the password to view any comments.', $custom_language_domain ),
   'comment_closed_message'
      => __( 'Comments are closed.', $custom_language_domain ),
   'previous_comments_link'
      => sprintf(
          __('%s&larr;%2$s Older Comments', $custom_language_domain),
          createHTMLElement('span', 'start', array('class'=>'meta-nav nav_prev') ), createHTMLElement('span','end')
         ),
   'next_comments_link'
     => sprintf(
         __('Newer Comments %1$s&larr;%2$s', $custom_language_domain),
         createHTMLElement('span', 'start', array('class'=>'meta-nav nav_next') ), createHTMLElement('span','end')
        ),
  ) );

 // */

