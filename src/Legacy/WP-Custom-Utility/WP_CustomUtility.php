<?php

/**
 * Everything starts here: 
 * https://developer.wordpress.org/reference/
 * https://codex.wordpress.org/Function_Reference
 */

global $WP_CUSTOM_UTILITY_DIR;
$WP_CUSTOM_UTILITY_DIR = get_stylesheet_directory() . "/Library/WP-Custom-Utility";

require_once($WP_CUSTOM_UTILITY_DIR . "/CustomUtility/CustomUtility.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/CustomUtility/CustomUtility_ClassTemplate.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/CustomUtility/CustomUtility_Skel.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_Class_Template.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_MetaBox.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_PostType_Object.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_Taxonomy.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_Template.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_Posts.php");
require_once($WP_CUSTOM_UTILITY_DIR . "/WP_CustomUtility_Admin.php");

new CustomUtility();

global
    $CUSTOM_UTILITY,
    $WP_CUSTOM_UTILITY,
    $WP_CUSTOM_UTILITY__ADMIN,
    $WP_CUSTOM_UTILITY__POSTS, // POST RELATED
    $WP_CUSTOM_UTILITY__POST_TYPES, // COLLECTION OF POST_TYPES
    $WP_CUSTOM_UTILITY__TEMPLATE, // UTILITY OBJECT
    $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN //
;


class WP_CustomUtility extends WP_CustomUtility_Class_Template
{
    public $default_priority = 10;
    public $PostTypes = array();

    public $SCRIPTS = array(
        "jquery_script" => array(),
        "js" => array(),
    );

    public function __construct()
    {
        $this->setup_constants();
        $this->setup_filters();
        $this->setup_modules();
        $this->setup_actions();
        add_action('after_setup_theme', array(&$this, 'setup_l10n'));

        // $this->setup_l10n();
    }

    private function setup_constants()
    {
        define('WPCU_PREFIX', 'WPCU__');

        define(WPCU_PREFIX . 'TEMPLATE_URL', get_bloginfo('template_url'));
        define(WPCU_PREFIX . 'TEMPLATE_URL_RELATIVE',   preg_replace('{https?:\x2f\x2f' . $_SERVER['SERVER_NAME'] . '}', '', constant(constant("WPCU_PREFIX") . "TEMPLATE_URL")));
        define(WPCU_PREFIX . 'STYLESHEET_URL', get_stylesheet_directory_uri());
        define(WPCU_PREFIX . 'STYLESHEET_URL_RELATIVE', preg_replace('{https?:\x2f\x2f' . $_SERVER['SERVER_NAME'] . '}', '', constant(constant("WPCU_PREFIX") . "STYLESHEET_URL")));

        define(WPCU_PREFIX . 'JS_LIBRARY_URL_ROOT',  constant(constant("WPCU_PREFIX") . "TEMPLATE_URL") . '/JavaScript');
        define(WPCU_PREFIX . 'CSS_LIBRARY_URL_ROOT', constant(constant("WPCU_PREFIX") . "TEMPLATE_URL") . '/CSS');

        define('WPURL', get_bloginfo('wpurl'));
    }

    private function setup_filters()
    {
        global $CUSTOM_UTILITY,
            $WP_CUSTOM_UTILITY;

        $i = $this->default_priority;

        add_filter(WPCU_PREFIX . "HTML_Element", array(&$CUSTOM_UTILITY->HTML, "create_element"), $i, 6);
        add_filter(WPCU_PREFIX . "Arguments",    array(&$CUSTOM_UTILITY, "parse_arguments"), $i, 3);
        add_filter(WPCU_PREFIX . 'Array_Value',  array($CUSTOM_UTILITY, 'array_value'), $i, 3);

        add_filter(WPCU_PREFIX . 'Is_User_Logged_In', $this->__object_func('is_specific_user_logged_in'), 1, 1);
        add_filter(WPCU_PREFIX . 'Is_PostType',       $this->__object_func('is_specific_post_type'), $i, 2);

        add_filter(WPCU_PREFIX . "Append_MDString", function () {
            return true;
        }, $i, 1);
        add_filter(WPCU_PREFIX . "MDString_Format", function ($format = NULL) {
            if (!is_string($format) && apply_filters(WPCU_PREFIX . "Append_MDString", NULL)) {
                $format = 'Y-m-d-h-i-s';
            }
            return $format;
        }, $i, 1);

        add_filter(WPCU_PREFIX . "MetaBox_HTML_Modifier", function ($content) {
            return $content;
        }, $i, 1);

        add_filter(
            WPCU_PREFIX . 'Language_Domain',
            function () {
                global $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;
                return $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;
            }
        );

        add_filter(WPCU_PREFIX . 'parse__tax_query_string', $this->__object_func('parse__tax_query_string'), $i, 2);
    }

    private function setup_actions()
    {
        add_action('wp_head', function () {
            do_action(WPCU_PREFIX . "Head");
        });
    }
    private function setup_modules()
    {
        global $WP_CUSTOM_UTILITY__TEMPLATE, $WP_CUSTOM_UTILITY__POSTS;

        $WP_CUSTOM_UTILITY__TEMPLATE = new WP_CustomUtility_Template();
        $WP_CUSTOM_UTILITY__POSTS = new WP_CustomUtility_Posts();
    }

    public function setup_l10n()
    {
        global $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;
        $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN = 'WP_CustomUtility';

        load_textdomain($WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN, get_template_directory() . '/Library/WP-Custom-Utility/lang/' . $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN . get_locale() . '.mo');
    }

    public function is_specific_post_type($type = 'post', $post = NULL)
    {
        /**
         *  Evaluates if current parsed query shows given post type.
         *  @return FALSE if none matched. TRUE if given post type matches current query. POST_TYPE_NAME if one of the queried matches.
         */

        $post = get_post($post);
        $types = (array) $type;
        $_type_is_array = is_array($type);


        foreach ($types as $t) {
            $_post_type_matched =
                $post ?
                ($t == $this->get_current_post_type($post, NULL, FALSE))
                : ($t == $this->get_current_post_type(NULL, NULL, FALSE));

            if ($_post_type_matched) {
                return $_type_is_array ? $t : TRUE;
            }
        }

        return FALSE;
    }

    public function get_current_post_type($post = NULL, $wp_query = NULL, $object = TRUE)
    {
        global $typenow, $current_screen;
        $type = NULL;
        if ($post = get_post($post)) return $post->post_type;
        if ($wp_query === NULL || !($wp_query instanceof WP_Query)) {
            global $wp_query;
        }

        if ($typenow) {
            $type = $typenow; //check the global $typenow - set in admin.php
        } elseif ($current_screen && $current_screen->post_type) {
            $type = $current_screen->post_type; //check the global $current_screen object - set in sceen.php
        } elseif (isset($_REQUEST['post_type']) && !empty($_REQUEST['post_type'])) {
            $type = sanitize_key($_REQUEST['post_type']);
        }

        if ($type) return $type;

        if (
            (isset($wp_query->queried_object->name) && $type = $wp_query->queried_object->name)
            ||
            (isset($wp_query->queried_object->post_type) && $type = $wp_query->queried_object->post_type)
            ||
            (isset($wp_query->query_vars['post_type']) && $type = $wp_query->query_vars['post_type'])
            ||
            (isset($wp_query->query['post_type']) && $type = $wp_query->query['post_type'])
        ) {
            return $object ? get_post_type_object($type) : $type;
        }

        return NULL;
    }

    public function is_specific_user_logged_in($userlist)
    {
        $u = wp_get_current_user();
        if (in_array($u->ID, (array) $userlist)) return true;
        return false;
    }

    public function JavaScript_Integration()
    {
        global $WP_CUSTOM_UTILITY__TEMPLATE;
        $WP_CUSTOM_UTILITY__TEMPLATE->set_scripts_and_styles(
            '
const TEMPLATE_URL = "' .          constant("WPCU__TEMPLATE_URL") . '",
      TEMPLATE_URL_RELATIVE = "' . constant("WPCU__TEMPLATE_URL_RELATIVE") . '",
      WP_CONTENT_URL = "' .        site_url() . '",
      STYLESHEET_URL = "' .        constant("WPCU__STYLESHEET_URL") . '",
      STYLESHEET_URL_RELATIVE = "' . constant("WPCU__STYLESHEET_URL_RELATIVE") . '",
',
            "scripts",
            "codes"
        );
        $WP_CUSTOM_UTILITY__TEMPLATE->set_scripts_and_styles('window.POST_TITLE_ORIGINAL = jQuery("#title").val();', "scripts", "jquery_codes");

        $WP_CUSTOM_UTILITY__TEMPLATE->set_scripts_and_styles(
            '
const IS_ARCHIVE = ' . (is_archive() ? 'true' : 'false') . ',
      CURRENT_POST_TYPE = "' . apply_filters('WPCU__Current_PostType', NULL, NULL, FALSE) . '"
           '
        );
    }

    public function mdstring($path)
    {
        /**
         * Returns string based on modified time of given file
         * @param string $path the file path
         */

        $mdstr = (file_exists($path)) ? date(apply_filters("WPCU__MDString_Format", NULL), filemtime($path)) : '';
        return $mdstr;
    }

    public function append_mdstring($path, $url, $key = "ver")
    {
        /**
         * Appends version string based on modified time (Ref. function mdstring)
         * Does nothing and returns original url IF the filter WPCU__Append_String returns false/empty
         */
        $cond = apply_filters(WPCU_PREFIX . "Append_MDString", NULL);
        if (!$cond) {
            return $url;
        }
        return $url . "?" . $key . $this->mdstring($path, apply_filters("WPCU__MDString_Format", NULL));
    }

    function get_id_by_slug($slug, $post_type = 'any')
    {
        $posts = get_posts(array('name' => $slug, 'post_type' => $post_type));
        if ($posts) {
            if (1 == count($posts)) {
                return $posts[0]->ID;
            }
        }
    }

    public function is_edit_post($id_or_slug = NULL)
    {
        $p = NULL;
        if ($id_or_slug) {
            if (is_numeric($id_or_slug)) {
                $p = get_post($id_or_slug);
            } else {
                $p = get_post($this->get_id_by_slug($id_or_slug));
            }
            if ($p) {
                return ((isset($_GET['post']) && $p->ID == $_GET['post']) || (isset($_POST['post_ID']) && $p->ID == $_POST['post_ID']));
            }
            return false;
        } else {
            return $this->is_edit_page();
        }
    }


    public function is_edit_page($new_or_edit = NULL)
    {
        global $pagenow;
        //make sure we are on the backend
        if (!is_admin()) return false;

        if ($new_or_edit == "edit")
            return in_array($pagenow, array('post.php',));
        elseif ($new_or_edit == "new") //check for new post page
            return in_array($pagenow, array('post-new.php'));
        else //check for either new or edit
            return in_array($pagenow, array('post.php', 'post-new.php'));
    }

    public function is_specific_taxonomy_queried($taxonomy, $boolean = FALSE)
    {
        global $wp_query, $query_string;

        if (isset($wp_query->tax_query) && isset($wp_query->tax_query->queries)) {
            foreach ($wp_query->tax_query->queries as $q) {
                if ($q['taxonomy'] == $taxonomy) return $boolean ? TRUE : $taxonomy;
            }
        }
        return FALSE;
    }

    public function register_jquery_script($script)
    {
        /**
         * @param script string
         */
        global $WP_CUSTOM_UTILITY, $CUSTOM_UTILITY;
        $WP_CUSTOM_UTILITY->scripts["jquery_scripts"][] = $script;
        return $this;
    }

    public function enqueue_scripts($hook = "wp_head")
    {
        add_action($hook, function () {
            global $WP_CUSTOM_UTILITY, $CUSTOM_UTILITY;
            echo '
<script id="custom-jquery-code">
(function($){
$(function(){           
'
                . $CUSTOM_UTILITY->Presets->LF
                . implode($CUSTOM_UTILITY->Presets->LF, $WP_CUSTOM_UTILITY->scripts["jquery_scripts"])
                . $CUSTOM_UTILITY->Presets->LF
                . $CUSTOM_UTILITY->Presets->LF
                . '
});})(jQuery)
</script>
';
        });
    }

    public function get_multi_post_meta($post_id, $key, $single = FALSE, $index = NULL)
    {
        $meta = (array) get_post_meta($post_id, $key, FALSE);
        $a = array();
        if (count($meta) == 1) {
            /* //
            Beaware in a case like below this returns a multi dimensional array (array of arrays)
                array(
                    0 => array( v1, v2, ... )
                    1 => array( w1, w2, ... ) // Occurs only with CHECKBOXES, RADIOS, SELECTS. 
                    2 => array( x1, x2, ... ) // : 
                )
         // */
            while (isset($meta[0]) && is_array($meta[0])) $meta = $meta[0];
            $a = array_merge($a, (array) $meta);
        } else {
            if ($single) {
                foreach ($meta as $m) {
                    while (isset($m[0]) && is_array($m[0])) $m = $m[0];
                    $a = array_merge($a, (array) $m[0]);
                }
            } else {
                $a = $meta;
            }
        }
        return $single ?
            implode('', $a)
            : ($index !== NULL ? $a[$index] : $a);
    }


    function parse__tax_query_string($string = NULL, $return_tax_query_key = true)
    {
        /**
         * parses taxonomy query usable in shortcode;
         * 
         * [{shortcode} taxonomy=AND(movie_janner->slug(action,comedy)&actor->id(103,115,206);operator->NOT_IN)]
         * :
         * as 
         * taxonomy_name->field(value,value2)&taxonomy_name_2->field(id1,id2,id3) 
         */
        $args = array();
        if (!$string) return $args;
        if ($string = preg_replace('/-(?:(?:&gt;)|>)/', '=>', $string)) {
            if (preg_match('/^(AND|OR)\x28(.*?)\x29$/', $string, $matches)) {
                $args['tax_query'] = array('relation' => $matches[1]);
                foreach (preg_split('/\x26(?:amp;)?/', $matches[2]) as $t) {
                    if ($t) {
                        $a = array();
                        foreach (explode(';', $t) as $p) {
                            $p = explode('=>', $p);
                            if (isset($p[1]) && preg_match('/^(.*?)\x28(.*?)\x29$/', $p[1], $m)) {
                                $a['taxonomy'] = $p[0];
                                $a['field'] = $m[1];
                                $a['terms'] = explode(',', $m[2]);
                            } else {
                                $a[$p[0]] = ($p[0] == 'operator' && isset($p[1])) ? str_replace('_', ' ', $p[1]) : '';
                            }
                        }
                        $args['tax_query'][] = $a;
                    }
                }
            } else {
                if (preg_match('/^(.*?)(?:[-=])\x3e(.*?)\x28(.*?)\x29?$/', $string, $matches)) {
                    list($string, $tax, $field, $value) = $matches;
                    $args['tax_query'] = array('relation' => 'AND');
                    $args['tax_query'][] = array('field' => $field, 'taxonomy' => $tax, 'terms' => explode(',', $value)); //$value; split(',',$term);
                }
            }
        }
        return $return_tax_query_key ? $args : $args['tax_query'];
    }

    function isolate_id($base, $increment = 1, $append_first = FALSE, $append_count = TRUE, $concat = '_')
    {
        global $wpcf_id_counts;
        $base = (string) $base;
        $increment = (int) $increment;
        $append_count = (bool) $append_count;
        $id = $base;
        if (!isset($wpcf_id_counts[$base])) {
            $wpcf_id_counts[$base] = 0;
            if (FALSE === (bool) $append_first) return $base;
        }
        $wpcf_id_counts[$base] += $increment;
        $suffix = $concat . $wpcf_id_counts[$base];
        if ($append_count) $id .= $suffix;
        return $id;
    }
}

$WP_CUSTOM_UTILITY = new WP_CustomUtility();
