<?php
class HTMLtoRSS_SiteData {

function __construct($args=null) {
 $this->setup_sitedata();
 $this->args = parse_args(
  array(
   'index' => 0,
   'keywords' => ''
  ), $args
 );
 $this->set_site($this->index());
 return $this;
}

function set_site($index=null) {
 $this->site = &$this->get_site_data($this->index($this->index($index)));
 $this->keywords();
 return $this;
}

function url($keywords='') {
 if ($this->site) return sprintf($this->site['url'], $this->keywords_for_uri());
 else return null;
}

function localname($args=null) {
 if (is_string($args)) $args = array('part'=>$args);
 $args = parse_args(array(
  'part' => 'host',
  'url' => $this->url(),
  'suffix' => '.rdf'
 ), $args);
 extract($args);
 $u = parse_url($url);
 $localname = '';
 if ($part == 'host') $localname = base64_encode_urlsafe($u['host']);
 if ($part == 'path') {
  $path = preg_replace('{/$}', '', $u['path']);
  $path .= empty($u['query'])? '' : '?' . $u['query'];
  $localname = base64_encode_urlsafe($path);
  empty($localname) && $localname = 'index';
  if ($suffix) $localname = preg_replace('/$/', $suffix, $localname);
 }
 $localname = preg_replace('/[^A-Za-z0-9\x2e]/', '',  $localname);
 return $localname;
}

function keywords($k='') {
 $k = $k ? $k : $this->args['keywords'];
 if (is_string($k)) {
  if (empty($k)) $k = array();
  else $k = preg_split('/[,+\x20]/', $k);
 }
 $k = (array) $k;
 $this->args['keywords'] = $k;
 return $k;
}

function keywords_for_uri($k='') {
 $s = array();
 if ($this->site) {
  foreach ($this->keywords($k) as $kwd) {
   $s[] = urlencode(mb_convert_encoding($kwd,$this->charset(),'utf-8'));
  }
  return implode('+', $s);
 }
}


function setup_sitedata($data = null) { // this function must be overwritten by sub class.
 $this->sites = array(); 
}

function add_site($data) {
 if (empty($data)) return $this;
 $data = parse_args(array(
  'charset'	 => 'utf-8', 'channel'	 => '', 'url' => '%s',
  'title'		 => array('selector'=>'title', 'node'=>'text'),
  'link'		 => array('selector'=>'', 'node'=>'@href'),
  'description'	 => array('selector'=>'', 'node'=>'text'),
 ), (array) $data);

 $i = $this->lastindex();
 $this->sites[($this->site_empty($this->get_site_data($this->lastindex())) ? $i : $i + 1)] = $data;
 return $this;
}

function &get_site_data($index) { return $this->sites[$this->index($index)]; }

function site_empty($data) {
 return empty($data);
}

function get_document($keywords='') {
 if ($u = $this->url($keywords)) {
  $context = null;
  if ($this->get_cookie()) {
   $context = stream_context_create(
    array(
     'http'=>array('method'=>'GET', 'header'=>$this->get_cookie(array('make_header'=>true)))
    )
   );
  }

  $this->site['document_orig'] = file_get_contents($u, false, $context);
  $this->site['document'] = preg_replace(
   '|(<meta http-equiv="Content-Type.*?charset=)(?:.*?)(" ?/?>)|i',
   '$1utf-8$2',
   mb_convert_encoding($this->site['document_orig'],'utf8',$this->charset())
  );
  $this->site['document'] = preg_replace(
   '|(</head>)|i',
   '<base href="'.$this->url().'" />$1',
   $this->site['document']
  );
 $this->setup_dom();
 }
 return $this->site['document'];
}

function element($element, $info=null) {
 if (in_array($element, array('title', 'link', 'description') )) {
  if (in_array($info, array('selector', 'node'))) return $this->site[$element][$info];
  else return $this->site[$element];
 }
 return null;
}

function setup_dom() {
 if (!isset($this->site['document']) || empty($this->site['document'])) $this->get_document();
 $this->site['dom'] = phpQuery::newDocumentHTML($this->site['document']);
 return $this;
}

function &dom() { $this->setup_dom(); return $this->site['dom']; }
function charset() { if ($c = $this->site['charset']) return $c; return 'UTF-8'; }
function channel_title() {
 if (!isset($this->site['channel']) || empty($this->site['channel'])) {
  $dom =& $this->dom();
  $this->site['channel'] = pq($dom['title'])->text();
 }
 return sprintf($this->site['channel'], implode(' ', $this->keywords()));
}
function channel_description() {
 return isset($this->site['channel_description']) ? $this->site['channel_description'] : null;
}
function get_cookie($args=null) {
 $args = parse_args(
  array(
   'make_header' => true,
  ), (array) $args
 );
 if (isset($this->site['cookie'])) {
  $c = $this->site['cookie'];
  if ($args['make_header']) {
   $h = 'Cookie: ';
   foreach ($c as $k=>$v) {
    $h .= $k . '=' . $v . '; ';
   }
   $h .= "\r\n";
   return $h;
  }
  return $c;
 }
}

function lastindex() { return count($this->sites) - 1; }

function index($index = null) {
 $i = ($index === null)? $this->args['index'] : $index;
 if ($i >= $this->lastindex()) $i = $this->lastindex();
 if ($i < 0) $i = 0;
 $this->args['index'] = $i;
 return $this->args['index'];
}

} // END OF CLASS : HTMLtoRSS_SiteData




