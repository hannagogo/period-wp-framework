<?php
class HTMLtoRSS_SiteData_SearchEngines extends HTMLtoRSS_SiteData {

function setup_sitedata($data = null) {
 $this->sites = array(
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'Yahoo!検索 - %s',
   'url'		 => 'http://search.yahoo.co.jp/search?ei=UTF-8&p=%s',
   'title'		 => array('selector'=>'#web ol li > a', 'node'=>'text'),
   'link'		 => array('selector'=>'#web ol li > a', 'node'=>'@href'),
   'description' => array('selector'=>'#web ol li div', 'node'=>'text'),
   'cookie'		 => array('sB'=>'n=100&'),
  ),
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'Bing - %s',
   'url'		 => 'http://www.bing.com/search?q=%s',
   'title'		 => array('selector'=>'#results .sa_wr .sa_cc .sb_tlst h3 a', 'node'=>'text'),
   'link'		 => array('selector'=>'#results .sa_wr .sa_cc .sb_tlst h3 a', 'node'=>'@href'),
   'description' => array('selector'=>'#results .sa_wr .sa_cc p', 'node'=>'text'),
  ),
  /*
  http://new.search.popin.cc/news/select/?version=2.2&hl=true&hl.fragsize=140&hl.simple.pre=<span style='background:yellow;padding:2px;'><b>&hl.simple.post=</b></span>&rows=10&fl=title url pubdate popinimg thumbnail topicpath topicpathurl category facet digest&wt=json&hl.alternateField=content&hl.maxAlternateFieldLength=140&json.wrf=PopInCustom.onKeyword&sort=pubdate desc&q=site:(www.yomiuri.co.jp/* OR chubu.yomiuri.co.jp/* OR hokuriku.yomiuri.co.jp/* OR hokkaido.yomiuri.co.jp/* OR osaka.yomiuri.co.jp/* OR kyushu.yomiuri.co.jp/* OR otona.yomiuri.co.jp/* OR job.yomiuri.co.jp/* NOT www.yomiuri.co.jp/editorial/column* NOT www.yomiuri.co.jp/jinsei**) AND (title:(リフォーム AND (千葉)) OR content:(リフォーム AND (千葉)))&start=0&group.truncate=true&group=true&group.ngroups=true&group.field=digest&facet=true&facet.field=category&facet.mincount=1<pre></pre>
  array(
   'charset'	 => 'euc-jp', 'channel'	 => 'YOMIURI ONLINE（読売新聞）',
   'url'		 => 'http://search.yomiuri.co.jp/index.html?q=%s',
   'title'		 => array('selector'=>'#resultList .listTitle a', 'node'=>'text'),
   'link'		 => array('selector'=>'#resultList .listTitle a', 'node'=>'@href'),
   'description' => array('selector'=>'#resultList .listTxt', 'node'=>'text'),
  ), */
 );
 return $this;
}

}
// END OF CLASS: HTMLtoRSS_SiteData_SearchEngines
