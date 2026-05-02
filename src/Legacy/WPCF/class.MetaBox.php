<?php
/* 
new MetaBox(array(
 'name' => 'post_settings',
 'title'=>__('Post Settings, Scripts and Styles', $custom_language_domain),
 'context' => 'side',
 'post_type' => 'any', // or any other PostType names. (give an array if plural)
 'create_box' => FALSE, // FALSE to not to display a box for the meta box in admin page
 'fields' => array(
  '__regular_form' => array(
   'label'=>__('CSS Files', $custom_language_domain),
   'type'=>'textarea',
   'rows'=>$rows,
   'cols'=>$cols,
   'default_title_value'=>array('truncate'=>32, 'overwrite'=>true) // use the value as #title value. set true overwrite
  ),
  '__using_post_type' => array(
   'type' => 'posts',
   'form_type' => 'checkbox', // or 'radio' for exclusive selection
   'label' => 'Using Post Type',
   'query' => array('post_type'=>'post', 'posts_per_page'=>get_option('posts_per_page'),),
  )
 ) );

//*/
class MetaBox extends ClassTemplate {



function __construct($atts = null) {
 $this->param( (array) $atts );
 if ( !$this->param('init_hook')) $this->param('init_hook', 'admin_xml_ns');
 if ( !isset($atts['create_box']) ) {
  $this->param('create_box', TRUE);
 }
 $_post_types = $this->param('post_type');
 is_null($_post_types) && $this->param('post_type', 'post');
 if (
  (  is_string($_post_types) && $_post_types == 'any')
  || 
  (is_array($_post_types) && in_array('any', $_post_types))
 ) {
  $this->param( 'post_type', array_merge(get_post_types(array('public'=>TRUE,'_builtin'=>FALSE)),get_post_types(array('_builtin'=>TRUE,'public'=>TRUE))) );
 }
 $this->param( 'post_type', (array) $this->param( 'post_type' ) );

 $this->image_form_params = array(
  'image_size_suffix'	 => '_size',
  'image_size_id_suffix' => '_image_size',
  'button_suffix'		 => '_button',
  'button_delete_suffix' => '_delete',
  'image_view_box_suffix'=> '_image_view',
 );
 $this->param('nonce', array(
  'action' => $this->param('name'),
  'name' => $this->param('name').'_nonce'
 ) );
 $this->setup();
}

function arguments() {
 $a = array();
 foreach ($this->accepted_param_keys as $k) $a[$k] = is_null($this->param($k))? null : $this->param($k);
 if (is_null($a['title']) && !is_null($a['name'])) {
  $a['title'] = sprintf('Title: %s', $a['name']);
 }
 return $a;
}


function _register_update_action($action = 'save_post') {
 add_action($action, array(&$this, 'update'));
}


function setup($meta_box_args = null) {
// if ( ! did_action( 'wp_enqueue_media' ) ) wp_enqueue_media();
 if (!$meta_box_args) $meta_box_args = $this->arguments();
 if (is_array($meta_box_args) && isset($meta_box_args['name'])) {
  add_action($meta_box_args['init_hook'], array(&$this, 'setup_meta_box'));
 }
 add_action('save_post', array($this, 'update'),100);
 foreach ((array) $meta_box_args['fields'] as $f) {
  if (isset($f['datepicker']) && $f['datepicker']) enqueue_admin_js_library_handle(array('jquery-ui', 'jquery-ui.sliderAccess'));
  if ((isset($f['datetimepicker']) && $f['datetimepicker']) || (isset($f['timepicker']) && $f['timepicker'])) enqueue_admin_js_library_handle(array('jquery-ui.timepicker-addon','jquery-ui.sliderAccess'));
 }
 add_action('wp_ajax_get_thumbnail', array(&$this, 'ajax_get_thumbnail'));
 add_action('wp_ajax_get_image_size', array(&$this, 'ajax_get_image_size'));
 add_filter('WPCF_MetaBox_Display_Obsolete_Field', function($a) { return $a; }); // Expects array('meta_box_field_name', 'value')
 return $this;
}


function setup_meta_box($meta_box_args = null) {
 if (!$meta_box_args) $meta_box_args = $this->arguments();//my_print_r($this->param('name'));
 if ($this->param('create_box') ) {
  foreach ($this->param('post_type') as $pt) {
   add_meta_box(
    get_class($this) . '_' . $meta_box_args['name'], // ID
    $meta_box_args['title'], // title
    array(&$this, 'meta_box_html'),
     // Function that prints out the HTML for the edit screen section.
    $pt,
     // The type of Write screen on which to show the edit screen section
     // ('post', 'page', 'link', or 'custom_post_type' where custom_post_type
     // is the custom post type slug)
    ($meta_box_args['context'] ? $meta_box_args['context'] : 'normal'),
     // The part of the page where the edit screen section should be shown
     // ('normal', 'advanced', or 'side')
    ($meta_box_args['priority'] ? $meta_box_args['priority'] : 'high'),
     // The priority within the context where the boxes should show
     // ('high', 'core', 'default' or 'low')
    $meta_box_args // Arguments to pass into your callback function.
     // The callback will receive the $post object and whatever parameters are passed
     // through this variable.
   );
  }
  enqueue_admin_jquery_code('$("#'.get_class($this) . '_' . $meta_box_args['name'] .' .inside").append($("<div />").css("clear","both"));');
 }
}

private function _metabox_html_build_multiplier_attrs($id, $plus_or_minus) {
 $porm = ($plus_or_minus == '+') ? 'plus' : 'minus';
 $a = array(
  'class' => array(
   __CLASS__ . '_form_field_multiply',
   __CLASS__ . '_form_field_multiply_'.$porm,
   $this->param('name') . '_form_field_multiply',
   $this->param('name') . '_form_field_multiply'.'_'.$porm,
   $id . '_form_field_multiply',
   $id . '_form_field_multiply'.'_'.$porm
  ),
  'id' => $id.'_'.$porm,
 );
 if ($plus_or_minus != '+')  $a[ 'data-multiply_target' ] = $id ;
 return $a
 ;
}

public function meta_box_html($post, $meta_box){
 global $wp_custom_functions, $custom_language_domain
 ;
 $post = get_post($post);
 $meta_args = isset($meta_box['args']) ? $meta_box['args'] : $meta_box ;
 $field_id = preg_replace('/\s/', '', $meta_args['name']);
 $field_count = 0;
 $visible_field_count = 0;
 $box_script = '';
 $n = $this->param('nonce');
 if (isset($meta_args['row_group'])) {
  $meta_args['row_group'] = intval($meta_args['row_group']);
 }
 else {
  $meta_args['row_group'] = 0;
 }
 $h = wp_nonce_field($n['action'], $n['name'], true, false); //init $h here

 $self_name = $this->param('name');
 $class_name = get_class($this);
 $form_types = array(
  'textfield'	 => 'input',
  'text'		 => 'input',
  'checkbox'	 => 'input',
  'hidden'		 => 'input',
  'radio'		 => 'input',
  'select'		 => 'select',
  'textarea'	 => 'textarea',
  'image'		 => 'image'
 );
 $default_form_type = array_value($meta_args, 'type');
 if (!$default_form_type) {
  $default_form_type = array_value(array_keys($form_types),0);
 }
 $box_script .= $meta_args['script']  . LF;
 $box_script .= '
var get_multiply_group = function(b){
 if (undefined === b || undefined === (b = $(b)).get(0)) return false;
 var field_name = b.attr("id").replace(/^'.$field_id.'_(.+?)(?:_(\d+))?_(?:plus|minus)$/, "$1")
    , multiply_group = false
 for (var i in multiply_groups) {
  if (in_array(field_name, multiply_groups[i])) { multiply_group = multiply_groups[i]; break; }
 }
 return multiply_group
}
' . LF;
 if (empty($meta_args['multiply_group'])) {
   $box_script .=
'
var multiply_groups = []
'
 ;
 }
 if (!empty($meta_args['multiply_group'])) {
  $group = is_array($meta_args['multiply_group']) ? $meta_args['multiply_group'] : explode( ',', $meta_args['multiply_group']) ;
  if (isset($group[0]) && is_string($group[0])) $group = array($group);
  $group_as_js = array();
  foreach ($group as $g) {
   $group_as_js[] = '["' . implode('","', $g) . '"]';
  }
  $group_as_js_string = '[' . implode(',', $group_as_js) . ']';
  foreach ($group as $i=>$n) {
   if (empty($n)) unset($group[$i]);
  }
  $group = array_merge(array(), $group);
  if (!empty($group)) {
   $box_script .= '
var multiply_groups = '.$group_as_js_string.'
  , multiply_in_process = false
$(".'.$field_id.'_form_field_multiply_plus'.'").on("click", function(){
 var b = $(this)
    , multiply_group = get_multiply_group(b)

 if (multiply_group === false) return false;
 
 if (multiply_in_process === false) { multiply_in_process = new Array() }
 if ( in_array(b.attr("id"), multiply_in_process) ) return false;
 else multiply_in_process.push(b.attr("id"))

 for (var i in multiply_group) {
  var id = "'.$field_id.'_" + multiply_group[i] + "_plus";
  if (in_array(id, multiply_in_process)) { continue }
  else {
   !(in_array(id, multiply_in_process)) && multiply_in_process.push(id)
   $("#"+id).trigger("click")
  }
 }
 if (multiply_in_process.length == multiply_group.length) {
  multiply_in_process = false
  multiply_group = false
  return false
 }
})
';
  }
 }

 if ( isset($meta_args['fields']) && $meta_args['fields'] ) {
  foreach ($meta_args['fields'] as $k => $v) {
  /* //
    $values = (array) get_post_meta($post->ID, $k, FALSE);
   $values = empty($values) ? array('') : $values;
   if (is_array($values[0])) $values = $values[0];
  // */
   $values = apply_filters('WPCF_Get_Post_Meta', $post->ID, $k, FALSE);
   $_obsolete = isset($v['obsolete']) && $v['obsolete'];
   $_hide_if_empty = !isset($v['hide_if_empty']) || (TRUE == (bool) $v['hide_if_empty']);
   $_hide_box = $_values_empty = $_empty = FALSE ;

   if ($_obsolete) {
    $_hide_box_filter = (bool) apply_filters('WPCF_MetaBox_Display_Obsolete_Field', array('meta_key'=>$k, 'meta_value'=>$v));
    if (empty($values)) { $_empty = TRUE; }
    else {
     $_has_value = FALSE ;
     foreach ($values as $_value) { $_has_value = !empty($_value) || $_has_value; }
     $_empty = !$_has_value;
    }
    $_values_empty = $_empty;
    $_hide_box = !$_hide_box_filter || ($_values_empty && $_hide_if_empty);
    if ($_hide_box) continue; ////// CONTINUE'S HERE
   }
   
   $field_count++;
   if (isset($v['type']) && $v['type'] != 'hidden') $visible_field_count++;
   
   if (!isset($v['type'])) $v['type'] = $default_form_type ;
   if ($v['type'] == 'textfield') $v['type'] = 'text';
   if (!isset($v['script'])) $v['script'] = '';
   if (!isset($v['values'])) $v['values'] = '';
   if (!isset($v['label'])) $v['label'] = '';
   if (!isset($v['default'])) $v['default'] = '';
   if (gettype($v['values']) == gettype('') && (isset($v['use_preset']) && $v['use_preset'])) { // Using Presets
    $preset = $v['values']; $v['values'] = array();
    if (array_key_exists($preset, $this->preset_select_options)) {
     foreach ($this->preset_select_options[$preset] as $pk=>$pv) {
      $v['values'][$pk] = __($pv, $custom_language_domain);
     }
    }
    else { $v['values'] = (array) $v['values']; }
   }
   else {
    $v['values'] = (array) $v['values'];
   }

   if (isset($v['_omit_field']) && !empty($v['_omit_field']) && in_array($k, (array) $v['_omit_field'])) {
    continue ; // EXITTING!
   }
   
   if ($v['type'] == 'select') {
    $multi = false;
    foreach ($values as $n=>$m) {
     if (is_array($m)) { $multi = true; break; }
    }
    if (!$multi) {
     $values = array( $values );
    }
   }
   $_is_multiple_form_element_type = isset($v['type']) && in_array($v['type'], array('radio','checkbox','select'));
   
   if (!isset($v['value_label'])) $v['value_label'] = null;
   else $v['value_label'] = (array) $v['value_label'];
   ($count = count($values)) == 0 && $count = 1;
   if (isset($v['increase_field']) && (bool) $v['increase_field']) { $count++; }
   
   if (isset($v['multiply']) && $v['multiply'] > $count) $count = $v['multiply'];
   $field_name = $k . '[]' ;
   $label_for = $field_id.'_'.$k;
   $multipliable = (isset($v['multipliable']) && (bool) $v['multipliable']) ?
    createHTMLElement('span', $this->_metabox_html_build_multiplier_attrs($label_for, '+'), '+')
    :
    ''
   ;
   $multipliable_wrap_start = createHTMLElement( 'div', 'start', array('class'=>array($self_name.'_form_field_wrap', $label_for.'_form_field_wrap', $class_name.'_form_field_wrap'), 'id'=>'%s_form_field_wrap') );
   $multipliable_wrap_end = createHTMLElement( 'div', 'end');
 
   $_use_pickers = array( 'datepicker' => FALSE, 'datetimepicker'=>FALSE, 'timepicker'=>FALSE, 'slider'=>FALSE, 'colorpicker'=>FALSE );
 
   $delete_field_dialog = $wp_custom_functions->parse_args( array(
    'message'		 => __('Are you sure you want to delete the field?', $custom_language_domain),
    'title'			 => __('Delete Field Confirmation', $custom_language_domain),
    'message_media'	 => __('Are you sure you want to delete the media field?', $custom_language_domain),
    'title_media'	 => __('Delete Media Field Confirmation', $custom_language_domain),
    'confirm_button'	 => __('Delete', $custom_language_domain),
    'cancel_button'	 => __('Cancel', $custom_language_domain),
   ), ((isset($v['confirm_delete_dialog']) && is_array($v['confirm_delete_dialog'])) ? $v['confirm_delete_dialog'] : array()) )
   ;
   if ($v['type'] != 'hidden') {
    $box_class = $label_class = array();
	if ($_obsolete) { $box_class[] = 'obsolete'; }
	if ($_values_empty) { $box_class[] = 'empty'; }
	if ($_hide_if_empty && $_values_empty) { $box_class[] = 'empty_hide'; $box_class[] = 'hide_box'; }
    if (isset($v['class']) && !empty($v['class'])) {
     if (!is_array($v['class'])) {
      $bc = preg_split('/[,\x20]/', $v['class']);
      foreach ($bc as $c) {
       $box_class[] = $c . '_box';
       $label_class[] = $c . '_title';
      }
     }
    }// if (is_specific_user_logged_in(1)) my_print_r($box_class);
    $box_class = array_merge($box_class, array(
     $self_name, $class_name,
     $self_name.'_box', $class_name.'_box',
     $class_name.'_field_count_'.($field_count % 2 ? 'odd':'even'),
     $class_name.'_field_count_'.$field_count,
     $class_name.'_visible_field_count_'.($visible_field_count % 2 ? 'odd':'even'),
     $class_name.'_visible_field_count_'.$visible_field_count,
     ($meta_args['row_group'] ? $class_name.'_row_'.(intval(($field_count -1) / $meta_args['row_group']) % 2  ? 'odd':'even') : '')
    ) )
    ;
    if (isset($form_types[$v['type']])) {
     $box_class[] = $self_name.'_'.($form_types[$v['type']] == $v['type'] ? $form_types[$v['type']] : $form_types[$v['type']].'_'.$v['type']).'_box';
     $box_class[] = $class_name.'_'.($form_types[$v['type']] == $v['type'] ? $form_types[$v['type']] : $form_types[$v['type']].'_'.$v['type']).'_box';
    }
    ;
    $h .= createHTMLElement('div', 'start', array( 'class'=>$box_class, 'id'=>$label_for.'_box') )
    . createHTMLElement(
       ($_is_multiple_form_element_type ?'div':'label'),
       array('for'=>$label_for.'_'.($count-1), 'class'=>array_merge(array($class_name.'_title', $self_name.'_title'), $label_class) ),
       $v['label']
      )
    . (
       $multipliable ?
		createHTMLElement('p', array('id'=>$label_for.'_delete_field_dialog', 'class'=>array($label_for.'_delete_field_dialog_message', 'delete_field_dialog_message', 'delete_field_dialog') ),
         $delete_field_dialog['message']
        )
      :
       ''
      )
    ;
   }
 
   foreach (array_keys($_use_pickers) as $_p) {
    $_use_pickers[$_p] = isset($v[$_p]) && $v[$_p];
   }
   
   if ($multipliable) {
    $v['script'] .= '
 $("#'.$label_for.'_delete_field_dialog").hide().dialog({
  autoOpen : false, closeOnEscape:true, modal:true,
  title:"'.$delete_field_dialog['title'].'",
  buttons:{
   "'.$delete_field_dialog['confirm_button'].'": function(){
    var target = $.'.__CLASS__.'_delete_field
      , m = target.match(/^'.$field_id.'_(.+?)_(\d)+$/)
      , field_name = RegExp.$1
      , field_count = RegExp.$2
      , multiply_group = get_multiply_group($("#" + target + "_minus"))
    if (false === multiply_group) { multiply_group = [field_name] }
    for (var i in multiply_group) {
     $("#'.$field_id.'_" + multiply_group[i] + "_" + field_count + "_form_field_wrap").fadeOut({complete:function(){$(this).remove()}}) ////////// DELETE
    }
    $(this).dialog("close")
    multiply_group = false
   },
   "'.$delete_field_dialog['cancel_button'].'":function(){ $(this).dialog("close") }
  }
 })
 
 var fn_confirm_delete_field_dialog = function(){
  var b = $(this)
    , multiply_group = get_multiply_group(b)
    , b_id = b.attr("id")
    , m = b_id.match(/^'.$field_id.'_(.+?)(?:_(\d+))?_minus$/) 
    , field_name = RegExp.$1
    , field_count = RegExp.$2
  $.extend({"'.__CLASS__.'_delete_field":b.data("multiply_target")})
  var target = $.'.__CLASS__.'_delete_field
  
  if (false === multiply_group || false === multiply_in_process) {
   $("#'.$label_for.'_delete_field_dialog").dialog("open"); $(".ui-front").css("z-index",300011); $(".ui-front.ui-widget-overlay").css("z-index",300010)
  }
 }
 
 $(".'.__CLASS__.'_form_field_multiply_minus", $("#'.$label_for.'_box")).each(function(){$(this).on("click", fn_confirm_delete_field_dialog)});
 
 $("#'.$label_for.'_plus").on("click", function(){
  var fieldname="'.$label_for.'"
    , field_count = parseInt(fieldname.replace(/^.*?_([\d]+)$/, "$1"))
    , wrapper = fieldname+"_form_field_wrap"
    , w = $("."+wrapper+":last")
    , ww = w.clone(true)
    , fid_re = new RegExp("("+fieldname+"_)([0-9]+)", "g")
    , fid_replace = function(){return arguments[1]+(parseInt(arguments[2])+1)}
    , minus = $("span.'.__CLASS__.'_form_field_multiply_minus",ww)
    , image_view = $(".'.$label_for.'_image_view", ww)
    , newid
    , delete_button = $(".'.$label_for.'_delete_button",ww)
    , pickup_button = $(".'.$label_for.'_pickup_button",ww)
    , img_fields = [image_view, delete_button, pickup_button]
    , form_elements = $("input, textarea",ww)
  
  ww.attr("id", ww.attr("id").replace(fid_re, fid_replace));
  if ( form_elements[0] ) {
   form_elements.each(function(){
    var fe = $(this)
      , is_checkbox = fe.attr("type") == "checkbox"
      , is_button = fe.attr("type") == "button"
    newid = fe.attr("id").replace(fid_re, fid_replace)
    fe.attr("id", newid)
    if (!is_checkbox && !is_button) fe.val("")
    var label = is_checkbox ? $(fe.parent("label").get(0)) : $("#"+fieldname+"_box label")
    if (is_checkbox) {
     var newname = fe.attr("name").replace(
      new RegExp(/(\x5b)(\d+)(\x5d)(\x5b\x5d)?$/), 
      function(){var a=arguments; a[2]=(parseNumber(a[2]) + 1); return a[1] + a[2] + a[3] + a[4]}
     )
     fe.attr("name", newname).attr("checked",false)
    }
    label.attr("for", newid)
   })
  }
  
  for ( i in img_fields ) {
   if (img_fields[i][0]) {
    img_fields[i].attr("class", img_fields[i].attr("class").replace(fid_re, fid_replace))
     .attr("id", img_fields[i].attr("id").replace(fid_re, fid_replace))
   }
  }
  if (image_view[0]) image_view.html("");
  if (delete_button[0]) delete_button.hide();
 
  if (!minus.length) {
   minus = $("<span '.make_html_attributes($this->_metabox_html_build_multiplier_attrs($label_for.'_1', '-'), "'").'>-</span>").appendTo(ww)
  }
  else {
   minus = minus.attr("id", minus.attr("id").replace(fid_re, fid_replace))
    .attr("class", minus.attr("class").replace(fid_re, fid_replace))
    .data("multiply_target", ww.attr("id").replace(/_form_field_wrap$/, ""));
  }
  minus.on("click", fn_confirm_delete_field_dialog);
  ww.hide().insertAfter(w).fadeIn(200, function(){$("input",this).focus()});
 ' . LF
  . ($v['type'] == 'text' ? 
     ($_use_pickers['datepicker'] ? 'var t=$("input",ww).removeClass("hasDatepicker").datepicker(datepicker_options).focus();'.LF:'')
   . ($_use_pickers['datetimepicker'] ? '$("input",ww).removeClass("hasDatepicker").datetimepicker(datetimepicker_options).focus();'.LF:'')
   . ($_use_pickers['timepicker'] ? '$("input",ww).removeClass("hasDatepicker").timepicker(timepicker_options).focus();'.LF:'')
   . ($_use_pickers['slider'] ? '_slider_options[_slider_options.length] = $.extend(slider_options, {value:'.(isset($v['value'])?$v['value']:0).'});
   $(".ui-slider",ww).empty().attr("id", newid+"_slider").slider(_slider_options[_slider_options.length-1]);'.LF:'')
   :'')
 .'});'.LF
   ;
   }
   
 /* //////      FIELDS LOOP      ////// */
   switch ($v['type']) {
   case ('') : break;
   case (NULL) : break;
   /* //////      HIDDEN      ////// */
   case ('hidden') : 	
    for ($i = 0; $i < $count; $i++) {
	 $h .= createHTMLElement('input', array('type'=>'hidden', 'value'=>(isset($values[$i]) ? $values[$i] : $v['default']), 'name'=>$field_name, 'id'=>$label_for.'_'.$i, 'class'=>(isset($v['class']) ? $v['class'] : '')));
	}
   break;
   /* //////      TEXT      ////// */  
   case ('text') : 
	if (isset($v['autocomplete_values']) && !empty($v['autocomplete_values'])) {
	 ////// Autocomplete //////
	 if ('_use_existing' === $v['autocomplete_values']) {
	  $limit = isset($v['autocomplete_limit']) ? $v['autocomplete_limit'] : -1;
	  $autocomplete_values = apply_filters('WPCF_Existing_MetaBox_Values', $k);
	  if (
	   isset($v['autocomplete_values_order'])
       && !empty($v['autocomplete_values_order'])
       && in_array($v['autocomplete_values_order'],  array('asort','arsort','krsort','ksort','natcasesort','natsort','rsort','shuffle','sort','uasort','uksort','usort') )
	  ) {
	   if (function_exists($v['autocomplete_values_order'])) {
	    $v['autocomplete_values_order']($autocomplete_values);
	   }
	   else {
	    natsort($autocomplete_values);
	   }
	  }
	 }
	 else {
	  $autocomplete_values = (array) $v['autocomplete_values'];
	 }
	 $autocomplete_script = sprintf(
	  '$(".'.$label_for.'").textfield_autocomplete([%s])',
	  implode(',', array_map(function($k) { return sprintf('"%s"', $k); }, $autocomplete_values))
	 )
	 ; //my_print_r($autocomplete_script);
	 $v['script'] .= $autocomplete_script ;
    }
    $picker_default_args = '{ampm:false,addSliderAccess:true,sliderAccessArgs:{touchonly:false},stepHour:1,stepMinute:1,hourGrid:3,minuteGrid:10,dateFormat:"yy/mm/dd",yearRange: "-100:+100",changeMonth: true,changeYear: true}';
    foreach (array_keys($_use_pickers) as $_p) {
     if ($_use_pickers[$_p]) {
      if ($_p == 'slider') {
       $v['script'] .= 'var _slider_options = [], slider_options = $.extend({min:0, max:10, step:0.5, value:5, slide:function(e,u){var id= $(this).attr("id").replace("_slider","");$("#"+id).val(u.value)}}, '
        . ( isset($v['slider_options']) ? $v['slider_options'] : '{}' ) . ');'
        . LF
       ;
      }
	  else if (in_array($_p, array('datepicker','timepicker','datetimepicker'))) {
       $v['script'] .= 'var '.$_p.'_options = parse_args('
        . $picker_default_args.', '
        . (isset($v[$_p.'_options']) && $v[$_p.'_options'] ? $v[$_p.'_options'] : '{}') // Options for datepicker/timepicker
        . ');'
        . LF
       ;
      }
	  else if ($_p == 'colorpicker') {
	  }
     }
    }
    for ($i = 0; $i < $count; $i++) {
     $script = '';
     $id = $label_for.'_'.$i;
     $h .= sprintf($multipliable_wrap_start, $id);
     $h .= createHTMLElement(
      'input',
      $wp_custom_functions->parse_args(
       array('type'=>'text', 'value'=>(isset($values[$i]) ? $values[$i] : $v['default']), 'name'=>$field_name, 'id'=>$id, 'size'=>32, 'class'=>array($label_for, $self_name.'_input_text', $class_name.'_input_text', $label_for.'_form_field_input_text', ($multipliable?'multipliable':''))),
       $v
      )
     );
     if ($i >= 1 && (bool) $multipliable) {
      $h .= createHTMLElement('span', $this->_metabox_html_build_multiplier_attrs($id, '-'), '-');
     }
     if ($_use_pickers['datepicker'])	 { $script .= '$("#'.$id.'").datepicker(datepicker_options);'. LF ; }		 /// datepicker
     if ($_use_pickers['datetimepicker']) { $script .= '$("#'.$id.'").datetimepicker(datetimepicker_options);'. LF; }/// datetimepicker
     if ($_use_pickers['timepicker'])	 { $script .= '$("#'.$id.'").timepicker(timepicker_options);'. LF; }		 /// timepicker
	 if ($_use_pickers['colorpicker'])	 { $script .= '$("#'.$id.'").colorpicker('.(isset($v['colorpicker_options']) ? $v['colorpicker_options'] : '{}').');'. LF; }		 /// colorpicker
     if ($_use_pickers['slider']) { /// slider
      $script .= '_slider_options['.$i.'] = slider_options;'
      . (isset($values[$i])? '_slider_options['.$i.']["value"] = parseFloat("'.$values[$i].'");' : '')
      . '$("<div id='.$id.'_slider />").appendTo($("#'.$id.'_form_field_wrap")).slider(_slider_options['.$i.']).val(_slider_options["value"]);'
      . LF ;
     }
     if ($_use_pickers['datepicker']||$_use_pickers['datetimepicker']||$_use_pickers['timepicker']) {
      $script .= '$("#ui-datepicker-div,#ui-timepicker-div").on("mouseover",function(){$(this).css("z-index",100)});' . LF;
     }
     $h .= $multipliable_wrap_end;
     $v['script'] .= $script . LF;
    }
    $h .= $multipliable;
   break;
   /* //////      SELECT      ////// */
   case 'select' : // my_print_r($values);
    for ($i = 0; $i < $count; $i++) {
	 $id = $label_for . '_' . $i;
     $h .= sprintf($multipliable_wrap_start, $id);
     $h .= html_select_element(array(
      'name'  => sprintf('%s[%d][]', $k, $i),
      'id'	  => $id,
      'class' => array($self_name.'_select', $self_name.'_select_label', $class_name.'_select', $class_name.'_select_label', ($multipliable?'multipliable':'')),
      'values'=> $v['values'],
      'labels'=> $v['value_label'],
	  'value' => (isset($values[$i][0]) ? $values[$i][0] : (isset($v['default']) ? $v['default'] : ''))
	 ) );
     $h .= $multipliable_wrap_end;
    }
    $h .= $multipliable;
   break;
   /* //////      CHECKBOX      ////// */
   case 'checkbox' : //my_print_r($values);
    if (empty($v['values']) || (isset($v['values'][0]) && empty($v['values'][0]))) {
     if ($v['value']) $v['values'] = $v['value'];
     else $v['values'] = $v['label']; // Fallback to "label"
    }
    if (!isset($v['defaults'])) $v['defaults'] = null;
    $v['values'] = (array) $v['values'];
    $v['defaults'] = (array) $v['defaults'];
    
    $value_groups = array();
    if (empty($values)) $value_groups[] = array();
    elseif ( isset($values[0]) && (is_string($values[0]) || is_numeric($values[0]) ) ) $value_groups[] = $values;
    else $value_groups = $values;
    
    foreach ($value_groups as $j => $group) {
     $group_id = $label_for.'_'.$j;
     $h .= sprintf($multipliable_wrap_start, $group_id);
     $h .= createHTMLElement('div', 'start', array('id'=> $self_name.'_'.$label_for.'_values_'.$j, 'class'=>array($self_name.'_values', $self_name.'_checkbox_values', $class_name.'_values', $class_name.'_checkbox_values')));
     for ($i = 0; $i < count($v['values']); $i++) {
      $id = $group_id . '_' . $i;
      $h .= createHTMLElement('label',
       array('class'=>array($self_name.'_input_checkbox', $self_name.'_input_checkbox_label', $class_name.'_input_checkbox', $class_name.'_input_checkbox_label'), 'for'=>$id),
       createHTMLElement(
        'input',
        array(
         'type'=>'checkbox',
     	'name'=>sprintf("%s[%d][]", $k, $j),
         'id'=>$id,
         'class' =>isset($v['class']) ? $v['class'] : '',
         'checked'=>(
          (count($group) == 0 && in_array($v['values'][$i], $v['defaults']))
          ||
          (count($group) > 0 && in_array($v['values'][$i], $group))
         ) ? 'checked' : '',
         'value' => $v['values'][$i]
        )
       )
	   . createHTMLElement('span', array('class'=>'label_text'), $this->get_value_label($v['values'],$v['value_label'],$i))
      );
     }
     $h .= createHTMLElement('div', 'end');
     if ($j >= 1 && (bool) $multipliable) {
      $h .= createHTMLElement('span', $this->_metabox_html_build_multiplier_attrs($group_id, '-'), '-');
     }
     $h .= $multipliable_wrap_end;
    }
    $h .= $multipliable;
   break;
   /* //////      RADIO      ////// */
   case 'radio' :
    $default_set = false;
    $h .= createHTMLElement('div', 'start', array('id'=> $self_name.'_'.$label_for.'_values', 'class'=>array($self_name.'_values ', $self_name.'_radio_values', $class_name.'_values ', $class_name.'_radio_values')));
    for ($i = 0; $i < count($v['values']); $i++) {
     $h .= createHTMLElement('label',
      array('class'=>array($self_name.'_input_radio', $self_name.'_input_radio_label', $class_name.'_input_radio', $class_name.'_input_radio_label')),
      createHTMLElement(
       'input',
       array(
        'type'=>'radio',
        'name'=>$k,
        'id'=>$label_for.'_'.$i,
        'checked'=>(
         (count($values) == 0 && isset($v['default']) && $v['default'] === $v['values'][$i] && !$default_set)
     	||
         (count($values) > 0 && in_array($v['values'][$i], $values))
        ) ? $default_set='checked' : '',
        'value' => $v['values'][$i]
       )
      ) . createHTMLElement('span', array('class'=>'label_text'), $this->get_value_label($v['values'],$v['value_label'],$i) )
     );
    }
    $h .= createHTMLElement('div', 'end');
   break
   ;
 
   /* //////      TEXTAREA      ////// */
   case 'textarea' : 
    for ($i = 0; $i < $count; $i++) {
     $id = $label_for.'_'.$i;
     $a = array_merge(
      $wp_custom_functions->parse_args(array('rows'=>2, 'cols'=>47), $v),
      array('id'=>$id, 'name'=>$k.'[]', 'class'=>
       array_merge(
        array($label_for, $self_name.'_textarea', $class_name.'_textarea'),
        ($multipliable? array($self_name.'_textarea_multipliable', $class_name.'_textarea_multipliable', 'multipliable') : array())
       )
      )
     );
     $h .= sprintf($multipliable_wrap_start, $id);
	 if (isset($v['tinymce']) && !empty($v['tinymce'])) {
	  $settings = $wp_custom_functions->parse_args(
	   array(
        'wpautop' => FALSE,
        'media_buttons' => TRUE,
		'textarea_name' => $k.'[]',
		'textarea_rows' => $a['rows'],
        'tabindex' => FALSE,
        'editor_css' => NULL,
		'editor_class' => implode(' ', $a['class']),
        'editor_height' => NULL,
        'teeny' => FALSE,
        'dfw' => FALSE,
        'tinymce' => TRUE,
        'quicktags' => TRUE,
        'drag_drop_upload' => TRUE,
	   ),
	   isset($v['wp_editor_args'])? $v['wp_editor_args'] : NULL
	  );
	  ob_start(); wp_editor((isset($values[$i])?$values[$i]:''), $a['id'], $settings); $editor = ob_get_clean();
	  $h .= $editor;
	 }
	 else {
      $h .= createHTMLElement('textarea', $a, empty($values[$i])?'':$values[$i]);
	 }
     if ($i >= 1 && (bool) $multipliable) {
      $h .= createHTMLElement('span', $this->_metabox_html_build_multiplier_attrs($id, '-'), '-');
     }
     $h .= $multipliable_wrap_end;
    }
    $h .= $multipliable;
   break;
   /* //////      IMAGE      ////// */
   case 'image' :
    $form_params = $wp_custom_functions->parse_args(array(
     'button_pickup_name' => __('Select/Upload Image', $custom_language_domain),
     'button_delete_name' => __('Clear Image', $custom_language_domain),
    ), $v);
    $image_size_id_suffix = $this->image_form_params['image_size_id_suffix'];
    $delete_image_dialog = $wp_custom_functions->parse_args( array(
     'message' => __('Are you sure you want to delete the image?', $custom_language_domain),
     'title' => __('Delete Image Confirmation', $custom_language_domain),
     'confirm_button' => __('Clear Image', $custom_language_domain),
     'cancel_button' => __('Cancel', $custom_language_domain),
    ), ((isset($v['confirm_delete_dialog']) && is_array($v['confirm_delete_dialog'])) ? $v['confirm_delete_dialog'] : array()) ) ;
 
    $h .= createHTMLElement(
      'p',
      array('class'=>'dialog_confirm_delete', 'id'=>'dialog_confirm_delete_'.$label_for),
      $delete_image_dialog['message']
    );
 /*
    $image_sizes = get_post_meta($post->ID, $k.$this->image_form_params['image_size_suffix'], FALSE);
    while (isset($image_sizes[0]) && is_array($image_sizes[0])) $image_sizes = $image_sizes[0];
 */
    $image_sizes = apply_filters('WPCF_Get_Post_Meta', $post->ID, $k.$this->image_form_params['image_size_suffix'], FALSE);
 
    for ($i = 0; $i < $count; $i++) {
	 $image_id = isset($values[$i]) ? $values[$i] : NULL;
	 $image_size = isset($image_sizes[$i]) ? $image_sizes[$i] : NULL;
     $id_suffix			 =  '_' . $i;
     $id					 = $label_for . $id_suffix;
     $button_id			 = $label_for.$this->image_form_params['button_suffix'] . $id_suffix;
     $image_view_id		 = $label_for.$this->image_form_params['image_view_box_suffix'] . $id_suffix;
	 $image				 = attachment_image_html($image_id, array('size'=>get_custom_image_size($image_size), 'width'=>'100%', 'height'=>''));
     $image_full			 = wp_get_attachment_image_src($image_id, 'full');
     $button_delete_id	 = $button_id.$this->image_form_params['button_delete_suffix'] . $id_suffix;
 
     $h .= sprintf($multipliable_wrap_start, $id);
     $h .= createHTMLElement('div', array('id'=>$image_view_id, 'class'=>array(
      $self_name.'_image_view', $label_for.'_image_view', $id.'_image_view', $class_name.'_image_view'
     ) ), ( $image ? $image : '' ) );
     $h .= createHTMLElement(
      'input',
       array('type'=>'hidden', 'value'=>$image_id, 'name'=>$k.'[]', 'id'=>$id, 'class'=>array(
        $label_for, $label_for.'_image_id', $id.'_image_id', $self_name.'_image_id', $class_name.'_image_id'
       ) )
     )
     . createHTMLElement(
      'input',
       array('type'=>'hidden', 'value'=>$image_size, 'name'=>$k.$this->image_form_params['image_size_suffix'].'[]', 'id'=>$label_for.$image_size_id_suffix.$id_suffix, 'class'=>array(
        $label_for.$image_size_id_suffix, $label_for.$image_size_id_suffix.$id_suffix, $self_name.$image_size_id_suffix, $class_name.$image_size_id_suffix
       ) )
     )
     . createHTMLElement(
      'input',
       array('type'=>'button', 'value'=>$form_params['button_pickup_name'], 'name'=>$button_id, 'id'=>$button_id, 'class'=>array(
        'pickup_button', $label_for.'_pickup_button', $id.'_pickup_button', $self_name.'_pickup_button', $class_name.'_pickup_button'
       ) )
     ) .
     createHTMLElement(
      'input',
       array('type'=>'button', 'value'=>$form_params['button_delete_name'], 'id'=>$button_delete_id, 'class'=>array(
        'delete_button', $label_for.'_delete_button', $id.'_delete_button', $self_name.'_delete_button', $class_name.'_delete_button'
       ))
     )
     ;
     if ($i >= 1 && (bool) $multipliable) {
      $h .= createHTMLElement('span', $this->_metabox_html_build_multiplier_attrs($id, '-'), '-');
     }
     $h .= $multipliable_wrap_end;
    } // end for
    $h .= $multipliable;
    $v['script'] .= '
 $("#dialog_confirm_delete_'.$label_for.'").dialog({
  autoOpen:false, closeOnEscape:true, modal:true,
  title:"'.str_replace('"','\"',$delete_image_dialog['title']).'",
  buttons:{
   "'.str_replace('"','\"',$delete_image_dialog['confirm_button']).'":function(){
    var wrap = $("#"+$["image_field_wrapper"]);
    $("img", wrap).fadeOut({complete:function(){$(".'.$label_for.'_image_view", wrap).html("");}});
    $(".'.$label_for.'", wrap).val("");
    $(".'.$label_for.$image_size_id_suffix.'", wrap).val("");
    $(".'.$label_for.'_delete_button", wrap).hide()
    $(this).dialog("close");
   },
   "'.str_replace('"','\"',$delete_image_dialog['cancel_button']).'":function(){$(this).dialog("close")}
  }
 });
 var delete_button = $(".'.$label_for.'_delete_button", $(".'.$label_for.'_form_field_wrap")).on("click", function(){
  var wrap = $($(this).parents(".'.$label_for.'_form_field_wrap").get(0)), e = $(".'.$label_for.'", wrap)
  if ($["image_field_wrapper"] === undefined) $.extend({ "image_field_wrapper": wrap.attr("id") });
  else $["image_field_wrapper"] =  wrap.attr("id")
  if(e.val()) { $("#dialog_confirm_delete_'.$label_for.'").dialog("open"); $(".ui-front").css("z-index",300010); }
  else return false;
  if (!e.val()) { $(this).hide() }
 });
 delete_button.each(function(){
  var wrap = $($(this).parents(".'.$label_for.'_form_field_wrap").get(0)), e = $(".'.$label_for.'", wrap)
  if (!e.val()) $(this).hide();
 }); ' . LF;
    $v['script'].='
 $(".'.$label_for.'_pickup_button", $(".'.$label_for.'_form_field_wrap")).on("click",function() {
  var mediabox;
  var wrap = $($(this).parents(".'.$label_for.'_form_field_wrap").get(0));
  if ($["image_field_wrapper"] === undefined) $.extend({ "image_field_wrapper": wrap.attr("id") })
  else $["image_field_wrapper"] =  wrap.attr("id")
  if (mediabox) { mediabox.open(); return; }
  mediabox = wp.media({ "state" : "'.$label_for.'_Media_Picker" });
  mediabox.states.add([
   new wp.media.controller.Library({
    id		 : "'.$label_for.'_Media_Picker",
    title	 : "'.$v['label'].' : '.$form_params['button_pickup_name'].'",
    filterable: "uploaded",
 // library	 : wp.media.query( mediabox.options.library ),
    ' . (empty($v['filetypes']) ? '' : 'library: "' . esc_attr( implode( ",", $v['filetypes'] )).'",').'
    multiple	 : mediabox.options.multiple ? "reset" : false,
    editable	 :   true,
    displayUserSettings	: false,
    contentUserSetting	: false,
    displaySettings		: true,
    allowLocalEdits		: true
   })
  ]);
  mediabox.on("select", function(){
   var m = mediabox.el,
    sidebar = $(".media-sidebar", m),
    wrap = $("#"+$["image_field_wrapper"]),
    id = $(".edit-attachment", sidebar).attr("href").match(/\x3f.*?post=(\d+)&?.*?$/)[1],
    image_view = $(".'.$label_for.'_image_view", wrap),
    image_size_field = $(".'.$label_for.$image_size_id_suffix.'", wrap),
    image_size = $(".attachment-display-settings select[name=size]", sidebar).val()
   ;
   $.ajax({
    type: "POST",
    url: "'.admin_url( 'admin-ajax.php' ).'",
    data: { "action" : "get_thumbnail", "attachment_id" : id },
    success: function(data){
     $(".'.$label_for.'", wrap).val(id);
     image_view.html(data); $(".'.$label_for.'_delete_button").show(); $("a", image_view).fancybox(); image_size_field.val(image_size);
    },
    error: function(){ image_view.html("'.__('Error occured while requesting image by ajax.', $this->language_domain()).'"); }
   });
  });
  mediabox.open();
 } );' . LF
   ;
   break; /// IMAGE
   
   /* //////      MEDIA BUTTON (UPLOAD ONLY)      ////// */
   case 'media_button' : 
	$h .= createHTMLElement('div', array('id'=>$label_for.'_media_button_container'),
	 createHTMLElement('a', array('id'=>$label_for.'_media_button'), __('Add Media', $custom_language_domain))
	);
	$v['script'] .= apply_filters('Wrap_JavaScript', '
var button = $("#'.$label_for.'_media_button").button();

button.on("click", function(){
 var mediabox;
 if (mediabox) { mediabox.open(); return; }
 mediabox = wp.media({
  "state" : "'.$label_for.'_Media_Picker",
   button	 : { text : "'. __('Finish Upload', $custom_language_domain).'" },
 });
 mediabox.states.add([
  new wp.media.controller.Library({
   id		 : "'.$label_for.'_Media_Picker",
   title	 : "'.$v['label'].'",
   filterable: "uploaded",
   ' . (empty($v['filetypes']) ? '' : 'library: "' . esc_attr( implode( ",", $v['filetypes'] )).'",').'
   multiple	 : mediabox.options.multiple ? "reset" : false,
   editable	 :   true,
   displayUserSettings	: false,
   contentUserSetting	: false,
   displaySettings		: true,
   allowLocalEdits		: true
  })
 ]);
 mediabox.open()
})
' . LF, array('jquery'=>TRUE))
   ;
   break; /// MEDIA BUTTON
   /* //////      POSTS      ////// */

   case 'posts' :
    $posts = get_posts((array) array_value($v, 'query'));
	$form_type = (array_value($v, 'form_type') == 'checkbox' ? 'checkbox' : 'radio'); 
    $v['values'] = (array) array_value($v,'values');
    $v['defaults'] = (array) array_value($v,'defaults');
	$h .= createHTMLElement('div', 'start', array('id'=> $self_name.'_'.$label_for.'_values', 'class'=>array($self_name.'_values ', $self_name.'_'.$form_type.'_values', $class_name.'_values ', $class_name.'_'.$form_type.'_values')));
	for ($i = 0; $i < count($posts); $i++) {
	 $id = $posts[$i]->ID ;
     $h .= createHTMLElement('label',
      array('class'=>array($self_name.'_input_'.$form_type, $self_name.'_input_'.$form_type.'_label', $class_name.'_input_'.$form_type, $class_name.'_input_radio_label')),
      createHTMLElement(
       'input',
       array(
        'type'=>$form_type,
		'name'=>$k.($form_type=='checkbox'?'[]':''),
        'id'=>$label_for.'_'.$i,
        'checked'=>(
		 (is_edit_page('new')  && in_array($id, $v['defaults']))
     	||
		 (is_edit_page('edit') && count($values) > 0 && in_array($id, $values))
        ) ? 'checked' : '',
        'value' => $id
       )
	  )
      .
      createHTMLElement('span',
	   array('class'=>'label_text'),
	   apply_filters('the_title',$posts[$i]->post_title). apply_filters('CF_HTML', 'span', array('class'=>$self_name.'_'.$label_for.'_values_label_post_id'), '(ID:'.$id.')') 
	  )
     );
	}
    $h .= createHTMLElement('div', 'end');
   break
   ;
   } // end switch
 
   if (isset($v['default_title_value']) && $v['default_title_value']) {
    if (!is_array($v['default_title_value']))  $v['default_title_value'] = array('truncate'=>false);
	$truncate = isset($v['default_title_value']['truncate']) && $v['default_title_value']['truncate'];
	$truncate_length = $truncate ? $v['default_title_value']['truncate'] : NULL ;
    $v['script'] .='
//var field_values_orig
if (undefined === window.field_values_orig) { window.field_values_orig = new Object }
field_values_orig["'.$label_for.'"] = $("#'.$label_for.'_0").val()

$("#title")
 .css({
  "background-color": "#f1f1f1",
  "border": "none",
  "box-shadow": "none",
  "color" : "black"
 })
 .bind( "click", function(){ $("#'.$label_for.'_0").trigger("focus") } )

 var field = $("#'.$label_for.'_0")
   , title_value = $("#title").val()
   , title_value_orig = window.POST_TITLE_ORIGINAL
   , field_value_orig = window.field_values_orig["'.$label_for.'"]
 if ( (field_value_orig == title_value_orig) ) {
  field.on("keyup", function() {
   var v = $(this).val()
   $("#post_name, #title").val(v)
  })
 }


//$("#post").on("submit",function(){ return true });
' . LF;
   }
   
   if (isset($v['script']) && $v['script']) {
	$box_script .= LF . $v['script'] . LF;
   }
   if ($v['type'] != 'hidden') $h .= createHTMLElement('div','end');
  } // End of LOOP
 } // End if $meta_args['fields']
 
 $box_script = preg_replace('/[\x0a]+/',LF,$box_script);
 $box_script = ($box_script = trim($box_script)) ?
  wrapJavaScript(
   $box_script, array('jquery'=>TRUE, 'jqueryready'=>(isset($meta_args['domready'])&&$meta_args['domready'] === FALSE ? FALSE: TRUE))
  )
  : 
  $box_script
 ;
 $h = apply_filters('WPCF_Meta_Box_HTML', $meta_args['prepend_html'] . $h . $box_script . $meta_args['append_html']) ;
 echo $h;
 return $this;
}


function ajax_get_thumbnail() {
 $img_html = attachment_image_html($_POST['attachment_id']);
 die($img_html);
}
function ajax_get_image_size() {
 extract($_POST); die("get_image_sizes");
 $sizes_html = createHTMLElement('select', 'start',
  array('name'=>'size', 'id'=>$label_for.$image_size_suffix.'_select')
 );
  foreach (apply_filters('image_size_names_choose', array()) as $s) {
   if (attachment_image_html($id, $s)) {
    $d = get_custom_image_dimensions($s);
    $sizes_html .= createHTMLElement('option', array('value'=>$s), sprintf("%s (%s x %s)", $s, $d[0], $d[1]));
   }
  }
  $sizes_html .= createHTMLElement('select', 'end');
}

function language_domain() {
 global $custom_language_domain; return $custom_language_domain;
}

function update($post_id = null) {
 global $post;
 !$post_id && $post_id = $post->ID;
 $meta_fields = $this->param('fields');
 
 $n = $this->param('nonce');
 if ($n && isset($_POST[$n['name']]) && !wp_verify_nonce($_POST[$n['name']], $n['action'])) return $post_id;
 if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
 if (isset($_POST['action']) && $_POST['action'] == 'inline-save') {
  // クイックポストの時は何もしない
  return $post_id;
 }

 $pt = $this->param('post_type');
 if (isset($_POST['post_type']) && in_array( $_POST['post_type'], $this->param('post_type') ) ) {
  if (!current_user_can('edit_post', $post_id)) return $post_id;
 }
 else return $post_id;
 $tmpfile = '/virtual/emy/tmp/php_test.tmp';
   file_put_contents($tmpfile, $this->name()."\n", FILE_APPEND);
 foreach (array_keys($meta_fields) as $k) {
  $v = $_POST[$k];
  $existing_values = get_post_meta($post_id, $k);
  if (
   ($existing_values == $v)
   ||
   ($v == array('') && $existing_values == array())
  ) { 
  if ($this->name() == 'coupons') {
//   file_put_contents($tmpfile, 'updating meta skipped: '.serialize($meta_fields), FILE_APPEND);
  }
   // If the value was not changed or empty value was posted, nothing is done.
   continue ;
  }
  if ($v == array('')) { $v = ''; }  // Saving Database Size, Enhance Visibility
  if ($meta_fields[$k]['type'] == 'checkbox') {
   // Each checkbox item is stored in single db record. This could be a bad old habit. Should be in serialized array and compiled in one record...
   if (count($existing_values) > 1) delete_post_meta($post_id, $k);
  }
  if (in_array($meta_fields[$k]['type'], qw('checkbox text textarea select radio textfield hidden posts'))) {
  if ($this->name() == 'coupons') {
//   file_put_contents($tmpfile, 'updating meta', FILE_APPEND);
  }
   update_post_meta($post_id, $k, $v);
  }
  else if (in_array($meta_fields[$k]['type'], qw('image'))) {
   $image_size_suffix = $this->image_form_params['image_size_suffix'];
   update_post_meta($post_id, $k, $v);
   update_post_meta($post_id, $k.$image_size_suffix, $_POST[$k.$image_size_suffix]);
  }
 }
 
 return $this;
}


function get_value_label($values, $labels, $index) {
 if (!isset($values) || !isset($index)) return;
 return
  isset($labels) ? (
   isset($labels[$values[$index]]) ? $labels[$values[$index]]
   :
   ( (isset($labels[$index]) && $labels[$index]) ? $labels[$index] : $values[$index] )
  )
  :
  $values[$index]
 ;
}
function get_value_label_array($field) {
 $l = array();
 if ($field) {
  $f = &$this->get_field($field);
  if (isset($f['value_label'])) {
   foreach ($f['values'] as $i=>$v) { $l[$v] = (isset($f['value_label'][$i]))? $f['value_label'][$i] : $v; }
   return $l;
  }
  return;
 }
 else {
 }
}
function &get_all_labels_and_values() {
 $a = array();
 foreach($this->get_field_names() as $k) {
  if ($this->get_value_label_array($k)) $a[$k] = $this->get_value_label_array($k);
 }
 return $a;
}
function get_keys($key = null) {
 return $this->get_field_names();
}
function &get_field_label($key) {
 $args = $this->arguments();
 $l = isset($args['fields'][$key]) && isset($args['fields'][$key]['label']) ? $args['fields'][$key]['label'] : '';
 return $l;
}
function &get_field($name) {
 $f = $this->param('fields');
 $n = NULL;
 if (isset($f[$name])) {
  $n = $f[$name];
  return $n;
 }
 return $n;
}
function get_field_names() {
 $args = $this->arguments();
 return array_keys($args['fields']);
}
function get_field_labels() {
 $a = array();
 foreach ($this->get_keys() as $f) {
  $a[$f] = $this->get_field_label($f);
 }
 return $a;
}

function name() { return $this->param('name'); }
function label() { return $this->title(); }
function title() { return $this->param('title'); }
// DEBUG 
function _test($d = null, $a = array()) {
 global $_test;
 if (isset($d)) {
  if ($a['write_to_file']) {
   $testfile = TEMPLATEPATH . '/_test';
   if (is_array($d)) foreach ($d as $k => $v ) file_put_contents($testfile, $k.'=>'.$v.','."\n", FILE_APPEND);
   else file_put_contents($testfile, $d."\n", FILE_APPEND);
  }
  else { $_test = $d; }
 }
}
function _test2($data = null) {
// if (!current_user_can('edit_posts')) return;
 $d = $_POST;
// $d['nonce_value'] = $_POST[$this->atts['nonce']['name']];
 $testfile = TEMPLATEPATH . '/_test';
 foreach ($d as $k => $v ) file_put_contents($testfile, $k.'=>'.$v.','."\n", FILE_APPEND);
 $ks = array($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING'], $_SERVER['HTTP_REFERRER']);
// $ks = (array) $_POST['post_type'];
// file_put_contents($testfile, implode(", ",$ks)."\n", FILE_APPEND); 
// file_put_contents($testfile, " by ".$self_name."\n\n", FILE_APPEND);
 if ($data) file_put_contents($testfile, $data."\n", FILE_APPEND); 
}

/*/// CLASS PROPERTIES ///*/

private $accepted_param_keys = array(
 'post_type',
 'nonce',
 'name',
 'fields',
 'title',
 'context',
 'priority',
 'init_hook',
 'multiply_group',
 'script',
 'row_group',
 'append_html',
 'prepend_html',
 'type',
);
var $image_params = array(); // ?
public $preset_select_options = array(
'zodiac'=>array('Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'),
'zodiac_uc'=>array('ARIES','TAURUS','GEMINI','CANCER','LEO','VIRGO','LIBRA','SCORPIO','SAGITTARIUS','CAPRICORN','AQUARIUS','PISCES'),
'blood_type_abo'=>array('A','B','O','AB'),
'blood_type_abo_group'=>array('Group A','Group B','Group O','Group AB'),
'blood_type_rh'=>array('Rh+','Rh-'),
'monthnames'=>array('January','February','March','April','May','June','July','August','September','October','November','December'),
'monthnames_uc'=>array('JANUARY','FEBRUARY','MARCH','APRIL','MAY','JUNE','JULY','AUGUST','SEPTEMBER','OCTOBER','NOVEMBER','DECEMBER'),
'monthnames_shortened'=>array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
'monthnames_shortened_uc'=>array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'),
'daynames'=>array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
'daynames_shortened'=>array('Sun','Mon','Tue','Wed','Thu','Fri','Sat'),
'daynames_uc'=>array('SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY'),
'daynames_shortened'=>array('SUN','MON','TUE','WED','THU','FRI','SAT'),

'zodiac_ja'=>array('牡羊座','牡牛座','双子座','蟹座','獅子座','乙女座','天秤座','蠍座','射手座','山羊座','水瓶座','魚座'),
 'zodiac_ja_kana'=>array('おひつじ座','おうし座','ふたご座','かに座','しし座','おとめ座','てんびん座','さそり座','いて座','やぎ座','みずがめ座','うお座'),
 'zodiac_cn'=>array('白羊宮','金牛宮','双児宮','巨蟹宮','獅子宮','処女宮','天秤宮','天蝎宮','人馬宮','磨羯宮','宝瓶宮','双魚宮'),
 'daynames_ja'=>array('日曜日','月曜日','火曜日','水曜日','木曜日','金曜日','土曜日'),
 'daynames_ja_shortened'=>array('日','月','火','水','木','金','土'),

);
} // end of CLASS

if (0) { // for POEdit;
array(
'zodiac'=>array(__('Aries'),__('Taurus'),__('Gemini'),__('Cancer'),__('Leo'),__('Virgo'),__('Libra'),__('Scorpio'),__('Sagittarius'),__('Capricorn'),__('Aquarius'),__('Pisces')),
'zodiac_uc'=>array(__('ARIES'),__('TAURUS'),__('GEMINI'),__('CANCER'),__('LEO'),__('VIRGO'),__('LIBRA'),__('SCORPIO'),__('SAGITTARIUS'),__('CAPRICORN'),__('AQUARIUS'),__('PISCES')),
'blood_type_abo'=>array(__('A'),__('B'),__('O'),__('AB')),
'blood_type_abo_group'=>array(__('Group A'),__('Group B'),__('Group O'),__('Group AB')),
'blood_type_rh'=>array(__('Rh+'),__('Rh-')),
'monthnames'=>array(__('January'),__('February'),__('March'),__('April'),__('May'),__('June'),__('July'),__('August'),__('September'),__('October'),__('November'),__('December')),
'monthnames_uc'=>array(__('JANUARY'),__('FEBRUARY'),__('MARCH'),__('APRIL'),__('MAY'),__('JUNE'),__('JULY'),__('AUGUST'),__('SEPTEMBER'),__('OCTOBER'),__('NOVEMBER'),__('DECEMBER')),
'monthnames_shortened'=>array(__('Jan'),__('Feb'),__('Mar'),__('Apr'),__('May'),__('Jun'),__('Jul'),__('Aug'),__('Sep'),__('Oct'),__('Nov'),__('Dec')),
'monthnames_shortened_uc'=>array(__('JAN'),__('FEB'),__('MAR'),__('APR'),__('MAY'),__('JUN'),__('JUL'),__('AUG'),__('SEP'),__('OCT'),__('NOV'),__('DEC')),
'daynames'=>array(__('Sunday'),__('Monday'),__('Tuesday'),__('Wednesday'),__('Thursday'),__('Friday'),__('Saturday')),
'daynames_shortened'=>array(__('Sun'),__('Mon'),__('Tue'),__('Wed'),__('Thu'),__('Fri'),__('Sat')),
'daynames_uc'=>array(__('SUNDAY'),__('MONDAY'),__('TUESDAY'),__('WEDNESDAY'),__('THURSDAY'),__('FRIDAY'),__('SATURDAY')),
'daynames_shortened'=>array(__('SUN'),__('MON'),__('TUE'),__('WED'),__('THU'),__('FRI'),__('SAT')),
);
}
/*
'zodiac'=>array(__('Aries',$custom_language_domain),__('Taurus',$custom_language_domain),__('Gemini',$custom_language_domain),__('Cancer',$custom_language_domain),__('Leo',$custom_language_domain),__('Virgo',$custom_language_domain),__('Libra',$custom_language_domain),__('Scorpio',$custom_language_domain),__('Sagittarius',$custom_language_domain),__('Capricorn',$custom_language_domain),__('Aquarius',$custom_language_domain),__('Pisces',$custom_language_domain)),
'zodiac_uc'=>array(__('ARIES',$custom_language_domain),__('TAURUS',$custom_language_domain),__('GEMINI',$custom_language_domain),__('CANCER',$custom_language_domain),__('LEO',$custom_language_domain),__('VIRGO',$custom_language_domain),__('LIBRA',$custom_language_domain),__('SCORPIO',$custom_language_domain),__('SAGITTARIUS',$custom_language_domain),__('CAPRICORN',$custom_language_domain),__('AQUARIUS',$custom_language_domain),__('PISCES',$custom_language_domain)),
'blood_type_abo'=>array(__('A',$custom_language_domain),__('B',$custom_language_domain),__('O',$custom_language_domain),__('AB',$custom_language_domain)),
'blood_type_abo_group'=>array(__('Group A',$custom_language_domain),__('Group B',$custom_language_domain),__('Group O',$custom_language_domain),__('Group AB',$custom_language_domain)),
'blood_type_rh'=>array(__('Rh+',$custom_language_domain),__('Rh-',$custom_language_domain)),
'monthnames'=>array(__('January',$custom_language_domain),__('February',$custom_language_domain),__('March',$custom_language_domain),__('April',$custom_language_domain),__('May',$custom_language_domain),__('June',$custom_language_domain),__('July',$custom_language_domain),__('August',$custom_language_domain),__('September',$custom_language_domain),__('October',$custom_language_domain),__('November',$custom_language_domain),__('December',$custom_language_domain)),
'monthnames_uc'=>array(__('JANUARY',$custom_language_domain),__('FEBRUARY',$custom_language_domain),__('MARCH',$custom_language_domain),__('APRIL',$custom_language_domain),__('MAY',$custom_language_domain),__('JUNE',$custom_language_domain),__('JULY',$custom_language_domain),__('AUGUST',$custom_language_domain),__('SEPTEMBER',$custom_language_domain),__('OCTOBER',$custom_language_domain),__('NOVEMBER',$custom_language_domain),__('DECEMBER',$custom_language_domain)),
'monthnames_shortened'=>array(__('Jan',$custom_language_domain),__('Feb',$custom_language_domain),__('Mar',$custom_language_domain),__('Apr',$custom_language_domain),__('May',$custom_language_domain),__('Jun',$custom_language_domain),__('Jul',$custom_language_domain),__('Aug',$custom_language_domain),__('Sep',$custom_language_domain),__('Oct',$custom_language_domain),__('Nov',$custom_language_domain),__('Dec',$custom_language_domain)),
'monthnames_shortened_uc'=>array(__('JAN',$custom_language_domain),__('FEB',$custom_language_domain),__('MAR',$custom_language_domain),__('APR',$custom_language_domain),__('MAY',$custom_language_domain),__('JUN',$custom_language_domain),__('JUL',$custom_language_domain),__('AUG',$custom_language_domain),__('SEP',$custom_language_domain),__('OCT',$custom_language_domain),__('NOV',$custom_language_domain),__('DEC',$custom_language_domain)),
'daynames'=>array(__('Sunday',$custom_language_domain),__('Monday',$custom_language_domain),__('Tuesday',$custom_language_domain),__('Wednesday',$custom_language_domain),__('Thursday',$custom_language_domain),__('Friday',$custom_language_domain),__('Saturday',$custom_language_domain)),
'daynames_shortened'=>array(__('Sun',$custom_language_domain),__('Mon',$custom_language_domain),__('Tue',$custom_language_domain),__('Wed',$custom_language_domain),__('Thu',$custom_language_domain),__('Fri',$custom_language_domain),__('Sat',$custom_language_domain)),
'daynames_uc'=>array(__('SUNDAY',$custom_language_domain),__('MONDAY',$custom_language_domain),__('TUESDAY',$custom_language_domain),__('WEDNESDAY',$custom_language_domain),__('THURSDAY',$custom_language_domain),__('FRIDAY',$custom_language_domain),__('SATURDAY',$custom_language_domain)),
'daynames_shortened'=>array(__('SUN',$custom_language_domain),__('MON',$custom_language_domain),__('TUE',$custom_language_domain),__('WED',$custom_language_domain),__('THU',$custom_language_domain),__('FRI',$custom_language_domain),__('SAT',$custom_language_domain)),
*/