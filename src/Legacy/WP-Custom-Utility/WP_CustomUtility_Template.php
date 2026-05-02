<?php
class WP_CustomUtility_Template extends WP_CustomUtility_Class_Template
{
    public $scripts_and_styles = array(
        "scripts" => array("codes" => array(), "files" => array(), "jquery_codes" => array()),
        "styles"  => array("codes" => array(), "files" => array()),
    );

    public function __construct($args = array())
    {
        global $CUSTOM_UTILITY;
        $args = $CUSTOM_UTILITY->parse_arguments(array(
            'default_priority' => 10
        ), $args);
        $this->init($args);
    }
    private function init($args)
    {
        $this->setup_filters($args);
        $this->setup_actions($args);
        $this->setup_shortcodes($args);
    }

    private function setup_filters($args)
    {
        global $WP_CUSTOM_UTILITY,
            $CUSTOM_UTILITY;

        $i = $args["default_priority"];

        add_filter(WPCU_PREFIX . 'Blog_Info', $this->__object_func("bloginfo"), $i, 1);

        add_filter(WPCU_PREFIX . "Sidebar_Content", $this->__object_func("get_sidebar_content"), 10, 1);

        add_filter(
            WPCU_PREFIX . 'WP_Title_Separator',
            function ($sep = NULL) {
                return $sep !== NULL ? (string) $sep : " : ";
            },
            $i,
            1
        );

        add_filter(WPCU_PREFIX . 'WP_Title',  array(&$this, "get_title"), 10, 1);


        add_filter(
            WPCU_PREFIX . 'Start_HTML',
            function ($html = "", $atts = NULL) {
                global $WP_CUSTOM_UTILITY__TEMPLATE;
                return $WP_CUSTOM_UTILITY__TEMPLATE->start_html($atts)
                    . $html;
            },
            $i,
            2
        );

        ////// Generic Utility
        add_filter(WPCU_PREFIX . 'Format_Price',    array($CUSTOM_UTILITY->HTML, 'format_price'), 3, 1);
        add_filter(WPCU_PREFIX . 'HTML',            array($CUSTOM_UTILITY->HTML, 'create_element'), $i, 5);
        add_filter(WPCU_PREFIX . 'Wrap_JavaScript', array($CUSTOM_UTILITY->HTML, 'wrap_JavaScript'), $i, 2);
        add_filter(WPCU_PREFIX . 'HTML_Select',     array($CUSTOM_UTILITY->HTML, 'select_element'), $i, 1);
        add_filter(WPCU_PREFIX . 'Default_Value',   array($CUSTOM_UTILITY, 'default_value'), $i, 3);
        add_filter(WPCU_PREFIX . 'Remove_HTML_Attribute',  array($CUSTOM_UTILITY->HTML, 'remove_attribute'), $i, 2);
        add_filter(WPCU_PREFIX . 'Truncate_HTML',   array($CUSTOM_UTILITY->HTML, 'truncate_html'), $i, 4);

        ////// Class Functions
        add_filter(WPCU_PREFIX . 'Post_Class',      $this->__object_func('post_class'), $i, 3);
        add_filter(WPCU_PREFIX . "Attachment_Image", $this->__object_func('attachment_image_html'), $i, 1);
        add_filter(WPCU_PREFIX . 'Page_Navigation', $this->__object_func('page_navigation'), $i, 1);
        add_filter(WPCU_PREFIX . 'Nav_Menu',        $this->__object_func('wp_nav_menu'), $i, 1);

        add_filter(WPCU_PREFIX . 'WP_Title_Separator', function ($sep = NULL) {
            return $sep !== NULL ? (string) $sep : " : ";
        }, $i, 1);

        add_filter(WPCU_PREFIX . "Asset_URL", function ($asset) {
            return constant(WPCU_PREFIX . "TEMPLATE_URL") . $asset;
        });
        add_filter(WPCU_PREFIX . "Register_Script_or_Style", $this->__object_func("register_script_or_style"), $i, 5);


        add_filter("nav_menu_css_class", $this->__object_func("wp_nav_menu__css_class"), $i, 3);
    }


    function setup_shortcodes()
    {
        add_shortcode(WPCU_PREFIX . 'TEMPLATE_URL', function ($args) {
            $args = apply_filters('WPCU__Arguments', array(
                'suffix' => ''
            ), $args);
            return get_template_directory_uri() . $args['suffix'];
        });
        add_shortcode(WPCU_PREFIX . 'STYLESHEET_DIRECTORY_URL', function ($args) {
            $args = apply_filters('WPCU__Arguments', array(
                'suffix' => ''
            ), $args);
            return get_stylesheet_directory_uri() . $args['suffix'];
        });
        add_shortcode(WPCU_PREFIX . 'HOME', function ($args) {
            $args = apply_filters('WPCU__Arguments', array(
                'suffix' => ''
            ), $args);
            return home_url() . $args['suffix'];
        });
        add_shortcode(WPCU_PREFIX . 'Nav_Menu', function ($args) {
            return apply_filters(WPCU_PREFIX . "Nav_Menu", $args);
        });
    }

    private function setup_actions()
    {
        add_action(WPCU_PREFIX . "Head", function () {
            global $WP_CUSTOM_UTILITY__TEMPLATE;
            echo $WP_CUSTOM_UTILITY__TEMPLATE->scripts_and_styles_html();
        });
    }

    public function start_html($attr = NULL)
    {
        global $CUSTOM_UTILITY;

        $html = '';
        $version = NULL;
        $n = $CUSTOM_UTILITY->Presets->LF;

        $attr = apply_filters(
            WPCU_PREFIX . "Arguments",
            array(
                'version' => 'html5',
                'elements' => array()
            ),
            (array) $attr
        );
        $version = $attr['version'];

        ob_start();
        language_attributes(preg_match('/^xhtml/', $version) ? 'xhtml' : 'html');
        $lang_attr = ob_get_clean();

        $html .= '<!doctype html>' . $n
            . '<html ' . $lang_attr . '>' . $n
            . '<head >' . $n
            . '<meta charset="' . get_bloginfo('charset') . '">' . $n
            . implode($n, $attr['elements']) . $n;

        return $html;
    }

    public function get_title($args)
    {
        global $wp_query, $post, $CUSTOM_UTILITY;

        $args_tmp = apply_filters("WPCU__Arguments", array(
            'sep' => NULL,
            'seplocation' => NULL,
        ), $args);

        $args = apply_filters('WPCU__Arguments', array(
            'wrap' => FALSE,
            'default' => is_singular() ? get_the_title() : wp_title(NULL, FALSE),
            'attributes' => NULL,
        ), $args);

        $title = '';
        if (is_singular()) {
            if (empty($title)) $title = get_the_title();
            else {
                if (is_array($title) && isset($title[0])) {
                    $title = trim($title[0]);
                }
            }
        } else {
            if (is_post_type_archive()) {
                $title = post_type_archive_title(NULL, FALSE);
            }
            if (is_tax() || is_category() || is_tag()) {
                $title = $wp_query->get_queried_object()->name;
            }
            if (is_date()) {
                $date = $wp_query->query;
                $monthnum = isset($date['monthnum']) ? sprintf(apply_filters('WPCF_Title_Format_Date_Month', '%s'), $date['monthnum']) : NULL;
                $year = isset($date['year']) ? sprintf(apply_filters('WPCF_Title_Format_Date_Year', '%s' . ($monthnum ? '/' : '')), $date['year']) : NULL;
                $title = sprintf(apply_filters('WPCF_Title_Format_Date', '%1$s%2$s'), $year, $monthnum);
            }
            if (is_author()) {
                $title = $wp_query->get_queried_object()->data->display_name;
            }
        }

        if (empty($title)) $title = $args['default'];

        if ($args['wrap']) {
            return $CUSTOM_UTILITY->HTML->create_element($args['wrap'], $args['attributes'], $title);
        }
        return $title;
    }

    public function replace_http_to_https($content)
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $host = $_SERVER['HTTP_HOST'];
            if (preg_match('{http://' . $host . '}', $content)) {
                $content = preg_replace('{http://' . $host . '}', 'https://' . $host, $content);
            }
        }
        return $content;
    }

    public function bloginfo($key = NULL, $trailingslashit = TRUE)
    {
        if ($key == 'home') $key = 'url';
        $i = do_shortcode(get_bloginfo($key));
        if (in_array($key, array('home', 'url', 'stylesheet_directory', 'template_directory', 'siteurl'))) {
            return $trailingslashit ? trailingslashit($i) : $i;
        }
        return $i;
    }

    public function get_sidebar_content($id)
    {
        ob_start();
        dynamic_sidebar($id);
        $d = ob_get_contents();
        ob_get_clean();
        return $d;
    }

    ////// Scipts and Styles //////
    public function set_scripts_and_styles($asset, $class, $var)
    {
        $this->scripts_and_styles[$class][$var] = $asset;
    }
    public function scripts_and_styles_html()
    {
        /** 
         *  We do not enqueue handles. Use wp_enqueue_script / wp_enqueue_style
         *  Refer to https://developer.wordpress.org/reference/functions/wp_register_script/
         */
        global $CUSTOM_UTILITY, $WP_CUSTOM_UTILITY;
        $n = $CUSTOM_UTILITY->Presets->LF;
        $html = "";

        if (!empty($this->scripts_and_styles["styles"]["files"])) {
            foreach ($this->scripts_and_styles["styles"]["files"] as $f) {
                $html .= apply_filters(WPCU_PREFIX . "HTML_Element", "link", array('rel' => 'stylesheet', 'href' => $f, 'media' => 'all', 'class' => 'custom_css_files')) . $n;
            }
        }

        if (!empty($this->scripts_and_styles["styles"]["codes"])) {
            $html .= apply_filters(
                WPCU_PREFIX . "HTML_Element",
                "style",
                array('type' => 'text/css', 'class' => 'custom_css_code'),
                apply_filters(WPCU_PREFIX . "HTML_Element", "_comment", $n . implode($n, $this->scripts_and_styles["styles"]["codes"]) . $n)
            ) . $n;
        }

        if (!empty($this->scripts_and_styles["scripts"]["codes"])) {
            foreach ($this->scripts_and_styles["scripts"]["files"] as $f) {
                $html .= apply_filters(WPCU_PREFIX . "HTML_Element", "script", array('type' => 'text/javascript', 'src' => $f, 'class' => 'custom_css_files'), NULL) . $n;
            }
        }

        if (!empty($this->scripts_and_styles["scripts"]["codes"])) {
            $html .= apply_filters(
                WPCU_PREFIX . "Wrap_JavaScript",
                str_replace(';;', ';', implode(';' . str_repeat($n, 2), $this->scripts_and_styles["scripts"]["codes"])) . $n,
                NULL,
                array('type' => 'text/javascript', 'class' => 'custom_javascript_code')
            ) . $n;
        }

        if (!empty($this->scripts_and_styles["scripts"]["jquery_codes"])) {
            $html .= apply_filters(
                WPCU_PREFIX . "Wrap_JavaScript",
                str_replace(';;', ';', implode(';' . str_repeat($n, 2), $this->scripts_and_styles["scripts"]["jquery_codes"])) . $n,
                array('jquery' => true, 'jqueryready' => true),
                array('class' => 'custom_jquery_code')
            ) . $n;
        }

        return $html;
    }

    public function register_script_or_style($var, $handle, $path, $dep = NULL, $version = NULL)
    {
        /**
         * Registers script with version string based on the modified date of the file.
         * @param string $var "script" or "style"
         * @param string $path relative path of the file (path from theme directory)
         * @param string $dep dependancy
         * @param string $version version if available 
         */

        global $WP_CUSTOM_UTILITY;

        $dir = get_stylesheet_directory();
        $uri = get_stylesheet_directory_uri();

        $fn = preg_replace('/\x3f.*?$/', "", $path); // Remove query string

        if (empty($version)) {
            $version = $WP_CUSTOM_UTILITY->mdstring($dir . $fn);
        }

        if ($var == "script") {
            return wp_register_script($handle, $uri . $path, $dep, $version);
        }
        if ($var == "style") {
            return wp_register_style($handle, $uri . $path, $dep, $version);
        }
    }


    ////// HTML Classes //////

    public function post_class($classes = NULL, $sep = '', $post_or_post_id = NULL, $layout_columns = 0)
    {
        /**
         * Builds custom html class for specific post.
         * @param string $classes Default class(es)
         * @param string $sep Separator of $classes
         * @param string $post_or_post_id Post to handle
         * @param int $layout_columns Itration count of every given number. Ignored when 0, 1 or 2
         */

        global $wp_query;

        $post = get_post($post_or_post_id);
        $post_class = array();
        $layout_column_class = array();

        if ($classes) {
            if (is_string($classes)) {
                $classes = (empty($sep)) ? preg_split('/[,\s]/', $classes) : explode($sep, $classes);
            }
            $post_class = (array) $classes;
        }

        $post_count = $wp_query->current_post;
        $post_count++;

        // // For Column Use
        // if ($layout_columns && $layout_columns >= 3) {
        //     $surplus = $post_count % $layout_columns;
        //     if (0 == $surplus) {
        //         $layout_column_class[] = 'post_count__' . $layout_columns . '-of-' . $layout_columns;
        //     } else {
        //         $layout_column_class[] = 'post_count__' . $surplus . '-of-' . $layout_columns;
        //     }
        // }

        if (!(!is_attachment($post) && current_theme_supports('post-thumbnails') && has_post_thumbnail($post->ID))) {
            $post_class[] = 'no-post-thumbnail';
        }
        $post_class = array_merge(
            $post_class,
            array(
                'article',
                (is_singular() ? 'singular' : 'posts'),
                'post_type__' . $post->post_type,
                'post_count__' . $post_count,
                'post_count__' . (($post_count % 2) ? 'odd' : 'even'),
                is_object($post) ? 'post_name__' . $post->post_name : '',
            ),
            $layout_column_class
        );
        if (current_filter() == 'post_class') {
            return $post_class;
        }
        return get_post_class($post_class);
    }


    ////// Attachment Image //////

    private function _image_arguments($args = NULL)
    {
        /**
         * Handles attachment_image_html arguments
         * @param array $args Arguments.
         */
        return apply_filters("WPCU__Arguments", array(
            'id'    => '',
            'size'  => 'medium',
            'caption'   => NULL,
            'width'     => NULL,
            'height'    => NULL,
            'img_title' => NULL,
            'wrap'  => FALSE,
            'url'   => NULL,
            'href'  => '',
            'style' => NULL,
            'img_classes'   => NULL,
            'alt'           => NULL,
            'description'   => NULL,
            'a_title'       => NULL,
            'a_classes'     => NULL,
            'wrapper'       => 'p',
            'wrapper_classes' => NULL,
            'wrapper_atts'  => NULL,
            'crossorigin'   => NULL,
            'image_ratio_data'  => 2,
            'srcset'    => TRUE,
            'class'     => NULL,
            'border'    => NULL,
            'hspace'    => NULL,
            'ismap'     => NULL,
            'longdesc'  => NULL,
            'sizes'     => NULL,
            'usemap'    => NULL,
            'vspace'    => NULL,
        ), (array) $args);
    }

    public function post_thumbnail_html($post_id = NULL, $param = array())
    {
        $id = get_post_thumbnail_id(get_post($post_id)->ID);
        return $this->attachment_image_html($id, $param);
    }

    public function attachment_image_html($id, $param = array())
    {
        if (empty($id)) return;
        $p = apply_filters("WPCU__Arguments", array('full_image' => TRUE), $param);
        $full_image_url = '';
        $full_image_src_array = NULL;
        if ($p['full_image'] || (isset($param['size']) && $param['size'] == 'full')) {
            $full_image_src_array = wp_get_attachment_image_src($id, 'full');
            $full_image_url = !empty($full_image_src_array) ? $full_image_src_array[0] : '';
        }
        $url_default = ($p['full_image'] && $full_image_url) ? $full_image_url : NULL;
        $default_args = $this->_image_arguments(array(
            'url' => $url_default,
            'href' => $url_default,
            'id' => $id,
        ));
        $args = apply_filters("WPCU__Arguments", $default_args, $param);
        if (!isset($args['href'])) $args['href'] = $args['url'];
        return $this->get_post_image($args);
    }

    public function get_post_image($args = array(), $p = NULL)
    {

        global $post,
            $CUSTOM_UTILITY;
        $post_orig = $post;
        $post = get_post($p);
        $post_thumbnail_id =  get_post_thumbnail_id($post->ID);
        $args_tmp = apply_filters(
            "WPCU__Arguments",
            array(
                'id'   => $post_thumbnail_id,
                'size' => 'full',
            ),
            $args
        );

        if (!$args_tmp['id']) {
            return; /* exits here. */
        }

        $attachment = get_post($args_tmp['id']);
        if (empty($attachment)) {
            return; /* exits here. */
        }
        $alt = get_post_meta($args_tmp['id'], '_wp_attachment_image_alt', $single = 1);
        $filepath = get_attached_file($attachment->ID);
        $attachment_url = wp_get_attachment_url($attachment->ID);

        $args = array_merge($args_tmp, apply_filters("WPCU__Arguments", $this->_image_arguments(array(
            'id'             => $args_tmp['id'],
            'href'         => (is_singular() || is_admin() ? $attachment_url : get_permalink($post->ID)),
            'a_title'         => $post->post_title,
            'img_title'     => $attachment->post_title,
            'alt'             => $alt ? $alt : basename(parse_url($attachment_url, PHP_URL_PATH)),
            'description'     => $attachment->post_content,
            'caption'         => $attachment->post_excerpt,
            'wrap'         => TRUE,
        )), $args));

        if ($args['size'] == 'post-thumbnail') {
            $args['size'] = $this->_post_thumbnail_size_in_additional_image_size_name();
        }
        foreach (array('wrapper_classes', 'a_classes', 'img_classes') as $c) {
            $args[$c] = (array) explode(',', $args[$c]);
        }

        $sizes = $this->get_custom_image_dimensions($args['size'] = $this->get_custom_image_size($args['size']));
        $_is_post_thumbnail = $args['id'] == $post_thumbnail_id;
        $at = wp_get_attachment_image_src($args['id'], $args['size']);

        $ratio = NULL;
        if ((bool) $at && NULL !== $args['image_ratio_data'] && FALSE !== $args['image_ratio_data']) {
            $ratio = round($at[1] / $at[2], $args['image_ratio_data']);
        }


        $land_or_port = array();
        if ($at[1] == $at[2]) {
            $land_or_port[] = 'square';
        } else if ($at[1] > $at[2]) {
            $land_or_port[] = 'landscape';
        } else if ($at[1] < $at[2]) {
            $land_or_port[] = 'portrait';
        };
        global $_wp_additional_image_sizes;

        $a_attr = array(
            'href'  => $args['href'],
            'title' => $args['a_title'],
        );
        $i_attr = array(
            'alt'         => $args['alt'],
            'title'     => $args['img_title'],
            'width'     => $args['width'],
            'height'     => $args['height'],
            'style'     => $args['style'],
            'srcset'     => '',
        );
        if ($ratio) {
            $i_attr['data-image-ratio'] = $ratio;
        }
        $classes = array_merge(
            array(
                'attachment-image_' . $args['size'],
                'attachment-image_width-' . $at[1],
                'attachment-image_height-' . $at[2],
                'attachment-image_id-' . $args['id'],
                'attachment-image_post-' . $post->ID,
            ),
            (array) $args['wrapper_classes'],
            array_map(function ($a) {
                return "attachment-image_dimension-" . $a;
            }, $land_or_port)
        );

        if (!$at[3]) {
            $classes[] = 'attachment-image_original';
        }
        if ($_is_post_thumbnail) {
            $classes = array_merge(array(
                'post-thumbnail',
                'post-thumbnail_' . $post->ID
            ), $classes);
        }
        $wrapper_classes = array();
        foreach ($classes as $c) {
            $wrapper_classes[] = $c . '_wrap';
        }
        $i_attr['class'] = $classes;
        $rewirted = NULL;
        if (!file_exists($rewrited = $CUSTOM_UTILITY->URL->URLToPath($at[0]))) {
            $img_full = get_attached_file($attachment->ID);

            $filename = pathinfo(basename($at[0]));
            $file_ext = isset($filename['extension']) ? $filename['extension'] : NULL;
            $basename = isset($filename['basename'])  ? $filename['basename']  : NULL;
            $filename = isset($filename['filename'])  ? $filename['filename']  : NULL;

            $img_full_filename = pathinfo(basename($img_full));
            $img_full_ext = isset($img_full_filename['extension']) ? $img_full_filename['extension'] : NULL;
            $img_full_basename = isset($img_full_filename['basename']) ?  $img_full_filename['basename'] : NULL;
            $img_full_filename = isset($img_full_filename['filename']) ? $img_full_filename['filename'] : NULL;
            $i_attr['display_url'] = $at[0];
            $at[0] = preg_replace(sprintf('/%s$/', preg_quote($img_full_basename)), $basename, $img_full);
        }

        if ($args['srcset']) {
            $i_attr['srcset'] = wp_get_attachment_image_srcset($args['id']);
        }
        $img = $CUSTOM_UTILITY->HTML->IMG($at[0], $i_attr);

        if ($a_attr['href']) {
            $img = apply_filters("WPCU__HTML_Element", 'a', $a_attr, $img);
        };

        if ($args['wrap']) {
            $content = apply_filters(
                "WPCU__HTML_Element",
                $args['wrapper'],
                array_merge((array) $args['wrapper_atts'], array('class' => $wrapper_classes, 'id' => 'attachment-' . $args['id'])),
                $img
            );
        } else $content = $img;

        $post = $post_orig;
        $post_orig = NULL;
        return $content;
    }
    private function _post_thumbnail_size_in_additional_image_size_name()
    {
        global $_wp_additional_image_sizes;
        $size = 'post-thumbnail';
        if (
            isset($_wp_additional_image_sizes['post-thumbnail'])
            && is_array($_wp_additional_image_sizes['post-thumbnail'])
            && isset($_wp_additional_image_sizes['post-thumbnail']['crop'])
            && (bool) $_wp_additional_image_sizes['post-thumbnail']['crop']
        ) {
            $dimensions = $_wp_additional_image_sizes['post-thumbnail'];
            foreach ($_wp_additional_image_sizes as $s => $d) {
                if ($size == $s) continue;
                if ((bool) $d['crop'] || 1) {
                    if ($d['width'] == $dimensions['width'] && $d['height'] == $dimensions['height']) {
                        $size = $s;
                        break;
                    }
                }
            }
        }
        return $size;
    }

    public function get_custom_image_dimensions($s = 'thumbnail')
    {
        global $_wp_additional_image_sizes;
        $dimensions = array();
        $s = $this->get_custom_image_size($s);
        if (isset($_wp_additional_image_sizes[$s])) {
            $dimensions = array(
                $_wp_additional_image_sizes[$s]['width'],
                $_wp_additional_image_sizes[$s]['height'],
                $_wp_additional_image_sizes[$s]['crop']
            );
        } else {
            if ($w = get_option($s . '_size_w') && $h = get_option($s . '_size_h') && ($crop = get_option($s . '_crop')) !== FALSE) {
                $dimensions = array($w, $h, $crop);
            } else {
                $dimensions = array(get_option('thumbnail_size_w'), get_option('thumbnail_size_h'), get_option('thumbnail_size_crop'));
            }
        };
        return $dimensions;
    }
    function get_custom_image_size($s = 'thumbnail', $below_or_above = 'below')
    {
        $sizes = apply_filters('intermediate_image_sizes', array('thumbnail', 'medium', 'large', 'post-thumbnail', 'full'));
        return in_array($s, $sizes) ?
            $s
            : (($s = $this->get_nearest_image_size($s, $below_or_above)) ? $s :  'thumbnail');
    }

    function get_nearest_image_size($size, $below_or_above = 'below')
    {
        global $_wp_additional_image_sizes;

        $width = absint($size);
        if (empty($size)) {
            return;
        }

        $new_size = '';
        foreach ($_wp_additional_image_sizes as $name => $dim) {
            $w = $dim['width'];
            if ($below_or_above == 'above') {
                if (empty($new_size) && $w > $width) $new_size = $name;
                if ($w > $width && $w < $_wp_additional_image_sizes[$new_size]['width']) {
                    $new_size = $name;
                }
            } else {
                if (empty($new_size) && $w < $width) $new_size = $name;
                if ($w < $width && $w > $_wp_additional_image_sizes[$new_size]['width']) {
                    $new_size = $name;
                }
            }
        }
        return $new_size;
    }
    public function get_attachment_info($attachment = NULL, $key = 'description', $size = NULL)
    {
        if (!$attachment) return;
        if ($id = (is_string($attachment) || is_numeric($attachment)) ? intval($attachment) : 0) {
            $attachment = get_post($id);
        }
        if (is_object($attachment)) {
            switch ($key) {
                case 'description':
                    return $attachment->post_content;
                case 'title':
                    return $attachment->post_title;
                case 'post_title':
                    return $attachment->post_title;
                case 'post_title':
                    return $attachment->post_title;
                case 'caption':
                    return $attachment->post_excerpt;
                case 'post_name':
                    return $attachment->post_name;
                case 'url':
                    $info = wp_get_attachment_image_src($id, $size);
                    return $info[0];
                    break;
            }
        }
        return;
    }


    ////// NAV MENU //////
    public function wp_nav_menu($args)
    {
        $items_wrap = null;
        if (isset($args['items_wrap'])) {
            $items_wrap = $args['items_wrap'];
        }
        $args = apply_filters('WPCU__Arguments', array(
            'menu'          => (isset($args[0]) && !isset($args['menu']) ? $args[0] : ''),
            'menu_class'    => 'menu',
            'menu_id'       => '',
            'container'     => 'nav',
            'container_class'   => '',
            'container_id'  => '',
            'container_aria_label' => '',
            'fallback_cb'   => 'wp_page_menu',
            'before'        => '',
            'after'         => '',
            'link_before'   => '',
            'link_after'    => '',
            'depth'         => 0,
            'walker'        => '',
            'theme_location' => '',
            'item_spacing'  => '',
            'a_class'       => '',
        ), $args);

        $args['echo'] = 0;
        if (!empty($items_wrap)) {
            $args['items_wrap'] = $items_wrap;
        }

        $menu = wp_nav_menu($args);

        if (!empty($args['a_class'])) {
            $menu = preg_replace('/(\x3ca )/', '$1 class="' . implode(' ', (array) $args['a_class']) . '" ', $menu);
        }

        $menu_li = explode('</li>', $menu);
        foreach ($menu_li as &$li) {
            $hrefre = '/(<a .*?href=(?:\x22|\x27))(.*?)(\x22|\x27)/';
            preg_match($hrefre, $li, $m);
            if (!isset($m[2]) || !$m[2]) continue;
            $href_as_class = $m[2];
            $href_as_class = str_replace(
                '/',
                '_',
                str_replace(apply_filters('WPCU__Blog_Info', 'home'), '', $href_as_class)
            );
            $href_as_class = preg_replace(
                '/[%#:]/',
                '---',
                preg_replace('/^_?(.*?)_?$/', '$1', $href_as_class)
            );

            $cn = 'menu-item-' . $href_as_class;
            $li = preg_replace('/(<li .*?)(class=(?:\x22|\x27))(.*?)(\x22|\x27)/', '$1$2$3 ' . $cn . '$4', $li);
        }

        return implode('</li>', $menu_li);
    }

    public function wp_nav_menu__css_class($classes, $current_menu_item, $nav_menu_args)
    {
        /**
         * Filter function appends class(es) to li of  navigation menu (wp_nav_menu)
         * wp_nav_menu(
         *  array(
         *   "additional_li_class" => "class_to_add class2_to_add"
         *  )
         * );
         */
        if (isset($nav_menu_args->additional_li_class)) {
            $classes[] = $nav_menu_args->additional_li_class;
        }
        // var_dump($current_menu_item);
        return $classes;
    }

    public function wp_nav_menu__css_class__add_postname($classes, $current_menu_item, $nav_menu_args)
    {
        /**
         * Filter function appends class based on $post's slug (post_name) to li of navigation menu (wp_nav_menu)
         * 
         * To enable:
         * global $WP_CUSTOM_UTILITY__TEMPLATE;
         * add_filter("nav_menu_css_class", array($WP_CUSTOM_UTILITY__TEMPLATE, "wp_nav_menu__css_class__add_postname"), 10, 3);
         * 
         */

        global $CUSTOM_UTILITY;

        $item = get_post($current_menu_item->object_id);

        $_class_to_add = "";
        if ($item) {
            $post_name = $item->post_name;
            $_class_to_add = "post_name__" . $CUSTOM_UTILITY->url_make_css_easy($post_name);
        }

        $classes[] = $_class_to_add;
        return $classes;
    }

    ////// PAGE NAVIGATION //////
    public function page_navigation($a = array())
    {
        global $paged, $wp_query;

        $a = array_merge(
            array(
                'pages'         => '',
                'range'         => 2,
                'text_prev'     => '&lsaquo;',
                'text_next'     => '&rsaquo;',
                'text_last'     => '&raquo;',
                'text_first'     => '&laquo;',
                'text_ellip'     => '&hellip;',
                '_show_next-prev_button' => 1,
            ),
            (array) $a
        );

        $pages = $a['pages'];
        $range = $a['range'];
        $showitems = ($range * 2) + 1;

        if (empty($paged)) $paged = 1;
        if ($pages == '' && !($pages = $wp_query->max_num_pages)) $pages = 1;

        if ($paged == 1) {
        }
        if (1 != $pages) {
            $c = apply_filters("WPCU__HTML_Element", "nav", 'start', array('id' => 'page_navigation', 'class' => 'clearfix nav'));

            if ($paged > 2 && $paged > $range + 1 && $showitems < $pages) {
                $c .= apply_filters("WPCU__HTML_Element", 'a', array('href' => get_pagenum_link(1), 'class' => 'page_nav_jump page_nav_first'), $a['text_first']);
            }
            if ($paged > 1 && $showitems < $pages)
                $c .= apply_filters("WPCU__HTML_Element", 'a', array('href' => get_pagenum_link($paged - 1), 'class' => 'page_nav_jump page_nav_prev'), $a['text_prev']);

            for ($i = 1; $i <= $pages; $i++) {
                if (1 != $pages && (!($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $pages <= $showitems)) {
                    if ($paged == $i) $c .= apply_filters("WPCU__HTML_Element", 'span', array('class' => 'page_nav_number page_nav_current'), $i);
                    else $c .= apply_filters("WPCU__HTML_Element", 'a', array('class' => 'page_nav_number', 'href' => get_pagenum_link($i)), $i);
                }
            }

            if ($paged + $range < $pages - 1) $c .= apply_filters("WPCU__HTML_Element", 'span', array('class' => 'page_ellip'), $a['text_ellip']);
            if ($paged + $range < $pages)
                $c .= apply_filters("WPCU__HTML_Element", 'a', array('href' => get_pagenum_link($pages), 'class' => 'page_nav_number'), $pages);

            if ($paged < $pages && $showitems < $pages)
                $c .= apply_filters("WPCU__HTML_Element", 'a', array('href' => get_pagenum_link($paged + 1), 'class' => 'page_nav_jump page_nav_next'), $a['text_next']);
            if ($paged < $pages - 1 &&  $paged + $range - 1 < $pages && $showitems < $pages)
                $c .= apply_filters("WPCU__HTML_Element", 'a', array('href' => get_pagenum_link($pages), 'class' => 'page_nav_jump page_nav_last'), $a['text_last']);

            return $c .= apply_filters("WPCU__HTML_Element", "nav", 'end')
                . apply_filters("WPCU__HTML_Element", '_comment', 'END OF #page_navigation');
        }
    }
}
