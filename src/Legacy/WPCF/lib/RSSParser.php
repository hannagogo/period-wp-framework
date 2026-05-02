<?php 
class RSSParser {
function __construct($args=null) {
 $a = parse_args(array('parser'=>'simplepie'), $args);
 $parsers = array(
  'simplepie' => 'SimplePie',
 );
 $m = "init_" . $parsers[$a['parser']];
 $this->{$m}($args);
 return $this;
}

function init_SimplePie($args) {
 include_once("SimplePie/autoloader.php");
 $a = parse_args(array(
  'source'	 => '',
  'file'	 => '',
  'url'		 => '',
  'encoding' => 'utf-8'
 ), $args);

 $this->parser = new RSSParser_Parser_SimplePie(new SimplePie());
 $this->parser->obj->set_input_encoding($a['encoding']);
 $this->parser->obj->enable_cache(false);

 if (!empty($a['file'])) {
  $a['source'] = file_get_contents($a['file']);
 }
 if (!empty($a['source'])) {
  $this->parser->obj->set_raw_data($a['source']);
 }
 else if (!empty($a['url'])) {
  $this->parser->obj->set_feed_url($a['url']);
 }
 $this->parser->obj->init();
 $this->source = &$this->parser->obj->raw_data;
}

function items($a=null) { return $this->parser->items(); }
function channel_data() { return $this->parser->channel_data(); }
function structure() { return $this->parser->structure(); }
} // END OF CLASS RSSParser



/* ******** */
class RSSParser_Parser_SimplePie extends RSSParser_Parser {
function __construct($parser_object) {
 $this->obj = &$parser_object;
}
function items($a=null) {
 $this->items = array();
 foreach ($this->obj->get_items() as $i) {
  $this->items[] = array(
   'title'		 => $i->get_title(),
   'link'		 => $i->get_link(),
   'pubdate'	 => $i->get_date('U'),
   'description' => $i->get_description()
  );
 }
 return $this->items;
}
function channel_data() {
 $this->channel_data = array();
 $this->channel_data['title'] = $this->obj->get_title();
 $this->channel_data['link'] = $this->obj->get_link();
 $this->channel_data['description'] = $this->obj->get_description();
 return $this->channel_data;
}
function structure() {
 $a = $this->channel_data();
 $a['items'] = $this->items();
 return $a;
}
}

class RSSParser_Parser {
 function items() {}
 function channel_data() {}
 function structure() {}
}

class RSSParser_Structure {
 var $title;
 var $link;
 var $description;
 var $items;
 function __construct() {}
 function title($title=null) { if (empty($title)) return $this->title; return $this->title = $title; }
 function set($key, $data=null) {
  
 }
}


