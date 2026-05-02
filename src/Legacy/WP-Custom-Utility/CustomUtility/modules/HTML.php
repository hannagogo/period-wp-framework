<?php

class CustomUtility_HTML
{
    public $attrs = array();
    public $elements = array();
    private $created_elements = array("elements" => array());

    public function __construct($utility = NULL)
    {
        $this->define_elements();
        return $this;
    }

    private function define_elements()
    {
        $this->attrs["events"] = array(
            "onabort", "onautocomplete", "onautocompleteerror", "onblur", "oncancel", "oncanplay", "oncanplaythrough", "onchange", "onclick",
            "onclose", "oncontextmenu", "oncuechange", "ondblclick", "ondrag", "ondragend", "ondragenter", "ondragleave", "ondragover",
            "ondragstart", "ondrop", "ondurationchange", "onemptied", "onended", "onerror", "onfocus", "oninput", "oninvalid", "onkeydown",
            "onkeypress", "onkeyup", "onload", "onloadeddata", "onloadedmetadata", "onloadstart", "onmousedown", "onmouseenter", "onmouseleave",
            "onmousemove", "onmouseout", "onmouseover", "onmouseup", "onmousewheel", "onpause", "onplay", "onplaying", "onprogress", "onratechange",
            "onreset", "onresize", "onscroll", "onseeked", "onseeking", "onselect", "onshow", "onsort", "onstalled", "onsubmit", "onsuspend",
            "ontimeupdate", "ontoggle", "onvolumechange", "onwaiting"
        );
        $this->attrs["coreattrs"] = array(
            "id", "class", "slot",
            "accesskey", "autocapitalize", "autofocus", "contenteditable", "dir", "draggable", "enterkeyhint", "hidden", "inert",
            "inputmode", "is", "itemid", "itemprop", "itemref", "itemscope", "itemtype", "lang", "nonce",
            "popover", "spellcheck", "style", "tabindex", "title", "translate"
        );
        $this->attrs["i18n"] = array();
        $this->attrs["global"] = array_merge($this->attrs["coreattrs"], $this->attrs["i18n"], $this->attrs["events"]);

        $this->elements["block"] = array(
            "address", "article", "aside", "blockquote", "canvas", "dd", "div", "dl", "dt", "fieldset", "figcaption", "figure",
            "footer", "form", "h1", "h2", "h3", "h4", "h5", "h6", "header", "hr", "li",
            "main", "nav", "noscript", "ol", "p", "pre", "section", "table", "tfoot", "ul", "video"
        );
        $this->elements["inline"] = array(
            "a", "abbr", "acronym", "b", "bdo", "big", "br", "button", "cite", "code", "dfn", "em", "i",
            "img", "input", "kbd", "label", "map", "object", "output", "q", "samp", "script", "select",
            "small", "span", "strong", "sub", "sup", "textarea", "time", "tt", "var"
        );
        $this->elements["__break_before_and_after"] = array_merge(
            $this->elements["block"],
            array("script",  "textarea", "input", "map", "object", "select", "button")
        );
    }

    public function make_tag($a = '', $b = null, $c = null)
    {
        global $CUSTOM_UTILITY;
        $debug = $_SERVER['QUERY_STRING'] == "DEBUG=DEBUG";
        if ($a === '') {
            return $a;
        }

        $attr = array();
        if (is_string($a)) { // CASE make_tag("div", "start", array("id"=>"my_div"));
            $attr['element'] = $a;
            $attr['start_or_end'] = $b;
            if ($CUSTOM_UTILITY->is_hash($c)) {
                foreach (array_keys($c) as $k) {
                    $attr[$k] = $c[$k];
                }
            }
        } elseif (!$CUSTOM_UTILITY->is_hash($a) && is_array($a)) {
            list($attr['element'], $attr['start_or_end']) = $a;
        } elseif ($CUSTOM_UTILITY->is_hash($a)) {
            $attr = $a;
        }

        if ((isset($attr['element']) && empty($attr['element'])) || !isset($attr['element'])) {
            return;
        }
        if (!isset($attr['start_or_end']) || empty($attr['start_or_end'])) {
            $attr['start_or_end'] = 'start';
        }
        if ($attr['start_or_end'] != 'start' && !$attr['start_or_end'] == 'empty') {
            $attr['start_or_end'] = 'end';
        }

        $tag_v = $attr['start_or_end'] . '_tag_';

        $attrs = $attr;
        unset($attrs['start_or_end']);
        unset($attrs['element']);
        if ($debug) {
            // var_dump($attr);
        }
        $start_tag_open_delimiter   = '<';
        $end_tag_open_delimiter     = '</';
        $start_tag_close_delimiter  = '>';
        $end_tag_close_delimiter    = '>';
        $empty_tag_open_delimiter   = '<';
        $empty_tag_close_delimiter  = '>';

        $attrs_str = $this->make_attributes($attrs);

        $tag = ${$tag_v . 'open_delimiter'} . $attr['element'] . "\x20";
        if ($attr['start_or_end'] == 'start' || $attr['start_or_end'] == 'empty') $tag .= $attrs_str;
        $tag = preg_replace('/ $/', '', $tag);
        $tag .= ${$tag_v . 'close_delimiter'};

        return $tag;
    }

    public function make_attributes($attrs, $quote = "\x22", $concat = "\x2c")
    {
        global $CUSTOM_UTILITY;
        $attrs_str = '';
        foreach ((array) $attrs as $k => $v) {
            if ($k == 'class') {
                $attrs[$k] = implode("\x20", (array) $attrs[$k]);
            } else if ($k == 'id') {
                $attrs[$k] = implode($concat, (array) $attrs[$k]);
            } else if ($k == 'data') {
                $data_string = '';
                if ($CUSTOM_UTILITY->is_hash($v)) {
                    foreach ($v as $dk => $d) {
                        $data_string .= sprintf('%s=%s%s%s ', "data-" . $dk, $quote, (string)$d, $quote);
                    }
                }
                $attrs_str .= $data_string;
            }
            if (((string) $attrs[$k]) != '') {
                $attrs_str .= sprintf("%s=%s%s%s ", $k, $quote, $attrs[$k], $quote);
            }
        }
        return $attrs_str;
    }

    public function remove_attrs($html, $attr)
    {
        foreach ((array) $attr as $a) {
            $html = $this->remove_attribute($html, $a);
        }
        return $html;
    }

    public function remove_attribute($html, $attrname, $quote = array("\x22", "\x27"))
    {
        global $CUSTOM_UTILITY;
        $attrname = preg_replace('/[^A-Za-z0-9\x2d\x5f]/', '', (string) $attrname);
        foreach ((array) $quote as $q) {
            $q = $CUSTOM_UTILITY->char2hex($q);
            $html = preg_replace('/ ' . $attrname . '\x3d' . $q . '.*?' . $q . '/i', '', $html);
        }
        return $html;
    }

    public function create_element($name, $attr = NULL, $content = NULL, $no_tags_if_empty = FALSE, $ignore_tags = FALSE, $force_strip_tags = FALSE)
    {
        /* //
       For SELECT element use html_select_element($attr)
       // */

        global $CUSTOM_UTILITY;
        $out = '';

        $empty_elements = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr', '_comment', '_cdata');
        $form_elements = array('input', 'select', 'option', 'textarea', 'button');

        if (is_string($attr) && ($attr == 'start' || $attr == 'end')) {
            $out .= $this->make_tag($name, $attr, $content);
            return $out;
        }
        if (in_array($name, $empty_elements) !== false) {
            if ($name == '_comment') $out .= '<!-- ' . $attr . ' -->';
            elseif ($name == '_cdata') $out .= '<![CDATA[ ' . $attr . ']]>';
            elseif (
                $name == 'input'
                && in_array($attr['type'], array('checkbox', 'radio'))
                && isset($attr['values'])
                && isset($attr['name'])
            ) {
                $out .= $this->make_checkbox_radio_elements($attr['name'], $attr);
            } else $out .= $this->make_tag($name, 'empty', $attr);
        } // end of empty element
        else {
            $content = (array) $content;
            $end = $this->make_tag($name, 'end');
            $_multiple = count($content) > 1;

            if (count($content) == 0 && !$no_tags_if_empty) $out .= $this->make_tag($name, 'start', $attr) . $end;
            else {
                foreach ($content as $i => $c) {
                    $c_clean = rtrim(trim(strip_tags($c)));
                    if (
                        $no_tags_if_empty
                        &&
                        (
                            (empty($c) || (!$ignore_tags && empty($c_clean)))
                            ||
                            ($ignore_tags && empty($c_clean))
                        )
                    ) {
                        $out .= $force_strip_tags ? $c_clean : $c;
                    } else {
                        $a = $attr;
                        if ($_multiple && isset($attr['id']) && !empty($attr['id'])) {
                            $a['id'] = sprintf('%s_%s', $a['id'], ++$i);
                        }
                        $out .= $this->make_tag($name, 'start', $a) . $c . $end;
                    }
                }
            }
        }

        // Store HTML Elements
        $this->created_elements['elements'][] = $out;
        if (!isset($this->created_elements[$name])) {
            $this->created_elements[$name] = array();
        }
        $this->created_elements[$name][] = $out;
        if (isset($attr['id']) && is_string($attr['id'])) {
            if (!empty($attr['id']) && !isset($this->created_elements[$attr['id']])) {
                $this->created_elements[$attr['id']] = array();
            }
            $this->created_elements[$attr['id']][] = $out;
        }
        if (in_array($name, $this->elements["__break_before_and_after"])) {
            $out = "\n" . $out . "\n";
        }
        return $out;
    }

    public function option_elements($args = null, $single = false)
    {
        global $CUSTOM_UTILITY;
        $args = $CUSTOM_UTILITY->parse_arguments(array(
            'values' => array(),
            'labels' => array(), // !!!! THIS OPTION IS ORDER SENSITIVE. BE CAREFUL ABOUT THE ORDER OF [LABEL] WHEN PASSING AN ARRAY (NON ASSOCIATIVE ARRAY.) 
            'value' => NULL,
            'name'  => '',
        ), $args);
        if (empty($args['values'])) return;
        $values = array();
        $labels = array();
        foreach ($args['values'] as $v) {
            $values[$v] = $v;
        }
        if (empty($args['labels'])) $args['labels'] = $args['values'];
        if (is_array($args['labels']) && !$CUSTOM_UTILITY->is_hash($args['labels']) && (array_keys($args['values']) == array_keys($args['labels']))) {
            foreach ($args['values'] as $i => $v) {
                $labels[$v] = $args['labels'][$i];
            }
        } else $labels = $args['labels'];
        $options = array();
        $default_value = NULL;
        if ($args['value'] === NULL) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[$args['name']])) $default_value = $_GET[$args['name']];
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$args['name']])) $default_value = (array) $_POST[$args['name']];
        } else {
            if ((bool) $args['value']) $default_value = $args['value'];
        }
        foreach ($values as $v) {
            $options[] = $this->create_element(
                'option',
                array_merge(
                    array('value' => $v),
                    ($v == $default_value ? array('selected' => 'selected') : array())
                ),
                $this->create_element('span', array('class' => 'label_text'), $CUSTOM_UTILITY->array_value($labels, $v, ''))
            );
        }
        if ($single) return implode($CUSTOM_UTILITY->Presets->LF, $options);
        return $options;
    }

    public function select_element($args = null)
    {
        global $CUSTOM_UTILITY;

        /* ////
       // USAGE: //
       echo html_select_element( array(
        'name'=>'somename',
        'id'=>'testselect',
        'optgroups'=>array(
         'Group A'=> array('eenie','meenie','miny','moe'),
         'Group B'=> array('catch','the tiger','by', 'the toe'),
         'Group C'=> array('if he', 'hollers','let him', 'go')
        ),
        'labels'=>array(
         'Group A'=> array('EENIE','MEENIE','MINY','MOE'),
         'Group B'=> array('CATCH','THE TIGER','BY', 'THE TOE'),
         'Group C'=> array('IF HE', 'HOLLERS','LET HIM', 'GO')
        )
       ) );
       echo html_select_element( array(
        'name'=>'somename',
        'id'=>'testselect2',
        'values'=>array( 'eenie','meenie','miny','moe' ),
        'labels'=>array('EENIE','MEENIE','MINY','MOE')
       ) );
       //// */

        $atts = $CUSTOM_UTILITY->parse_arguments(array(
            'name' => '',
            'size' => '',
            'multiple' => '',
            'id' => '',
            'class' => '',
            'disabled' => FALSE,
            'tabindex' => ''
        ) + $CUSTOM_UTILITY->make_associative_array($this->attrs["global"]), $args);
        $params = $CUSTOM_UTILITY->parse_arguments(array(
            'name'   => $atts['name'],
            'values' => NULL,
            'optgroups' => NULL,
            // accepts: array("group_name"=> array("val1","val2",..));  'group_name' is used in optgroup attr. label
            'labels' => NULL,
            //'labels' accepts: array("group_name"=> array("Value 1","Value 2",..))
            'value'  => NULL
        ), $args);

        $options = '';
        if (!empty($params['optgroups']) && empty($params['values'])) {
            $_labels_is_hash = FALSE;
            foreach ($params['optgroups'] as $grp_name => $grp) {
                $p = $CUSTOM_UTILITY->parse_arguments($params, array(
                    'values' => $grp,
                    'labels' => isset($params['labels'][$grp_name]) ? $params['labels'][$grp_name] : NULL
                ));
                $options .= $this->create_element('optgroup', array('label' => $grp_name), $this->option_elements($p, $single = 1));
            }
        } else {
            $options = $this->option_elements($params, $single = 1);
        }
        return $this->create_element('select', $atts, $options);
    }


    public function make_checkbox_radio_elements($name, $attr)
    {
        global $CUSTOM_UTILITY;
        $out = '';
        $ids = $labels = array();
        if (!isset($attr['defaults'])) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[$name])) $attr['defaults'] = (array) $_GET[$name];
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$name])) $attr['defaults'] = (array) $_POST[$name];
        }
        $attr = $CUSTOM_UTILITY->parse_arguments(array(
            'name'         => $name,
            'type'         => NULL,
            'values'         => array(),
            'defaults'     => NULL,
            'ids'             => NULL,
            'labels'         => NULL,
            'disabled'     => NULL,
            '_id_base'     => NULL,
            '_id_parts'     => array('name', 'order'), // 'name', 'order', 'value', 'id_base'
            '_wrap_with_label' => FALSE,
            '_use_label'     => TRUE,
            '_use_id'         => TRUE,
            '_id_format'     => '%s_%s',
            '_input_and_label_position_format' => '%s%s', // sprintf format for position of input element and label text
        ), $attr);
        /* //
        This makes each radio/checkbox element's ID from given attributes:
        $atts['_use_id'] == false to set no ID. 'id'
        If $atts['ids'] given, uses $atts['ids'][index] or $atts['ids'][value] (in this order)
        
        $atts['_id_parts'] and $atts['_id_format'] are used to build IDs. vspritf() is called and use these attributes.
        'id_parts' must be an array and can include one of these: 'name', 'order', 'value', 'id_base'
        'name' name attributes of the input element
        'order' index of the value
        'value' value of the input
        'id_base' same as $attr['_id_base']
        // */

        $attr['values'] = (array) $attr['values'];
        $attr['labels'] = (array) $attr['labels'];
        $attr['_id_parts'] = (array) $attr['_id_parts'];
        $disable = (array) $attr['disabled'];

        $id_parts = array(
            'name' => $attr['name'],
            '_id_base' =>  $attr['_id_base'],
        );
        foreach ($attr['values'] as $i => $v) {
            if ($attr['_use_label']) {
                $labels[$attr['values'][$i]] = isset($attr['labels'][$i]) ? $attr['labels'][$i] : $v;
            }
            if ($attr['_use_id']) {
                $id = '';
                $id_parts['order'] = $i;
                $id_parts['value'] = $v;
                $parts = array();
                foreach ($attr['_id_parts'] as $p) {
                    $parts[] = $id_parts[$p];
                }
                $id_base = vsprintf($attr['_id_format'], $parts);

                if (isset($attr['ids'][$i]) && !empty($attr['ids'][$i])) $id = $attr['ids'][$i];
                else if (isset($attr['ids'][$v]) && !empty($attr['ids'][$v])) $id = $attr['ids'][$v];
                else {
                    $id = vsprintf($attr['_id_format'], $parts);
                }
                $ids[$v] = $id;
            }
        }

        foreach ($attr['values'] as $v) {
            $input_attr = $label_attr = array();
            if (!isset($ids[$v])) $ids[$v] = NULL;
            if (!isset($labels[$v])) $labels[$v] = $v;

            $input_attr = array(
                'type' => $attr['type'],
                'name' => $attr['name'] . ($attr['type'] == 'checkbox' ? '[]' : ''),
                'value' => $v,
            );
            $_is_disabled = in_array($v, $disable);
            if ($_is_disabled) {
                $input_attr['disabled'] = 'disabled';
                $input_attr['class'] = 'input_' . $input_attr['type'] . '_disabled input_disabled';
                $label_attr['class'] = 'input_' . $input_attr['type'] . '_disabled_label input_disabled_label';
            }
            if ($attr['_use_label'] !== FALSE) {
                $label_attr['for'] = $ids[$v];
            }
            if ($attr['_use_id'] !== FALSE) {
                $label_attr['id'] = ($ids[$v] ? $ids[$v] : $v) . '_label';
                $input_attr['id'] = $ids[$v];
            }

            if (isset($attr['defaults']) && !empty($attr['defaults']) && in_array($v, (array) $attr['defaults'])) {
                $input_attr['checked'] = 'checked';
            }
            $input = $this->create_element('input', $input_attr);
            if ($attr['_use_label']) {
                if ($attr['_wrap_with_label']) {
                    $out .= $this->create_element('label', $label_attr, sprintf($attr['_input_and_label_position_format'], $input, $labels[$v]));
                } else {
                    $out .= sprintf($attr['_input_and_label_position_format'], $input, $this->create_element('label', $label_attr, $labels[$v]));
                }
            } else {
                $out .= $input;
            }
        }
        return $out;
    }

    public function simple_table_rows($a, $attr = null)
    {
        $html = '';
        $attr = (array) $attr;
        foreach ((array) $a as $h => $d) {
            $row = $this->create_element('th', $attr, $h) . $this->create_element('td', $attr, $d) . "\n";
            $html .= $this->create_element('tr', null, $row) . "\n";
        }
        return $html;
    }


    public function div_table_col($content = NULL, $col_atts = NULL, $col_content_atts = NULL)
    {
        $col_class = 'table_col';
        $col_content_class = 'table_col_content';
        $col_atts = (array) $col_atts;
        $col_content_atts = (array) $col_content_atts;

        if (!isset($col_atts['class'])) $col_atts['class'] = array();
        else $col_atts['class'] = (array) $col_atts['class'];
        if (!isset($col_content_atts['class'])) $col_content_atts['class'] = array();
        else $col_content_atts['class'] = (array) $col_content_atts['class'];

        $col_atts['class'][] = $col_class;
        $col_content_atts['class'][] = $col_content_class;

        return $this->create_element('div', $col_atts, $this->create_element('div', $col_content_atts, $content));
    }


    public function class_attribute($class, $add = NULL, $stringify = FALSE)
    {
        global $CUSTOM_UTILITY;
        if (is_string($class)) $class = preg_split('/\s+/', $class);
        $addn = array();
        foreach ((array) $add as $a) {
            if (is_array($a)) {
                $a = $CUSTOM_UTILITY->array_flatten($a);
            } else if (is_string($a)) {
                $a = preg_split('/\s+/', $a);
            } else {
                continue;
            }
            $addn = array_merge($addn, $a);
        }
        $classes = array_merge($class, $addn);
        return $stringify ? implode(' ', $classes) : $classes;
    }

    public function wrap_JavaScript($js = null, $a = array(), $p = array()) // 要検証
    {
        global $CUSTOM_UTILITY;
        if (!$a) $a = array();
        $n = $CUSTOM_UTILITY->Presets->LF;

        $a = $CUSTOM_UTILITY->parse_arguments(array(
            'tag' => true,
            'cdata' => false,
            'jqueryready' => false,
            'jquery' => false
        ), $a);

        $p['type'] = 'text/javascript';
        if ($a['jqueryready']) $js = '$(function(){ ' . $n . $js . $n . '});';
        if ($a['jquery']) $js = '(function($){ ' . $n . $js . $n . '})(jQuery);';
        if ($a['cdata']) $js =
            $this->create_element('_comment', ' // ') .
            $this->create_element('_cdata', $n . $js . ' // ');
        if ($a['tag']) $js = $this->create_element('script', $p, $js) . $n;
        return $js;
    }

    public function wrap_jQuery($code, $prefix = "($(function($){", $suffix = "}))(jQuery)") // 要検証
    {
        global $CUSTOM_UTILITY;
        $n = $CUSTOM_UTILITY->Presets->LF;

        return $prefix . $n
            . $code . $n
            . $suffix . $n;
    }

    public function truncate_html($html, $length, $ellip = '...', $refine = TRUE)
    {
        $text_length = 0;
        $length = floor($length);
        $truncated = '';
        preg_match_all('/<[^\x3c\x3e]*?>/', $html, $tags); // Matching start tags.
        if (empty($tags[0])) { // No tags found.
            return mb_substr($html, 0, $length) . (mb_strlen($html) > $length ? $ellip : '');
        };
        foreach ($tags[0] as $tag) {
            $re = '/([^\x3c\x3e]*?)(' . str_replace('/', "\x5c\x2f", preg_quote($tag)) . ')/';
            preg_match($re, $html, $match);
            $text = trim($match[1]);
            $len = mb_strlen($text);
            if ($text_length < $length) {
                if ($text_length + $len < $length) {
                    $text_length += mb_strlen($text);
                    $truncated .= $text;
                } else {
                    $text = mb_substr($text, 0, $length - $text_length) . $ellip;
                    $text_length += mb_strlen($text);
                    $truncated .= $text;
                }
            }
            if (!($refine && preg_match('/\x3c(?:br)\x20?\x2f?\x3e/', $match[2]))) {
                $truncated .= $match[2];
            }
            if ($refine) {
            }
            $html = preg_replace($re, '', $html, 1);
        }
        $html = trim($html);
        $truncated .= ($text_length < $length ? mb_substr($html, 0, $length - $text_length) : '');
        return $truncated;
        // mb_convert_encoding(phpQuery::newDocument($truncated)->htmlOuter(), UTF8, 'HTML-ENTITIES') to refine HTML
    }

    public function remove_empty_content($content, $tags, $recursive = TRUE)
    {
        $tags = (array) $tags;
        foreach ($tags as $t) {
            $re = '{<' . $t . '[^>]*?></' . $t . '>}';
            if ($recursive) {
                $content = preg_replace($re, '', $content);
            }
        }
        return $content;
    }

    public function array_to_table($array, $columns = 1, $table_atts = array())
    {
        $array_count = 1;
        $table = $this->create_element('table', 'start', $table_atts);
        if (isset($table_atts['caption']) && $table_atts['caption']) $table .= $this->create_element('caption', $table_atts['caption']);
        foreach ((array) $array as $k => $v) {
            if (preg_match('/^_/', $k)) continue;
            if ($array_count % $columns == 1) $table .= $this->create_element('tr', 'start');
            $table .= $this->create_element('th', null, $k) . $this->create_element('td', null, $v);
            if ($array_count % $columns == $columns) $table .= $this->create_element('tr', 'end');
            $array_count++;
        }
        $table .= $this->create_element('table', 'end');
        return $table;
    }

    public function format_price($price, $atts = null, $html_atts = null)
    {
        global $CUSTOM_UTILITY;

        $atts = $CUSTOM_UTILITY->parse_arguments(array(
            'unit' => '',
            'unit_prefix' => '￥',
            'unit_suffix' => '円',
            'decimals' => 0,
            'placing' => true,
            'unit_position' => 1, // prefix: -1; none: 0; suffix: 1
        ), $atts);
        $html_atts = $CUSTOM_UTILITY->parse_arguments(array(
            'price_class' => 'price',
            'unit_class' => 'price_unit',
            'wrap' => true,
            'wrapper_class' => 'price_box'
        ), $html_atts);

        switch ($atts['unit_position']) {
            case -1:
                if ($atts['unit']) {
                    $atts['unit_prefix'] = $atts['unit'];
                } else {
                    $atts['unit'] = $atts['unit_prefix'];
                }
                break;
            case  0:
                $atts['unit'] = '';
                break;
            case  1:
                if ($atts['unit']) {
                    $atts['unit_suffix'] = $atts['unit'];
                } else {
                    $atts['unit'] = $atts['unit_suffix'];
                }
                break;
        }

        $price = preg_replace('/[^\d\x2e]/', '', $price);
        if ($atts['placing']) $price = number_format($price, $atts['decimals']);

        $unit = $atts['unit'] ? $this->create_element('span', array('class' => $html_atts['unit_class']), $atts['unit']) : '';

        $formatted = sprintf(
            '%s%s%s',
            ($atts['unit_position'] == -1 ? $unit : ''),
            $this->create_element('span', array('class' => $html_atts['price_class']), $price),
            ($atts['unit_position'] ==  1 ? $unit : '')
        );
        if ($html_atts['wrap']) {
            $formatted = $this->create_element('span', array('class' => $html_atts['wrapper_class']), $formatted);
        }
        return $formatted;
    }

    public function format_prices($prices, $atts = NULL, $html_atts = NULL)
    {
        $p = array();
        foreach ((array)$prices as $price) {
            $p[] = $this->format_price($price, $atts, $html_atts);
        }
        return $p;
    }

    public function remove_anchor($url, $content, $remove_entire_tag = FALSE)
    {
        global $CUSTOM_UTILITY;
        $full_url = '';
        $u = array();
        parse_url($url);
        $root = sprintf('%s://%s', $u['scheme'], $u['host']);
        $path = $u['path'];
        if (isset($u['query'])) $full_url .= '?' . $u['query'];
        $re = '/(\x3ca[^>]+)(href=(?:\x27|\x22))(' . str_replace('/', '\x2f', '(?:' . $root . ')?' . preg_quote($path)) . ')(\x22|\x27)(\x20?.*?\x3e)(.*?)(\x3c\x2fa\x3e)/';

        return preg_replace($re, ($remove_entire_tag ? '$6' : '$1$5$6$7'), $content);
    }

    public  function html_entity_replace($matches)
    {
        global $CUSTOM_UTILITY;
        if ($matches[2]) {
            return $CUSTOM_UTILITY->chr_utf8(hexdec($matches[3]));
        } elseif ($matches[1]) {
            return $CUSTOM_UTILITY->chr_utf8($matches[3]);
        }
        switch ($matches[3]) {
            case "nbsp":
                return $CUSTOM_UTILITY->chr_utf8(160);
            case "iexcl":
                return $CUSTOM_UTILITY->chr_utf8(161);
            case "cent":
                return $CUSTOM_UTILITY->chr_utf8(162);
            case "pound":
                return $CUSTOM_UTILITY->chr_utf8(163);
            case "curren":
                return $CUSTOM_UTILITY->chr_utf8(164);
            case "yen":
                return $CUSTOM_UTILITY->chr_utf8(165);
                //... etc with all named HTML entities 
        }
        return false;
    }

    public  function htmlentities2utf8($string)
    // Fix of the html_entity_decode() bug with UTF-8 
    {
        $string = preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $string);
        return $string;
    }

    public function IMG($url, $param = array(), $check_existence = TRUE)
    {
        global $CUSTOM_UTILITY;
        $scheme = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 's' : '');
        $host = $_SERVER['SERVER_NAME'];
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $path = '';
        $attr = array();

        if (preg_match('/^https?\x3a\x2f\x2f/', $url)) {
            if (preg_match('/^https?\x3a\x2f\x2f' . $host . '/', $url)) $path = $CUSTOM_UTILITY->URL->URLToPath($url);
        } elseif (preg_match('/^\x2f/', $url)) {
            if (file_exists($url)) {
                $path = $url;
                $url = preg_replace('/^' . preg_replace('/\x2f/', '\x2f', $document_root) . '/', $scheme . '://' . $host, $url);
            } elseif (file_exists($document_root . $url)) {
                $path = $document_root . $url;
            }
        } else if (file_exists(getcwd() . DIRECTORY_SEPARATOR . $url)) {
            $path = getcwd() . DIRECTORY_SEPARATOR . $url;
        }

        if ($path != '' && file_exists($path)) {
            $imagesize = NULL;
            if (
                (!isset($param['width']) || $param['width'] === NULL)
                ||
                (!isset($param['height']) || $param['height'] === NULL)
            ) {
                $imagesize = getimagesize($path);
            }
            $attr['width'] = (!isset($param['width']) || $param['width'] === NULL) ? $imagesize[0] : $param['width'];
            $attr['height'] = (!isset($param['height']) || $param['height'] === NULL) ? $imagesize[1] : $param['height'];
        } else return;
        $attr['src'] = $url;

        foreach ($this->attrs["global"] as $k) {
            if (isset($param[$k])) $attr[$k] = $param[$k];
        }
        foreach (array_keys($param) as $k) {
            if (preg_match('/^data-/', $k)) {
                $attr[$k] = $param[$k];
            }
        }

        if (isset($param['src']) && $param['src']) return $attr['src'];
        if (isset($param['path']) && $param['path']) return $document_root . $attr['src'];
        if (isset($param['array']) && $param['array']) return $attr;
        if (isset($param['display_url']) && $param['display_url']) $attr['src'] = $param['display_url'];
        if (isset($param['style']) && $param['style']) $attr['style'] = $param['style'];
        return $this->create_element('img', $attr);
    }
};
