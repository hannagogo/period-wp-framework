<?php
/* ////// Incompatibility Fix ////// */
function is_html5_capable() {
 global $html5_capable;
 if (defined('CHECK_HTML5_CAPABILITY') && CHECK_HTML5_CAPABILITY && defined('HTML5_CAPABLE')) return HTML5_CAPABLE;
 return $html5_capable;
}


function ie_html5 ($detect = TRUE, $echo = TRUE) {
 global $is_IE;
 if (($detect && $is_IE) || !$detect) {
  $script = "<!--[if lt IE 9]>" . LF .
  '<script type="text/javascript">
   var html5elements = ["article", "aside", "details", "figcaption", "figure", "footer", "header", "hgroup", "menu", "nav", "section"];
   for ( var i=0, j=html5elements.length; i<j; i++ ) { document.createElement(html5elements[i]); }
   </script>
<![endif]-->';
  if (!$echo) return $script;
  else echo $script;
 }
}


function set_ie_compatibility_mode() {
 global $is_IE;
 if ($is_IE) echo createHTMLElement('meta', array("http-equiv"=>"X-UA-Compatible", "content"=>"IE=Edge"), NULL);
}


// --- MODIFIED WPAUTOP - Allow HTML5 block elements in wordpress posts --- //
function html5autop($pee, $br = 1) {
 if ( trim($pee) === '' ) return '';
 $pee = $pee . "\n"; // just to make things a little easier, pad the end
 $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
 // Space things out a little
 // *insertion* of section|article|aside|header|footer|hgroup|figure|details|figcaption|summary
 $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|header|footer|hgroup|figure|details|figcaption|summary)';
 $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
 $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
 $pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
 if ( strpos($pee, '<object') !== false ) {
  $pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
  $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
 }
 $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
 // make paragraphs, including one at the end
 $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
 $pee = '';
 foreach ( $pees as $tinkle ) $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
 $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
// *insertion* of section|article|aside
 $pee = preg_replace('!<p>([^<]+)</(div|address|form|section|article|aside)>!', "<p>$1</p></$2>", $pee);
 $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
 $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
 $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
 $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
 $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
 $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
 if ($br) {
  $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', function($matches) { return str_replace("\n", "<WPPreserveNewline />", $matches[0]); }, $pee);
  $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
  $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
 }
 $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
// *insertion* of img|figcaption|summary
 $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol|img|figcaption|summary)[^>]*>)!', '$1', $pee);
 if (strpos($pee, '<pre') !== false)
  $pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', 'clean_pre', $pee );
 $pee = preg_replace( "|\n</p>$|", '</p>', $pee );

 return $pee;
}


function delete_host_from_attachment_url() { return preg_replace( '/^https?\x3a\x2f\x2f[^\x2f\s]+(.*)$/', '$1', func_get_arg(0) ); }
//add_filter( 'wp_get_attachment_url', 'delete_host_from_attachment_url' );

function removeIdTop() {
 echo preg_replace('|<p id="top" ?/?>(.*?<\/p>)?|', '', apply_filters('the_content', get_the_content())); 
}


function fix_cdata($content) {
 $cdata_start = '\x3c\x21\x5bCDATA\x5b';
 $cdata_end_escaped = '\x5d\x5d\x26\x67\x74\x3b';
 $cdata_end = "\x5d\x5d\x3e";
 
 $regex =  '/('.$cdata_start.')(.*?)'. $cdata_end_escaped.'/m';

 return preg_replace(
  $regex,
  '$1$2' . $cdata_end,
  $content
 );
}


function clean_sc_content($content) {
 $br_re = '(?:\x3cbr(?: *\x2f)?\x3e)';
 $content = preg_replace('/' . $br_re . '+$/', '', preg_replace('/^' . $br_re . '+/', '', trim($content)));
 return $content;
}


function escape_rss_feed($feed) {
 $table = array(
  '&raquo;' => '&#187;',
 );
 foreach ($table as $k=>$v) {
  if (preg_match('|^/.*?/$|', $k)) $feed = preg_replace($k,$v,$feed);
  else $feed = str_replace($k,$v,$feed);
 }
 return $feed;
}

function wpcf_url_to_https($url, $remove_scheme=FALSE) {
 if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && preg_match('/^http\x3a/', $url)) {
  return preg_replace('/^http\x3a/', $remove_scheme ? '':'https:', $url);
 }
 return $url
 ;
}

function wpcf_is_https() {
 return isset($_SERVER['HTTPS']) && (bool) $_SERVER['HTTPS'];
}