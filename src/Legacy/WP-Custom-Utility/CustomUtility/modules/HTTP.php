<?php
class CustomUtility_HTTP
{
    public function http_request_simple($url, $data, $meta = null, $scheme = 'http')
    {
        global $http_post_simple_response;
        global $CUSTOM_UTILITY;
        $u = parse_url($url);

        $headers = $CUSTOM_UTILITY->parse_arguments(array(
            'cookie' => '',
            'referer' => '',
            'user-agent' => '',
            'accept-language' => '',
            'host' => $u['host']
        ), $meta);
        $meta = $CUSTOM_UTILITY->parse_arguments(array(
            'method' => 'GET',
        ), $meta);

        if (isset($headers['cookie']) && $headers['cookie']) {
            $headers['cookie'] = $CUSTOM_UTILITY->HTTP->http_cookie_simple_build($headers['cookie']);
        }

        $header = '';
        $post_data = array();
        $meta['method'] = strtoupper($meta['method']);

        $data = http_build_query((is_object($data) || is_array($data) ? $data : (array) $data), "", "&");
        if (!is_string($data)) return null;

        if ($meta['method'] == 'POST') {
            $header
                .= "Content-type: application/x-www-form-urlencoded" . $CUSTOM_UTILITY->Presets->CRLF
                .  "Content-Length: " . strlen($data) . $CUSTOM_UTILITY->Presets->CRLF;
            $post_data = array('content' => $data);
        } else {
            if ($data) $url .= '?' . $data;
        }

        foreach ($headers as $f => $h) {
            if ($h) {
                $fieldname = ucfirst(
                    preg_replace_callback('/(?<=-)([a-z])/', function ($m) {
                        return strtoupper($m[0]);
                    }, $f)
                ) . ': ';
                $h = trim(preg_replace('/^(?:' . $fieldname . '?)*/', $fieldname, $h));
                $h = $h . $CUSTOM_UTILITY->Presets->CRLF;
                $header .=  $h;
            }
        }

        $option = array($scheme => array_merge(
            array(
                'method' => $meta['method'],
                'header' => $header,
            ),
            $post_data
        ));
        //my_print_r($option);
        $d = file_get_contents($url, false, stream_context_create($option));
        $http_post_simple_response = $http_response_header;
        return $d;
    }


    public function http_post_simple($url, $data, $meta = array(), $scheme = 'http')
    {
        global $CUSTOM_UTILITY;
        $meta['method'] = 'POST';
        return $CUSTOM_UTILITY->HTTP->http_request_simple($url, $data, $meta, $scheme);
    }

    public function http_get_simple($url, $data, $meta = array(), $scheme = 'http')
    {
        global $CUSTOM_UTILITY;
        $meta['method'] = 'POST';
        return $CUSTOM_UTILITY->HTTP->http_request_simple($url, $data, $meta, $scheme);
    }

    public function get_http_post_simple_response($parse = NULL)
    {
        global $http_post_simple_response;
        if ($parse) {
            $_r = array();
            foreach ((array) $http_post_simple_response as $r) {
                $a = explode(': ', $r);
                $_r[$a[0]] = $a[1];
            }
            return $_r;
        }
        return $http_post_simple_response;
    }


    public function http_cookie_simple_build($data)
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

    public function get_attachment_filename($response = NULL)
    {
        global $CUSTOM_UTILITY;

        if (!$response) $response = $CUSTOM_UTILITY->HTTPCookie->get_http_post_simple_response();
        if ($response) {
            foreach ((array) $response as $r) {
                if (preg_match('/^Content-Disposition: attachment; filename=(.+?)$/', $r, $m)) {
                    return $m[1];
                    break;
                }
            }
        }
        return NULL;
    }

    public function get_location_header_url($h = null)
    {
        global $http_response_header; // my_print_r(__FUNCTION__); my_print_r($http_response_header);
        if (empty($h)) $h = $http_response_header;
        $lh_re = '/^Location: ?/';
        if (is_array($h)) {
            foreach ($h as $r) {
                if (preg_match($lh_re, $r)) {
                    $r = preg_replace($lh_re, '', $r);
                    return $r;
                    $url = parse_url($r);
                    $queries = array();
                    foreach (explode('&', $url['query']) as $kv) {
                        $kv = explode('=', $kv);
                        $queries[$kv[0]] = $queries[$kv[1]];
                    }
                    return $r;
                }
            }
        }
        return null;
    }


    public function from_same_host($referer = null, $host = null)
    {
        $s = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        if (!$referer) {
            $referer = $_SERVER['HTTP_REFERER'];
        }
        if (!$referer) return false;

        if (!$host) {
            $host = $s;
        }
        $r = parse_url($referer);
        return $r['host'] == $host;
    }
}
