<?php
class HTMLtoRSS_SiteData_Misc extends HTMLtoRSS_SiteData {
function setup_sitedata($data = null) {
 $this->sites = array(
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'おそうじ本舗 お知らせ',
   'url'		 => 'http://www.osoujihonpo.com/',
   'title'		 => array('selector'=>'#info-osouji dd a', 'node'=>'text'),
   'link'		 => array('selector'=>'#info-osouji dd a', 'node'=>'@href'),
  ), // */
  array(
   'charset'	 => 'utf-8', 'channel'	 => 'SHUFOO - 263-0024',
   'url'		 => 'http://www.shufoo.net/shxweb/site/chirashiList.do?lat=35.6319408&lng=140.1084169&&type=all&sort=new',
   'title'		 => array('selector'=>'.list-thumb dt a', 'node'=>'text'),
   'link'		 => array('selector'=>'.list-thumb dt a', 'node'=>'@href'),
   'description' => array('selector'=>'.list-thumb .image', 'node'=>'@href'),
  ), // */
 );
 return $this;
}

}
// END OF CLASS: HTMLtoRSS_SiteData_SearchEngines
