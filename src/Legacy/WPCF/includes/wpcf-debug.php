<?php
/* ////// DEBUG ////// */
function _debug_page_request() {
  global $wp, $template;
  define("D4P_EOL", "\r\n");

  echo '<!-- Request: ';
  echo empty($wp->request) ? "None" : esc_html($wp->request);
  echo ' -->'.D4P_EOL;
  echo '<!-- Matched Rewrite Rule: ';
  echo empty($wp->matched_rule) ? None : esc_html($wp->matched_rule);
  echo ' -->'.D4P_EOL;
  echo '<!-- Matched Rewrite Query: ';
  echo empty($wp->matched_query) ? "None" : esc_html($wp->matched_query);
  echo ' -->'.D4P_EOL;
  echo '<!-- Loaded Template: ';
  echo basename($template);
  echo ' -->'.D4P_EOL;

}

function _tmp_($a=NULL) {
 $f = fopen('/tmp/wpcf_tmp', 'w');
 ob_start();
 var_dump($a);
 fwrite($f, ob_get_clean());
 fclose($f);
}

/* // dummy functions 
function checkval($v) {
 $f = fopen($_SERVER['DOCUMENT_ROOT'].'/wp-content/tmp.txt', 'w');
 fwrite($f, serialize($v));
 fclose($f);
}
// */
