<?php
function get_custom_image_size($s = 'thumbnail', $below_or_above='below') {
 $sizes = apply_filters('intermediate_image_sizes', array('thumbnail', 'medium', 'large', 'post-thumbnail', 'full') );
 return in_array($s, $sizes) ?
  $s
  :
  ( ($s = get_nearest_image_size($s,$below_or_above)) ? $s :  'thumbnail' )
 ;
}

function get_nearest_image_size($size, $below_or_above='below') {
 $width = absint($size);
 if (empty($size)) { return; }

 global $_wp_additional_image_sizes;
 $new_size = '';
 foreach ($_wp_additional_image_sizes as $name => $dim) { 
  $w = $dim['width'];
  if ($below_or_above == 'above') {
   if (empty($new_size) && $w > $width) $new_size = $name;
   if ($w > $width && $w < $_wp_additional_image_sizes[$new_size]['width']) { $new_size = $name ; }
  }
  else {
   if (empty($new_size) && $w < $width) $new_size = $name;
   if ($w < $width && $w > $_wp_additional_image_sizes[$new_size]['width']) { $new_size = $name; }
  }
 }
 return $new_size
 ;
}

function get_custom_image_dimensions($s='thumbnail') { 
 global $_wp_additional_image_sizes;
 $dimensions = array();
 $s = get_custom_image_size($s);
 if (isset($_wp_additional_image_sizes[$s])) {
  $dimensions = array(
   $_wp_additional_image_sizes[$s]['width'],
   $_wp_additional_image_sizes[$s]['height'],
   $_wp_additional_image_sizes[$s]['crop']
  );
 }
 else {
  if ($w = get_option($s.'_size_w') && $h = get_option($s.'_size_h') && ($crop = get_option($s.'_crop')) !== FALSE) {
   $dimensions = array($w,$h,$crop);
  }
  else $dimensions = array(get_option('thumbnail_size_w'), get_option('thumbnail_size_h'), get_option('thumbnail_size_crop'));
 };
 return $dimensions;
}

function set_preloadable_image($image) {
 global $preloadable_images; foreach ((array) $image as $i) $preloadable_images[] = $i;
}

function preloadable_images_script($check_existence = false) {
 global $preloadable_images;
 if (empty($preloadable_images)) return;
 $script = array('var img = new Array()');
 foreach ($preloadable_images as $i) {
  if ($check_existence) {
   $url = get_bloginfo('home') . '/' . $i; // 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '//' . $_SERVER['SERVER_NAME']
   if( ( $r = @file_get_contents($url) ) == false ){
    list($version, $status, $msg) = explode(' ',$http_response_header[0], 3);
    if ($status == 404) continue;
   }
  }
  $script[] = 'img.push(new Image());img[img.length-1].src="' . $i . '"';
 }
 enqueue_javascript_code( wrapJavaScript(implode(';', $script)) );
}




function add_post_thumbnail($content) {
 global $post;
 $size = apply_filters('WPCF_Get_Post_Meta', $post->ID, 'post_thumbnail_size', 1);
 $size = $size ? $size : 'post-thumbnail';
 $content = add_post_image($content, array('size'=>$size), $post);
 return $content;
}
/*//
add_filter('the_content', 'add_post_thumbnail');
add_filter('the_excerpt', 'add_post_thumbnail');
//*/

function add_post_image($content, $atts=array(), $p=NULL, $position='prepend') {
 $i = get_post_image($atts, $p);
 return ($position == 'prepend'? $i : '') . $content . ($position == 'append' ? $i : '');
}


function _wpcf_image_arguments($args = NULL) {
 return parse_args(array(
  'id'						 => '',
  'size'					 => 'medium',
  'caption'					 => NULL,
  'width'					 => NULL,
  'height'					 => NULL,
  'img_title'				 => NULL,
  'img_classes'				 => NULL,
  'alt'						 => NULL,
  'description'				 => NULL,
  'a_title'					 => NULL,
  'a_classes'				 => NULL,
  'wrapper'					 => 'p',
  'wrapper_classes'			 => NULL,
  'wrapper_atts'			 => NULL,
  'wrap'					 => FALSE,
  'url'						 => NULL,
  'href'					 => '',
  'style'					 => NULL,
  'image_ratio_data'		 => 2,
  'fit_to_wrapper'			 => NULL,
  'centering'	 			 => FALSE,
  'centering_box_dimensions' => array(),
  'srcset'					 => TRUE,
  'class'					 => NULL,
  'border'					 => NULL,
  'crossorigin'				 => NULL,
  'hspace'					 => NULL,
  'ismap'					 => NULL,
  'longdesc'				 => NULL,
  'sizes'					 => NULL,
  'usemap'					 => NULL,
  'vspace'					 => NULL,
 ), (array) $args);
}

function post_thumbnail_html($post_id=NULL, $param=array()) {
 if (empty($post_id)) { 
  global $post;
  $post_id = $post->ID;
 }
 $id = get_post_thumbnail_id($post_id);
 return attachment_image_html($id, $param);
}


function attachment_image_html($id, $param = array()) {
 if (empty($id)) return;
 $p = parse_args(array( 'full_image'=>TRUE ), $param);
 $full_image_url = '';
 $full_image_src_array= NULL;
 if ($p['full_image'] || (isset($param['size']) && $param['size'] == 'full')) {
  $full_image_src_array = wp_get_attachment_image_src($id, 'full');
  $full_image_url = !empty($full_image_src_array) ? $full_image_src_array[0] : '';
 }
 $url_default = ($p['full_image'] && $full_image_url)? $full_image_url : NULL;
 $default_args = _wpcf_image_arguments(array(
  'url' => $url_default,
  'href' => $url_default,
  'id' => $id,
 ));
 $args = parse_args($default_args, $param )
 ;
 if (!isset($args['href'])) $args['href'] = $args['url'];
 return get_post_image($args);
}


function get_post_image( $args=array(), $p=NULL ) {
 global $post ;
 $post_orig = $post;
 $post = get_post($p);
 $post_thumbnail_id =  get_post_thumbnail_id($post->ID);
 $args_tmp = parse_args(
  array(
   'id'		 => $post_thumbnail_id,
   'size'	 => 'full',
  ), $args
 )
 ;
 if (isset($args['fit_to_wrapper'])) {
  $args['fit_to_wrapper'] = parse_args(array(
   'width'		 => NULL,
   'height'		 => NULL,
   'percent'	 => FALSE,
   'vertical_center' => TRUE,
  ), $args['fit_to_wrapper']);
 }

 if (!$args_tmp['id']) { return ; /* exits here. */ }
 
 $attachment = get_post($args_tmp['id']);
 if (empty($attachment)) { return ; /* exits here. */ }
 $alt = get_post_meta($args_tmp['id'], '_wp_attachment_image_alt', $single=1);
 $filepath = get_attached_file( $attachment->ID );
 $attachment_url = wp_get_attachment_url($attachment->ID);
 
 $args = array_merge($args_tmp, parse_args(_wpcf_image_arguments(array(
  'id'			 => $args_tmp['id'],
  'href'		 => ( !has_multiposts() || is_admin() ? $attachment_url : get_permalink($post->ID) ),
  'a_title'		 => $post->post_title,
  'img_title'	 => $attachment->post_title,
  'alt'			 => $alt ? $alt : basename( parse_url($attachment_url, PHP_URL_PATH) ),
  'description'	 => $attachment->post_content,
  'caption'		 => $attachment->post_excerpt,
  'wrap'		 => TRUE,
 ) ), $args) );

 if ($args['size'] == 'post-thumbnail') {
  $args['size'] = post_thumbnail_size_in_additional_image_size_name();
 }
 foreach (array('wrapper_classes', 'a_classes', 'img_classes') as $c ) {
  $args[$c] = (array) explode(',', $args[$c]);
 }
 if (is_string($args['centering_box_dimensions'])) {
  $args['centering_box_dimensions'] = explode(',', $args['centering_box_dimensions']);
 }

 $sizes = get_custom_image_dimensions( $args['size'] = get_custom_image_size($args['size']) );
 $_is_post_thumbnail = $args['id'] == $post_thumbnail_id;
 $at = wp_get_attachment_image_src($args['id'], $args['size']);

 $ratio = NULL ;
 if ((bool) $at && NULL !== $args['image_ratio_data'] && FALSE !== $args['image_ratio_data']) {
  $ratio = round($at[1] / $at[2], $args['image_ratio_data']);
 }
 
 
 $land_or_port = array();
 if ($at[1] == $at[2]) { $land_or_port[] = 'square'; }
 else if ($at[1] > $at[2]) { $land_or_port[] = 'landscape'; }
 else if ($at[1] < $at[2]) { $land_or_port[] = 'portrait'; }
 ;
 global $_wp_additional_image_sizes
 ;
 if ( !empty($args['fit_to_wrapper']) && !empty($args['fit_to_wrapper']['width']) && !empty($args['fit_to_wrapper']['height']) ) {
  $f = $args['fit_to_wrapper'];
  list($width, $height) = get_image_dimensions_to_fit_to_box($f['width'], $f['height'], $at[1], $at[2], $f['percent']);
  $margin_top = get_image_margin_for_vertical_center($f['width'], $f['height'], $at[1], $at[2], 0, $f['percent'], '');  
  $img_style = array();
  foreach (array('margin-top','width','height') as $prop) {
   $var_name = str_replace('-', '_', $prop);
   $image_style[] = sprintf('%s:%s', $prop, ${$var_name} ? ${$var_name} : 'auto');
  }
  $args['style'] = implode('; ', array_merge($image_style, (array)$args['style']));
 }
 
 $a_attr = array(
  'href'	 => $args['href'],
  'title'	 => $args['a_title'],
 );
 $i_attr = array(
  'alt'		 => $args['alt'],
  'title'	 => $args['img_title'],
  'width'	 => $args['width'],
  'height'	 => $args['height'],
  'style'	 => $args['style'],
  'srcset'	 => '',
 );
 if ($ratio) { $i_attr['data-image-ratio'] = $ratio ; }
 $classes = array_merge(
  array(
   'attachment-image_' . $args['size'],
   'attachment-image_width-' . $at[1],
   'attachment-image_height-' . $at[2],
   'attachment-image_id-' . $args['id'],
   'attachment-image_post-' . $post->ID,
  ),
  (array) $args['wrapper_classes'],
  array_map(function($a) { return "attachment-image_dimension-" . $a; }, $land_or_port)
 );
 
 if (!$at[3]) { $classes[] = 'attachment-image_original'; }
 if ($_is_post_thumbnail) {
  $classes = array_merge(array(
   'post-thumbnail', 'post-thumbnail_'. $post->ID
  ), $classes);
 }
 $wrapper_classes = array();
 foreach ($classes as $c) {
  $wrapper_classes[] = $c.'_wrap';
 }
 $i_attr['class'] = $classes;
 $rewirted = NULL;
 if (!file_exists($rewrited = URLToPath($at[0]))) {
  $img_full = get_attached_file($attachment->ID);

  $filename = pathinfo(basename($at[0])); 
  $file_ext = isset($filename['extension']) ? $filename['extension'] : NULL ;
  $basename = isset($filename['basename'])  ? $filename['basename']  : NULL ;
  $filename = isset($filename['filename'])  ? $filename['filename']  : NULL ;

  $img_full_filename = pathinfo(basename($img_full));
  $img_full_ext = isset($img_full_filename['extension']) ? $img_full_filename['extension'] : NULL ;
  $img_full_basename = isset($img_full_filename['basename']) ?  $img_full_filename['basename'] : NULL ;
  $img_full_filename = isset($img_full_filename['filename']) ? $img_full_filename['filename'] : NULL ;
  $i_attr['display_url'] = $at[0];
  $at[0] = preg_replace(sprintf('/%s$/', preg_quote($img_full_basename)), $basename, $img_full);
 }

 if ($args['srcset']) {
  $i_attr['srcset'] = wp_get_attachment_image_srcset($args['id']);
 }
 $img = IMG($at[0], $i_attr);

 if ($a_attr['href']) {
  $img = createHTMLElement('a', $a_attr, $img );
 }
 ;
 if ($args['centering']) {
  $args['centering_box_dimensions'] = parse_args(array(0=>240,1=>240), $args['centering_box_dimensions']);
  $img_src_full = wp_get_attachment_image_src($args['id'], 'full');
  $box_dim = $args['centering_box_dimensions'];
  if ($img_src_full) {
   $img = html_img_centering_box($img, $box_dim[0], $box_dim[1], $img_src_full[1], $img_src_full[2]);
  }
 }

 if ($args['wrap']) {
  $content = createHTMLElement(
   $args['wrapper'],
   array_merge((array) $args['wrapper_atts'], array( 'class'=>$wrapper_classes, 'id'=>'attachment-'.$args['id'] ) ),
   $img
  );
 }
 else $content = $img;

 $post = $post_orig; $post_orig = NULL;
 return $content;
}


function post_thumbnail_size_in_additional_image_size_name() {
 global $_wp_additional_image_sizes
 ;
 $size = 'post-thumbnail';
 if (
  isset($_wp_additional_image_sizes['post-thumbnail'])
   && is_array($_wp_additional_image_sizes['post-thumbnail'])
   && isset($_wp_additional_image_sizes['post-thumbnail']['crop'])
   && (bool) $_wp_additional_image_sizes['post-thumbnail']['crop']
  ) {
  $dimensions = $_wp_additional_image_sizes['post-thumbnail'];
  foreach ($_wp_additional_image_sizes as $s => $d) {
   if ($size == $s) continue;
   if ((bool) $d['crop'] || 1) {
	if ($d['width'] == $dimensions['width'] && $d['height'] == $dimensions['height']) {
     $size = $s;
	 break;
	}
   }
  }
 }
 return $size;
}


function wpcf_get_attached_files($post=NULL, $posts_per_page=-1) {
 $post = get_post($post);
 $images = get_posts(array(
  'post_parent'		 =>$post->ID,
  'post_type'		 =>'attachment',
  'posts_per_page'	 => $posts_per_page,
 ) );
 return $images
 ;
}


function modify_post_thumbnail_size($size, $restore=true) {
 $orig_size = get_custom_image_dimensions('post-thumbnail');
 $size_opts = get_custom_image_dimensions($size);
 set_post_thumbnail_size($size_opts[0], $size_opts[1], $size_opts[2]);
 return array($size=>$size_opts, 'post-thumbnail'=>$orig_size);
}


function replace_image_with_another_size($content, $size, $attr=NULL, $idre='attachment_image_id_(\d+)', $i=1, $ere='<img .*?>') {
 $attr = apply_filters( 'WPCF_Arguments', parse_args( $attr, array('size'=>$size)) );
 return preg_replace_callback(
  sprintf('/%s/',$ere),
  function($match) use ($i, $attr, $idre) {
   $c = $match[0];
   if ( preg_match(sprintf('/%s/', $idre), $c, $m) ) {
    $c = apply_filters("WPCF_Attachment_Image", $m[$i], $attr);
   }
   return $c;
  },
 $content
 );
}

/* ////// Image Sizes ////// */
function setup_image_sizes($atts=array()) {
 global $wpcf_image_sizes, $_wp_additional_image_sizes, $wpdb
 ; 
 $post_thumbnail_size = $_wp_additional_image_sizes['post-thumbnail'];
 $styled_width_min = $post_thumbnail_size['width'];
 $styled_height_min = $post_thumbnail_size['height'];
 $size_opt_suffix = array('size_w', 'size_h', 'crop');
 $default_size_names = array('thumbnail', 'medium', 'large');
 
 $default_sizes_option_names = $default_sizes_options = array();
 foreach ($default_size_names as $s) {
  foreach ($size_opt_suffix as $suf) {
   $default_sizes_option_names[] = "'{$s}_{$suf}'";
  }
 }
 foreach ($wpdb->get_results('SELECT * FROM ' . $wpdb->options . ' WHERE option_name IN (' . implode(',', $default_sizes_option_names) . ')') as $o) {
  $default_sizes_options[$o->option_name] = $o;
 }
 foreach ( $default_size_names as $s ) {
  if (isset($default_sizes_options[$s.'_size_w']) && isset($default_sizes_options[$s.'_size_h']) ) {
   add_image_size(
    $s,
    $default_sizes_options[$s.'_size_w']->option_value,
    $default_sizes_options[$s.'_size_h']->option_value,
    (isset($default_sizes_options[$s.'_crop']) ? $default_sizes_options[$s.'_crop']->option_value : false)
   );
  }
 }

 $atts = parse_args(
  array(
   'sizes'			 => NULL,
   'parse_style'	 => TRUE,
   'refresh_style'	 => FALSE
  ), $atts
 )
 ;
 extract($atts);
 if (!$sizes) {
  $sizes = array();
  if (isset($wpcf_image_sizes)) $sizes = $wpcf_image_sizes;
  else return; // Nothing to do.
 }
 else {
  $sizes = (array) $sizes; unset($sizes[0]); // Fix for arguments passed by action hook ('')
  if (count($sizes)) $wpcf_image_sizes = array_merge($wpcf_image_sizes, $sizes);
  $sizes = $wpcf_image_sizes;
 }
 
 $styled_width = array();
 $sizes_w = array();
 $styled_sizes_w = array();
 
 if ($parse_style) {
  $styled_width = (array) get_styled_width(NULL,NULL,$refresh_style); sort($styled_width);
  foreach ($sizes as $s) { $sizes_w[] = $s[0]; } $sizes_w = array_unique($sizes_w);
  foreach ($styled_width as $w) {
   if (in_array($w, $sizes_w)) continue;
   if ($w <= $styled_width_min) continue;
   $styled_sizes_w[$w.'px_theme-css'] = array($w, $w, 0);
  }
  $sizes = array_merge($sizes, $styled_sizes_w);
  $wpcf_image_sizes = array_merge($wpcf_image_sizes, $sizes);
 }
 $options_sql = array_flatten( array_map(
  function($s) {
   return array("\x27{$s}_size_w\x27", "\x27{$s}_size_h\x27", "\x27{$s}_crop\x27"); 
  },
  array_keys($wpcf_image_sizes)
 ) );

 $image_sizes = array();
 foreach ($wpdb->get_results('SELECT * FROM ' . $wpdb->options . ' WHERE option_name IN (' . implode(',', $options_sql) . ')') as $o) {
  $image_sizes[$o->option_name] = $o->option_value;
 }
 ;
 foreach ($wpcf_image_sizes as $s=>$o) {
  add_image_size($s,$o[0],$o[1],$o[2]);
  foreach ($size_opt_suffix as $i=>$suffix) {
   $suffix = '_'.$suffix;
   if (isset($image_sizes[$s.$suffix])) { if ($o[$i] != $image_sizes[$s.$suffix]) { update_option($s.$suffix, $o[$i]); } }
   else { add_option($s.$suffix, $o[$i], false); }
  }
 }
 /* //
  foreach ($wpcf_image_sizes as $s=>$o) {
  if( FALSE === get_option($s."_size_w") ) {
   add_option($s.'_size_w', $o[0]);
   add_option($s.'_size_h', $o[1]);
   add_option($s.'_crop', $o[2]);
  }
  else {
   update_option($s.'_size_w', $o[0]);
   update_option($s.'_size_h', $o[1]);
   update_option($s.'_crop', $o[2]);
  }
 }
 // */
 }


function add_image_sizes($sizes, $additional_sizes = array()) {
 global $wpcf_image_sizes, $_wp_additional_image_sizes
 ;
 if (empty($additional_sizes)) $additional_sizes = array_keys($wpcf_image_sizes);
 foreach ($additional_sizes as $s) {
  $sizes[] = $s; // $wpcf_image_sizes[$s] = $sizes[$s];
 }
 return $sizes;
}


function additional_image_attachment_fields($form_fields, $post) { // THIS FUNCTION IS OBSOLETE AS OF VERSION 3.5. See 'additional_image_size_names_choose'
 global $wpcf_image_sizes
 ;
 $html = createHTMLElement('div', 'start', array('class'=>__FUNCTION__, 'id'=>__FUNCTION__.'_'.$post->ID));
 $checked = '';
 foreach (array_keys($wpcf_image_sizes) as $size_name) {
  $downsize = image_downsize($post->ID, $size_name);
  $enable = ( $downsize[3] || 'full' == $size_name );  // if the size selectable
  $css_id = "image-size-{$size_name}-{$post->ID}";
  if ($checked && !$enable) $checked = '';  // if this size is default but not available, uncheck
  if (!$checked && $enable && 'thumbnail' != $size_name) $checked = $size_name;
   // defaults to the first available size bigger than the 'thumbnail'
  $html .= createHTMLElement('div', array('class'=>'image-size-item'),
   createHTMLElement('input',
    array_merge(
     array(
      'type' => 'radio',
      'name' => 'attachments['.$post->ID.'][image-size]',
      'id'     => $css_id,
      'value'=> $size_name
     ),
     ($enable ? array() : array('disabled'=>'disabled')),
     ($checked == $size_name ? array('checked'=>'checked') : array())
    )
   ) .
   createHTMLElement('label', array('for'=>$css_id),
    $size_name .
    (($enable)?
     createHTMLElement('span', array('class'=>'dimensions'), sprintf( __(" (%d&times;%d)"), $downsize[1], $downsize[2]))
     :
     ''
    )
   )
  );
 }
 $form_fields['image-size']['html'] .= $html . createHTMLElement('div', 'end') ;
 return $form_fields;
}
global $wp_version;
if (0 > version_compare($wp_version, '3.4') && !function_exists('ais_get_images')) {
  add_filter('attachment_fields_to_edit', 'additional_image_attachment_fields', 11, 2);
}


function additional_image_size_names_choose($array) {
 global $wpcf_image_sizes
 ;
 $a = array();
 foreach (array_keys($wpcf_image_sizes) as $size) {
  $a[$size] = $size;
 }
 $a = array_merge($array, $a);
 return $a;
}



function get_styled_width($cssfile=NULL,$unit=NULL,$force=false) {
 return get_styled_length($cssfile,'width',$unit,$force);
}
function get_styled_height($cssfile=NULL,$unit=NULL,$force=false) {
 return get_styled_length($cssfile,'height',$unit,$force);
}

function get_styled_length($cssfile=NULL,$worh='width',$unit='px',$force_get_contents = false) {
 if (empty($cssfile) || !file_exists($cssfile)) $cssfile = TEMPLATEPATH . '/style.css';
 $stylesheet = trailingslashit(STYLESHEETPATH).'style.css';
 $template_stylesheet = trailingslashit(TEMPLATEPATH).'style.css';
 
 $cssfiles = array();
 $image_sizes = array('width'=>NULL,'height'=>NULL);
 
 foreach (array_merge((array)$cssfile, array($stylesheet, $template_stylesheet)) as $f) {
  $cssfiles[$f] = $f;
 }
 if ($force_get_contents) {
  foreach (array('width','height') as $d) {
   foreach ($cssfiles as $f) {
    $css = file_get_contents($f);
    preg_match_all('/'.$d.'\s*?:\s*?(\d+)'.$unit.'/', $css, $sizes); $sizes = $sizes[1];
    foreach ($sizes as $i=>$s) $sizes[$i] = floatval($s);
    $image_sizes[$d] = array_merge((array)$image_sizes[$d], (array)$sizes);
   }
   $image_sizes[$d] = array_unique($image_sizes[$d]); sort($image_sizes[$d]);
   set_custom_functions_data( array( 'name'=>'image_sizes_'.$d, 'value'=>$image_sizes[$d] ) );
  }
  return $image_sizes[$worh];
 }
 else {
  return get_custom_functions_data('image_sizes_'.$worh);
 }
}


function get_attachment_info($attachment=NULL, $key='description', $size=NULL) {
 if (!$attachment) return;
 if ($id = (is_string($attachment) || is_numeric($attachment)) ? intval($attachment) : 0) {
  $attachment = get_post($id);
 }
 if (is_object($attachment)) {
  switch($key) {
   case 'description' : return $attachment->post_content ;
   case 'title' : return $attachment->post_title ;
   case 'post_title' : return $attachment->post_title ;
   case 'post_title' : return $attachment->post_title ;
   case 'caption' : return $attachment->post_excerpt ;
   case 'post_name' : return $attachment->post_name ;
   case 'url' :
    $info = wp_get_attachment_image_src($id, $size); 
    return $info[0];
    break
   ;
  }
 }
 return;
}



function wpcf_resize_image_on_upload($file) {
 if ( preg_match( '/^image\x2f/', $file['type']) ) {
  $size = intval( preg_replace('/^(\d+)(?:[^\d]{1,}.*?)?$/', '$1', apply_filters(WPCF_PREFIX.'Upload_Image_Size_Limit', NULL) ) );
  if (empty($size)) return $file;
  $image = wp_get_image_editor( $file['file'] );
  if ( !is_wp_error( $image ) ){
   $imagesize = getimagesize( $file['file'] );
   if ($imagesize[0] > $size || $imagesize[1] > $size) {
    $ratio = $imagesize[0] / $imagesize[1];
    $w = $h = 0;
    if ($imagesize[0] > $imagesize[1]) {
	 $w = $size; $h = $w * $ratio;
	}
	else {
	 $h = $size; $w = $size * $ratio;
	}
   }
   $image->resize( $w, $h, false );
   $image->save( $file['file'] );
  }
 }
 return $file;
}
add_action( 'wp_handle_upload', 'wpcf_resize_image_on_upload' );


function wpcf_swap_attachment_image($content, $args=NULL) {
 $size = NULL
 ;
 if (is_string($args)) $size = $args ;
 $defaults = _wpcf_image_arguments( array(
  'size'					 => 'full', // bulk setting
  'overwrite_class'			 => FALSE,
  'centering'				 => FALSE,
  'centering_box_dimensions' => '1,1',
  'atts'					 => array(), // array('attachment_id'=>array('size'=>'middle', 'alt'=>'Text', 'width'=>'100%', ...), ... )
  'swap_range'				 => -1,
 ) );
 $args = apply_filters('WPCF_Parse_Arguments', $defaults, $args);

 if ($args['centering']) {
  $args['centering_box_dimensions'] = explode(',', $args['centering_box_dimensions']);
  if (empty($args['centering_box_dimensions'])) {
   $args['centering_box_dimensions'] = $defaults['centering_box_dimensions'];
  }
  else if (!isset($args['centering_box_dimensions'][1])) {
   $args['centering_box_dimensions'][1] = $args['centering_box_dimensions'][0];
  }
 }

 preg_match_all('/((?:\x3c)img [^>]*?class=[\x22\x27][^>\x22\x27]*?wp-image-(\d+)[^>\x22\x27]*?[\x22\x27][^>]*? ?\x2f?(?:\x3e))/', $content, $matches);

 if (!empty($matches[1])) {
  foreach ($matches[1] as $i => $match) {
   $id = $matches[2][$i];
   if (!$id) { continue; }
   $i_atts = array();
   $attachment_image_args = array('id'=>$id);
   ;
   $atts = apply_filters('WPCF_Parse_Arguments', $args, isset($args['atts'][$id]) ? $args['atts'][$id] : array() );
   ;
   preg_match_all('/([^\x22\x27\x20]+?)=[\x22\x27](.*?)[\x22\x27]/', $match, $atts_matches);
   ;
   foreach ($atts_matches[0] as $j=>$_a) {
	if ($atts_matches[1][$j] == 'class') {
	 $a = array();
     if ($args['overwrite_class']) {
	  $a = HTMLClassAttribute($atts['img_classes']);
	 }
	 else {
	  $a = HTMLClassAttribute($atts_matches[2][$j]);
      if ($atts['img_classes']) {
	   $a = HTMLClassAttribute($a, $atts['img_classes']);
	  }
	 }
     $a[] = 'attachment-image-swapped';
	 $attachment_image_args[$atts_matches[1][$j]] = $a;
	 $attachment_image_args['img_classes'] = $a;
	 $i_atts[$atts_matches[1][$j]] = $a;
	}
	else if ($atts_matches[1][$j] == 'title') {
     if ($atts['img_title']) {
	  $attachment_image_args['img_title'] = $atts['img_title'];
      $i_atts[$atts_matches[1][$j]] = $atts['img_title'];
	 }
	 else {
	  $attachment_image_args['img_title'] = $atts_matches[2][$j];
      $i_atts[$atts_matches[1][$j]] = $atts_matches[2][$j];
	 }
	}
	else {
	 $attachment_image_args[$atts_matches[1][$j]] = $atts_matches[2][$j];
	 $i_attr[$atts_matches[1][$j]] = $atts_matches[2][$j];
	}
   }
   
   foreach (array('href','src','width','height') as $k) {
    $attachment_image_args[$k] = '';
   }
   $attachment_image_args = array_merge($atts, $attachment_image_args);
// my_print_r($attachment_image_args);
   $content = str_replace($match, attachment_image_html($id, $attachment_image_args), $content);
  }
 }
 return $content;
}


function wpcf_content_image_id($post_id=NULL, $order=1) {
 $post = get_post($post_id);
 $content = $post->post_content;
 preg_match_all('/((?:\x3c)img [^>]*?class=[\x22\x27][^>\x22\x27]*?wp-image-(\d+)[^>\x22\x27]*?[\x22\x27][^>]*? ?\x2f?(?:\x3e))/', $content, $matches);
 return isset($matches[2]) && isset($matches[2][$order-1]) ? $matches[2][$order-1] : NULL;
}

function wpcf_display_image_prop_size($atts, $content) {
 $classname = isset($atts['class']) ? $atts['class'] : 'display_prop_size';
 return preg_replace('/((?:\x3c)img [^>]*?class=[\x22\x27])([^>\x22\x27]*?)([\x22\x27][^>]*? ?\x2f?(?:\x3e))/', '$1 '.$classname.'$2$3', $content);
}
//add_shortcode('wpcf_img_prop_size', 'wpcf_display_image_prop_size');
