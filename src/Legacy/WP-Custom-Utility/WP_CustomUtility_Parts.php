<?php
class WP_CustomUtility_Parts extends CustomUtility_ClassTemplate
{
    public $name;
    public $object;

    function __construct()
    {
        global
            $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;

        $this->name = 'parts';
        $this->object =
            new WP_CustomUtility_PostType_Object(
                array(
                    'name'     => $this->name,
                    'label'     => __('Parts', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'menu_position' => 60,
                    'feeds' => false,
                    'common_meta_box' => array(
                        'post_settings' => array('fields' => array('css', 'jquerycode', 'js')),
                    ),
                    'singular_name' => __('Part', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'show_submenu_on_dashboard' => array('posts_per_page' => -1),
                )
            );
        add_shortcode(WPCU_PREFIX . 'Part', array(&$this, 'sc_part'));
        add_filter(WPCU_PREFIX . 'Modify_Part', 'do_shortcode', 999, 1);
        add_filter(WPCU_PREFIX . 'Part', array(&$this, 'get_part'), 1, 3);
        add_filter(WPCU_PREFIX . '_Is_Part', array(&$this, 'is_part'), 1, 1);
    }

    public function is_part($post = NULL)
    {
        return get_post($post)->post_type == $this->name;
    }

    public function sc_part($args)
    {
        $args = apply_filters(WPCU_PREFIX . 'Arguments', array(
            0 => NULL,
            'p' => NULL,
            'name' => NULL,
            'field' => 'post_content',
            'prefix' => '',
            'suffix' => '',
            'filter' => 1
        ), $args);
        $key = $keyname = NULL;
        if (!empty($args[0])) {
            if (preg_match('/[^\d]/', $args[0])) {
                $keyname = 'name';
            } else {
                $keyname = 'p';
            }
            $key = $args[0];
        } else {
            if (empty($args['p'])) {
                $key = $args['name'];
                $keyname = 'name';
            } else {
                $key = $args['p'];
                $keyname = 'p';
            }
        }
        $data = $this->get_part($key, $keyname, $args['field']);
        if ($args['filter']) {
            $data = do_shortcode($data);
        }
        return sprintf('%s%s%s', $args['prefix'], $data, $args['suffix']);
    }

    public function get_part($key, $keyname = 'name', $field = 'post_content')
    {
        global $post;
        $post_orig = $post;
        $posts = array();
        if ($key === NULL) return;
        if (empty($field)) $field = 'post_content';
        $posts = get_posts(array(
            'post_type' => $this->name,
            $keyname => $key,
        ));
        if (empty($posts)) return NULL;
        $post = $posts[0];
        if ($keyname == 'object') {
            //  return $post;
        }
        $content = apply_filters(WPCU_PREFIX . 'Parts_Modification', $post->{$field});
        $post = $post_orig;
        return $content;
    }

    function edit_box()
    {
        global $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN, $wp_custom_functions;
        wp_add_dashboard_widget(
            'parts_edit_box',
            __('Edit Parts', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
            array(&$this->object, 'edit_post_type_widget'),
            NULL,
            array($this->name, -1)
        );
    }

    function edit_button($content)
    {
        global $post;
        if (current_user_can('edit_posts')) {
            $content .=
                apply_filters(
                    WPCU_PREFIX . 'HTML_Element',
                    'div',
                    array('class' => 'wpcu_parts_edit_button_box post_edit_button_box'),
                    apply_filters(WPCU_PREFIX . 'HTML', 'a', array('href' => get_edit_post_link($post->ID), 'class' => 'wpcu_parts_data_edit_button post_edit_button'), __('Edit'))
                );
        }
        return $content;
    }
}/* //////// END OF CLASS WP_CustomUtility_Parts //////// */
