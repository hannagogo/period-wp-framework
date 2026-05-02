<?php 

class WelcartUtility extends ClassTemplate {
private $_options = array();
private $options = null;
private $decoration = array();

function __construct($args = NULL) {
 global $wp_custom_functions
 ;
 require_once('class.HashAccessor.php');
 $this->_options = get_option('usces');
 $args = apply_filters('WPCF_Arguments', $wp_custom_functions->parse_args( array(
  'sku_options_filter_prefix' => 'SKU_OPTIONS_FILTER_',
  'sku_options_filter_optionname_prefix' => 'OPTIONNAME_',
  'usces_item_taxonomy' => 'category',
 ), $args) );
 $this->options = new HashAccessor($this->_options);
 foreach ( $args as $k => $v ) {
  $this->options->param($k, $v);
 }
 $this->_setup_filters();
 $this->_setup_decoration();
 $this->_add_shortcodes();
// add_action('admin_head', function() { my_print_r("welcart utlity loaded"); });
 add_action('WPCF', array(&$this, '_setup_wp_content') ); 
}

private function _setup_filters() {
 add_filter( 'WU_Format_Price',			 array(&$this, '_filter_format_price'), 1);
 add_filter( 'WU_Format_List_Item',		 array(&$this, '_filter_format_list_item'), 1);
 add_filter( 'WU_Info_Decoration',		 array(&$this, '_filter_info_decoration'), 1);
 add_filter( 'WU_Item_Title',			 array(&$this, 'item_title'), 1);
 add_filter( 'WU_Item_Price',			 function($p){return $p;}, 10, 1);
 add_filter( 'WU_Title_Offprice_Format', array(&$this, '_filter_title_offprice_format'), 1);
 add_filter( 'WU_Is_Welcart_Item', array(&$this, 'is_usces_item'), 1);
 add_filter( 'WU_Item_Has_Options', array(&$this, 'usces_item_has_options'), 1);
 add_filter( 'WU_Item_Option_Names', array(&$this, 'get_usces_item_option_names'), 1, 1);
 add_filter( 'WU_Item_Image', '_return_argument', 10, 1);
 
// add_filter( 'WU_SKU_Option', function($html,$args,$matches) { return $html; }, 1, 3);
 add_filter( 'WU_SKU_Option',			 array(&$this, '_filter_sku_option'), 1, 3 );
 
 add_filter( 'the_excerpt',				 array(&$this, 'format_list_item'), 30);
 add_filter( 'the_title',				 array(&$this, '_filter_item_title'), 30);
 add_filter( 'the_content', array(&$this, '_filter_single_item_html'), 20 );
 add_filter( 'the_content', array(&$this, '_filter_single_item_html'), 20 );

 add_filter( 'usces_filter_the_item_price_cr', array(&$this, '_filter_item_price'), 100);
 add_filter( 'usces_filter_singleitem_skudisp', array(&$this, 'skudisp_modify_format') );
 add_filter( 'usces_filter_the_itemOption', array(&$this, '_filter_usces_filter_the_itemOption'), 10, 6 );
 add_filter( 'usces_filter_the_itemImage', array(&$this, '_filter_item_image') );
}

private function option($name=null) {
 if ($name) return $this->options->param($name);
}


public function usces_item_has_options() {
 global $usces;
 if ($usces) return usces_is_options();
 return FALSE
 ;
}

public function get_usces_item_option_names($index) {
 global $usces;
 if ($usces) {
  $options = array();
  usces_the_item(); usces_have_skus(); while(usces_have_options()) $options[] = usces_getItemOptName() ;
  if ($index === NULL) return $options;
  if (isset($options[$index])) return $options[$index];
 }
 return (array) FALSE
 ;
}

private function _setup_decoration() {
 $this->decoration = apply_filters('WU_Info_Decoration', null);
}

public function _setup_wp_content() {
 register_file('css', 'jquery-ui-welcart', trailingslashit(wpcf_plugin_url()).'css/jquery-ui-welcart/jquery-ui-1.10.3.custom.min.css');
 do_action("WPCF_Set_CSS_Handle", "welcart-common");
 if (is_singular()) {
  do_action("WPCF_Set_JQuery_Code", '
$(".skumulti > tbody > tr").each(function(){
 var classname=""
  ,i = arguments[0]
  ,mod3 = i % 3
  ,turns = (i - mod3) / 3 + 1
  ,row_eo = turns % 2 ? "odd" : "even"
  ,class_suffix = "sku_row_"
  ,ws = " "
 ;
 switch (mod3) {
  case 0 : classname = class_suffix + "error_message"; break;
  case 1 : classname = class_suffix + "itemsku"; break;
  case 2 : classname = class_suffix + "incart"; break;
 }
classname += ws+class_suffix+row_eo + ws+class_suffix+"turn_"+turns;
$(this).addClass(classname) } );
'
  );
  do_action( 'WPCF_Set_JS_Handle', array('jquery-ui', 'jquery-ui.combobox') );
  do_action( "WPCF_Set_CSS_Handle", "jquery-ui-welcart" );
 }
}

public function format_list_item($content) {
 return apply_filters('WU_Format_List_Item', $content);
}

public function format_price($prices) {
 return apply_filters('WU_Format_Price', $prices);
}

public function item_title($title) {
 return apply_filters('WU_Item_Title', $title);
}

function is_usces_item($post) {
 $post = get_post($post);
 if ($post === NULL) return FALSE
 ; 
 $usces_cat_id = get_option('usces_item_cat_parent_id');

 if (
     is_specific_taxonomy_term($usces_cat_id, $this->options->param('usces_item_taxonomy'), $post)
     ||
     post_is_in_descendant_taxonomy_term($usces_cat_id, $this->options->param('usces_item_taxonomy'), $post)
    ) {
  return TRUE ;
 }
 ;
 return FALSE;
}

function skudisp_modify_format($content) {
 global $post; $post = get_post($post);
 $content = preg_replace(
  '/('.preg_quote($post->post_title).')/',
  createHTMLElement('span', array( 'class'=>'skudisp_titlepart' ), '$1'),
  $content
 );
 $content = preg_replace('/(<span class="skudisp_titlepart".*?\x2fspan>)(.*?)$/', 
  '$1' . createHTMLElement('span', array('class'=>'skudisp_skunamepart'), '$2'),
  $content
 );
 return $content;
}

public function _filter_single_item_html($content) {
 /* // moving itemsubimg // */
 $itemimg = '';
 $itemsubimg = '';
 if ( preg_match('/<div class="itemimg">.*?<\x2fdiv>/ms', $content, $m) ) { $itemimg = $m[0]; }
 if ( preg_match('/<div class="itemsubimg">.*?end of itemsubimg -->/ms', $content, $m) ) { $itemsubimg = $m[0]; }
 $content = str_replace($itemimg, $itemimg . $itemsubimg, str_replace($itemsubimg, '', $content));
 
 /* // adding classnames // */
 $content = str_replace( 'td rowspan="2"', 'td rowspan="2" class="itemsku"', $content);

 /* // remove thead // */
 if ( preg_match('/<table class="skumulti">.*?(<thead>.*?<\x2fthead>)/ms', $content, $m) ) {
  $content = str_replace( $m[1], '', $content );
 }
 /* // move itemsku // */
 $content = preg_replace_callback(
  '/(<td [^<>]*?rowspan="2" class="itemsku">.*?<\x2ftd>)([\r\n]*<td colspan="2" class="skudisp subborder">)/ms',
  function($m) { return $m[2] . preg_replace('/(<\x2f?)td(?: (?:col|row)span="2")?/', '$1'."div", $m[1]); },
  $content
 )
 ;
 return $content;
}


public function _filter_usces_filter_the_itemOption() {
 /* 
 function _filter_usces_filter_the_itemOption : filters selectable options of each SKU item.
 It accepts six (6) arguments i.e.:
  $html		 : HTML generated by 'usces_filter_the_itemOption',
  $opts		 : Array of pairs of [Option Name] => Array(
   [meta_id]	 => [id],
   [name]		 => [Option Name],
   [means]		 => 0,
   [essential]	 => 1,
   [value]		 => [LF (\x0a) separated list of values]
   [sort]		 => 
  ),
  $name		 : [Option Name],
  $label	 : [?],
  $post_id	 : the post id,
  $skucode	 : the SKU code
  
 It also uses a filter 'WU_SKU_Option' which filters each <option > HTML tag in order to decide display the option or not.
 'WU_SKU_Option' accepts three (3) arguments, i.e.
  $html		 : the HTML '<option >...</option>',
  $arguments : Array (
   'meta_id'	 => [id],
   'name'		 => [Option Name],
   'means'		 => 0,
   'essential'	 => 1,
   'value'		 => [LF (\x0a) separated list of values]
   'sort'		 => 
   'html'		 => '<label>...</label><select >...</select>'
  ),
  $matched	 : Array(
   'value'		 => [value of the <option >...</option> HTML tag],
   'label'		 => [text node of the <option >...</option> HTML tag ('...' part of this code)]
  )
  
 Use of the [SKU id], [option values], [matched option value], [post id], etc. makes easier to define which SKU you are handling.
 
 This process is done while Welcart handles the SKU informations loop.
 
 By default, it uses custom fields to filter each SKU options. See function: _filter_sku_option
 
 // */
 $args = func_get_args(); // $html, $opts, $name, $label, $post_id, $skucode;
 $html = $args[0];
 $re_quote = '(?:\x22|\x27)';
 preg_match_all('/\x3coption value='.$re_quote.'(.*?)'.$re_quote.'.*?>(.*?)<\x2foption>/', $html, $matches);

 $a = array(
  'opts'	 => $args[1],
  'name'	 => $args[2],
  'label'	 => $args[3],
  'post_id'	 => $args[4],
  'skucode'	 => $args[5],
  'html'	 => $args[0],
 );
 if (isset($matches[0])) {
  foreach ($matches[0] as $i=>$m) {
   if ($i == 0) continue;
   $mm = array(
	'value' => trim( $matches[1][$i] ),
	'label' => trim( $matches[2][$i] )
   );
   $html = str_replace($m,
	apply_filters('WU_SKU_Option', $m, $a, $mm),
     // html, post info, matched = Array( 'value' => [html value], 'label' => [text node] )
    $html);
  }
 }
 return $html;
}


public function _filter_sku_option($html, $args, $matches) {
/*
 This function filters SKU item options based on post_meta values (custom fields).
 By default, custom field value in which the key is:
  'SKU_OPTIONS_FILTER_'.[SKU CODE].'_OPTIONNAME_'.[OPTION NAME]
 is used, it is supposed to be a comma, CR, LF or CRLF separated list of numbers (e.g. 1,2,3...)
 each of the numbers is to be treated as an index of the option list (starts from zero (0). )
//*/
 global $post;
//my_print_r(func_get_args(),1);
 $skucode = $args['skucode'];
 $optionname = $args['name'];
 $current_option_value = $matches['value'];
 $re_br = CRLF.'|'.CR.'|'.LF;
 $full_options = preg_split('/'.$re_br.'/', $args['opts'][$optionname]['value']);

 $options = array();
 $meta_key = $this->options->param('sku_options_filter_prefix')
  . $skucode
  . '_'
  . $this->options->param('sku_options_filter_optionname_prefix')
  . $optionname
 ;
 $indexes = get_post_meta($post->ID, $meta_key, 1);
 if ($indexes) {
  $indexes = preg_split('/'.$re_br.'|,/', $indexes);
  foreach ($indexes as $i=>$index) {
   if (isset($full_options[$index]) && !empty($full_options[$index])) $options[] = $full_options[$index];
  }
//  my_print_r(LF.$current_option_value.bin2hex($current_option_value),1);
  if (in_array($current_option_value, $options)) return $html;
  return '';
 }
 return $html;
}


public function _filter_format_price($content) {
 global $usces, $post;
 $post = get_post( $post );

 $skus = array();
 $price_unit = __('dollars', 'usces');
 foreach ($usces->get_skus($post->ID) as $sku) {
  list($cprice, $price, $name) = array($sku['cprice'], $sku['price'], $sku['name']);
//  $skutitle = str_replace($post->post_title, '', $name);
  $skutitle = preg_replace('/^／/','', str_replace($post->post_title, '', $name));
  if ($cprice && $cprice = number_format(floatval( $cprice ))) $cprice .=
    createHTMLElement('span', array('class'=>'price_unit cprice_price_unit'), $price_unit)
  . usces_guid_tax('return')
  ;
  $skus[] = createHTMLElement('span', array('class'=>'skutitle'), $skutitle)
   . createHTMLElement('span', array('class'=>'cprice'), $cprice)
   . createHTMLElement('span', array('class'=>'price_box'),
      createHTMLElement('span', array('class'=>'price'), number_format($price) )
    . createHTMLElement('span', array('class'=>'price_unit'), $price_unit )
   ) 
  ;
 }
 $formatted = createHTMLElement('span', array('class'=>"sku_price"), $skus);
 return $formatted;
}


public function _filter_item_title($content) {
 global $usces, $post;
 $post = get_post($post);

 if ($this->is_usces_item($post)) {
  $t = get_post_custom_values('_itemName', $post->ID);
  $title_elements = array('', $t[0]);
  $items = $usces->get_skus($post->ID);
  $offs = array();
  foreach ($items as $i) {
   if ($i['cprice'] != 0) {
    $offs[] = round( 100 * (1 - $i['price'] / $i['cprice']) );
   }
  }

  if (isset($offs[0]) && in_the_loop())
   return sprintf(
    apply_filters("WU_Title_Offprice_Format", null),
    $content, $offs[0]
   );
  else return $content;
 }
 return $content;
}

public function _filter_item_price($price) {
 return apply_filters('WU_Item_Price', $price);
}


public function _filter_title_offprice_format($format) {
 return createHTMLElement('span', array('class'=>'the_title'), '%s') . ' [%d%%OFF]';
}


public function _filter_format_list_item($content) {
 global $usces, $post, $custom_language_domain;
 $post = get_post($post);
 
 if ($this->is_usces_item($post)) {
  $list_elements = array();
  array_push(
   $list_elements, 
   usces_the_itemImage(0,210,210,null,'return'),
   apply_filters('WU_Format_Price', null)
  );
  $content = '';
  foreach ($list_elements as $a) {
   $content .= createHTMLElement('a', array('href'=>get_permalink($post->ID)), $a);
  }
  $content .= createHTMLElement('a',
   array( 'class'=>"wu_btn_detail", 'href'=>get_permalink($post->ID) ),
   sprintf(
    __('See details about %s', $custom_language_domain),
    $post->post_title
   )
  );
 }
 return $content;
}


function _filter_item_image($html) {
 return apply_filters('WU_Item_Image', $html);
}

public function _filter_info_decoration($data) {
 global $usces, $custom_language_domain;
__('dollars', 'usces');
 $nf = '%s';
 return (array) $data + array(
  'company_name' => $nf,
  'address1'	 => $nf,
  'address2'	 => $nf,
  'zip_code'	 => __('Zip: %s', $custom_language_domain),
  'tel_number' 	 => __('Tel. %s', $custom_language_domain),
  'fax_number'	 => __('Fax. %s', $custom_language_domain),
  'order_mail'	 => __('E-mail: %s', $custom_language_domain),
  'inquiry_mail' => __('E-mail: %s', $custom_language_domain),
  'sender_mail'	 => __('E-mail: %s', $custom_language_domain),
  'error_mail'	 => __('E-mail: %s', $custom_language_domain),
  'copyright'	 => __('©%s', $custom_language_domain),
  'transferee'	 => $nf,
  'start_point'	 => $nf,
 );
}

/************* SHORT CODES ************/
private function _add_shortcodes() {
 foreach (array(
  'company_name',
  'address1',
  'address2',
  'zip_code',
  'tel_number',
  'fax_number',
  'order_mail',
  'inquiry_mail',
  'sender_mail',
  'error_mail',
  'copyright',
  'transferee',
  'start_point',
 ) as $o) {// my_print_r($o,1);
  add_shortcode('welcart_'.$o,
   function ($atts) use ($o) {
    $atts = apply_filters('WPCF_Parse_Arguments', array('decorate' => 1), $atts);
    return sprintf(
     ($atts['decorate'] ? $this->decoration[$o] : '%s'),
     preg_replace( '/(?:\x0d\x0a|\x0d|\x0a)/', '<br />', str_replace( '"', '\"', $this->option($o) ))
    );
   }
  );
 }
 add_shortcode('welcart_address',
  function() { return do_shortcode("[welcart_address1]") . do_shortcode("[welcart_address2]"); }
 );
 add_shortcode('welcart_info', array(&$this, 'sc_info'));
}

public function sc_info($atts) {
 global $wp_custom_functions ;
 $atts = $wp_custom_functions->parse_args(array(
  'info'		 => null,
  'separator'	 => ',',
  'concat'		 => '<br />',
  'decorate'	 => 1,
 ), $atts);

 $info = array();
 foreach ((array) explode($atts['separator'], $atts['info']) as $k) {
  $info[] = do_shortcode(
   sprintf('[welcart_%s decorate=%s]', $k, $atts['decorate'])
  );
 } 
 return implode($atts['concat'], $info);
}

} // END OF WELCART UTILITY

add_action('plugins_loaded',
 function() {
  global $usces; 
  if ($usces) {
   global $welcart_utility;
   $welcart_utility = new WelcartUtility();
  }
 }
);
