<?php
require_once 'CustomFunctions.php';
require_once 'ClassTemplate.php';
require_once 'class.HashAccessor.php';
class HTTPCookie extends ClassTemplate {

private $cookie = NULL;

function __construct($param=null) {
 $this->cookie = new HashAccessor(array());
 foreach ((array) $param as $k=>$v) {
  if (is_string($v)) $this->cookie($k,$v);
 }
}

function request_header() {
 $fields = func_get_args();
 if (is_array($fields[0])) $fields = $fields[0];
 if (empty($fields)) $fields = $this->keys();
 $p = array();
 foreach ($fields as $f) {
  $p[] = implode('=', array($f, $this->cookie($f)));
 }
 $s = implode('; ', $p);
 if ($s) $s = 'Cookie: ' . $s . CRLF;
 return $s;
}

function parse_header($header) {
 foreach ((array) $header as $h) {
  list ($f, $s) = preg_split('/: ?/', $h);
  if ($f == 'Set-Cookie') {
   foreach (preg_split('/; ?/', $s) as $p) {
    list ($k, $v) = explode('=', $p);
    $this->cookie($k, $v);
   }
  }
 }
}

function cookie($key=null, $value=null) {
 if ($key) {
  return $this->cookie->param($key, $value);
 }
 return $this->cookie->param();
}

function keys() {
 return array_keys($this->cookie());
}

} // END OF CLASS HTTPCookie