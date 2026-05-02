<?php
function is_posttype($type)
{
  global $wp_filter;
  $filter = "WPCU__Is_PostType";
  if (!isset($wp_filter[$filter])) {
    return false;
  }
  return  apply_filters($filter, $type) !== FALSE;
}

function make_html($e, $args)
{
  $a = array_shift($args);
  $c = "";
  if (isset($args[0])) {
    if (is_array($args[0])) {
      $c = $args[0];
    } else {
      foreach ($args as $v) {
        $c .= $v;
      }
    }
  }
  return apply_filters('WPCU__HTML_Element', $e, $a, $c);
  //function create_element($name, $attr=NULL, $content=NULL, $no_tags_if_empty=FALSE, $ignore_tags=FALSE, $force_strip_tags=FALSE) {

}

function html_a()
{
  return make_html("a", func_get_args());
}
function html_p()
{
  return make_html("p", func_get_args());
}
function html_div()
{
  return make_html("div", func_get_args());
}
function html_span()
{
  return make_html("span", func_get_args());
}
function html_ul()
{
  return make_html("ul", func_get_args());
}
function html_li()
{
  return make_html("li", func_get_args());
}
function html_comment()
{
  return make_html("_comment", func_get_args());
}
function jquery_code($code)
{
  return apply_filters('WPCU__Wrap_JavaScript', $code, array('jquery' => TRUE, 'jqueryready' => TRUE));
}
function theme_img_full($handle, $a)
{
  $a = apply_filters(
    'WPCU__Arguments',
    array(
      'width' => '100%',
      'height' => '',
      'alt' => NULL,
      'id' => NULL,
      'class' => NULL,
      'border' => NULL,
      'crossorigin' => NULL,
      'hspace' => NULL,
      'ismap' => NULL,
      'longdesc' => NULL,
      'sizes' => NULL,
      'srcset' => NULL,
      'usemap' => NULL,
      'vspace' => NULL,
    ),
    $a
  );
  return apply_filters('WPCF_Theme_Image', $handle, $a);;
}

function img_full($id, $args = NULL)
{
  return apply_filters('WPCU__Attachment_Image', $id, $args);
}
/***** USAGE *****//*
 echo make_html('div', array('id'=>'var', 'class'=>'foo'), '<span>a</span>','<div>b</div>','<br>','<p>foo</p>');

 echo html_div(NULL, 'Content');
 echo html_div(array('id'=>'var'), 'eenie','meenie','minie','moe');
 echo html_div(array('id'=>'var'), array('<span>a</span>','<div>b</div>','<br>','<p>foo</p>'));
/* // */


function make_table_row($th, $td = NULL, $class = NULL, $id = NULL, $classes = NULL)
{
  $tr_class = 'table_row';
  if ($class) {
    $tr_class = array_merge((array) $tr_class, (array) $class);
  }
  if ($td === NULL) {
    $td = $th;
    $th = NULL;
  }
  $data = $header = '';

  if ($th) {
    $th_c = array('table_col', 'table_header_col');
    if (is_array($classes) && isset($classes[0])) {
      $th_c[] = array_shift($classes);
    }
    $header = html_div(array('class' => $th_c), $th);
  }
  $td_c = array('table_col', 'table_data_col');
  foreach ((array) $td as $i => $_td) {
    if (isset($classes[$i])) {
      $td_c[] = $classes[$i];
    }
    $data .= html_div(array('class' => $td_c), $_td);
  }

  $html = html_div(array('class' => $tr_class, 'id' => $id), $header . $data);

  return $html;
}

function is_user($user)
{
  return apply_filters("WPCU__Is_User_Logged_In", $user) === true;
}
function is_admin_user()
{
  return is_user(array(1));
}
function wpcf_postmeta($key, $single = TRUE)
{
  global $post;
  return apply_filters('WPCF_Get_Post_Meta', $post->ID, $key, $single);
}


function my_print_r($content = null)
{
  global $wp_filters, $CUSTOM_UTILITY;
  $CUSTOM_UTILITY->custom_print_r($content);
}

function __p()
{
  global $CUSTOM_UTILITY;
  $a = func_get_args();
  $CUSTOM_UTILITY->custom_print_r($a);
}
