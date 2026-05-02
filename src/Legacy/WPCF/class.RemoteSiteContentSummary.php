<?php
class RemoteSiteContentSummary {

function __construct($args=null) {
 flush();
 global $wpdb, $wp_custom_functions ;
 $this->table_name = $wpdb->prefix . __CLASS__;
 $this->setup_db_table();
 $this->data = null;
 $this->save_flag = false;

 $this->args = $wp_custom_functions->parse_args(array(
  'feedurl'		 => '',
  'preset'		 => '',
  'preset_index' => 0,
  'keywords'	 => '',
  'refresh'		 => 60*60,
  'debug'		 => false,
  'exclude_match' => null, // array('field'=>'/RegEx/')
 ), $args);
 
 $this->debug = $this->args['debug'];
 if ($this->debug) $this->debug_record = array();
 $this->use_feed = !empty($this->args['feedurl']);
 require_once('HTMLtoRSS.php');
 require_once('RSSParser.php');

 $this->h2r = new HTMLtoRSS();
 $this->h2r->setup($this->args);
 if ($this->use_feed) {
  $this->record('use_feed', true);
  $this->args['preset_index'] = $this->h2r->Sites->lastindex();
  $this->h2r->Sites
   ->add_site(array('url'=>html_entity_decode($this->args['feedurl'])))
   ->set_site($this->h2r->Sites->lastindex())
  ;
 }
 if ($this->get_row() == null) {
  $this->record('row', false);
  $this->get_document();
  $this->save_flag = true;
 }
 else {
  $this->record('row', true);
  if ($this->pubdate + $this->args['refresh'] < time()) {
   $this->record('refresh', true);
   $this->get_document();
   $this->save_flag = true;
  }
 }
 if ($this->save_flag) add_action('shutdown', array(&$this,'save'));
}


function get_document() {
 if ($this->use_feed) {
  $this->record('use_feed', true);
  $this->rssparser = new RSSParser(array('url'=>$this->h2r->Sites->url()));
  $this->rss = $this->rssparser->source;
 }
 else {
  $this->record('use_feed', false);
  $this->document = $this->h2r->get_document();
  $this->rss = $this->h2r->feed($this->document);
  $this->rssparser = new RSSParser(array('source'=>$this->rss));
 }
 
 $this->summary['channel'] = $this->rssparser->channel_data();
 $this->summary['items'] = $this->rssparser->items();
 foreach ($this->summary['items'] as $i=>$v) {
  $this->summary['items'][$i]['description'] = decode_numeric_refernce($v['description']);
  $this->summary['items'][$i]['title'] = decode_numeric_refernce($v['title']);
 }
 $i = count($this->summary['items']); 
 if (isset($this->args['posts_per_page']) && (!$this->args['posts_per_page'] || $this->args['posts_per_page'] > $i)) {
  $this->args['posts_per_page'] = $i;
 }
 $this->summary_html = $this->summary_html($this->args);
 return $this;
}


function get_row() {
 global $wpdb;
 if ($this->get_hostname() == null) return null; 
 $q = sprintf('SELECT * FROM `%s` ', $this->table_name) . $wpdb->prepare('WHERE host=%s and path=%s', $this->get_hostname(), $this->get_path());
 if ($this->data = $wpdb->get_row($q) ) {
  $this->id			 = $this->data->id;
  $this->document	 = $this->data->document;
  $this->rss		 = $this->data->rss;
  $this->summary	 = maybe_unserialize($this->data->summary);
  $this->summary_html= $this->data->summary_html;
  $this->pubdate	 = strtotime($this->data->pubdate);
  foreach ($this->summary['items'] as &$i) {
   //  $i['title'] = decode_numeric_refernce($i['title']);
   //  $i['description'] = decode_numeric_refernce($i['description']);
  }
 }
 return $this->data;
}


function get_summary($refresh=false) {
 if ($refresh) {
  $this->summary = null;
  $this->rss = null;
 }
 if (empty($this->summary['items'])) {
  $rss = $this->get_rss();
  if (!empty($rss)) {
   $this->rssparser	 = new RSSParser(array('source'=>$rss));
   $this->summary	 = $this->rssparser->structure();
  }
 } 
 return $this->summary;
}


function get_rss() { return $this->rss; }


function summary_html($args = null) {
 global $wp_custom_functions
 ;
 $summary = (array) $this->get_summary();
 if (empty($this->summary_html) || $this->save_flag) {
  $atts_tmp = $wp_custom_functions->parse_args(array('class'=>'list_site_summary', 'id'=>''), $args);
  $atts_tmp['container_class'] = $atts_tmp['class'].'_container';
  $atts_tmp['title_class'] = $atts_tmp['class'].'_title';
  $this->sc_args = $wp_custom_functions->parse_args(
   array(
   'posts_per_page' => get_option('posts_per_page'),
   'item_tag'		 => is_html5_capable() ? 'article' : 'div',
   'item_class'		 => $atts_tmp['class'] . '_item',
   'title_tag'		 => is_html5_capable() ? 'h1' : 'div',
   'title_class'	 =>  $atts_tmp['title_class'],
   'link_title'		 => 1,
   'show_description'=> TRUE,
   'description_container' => 'p',
   'truncate'		 => FALSE,
   'remove_tags'	 => FALSE,
   'morelinktext'	 => '...',
   'ellipsis'		 => '...',
   'morelink'		 => TRUE,
   'target'			 => '_blank',
   'show_channel'	 => TRUE,
   'channel_title'	 => '',
   'channel_tag'	 => 'h3',
   'showdate'		 => '',
   'date_format'	 => 'Y/n/j',
   'container_class' => $atts_tmp['container_class'],
   'container_id'	 => ($atts_tmp['id'] ? $atts_tmp['id'] : $atts_tmp['class']) . '_container',
   'exclude_match'	 => NULL,
   'exclude_match_tags'	 => 0,
   'strip_tags'		 => FALSE,
   'allow_br'		 => TRUE,
   'allow_coutinuous_br' => TRUE,
   'substitute_block_tags'=> FALSE,
   'default_filter'	 => 1,
   'allow_duplicate_id' => FALSE,
  ), $args);
  $args = &$this->sc_args; 

  $container_id = $args['allow_duplicate_id'] ? $args['container_id'] : isolate_id($args['container_id']);

  $html = createHTMLElement('div', 'start',
   array(
    'class'=>array(
      $args['container_class'],
     'site_summary_container',
      ($atts_tmp['id'] ? $atts_tmp['id'].'_container' : NULL)),
    'id'=>$container_id
   )
  );

  if ($args['show_channel']) {
   $html .= createHTMLElement('div', array('class'=>'channel_data'),
    createHTMLElement($args['channel_tag'], array('class'=>'channel_title'),
     createHTMLElement('a',
      array('href'=>$summary['channel']['link'], 'target'=>$args['target']),
      $args['channel_title'] ? $args['channel_title'] : $summary['channel']['title']
     ) .
	 (isset($summary['channel']['pubdate'])? createHTMLElement('span', array('class'=>'date'), date($args['date_format'], $summary['channel']['pubdate']) ) : '')
    )
   );
  }
  
  $html .= createHTMLElement('div', 'start', array(
	'class'	 => array($args['container_class'].'_items', 'site_summary_container_items', $container_id.'_items'),
    'id'	 => $container_id.'_items'
  ) );
  $count = 0;
//  if (is_specific_user_logged_in(1)) { my_print_r($this->get_rss()); }
  foreach ($summary['items'] as $i=>$item) {
   if ($count >= $args['posts_per_page']) break;
   $exclude_item = false;
   if ($args['default_filter']) {
    if (preg_match('/^PR: ?/', strip_tags($item['title']))) $exclude_item = true;
   }
   
   $m = (array) $args['exclude_match'];
   if (!empty($m)) {
    foreach ($m as $f=>$re) {
	 if (empty($re) || empty($f)) continue;
	 $re = sprintf('/%s/', preg_replace('/^\x2f?(.*?)\x2f?$/', '$1', $re) );
     if ( isset($item[$f]) && ( $args['exclude_match_tags'] ? preg_match($re, $item[$f]) : preg_match($re, strip_tags($item[$f])) ) ) {
	  $exclude_item = true; break;
	 }
	}
   }
   if ($exclude_item) continue;
   $html .= createHTMLElement(
    $args['item_tag'],
    'start',
    array('class' => array(
     $args['item_class'],
     $args['item_class'].'_count_'.($count+1),
     $args['item_class'].'_count_'.(($count+1)%2 == 1 ? 'odd' : 'even')
    ))
   );
   $html .= createHTMLElement($args['title_tag'], array('class'=>array('article_title', $args['title_class'])),
    createHTMLElement(
     ($args['link_title'] ? 'a' : 'span'),
     ($args['link_title'] ? array('href'=>$item['link'], 'target'=>$args['target']) : array()),
     mb_convert_kana($item['title'], 'K')
//     $item['title']
    ) .
	( isset($item['pubdate']) && $args['showdate'] ? createHTMLElement('span', array('class'=>'date'), date($args['date_format'], $item['pubdate']) ) : '' )
   );
   if ($args['show_description']) {
	if ($args['strip_tags']) {
	 $desc = preg_replace('/[\r\n]/','',$item['description']);
	 if ($args['allow_br']) {
      $desc = preg_replace('/(?:<br)(?:\x20?\x5c)?(?:>)/', "\x0a", $desc);
	 }
	 if ($args['substitute_block_tags']) {
	  foreach (explode('|', $args['substitute_block_tags']) as $tag) {
	   $desc = preg_replace('/\x3c'.$tag.'.*?\x3e/', "\x0a", $desc);
	  }

	 }
     $desc = strip_tags($desc);
	 if ($args['allow_br']) {
	  $desc = preg_replace('/\x0a/', '<br />', $desc);
	 }
	 $item['description'] = $desc;
	}
    $desc_len = mb_strlen($item['description']);
    $len = ($args['truncate'] > 0) ? 
     ($args['truncate'] <= $desc_len ? $args['truncate'] : $desc_len)
     :
     $desc_len ;
    $html .= createHTMLElement($args['description_container'], array('class'=>'item_description'),
     mb_convert_kana( truncate_html(trim($item['description']), $len, $args['ellipsis']) .
     ($args['morelinktext'] ? 
	  createHTMLElement(
       $args['morelink'] ? 'a':'span',
	   array_merge(
	    $args['morelink'] ? array('href'=>$item['link'], 'target'=>$args['target']) : array(),
        array('class'=>'more '.$args['container_id'].'_more')
       ),
       $args['morelinktext']) : ''
	  ), 'K')
    );
   }
   $html .= createHTMLElement($args['item_tag'], 'end');
   $count++;
  }
  $html .= createHTMLElement('div', 'end');
  $html .= createHTMLElement('div', 'end');
  $this->summary_html = $html;
 }
 return $this->summary_html;
}


function setup_db_table() {
 global $wpdb;
 $this->column_names = array('id', 'host', 'path', 'document', 'rss', 'summary', 'summary_html', 'pubdate');
 if ($wpdb->get_var("show tables like '". $this->table_name . "'") != $this->table_name) {
  $sql_query = "CREATE TABLE `". $this->table_name ."` (
   `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
   `host` TEXT NOT NULL,
   `path` TEXT NOT NULL,
   `document` LONGTEXT NULL,
   `rss` LONGTEXT NULL,
   `summary` LONGTEXT NULL,
   `summary_html` LONGTEXT NULL,
   `pubdate` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
  );";
  $wpdb->query($sql_query);
 }
 $this->formats = array(
  'id'		 => '%d',
  'host'	 => '%s',
  'path'	 => '%s',
  'document' => '%s',
  'rss'		 => '%s',
  'summary'	 => '%s',
  'summary_html' => '%s',
  'pubdate'	 => '%s',
 );

}


function db_formats($keys=null) {
 $keys = (array) $keys;
 if (is_hash($keys)) $keys = array_keys($keys);
 if (empty($keys)) $keys = array_keys($this->formats);
 $f = array();
 foreach ($keys as $k) { $f[] = $this->formats[$k]; }
 return $f;
}


function get_hostname($name=null) {
 $name = $name ? $name : $this->h2r->Sites->localname();
 $host = base64_decode_urlsafe($name);
 return $host;
}


function get_path() { return $this->h2r->Sites->localname(array('part'=>'path', 'suffix'=>'')); }


function save() {
 global $wpdb;
 $d = $this->data(); 
 if ($this->get_row()) {
  $w = array('host'=>$this->get_hostname(), 'path'=>$this->get_path());
  $r = $wpdb->update(
   $this->table_name,
   $d,
   $w
  );
 }
 else {
  if ($this->get_hostname()) $wpdb->insert( $this->table_name, $d );
 }
 return $r;
}


function data($serialize = true) {
 return array(
  'host'	 => $this->get_hostname(),
  'path'	 => $this->get_path(),
  'document' => $this->document,
  'rss'		 => $this->get_rss(),
  'summary'	 => $serialize ? maybe_serialize($this->get_summary()) : $this->get_summary(),
  'summary_html'=> $this->summary_html,
  'pubdate'	 => date('Y-m-d H:i:s', time())
 );
}


function data_for_update() {
 return array(
  'document' => 'a'.$this->document,
  'rss'		 => $this->rss,
  'summary'	 => maybe_serialize($this->get_summary()),
  'summary_html'=> $this->summary_html,
  'pubdate'	 => date('Y-m-d H:i:s', time())
 );
}

function sc($atts) { return $this->summary_html($atts); }

function record($key, $value=null) {
 if (!$this->debug) return;
 if ($value===null) return $this->debug_record[$key];
 else $this->debug_record[$key] = $value;
 return $value;
}
/*
function get_document_by_xml_rss() {
 require_once('XML/RSS.php');
 if ($this->use_feed) {
  $this->xmlrss = new XML_RSS($this->h2r->Sites->url());
 }
 else {
  $this->document = $this->h2r->get_document();
  $this->rss = $this->h2r->feed($this->document);
  $this->xmlrss = new XML_RSS($this->rss, 'utf-8');
 }
 $this->xmlrss->parse();
 if ($this->xmlrss->getStructure()) {
  $this->summary['channel'] = $this->xmlrss->getChannelInfo();
  $this->summary['items'] = $this->xmlrss->getItems();
  $i = count($this->summary['items']); 
  if (!$this->args['posts_per_page'] || $this->args['posts_per_page'] >= $i) {
   $this->args['posts_per_page'] = $i;
  }
 }
 return $this;
} // */

} // END OF class RemoteSiteRSS