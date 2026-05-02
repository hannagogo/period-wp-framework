<?php

class CustomUtility_Date
{

    private $data = array();
    private $holiday = null;
    private $date = NULL;


    public function __construct($atts = NULL)
    {

        if (empty($atts) || is_numeric($atts) || is_string($atts)) { // Accepts UNIX Epoch in secs.
            $e = empty($atts) ? time() : intval($atts);
            $atts = array();
            $atts['year'] = date('Y', $e);
            $atts['monthnum'] = date('n', $e);
            $atts['day'] = date('j', $e);
        }

        $a = $this->parse_date_args($atts);

        $this->holiday = new CustomUtility_Date_PublicHoliday_JA($a['year']);
        $this->set_date($a['year'], $a['monthnum'], $a['day']);

        return $this;
    }

    public function datestr($y = NULL, $m = NULL, $d = NULL)
    {
        $k2p = array('y' => 'year', 'm' => 'mon', 'd' => 'day');
        foreach (array('y', 'm', 'd') as $k) {
            if (empty(${$k})) ${$k} = $this->{$k2p[$k]}();
        }
        return implode('-', array($y, $m, $d));
    }

    public function ndays()
    {
        return $this->data['ndays'] = $this->mdays(array('year' => $this->year(), 'monthnum' => $this->mon()));
    }

    public function days_of_month($d = NULL)
    {
        return $this->mdays($d);
    }
    public function mdays($d = array())
    {
        // Calculates and returns number of days in a month
        // THOUGH, date('t', time()) returns the number of days in the month!
        $d = $this->parse_date_args(func_get_args());
        $mdays = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        !isset($d['monthnum']) && $d['monthnum'] = $this->mon();
        !isset($d['year']) && $d['year'] = $this->year();
        $ndays = $mdays[$d['monthnum'] - 1];
        $d['monthnum'] == 2 && $this->isLeap($d['year']) && $ndays++;
        return $ndays;
    }

    public function year($set = NULL)
    {
        if ($set) {
            $this->set_date($set, $this->mon(), $this->day());
            return $this;
        }
        return isset($this->data['year']) ? $this->data['year'] : NULL;
    }
    public function mon($set = NULL)
    {
        if ($set) {
            $this->set_date($this->year(), $set, $this->day());
            return $this;
        }
        return isset($this->data['monthnum']) ? $this->data['monthnum'] : NULL;
    }
    public function day($set = NULL)
    {
        if ($set) {
            $this->set_date($this->year(), $this->mon(), $set);
            return $this;
        }
        return isset($this->data['day']) ? $this->data['day'] : NULL;
    }
    public function time($set = NULL)
    {
        if ($set) {
            $this->set_time($set);
            return $this;
        }
        return isset($this->data['time']) ? $this->data['time'] : NULL;
    }
    public function next_month($format = NULL)
    {
        return $this->calc_month($this->year(), $this->mon(), $this->day(), 1, $format);
    }
    public function previous_month($format = NULL)
    {
        return $this->calc_month($this->year(), $this->mon(), $this->day(), -1, $format);
    }
    public function previous_months_year()
    {
        $mon = $this->mon();
        $year = $this->year();
        return $mon-- ? $year : $year - 1;
    }
    public function next_months_year()
    {
        $mon = $this->mon();
        $year = $this->year();
        return $mon == 12 ? $year + 1 : $year;
    }
    public function isLeap($year = NULL)
    {
        !$year && $year = $this->year();
        if (
            (($year % 4) == 0 && ($year % 100) != 0) || (($year % 400) == 0)
        ) return true;
        return false;
    }

    public function is_holiday($y = NULL, $m = NULL, $d = NULL)
    {
        $a = $this->parse_date_args(func_get_args());
        return $this->holiday->is_holiday($a['year'], $a['monthnum'], $a['day']);
    }

    public function day_of_week($d = NULL)
    {
        $this->wday($d);
    }
    public function wday($d = NULL)
    {
        /* returned value: 0 = SUN, 1 = MON, ... , 6 = SAT */
        $d = $this->parse_date_args(func_get_args());
        list($year, $mon, $mday) = array($d['year'], $d['monthnum'], $d['day']);

        if ($mon == 1 || $mon == 2) {
            $year--;
            $mon += 12;
        }

        $w = (int) ($year + (int)($year / 4) - (int)($year / 100) + (int)($year / 400)
            + (int) ((13 * $mon + 8) / 5) + $mday) % 7;
        return $w;
    }

    public function get_calendar_wdays($start_of_week = 0, $array = array(0, 1, 2, 3, 4, 5, 6))
    {
        $array = array_splice($array, 0, 7);
        return array_merge(array_splice($array, $start_of_week), $array);
    }

    public function get_calendar_index($start_of_week = 0)
    {
        $start_of_week = $start_of_week % 7;
        $days = array_merge(array(0), range(1, $this->mdays()));
        $wd = range(0, 6);
        $index = array();
        $first_day = $this->wday(array('day' => 1));
        $start = $first_day - $start_of_week;
        if ($start < 0) $start += 7;
        if ($start > 0) {
            for ($i = 0; $i < $start; $i++) {
                $index[] = NULL;
            }
        }
        for ($i = 1; $i < count($days); $i++) {
            $index[] = $days[$i];
        }
        return $index;
    }
    /* ////// Setting Utilities ////// */
    function parse_date_args()
    {
        /* //
 Parses implicitly passed arguments.
 Accepts any of these below:
  parse_date_args(2013,4,1);
  parse_date_args(array(2014,1,1));
  parse_date_args(array('year'=>2013, 'monthnum'=>2, 'day'=>28)); // Represents 28th of February, 2014 A.D. (NOT March)
// */
        $args = func_get_args();
        global $CUSTOM_UTILITY;

        while (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }
        $self_args = $CUSTOM_UTILITY->parse_arguments(
            array('year' => date('Y'),     'monthnum' => date('n'),    'day' => date('j')),
            array('year' => $this->year(), 'monthnum' => $this->mon(), 'day' => $this->day())
        );
        if (empty($args)) return $self_args;
        if ($CUSTOM_UTILITY->is_hash($args)) return $CUSTOM_UTILITY->parse_arguments($self_args, $args);
        return $CUSTOM_UTILITY->parse_arguments($self_args, array(
            'year'     => isset($args[0]) && (is_string($args[0]) || is_numeric($args[0])) ? $args[0] : NULL,
            'monthnum' => isset($args[1]) && (is_string($args[1]) || is_numeric($args[1])) ? $args[1] : NULL,
            'day'      => isset($args[2]) && (is_string($args[2]) || is_numeric($args[2])) ? $args[2] : NULL,
        ));
    }

    public function time_to_date($time = NULL, $hash = FALSE)
    {
        global $CUSTOM_UTILITY;
        if ($time === NULL) $time = $this->time();
        if (is_array($time)) {
            $time = $CUSTOM_UTILITY->array_flatten($time);
            $time = $time[0];
        }
        list($y, $m, $d) = array(date('Y', $time), date('n', $time), date('j', $time));
        return $hash ? array('year' => $y, 'monthnum' => $m, 'day' => $d) : array($y, $m, $d);
    }

    public function set_date($y = null, $m = null, $d = null)
    {
        $date = $this->parse_date_args(func_get_args());
        list($this->data['year'], $this->data['monthnum'], $this->data['day'])
            = array($date['year'], $date['monthnum'], $date['day']);
        $this->data['time'] = strtotime($this->datestr());
        $this->ndays();
        $this->holiday->set($this->year(), $this->mon(), $this->day());
        return $this;
    }

    public function set_time($time = null)
    {
        if ($time === null && $time = strtotime($this->datestr())) $this->data['time'] = $time;
        else $this->data['time'] = $time;
        list($this->data['year'], $this->data['monthnum'], $this->data['day']) = array(
            (int) date('Y', $time), (int) date('n', $time), (int) date('j', $time)
        );
        $this->ndays();
        return $this;
    }

    public function calc_month($year, $month, $day, $diff, $format = null)
    {
        $month += $diff;
        $endDay = $this->end_day_of_month($year, $month);
        if ($day > $endDay) $day = $endDay;
        $time = mktime(0, 0, 0, $month, $day, $year); // Regularization
        if ($format) return date($format, $time);
        return $time;
    }


    public function age_by_date($date)
    {
        $year_diff = '';
        $time = strtotime($date);
        if (false === $time) return false;

        $date = date('Y-m-d', $time);
        list($year, $month, $day) = explode('-', $date);
        $year_diff = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff = date("d") - $day;
        if ($day_diff < 0 || $month_diff < 0) $year_diff--;

        return $year_diff;
    }

    public function end_day_of_month($year, $month)
    {
        // pass 0 as date number to mktime and get the end of the previous month
        // $month + 1 may result the month number 13, is to be fixed automatically
        return date("d", mktime(0, 0, 0, $month + 1, 0, $year));
    }

    public function calc_day($year, $month, $day, $diff, $format = null)
    {
        $baseSec = mktime(0, 0, 0, $month, $day, $year); // base date in seconds
        $addSec = $diff * 86400; // number of days in seconds
        if ($format) return date($format,  $baseSec + $addSec);
        return  $baseSec + $addSec;
    }

    public function same_day($time1, $time2 = null)
    {
        return (date('Y-m-d', $time1) == date('Y-m-d', $time2));
    }
    public function in_same_month($time1, $time2 = null)
    {
        return (date('Y-m', $time1) == date('Y-m', $time2));
    }
}

class CustomUtility_Date_Holiday extends CustomUtility_ClassTemplate
{
    public $y = NULL;
    public $m = NULL;
    public $d = NULL;
    public $holidays = NULL;
    public $holidays_2007to2020 = NULL;
    public $custom_holidays = NULL;
    public $holiday_class = NULL;
    public $date;
    public $params = NULL;
    function __construct($param = NULL)
    {
        require_once('class.HashAccessor.php');
        $this->param($CUSTOM_UTILITY->parse_arguments(
            array(
                'type'      => 'Holiday',     //'static', 'Holiday' or 'public_holiday_2.0' (deprecated)
                'holidays'  => NULL,         // assumed array('2013-1-5' => 'Holiday Name') other formats ignored
                'year'      => date('Y'),
                'month'     => date('n'),
                'day'       => date('j')
            ),
            (array) $param
        ));
        $this->set($this->param('year'), $this->param('month'), $this->param('day'));
        $this->custom_holidays = (array) $this->param('holidays');
        $this->setup_holiday_data();

        return $this;
    }


    function setup_holiday_data($_static_data = NULL)
    {
        return $this;
    }


    function is_holiday($y = NULL, $m = NULL, $d = NULL)
    {
        extract($this->date_as_array($y, $m, $d));
        $h = FALSE;
        $datestr = sprintf("%d-%d-%d", $y, $m, $d);;
        switch ($this->param('type')) {
            case 'Holiday':
                $c = array();
                if (isset($this->custom_holidays[$datestr]) && $this->custom_holidays[$datestr]) {
                    $c[$d] = $this->custom_holidays[$datestr];
                }
                $hs = $this->holiday_class->get($y, $m, $c);
                $h = in_array($d, array_keys($hs));
                break;
            case 'static':
                $h = in_array($datestr, array_keys($this->holidays));
                break;
        }
        return $h;
    }


    public function date_as_array($y = null, $m = null, $d = null)
    {
        global $CUSTOM_UTILITY;
        $ymd = $CUSTOM_UTILITY->parse_arguments(
            $CUSTOM_UTILITY->parse_arguments(
                array(
                    'y' => intval(date('Y')),
                    'm' => intval(date('n')),
                    'd' => intval(date('j'))
                ),
                array(
                    'y' => $this->y,
                    'm' => $this->m,
                    'd' => $this->d
                )
            ),
            array('y' => $y, 'm' => $m, 'd' => $d)
        );
        return $ymd;
    }


    public function set($y = null, $m = null, $d = null)
    {
        $ymd = $this->date_as_array($y, $m, $d);
        foreach ($ymd as $k => $v) $this->{$k} = $v;
        $this->date = strtotime(vsprintf('%s-%s-%s', array($ymd['y'], $ymd['m'], $ymd['d'])));
        return $this;
    }

    public function set_static_holidays($param = NULL)
    {
    }
}

class CustomUtility_Date_PublicHoliday_JA extends CustomUtility_ClassTemplate
{
    public $holidays = array();

    public function __construct($year = NULL)
    {
        return $this->get_japan_holidays($year);
    }

    public function get_japan_holidays($year = null)
    {
        return $this;
    }

    public function is_holiday()
    {
    }

    public function set()
    {
    }
}
