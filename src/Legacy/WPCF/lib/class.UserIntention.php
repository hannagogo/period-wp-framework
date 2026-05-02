<?php
require_once("CustomFunctions.php");
require_once("ClassTemplate.php");
require_once("class.HashAccessor.php");
mb_regex_encoding('utf-8');

class UserIntention extends ClassTemplate {
public $search_engines = array(
 'www.google.co.jp'		 => array('key' => 'q'),
 'search.yahoo.co.jp'	 => array('key' => 'p'),
 'www.excite.co.jp'		 => array('key' => 'search'),
 'www.bing.com'			 => array('key' => 'q'),
 'search.goo.ne.jp'		 => array('key' => 'MT'),
 'cgi.search.biglobe.ne.jp' => array('key' => 'q'),
 'search.nifty.com'		 => array('key' => 'q'),
 'search.yahoo.com'		 => array('key' => 'p'),
);
public $intended = array();
private $resource = null;
public $filter = '\x2e(html|php|jpeg)\x2f?$';
public $markers = '[\x20\x2f\x3d\x2e　・_-]';
public function resource() {
 return $this->resource;
}

public function __construct($args=null) {
 $this->params( parse_args(array(
  'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
  'resource' => array(),
  'regularizer' => array(),
 ), $args) );
 $this->regularizer = new HashAccessor($this->param('regularizer'));
 $this->resource = new UserIntention_Resource( $this->param('resource') );
 $this->parse_referer();
}

private function parse_referer($referer = null) {
 if (empty($referer)) $referer = $this->param('referer');
 else $this->param('referer', $referer);
 if (empty($referer)) return;
 
 $r = parse_url($referer);
 isset($r['query']) ? parse_str($r['query'], $get) :  $get = array();
 $keywords = array();

 if (isset($r['host']) && array_key_exists($r['host'], $this->search_engines) && isset( $get[$this->search_engines[$r['host']]['key']] )) {
  $str = mb_ereg_replace('/'.$this->markers.'/', ' ', $get[$this->search_engines[$r['host']]['key']]);
  $keywords = explode(' ', $str);
 }
 else {
  $query_values = (array) array_values($get);
  $path = explode(' ',
   preg_replace('/\x2f/', ' ', 
    preg_replace('/'.$this->filter.'/', '', $r['path'])
   )
  );
  $keywords = $query_values + $path;
 }
 foreach ($keywords as $kw) {
  $r = $this->resource->has($kw);
  if (!empty($r)) {
   $this->intended[$kw] = $this->resource->get($r[0]);
  }
 }
 return;
}


public function get_resources($keywords) {
 $resources = array();
 $names = array(); 
 foreach ((array) $keywords as $kw) {
  if (isset($this->intended[$kw])) {
   $names[] = $kw; continue;
  }
  $a = $this->resource->has($kw);
  if (empty($a)) continue ;
  $names = array_merge($names, $a) ;
 }
 foreach ($this->intended as $c => $r ) {
  if (in_array($c, $names)) $resources[$c] = $r;
 } // my_print_r(array('res'=>$resources),1);
 if (empty($resources)) return FALSE ;
 return $resources;
 return FALSE ;
}


public function get_resource($keyword) {
 return $this->get_resources($keyword);
}


public function is_intended($keyword) {
 return in_array($keyword, array_keys($this->intended));
}


function regularize_keyword($keyword) {
 return $this->args['regularizer'][$keyword];
}

} // END OF CLASS USERINFO.




class UserIntention_Resource {
protected $resource = null;
protected $reverse_dictionary = null;
public function __construct($r=null) {
 $rr = array();
 $r = (array) $r;
 foreach ($r as $cat=>$resource) {
  foreach ($r[$cat] as $res=>$val) {
   if (!is_array($val)) $val = (array) $val;
   $r[$cat][$res] = $val; 
   foreach ($r[$cat][$res] as $kw) {
    if (isset($rr[$kw])) { $rr[$kw][] = $cat; }
	else {
	 $rr[$kw] = array($cat);
	}
   }
  }
  if (isset($rr[$cat])) { if (!in_array($cat, array_values($rr[$cat]))) $rr[$cat][] = $cat; }
  else { $rr[$cat] = array($cat); }
 }
 $this->resource = new HashAccessor($r);
 $this->reverse_dictionary = new HashAccessor($rr);

}
public function has($keyword) { //my_print_r($this->reverse_dictionary,1);
 return $this->reverse_dictionary->param($keyword);
}
public function get($cat) {
 return $this->resource->param($cat);
}
}