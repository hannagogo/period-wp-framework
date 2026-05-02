<?php
class CustomUtility_Calendar extends CustomUtility_ClassTemplate
{
    public $calendar = array();
    /**
     * Now. Today.
     */
    private $today = NULL;

    /**
     * Date currently treated.
     */
    private $current = array('year' => NULL, 'month' => NULL, 'day' => NULL);
    private $date = NULL;
    private $wdays = array();
    private $monthnames = array();
    private $templates = array();

    public function __construct($args = array())
    {
        global $CUSTOM_UTILITY;
        $this->date = new CustomUtility_Date;
        $this->now();
        $this->_init();

        if (isset($args['templates'])) {
            $args['templates'] =  $CUSTOM_UTILITY->parse_arguments(
                $this->templates,
                $args['templates']
            );
        }
        $args = $CUSTOM_UTILITY->parse_arguments(array(
            'start_of_week'     => 0,
            'year' => $this->today->year,
            'month' => $this->today->month,
            'templates' => array(),
            'monthnames' => $this->monthnames,
            'wdays' => $this->wdays
        ), $args);

        $this->param($args);
    }


    private function _init()
    {
        // $this->_list_of_accepted_params = array(
        //     'start_of_week',
        //     'templates',
        //     'wdays',
        //     'monthnames',
        //     'templates',
        // );
        $this->wdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        $this->monthnames = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $this->templates = array(
            'day' => '
                <td class="%td_class">
                    %date%
                </td>
',
            'week' => '
        <tr class="%tr_class%" >
            %days%
        </tr>
',
            'header' => '
    <thead>
        <tr class="%tr_class%" >
            %header%
        </tr>
    </thead>
',
            'footer' => '
    <tfoot>
        <tr class="%tr_class%" >
            %footer%
        </tr>
    </tfoot>
',
            'month' => '
<table class="%table_class%" id="%table_id%">
    %header%
    %weeks%
    %footer%
</table>
'
        );
    }

    public function format()
    {
        $skel = new CustomUtility_Skel();
    }

    public function now()
    {
        $t = time();
        $this->today = new CustomUtility_Calendar_Date(
            array(
                'year' => date('Y', $t),
                'month' => date('n', $t),
                'day' => date('j', $t),
                'time' => $t,
            )
        );
        return $this;
    }
}


class CustomUtility_Calendar_Date
{
    public $year;
    public $day;
    public $month;
    public $time;
    public $template;
    public $data;

    function __construct($args = NULL)
    {
        global $CUSTOM_UTILITY;
        $t = time();

        $args = $CUSTOM_UTILITY->parse_arguments(array(
            'year'  => date('Y', $t),
            'month' => date('n', $t),
            'day'   => date('j', $t),
            'wday' => null,
            'time'  => $t,
            'template' => '
            ',
            'data' => array()
        ), $args);

        foreach ($args as $k => $v) {
            $this->{$k} = $v;
        }
    }

    function format()
    {
        $skel = new CustomUtility_Skel(array('data' => $this->data, 'skel' => $this->template));
        return $skel->html();
    }
}
