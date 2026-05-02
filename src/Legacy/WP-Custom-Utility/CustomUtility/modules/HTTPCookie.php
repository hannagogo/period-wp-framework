<?php
class CustomUtility_HTTPCookie
{
    private $cookie;

    public function __construct($param = null)
    {
        $this->cookie = new HashAccessor(array());
        foreach ((array) $param as $k => $v) {
            if (is_string($v)) $this->cookie($k, $v);
        }
    }

    public function get_cookies($h = null)
    {
        global $http_response_header;
        if (empty($h)) $h = $http_response_header;
        $ca = array();
        foreach ($h as $i => $r) {
            if (strpos($r, 'Set-Cookie') === false) continue;
            $c = explode(' ', $r);
            $cc = explode('=', str_replace(';', '', $c[1]));
            if (!($ca[$cc[0]])) $ca[$cc[0]] = $cc[1];
        }
        return $ca;
    }


    public function cookies_array($c = NULL, $_include_empty_value = FALSE)
    {
        if (empty($c)) return array();
        $cookie = array();
        foreach ($c as $k => $v) {
            if ((empty($v) && $_include_empty_value) || !empty($v)) $cookie[] = $k . '=' . $v;
        }
        return $cookie;
    }

    public function http_cookie_build_simple($data)
    {
        global $CUSTOM_UTILITY;
        $cookie = '';
        if (!empty($data) && $CUSTOM_UTILITY->is_hash($data)) {
            $_ca = array();
            foreach ((array) $data as $k => $v) {
                $_ca = $k . '=' . $v;
            }
            $data = $_ca;
        }
        if (is_array($data)) {
            $cookie = implode('; ', $data);
        } else $cookie = $data;

        if ($cookie) {
            $cookie = preg_replace('/^(?:Cookie: ?)*/', 'Cookie: ', $cookie);
            $cookie = preg_replace('/(?:\x0d\x0a)+$/', $CUSTOM_UTILITY->Presets->CRLF, $cookie);
        }
        return $cookie;
    }

    public function request_header()
    {
        global $CUSTOM_UTILITY;
        $fields = func_get_args();
        if (is_array($fields[0])) $fields = $fields[0];
        if (empty($fields)) $fields = $this->keys();
        $p = array();
        foreach ($fields as $f) {
            $p[] = implode('=', array($f, $this->cookie($f)));
        }
        $s = implode('; ', $p);
        if ($s) $s = 'Cookie: ' . $s . $CUSTOM_UTILITY->Presets->CRLF;
        return $s;
    }

    function parse_header($header)
    {
        foreach ((array) $header as $h) {
            list($f, $s) = preg_split('/: ?/', $h);
            if ($f == 'Set-Cookie') {
                foreach (preg_split('/; ?/', $s) as $p) {
                    list($k, $v) = explode('=', $p);
                    $this->cookie($k, $v);
                }
            }
        }
    }

    public function cookie($key = null, $value = null)
    {
        if ($key) {
            return $this->cookie->param($key, $value);
        }
        return $this->cookie->param();
    }

    public function keys()
    {
        return array_keys($this->cookie());
    }
}
