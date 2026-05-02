<?php

class CustomUtility_URL
{
    public function fix_document_root()
    {
        /* //
        Fixes when $_SERVER['DOCUMENT_ROOT'] is not the site's root directory. 
         e.g. $_SERVER['DOCUMENT_ROOT']/example.com/ is site's root; SAKURA Internet shared rental server
        // */
        if ('support@sakura.ad.jp' == $_SERVER['SERVER_ADMIN']) {
            $path =  $_SERVER["DOCUMENT_ROOT"] . '/' . $_SERVER['HTTP_HOST'];
            if (file_exists($path)) {
                $_SERVER['DOCUMENT_ROOT'] = $path;
                return TRUE;
            }
        }
        return FALSE;
    }

    public function path_to_full_url($path = '', $base = '')
    {
        if (!$base) $base = $_SERVER['FULL_URI'];

        $baseinfo = parse_url($base);

        if (preg_match('/^https?\:\/\//', $path)) return $path;
        elseif (preg_match('/^\//', $path)) return $baseinfo['scheme'] . '://' . $baseinfo['host'] . $path;
        else {
            $base_parts = explode('/', $baseinfo['path']);
            array_pop($base_parts);
            $path_parts = explode('/', $path);

            for ($i = 0; $i < count($path_parts); $i++) {
                if (strcmp($path_parts[$i], '..') == 0) {
                    array_pop($base_parts);
                    continue;
                }
                array_push($base_parts, $path_parts[$i]);
            }
            return (($baseinfo['scheme']) ? $baseinfo['scheme'] . '://' : '') .
                $baseinfo['host'] .
                ((strcmp($base_parts[0], '') == 0) ? '' : '/') .
                join('/', $base_parts);
        }
    }

    public function root_url($url = NULL)
    {
        if ($url === NULL) {
            $url =  $_SERVER['FULL_URI'];
        }
        return preg_replace('/^(.*?(?<!\x2f))\x2f(?!\x2f).*?$/', '$1', $url);
    }

    public function root_relative_url($url = NULL)
    {
        if ($url === NULL) {
            $url =  $_SERVER['FULL_URI'];
        }
        return preg_replace('/^' . preg_quote($this->root_url($url), '/') . '/', '',  $url);
    }

    public function URLToPath($str = '', $docroot = NULL)
    {
        if ($docroot === NULL) $docroot = $_SERVER['DOCUMENT_ROOT'];
        if ($str) return preg_replace('/^https?\x3a\x2f\x2f[^\x2f]*?(\x2f)/', $docroot . '$1', $str);
        return;
    }

    public function current_url()
    {
        return ('http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    }
}
