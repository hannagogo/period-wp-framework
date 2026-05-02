<?php
/* // This Class is obsolete. Please Use EventSchedule instead. // */

class ScheduleCalendar
{
  var $date = array();
  var $atts = array();
  var $ndays = null;
  var $contents = array();
  var $event_posts = array();
  var $wdays = array('SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT');
  var $monthnames = array('JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
  var $calendar_count = 0;
  var $meta_box_fields = array();
  var $holiday = null;

  function __construct($param = array())
  {
    global $wp_rewrite, $wp_query, $custom_language_domain, $wp_custom_functions;
    require_once('class.PublicHoliday.php');
    $this->set_values();
    $this->_atts = $param;

    if (isset($param['uri_base']) && !$param['uri_base'] && $param['uri_base'] !== 0) unset($param['uri_base']);
    $this->atts = $wp_custom_functions->parse_args(array(
      //  'post_range' => array(3600*24*30*6, -3600*24*30*6),
      //  'set_post_date' => false,
      'uri_base'     => 'schedule',
      'meta_box_args'   => array(),
      'post_type_args'   => array(),
      'posts_per_page'   => -1,
      'meta_box_field_names'   => array('date', 'open_time', 'start_time', 'end_time'),
      'holiday_preset'   => NULL,
      'holiday_params'   => NULL,
    ), $param);
    $this->atts = $this->atts + $wp_custom_functions->parse_args(
      array(
        'rewrite_rule_base' => 'pagename=' . preg_replace('|^/?([^/]+)?/?.*?$|', '$1', $this->atts['uri_base'])
      ),
      $param
    );

    // Setting Holidays
    $this->atts['holiday_params'] = $wp_custom_functions->parse_args(array(
      'type'   => 'Holiday',   //'static', 'Holiday' or 'public_holiday_2.0'
      'holidays' => NULL,     // assumed array('2013-1-5' => 'Holiday Name') other formats ignored
      'year'   => date('Y'),
      'month'   => date('n'),
      'day'     => date('j')
    ), $this->atts['holiday_params']);
    if (!$this->atts['holiday_params']['type'] && $this->atts['holiday_preset']) {
      $this->atts['holiday_params']['type'] = $this->atts['holiday_preset'];
    }
    $this->holiday = new PublicHoliday($this->atts['holiday_params']);

    // PostType
    if (!isset($param['post_type_args']) || !is_array($param['post_type_args']))
      $param['post_type_args'] = array();
    if (isset($param['meta_box_args']) && !is_array($param['meta_box_args']))
      $param['meta_box_args'] = (array) $param['meta_box_args'];;
    // base meta box: [0]
    if (!in_array('date', $this->atts['meta_box_field_names'])) $this->atts['meta_box_field_names'] = array_merge(array('date'), $this->atts['meta_box_field_names']);
    if (!in_array('post_time', $this->atts['meta_box_field_names'])) $this->atts['meta_box_field_names'] = array_merge(array('post_time'), $this->atts['meta_box_field_names']);
    if (isset($param['meta_box_args']['additional_fields']) && is_array($a = $param['meta_box_args']['additional_fields'])) {
      foreach ($a as $afn => $af) {
        $this->meta_box_fields[$afn] = $af;
        if (!in_array($afn, $this->atts['meta_box_field_names'])) $this->atts['meta_box_field_names'][] = $afn;
      }
    }
    $base_meta_box_fields = array();
    foreach ($this->atts['meta_box_field_names'] as $mbfn) {
      if (isset($this->meta_box_fields[$mbfn])) {
        if (!isset($param['meta_box_args'][$mbfn])) $param['meta_box_args'][$mbfn] = array();
        $base_meta_box_fields[$mbfn] = $wp_custom_functions->parse_args($this->meta_box_fields[$mbfn], $param['meta_box_args'][$mbfn]);
      }
    }
    $meta_boxes_args = array(
      array(
        'name' => 'schedule_info',
        'title' => __('Date and Time', $custom_language_domain),
        'fields' => $base_meta_box_fields
      )
    );

    // additional single meta box [1]
    if (isset($param['meta_box']) && is_array($param['meta_box'])) $meta_boxes_args[] = $param['meta_box'];
    // additional multiple meta boxes [1,2..]
    if (isset($param['meta_boxes']) && count($param['meta_boxes'])) foreach ($param['meta_boxes'] as $m) $meta_boxes_args[] = $m;

    $this->atts['post_type_args'] = $wp_custom_functions->parse_args(
      array('name' => 'scheduled-events'),
      $param['post_type_args']
    );
    $this->atts['post_type_args'] = $wp_custom_functions->parse_args(
      array(
        'name' => $this->atts['post_type_args']['name'],
        'label' => __('Schedule', $custom_language_domain),
        'singular_name' => __('Scheduled Event', $custom_language_domain),
        'hierarchical' => false,
        'supports' => array('title', 'editor', 'thumbnail', 'author'),
        'has_archive' => true,
        'meta_boxes' => $meta_boxes_args,
        'taxonomy' => null,
        'custom_rules' => array(
          $this->atts['uri_base'] . '(?:/(\d{4}/?)?(?:/(\d{1,2})/?)?(?:/(\d{1,2})/?)?)?/?$' => 'index.php?' . implode('&', array('scy=$matches[1]&scm=$matches[2]&scd=$matches[3]', $this->atts['rewrite_rule_base'])),
        )
      ),
      $param['post_type_args']
    );
    wpcf_add_post_type(new PostType($this->atts['post_type_args']));
    enable_query_vars(array("scy", "scm", "scd"));
    add_action('wp', array(&$this, 'setup_posts'));
    $this->init(); // overwrite constructed values

  } // end of __contruct


  function setup_posts($atts = array())
  {
    if (is_array($atts) && count($atts) > 0) { // if array passed to this method
      if (isset($atts['time'])) {
        $atts = $atts + array(
          'year' => date('Y', $atts['time']),
          'monthnum' => date('n', $atts['time']),
          'day' => date('j', $atts['time']),
        );
      } else $atts = $this->parse_date($atts['year'], $atts['monthnum'], $atts['day']);
    } else if (isset($this->_atts['time'])) { // if 'time' passed to constructer
      $atts['time'] = $this->_atts['time'];
    } else if (isset($this->_atts['year']) || isset($this->_atts['monthnum']) || isset($this->_atts['day'])) {
      // if date numbers passed to constructer
      $atts = $this->parse_date(
        isset($this->_atts['year']) ? $this->_atts['year'] : null,
        isset($this->_atts['monthnum']) ? $this->_atts['monthnum'] : null,
        isset($this->_atts['day']) ? $this->_atts['day'] : null
      );
    } else { // parse query_vars or now
      $atts = $this->parse_date(
        (($y = get_query_var('scy')) ? $y : null),
        (($m = get_query_var('scm')) ? $m : null),
        (($d = get_query_var('scd')) ? $d : null)
      );
    }

    if (isset($atts['time'])) $this->set_time($atts['time']);
    else $this->set_date($atts['year'], $atts['monthnum'], $atts['day']);
    $this->atts['calendar_args'] = $this->_calendar_args(isset($this->_atts['calendar_args']) ? $this->_atts['calendar_args'] : array());
    $this->get_event_posts($this->atts['calendar_args']);
    return $this;
  }

  function init()
  {
    // this function is for override.
  }

  function get_event_posts($args = array())
  {
    global $wpdb;

    $db_query =
      "SELECT * FROM " . $wpdb->posts .
      " WHERE post_date >= '" . sprintf('%4d-%02d-01T00:00:00', $this->year(), $this->mon()) . "'" .
      " AND post_date   <= '" . sprintf('%4d-%02d-%02dT23:59:59', $this->year(), $this->mon(), $this->ndays()) . "'" .
      " AND post_type = '" . $this->atts['post_type_args']['name'] . "'" .
      " AND (post_status = 'publish' OR post_status = 'future')";
    $event_posts = $wpdb->get_results($db_query);
    $post_dates = array();
    foreach ($event_posts as $p) {
      setup_postdata($p);
      $post_dates[] = strtotime($p->post_date);
    }
    array_multisort($post_dates, $event_posts);
    foreach ($event_posts as $p) {
      $event_date = apply_filters('WPCF_Get_Post_Meta', $p->ID, 'date', 1);
      if (empty($event_date)) continue;
      $date = getdate(strtotime($p->post_date));
      !isset($this->event_posts[$date['mday']]) && $this->event_posts[$date['mday']] = array();
      $this->event_posts[$date['mday']][] = $p;
    }
    $this->events_per_date = 0;
    foreach ($this->event_posts as $posts) {
      if (($n = count($posts)) > $this->events_per_date) $this->events_per_date = $n;
    }
    if ($this->events_per_date >= 2) {
      add_action('get_header', function () {
        enqueue_js_library_handle("jquery.AutoHeight");
      });
      add_action('get_header', function () {
        enqueue_jquery_code('$(".day_box").each(function(){for(var i=0; i<' . $this->events_per_date . '; i++){$(this).find(".event_"+i).autoHeight()}});');
      });
    }
    $event_posts = null;
    return $this;
  }


  function get_post_range($start_or_end = 'start', $range = null)
  {
    if (!$range) $range = $this->atts['post_range'];
    if ($start_or_end !== 'start') $start_or_end = 'end';
    if (!(count($range) < 2)) return;
    list($range_start, $range_end) = ($range[0] >= $range[1]) ? array($range[1], $range[0]) : $range;
    return ${'range_' . $start_or_end};
  }
  function post_range_start($range = null)
  {
    return $this->get_post_range('start', $range);
  }
  function post_range_end($range = null)
  {
    return $this->get_post_range('end', $range);
  }


  function _calendar_args($args = array())
  {
    global $custom_language_domain, $wp_custom_functions;
    $args['navigation_atts'] = $wp_custom_functions->parse_args(
      array(
        'previous_format' => '<< %2$s月',
        'next_format'   => '%2$s月 >>'
      ),
      (isset($args['navigation_atts']) ? $args['navigation_atts'] : array())
    );
    $a = $wp_custom_functions->parse_args(array(
      'container'   => 'table',
      'type'     => 'calendar', // or 'schedule'
    ), $args);
    $args['container_atts'] = $wp_custom_functions->parse_args(
      array(
        'summary' => 'SCHEDULE',
        'class'   => 'calendar',
        'id'      => '',
        'attach_id' => false
      ),
      (isset($args['container_atts']) ? $args['container_atts'] : array())
    );
    $args['event_post_atts'] = $wp_custom_functions->parse_args(
      array(
        'rel' => 'fancybox',
        'class'   => 'calendar',
        'id'   => 'calendar_table',
      ),
      (isset($args['event_post_atts']) ? $args['event_post_atts'] : array())
    );
    $args = $wp_custom_functions->parse_args(array(
      'navigation'     => false,
      'sort_key'     => '',
      'navigation_atts'   => $args['navigation_atts'],
      'title_format'   => '%1$s年%2$s月',
      'monthnames'     => $this->monthnames,
      'wdays'       => $this->wdays,
      'wday_format'   => '(%6$s)',
      'container'   => $a['container'],
      'type'     => $a['type'],
      'container_atts'   => $args['container_atts'],
      'morelinktext'   => __('See Details', $custom_language_domain),
      'date_number_format' => '%3$s',
      'schedule_labels'   => array('Date', 'Day of the Week', 'Schedule'),
      'calendar_count'   => 0
    ), $args);
    return $args;
  }

  function sort_by_key($args = array())
  {
    $args = $this->_calendar_args($args);
    if ($sort_key = $args['sort_key']) {
      foreach ($this->event_posts as &$posts) {
        if (count($posts) >= 2) {
          foreach ($posts as $i => $p) {
            $sort_values[$i] = strtotime(apply_filters('WPCF_Get_Post_Meta', $p->ID, $sort_key, 1));
          }
          array_multisort($posts, $sort_values);
        }
      }
    }
    return $this;
  }

  function &get_event($day = null)
  {
    !$day && $day = $this->day();
    return $this->event_posts[$day];
  }
  /* ////// Representation ////// */
  function make_calendar($args = array(), $contents)
  {
    global $wp_custom_functions;
    $this->calendar_count++;
    // if (isset($args['sort_key'])) $this->sort_by_key($args);
    $args = $this->_calendar_args($wp_custom_functions->parse_args($this->atts['calendar_args'], $args));
    $args['firstwday'] = $this->wday(array('year' => $this->year(), 'monthnum' => $this->mon(), 'day' => 1));
    $is_table = $args['is_table'] = ($args['container'] == 'table');
    list($args['is_calendar'], $args['is_schedule']) = array(
      $is_calendar = ($args['type'] == 'calendar') ? true : false,
      $is_schedule = ($args['type'] == 'schedule') ? true : false
    );
    $args['container_atts']['id'] = $this->make_calendar_id($args);
    $container_atts = $args['container_atts'];

    if ($is_table) {
      $container_atts['border'] = 0;
      $container_atts['cellspacing'] = 0;
    }
    $date_format_array = array($this->year(), $this->mon(), $this->day(), '', $args['monthnames'][$this->mon() - 1]);
    $prev_mon = $this->previous_month();
    $next_mon = $this->next_month();
    $url_next = implode('/', array(get_bloginfo('url'), $this->atts['uri_base'], date('Y', $next_mon), date('n', $next_mon), ''));
    $url_prev = implode('/', array(get_bloginfo('url'), $this->atts['uri_base'], date('Y', $prev_mon), date('n', $prev_mon), ''));

    $table = $is_table ? 'table' : 'div';
    $caption = $is_table ? 'caption' : 'div';
    $tr = $is_table ? 'tr' : 'div';
    $td = $is_table ? 'td' : 'div';
    $th = $is_table ? 'th' : 'div';

    $c = createHTMLElement($table, 'start', $container_atts);
    if ($args['navigation']) {
      $c .= createHTMLElement(
        $caption,
        array('class' => array($container_atts['class'] . '_nav', 'caption')),
        createHTMLElement(
          'span',
          array('class' => $container_atts['class'] . '_nav_prev'),
          createHTMLElement(
            'a',
            array('href' => $url_prev),
            vsprintf(
              $args['navigation_atts']['previous_format'],
              array(date('Y', $prev_mon), date('n', $prev_mon), date('j', $prev_mon), null, null, null)
            )
          )
        ) .
          vsprintf($args['title_format'], $date_format_array) .
          createHTMLElement(
            'span',
            array('class' => $container_atts['class'] . '_nav_next'),
            createHTMLElement(
              'a',
              array('href' => $url_next),
              vsprintf(
                $args['navigation_atts']['next_format'],
                array(date('Y', $next_mon), date('n', $next_mon), date('j', $next_mon), null, null, null)
              )
            )
          )
      );
    }

    if ($is_schedule) {
      if (!isset($contents[0])) {
        $contents[0] = createHTMLElement(
          $tr,
          array('class' => 'schedule_labels'),
          createHTMLElement($th, null, $args['schedule_labels'])
        );
        $c .= $contents[0];
      } else if ($contents[0]) {
        $c .= $contents[0];
      }
    } else {
      $th_content = '';
      foreach ($this->wdays as $k => $v) {
        $th_content .= createHTMLElement(
          $th,
          array('class' => $this->wdays[$k]),
          vsprintf($args['wday_format'], array(null, null, null, null, null, $args['wdays'][$k]))
        );
      }
      $c .= createHTMLElement(
        $tr,
        array(
          'class' => array('wdays', $container_atts['class'] . '_' . 'wdays'),
          'id' => $container_atts['id'] . '_' . 'wdays'
        ),
        $th_content
      );
    };
    for ($i = 1; $i <= $this->ndays() + $args['firstwday'] + (6 - $this->wday(array('day' => $this->ndays()))); $i++) {
      $datenum = $i - $args['firstwday'];
      $date_classes = array(strtolower($this->wdays[($i - 1) % 7]), 'day_box');
      if ($i > $args['firstwday'] && $i <= $this->ndays() + $args['firstwday']) $date_classes[] = 'day_' . $datenum;
      if ($this->holiday->is_holiday($this->year(), $this->mon(), $datenum)) $date_classes[] = 'holiday';
      if ($i <= $args['firstwday']) $date_classes = array_merge(
        $date_classes,
        array(
          'previous_month',
          'day_' . ($this->mdays(array(
            'year' => date('Y', $prev_mon),
            'monthnum' => date('n', $prev_mon)
          )) + ($i - $args['firstwday']))
        )
      );

      if ($is_schedule && $datenum < 1) continue;
      if ($is_calendar && $i > $this->ndays() + $args['firstwday']) {
        $c .= createHTMLElement($td, array('class' => array_merge($date_classes, array('next_month', 'day_' . ($i - $this->ndays() - $args['firstwday']))), ''));
        if ($i % 7 == 0) $c .= createHTMLElement($tr, 'end'); // end of month
        continue;
      }
      $da = $date_format_array;
      $da[2] = $datenum;
      $da[5] = $this->wdays[$this->wday(array('day' => $datenum))];
      $add_tr_start = $is_schedule || ($is_calendar && (($i % 7) == 1));
      $add_tr_end = ($is_schedule || (($i + 1) % 7) == 1);  // end of the week

      $trid = $is_schedule && $args['container_atts']['attach_id'] ? $args['container_atts']['id'] . '_' . $datenum : null;

      if ($add_tr_start) $c .= createHTMLElement($tr, 'start', array('class' => $is_schedule ? $date_classes : 'week', 'id' => $trid));

      $date_content_args = array_merge(
        $args,
        array('loop' => $i, 'datenum' => $datenum, 'class' => $date_classes, 'date_format_array' => $da)
      );
      $date_content = (isset($contents[$datenum]) && $contents[$datenum]) ? $contents[$datenum] : '';

      if ($i > $args['firstwday']) {
        if ($is_calendar || $i <= $args['firstwday'] + $this->ndays())
          $c .= $this->format_date_content($date_content_args, $date_content); // if type schedule, does not care start/end TDs.
      } else {
        $c .= createHTMLElement($td, array('class' => $date_content_args['class']), '');
      }

      if ($add_tr_end) {
        $c .= createHTMLElement($tr, 'end');
      }
    }
    $c .= createHTMLElement($table, 'end') . "\n";
    return $c;
  }


  function format_date_content($args = array(), $content = '')
  {
    $dc = '';
    $is_table = $args['is_table'];
    list($is_calendar, $is_schedule) = array($args['is_calendar'], $args['is_schedule']);
    $td = $is_table ? 'td' : 'div';
    if ($is_calendar) {
      if ($args['datenum'] > 0) {
        $dc = $this->format_calendar_date_content($args, $content);
      }
      $tdid = $args['container_atts']['attach_id'] ? $args['container_atts']['id'] . '_' . $args['datenum'] : null;
      return createHTMLElement(
        $td,
        array('class' => array_merge(array('day_box'), $args['class']), 'id' => $tdid),
        createHTMLElement('div', array('class' => 'date_number'), $dc)
      );
    } else {
      return $this->format_schedule_date_content($args, $content);
    }
  }

  function format_calendar_date_content($args = array(), $content = '')
  {
    return vsprintf($args['date_number_format'], $args['date_format_array']) . $content;
  }

  function format_schedule_date_content($args = array(), $content = '')
  {
    $td = $args['is_table'] ? 'td' : 'div';
    return createHTMLElement($td, array('class' => 'date_number'), vsprintf($args['date_number_format'], $args['date_format_array'])) .
      createHTMLElement($td, array('class' => 'wday'), vsprintf($args['wday_format'], $args['date_format_array'])) .
      createHTMLElement($td, array('class' => 'content'), $content);
  }

  function format_time($start = null, $end = null, $to = '〜')
  {
    return createHTMLElement(
      'time',
      array('class' => 'start_end'),
      createHTMLElement(
        'time',
        array('class' => 'start'),
        ((preg_match('/^00:00$/', date('H:i', $start))) ? '' : date('H:i', $start) . $to)
      ) .
        createHTMLElement('time', array('class' => 'end'), (($start != $end) ? date('H:i', $end) : ''))
    );
  }


  function format_event_post($p = null, $a = array())
  {
    global $custom_language_domain;
    if (is_string($p)) return createHTMLElement(is_html_capable() ? 'article' : 'div', array('class' => array('calendar_content', 'article')), $p);
    if (!$p) return $p;
    $d = '';

    if ($t = $p->post_title) $evt_title = createHTMLElement('h1', null, $t);

    $pc = preg_replace('/^<br \x2f>[\r\n\s]+$/', '', $p->post_content);
    if ($pc) {
      $evt_pc = createHTMLElement(
        'div',
        array('class' => 'content_link'),
        createHTMLElement(
          'a',
          array(
            'href' => '#post_content_' . $p->ID,
            'rel' => 'prettyPhoto',
            'class' => $o->atts['calendar_atts']['id'] . '_morelink'
          ),
          (($t = $o->atts['calendar_atts']['morelinktext']) ? $t : __('See Details', $custom_language_domain))
        )
      ) .
        createHTMLElement(
          'div',
          array('class' => $o->atts['calendar_atts']['class'] . '_' . 'post-content', 'id' => 'post_content_' . $p->ID),
          apply_filters('the_content', $p->post_content)
        );
    }

    if (is_admin_user())
      $evt_edit_post = createHTMLElement('a', array('href' => get_edit_post_link($p->ID), 'target' => '_blank', 'class' => 'event_edit'), __('Edit'));

    return $evt_time . $evt_title . $evt_act . $evt_pc . $evt_edit_post;
  }


  /* ////// Utilities ////// */
  function make_calendar_id($args = array())
  {
    return implode('_', array(
      vsprintf('%s-%02d-%02d', array(get_class($this), $this->year(), $this->mon())),
      sprintf('%02d', $args['calendar_count'] ? $args['calendar_count'] : $this->calendar_count)
    ));
  }

  function ndays()
  {
    return $this->date['ndays'] = $this->mdays(array('year' => $this->year(), 'monthnum' => $this->mon()));
  }
  function mdays($d = array())
  {
    $mdays = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    !isset($d['monthnum']) && $d['monthnum'] = $this->mon();
    !isset($d['year']) && $d['year'] = $this->year();
    $ndays = $mdays[$d['monthnum'] - 1];
    $d['monthnum'] == 2 && $this->isLeap($d['year']) && $ndays++;
    return $ndays;
  }

  function datestr($y = null, $m = null, $d = null)
  {
    $k2p = array('y' => 'year', 'm' => 'mon', 'd' => 'day');
    foreach (array('y', 'm', 'd') as $k) if (!${$k}) ${$k} = $this->{$k2p[$k]}();
    return implode('-', array($y, $m, $d));
  }

  function parse_date($y = null, $m = null, $d = null)
  {
    global $wp_custom_functions;
    return $wp_custom_functions->parse_args(
      array('year' => date('Y'),  'monthnum' => date('n'),  'day' => date('j')),
      array('year' => $y,      'monthnum' => $m,      'day' => $d)
    );
  }

  function year($set = null)
  {
    if ($set) $this->set_date($set);
    return $this->date['year'];
  }
  function mon($set = null)
  {
    if ($set) $this->set_date(null, $set);
    return $this->date['monthnum'];
  }
  function day($set = null)
  {
    if ($set) $this->set_date(null, null, $set);
    return $this->date['day'];
  }
  function next_month($format = null)
  {
    return calc_month($this->year(), $this->mon(), $this->day(), 1, $format);
  }
  function previous_month($format = null)
  {
    return calc_month($this->year(), $this->mon(), $this->day(), -1, $format);
  }
  function isLeap($year = null)
  {
    !$year && $year = $this->year();
    if (
      (($year % 4) == 0 && ($year % 100) != 0) || (($year % 400) == 0)
    ) return true;
    return false;
  }


  function wday($d = array())
  {
    global $wp_custom_functions;
    /* returned value: 0 = SUN, 1 = MON, ... , 6 = SAT */
    $d = $wp_custom_functions->parse_args(array(
      'year' => $this->year(),
      'monthnum'  => $this->mon(),
      'day' => $this->day()
    ), $d);
    list($year, $mon, $mday) = array($d['year'], $d['monthnum'], $d['day']);

    if ($mon == 1 || $mon == 2) {
      $year--;
      $mon += 12;
    }

    return (int) ($year + (int)($year / 4) - (int)($year / 100) + (int)($year / 400)
      + (int)((13 * $mon + 8) / 5) + $mday) % 7;
  }

  function cmp_time($a, $b)
  {
    $t1 = strtotime($a);
    $t2 = strtotime($b);
    return ($t1 == $t2) ? 0 : (($t1 > $t2) ? +1 : -1);
  }

  function edit_link() {}
  /* ////// Setting Utilities ////// */
  function set_date($y = null, $m = null, $d = null)
  {
    $date = $this->parse_date($y, $m, $d);
    list($this->date['year'], $this->date['monthnum'], $this->date['day']) = array($date['year'], $date['monthnum'], $date['day']);
    $this->date['time'] = strtotime($this->datestr());
    $this->ndays();
    return $this;
  }

  function set_time($time = null)
  {
    if ($time === null && $time = strtotime($this->datestr())) $this->date['time'] = $time;
    else $this->date['time'] = $time;
    list($this->date['year'], $this->date['monthnum'], $this->date['day']) = array(
      (int) date('Y', $time),
      (int) date('n', $time),
      (int) date('j', $time)
    );
    $this->ndays();
    return $this;
  }

  /* ////// Misc //////*/
  function set_values()
  {
    global $custom_language_domain;
    $this->meta_box_fields = array(
      'date' => array(
        'label' => __('Date', $custom_language_domain),
        'type' => 'text',
        'script' => '$("#post").bind("submit",function(){
$("#schedule_info_date_0").val($("#schedule_info_date_0").val().replace(/\x2f/,"-"));
if($("#schedule_info_date_0").val().match(/(\d{4})-(\d{2})-(\d{2})/)) {
 var y = RegExp.$1; var m = RegExp.$2; var d = RegExp.$3;
 $("#title").val($("#title").val().replace(/(\d{4}-\d{2}-\d{2})/,y+"-"+m+"-"+d));
 $("#aa").val(y); $("#mm").val(m); $("#jj").val(d); $("#post_name").val(""); } });',
        'default_title_value' => null,
        'datepicker' => 1,
        'datepicker_options' => '{numberOfMonths:3,dateFormat:"yy-mm-dd",altField:"#schedule_info_datetime", altFormat:"@", onClose:function(){var dt=$("#schedule_info_datetime").val(); $("#schedule_info_datetime").val(dt/1000)} }'
      ),
      'open_time' => array(
        'label' => __('Open Time', $custom_language_domain),
        'type' => 'text',
        'script' => null,
        'default_title_value' => null,
        'timepicker' => 1,
        'timepicker_options' => null
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
        'type' => 'text',
        'style' => 'visibility:hidden',
        'script' => '$("#post").bind("submit",function(){if(!$("#schedule_info_post_time_0").val()){$("#schedule_info_post_time_0").val(parseInt((new Date)/1000))} });$("#schedule_info_post_time_box").css({"display":"none"});'
      ),
    );
  }
} // END OF CLASS.SCHEDULECALENDAR
