<?php
/* //
Basic Usage

global $schedule
;
$schedule = new EventSchedule(array(
 'meta_box_args' => array(
  'date'=>array('default_title_value' => 1),
  'start_time' => array('timepicker_options' =>'{hour:18,minute:30}','label'=>'START','script'=>'
$("#schedule_info_start_time_box").insertAfter($("#schedule_info_open_box"));'
  ),
  'end_time' => array('script'=>'$("#schedule_info_end_time_box").hide();'),
 ),
 'additional_meta_box_fields'=> array(
  'event_title'	 =>array('label'=>'Event Title', 'type'=>'textarea', 'rows'=>3,),
  'charge'		 =>array('label'=>'Price', 'type'=>'text'),
  'notes'		 =>array('label'=>'Notes', 'type'=>'textarea', 'rows'=>2 ),
 )
) );

echo $schedule->navigation()
echo $schedule->calendar(array('_auto_format_script'=>FALSE, 'start_of_week'=>1));

////// in functions.php //////
function is_schedule($post=NULL) {
 return apply_filters('WPCF_Is_PostType', 'scheduled-events', $post);
}

////// in index.php ///////
global $schedule
;
if (is_schedule()) {
 echo $schedule->navigation();
 echo $schedule->calendar();
}
else {
 if have_posts() : while have_posts() :
  the_post();
  the_content();
  // :
  // some actions
 endwhile; endif;
}
// */

class EventSchedule extends ClassTemplate {
var $param = array();
var $date = NULL;
var $now = NULL;
var $post_type = NULL;
var $meta_box_fields = array();
var $holiday = null;

var $wdays = array('SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT');
var $wdays_classes = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
var $monthnames = array('JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
var $year_var_name = 'custom_calendar_year';
var $month_var_name = 'custom_calendar_month';
var $day_var_name = 'custom_calendar_day';
var $week_var_name = 'custom_calendar_week';
var $base_meta_box_name = 'event_info';

var $wp_query = NULL;
var $posts = array();
var $calendar_count = 0;
var $calendar_args = array(); // is for temporary use

var $calendar_leading_pad = 0;
var $calendar_trailing_pad = 0;
var $prefix = NULL;
var $_in_calendar_loop = FALSE;
var $calendar_loop_datenum = 0;

function __construct($param = array()) {
 global $wp_rewrite, $wp_query, $custom_language_domain, $wp_custom_functions ;
 require_once('class.PublicHoliday.php'); 
 require_once('class.Date.php'); 
 $this->set_values();
 $this->_atts = $param;
 $this->holiday = new PublicHoliday;
 $this->date = new Date;
 $this->now  = new Date;
 $this->prefix = WPCF_PREFIX . __CLASS__ . '_';
 
 apply_filters('WPCF_Set_CSS_Handle', 'event-schedule');
// add_action(WPCF_PREFIX.'EventSchedule_Init', NULL, 1, 1);
 if (isset($param['url_base']) && !$param['url_base'] && $param['url_base'] !== 0) unset($param['url_base']);

 $param_tmp =  $wp_custom_functions->parse_args( array(
  'post_type_args' => array(),
  'meta_box_args'  => array(),
  'url_base'	   => 'schedule',
  'calendar_args'  => array(),
  'meta_box_field_names' => array_keys($this->meta_box_fields),
 ), $param);
 !is_array($param_tmp['post_type_args']) && $param['post_type_args'] = (array) $param['post_type_args'];
 !is_array($param_tmp['meta_box_args']) && $param['meta_box_args'] = (array) $param['meta_box_args'];

 $_pta_tmp = $wp_custom_functions->parse_args(
  array(
   'name'			 => 'scheduled-events',
   'label'			 => __('Schedule', $custom_language_domain),
   'singular_name'	 => __('Scheduled Event', $custom_language_domain),
   'taxonomy'		 => NULL,
  ), $param_tmp['post_type_args']
 );

 $this->param( $wp_custom_functions->parse_args(array(
  'url_base'		 => $param_tmp['url_base'],
  'post_type_args'	 => array(),
  'posts_per_page'	 => -1,
  'meta_box_args'	 => array(),
  'meta_box_field_names' => $param_tmp['meta_box_field_names'],
  'additional_meta_box_fields'=> array(),
  'meta_boxes'		 => NULL,
  'base_meta_box_name' => sprintf(__('%s Information', $custom_language_domain), array_value($this->param('post_type_args'), 'label')),
  'start_of_week'	 => get_option('start_of_week'),
  'time' 			 => NULL,
  'year' 			 => NULL,
  'monthnum'		 => NULL,
  'day' 			 => NULL,
  'yearmonth_format' => '%1$s/%2$s', // Used in vsprintf; array(yyyy, m, d, [monthname])
  'monthnames'		 => $this->monthnames,
  'calendar_args'	 => $this->_calendar_args($param_tmp['calendar_args']),
//'rewrite_rule_base' => 'pagename=' . preg_replace('|^/?([^/]+)?/?.*?$|', '$1', $param_tmp['url_base'])
  'rewrite_rule_base'=> 'post_type=' . $_pta_tmp['name'],
  'title_format'	 => '%1$s : %2$s',
  'single_event'	 => FALSE,
  'wp_title_basename' => $_pta_tmp['label'],
  'post_orderby'	 => 'date',
  'post_order'		 => 'ASC',
 ), $param) );

 $this->monthnames = $this->param('monthnames');
 
 // Set Time
 if ($this->param('time')) { $this->set_time($this->param('time')); }
 else {
  $time_params = $wp_custom_functions->parse_args( array(
   'year' => date('Y'),
   'monthnum' => date('n'),
   'day' => date('j')
  ), $this->param() );
  $this->set_date($time_params['year'], $time_params['monthnum'], $time_params['day']);
 }
 
 // Meta Boxes
  // base meta box
 if ( !in_array('date', $this->param('meta_box_field_names')) ) {
  $this->param('meta_box_field_names', array_merge(array('date'), $this->param('meta_box_field_names') ) );
 }
 if ( !in_array('post_time', $this->param('meta_box_field_names')) ) {
  $this->param('meta_box_field_names', array_merge($this->param('meta_box_field_names'), array('post_time') ) );
 }
 $meta_box_fields = array();
 $additional_fields = (array) $this->param('additional_meta_box_fields');
 $field_names = array_unique(array_merge($this->param('meta_box_field_names'), array_keys($additional_fields)));
 foreach ($field_names as $f) {
  if (isset($this->meta_box_fields[$f])) {
   if (!isset($param['meta_box_args'][$f])) {
    $param['meta_box_args'][$f] = array();
   }
   $meta_box_fields[$f] = $wp_custom_functions->parse_args($this->meta_box_fields[$f], $param['meta_box_args'][$f]);
  }
  else {
   if (isset($additional_fields[$f])) {
   $meta_box_fields[$f] = $additional_fields[$f];
   }
  }
 }
 
 $meta_boxes = array();
 $meta_boxes[] = array(
   'name' => $this->base_meta_box_name,
   'title' => $this->param('base_meta_box_name'),
   'fields' => $meta_box_fields,
 );
  // additional multiple meta boxes
 if (is_hash($this->param('meta_boxes'))) {
  $meta_boxes[] = $this->param('meta_boxes');
 }
 else {
  $meta_boxes = array_merge($meta_boxes, (array) $this->param('meta_boxes'));
 }

 // PostType
 $custom_rules = array();
 if ($this->param('single_event')) {
  $custom_rules[''.$_pta_tmp['name'].'/(\d+)(?:/.+?/?)?(?:\x3f.*?)?$'] = 'index.php?p=$matches[1]&'.$this->param('rewrite_rule_base');
 }

 $custom_rules[$this->param('url_base') . '(?:/(\d{4}/?)?(?:/(\d{1,2})/?)?(?:/(\d{1,2})/?)?)?/?(\x3f.*?)?$'] = 
  'index.php?' . implode('&', array(
    $this->year_var_name.'=$matches[1]',
    $this->month_var_name.'=$matches[2]',
    $this->day_var_name.'=$matches[3]',
//    $this->week_var_name.'=$matches[4]',
    $this->param('rewrite_rule_base')
   ) )
 ;
 $this->param('post_type_args', $wp_custom_functions->parse_args(
  array(
   'name' => $_pta_tmp['name'],
   'label' => $_pta_tmp['label'],
   'singular_name' => $_pta_tmp['singular_name'],
   'hierarchical' => false,
   'supports' => array('title', 'editor', 'thumbnail', 'author'),
   'has_archive' => true,
   'meta_boxes' => $meta_boxes,
   'common_meta_box' => array(
    'post_settings'=>array('fields'=>array('css','jquerycode','js')),
   ),
   'taxonomy' => null,
   'custom_rules' => $custom_rules,
  ), $this->param('post_type_args')
 ) );
 $_pt =  new PostType( $this->param('post_type_args') ) ;
 $this->post_type = wpcf_add_post_type($_pt);
 enable_query_vars(array($this->year_var_name, $this->month_var_name, $this->day_var_name));
 add_action('wp', array(&$this, 'setup_posts'));
 do_action($this->prefix.'Init');
 add_filter($this->prefix.'Calendar_Header', array(&$this, '_calendar_header'), 10, 1);
 add_filter($this->prefix.'Calendar_Footer', array(&$this, '_calendar_footer'), 10, 1);
 add_filter($this->prefix.'Schedule_Header', array(&$this, '_schedule_header'), 10, 1);
 add_filter($this->prefix.'Schedule_Footer', array(&$this, '_schedule_footer'), 10, 1);
 add_filter($this->prefix.'Schedule_Day_Posts', array(&$this, 'schedule_day_posts'), 10, 1);
 add_filter(WPCF_PREFIX.'Is_EventSchedule', array(&$this, '_is_event_schedule'), 10, 1);

 add_filter($this->prefix.'Format_Event', function($content) { return $content; }, 10, 1);
 add_filter($this->prefix.'Format_Event', array(&$this, 'add_edit_post_link'), 9999);
 add_filter($this->prefix.'Admin_Script', array(&$this, 'admin_script'), 9999);
 add_filter('the_content', array(&$this, 'format_event'));
 add_filter('wp_title',  array(&$this, 'wp_title'));
 add_action($this->prefix.'Void_Query', array(&$this, 'void_query'), 10);
 add_action('WPCF', array(&$this, 'redirect_post_type_archive_page'), 10);
 add_action('WPCF', array(&$this, 'single_post_query'), 10);
// add_action('wp_head', array(&$this, 'void_query'));

 add_filter( 'wp_insert_post_data', array(&$this, 'force_future_to_publish'), '10', '2');
 return $this;
} // end of __contruct



function setup_posts($atts=NULL) {
 global $wp_rewrite
 ;
 $WPOBJ = NULL;
 if ($atts instanceof WP) { // Called by hook wp set by __construct
  $WPOBJ = $atts;
  $atts = NULL;
 }
 if ($atts !== NULL) {
  if ( is_array($atts) && count($atts) > 0 && isset($atts['time']) ) { // If single array/hash passed to this method
   $atts = $atts + array( // Making y,m,d array. Value of 'time' prior to the given date values
    'year'		 => date('Y',$atts['time']),
    'monthnum'	 => date('n',$atts['time']),
    'day'		 => date('j',$atts['time']),
   );
  }
  else { // First arg is NOT array
   $atts = $this->date->parse_date_args(func_get_args());
  }
 }
 else { // parse query_vars
  $atts = $this->date->parse_date_args(
   (($y = get_query_var($this->year_var_name)) ? $y:NULL),
   (($m = get_query_var($this->month_var_name))? $m:NULL),
   (($d = get_query_var($this->day_var_name))  ? $d:NULL) 
  );
 }
 $this->set_date($atts['year'],$atts['monthnum'],$atts['day']);
 $this->get_event_posts($this->param('calendar_args'));
 return $WPOBJ;
}


function get_event_posts($args = array()) {
 global $wpdb;
 /* // needs dev. when using post_range
 // REF: https://wpdocs.osdn.jp/カスタムクエリ#.E3.82.AD.E3.83.BC.E3.83.AF.E3.83.BC.E3.83.89.E6.A4.9C.E7.B4.A2.E3.83.97.E3.83.A9.E3.82.B0.E3.82.A4.E3.83.B3.E3.83.86.E3.83.BC.E3.83.96.E3.83.AB
 add_filter(
  'posts_where',
  function($where="") {
   $where .= ' AND post_date >= ' . date('Y-m-d', $this->post_range_start) . ' AND post_date <= '. date('Y-m-d', $this->post_range_end)
   ;
   return $where;
  )
 );
*/
 $db_query =
  "SELECT * FROM " . $wpdb->posts .
  " WHERE post_date >= '" . sprintf('%4d-%02d-01T00:00:00', $this->year(), $this->mon()) . "'" .
  " AND post_date   <= '" . sprintf('%4d-%02d-%02dT23:59:59', $this->year(), $this->mon(), $this->ndays()) . "'" .
  " AND post_type = '" . $this->post_type->name . "'" .
  " AND (post_status = 'publish' OR post_status = 'future')"
 ;
 foreach ($wpdb->get_results($db_query) as $p) {
  $event_date = apply_filters('WPCF_Get_Post_Meta', $p->ID, 'date', 1);
  if (empty($event_date)) continue;
  $date = getdate(strtotime($p->post_date));
  !isset($this->posts[$date['mday']]) && $this->posts[$date['mday']] = array();
  $this->posts[$date['mday']][] = $p;
 }
 $this->events_per_date = 0;
 foreach ($this->posts as $posts) {
  if (($n=count($posts)) > $this->events_per_date) $this->events_per_date = $n;
 }
 return $this;
}


function query_posts($args = NULL, $_in_main_loop = false) {
// $this->get_event_posts();
 $post_ids = array(); 
 foreach ($this->posts as $posts) {
  foreach ($posts as $p) {
   $post_ids[] = $p->ID;
  }
 }

 $this->wp_query = new WP_Query(array_merge(array(
  'post_type'	 => $this->post_type->name,
  'posts_per_page'=> -1,
  'post__in'	 => empty( $post_ids ) ? 1 : $post_ids,
  'post_status'	 => array('publish','future'),
  'orderby'		 => $this->param('post_orderby'),
  'order'		 => $this->param('post_order'),
 ), (array) $args));
 if ($_in_main_loop) {
  query_posts($this->wp_query->query);
 }
 else {
  $this->wp_query->query_posts();
 }
 return $this->wp_query
 ;
}


function redirect_post_type_archive_page() {
 $current_url = apply_filters('WPCF_Current_URL',NULL);

 if (
  preg_match('/^'.preg_replace('/\x2f/', '\x2f', get_post_type_archive_link($this->post_type->name)).'/', $current_url)
  &&
  !$this->param('single_event')
 ) {
  wp_redirect(home_url(trailingslashit($this->param('url_base'))));
  exit();
 }
}


function single_post_query() {
 global $wp_query
 ;
 $q = new WP_Query(array_merge($wp_query->query, array('post_status'=>'publish,future')));
 if ($q->is_singular() && $this->param('single_event') && (isset($wp_query->query['post_type']) && $wp_query->query['post_type']) == $this->post_type->name) {
  query_posts($q->query);
 }
}


function void_query() {
 global $wpdb;
 query_posts('p='.$wpdb->insert_id);
}


function wp_title($content) {
 if (apply_filters(WPCF_PREFIX.'Is_EventSchedule', NULL) && !$this->param('single_event')) {
  $ym = sprintf( $this->param('yearmonth_format'), $this->year(), $this->mon(), $this->monthnames[$this->mon()-1] );
  $title = sprintf(
   $this->param('title_format'),
   $this->param('wp_title_basename'),
   $ym
  );
  return $title;
 }
 return $content;
}

function get_post_range($start_or_end = 'start', $range=null) {
 if (!$range) $range = $this->param('post_range');
 if ($start_or_end !== 'start') $start_or_end = 'end';
 if (!(count($range) < 2)) return;
 list($range_start,$range_end) = ($range[0] >= $range[1]) ? array($range[1],$range[0]) : $range;
 return ${'range_'.$start_or_end};
}
function post_range_start($range=null) { return $this->get_post_range('start',$range); }
function post_range_end($range=null) { return $this->get_post_range('end',$range); }



function _calendar_args($args = NULL) {
/* //
Function _calendar_args :
This function makes arguments for calendar outputs.
Each parameters defaults to object default i.e. value of $param class property.
It also set $calendar_args property.
// */

 global $custom_language_domain, $wp_custom_functions
 ;
 if (isset($args['prev_format'])) $args['previous_format'] = $args['prev_format'];
 if (isset($args['container'])) $args['container'] = $args['container'] == 'table' ? 'table' : 'div';

 $args = $wp_custom_functions->parse_args( array(
  'container'	 => 'div',	 // or 'div'
  'type'		 => 'calendar',	 // or 'schedule'
  'row'			 => 'week',		 // or 'day'
  'previous_format'	 => '&lt;&lt; %s',
  'next_format'		 => '%s &gt;&gt;',
  'yearmonth_format' => '%1$s/%2$s', // '%1$s年%2$s月', '%2$s, %1$s', etc.
  'wday_format'	 => '(%s)',
  'date_number_format'=>'%s',
  'id'			 => '',
  'class'		 => '',
  'monthnames'	 => $this->monthnames,
  'wdays'		 => $this->wdays,
  'show_date_number' => TRUE,
  'sort_key'	 => NULL,
  'start_of_week'=> $this->param('start_of_week'),
  'morelinktext' => __('See Details', $custom_language_domain),
  'calendar_count' => $this->calendar_count,
  'header'		 => '',
  'calendar_name'=> $this->prefix . 'Calendar',
  'tr_class'	 => 'table_tr',
  'td_class'	 => 'table_td',
  'td_content_class' => 'table_td_content',
  'div_table_class'	 => 'table',
  'div_tr_class'	 => 'table_row',
  'div_td_class'	 => 'table_col',
  'div_td_content_class'=> 'table_col_content',
  'meta_keys' 	 => array('event_title', 'start_time'),
  'script'		 => '' , //isset($args['container']) && $args['container'] == 'table' ? FALSE : TRUE,
  '_auto_format_script' => TRUE,
 ), $args ? $args : $this->param('calendar_args') );

 $this->calendar_args = $args;
 $this->set_calendar_variables();
 foreach ((array) $args['meta_keys'] as $k) {
  add_filter($this->prefix.'Format_Meta_Value_'.$k, function($content) { return $content; });
 }
 return $args;
}


function cal_args($args=NULL) {
/* //
This is the lighter version of _calendar_args.
It returns class property $calendar_args if $args is NULL.
if $args passed is passed to and parsed by _calendar_args. Look at below :)
// */
 return $args === NULL ? $this->calendar_args : $this->_calendar_args($args);
}



function sort_by_key($args=NULL) {
 $args = $this->cal_args($args);
 if ($sort_key = $args['sort_key']) {
  foreach ($this->posts as &$posts) {
   if (count($posts) >= 2) {
    foreach ($posts as $i=>$p) {
	 $sort_values[$i] = strtotime(apply_filters('WPCF_Get_Post_Meta', $p->ID,$sort_key,1));
    }
    array_multisort($posts, $sort_values);
   }
  }
 }
 return $this;
}

function &get_event($day=null) {
 !$day && $day = $this->day();
 return $this->posts[$day];
}



/* ////// Display Methods ////// */
function _build_calendar_data($args=NULL, $data=NULL) { 
 global $wp_custom_functions, $post
 ;
 $_post_orig = $post;
 $this->calendar_count++;
 $args = $this->cal_args($args);
 $skel = $this->_calendar_skel($args);
 $type_calendar = $args['type'] == 'calendar';

 $pad = $type_calendar ? $this->calendar_leading_pad : 0;
 if (empty($data)) {
  $data = $this->posts;
 }
 $data = (array) $data;

 $values = array();
 if ($type_calendar && count($data) <= $this->mdays()) {
  $values = array_merge(array_map(function($i){ return ""; }, range(1,$pad)), $values);
 }

 for ($i = $pad ; $i < $pad + $this->mdays() + 7 - ( ($pad + $this->mdays()) % 7 ); $i++) {
  $the_content = '';
  $datenum = $i - $pad + 1;
  if (isset($data[$datenum])) {
   if ($type_calendar) {
    foreach ($data[$datenum] as $j=>$p) {
     $post = $p;
     $cell_classes = array(
	  $this->make_calendar_id().'_event_post',
      $args['calendar_name'].'_day_event',
      $args['calendar_name'].'_day_event_'.($j+1),
	  $args['calendar_name'].'_day_event_'.($j+1%2 ? 'odd':'even'),
     );
     if ($j+1 == 1) {
      $cell_classes[] = $args['calendar_name'].'_day_event_first';
      $cell_classes[] = $args['calendar_name'].'_day_'.($j+1).'_event_first';
     }
     if ($j+1 == count($data[$datenum])) {
      $cell_classes[] = $args['calendar_name'].'_day_event_last';
      $cell_classes[] = $args['calendar_name'].'_day_'.($j+1).'_event_last';
     }
     $the_content .= createHTMLElement('div', array(
      'id'	 => $this->make_calendar_id().'_event_post-'.$post->ID,
      'class' => $cell_classes,
     ), apply_filters( WPCF_PREFIX.'The_Content', $post ) ); 
    }
   }
   else {
	$the_content = apply_filters(WPCF_PREFIX.'Schedule_Day_Posts', $data[$datenum]);
   }
  }
  $values[$i] = $the_content
  ;
 }
 $post = $_post_orig;

 return $values;
}



function calendar($args=NULL, $data=NULL) {
 $args = $this->_calendar_args($args);
 $script = 'var calendar = $("#'.$this->make_calendar_id().'")' . LF
 ;
 if ($args['_auto_format_script']) {
  if ($args['container'] == 'div') {
   $script .= '
$(".week", calendar).each(function(){ $(".table_col_content",this).sameHeight() })
$(".table_col", calendar).each(function(){ var col = $(this); col.width(col.width()+1) })' . LF;
  }
  else {
   $script .= '$(".week", calendar).each(function(){ $(".table_td_content",this).sameHeight() })' . LF;
  } 
 }
 if ($args['script']) {
  $script .= $args['script'] . LF;
 }
 $script = apply_filters('CF_Wrap_JavaScript', $script, array('jquery'=>TRUE));
 return 
    apply_filters($this->prefix.'Calendar_Header', NULL)
  . vsprintf($this->_calendar_skel($args), $this->_build_calendar_data($args, $data))
  . apply_filters($this->prefix.'Calendar_Footer', NULL)
  . $script
 ;
}



function schedule($args=NULL, $data=NULL) {
 $args = $this->_calendar_args($args);
 return 
    apply_filters($this->prefix.'Schedule_Header', NULL)
  . vsprintf($this->_calendar_skel($args), $this->_build_calendar_data($args, $data))
  . apply_filters($this->prefix.'Schedule_Footer', NULL)
 ;
}



function schedule_day_posts($day_posts) {
 global $post;
 $_post_orig = $post;
 if (empty($day_posts)) return $day_posts;
 
 if (!isset($day_posts['columns']) ) {
  $day_posts['columns'] = array();
 }
 $index = array();
 foreach (array_keys($day_posts) as $k) {
  if (!is_numeric($k)) continue;
  $index[] = $k;
 }
 $col_skel = array(
  'container' => $args['container'] == 'table' ? 'td':'div',
  'atts' => NULL,
  'content' => ''
 );
 foreach ($index as $k) {
  $post = $day_posts[$k];
  $col_data = $col_skel;
  $col_data['content'] = apply_filters(WPCF_PREFIX.'The_Content', $post);
  $day_posts['columns'][] = $col_data;
 }
 
 $post = $_post_orig ;
 return $day_posts;
}


function navigation_calendar($args=NULL) {
 $args = $this->_calendar_args($args);
 $calendar_data = array();
 $ndays = $this->ndays();
 if ($this->calendar_leading_pad) {
  foreach (range(0, $this->calendar_leading_pad -1) as $_p) { $calendar_data[$_p] = ''; }
 }
 foreach (range(1,$ndays) as $i) {
  $datenum = sprintf('%02d', $i);
  if (!empty($this->posts[$i])) {
   $datenum = apply_filters(
	'CF_HTML',
    'a',
    array(
     'href'=>sprintf('#post-%s', $this->posts[$i][0]->ID),
     'class'=>'navigation_calendar_date_link'
    ),
    $datenum
   );
  }
  $calendar_data[$i+$this->calendar_leading_pad-1] = $datenum;
 }

 foreach (range(count($calendar_data), count($calendar_data) + $this->calendar_trailing_pad) as $_p) { $calendar_data[$_p] = ''; }

 return
    apply_filters($this->prefix.'Calendar_Header', NULL)
   . vsprintf($this->_calendar_skel($args), $calendar_data)
// . ($this->_calendar_skel($args))
  . apply_filters($this->prefix.'Calendar_Footer', NULL)
 ;
}


function calendar_loop_date() {
 if ($this->_in_calendar_loop) {
  if ($this->_in_calendar_loop === TRUE) {
   $monthnum = $this->date->mon();
   $year = $this->year();
  }
  else if ($this->_in_calendar_loop == 'previous') {
   $monthnum = date('n',$this->date->previous_month());
   $year = date('Y',$this->date->previous_month());
  }
  else if ($this->_in_calendar_loop == 'next') {
   $monthnum = date('n',$this->date->next_month());
   $year = date('Y',$this->date->next_month());
  }
  $datestr = sprintf('%s/%s/%s', $year, $monthnum, $this->calendar_loop_datenum);
  return strtotime($datestr);
 }
 return $this->_in_calendar_loop
 ;
}


function _calendar_skel($args=NULL) {
 /* //
  This generates ONLY rows of the table.
  i.e. this does NOT produce <table ></table> or <div class="table" ></div> tags.
  These tags are needed when generating calendar/schedule.
  
  Header and Footer should be added if needed.
 // */

 global $custom_language_domain, $wp_custom_functions
 ;
 $args = $this->_calendar_args($args);

 $html = $header_row = '';

 $firstwday = $this->date->wday(array('year'=>$this->year(), 'monthnum'=>$this->mon(), 'day'=>1));
 $table_atts = array();
 $is_table = $args['container'] == 'table';
 if ($is_table) {
  $table_atts['border'] = 0;
  $table_atts['cellspacing'] = 0;
 }
 $table = $is_table?'table':'div';
 $caption = $is_table?'caption':'div';
 $tr = $is_table?'tr':'div';
 $td = $is_table?'td':'div';
 $th = $is_table?'th':'div';
 
 $type_calendar = $args['type'] == 'calendar';
 $type_schedule = !$type_calendar;
 
 $wdays_ord = $this->order_wdays($args['wdays'], $args['start_of_week']);
 $wdays_classes = $this->order_wdays($this->wdays_classes, $args['start_of_week']);
 if ($args['header']) {
  if ($is_table) {
  }
 }

 ;
 $previous_month_mdays = $this->mdays( date('Y',$this->date->previous_month()), date('n',$this->date->previous_month()) );

 /* ////// CALENDAR LOOP ////// */
 $last_cell_number = $this->calendar_leading_pad + $this->ndays();
 
 for ($i = 0; $i < $last_cell_number + $this->calendar_trailing_pad; $i++) {
  $datenum = $i - $this->calendar_leading_pad + 1 ;
  if ($type_schedule && $datenum < 1) continue;
  if ($type_schedule && $i >= $last_cell_number) break;
  
  $col_mod = $i % 7;
  $col_num = $col_mod ? $col_mod : 7;
  
  $_is_previous_month = $i < $this->calendar_leading_pad;
  $_is_next_month = $i >= $last_cell_number ;
  $_is_current_month = !$_is_previous_month && !$_is_next_month ;

  $datenum_anyway = $datenum ;
  if ($_is_current_month) {
   $this->_in_calendar_loop = TRUE ;
  }
  else if ($_is_previous_month) {
   $datenum_anyway = $previous_month_mdays - $this->calendar_leading_pad + 1 + $i ;
   $this->_in_calendar_loop = 'previous' ;
  }
  else if ($_is_next_month) {
   $datenum_anyway = $i - $last_cell_number + 1 ;
   $this->_in_calendar_loop = 'next' ;
  }

  $this->calendar_loop_datenum = $datenum_anyway;

  $add_tr_start = $type_schedule || $col_mod == 0 ; // start of week
  $add_tr_end   = $type_schedule || $col_mod == 6 ;  // end of week
  
  $_is_current_day = $_is_current_tr = $_is_current_td = FALSE ;

  if ( $_is_current_month && $this->year() == $this->now->year() && $this->mon() == $this->now->mon() ) {
   if ($datenum_anyway == $this->day()) {
    $_is_current_day = TRUE ;
   }
   if ($type_calendar) {
    if ($add_tr_start && $this->now->day() >= $datenum_anyway && $this->now->day() <= $datenum_anyway + 6) {
     $_is_current_tr = TRUE;
    }
   }
   else if ($type_schedule) {
    if ($_is_current_day) $_is_current_tr = TRUE;
   }
  }
  

  $date_classes = array( // Date Column/Row Classes
   $wdays_classes[$col_mod],
   'day_box',
   $i < $this->calendar_leading_pad ? 'previous_month' : ( $i >= $last_cell_number ? 'next_month' : 'current_month' ),
   'day_' . $datenum_anyway,
  );
  if ( $_is_current_day ) $date_classes[] = 'today';
  if ( $is_table && $type_calendar ) $date_classes[] = $args['td_class'];
  if ( !$is_table && $type_calendar ) $date_classes[] = $args['div_td_class'];
  if ( $this->holiday->is_holiday($this->year(), $this->mon(), $datenum)) $date_classes[] = 'holiday';
  if ( $type_calendar ) { $date_classes[] = 'column_' . $col_num; }
  
  $day_id = $this->make_day_id($datenum);
  if ($add_tr_start) {
   $trid = $type_schedule ? $day_id : null;
   $tr_classes = array($is_table ? $args['tr_class'] : $args['div_tr_class']);

   if ($type_schedule) {
    $tr_classes = array_merge($tr_classes, $date_classes);
    if ($_is_current_tr) {
	 $tr_classes[] = 'today';
    }
    $trid = $day_id ;
   }
   if ($type_calendar) {
    $tr_classes[] = 'week';
    if ($_is_current_tr) {
	 $tr_classes[] = 'this_week';
    }
    $week_id = 'week_'. (intval($i/7)+1);
    $tr_classes[] = $week_id;
    $trid = $this->make_calendar_id() . '_' . $week_id ;
   }
   $html .= createHTMLElement( $tr, 'start', array('class'=> $tr_classes, 'id'=>$trid ) );
  }

  $content_format = sprintf('%%%d$s', $i + ($type_calendar ? 1 : -1 * $this->calendar_leading_pad + 1));
  if ($type_calendar) {
   $html .= createHTMLElement(
    $td,
    array(
	 'class'=>$date_classes,
	 'id' => $type_calendar ? $day_id : NULL,
	),
	createHTMLElement( 'div', array('class'=>$is_table?$args['td_content_class']:$args['div_td_content_class']),
	 apply_filters('WPCF_EventSchedule_Calendar_DateNumber', apply_filters('CF_HTML', 'div', array('class'=>'date_number'), $datenum_anyway) ) .
     $content_format
    )
   );
  }
  else if ( $i <= $this->calendar_leading_pad + $this->mdays() ) {
   $html .= $content_format;
  }
  if ($add_tr_end) { $html .= createHTMLElement($tr, 'end'); }
 }
 $this->_in_calendar_loop = FALSE ;
 $this->calendar_loop_datenum = 0 ;
 ;
 /* ////// END CALENDAR LOOP ////// */
 return $html;
}


function navigation($args=NULL) {
 $args = $this->cal_args($args);
 $class = array($args['calendar_name'].'_Navigation');
 if (!is_array($args['class'])) {
  $args['class'] = preg_split('/[,\x20]/', $args['class']);
 }
 $class = array_merge($class, $args['class']);
 $n =
  createHTMLElement('div', array(
	 'class' => $class,
     'id' => $this->make_calendar_id($args).'_Navigation'
    ),
    $this->nav_prev($args)
  . $this->nav_next($args)
 )
 ;
 return $n;
}


function nav_prev($args=NULL) {
 $args = $this->cal_args($args);
 $date = $this->date->previous_month();
 return $this->_nav($date, sprintf($args['previous_format'], $args['yearmonth_format']));
}


function nav_next($args=NULL) {
 $args = $this->cal_args($args);
 $date = $this->date->next_month();
 return $this->_nav($date, sprintf($args['next_format'], $args['yearmonth_format']));
}


function _nav($date, $format) {
 $prev_or_next = $this->date->time() > $date ? -1 : 1;
 $p_n = $prev_or_next == -1 ? 'prev' : 'next';
 return createHTMLElement('span', array( 'class'=>array( $this->prefix.'nav_'.$p_n, $this->prefix.'nav' ) ),
  createHTMLElement('a', array('href'=>$this->url($prev_or_next)),
   vsprintf( $format, array(date('Y',$date), date('n',$date), $this->monthnames[date('n',$date)-1]) )
  )
 );
}

function url($n=0) {
 if ($n < 0) { $n = -1; }
 else {
  $n = (bool) $n ? 1 : 0;
 }
 $date = NULL;
 switch ($n) {
  case 0 :
   $date = $this->date->time(); break;
  case 1 :
   $date = $this->date->next_month(); break;
  case -1 :
   $date = $this->date->previous_month(); break;
 }
 $url = implode('/', array(
  trailingslashit( get_bloginfo('url') ) .  $this->param('url_base'),
  date('Y', $date),
  date('n', $date),
  ''
 ) );
 return $url;
}



function format_time($start=null, $end=null, $to = '〜') {
 return createHTMLElement('time', array('class'=>'start_end'),
   createHTMLElement('time', array('class'=>'start'),
	  ((preg_match('/^00:00$/', date('H:i', $start)))? '' : date('H:i', $start).$to)
	) .
   createHTMLElement('time', array('class'=>'end'), (($start != $end)?date('H:i', $end):''))
 );
}


function format_event($content) {
 if (apply_filters(WPCF_PREFIX.'Is_EventSchedule', NULL)) {
  return apply_filters($this->prefix.'Format_Event', $content);
 }
 return $content;
}


function format_event_post($p=null, $a=array()) {
 global $custom_language_domain
 ;
 if (is_string($p)) return createHTMLElement(is_html_capable()? 'article':'div', array('class'=>array('calendar_content','article')), $p); 
 if (!$p) return $p;
 $d = '';
 
 if ($t = $p->post_title) $evt_title = createHTMLElement('h1', null, $t);
 
 $pc = preg_replace('/^<br \x2f>[\r\n\s]+$/', '', $p->post_content);
 if ($pc) {
  $evt_pc = createHTMLElement('div', array('class'=>'content_link'),
   createHTMLElement(
    'a',
    array(
 	 'href'=>'#post_content_'.$p->ID,
 	 'rel'=>'prettyPhoto',
 	 'class'=>$o->atts['calendar_atts']['id'].'_morelink'
 	),
    (($t = $o->atts['calendar_atts']['morelinktext'])? $t : __('See Details', $custom_language_domain))
   )
  ) .
  createHTMLElement(
   'div', array('class'=>$o->atts['calendar_atts']['class'].'_'.'post-content', 'id'=>'post_content_'.$p->ID),
   apply_filters(WPCF_PREFIX.'The_Content', $p)
  );
 }
 
 if (is_admin_user())
  $evt_edit_post = createHTMLElement('a', array('href'=>get_edit_post_link($p->ID), 'target'=>'_blank', 'class'=>'event_edit'), __('Edit')); 
 
 return $evt_time . $evt_title . $evt_act . $evt_pc . $evt_edit_post;
}


function _calendar_header($content) {
 global $wp_custom_functions
 ;
 $args = $this->calendar_args;
 $type_calendar = $args['type'] == 'calendar';
 $type_schedule = !$type_calendar;
 $wdays_ord = $this->order_wdays($args['wdays'], $args['start_of_week']);
 $wdays_classes = $this->order_wdays($this->wdays_classes, $args['start_of_week']);
 
 $is_table = $args['container'] == 'table';
 
 $tr = $is_table?'tr':'div';
 $td = $is_table?'td':'div';
 $th = $is_table?'th':'div';
 $table = $is_table?'table':'div';

 $header_row = '';
 
 if ($type_calendar) {
  foreach ($wdays_ord as $k=>$v) {
   $th_class = array();
   $th_class[] = $this->make_calendar_id() . '_wday_' . $wdays_classes[$k];
   $th_class[] = $is_table ? $args['td_class'] : $args['div_td_class'];
   $th_class[] = 'wday_box';
   $th_class[] = $wdays_classes[$k];
 
   $th_content =
   $header_row .= createHTMLElement($th, array('class'=>$th_class ),
    createHTMLElement(
	 'div',
     array('class' => $is_table?$args['td_content_class']:$args['div_td_content_class']),
     vsprintf($args['wday_format'], $wdays_ord[$k])
    )
   );
  }
  if ($header_row) {
   $header_row = createHTMLElement(
    $tr,
    array(
	 'class'=>array(
	  'wdays',
      $this->make_calendar_id() .'_wdays',
      'table_header_row',
      $is_table ? $args['tr_class'] : $args['div_tr_class'],
      $this->make_calendar_id().'_table_header_row'
     ),
    ),
    $header_row
   );
  }
 }
 return
  createHTMLElement($table,'start', array('class'=>array($is_table ?'':$args['div_table_class'], $args['calendar_name']), 'id'=>$this->make_calendar_id()))
  . $header_row
  . $content
 ;
}


function _calendar_footer($content) {
 $args = $this->calendar_args;
 $type_calendar = $args['type'] == 'calendar';
 $type_schedule = !$type_calendar;
 $is_table = $args['container'] == 'table';
 
 $tr = $is_table?'tr':'div';
 $td = $is_table?'td':'div';
 $th = $is_table?'th':'div';
 $table = $is_table?'table':'div';
 return
    $content
  . createHTMLElement($table,'end') . createHTMLElement('_comment', 'End of #'.$this->make_calendar_id()); ;
}


function _schedule_header($content) {
 return $content;
}

function _schedule_footer($content) {
 return $content;
}

function _is_event_schedule($null) { 
 return apply_filters(WPCF_PREFIX.'Is_PostType', $this->post_type->name, NULL);
}


function _is_schedule($args=NULL) {
 return $this->param('_is_schedule');
}


/* ////// Utilities ////// */
function make_calendar_id($args=NULL, $increment=0) {
 $args = $this->cal_args($args);
 return implode('_', array(
  vsprintf('%s_%04d-%02d_%d', array($args['calendar_name'], $this->year(), $this->mon(), $args['calendar_count'] + $increment) ),
 ));
}



function make_day_id($day, $args = NULL) {
 global $wp_custom_functions
 ;
 $args = $wp_custom_functions->parse_args( array(
  'calendar_name' => $this->calendar_args['calendar_name'],
  'calendar_count' => $this->calendar_args['calendar_count'],
 ), $args );
 return sprintf('%s_%s_day_%s', $args['calendar_name'], $args['calendar_count'], $day);
}

function order_wdays($wdays=NULL, $start_of_week=NULL) {
 $wdays == NULL && $wdays = $this->wdays;
 $start_of_week === NULL && $start_of_week = $this->param('start_of_week');
 return
  array_merge(
   array_splice($wdays, $start_of_week, count($wdays)),
   array_splice($wdays, 0, $start_of_week)
  );
}

function ndays() { /* moved to class.Date.php */ return $this->date->ndays(); }
function mdays($y=NULL,$m=NULL) { /* moved to class.Date.php */ return $this->date->mdays($y,$m); }
function datestr($y=NULL,$m=NULL,$d=NULL) { /* moved to class.Date.php */ return $this->date->datestr($y,$m,$d); }
function year($set=NULL) { /* moved to class.Date.php */ $y = $this->date->year($set); return $set ? $this : $y; }
function mon($set=NULL) { /* moved to class.Date.php */  $m = $this->date->mon($set); return $set ? $this : $m; }
function day($set=NULL) { /* moved to class.Date.php */  $d = $this->date->day($set); return $set ? $this : $d; }

function cmp_time($a, $b) {
 $t1 = strtotime($a);
 $t2 = strtotime($b);
 return ($t1 == $t2) ? 0 : (($t1 > $t2)? +1 : -1);
}


function add_edit_post_link($content) {
 global $post
 ;
 if (is_admin_user()) {
  return $content . apply_filters('CF_HTML', 'div', array('class'=>$this->prefix.'Edit_Post'),
   apply_filters('CF_HTML', 'a', array('href'=>get_edit_post_link($post->ID), 'class'=>'admin_button'), __('Edit'))
  )
  ;
 }
 return $content;
}

function admin_script($script) {
 $s = apply_filters('CF_Wrap_JavaScript', '
$(".'.$this->prefix.'Edit_Post a.admin_button").button()
$(".entry").each(function(){
 var e = $(".'.$this->prefix.'Edit_Post", this).css({
  "position":"absolute", "bottom":0
 }).hide()
 $(this).hoverIntent( function(){e.fadeIn()}, function(){e.fadeOut()})
})
', array('jquery'=>TRUE, 'jqueryready'=>TRUE)
 )
 ;
 return $script . (empty($script) ? '' : ';') . $s ;
}

function get_calendar_id() {
 return $this->calendar_args['calendar_id'];
}

function force_future_to_publish( $data, $postarr ) {
 if (
  ( isset($_POST['post_type']) && $this->post_type->name != $_POST['post_type'] )
  ||
  (isset( $_POST['ID']) && !current_user_can('edit_post', $_POST['ID'])) // Checking capability
  ||
  ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) // Do nothing if AUTOSAVE
  ||
  (isset($_POST['ID']) && empty($_POST['ID'])) // Check if proper data posted
  ||
  (isset($_POST['action']) && empty($_POST['action']) )
  || 
  empty($data['post_status'])
 ) {
  return $data;
 }
 if ( isset($_POST['action']) && $_POST['action'] == 'editpost' && isset($_POST['post_status']) && $_POST['post_status'] == 'future' ){
  $data['post_status'] = 'publish';
 }
 return $data;
}


/* ////// Setting Utilities ////// */
function set_date($y=NULL,$m=NULL,$d=NULL) {
 /* moved to class.Date.php */ $this->date->set_date($y,$m,$d);
 $this->set_calendar_variables();
 return $this;
}

function set_time($time=NULL) {
 /* moved to class.Date.php */ $this->date->set_time($time);
 $this->set_calendar_variables();
 return $this;
}

function set_calendar_variables() {
 $firstwday = $this->date->wday(array('year'=>$this->year(), 'monthnum'=>$this->mon(), 'day'=>1)); 
 $pad = $firstwday - $this->calendar_args['start_of_week'] ;
 if ($pad < 0) $pad += 7;
 $this->calendar_leading_pad = $pad;
 $last_cell = $this->ndays() + $this->calendar_leading_pad;
 $this->calendar_trailing_pad = $last_cell % 7 ? 7 - $last_cell % 7 : 0
 ;
}

/* ////// Misc //////*/
function set_values() {
 global $custom_language_domain;
 $this->meta_box_fields = array(
 'event_title' => array(
  'label' => __('Event Title', $custom_language_domain),
  'type' => 'textarea',
  'rows' => 2,
 ),
 'date'=>array(
  'label' =>__('Date', $custom_language_domain),
  'type' => 'text',
  'script' => '

var schedule_info_date = $("#'.$this->base_meta_box_name.'_date_0")
  , event_title = $("#'.$this->base_meta_box_name.'_event_title_0")
  , start_time = $("#'.$this->base_meta_box_name.'_start_time_0")

$("#post").on("submit",function(){
 var ymd = "", hm = "", et = "", st = ""
 $("#post_name").val("")
 if ( $("#title").val() == "undefined" ) $("#title").val("")
 
 if ( schedule_info_date.val() != undefined ) {
  schedule_info_date.val( schedule_info_date.val().replace(/\x2f/,"-"))
  if(schedule_info_date.val().match(/(\d{4})-(\d{2})-(\d{2})/)) {
   var y = RegExp.$1, m = RegExp.$2, d = RegExp.$3
   ymd = sprintf("%04d-%02d-%02d", y, m, d)
   $("#aa").val(y); $("#mm").val(m); $("#jj").val(d);
  }
 }
 if (event_title.val() != undefined && event_title.val() != "") {
  et = event_title.val()
  et = et.replace(/<.*?>/g,"")
 }
 if (start_time.val() != undefined && start_time.val() != "") {
  hm = start_time.val()
  hm.match(/(\d{2})\x3a(\d{2})/)
  var h = RegExp.$1, m = RegExp.$2
  $("#hh").val(h)
  $("#mn").val(m)
 }
 $("#title").val(sprintf("%s %s %s", ymd, hm, et)) // Forces title to a specified format
})
',
  'default_title_value' => null,
  'datepicker' => 1,
  'datepicker_options' => '{numberOfMonths:3,dateFormat:"yy-mm-dd",altField:"#'.$this->base_meta_box_name.'_datetime", altFormat:"@", onClose:function(){var dt=$("#'.$this->base_meta_box_name.'_datetime").val(); $("#'.$this->base_meta_box_name.'_datetime").val(dt/1000)} }'
 ),
 'start_time' => array(
  'label' => __('Start Time', $custom_language_domain),
  'type' => 'text',
  'script' => null,
  'default_title_value' => null,
  'timepicker' => 1,
  'timepicker_options' => null
 ),
 'end_time' => array(
  'label' => __('End Time', $custom_language_domain),
  'type' => 'text',
  'script' => null,
  'default_title_value' => null,
  'timepicker' => 1,
  'timepicker_options' => null
 ),
 'post_time' => array( // date (time) of the post created
  'type' =>'hidden',
  'script'=>'$("#post").on("submit",function(){
  if ( !$("#'.$this->base_meta_box_name.'_post_time_0").val() && ($("#post_status").val() == "future" || $("#post_status").val() == "publish") ) {$("#'.$this->base_meta_box_name.'_post_time_0").val(parseInt((new Date)/1000));console.log(this)}
 }
)'
 ),
);
}



} // END OF CLASS.EventSchedule
