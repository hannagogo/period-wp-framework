<?php
class HTMLtoRSS_SiteData_NewsSearch extends HTMLtoRSS_SiteData {

function setup_sitedata($data = null) {
 $this->sites = array(
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'Yahoo!ニュース - %s',
   'url'		 => 'http://nsearch.yahoo.co.jp/bin/query?ei=UTF-8&p=%s',
   'title'		 => array('selector'=>'#NSm .l .t a', 'node'=>'text'),
   'link'		 => array('selector'=>'#NSm .l .t a', 'node'=>'@href'),
   'description' => array('selector'=>'#NSm .l .txt .a', 'node'=>'text'),
  ),
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'Bingニュース - %s',
   'url'		 => 'http://www.bing.com/news/search?q=%s',
   'title'		 => array('selector'=>'.ResultsArea .sn_hd a', 'node'=>'text'),
   'link'		 => array('selector'=>'.ResultsArea .sn_hd a', 'node'=>'@href'),
   'description' => array('selector'=>'.ResultsArea .sn_snip', 'node'=>'text'),
  ),
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'NHKニュース - %s',
   'url'		 => 'http://cgi2.nhk.or.jp/news/nsearch/query.cgi?qt=%s',
   'title'		 => array('selector'=>'#disp-area .result-area .result-title a', 'node'=>'text'),
   'link'		 => array('selector'=>'#disp-area .result-area .result-title a', 'node'=>'@href'),
   'description' => array('selector'=>'#disp-area .result-area .result-lead:first', 'node'=>'text'),
  ),
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'Infoseek ニュース - %s',
   'url'		 => 'http://news.infoseek.co.jp/search?q=%s&type=article',
   'title'		 => array('selector'=>'.searchResultArea .title a', 'node'=>'text'),
   'link'		 => array('selector'=>'.searchResultArea .title a', 'node'=>'@href'),
   'description' => array('selector'=>'.searchResultArea .text p', 'node'=>'text'),
  ),
  array(
   'charset'	 => 'euc-jp', 'channel'	 => 'Livedoor ニュース - %s',
   'url'		 => 'http://news.livedoor.com/search/article/?word=%s',
   'title'		 => array('selector'=>'#article-list ul li a', 'node'=>'text'),
   'link'		 => array('selector'=>'#article-list ul li a', 'node'=>'@href'),
  ),
  array(
   'charset'	 => 'shift_jis', 'channel'	 => 'Excite ニュース - %s',
   'url'		 => 'http://www.excite.co.jp/search.gw?src=%1$s&select=hourly&search=%1$s&target=hourly&charset=utf8',
   'title'		 => array('selector'=>'#resultsBody .hit a', 'node'=>'text'),
   'link'		 => array('selector'=>'#resultsBody .hit a', 'node'=>'@href'),
   'description' => array('selector'=>'#resultsBody .hit table', 'node'=>'text'),
   'exclude'	 => array(0,1,2,3)
  ),
  array(
   'charset'	 => 'shift_jis', 'channel'	 => 'Nifty ニュース - %s',
   'url'		 => 'http://news.nifty.com/cs/catalog/news_pssearch/search/1.htm?adwkey=q&adwid=3&q=%s',
   'title'		 => array('selector'=>'.newsList a', 'node'=>'text'),
   'link'		 => array('selector'=>'.newsList a', 'node'=>'@href'),
  ),
  array(
   'charset'	 => 'euc-jp', 'channel'	 => '時事ドットコム - %s',
   'url'		 => 'http://www.jiji.com/jc/z?key=%s',
   'title'		 => array('selector'=>'#search-result ul li a', 'node'=>'text'),
   'link'		 => array('selector'=>'#search-result ul li a', 'node'=>'@href'),
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
// END OF CLASS: HTMLtoRSS_SiteData_NewsSearch
