<?php
/* ////// CUSTOM DATABASE TABLE ////// */
function get_custom_functions_data($args=NULL, $full_table=false, $unserialize=NULL) {
// prepare_custom_functions_db_table();
 global $wpdb;
 if (is_string($args)) $args = array('name'=>$args);
 $args = parse_args(array(
  'name'		 => NULL,
  'id'			 => NULL,
  'table_name'	 => WPCF_CUSTOM_FUNCTIONS_TABLE_NAME,
  'full_table'	 => $full_table,
  'unserialize'	 => is_NULL($unserialize) ? true : $unserialize
 ), $args);

 if (!$args['id'] && !$args['name']) return;
 $unserialize = $args['unserialize'];
 $column_prefix = preg_replace('/^(?:'.$wpdb->prefix.')?/', '', $args['table_name']);
 $columns = array($column_prefix.'_id', $column_prefix.'_name', $column_prefix.'_value', $column_prefix.'_time');
 $where = array();
 foreach (array('name', 'id') as $c) if ($args[$c]) $where[] = $column_prefix.'_'.$c."='".$args[$c]."'";
 $r = $wpdb->get_results( "SELECT " . implode(',', $columns) . " FROM " . $args['table_name'] . " WHERE " . implode(' AND ',$where));
 if (empty($r)) return NULL;

 $result = array();
 foreach (array('name','id','value','time') as $k) {
  $col = sprintf('%s_%s', $column_prefix, $k);
  $d = $r[0]->{$col};
  if ($k=='value' && $unserialize) $d = maybe_unserialize($d);
  if ($k == 'time') $d = strtotime($d);
  $result[$k] = $d;
 }
 return $args['full_table'] ? $result : $result['value'];
}


function set_custom_functions_data($args,$a2=NULL,$a3=NULL,$a4=NULL) {
 global $wpdb
 ;
 if (is_string($args)) { // The first arg is string. i.e. suppose the name passed, second would be the value
  $args = array(
   'name'		 => $args,
   'value'		 => $a2,
   'force_insert'=> $a3 ? $a3 : false,
   'serialize'	 => $a4 ? $a4 : true,
  );
 }
 else {
  if (isset($args['value']) && !isset($args['force_insert'])) { $args['force_insert'] = $a2 ? $a2 : false; }
 }
 $date_format = 'Y-m-d H:i:s';
 $args = parse_args(array(
  'name'		 => NULL,
  'id' 			 => NULL,
  'table_name'	 => WPCF_CUSTOM_FUNCTIONS_TABLE_NAME,
  'value'		 => NULL,
  'time'		 => date($date_format),
  'force_insert' => false,
  'serialize'	 => true,
 ), $args);
 if (!is_string($args['time'])) {
  $args['time'] = date($date_format, intval($args['time'])); // Regularize the time
 }
 if ($args['value']===NULL || (empty($args['name']) && empty($args['id']))) return; // The value is NULL or both the name and the id is empty


 $column_prefix = preg_replace('/^(?:'.$wpdb->prefix.')?/', '', $args['table_name']);
 $data = $q = $where = array();

 foreach (array('name','id','value') as $c) {
  $col = sprintf("%s_%s", $column_prefix, $c);
  if (NULL !== $args[$c]) {
   $data[$col] = $q[$c] = $where[$col] = ($c=='value' && $args['serialize'] ? maybe_serialize($args[$c]) : $args[$c]);
   if ($c=='value' && !$args['force_insert']) {
    unset($q[$c]); unset($where[$col]);
   }
  }
 }
 $data[sprintf("%s_%s",$column_prefix,'time')] = $args['time'];
 $r = NULL;
 if (get_custom_functions_data($q, true)) {
  $r = $wpdb->update($args['table_name'], $data, $where);
 }
 else $r = $wpdb->insert($args['table_name'], $data);
 if ($r) return $args['value'];
}



