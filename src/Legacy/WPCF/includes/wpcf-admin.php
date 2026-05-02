<?php
/* ////// Admin Page ////// */


function is_admin_page() {
 return is_admin();
}

function activate_update_services_for_multisite() { // http://wordpress.org/extend/plugins/activate-update-services/
 if (MULTISITE) {
  remove_filter('enable_update_services_configuration', '__return_false');
  add_filter('whitelist_options', function($input) { $input['writing'][] = 'ping_sites'; return $input; }, 99);
 }
}


function is_edit_post($id_or_slug = NULL) {
 $p = NULL
 ;
 if ($id_or_slug){
  if (is_numeric($id_or_slug)) { $p = get_post($id_or_slug); }
  else { $p = get_post(get_id_by_slug($id_or_slug)); }
  if ($p) {
   return ( (isset($_GET['post']) && $p->ID == $_GET['post']) || (isset($_POST['post_ID']) && $p->ID == $_POST['post_ID']) );
  }
  return false;
 }
 else {
  return is_edit_page();
 }
}


function is_edit_page($new_or_edit = NULL){
 global $pagenow;
 //make sure we are on the backend
 if (!is_admin()) return false;
 
 if($new_or_edit == "edit")
  return in_array( $pagenow, array( 'post.php',  ) );
 elseif($new_or_edit == "new") //check for new post page
  return in_array( $pagenow, array( 'post-new.php' ) );
 else //check for either new or edit
  return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
}



/*// filtering gettext/ngettext text  //*/
function custom_admin_menu_text( $translated ) {
 $translated = str_ireplace('ダッシュボード', '管理画面TOP', $translated);
 $translated = str_ireplace('投稿', '記事の管理', $translated);
 $translated = str_ireplace('リンク', 'お気に入り', $translated);
 $translated = str_ireplace('メディア', '画像・ファイル', $translated);
 $translated = str_ireplace('固定ページ', 'ページ', $translated);
 $translated = str_ireplace('外観', 'デザイン管理', $translated);
 $translated = str_ireplace('ユーザー', 'プロフィール設定', $translated);
 $translated = str_ireplace('設定', '各種設定', $translated);
 return $translated;
}
//add_filter('gettext',  'custom_admin_menu_text');
//add_filter('ngettext',  'custom_admin_menu_text');

//get_role('author')->add_cap('manage_categories');



/* ////// Editor ////// */
function extend_valid_html5($init) {
 // Standard attributes
 $atts = 'role|accesskey|class|contenteditable|contextmenu|dir|draggable|hidden|id|item|itemprop|lang|spellcheck|style|subject|tabindex|title';
 $html5Elements = array(
  'article[#|cite|pubdate]',
  'aside[#]',
  'audio[#]',
  'canvas[#]',
  'command[#]',
  'datalist[#]',
  'details[#]',
  'figure[#]',
  'figcaption[#]',
  'footer[#]',
  'header[#]',
  'hgroup[#]',
  'mark[#]',
  'meter[#]',
  'nav[#]',
  'output[#]',
  'progress[#]',
  'section[#]',
  'summary[#]',
  'time[#|datetime]',
  'video[#]'
 );
 if(!isset($init['extended_valid_elements'])) {
  $init['extended_valid_elements'] = '';
 }
 $init['extended_valid_elements'] .= str_replace('#',$atts,implode(',',$html5Elements));
 return $init;
}

function tiny_mce_custom_block_elements($a) {
// $a['theme_advanced_blockformats'] = 'p,div,pre,code,h1,h2,h3,h4,h5,h6';
 $style_formats = array(
     array(
         'title' => 'DIV',
         'block' => 'div',
         'classes' => '',
     ),
     array(
         'title' => 'CODE',
         'classes' => '',
     ),
 );
 $a['style_formats'] = json_encode($style_formats);
 return $a;
}

function TinyMceInitCustom( $initArray ) {
 return array_merge(
  $initArray,
  array(
   'force_p_newlines'	 => FALSE,
   'force_br_newlines'	 => FALSE,
   'forced_root_block'	 => "",
   'remove_linebreaks'	 => FALSE,
   'remove_redundant_brs'	 => FALSE,
   'convert_newlines_to_brs' => FALSE
  )
 );
}

/* //
 メディアを挿入の初期表示を「この投稿へのアップロード」にする
// */
function set_media_uploader_default_selection($echo = false) {
 $script = '
(function(media) {
if ( media ) {
 media.view.MediaFrame.Select.prototype.initialize = function() {
  media.view.MediaFrame.prototype.initialize.apply( this, arguments )
  // Fix for WooCommerce Product Gallery
  this.states.forEach(function( state ) {
   var library = state.get( "library" )
   if ( library ) {
    library.props.set( "3To", media.view.settings.post.id )
    library.props.set( "orderby", "menuOrder" )
    library.props.set( "order", "ASC" )
   }
  })
  _.defaults( this.options, {
   "selection": [],
   "library"  : { 
    "uploadedTo": media.view.settings.post.id, 
    "orderby"   : "menuOrder", 
    "order"     : "ASC" 
   },
   "multiple" : false,
   "state"    : "library"
  })
  this.createSelection()
  this.createStates()
  this.bindHandlers()
 }
 media.controller.FeaturedImage.prototype.initialize = function() {
  var library, comparator
  if ( ! this.get( "library" ) ) {
   this.set( "library", media.query( { 
    "type"      : "image", 
    "uploadedTo": media.view.settings.post.id, 
    "orderby"   : "menuOrder", 
    "order"     : "ASC" 
   } ) )
  }
  media.controller.Library.prototype.initialize.apply( this, arguments )
  library    = this.get( "library" )
  comparator = library.comparator
  library.comparator = function( a, b ) {
   var aInQuery = !! this.mirroring.get( a.cid )
     , bInQuery = !! this.mirroring.get( b.cid )
   if ( ! aInQuery && bInQuery ) return -1
   else if ( aInQuery && ! bInQuery ) return 1
   else return comparator.apply( this, arguments )
  }
  library.observe( this.get( "selection" ) )
 }
} })(wp.media)
';
/* //
'
wp.media.view.Modal.prototype.on("ready",
 function(){ $("select.attachment-filters").find("[value='."'uploaded'".']").attr("selected", true ).trigger("change") }
);
'
;
'
$("#wpcontent").ajaxSuccess(function() {
 $("select.attachment-filters [value=uploaded]").attr("selected", true ).parent().trigger("change")
})
'
// */
 if ($echo) echo $script;
 return $script;
}


/* ////// Meta Box ////// */

function get_posts_for_meta_box_values($args=NULL) {
 $values = array();
 if (!is_admin_page()) return $values;
 $args = parse_args(array(
  'post_type'		 => 'post',
  'posts_per_page'	 => get_option('posts_per_page'),
  'tax_query'		 => array(),
  'orderby'			 => 'order',
  'order'			 => 'DESC',
  'label'			 => 'post_title', // 'custom_field[{custom_field_key}]'
 ), (array) $args);
 $posts = get_posts($args);
 foreach($posts as $p) {
  setup_postdata($p);
  $v = '';
  if ($args['label'] == 'post_title') $v = $p->post_title;
  else if (preg_match('/custom_field\[(.*?)\]/', $args['label'], $m)) $v = get_post_meta($post->ID, $m[1], true);
  $values[$p->ID] = $v;
 }
 return $values;
}


function is_specific_metabox_in_process($name) {
 // Detect MetaBox instance
 $mbo = NULL;
 foreach (debug_backtrace() as $o) {
  if (isset($o['object']) && is_object($o['object']) && $o['object'] instanceof MetaBox ) {
   $mbo = $o['object']; break;
  }
 }
 if ($mbo) {
  return $name == $mbo->param('name');
 }
 return FALSE
 ;
}


/* ////// Common Meta Box ////// */

function setup_common_meta_box($boxes=NULL, $post_type=NULL) {
 global $common_meta_box_args, $common_meta_box_field_names, $wp_custom_style_handles, $wp_custom_script_handles, $custom_language_domain
 ;
 $wcsh = $wcsh2 = array();
 $language_files = array('jquery-ui.timepicker-addon', 'jquery-ui.datepicker');
 foreach ($wp_custom_script_handles as $h) {
  $_is_lang_file = FALSE ;
  foreach ($language_files as $lf) {
   if (preg_match(sprintf('/^%s/', $lf), $h)) {
	if ($h == sprintf('%s.%s', $lf, get_bloginfo("language"))) $wcsh[$h] = $h;
	$_is_lang_file = TRUE ;
   }
  }
  if (!$_is_lang_file) $wcsh[$h] = $h;
 }
 foreach ($wp_custom_style_handles as $h) {
  $wcsh2[$h] = $h;
 }
 array_multisort($wcsh); array_multisort($wcsh2);
 list($rows,$cols) = array(2,33);

 $fields = array(
  'cssfile' => array('label'=>__('CSS Files', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols ),
  'css' => array('label'=>__('CSS Codes', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols ),
  'jquerycode' => array('label'=>__('JQuery specific code', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols ),
  'jsfile' => array('label'=>__('JavaScript Files', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols ),
  'js' => array('label'=>__('JavaScript Codes', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols ),
  'no_post_excerpt' => array('label'=>__('Do not use Post Excerpt', $custom_language_domain), 'type'=>'checkbox', 'values'=>1, 'value_label'=>array(1=>__('Do not use Post Excerpt', $custom_language_domain) ) ),
  'excerpt_length' => array('label'=>__('Post Excerpt Length', $custom_language_domain), 'type'=>'textfield', 'size'=>6, ),
  'no_post_thumbnail' => array('label'=>__('No Post Thumbnail', $custom_language_domain), 'type'=>'checkbox',
   'values'=>array('no_post_thumbnail'), 'value_label'=>array('no_post_thumbnail'=> __('No Post Thumbnail', $custom_language_domain)) ),
 );

 $_lib_checkbox = apply_filters(WPCF_PREFIX.'Admin_Show_Library_MetaBox_In_Edit_Page', FALSE);
 $_dep_fields = apply_filters(WPCF_PREFIX.'Admin_Show_Deprecated_In_Edit_Page', FALSE);

 if ($_lib_checkbox) {
  $fields += array(
   'css_handles' => array('label'=>__('Registered CSS Files', $custom_language_domain), 'type'=>'checkbox', 'values'=>array_keys($wcsh2) ),
   'js_libraries' => array('label'=>__('Registered JavaScript Libraries', $custom_language_domain), 'type'=>'checkbox', 'values'=>array_keys($wcsh) ),
  );
 }
 if ($_dep_fields) {
  $fields += array(
   'post_thumbnail_size' => array('label'=>__('Post Thumbnail Size'), 'type'=>'select', 'values'=>array_merge(
   array(''),array_keys((array) apply_filters('WPCF_Image_Sizes', NULL)) )),
  );
 }
 $common_meta_box_args = array(
  'post_settings' => array(
   'name' => 'post_settings',
   'title'=>__('Post Settings, Scripts and Styles', $custom_language_domain),
   'context' => 'side',
   'fields' =>$fields
  ),
  'alternate_content' => array(
   'name' => 'alternate_content',
   'title'=>__('Alternative Post Content Fields', $custom_language_domain),
   'fields' => array(
    'h1' => array(
     'label'=>__('Specify H1 element content', $custom_language_domain), 'type'=>'textarea', 'rows'=>4, 'cols'=>$cols ),
    'bc_title' => array(
     'label'=>__('Title for Breadcrumb', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols),
    'bc_title_length' => array(
     'label'=>__('Truncate Title for Breadcrumb', $custom_language_domain), 'type'=>'textfield', 'size'=>18),
	'alternate_post_images' => array(
     'label' => sprintf('%s', __('Alternate Post Thumbnail', $custom_language_domain)),
     'type' => 'image',
     'multipliable' => TRUE,
	),
    'alternate_excerpt' => array(
	 'label'=>__('Alternative Post Excerpt', $custom_language_domain), 'type'=>'textarea', 'rows'=>$rows, 'cols'=>$cols, 'tinymce'=>TRUE),
   )
  ),
  'post_header' => array(
   'name' => 'post_meta_contents',
   'title'=>__('Post Meta Contents', $custom_language_domain),
   'fields' => array(
    'post_header_after_header' => array(
     'label'=>__('Content After Header', $custom_language_domain),
     'tinymce'=>TRUE, 'type'=>'textarea', 'rows'=>6, 'cols'=>100
    ),
    'post_header_above' => array(
     'label'=>__('Content After "Content Header" Starts', $custom_language_domain),
     'tinymce'=>TRUE, 'type'=>'textarea', 'rows'=>6, 'cols'=>100
    ),
    'post_header' => array(
     'label'=>__('Content At "Content Header" Ends', $custom_language_domain),
     'tinymce'=>TRUE, 'type'=>'textarea', 'rows'=>6, 'cols'=>100
    ),
   )
  ), /* //
  'utilities' => array(
   'name'=>'utilities',
   'context' => 'side',
   'post_type' => 'any',
   'title' => __('Utilities', $custom_language_domain),
   'fields'=> array(
	'color' => array(
     'label' => sprintf('%s', __('Color Picker', $custom_language_domain)),
   'script'=>'
var colorpicker_options = {
 parts:  [ "header", "map", "bar", "hex", "hsv", "rgb", "alpha", "preview", "swatches", "footer" ],
 alpha:  true
}
, colorpicker = $("<div id=utilities-colorpicker ></div>").colorpicker(colorpicker_options).css({"top": "1em", "position":"relative", "left":"-300px", "z-index":999}).draggable()
, label = $("label[for=utilities_color_0]")

label.after(colorpicker.hide())
label.on("click", function(){ if (colorpicker.isVisible()) { colorpicker.fadeOut() } else colorpicker.fadeIn() } )
$
'
  ), 
	)
   ), // */
 );

 $common_meta_box_field_names = array();
 foreach ($common_meta_box_args as $b=>$v) {
  $common_meta_box_field_names[$b] = array_keys($v['fields']);
 }

 enqueue_admin_js_library_handle( array(
  'jquery.nanoScrollerJS', 'jquery.apply_nanoScroller', 'jquery.fancybox2', 'jquery.colorpicker'
 ) );


 $boxes = $boxes ? (array) $boxes : $common_meta_box_field_names;
 if (!is_hash($boxes)) { // if only name(s) of the box passed
  $b = array();
  foreach ($boxes as $box) {
   $b[$box] = array();
  }
  $box = NULL ;
 }
 
 $post_type = (array) ( $post_type ? $post_type : array('post','page') )
 ;
 if (!empty($boxes)) {
  enqueue_admin_jquery_code('$("#MetaBox_post_settings textarea").each( function(){
   var e=$(this), w = e.width(), z=e.css("z-index");
   e.on("focus", function(){
    e.css({"float":"right","clear":"both"}).animate({width:640},200)
   }).on("blur", function(){ e.css({float:"none"}).width(w) }).autosize();
  } );
  $("#postcustomstuff textarea").each(function(){
   $(this).on("blur",function(){$(this).height("2em")}).autosize();
  } );');
  enqueue_admin_js_library_handle(array('jquery.autosize'));
 
  foreach (array_keys($boxes) as $box) {
   if (empty($box)) continue;
   $meta_box_args = $common_meta_box_args
   ;
   if (in_array($box, array_keys($common_meta_box_args))) {
    foreach($common_meta_box_args[$box]['fields'] as $f=>$a) {
	 if (isset($boxes[$box]) && isset($boxes[$box]['fields'])) {
	  if (!in_array($f, $boxes[$box]['fields'])) unset($meta_box_args[$box]['fields'][$f]);
	 }
    }
	foreach($post_type as $pt) {
     new MetaBox( array_merge($meta_box_args[$box], array('post_type'=>$pt)) );
	}
   }
  }
 }
}


function format_custom_meta_box($args=array(), $post = NULL) {
 global $wp_custom_functions, $wp_query
 ;
 $formatted = '';
 $post = get_post($post);
 $args_tmp = $wp_custom_functions->parse_args( array(
  'post_type' => 'post',
  'meta_box' => 'post_settings',
 ), $args);
 $post_type = apply_filters('WPCF_PostType', $args_tmp['post_type']);
 if ( !($post_type instanceof PostType) ) {
  return $formatted;
 }

 $meta_box = $post_type->get_meta_box($args_tmp['meta_box']);
 $args = $wp_custom_functions->parse_args( array(
  'optional_keys' => array(),
  'keys' => array(),
  'prefix' => $meta_box->name() .'_',
  'container_suffix' => '_container',
  'label' => TRUE,
  'label_only' => FALSE,
  'alternate_values' => array()
 ), $args )
 ;
 extract($args);
 foreach (array_merge($keys) as $key) {
  $d = apply_filters('WPCF_Get_Post_Meta', $post->ID, $key);
  if ( in_array($key, $optional_keys) && ( empty($d) || ( count($d) == 1 && empty($d[0]) ) ) ) {
   continue ;
  }
  if (!empty($alternate_values) && isset($alternate_values[$key])) {
   $d_alt = array();
   foreach ((array) $d as $_d) {
    $d_alt[] = isset($alternate_values[$key][$_d]) ? $alternate_values[$key][$_d] : $_d;
   }
   $d = $d_alt;
  }
  $formatted .= apply_filters('CF_HTML', 'div',
   array('class'=> array($prefix.'field', $prefix.'field_'.$key )),
    ($label ?
	 apply_filters('CF_HTML', 'div', array('class'=> array( $prefix.'field_title'.$container_suffix, $prefix.'field_title_'.$key.$container_suffix )),
	  apply_filters('CF_HTML', 'div', array('class'=> array( $prefix.'field_title', $prefix.'field_title_'.$key) ), $meta_box->get_field_label($key) )
     ) : ''
	)
   .
	($label_only ? '' :
     apply_filters('CF_HTML', 'div', array('class'=> array($prefix.'field_values', $prefix.'field_values_'.$key) ),
      apply_filters('CF_HTML', 'div', array('class'=>array($prefix.'field_value')), $d)
     )
	)
  );
 }

 return $formatted
 ;
}

