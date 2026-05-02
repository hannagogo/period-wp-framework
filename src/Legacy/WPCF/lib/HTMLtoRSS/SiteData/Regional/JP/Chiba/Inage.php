<?php
class HTMLtoRSS_SiteData_Region_JP_Chiba_Inage extends HTMLtoRSS_SiteData {

function setup_sitedata($data = null) {
 $this->sites = array(
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'NHK千葉のニュース',
   'url'		 => 'http://www.nhk.or.jp/chiba/lnews/',
   'title'		 => array('selector'=>'#main h2 a', 'node'=>'text'),
   'link'		 => array('selector'=>'#main h2 a', 'node'=>'@href'),
   'description' => array('selector'=>'#main h2 span', 'node'=>'text'),
  ),
  array(
   'charset'	 => 'utf-8', 'channel'	 => '千葉市：千葉市動物公園ニュース',
   'url'		 => 'http://www.city.chiba.jp/zoo/news/news_top.html',
   'title'		 => array('selector'=>'table[height=103] a', 'node'=>'text'),
   'link'		 => array('selector'=>'table[height=103] a', 'node'=>'@href'),
   'description' => array('selector'=>'table[height=103] p', 'node'=>'text'),
  ),
/* // Template:
  array(
   'charset'	 => 'utf-8', 'channel'	 => '',
   'url'		 => '%s',
   'title'		 => array('selector'=>'', 'node'=>'text'),
   'link'		 => array('selector'=>'', 'node'=>'@href'),
   'description' => array('selector'=>'', 'node'=>'text'),
  ), // */
 );
 return $this;
}

}
// END OF CLASS: HTMLtoRSS_SiteData_SearchEngines
