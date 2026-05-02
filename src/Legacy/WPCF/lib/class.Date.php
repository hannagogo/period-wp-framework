<?php 
require_once("CustomFunctions.php");
require_once('class.PublicHoliday.php'); 

class Date {

var $data = array();
var $holiday = null;


public function __construct($atts=NULL) {
 $this->holiday = new PublicHoliday;

 if (empty($atts) || is_numeric($atts) || is_string($atts) ) { // Accepts UNIX Epoch in secs.
  $e = empty($atts) ? time() : intval($atts);
  $atts = array(); 
  $atts['year'] = date('Y', $e);
  $atts['monthnum'] = date('n', $e);
  $atts['day'] = date('j', $e);
 }

 $a = $this->parse_date_args( $atts );
 $this->set_date( $a['year'], $a['monthnum'], $a['day'] );
 return $this
 ;
}

public function datestr($y=NULL,$m=NULL,$d=NULL) { 
 $k2p = array('y'=>'year', 'm'=>'mon', 'd'=>'day');
 foreach(array('y', 'm', 'd') as $k) {
  if (empty(${$k})) ${$k} = $this->{$k2p[$k]}();
 }
 return implode('-', array($y,$m,$d));
}

public function ndays() {
 return $this->data['ndays'] = $this->mdays(array('year'=>$this->year(), 'monthnum'=>$this->mon()));
}

public function days_of_month($d=NULL) { return $this->mdays($d); }
public function mdays($d = array()){
 // Calculates and returns number of days in a month
 // THOUGH, date('t', time()) returns the number of days in the month!
 $d = $this->parse_date_args(func_get_args());
 $mdays = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
 !isset($d['monthnum']) && $d['monthnum'] = $this->mon();
 !isset($d['year']) && $d['year'] = $this->year();
 $ndays = $mdays[$d['monthnum']-1];
 $d['monthnum'] == 2 && $this->isLeap($d['year']) && $ndays++;
 return $ndays;
}

public function year($set=NULL) {
 if ($set) { $this->set_date($set,$this->mon(),$this->day()); return $this; }
 return isset($this->data['year']) ? $this->data['year'] : NULL ;
}
public function mon($set=NULL) {
 if ($set) { $this->set_date($this->year(),$set,$this->day()); return $this; }
 return isset($this->data['monthnum']) ? $this->data['monthnum'] : NULL ;
}
public function day($set=NULL) {
 if ($set) { $this->set_date($this->year(),$this->mon(),$set); return $this; }
 return isset($this->data['day']) ? $this->data['day'] : NULL ;
}
public function time($set=NULL) {
 if ($set) { $this->set_time($set); return $this; }
 return isset($this->data['time']) ? $this->data['time'] : NULL ;
}
public function next_month($format=NULL) {
 return calc_month($this->year(),$this->mon(),$this->day(), 1, $format);
}
public function previous_month($format=NULL) {
 return calc_month($this->year(),$this->mon(),$this->day(), -1, $format);
}
public function previous_months_year() {
 $mon = $this->mon();
 $year = $this->year();
 return $mon-- ? $year : $year - 1;
}
public function next_months_year() {
 $mon = $this->mon();
 $year = $this->year();
 return $mon == 12 ? $year + 1 : $year;
}
public function isLeap($year = NULL) {
 !$year && $year = $this->year();
 if (
  (($year % 4) == 0 && ($year % 100) != 0) || (($year % 400) == 0)
 ) return true;
 return false;
}

public function is_holiday($y=NULL,$m=NULL,$d=NULL) {
 $a = $this->parse_date_args(func_get_args());
 return $this->holiday->is_holiday($a['year'],$a['monthnum'],$a['day']);
}

public function day_of_week($d=NULL) { $this->wday($d); }
public function wday($d=NULL) {
 /* returned value: 0 = SUN, 1 = MON, ... , 6 = SAT */
 $d = $this->parse_date_args(func_get_args());
 list($year, $mon, $mday) = array($d['year'], $d['monthnum'], $d['day']);
 
 if ($mon == 1 || $mon == 2) { $year--; $mon += 12; }

 $w = (int) ($year + (int)($year / 4) - (int)($year / 100) + (int)($year / 400)
    + (int) ((13 * $mon + 8) / 5) + $mday) % 7;
 return $w;
}

public function get_calendar_wdays($start_of_week=0, $array=array(0,1,2,3,4,5,6)) {
 $array = array_splice($array, 0, 7);
 return array_merge(array_splice($array, $start_of_week), $array);
}

public function get_calendar_index($start_of_week=0) {
 $start_of_week = $start_of_week % 7;
 $days = array_merge(array(0),range(1,$this->mdays()));
 $wd = range(0,6);
 $index = array();
 $first_day = $this->wday(array('day'=>1));
 $start = $first_day - $start_of_week;
 if ($start < 0) $start += 7;
 if ($start > 0) {
  for ($i = 0; $i < $start; $i++) { $index[] = NULL; }
 }
 for ($i = 1; $i < count($days); $i++) { $index[] = $days[$i]; }
 return $index;
}
/* ////// Setting Utilities ////// */
function parse_date_args() {
 /* //
 Parses implicitly passed arguments.
 Accepts any of these below:
  parse_date_args(2013,4,1);
  parse_date_args(array(2014,1,1));
  parse_date_args(array('year'=>2013, 'monthnum'=>2, 'day'=>28)); // Represents 28th of February, 2014 A.D. (NOT March)
 // */
 $args = func_get_args();

 while(isset($args[0]) && is_array($args[0])) { $args = $args[0]; }
 $self_args = parse_args(
  array('year'=>date('Y'),     'monthnum'=>date('n'),    'day'=>date('j')),
  array('year'=>$this->year(), 'monthnum'=>$this->mon(), 'day'=>$this->day())
 ); 
 if (empty($args)) return $self_args;
 if (is_hash($args)) return parse_args($self_args, $args);
 return parse_args($self_args, array(
  'year'     => isset($args[0]) && (is_string($args[0]) || is_numeric($args[0])) ? $args[0] : NULL,
  'monthnum' => isset($args[1]) && (is_string($args[1]) || is_numeric($args[1])) ? $args[1] : NULL,
  'day'      => isset($args[2]) && (is_string($args[2]) || is_numeric($args[2])) ? $args[2] : NULL,
 ) );
}

public function time_to_date($time=NULL,$hash=FALSE) {
 if ($time === NULL) $time = $this->time();
 if (is_array($time)) {
  $time = array_flatten($time);
  $time = $time[0];
 }
 list($y,$m,$d) = array(date('Y',$time), date('n',$time), date('j',$time));
 return $hash ? array('year'=>$y, 'monthnum'=>$m, 'day'=>$d) : array($y,$m,$d) ;
}

public function set_date($y=null,$m=null,$d=null) {
 $date = $this->parse_date_args(func_get_args());
 list($this->data['year'], $this->data['monthnum'], $this->data['day'])
    = array($date['year'], $date['monthnum'],       $date['day']);
 $this->data['time'] = strtotime($this->datestr());
 $this->ndays();
 $this->holiday->set($this->year(),$this->mon(),$this->day());
 return $this;
}

public function set_time($time=null) {
 if ($time === null && $time = strtotime($this->datestr())) $this->data['time'] = $time;
 else $this->data['time'] = $time;
 list($this->data['year'], $this->data['monthnum'], $this->data['day']) = array(
  (int) date('Y', $time), (int) date('n', $time), (int) date('j', $time)
 );
 $this->ndays();
 return $this;
}

}