class MediaBox extends ClassTemplate {
private $boxes = array();

function __construct($param=null) {
}
function html($id=null) {
}
function add($param) {
 $p = new HashAccessor($param);
 if (!$p->param('id')) return;
 return $this->boxes[$p->param('id')] = new MediaBox_Box($p->param());
}
function setup() {
 global $custom_language_domain;
 wp_localize_script('SimpleMediaBox', 'mediabox_title',  __('Insert Media', $custom_language_domain) );
}
function get($id) {
 return $this->boxes[$id];
}

} // END OF CLASS MEDIABOX



class MediaBox_Box extends ClassTemplate {

function __construct($param) {
 global $custom_language_domain, $wp_custom_functions ;

 $confirm_delete_dialog_params = array();
 $button_params = array();
 $data = array();
 $id = '';

 if (isset($param['confirm_delete_dialog'])) {
  $confirm_delete_dialog_params = $wp_custom_functions->parse_args( array(
   'message'	 => __('Are you sure you want to delete the image?', $custom_language_domain),
   'title'			 => __('Delete Image Confirmation', $custom_language_domain),
   'confirm_button'	 => __('Clear Image', $custom_language_domain),
   'cancel_button'	 => __('Cancel', $custom_language_domain),
  ), $param['confirm_delete_dialog'] ) ;
  unset($param['confirm_delete_dialog']);
 }
 if (isset($param['button_params'])) {
  $button_params = $wp_custom_functions->parse_args(array(
   'pickup_name'	 => __('Select/Upload Image', $custom_language_domain),
   'delete_name'	 => __('Clear Image', $custom_language_domain),
  ), $param['button_params'] );
  unset($param['button_params']);
 }
 if (isset($param['data'])) {
  $data = $wp_custom_functions->parse_args(array(
   'id'		 => null,
   'size'	 => null
  ), $param['data'] );
  unset($param['data']);
 }
 if (isset($param['name'])) {
  //unset($param['data']);
 }
 
 
 $this->param( $wp_custom_functions->parse_args( array(
  'id_suffix' => '',
  'name' => '',
  'image_size_suffix'	 => '_size',
  'button_suffix'		 => '_button',
  'button_delete_suffix' => '_delete',
  'image_view_box_suffix'=> '_image_view',
  'confirm_delete_dialog'=> $confirm_delete_dialog_params,
  'button'				 => $button_params,
  'data'				 => $data,
 ), $param) );
 
 $this->param( array(
  'button_id'		 => $this->param('id') . $this->param('button_suffix'),
  'image_view_id'	 => $this->param('id') . $this->param('image_view_box_suffix'),
  'button_delete_id' => $this->param('button_id') . $this->param('button_delete_suffix')
 ) );
 if ($id = $this->param('id')) {
  $this->img = attachment_image_html($id, get_custom_image_size($this->param('size')));
  $this->image_full = wp_get_attachment_image_src($id, 'full');
 }
}

function html() {
 $h .= createHTMLElement('div', array('id'=>$image_view_id, 'class'=>$self_name.'_image_view'),
    ( ($image)? $image : '' )
   );
 $h .= createHTMLElement(
    'input',
     array('type'=>'hidden', 'value'=>$values[0], 'name'=>$k, 'id'=>$label_for, 'class'=>'user_input')
   ) .createHTMLElement(
    'input',
     array('type'=>'hidden', 'value'=>$image_size[0], 'name'=>$k.$image_size_suffix, 'id'=>$label_for.$image_size_suffix, 'class'=>'user_input')
   ) .
   createHTMLElement(
    'input',
     array('type'=>'button', 'value'=>$form_params['button_pickup_name'], 'name'=>$button_id, 'id'=>$button_id)
   ) .
   createHTMLElement(
    'input',
     array('type'=>'button', 'value'=>$form_params['button_delete_name'], 'id'=>$button_delete_id)
   ) .
   createHTMLElement(
    'div',
	array('class'=>'dialog_confirm_delete', 'id'=>'dialog_confirm_delete_'.$label_for), $confirm_delete_dialog_params['message']
   );
 
}
}

  
$v['script'] = '$("#dialog_confirm_delete_'.$label_for.'").dialog({
 autoOpen:false,
 closeOnEscape:true,
 modal:true,
 title:"'.str_replace('"','\"',$confirm_delete_dialog_params['title']).'",
 buttons:{
  "'.str_replace('"','\"',$confirm_delete_dialog_params['confirm_button']).'":function(){
   $("#'.$image_view_id.'").html("");
   $("#'.$label_for.'").val("");
   $("#'.$label_for.$image_size_suffix.'").val("");
   $("#'.$button_delete_id.'").hide();
   $(this).dialog("close");
  },
  "'.str_replace('"','\"',$confirm_delete_dialog_params['cancel_button']).'":function(){$(this).dialog("close")}
 }
});

$("#'.$button_delete_id.'").click(function(){if($("#'.$label_for.'").val()) $("#dialog_confirm_delete_'.$label_for.'").dialog("open"); else return false;});

if ($("#'.$label_for.'").val()) {
 $("#'.$image_view_id.'").click(function(){tb_show("","'.$image_full[0].'")});
}
if (!$("#'.$label_for.'").val()) $("#'.$button_delete_id.'").hide();

var mediabox, meta_box=$("#'.$label_for.'_box");
$("#'.$button_id.'").on("click",function() {
 $("#'.$label_for.'").addClass("imageField");
 var imageField = $(".imageField").attr("name");
 event.preventDefault();
 if (mediabox) { mediabox.open(); return; }
 mediabox = wp.media({
  title: "'.$v['label'].' : '.$form_params['button_pickup_name'].'",
  editing:   true,
  multiple:  false,
  '.(empty($v['filetypes']) ? '' : 'library: "'.esc_attr( implode( ",", $v['filetypes'] )).'",').'
  "" : undefined
 });
 mediabox.on("select", function(){
  var m = mediabox.el,
   sidebar = $(".media-sidebar", m),
   id = $(".edit-attachment", sidebar).attr("href").match(/\x3f.*?post=(\d+)&?.*?$/)[1],
   image_view = $("#'.$image_view_id.'");
  $("#'.$label_for.'").val(id);
  $("#'.$label_for.$image_size_suffix.'").val($("select[name=size]", m).val());
  $("#'.$button_delete_id.'").show();
  $.ajax({
   type: "POST",
   url: "'.admin_url( 'admin-ajax.php' ).'",
   data: { 
    "action"		 : "get_thumbnail",
	"attachment_id"	 : id
   },
   success: function(data){ image_view.html(data) },
   error: function(){ image_view.html("'.__('Error occured while requesting image by ajax.', $this->language_domain()).'"); }
  });
  $("#'.$label_for.'").removeClass("imageField");
 });
 mediabox.on("open", function(){
  var f = function(){ $(this).on("click", function(){ $(".media-sidebar",mediabox); }) }
 });
 mediabox.on("select", function() {
  return false;
  $.ajax({
   type: "POST",
   url: "'.admin_url( 'admin-ajax.php' ).'",
   data: { 
    "action"		 : "get_image_sizes",
	"attachment_id"	 : id,
	"label_for"		 : "'.$label_for.'",
	"image_size_suffix" : "'.$image_size_suffix.'"
   },
   success: function(data){ image_view.html(data) },
   error: function(){ image_view.html("'.__('Error occured while requesting image by ajax.', $this->language_domain()).'"); }
  });
 } );
 mediabox.open();
});';
