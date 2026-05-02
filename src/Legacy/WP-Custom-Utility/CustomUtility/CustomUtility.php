<?php
require_once("CustomUtility_ClassTemplate.php");
require_once("CustomUtility_HashAccessor.php");
require_once("CustomUtility_Presets.php");

class CustomUtility
{

    private $modules = array("HTML", "URL", "Date", "HTTP", "HTTPCookie",);
    public $Presets;
    private $options;
    public $CUSTOM_UTILITY_LOADED;

    public $HTML;
    public $URL;
    public $Date;
    public $HTTP;
    public $HTTPCookie;

    public function __construct($args = array())
    {

        $this->Presets = new CustomUtility_Presets();
        $this->options = $this->parse_arguments(
            array(
                "modules" => $this->modules,
                "name" => "CustomUtility"
            ),
            $args
        );

        define("CUSTOM_UTILITY_NAME", $this->options["name"]);
        $this->init();
        $this->__load_modules();

        return $this;
    }

    private function init()
    {
        global $CUSTOM_UTILITY;

        if (function_exists("mb_regex_encoding")) {
            mb_regex_encoding($this->Presets->UTF8);
        }
        mb_internal_encoding($this->Presets->UTF8);
        $this->add_include_path(dirname(__FILE__));

        $_SERVER['FULL_URL'] = // is an alias
            $_SERVER['FULL_URI'] = 'http' .
            ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') .
            '://' . $this->array_value($_SERVER, 'HTTP_HOST', '') .
            ((isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != '80')) ? ':' . $_SERVER['SERVER_PORT'] : '') .
            $this->array_value($_SERVER, 'REQUEST_URI', '');
        $this->CUSTOM_UTILITY_LOADED = TRUE;
        $CUSTOM_UTILITY = $this;

        return $this;
    }

    private function __load_modules()
    {
        foreach ($this->options["modules"] as $m) {
            if (!in_array($m, $this->modules)) {
                continue;
            }
            $class = "CustomUtility_" . $m;
            include_once("modules/" . $m . ".php");

            $this->{$m} = new $class;
        }
    }

    public function parse_arguments_simple($defaults, $args, $recursive = FALSE)
    {
        /**
         * Old version of "parse_arguments" that does not consider adding custom arguments to defalt ones.
         */
        $a = array();
        $defaults = (array) $defaults;
        $args = (array) $args;
        foreach ($defaults as $k => $v) {
            if (isset($args[$k])) {
                if ($recursive && is_array($defaults[$k])) {
                    $a[$k] = $this->parse_arguments($defaults[$k], (array) $args[$k], $recursive);
                } else {
                    $a[$k] = $args[$k];
                }
            } else {
                $a[$k] = $defaults[$k];
            }
        }
        return $a;
    }

    public function parse_arguments($defaults, $args, $recursive = FALSE)
    {
        $a = array();
        $defaults = (array) $defaults;
        $args = (array) $args;
        $default_imploder = ' ';
        $default_append_indicator = '+';

        $imploder_key = '__' . __CLASS__ . '__' . __FUNCTION__ . '__implode';
        $imploder = isset($args[$imploder_key]) ? $args[$imploder_key] : $default_imploder;

        $append_indicator_key = '__' . __CLASS__ . '__' . __FUNCTION__ . '__append';
        $append_indicator = isset($args[$append_indicator_key]) ? $args[$append_indicator_key] : $default_append_indicator;

        foreach ($defaults as $k => $default) {
            $key_addn = $k . $append_indicator;
            $addn = isset($args[$key_addn]) ? $args[$key_addn] : null;
            $user = isset($args[$k]) ? $args[$k] : null;

            if (is_array($default)) {
                if (is_array($user)) {
                    if ($recursive) {
                        $a[$k] = $this->parse_arguments(
                            array_merge($default, (array) $addn),
                            $user,
                            $recursive
                        );
                    } else {
                        $a[$k] = array_merge($user, (array) $addn);
                    }
                } else if (is_string($user) || is_numeric($user)) {
                    $a[$k] = implode($imploder, array_merge(
                        (array) $user,
                        (array) $addn
                    ));
                } else if ($user === NULL) {
                    $a[$k] = array_merge($default, (array)$addn);
                } else {
                    // other types.
                    $a[$k] = $user;
                }
            } else if (is_string($default) || is_numeric($default)) {
                if (is_string($user) || is_numeric($user)) {
                    $a[$k] = implode($imploder, array_merge(
                        (array) $user,
                        (array) $addn
                    ));
                } else if (is_array($user)) {
                    $a[$k] = array_merge((array)$default, (array)$addn);
                } else if ($user === null) {
                    $a[$k] = implode($imploder, array_merge(
                        (array) $default,
                        (array) $addn
                    ));
                } else {
                    // $other types.
                    $a[$k] = $user;
                }
            } else if ($default === NULL) {
                $a[$k] = $user ? $user : $addn;
            } else {
                // other types
                $a[$k] = $user ? $user : $default;
            }
        }
        foreach ($args as $k => $v) {
            /**
             * Look for additional arguments
             */
            if (preg_match('/^(.*?)' . preg_quote($append_indicator) . '$/', $k, $m) && !isset($a[$m[1]])) {
                $a[$m[1]] = $v;
            }
        }
        return $a;
    }

    public function add_include_path($path)
    {
        return set_include_path(implode(
            PATH_SEPARATOR,
            array(get_include_path(), $path)
        ));
        return;
    }

    public function qw($str = '')
    {
        if (is_string($str) && $str) return explode("\x20", preg_replace('/[\s]+/', "\x20", trim($str)));
        return $str;
    }

    public function name_to_dir($str = '')
    {
        if (is_string($str)) {
            $s = DIRECTORY_SEPARATOR;
            return preg_replace('"$s+?$"', "$s", trim($str));
        }
        return;
    }

    public function is_hash($array)
    {
        if (!is_array($array)) return FALSE;
        foreach (array_keys($array) as $k) {
            if (gettype($k) == 'string') return TRUE;
        }
        return FALSE;
        /* // Folloing is well known code. It returns true when the array is not sequential: ie. array(3=>'value', 5=>'value'), etc.
        if (!is_array($array)) return FALSE ;
        return array_keys($array) !== range(0, count($array) - 1);
        // */
    }

    public function echo_function_return_value($function_name)
    {
        if (function_exists($function_name)) {
            $r = $function_name();
            if (!empty($r)) echo $r;
        }
    }

    public function array_value($array, $key, $default = NULL)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
    public function array_flatten_recursive($array, $flat = false)
    {
        if (!is_array($array) || empty($array)) return (array) $array;
        $flat = (array) $flat;
        foreach ($array as $key => $val) {
            if (is_array($val)) $flat = $this->array_flatten($val, $flat);
            else $flat[] = $val;
        }
        return $flat;
    }

    public function array_flatten_iterator(array $arr)
    {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($arr)), false);
    }

    public function array_flatten(array $array)
    {
        return $this->array_flatten_iterator($array);
    }

    public function array_values_empty($array, $recursive = FALSE)
    {
        $_has_value = FALSE;
        foreach ($array as $i) {
            if ($_has_value) break;
            $_item_has_value = FALSE;
            if ($recursive && is_array($i)) $_item_has_value = !($this->array_values_empty($i, $recursive));
            else $_item_has_value = !empty($i);
            $_has_value = $_item_has_value || $_has_value;
        }
        return !$_has_value;
    }

    public function decode_numeric_refernce($string, $quote_style = ENT_COMPAT, $charset = "utf-8")
    {
        $string = html_entity_decode($string, $quote_style, $charset);
        return $string;
    }


    public function base64_encode_urlsafe($s)
    {
        $s = base64_encode($s);
        return (str_replace(array('+', '=', '/'), array('_', '-', '.'), $s));
    }
    public function base64_decode_urlsafe($s)
    {
        $s = (str_replace(array('_', '-', '.'), array('+', '=', '/'), $s));
        return (base64_decode($s));
    }

    public function custom_print_r($a = null, $commentout = null, $verbose = false)
    {
        // if (headers_sent()) {
        echo $this->Presets->LF;
        echo ($commentout ? '<!--' : '<pre>');
        if ($verbose) var_dump($a);
        else print_r($a);
        echo ($commentout ? '-->' : '</pre>');
        echo $this->Presets->LF;
        // }
    }

    public function make_associative_array($keys, $values = null)
    {
        $values = (array) $values;
        $a = array();
        foreach ((array) $keys as $k) {
            $a[$k] = isset($values[$k]) ? $values[$k] : null;
        }
        return $a;
    }


    public function has_caller($func, $class = NULL)
    {
        foreach (debug_backtrace() as $b) {
            if (
                (isset($b['function']) && $b['function'] == $func)
                &&
                ($class ? (isset($b['class']) && $b['class'] == $class) : TRUE)
            ) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function default_value($value, $fallback, $consider_zero = TRUE)
    {
        if ($consider_zero && $value === 0) return $value;
        if (empty($value)) return $fallback;
        return $value;
    }

    public function quote_string($string, $quote = "\x22")
    {
        return $quote . str_replace("\x22", "\x5c\x22", $string) . $quote;
    }

    public function swap_boolean_value($bool, $value = NULL, $strict = FALSE)
    {
        global $CUSTOM_UTILITY;
        // Swaps a boolean value to human readable string. If $strict evaluates only ( false | null ) as false
        $value = $CUSTOM_UTILITY->parse_arguments(array(0, 1), $value);
        $strict = (bool) $strict;
        if (($strict && $bool !== NULL && $bool !== FALSE) || $bool) return $value[1];
        else return $value[0];
    }

    public function char2hex($suppose_char, $prefix = TRUE)
    {
        $hex = '';
        if (preg_match('/^(?:\x5c)(?:x)([0-9a-fA-F]{2})/', $suppose_char, $m)) {
            $hex = $m[1];
        } else {
            $hex = dechex(ord($suppose_char[0]));
        }
        if ($hex) {
            $prefix = ($prefix === TRUE ? '\x' : ($prefix ? $prefix : ''));
            return $prefix . $hex;
        }
    }

    // Thanks to: http://php.net/manual/ja/function.html-entity-decode.php
    /* // > Here is the ultimate functions to convert HTML entities to UTF-8 // */
    public function chr_utf8($code)
    {
        if ($code < 0) return false;
        elseif ($code < 128) return chr($code);
        elseif ($code < 160) // Remove Windows Illegals Cars 
        {
            if ($code == 128) $code = 8364;
            elseif ($code == 129) $code = 160; // not affected 
            elseif ($code == 130) $code = 8218;
            elseif ($code == 131) $code = 402;
            elseif ($code == 132) $code = 8222;
            elseif ($code == 133) $code = 8230;
            elseif ($code == 134) $code = 8224;
            elseif ($code == 135) $code = 8225;
            elseif ($code == 136) $code = 710;
            elseif ($code == 137) $code = 8240;
            elseif ($code == 138) $code = 352;
            elseif ($code == 139) $code = 8249;
            elseif ($code == 140) $code = 338;
            elseif ($code == 141) $code = 160; // not affected 
            elseif ($code == 142) $code = 381;
            elseif ($code == 143) $code = 160; // not affected 
            elseif ($code == 144) $code = 160; // not affected 
            elseif ($code == 145) $code = 8216;
            elseif ($code == 146) $code = 8217;
            elseif ($code == 147) $code = 8220;
            elseif ($code == 148) $code = 8221;
            elseif ($code == 149) $code = 8226;
            elseif ($code == 150) $code = 8211;
            elseif ($code == 151) $code = 8212;
            elseif ($code == 152) $code = 732;
            elseif ($code == 153) $code = 8482;
            elseif ($code == 154) $code = 353;
            elseif ($code == 155) $code = 8250;
            elseif ($code == 156) $code = 339;
            elseif ($code == 157) $code = 160; // not affected 
            elseif ($code == 158) $code = 382;
            elseif ($code == 159) $code = 376;
        }
        if ($code < 2048) return chr(192 | ($code >> 6)) . chr(128 | ($code & 63));
        elseif ($code < 65536) return chr(224 | ($code >> 12)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63));
        else return chr(240 | ($code >> 18)) . chr(128 | (($code >> 12) & 63)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63));
    }

    public function str_indent($str, $tab = "\t")
    {
        $str = preg_replace('/^/', $tab, $str);
        $str = preg_replace('/(\n)/', '$1' . $tab, $str);
        return $str;
    }

    public  function list_directory_entries($dir, $recursive = TRUE, $tab = "\t", $full_path = FALSE)
    {
        $dir_list = '';
        $sep = "/";
        $indent = $full_path ? $dir . $sep : $tab;
        $self = __FUNCTION__;
        if ($recursive && is_dir($dir)) {
            $dir_list .=  ($full_path ? $dir . $sep : basename($dir) . $sep) . $this->Presets->LF;
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/^\x2e{1,2}$/', $file)) {
                        continue;
                    }
                    if (is_dir($dir . $sep . $file)) {
                        $i = $indent;
                        if ($full_path) $i = preg_replace('{^' . $dir . '/}', '', $i);
                        $dir_list .= $this->str_indent(preg_replace('/[\r\n]$/', '', $self($dir . $sep . $file, $recursive, $indent, $full_path)), $i) . $this->Presets->LF;
                    } else {
                        $dir_list .= $indent . $file . $this->Presets->LF;
                    }
                }
                closedir($dh);
            }
        }
        return $dir_list;
    }


    public function make_javascript_array($array, $return = 'STRING' /* // could be 'ARRAY' to get array of quoted values // */)
    {
        $array = (array) $array;
        $array_out = array();
        foreach ($array as $i => $v) {
            $array_out[$i] = sprintf('"%s"', str_replace('"', '\"', $v));
        }
        if ($return == 'STRING') {
            return sprintf('[%s]', implode(',', $array_out));
        }
        return $array_out;
    }

    public function url_make_css_easy($u, $prefix = '', $table = array())
    {
        /**
         * make
         * https://example.com/path/to/some-directory/index.html?foo=bar&baz=qux&q=%E3%83%86%E3%82%B9%E3%83%88#fragment
         * into
         * example_com--path--to--some-directory--index_html___foo-bar--baz-qux--q--_-E3-_-83-_-86-_-E3-_-82-_-B9-_-E3-_-83-_-88____fragment
         * 
         */

        $classes = array();
        $url = parse_url($u);
        $class = '';
        $table = $this->parse_arguments(array(
            '\x2e' => '_',    // . (dots)
            '\x25' => '-_-',  // % (percent signs)
            '\x2f' => '--',   // / (slashes)
            '\x26' => '--',   // & (ampersands)
            '\x3d' => '-',    // = (equal signs)
            '\x23' => '____', // # (nuber signs)
            '\x3f' => '___',  // ? (question)
        ), $table);

        if ($url) {
            $class .= $prefix;
            if (isset($url['host']) && $url['host'] != $_SERVER['HTTP_HOST']) {
                $class .= preg_replace('/\x2e/', $table['\x2e'], $url['host']);
            }
            $path = $query = $fragment = '';
            if (isset($url['path'])) {
                $path = $url['path'];
            }
            if (isset($url['query'])) {
                $query = $url['query'];
            }
            if (isset($url['fragment'])) {
                $fragment = $url['fragment'];
            }
            foreach ($table as $c => $a) {
                if ($path) {
                    $path = preg_replace('/' . $c . '/', $a, $path);
                }
                if ($query) {
                    $query = preg_replace('/' . $c . '/', $a, $query);
                }
                if ($fragment) {
                    $fragment = preg_replace('/' . $c . '/', $a, $fragment);
                }
            }
            $query    ? $query    = $table['\x3f'] . $query : NULL;
            $fragment ? $fragment = $table['\x23'] . $fragment : NULL;
            $class .= $path . $query . $fragment;
        }
        $class = preg_replace('/-$/', '', $class);
        return $class;
    }

    function var_dump_box($data, $style = '')
    {
        $style = 'font-family:"source code pro";width:100%;height:100em;' . $style;
        echo '<textarea style=\'' . $style . '\'>';
        var_dump($data);
        echo '</textarea>';
    }
}
