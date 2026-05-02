<?php
/* ////// Query ////// */

function is_wp_query_object($object) {
 return $object !== NULL && $object instanceof WP_Query;
}

function flushRules() { global $wp_rewrite; $wp_rewrite->flush_rules(); }

function enable_rewrite_rules($rules, $vars=NULL, $after='top') {
 global $wp_rewrite
 ;
 $rules = (array) $rules;
 if (empty($rules)) return;
 $r = array();
 $vars === NULL && $vars = array();

 foreach ($rules as $p => $m) {
  $r[$p] = $m;
  $mm = explode('?', $m);
  parse_str($mm[1], $s);
  add_rewrite_rule($p,$m,$after);
  enable_query_vars(array_keys($s));
 }
 flushRules();
 return $r
 ;
}


function wpcf_modify_query($a, $obj=NULL, $query=FALSE) {
/* either of the followings:

   $query_vars = wpcf_modify_query($key, $value);

   $query_vars = wpcf_modify_query(array($key, $value));

   $wq = new WP_Query;
   $query_vars = wpcf_modify_query(array($key, $value), $wq);
   $wq->query_posts($query_vars);
   
   wpcf_modify_query($key, $value, $make_query=TRUE); // DOES query_posts() and modify current query
*/
 $obj_name = '';
 $q = array();

 if (is_a($obj, 'WP_Query')) $obj_name = 'obj';
 else {
  global $wp_query;
  $obj_name = 'wp_query';
  if (is_array($a)) $q = $a;
  else if (is_string($a)) $q[$a] = $obj;
  else return;
 }
 foreach ($q as $k=>$v) ${$obj_name}->set($k, $v);
 if ($query) {
  query_posts(${$obj_name}->query_vars);
 }
 return ${$obj_name}->query_vars;
}


function enable_query_vars($vars = NULL) {
 global $wp_query;
 if (!$vars) return;
 $vars = (array) $vars;
 if (count($vars)) {
  return add_filter( 'query_vars', function($v) use ($vars) { return array_merge($v, $vars); } );
 }
 return;
}


