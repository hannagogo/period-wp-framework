<?php
/* // Appends custom option field in general settings page. // */
class CustomOption extends ClassTemplate {

var $args = array();

public function __construct($args) {
 $this->setup_arguments($args);
 if (empty($this->args['name'])) return; // EXITTING.

 if (empty($this->args['field'])) {
  $this->args['field'] = createHTMLElement('input', array(
   'type'=>'text',
   'value'=>get_option($this->args['name']),
   'name'=>$this->args['name'],
   'class'=>"regular-text"
  ) );
 }

 add_action( 'WPCF_Admin_Initialize', array(&$this, 'add') );
 add_filter( 'whitelist_options', array( &$this, 'add_whitelist') );
 return $this;
}

public function add_whitelist($whitelist) {
 $whitelist['general'][] = $this->args['name'];
 return $whitelist;
}
private function setup_arguments($args) {
 global $wp_custom_functions;
 if (is_string($args)) { $this->args["name"] = (string) $args; }
 else { $this->args = (array) $args; } 

 $this->args = parse_args( array(
  'name' => '',
  'label' => array_value($this->args, "name"),
  'field' => '',
  'section' => 'default',
  'args' => array()
 ), $this->args );
 return $this;
}

public function add($args = NULL) {
 if (!empty($args)) $this->setup_arguments($args);
 register_setting( 'WPCF_Custom_Option', $this->args['name'] );
 add_settings_field($this->args['name'], $this->args['label'], array(&$this, 'field'), 'general');
}

public function field($args = NULL) {
 if (!empty($args)) $this->setup_arguments($args);
 echo $this->args['field'];
}

} /* ////// END OF CLASS ////// */