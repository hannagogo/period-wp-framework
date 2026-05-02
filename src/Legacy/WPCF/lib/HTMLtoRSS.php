<?php
/* 
// BASIC USAGE

require_once('CustomFunctions.php');
require_once("HTMLtoRSS.php");

$q = new CGI_PathInfo;
$q->param('keywords', explode(',', $q->param('keywords')));
$q->param('exclude',  explode(',',$pathinfo->param('exclude')));

$args = parse_args(
 array(
  'preset'		 => 'search_engines',
  'preset_index' => 0,
  'keywords'	 => '',
  'exclude_index'=> null,
  'refresh'		 => 60*60
 ), $q->param()
);

$r = new HTMLtoRSS($pathinfo->param());
echo $r->feed();
exit();
 
// */
class HTMLtoRSS {
function __construct($args=null) {
 require_once("phpQuery.php");
 require_once("HTMLtoRSS/SiteData.php");
 $this->setup($args);
 
 // Get document
 // $this->get_document($this->args['document']);
}

function setup_preset($preset) {
 $this->args['preset'] = $preset;
 $preset_class = $this->preset_class_name($this->args['preset']);
 $this->Sites = new $preset_class;

 if (!empty($this->args['site_data'])) {
  $this->Sites->add_site($this->args['site_data']);
  $this->Sites->set_site($this->Sites->lastindex());
 }
 else { $this->Sites->set_site($this->args['preset_index']); }
}

function setup($args) {
 $this->setup_variables($args);
 foreach ($this->presets as $n => $c) require_once($c);
 $this->args = parse_args(
  array(
   'doctype'	 => 'xhtml',
   'document'	 => '',
   'preset'		 => '',
   'site_data'	 => array(),
   'preset_index'=> 0,
   'keywords'	 => '',
   'exclude_index' => null,
   'exclude_match' => null,
   'presets'	 => null
  ), $args
 );

 $this->attlist = $this->get_attlist($this->args['doctype']);
 $this->setup_preset($args['preset']);
 $this->keywords($this->args['keywords']);
 $this->setup_exclusions($this->args);
 return $this;
}

function setup_exclusions($args) {
 // Setting up exclusions
 if (is_string($args['exclude_index'])) $args['exclude_index'] = $this->make_array($args['exclude_index']); 
 else $args['exclude_index'] = (array) $args['exclude_index'];
 
 // Setting up exlusions by Regexp
 if ($e = $args['exclude_match']) {
  if (is_string($args['exclude_match'])) $e = array('global'=>$this->make_re($e));
  else if (is_hash($e)) {
   foreach ($e as $k=>$v) { if (is_string($v)) $v = $this->make_re($v); }
  }
  else $e = array('global'=>$this->make_re($e));
 }
 return $this;
}

function make_array($array) {
 $re = '/[+,\s|]/';
 if (is_hash($array)) {
  foreach ($array as $k=>$v) $array[$k] = explode($re, $v);
  return $array;
 }
 if (is_array($array)) return $array;
 return explode($re, $array);
}
function make_re($array) {
 $array = $this->make_array($array);
 foreach ($array as $i=>$v) $array[$i] = preg_replace('/\x2f/','\x2f', $v);
 return '/' . implode('|', $array) . '/';
}

function get_attlist($doctype=null) {
 if ($doctype && isset($this->html_attlist[$doctype])) return $this->html_attlist[$doctype];
 else return $this->html_attlist['xhtml'];
}

function get_document($html=null) {
 $h = ($html)? $html : $this->args['document'];
 if (empty($h)) {
  $h = $this->Sites->get_document();
 }
 $this->document = $h; //my_print_r($h);
 return $h;
}

function scrape_data($html=null) {
 $this->get_document($html);
 foreach ($this->item_elements as $e) { 
  if (!$this->Sites->element($e)) continue;
  $site = &$this->Sites;
  $dom = &$site->dom();
  $i = 0;
  $loop = 0;
  
  foreach ($dom[$site->element($e,'selector')]->elements as $s) {
   $v = '';
   $node = $site->element($e,'node');
   if (!isset($this->items[$i])) $this->items[$i] = array();

   if ($this->is_exclude($loop)) { $loop++; continue; }

   if (preg_match('/(?:\x40text)|(?:\x40?(?:'.implode('|',$this->attlist).'))/', $node, $m)) {
    $a = preg_replace('/^\x40/', '', $m[0]); 
    $v = pq($s)->attr($a);
   }
   else {
    preg_match('/(html|text)/', $node, $m);
    $method = $m[1];
    if (!$method) $method = 'html';
    $v = pq($s)->{$method}();
   }

   $this->items[$i][$e] = $v;
   $i++; $loop++;
  }
 }
 return $this;
}


function feed($html=null) {
 if (!defined('TIME_ZONE')) define('TIME_ZONE', '+09:00');
 require_once("feedcreator-1.7.2-ppt/include/feedcreator.class.php");
 $rss = new UniversalFeedCreator();
 $rss->encoding = 'utf-8';
 $rss->title = $this->Sites->channel_title();
 $rss->description = $this->Sites->channel_description();
 $rss->link = htmlentities($this->Sites->url($this->keywords()));
 $rss->syndicationURL = $_SERVER['REQUEST_URI'];

 $this->scrape_data($html);
/* $image = new FeedImage();
$image->title = "dailyphp.net logo";
$image->url = "http://www.dailyphp.net/images/logo.gif";
$image->link = "http://www.dailyphp.net";
$image->description = "Feed provided by dailyphp.net. Click to visit.";
$rss->image = $image;
 */
 
 foreach ($this->items as $i) {
  $item = new FeedItem();
  $item->title = $i['title'];
  $item->link = htmlentities( path_to_full_uri($i['link'], $rss->link) );
  $item->description = $i['description'];
  $item->date = time();
  $item->source = $i['link'];
  $rss->addItem($item);
 }
 

 $this->feed_orig = $rss->createFeed('RSS2.0');
 $this->feed = preg_replace('/<!--.*?-->(?:\r|\n)/', '', preg_replace('/(<\x3fxml version="1.0" encoding=")(?:ISO-8859-1)("\x3f>)/', '$1UTF-8$2', $this->feed_orig));
 return $this->feed;
}

function keywords($k=null) { return $this->Sites->keywords($k); }

function setup_variables($args=null) {
 $this->html_attlist = array(
 'html' => array('abbr','accept','accept-charset','accesskey','action','align','alink','alt','archive','axis','background','bgcolor','border','cellpadding','cellspacing','char','charoff','charset','checked','cite','class','classid','clear','code','codebase','codetype','color','cols','colspan','compact','content','coords','data','datetime','declare','defer','dir','disabled','enctype','face','for','frame','frameborder','headers','height','href','hreflang','hspace',
 'http-equiv','id','ismap','label','lang','language','link','longdesc','marginheight','marginwidth','maxlength','media','method','multiple','name','nohref','noresize','noshade','nowrap','object','onblur','onchange','onclick','ondblclick','onfocus','onkeydown','onkeypress','onkeyup','onload','onmousedown','onmousemove','onmouseout','onmouseover','onmouseup','onreset','onselect','onsubmit','onunload','onunlope','size','span','src','standby','start','style','summary','tabindex','target','text','title','type','usemap','valign','value','valuetype','version','vlink','vspace','width'),
 'xhtml' => array('align','char','charoff','valign','id','class','style','title','onclick','ondblclick','onmousedown','onmouseup','onmouseover','onmousemove','onmouseout','onkeypress','onkeydown','onkeyup','lang','xml:lang','dir','profile','xmlns','href','http-equiv','name','content','scheme','charset','hreflang','type','rel','rev','media','xml:space','src','defer','onload','onunload','cite','datetime','accesskey','shape','coords','tabindex','onfocus','onblur','declare','classid','codebase','data','codetype','archive','standby','height','width','usemap','value','valuetype','alt','longdesc','ismap','nohref','action','method','enctype','onsubmit','onreset','accept','accept-charset','for','checked','disabled','readoney','onselect','onchange','summary','border','frame','rules','cellspacing','cellpadding','span','abbr','axis','headers','scope','rowspan','colspan')
 );
 
 $this->presets = array(
  'search_engines'			 => 'HTMLtoRSS/SiteData/SearchEngines.php',
  'regional_jp_chiba_inage'	 => 'HTMLtoRSS/SiteData/Regional/JP/Chiba/Inage.php',
  'misc'					 => 'HTMLtoRSS/SiteData/Misc.php',
  'default'					 => 'HTMLtoRSS/SiteData.php',
  'news_search'				 => 'HTMLtoRSS/SiteData/NewsSearch.php',
 );
 
 $this->args = $args;
 if (isset($this->args['presets'])) {
  $this->presets = array_merge($this->presets, $this->args['presets']);
 }
 
 $this->item_elements = array('title', 'link', 'description');
 
 $this->items = array();

}

function preset_class_name($f) {
 if (!isset($this->presets[$f])) $f = 'default';
 $f = $this->presets[$f];
 $f = str_replace('/', '_', preg_replace('/^(.*?)\x2ephp$/', '$1', $f)); 
 return $f;
}

function is_exclude($i) {
 foreach ( (array) $this->args['exclude_index'] as $v ) {
  if ($v == null) continue;
  if ($v == $i) return true;
 }
}



} // END OF CLASS: HTMLtoRSS
