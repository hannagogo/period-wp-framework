<?php
require_once("CustomUtility.php");

class CustomUtility_Skel extends CustomUtility_ClassTemplate
{
    /**
     * Skel: Content Template Handler
     */
    public $data = array();
    public $skel = "";
    public $keys = array();
    private $indicator = "%";

    function __construct($args = array())
    {
        global $CUSTOM_UTILITY;
        if (empty($CUSTOM_UTILITY)) {
            $CUSTOM_UTILITY = new CustomUtility;
        }

        $args = $CUSTOM_UTILITY->parse_arguments(
            array(
                "data" => array(),
                "skel" => "",
                "indicator" => "%",
            ),
            $args
        );

        $this->data($args["data"]);
        $this->skel($args["skel"]);
        $this->indicator = $this->indicator($args["indicator"]);
        return $this;
    }
    function indicator($i = NULL)
    {
        if ($i === NULL) {
            return $this->indicator;
        } else {
            $this->indicator = $i;
            return $this->indicator;
        }
    }
    function data($key = NULL, $data = NULL)
    {
        /**
         * Skel Data setter and fetcher
         */
        if (is_array($key) && $data === NULL && !empty($key)) {
            $this->data = $key;
            foreach (array_keys($key) as $k) {
                if (!in_array($k, $this->keys)) {
                    $this->keys[] = $k;
                }
            }
            return $this;
        }
        if ($data == NULL) {
            if (!empty($key)) {
                if (isset($this->data[$key])) {
                    return $this->data[$key];
                } else {
                    return NULL;
                }
            }
            return $this->data; // data == NULL AND key == NULL
        } else if (!empty($key)) {
            $this->data[$key] = $data;
            return $this;
        }
        return $this->data;
    }

    function skel($skel_html = NULL)
    {
        if ((NULL == $skel_html)) {
            return $this->skel;
        }
        $this->keys = array();
        $this->skel = $skel_html;
        preg_match_all("/%(.*?)%/", $skel_html, $matches);
        foreach ($matches[1] as $m) {
            // $this->data[$m] = "";
            $this->keys[] = $m;
        }
    }
    function format($data = NULL)
    {
        $formatted = $this->skel;
        $this->data($data);
        foreach ($this->keys as $k) {
            $formatted = preg_replace("/" . $this->indicator . $k . $this->indicator . "/", $this->data[$k], $formatted);
        }
        return $formatted;
    }
    function html($data = NULL)
    {
        return $this->format($data);
    }
}
