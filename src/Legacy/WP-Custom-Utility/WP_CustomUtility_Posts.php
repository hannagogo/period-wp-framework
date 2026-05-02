<?php

new WP_CustomUtility_Posts;

class WP_CustomUtility_Posts extends WP_CustomUtility_Class_Template
{
    function __construct()
    {
        add_shortcode("posts", array(&$this, "sc_posts"));
    }

    function get_posts_and_navigation($args)
    {
        global $wp_query, $paged, $post, $WP_CUSTOM_UTILITY__TEMPLATE;
        $wp_query_orig = $wp_query;
        $post_orig = $post;
        $paged_orig = $paged;
        $navigation = "";
        $m = array();

        $re_page = isset($args['paged_re']) && $args['paged_re'] ? $args['paged_re'] : '\x2fpage\x2f(\d+)\x2f?$';

        if (isset($args['paged']) && $args['paged'] === 0) {
            $paged = 0;
        } else
    if (preg_match("/$re_page/", $_SERVER["REQUEST_URI"], $m)) {
            $paged = $m[1];
        }

        $args = apply_filters("WPCU__Arguments", array(
            'paged' => $paged,
            'query_args' => array(),
            '_page_navigation' => 1,
            '__navigation_args' => '', // key1:var1;key2:var2
        ), $args);

        $wp_query = new WP_Query(
            apply_filters("WPCU__Arguments", $args['query_args'], array('paged' => $paged))
        );
        $posts = get_posts($args);

        $navigation_args = array();

        if ($args['_page_navigation']) {
            if ($args['__navigation_args']) {
                foreach (explode(';', $args['__navigation_args']) as $kv) {
                    list($k, $v) = explode(':', $kv);
                    $navigation_args[$k] = $v;
                }
            }
            $navigation = $args['_page_navigation'] ? $WP_CUSTOM_UTILITY__TEMPLATE->page_navigation($navigation_args) : '';
        }
        $wp_query = $wp_query_orig;
        $post = $post_orig;
        $paged = $paged_orig;
        return array($posts, $navigation);
    }


    public function sc_posts($atts)
    {
        global $post, $wp_query, $more, $WP_CUSTOM_UTILITY, $CUSTOM_UTILITY;
        $current_blog_id = $GLOBALS["blog_id"];

        $id_base = 'list_posts';
        $class_base = '_container';
        $atts_tmp = apply_filters(WPCU_PREFIX . 'Arguments', array(
            'id' => $id_base,
            'container_tag' => 'ul',
            'post_content' => FALSE
        ), $atts);
        $atts_tmp['container_tag'] = strtolower($atts_tmp['container_tag']);

        if (isset($atts['allow_duplicate_id']) && !$atts['allow_duplicate_id']) {
            $atts_tmp['container_id'] = $WP_CUSTOM_UTILITY->isolate_id($atts_tmp['container_id']);
        }
        $atts_tmp['class'] = ($atts_tmp['id']) ? $atts_tmp['id'] . '_post_item' : preg_replace('/(list_post)s?$/', '$1', $atts_tmp['id']) . '_item';
        $atts_tmp['container_class'] = $atts_tmp['id'] . '_container';
        $atts_tmp['container_id'] = $atts_tmp['id'] . '_container';

        $post_orig = $post; // keep original $post
        $wp_custom_attributes = apply_filters(WPCU_PREFIX . 'Arguments', array(
            'posts_per_page'    => get_option('posts_per_page'),
            'order'             => 'DESC', // 'ASC'
            'orderby'           => 'date',
            // accepts: 'author', 'category','content', 'date', 'ID', 'menu_order', 'mime_type',
            //          'modified', 'name', 'parent', 'password', 'rand', 'status', 'title', 'type'
            //          'meta_value_num', 'post__in', 'meta_value'
            'meta_key'          => '', // must be with 'orderby'=>'meta_value' / 'meta_value__in' / 'meta_value__not_in' / 'meta_value__and'
            'meta_value'        => NULL,
            'meta_value__in'    => NULL,
            'meta_value__not_in' => NULL,
            'meta_value__and'   => NULL,
            'random'            => FALSE,
            'post_type'         => '',
            'name'              => '',
            'post_status'       => 'publish',
            'pagename'          => '',
            'tax_query'         => '',
            // Like: 'AND(movie_janner->slug(action,comedy)&actor->id(103,115,2);operator->NOT_IN&some_other_tax->slug(term1,term2))'
            'blog_id'           => $current_blog_id,
            'p'                 => '', // alias of post__in
            'post__in'          => '', // include post by ids
            'post__not_in'      => '', // exclude post by ids
            'category'          => '',
            'category_name'     => '',
            'taxonomy'          => '',
            'terms'             => '',
            'tax_query_relation' => 'AND',
            'tax_query_field'   => 'slug',
            'remove_filters'    => '', // hook_name=>([priority]=>filter_name)&the_content=>(10=>my_filter,9999=>my_another_filter)
            'class'             => $atts_tmp['class'],
            'container_id'      => $atts_tmp['container_id'],
            'container_class'   => $atts_tmp['container_class'],
            'id'                => $atts_tmp['id'],
            'title_tag'         => 'h1',
            'title_class'       => array('article_title'),
            'no_title'          => FALSE,
            'no_title_link'     => FALSE,
            'remove__a_tag'     => FALSE,
            'article_tag'       => 'article',
            'container_tag'     => $atts_tmp['container_tag'],
            'item_tag'          => ($atts_tmp['container_tag'] == 'ul' || $atts_tmp['container_tag'] == 'ol') ? 'li' : 'div',
            'post_content'      => FALSE,
            'post_excerpt'      => $atts_tmp['post_content'] ? FALSE : TRUE,
            'post_thumbnail'    => FALSE,
            'post_thumbnail_number'     => 0, // 0: Post Thumbnail (default); 1 or more: Alternate Post Image;
            'post_thumbnail_fallback'   => TRUE,
            'post_thumbnail_size'       => 'thumbnail',
            'post_thumbnail_link'       => TRUE, // FALSE to link to the originai image
            'post_thumbnail_position'   => 0, // 0/prepend: prepend, 1/append: append
            'use_more'          => FALSE, // Uses <!--more--> short tag
            'morelinktext'      => $atts_tmp['post_content'] ? '' : '...',
            'hide_more_when_nomore' => TRUE,
            'truncate'          => '',
            'strip_teaser'      => NULL,
            'handle_post_scripts' => FALSE, // In Progress
            'handle_post_styles' => FALSE, // In Progress
            'more_file'         => NULL,
            'empty_fallback'    => TRUE,
            'allow_duplicate_id' => FALSE,
            'context'           => NULL, // USE FOR PASSING CUSTOM DATA
            'post_count_suffix' => 'post_count_',
            'post_count_start'  => 1,
            'link_to'           => 'permalink', // or "fragment" for inner page link
            'fragment_id'       => '', // Prefix for inner link
            'no_link'           => FALSE,
        ), $atts);
        extract($wp_custom_attributes);
        $wp_custom_attributes['_shortcode_function'] = __FUNCTION__;
        $wp_custom_attributes['_function'] = __FUNCTION__;
        $post_status = explode(',', $post_status);

        if ($p && empty($post__in)) {
            $post__in = explode(',', $p);
        } else if (!empty($post__in)) {
            $post__in = explode(',', $post__in);
        }

        if (!$post_type) {
            // * if $include (assumed $p, Post IDs) specified then involve any post_types
            if ($post__in) $post_type = 'any';
            else $post_type = 'post';
        }
        $post_type = explode(',', $post_type);

        if ($current_blog_id != $blog_id) switch_to_blog($blog_id);

        remove_shortcode('posts'); // ////// Prevent infinite loop ////// //

        $tax_query = $WP_CUSTOM_UTILITY->parse__tax_query_string($tax_query);
        //if (is_specific_user_logged_in(1)) my_print_r($tax_query);
        $cat = $category ? array('cat' => $category) : array();
        if ($taxonomy && $terms && empty($tax_query)) {
            $terms = explode(',', $terms);
            $tax_query = array('tax_query' => array(
                'relation' => $tax_query_relation,
                array(
                    'taxonomy' => $taxonomy,
                    'field' => $tax_query_field,
                    'terms' => $terms,
                ),
            ));
        }


        $args = array_merge($cat, compact('name', 'category', 'posts_per_page', 'post__not_in', 'post__in', 'order', 'orderby', 'category_name', 'post_type', 'post_status'), $tax_query);
        if ((bool) $args['post__in']) {
            unset($args['post__not_in']);
        } else if ((bool) $args['post__not_in']) {
            unset($args['post__in']);
        }

        $the_content_tn_priority = has_filter('the_content', 'add_post_thumbnail');
        $the_excerpt_tn_priority = has_filter('the_excerpt', 'add_post_thumbnail');
        remove_filter('the_content', 'add_post_thumbnail');
        remove_filter('the_excerpt', 'add_post_thumbnail');

        $ul_classes = explode(',', $container_class);
        foreach ($post_type as $p) {
            $ul_classes[] = $p . '_container';
        }
        $ul_classes[] = $id_base . '_container';

        $article_end = apply_filters(WPCU_PREFIX . 'HTML_Element', $article_tag, 'end');
        $formed_posts = array();

        $wp_query_orig = $wp_query;
        $wp_query = new WP_Query($args);

        $post_count = $post_count_start;
        $_in_the_loop_orig = in_the_loop(); // if (is_specific_user_logged_in(1)) my_print_r( $wp_query->query );

        if (have_posts()) while (have_posts()) {
            the_post();
            if ($meta_key) {
                $meta_query_passed = FALSE;

                $meta_values = apply_filters('WPCF_Get_Post_Meta', $post->ID, $meta_key, FALSE);
                if ($meta_value) {
                    if (in_array($meta_value, $meta_values)) $meta_query_passed = TRUE;
                } else if ($meta_value__in) {
                    $values = explode(',', $meta_value__in);
                    foreach ($values as $v) {
                        if (in_array($v, $meta_values)) {
                            $meta_query_passed = TRUE;
                            break;
                        }
                    }
                } else if ($meta_value__not_in) {
                    $values = explode(',', $meta_value__not_in);
                    foreach ($values as $v) {
                        if (in_array($v, $meta_values)) {
                            $meta_query_passed = FALSE;
                            break;
                        } else {
                            $meta_query_passed = TRUE;
                        }
                    }
                } else if ($meta_value__and) {
                    $values = explode(',', $meta_value__and);
                    $meta_query['relation'] = 'AND';
                    foreach ($values as $v) {
                        if (in_array($v, $meta_values)) {
                            $meta_query_passed = TRUE;
                        } else {
                            $meta_query_passed = FALSE;
                            break;
                        }
                    }
                }
                if (!$meta_query_passed) {
                    continue;
                }
            }
            if ($use_more) {
                $more = 0;
                $post_excerpt = TRUE;
                $post_content = FALSE;
                //   if ($post_excerpt !== TRUE) { $post_excerpt = (bool) $post_excerpt; }
                //   if ($post_content !== FALSE) { $post_content = (bool) $post_content; }
            }
            $_post_is_original_post = $post instanceof WP_Post && $post_orig->ID == $post->ID;
            $the_content = $the_excerpt = $the_title = $morelink = $more_text = $c = $post_thumbnail_html = $img = $the_post_content = '';

            $c .= apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                $article_tag,
                'start',
                array(
                    'id' => $class . '_article_' . $post->ID,
                    'class' => apply_filters('WPCF_Post_Class', array(
                        'article',
                        $class . '_article',
                        $id_base . '_article',
                        $CUSTOM_UTILITY->url_make_css_easy($post->post_name, $class . '-post_name-'),
                        $CUSTOM_UTILITY->url_make_css_easy(get_permalink($post->id), $class . '-url-'),
                        has_post_thumbnail() ? 'has-post-thumbnail' : 'no-post-thumbnail'
                    ))
                )
            );

            $_link_to_fragment =
                ($link_to == 'fragment')
                &&
                ('' !== $fragment_id);
            $link =
                ((bool)$no_link) ? ''
                : (($_link_to_fragment) ? '#' . $fragment_id . '_post_content_' . $post->ID : get_permalink());
            if (!$no_title) {
                $the_title = the_title(NULL, NULL, FALSE);
                if ($no_title_link || $no_link) {
                    $re = $remove__a_tag ? '/<\x2f?a.*?>/' : '/ href=\x22.*?\x22/';
                    $the_title = preg_replace($re, '', $the_title);
                }
                if ($_link_to_fragment) {
                    $the_title = preg_replace('/(href=\x22).*?(\x22)/', '$1' . $link . '$2', $the_title);
                }
                $the_title = apply_filters(
                    WPCU_PREFIX . 'HTML_Element',
                    $title_tag,
                    array('class' => $title_class),
                    $the_title,
                );
            }

            $more_text .= $morelinktext ? $morelinktext : '';;
            if ($_post_is_original_post && $_in_the_loop_orig) {
                $the_content = $post->post_content;
                $the_excerpt = $post->post_excerpt;
            } else {
                if ($post_content || $use_more) {
                    $the_content = apply_filters('the_content', get_the_content($more_text, $strip_teaser, $more_file));
                }
                if ($post_excerpt) {
                    $the_excerpt = apply_filters('the_excerpt', get_the_excerpt());
                }
            }

            if (!$use_more && $more_text) $morelink .= apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                'a',
                array('href' => $link, 'class' => array('morelink', $id . '_morelink', $class . '_morelink')),
                $more_text
            );

            if ($post_thumbnail) {
            }

            if ($post_content || $use_more) {
                $the_post_content = $the_content;
            } else if ($post_excerpt && !$use_more) {
                $the_post_content = $the_excerpt;
            } else if ($post_thumbnail_only) {
            }

            $c .= apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                'div',
                array(
                    'id' => $class . '_post_content_' . $post->ID,
                    'class' => array(
                        $class . '_post_content',
                        $id_base . '_post_content',
                        ((bool) $the_excerpt ? $id_base . '_post_excerpt' : ''),
                        $post_count_suffix . $post_count,
                        $id_base . '_' . $post_count_suffix . $post_count,
                        $class . '_' . $post_count_suffix . $post_count,
                    )
                ),
                $the_title
                    . $the_post_content
            )
                . $morelink
                . $article_end;
            $post_classes = array();
            $item_classes = array_merge(
                array(
                    $class,
                    $class . '_li',
                    $id_base . '_li',
                    $class . '_li_' . $post_count,
                    $id_base . '_li_' . $post_count,
                ),
                $post_classes
            );
            $formed_posts[$post->ID] = apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                $item_tag,
                array(
                    'id'   => $class . '_li_' . $post->ID,
                    'class' => $item_classes
                ),
                $c
            );
            $post_count++;
        } // END THE_LOOP

        $wp_query = $wp_query_orig;

        $ul_classes[] = 'post_count_' . ($post_count - 1);

        $content = apply_filters(
            WPCU_PREFIX . 'HTML_Element',
            $container_tag,
            array('id' => $container_id, 'class'    => $ul_classes),
            implode("\n", $formed_posts)
        );;
        $content = str_replace(']]>', ']]&gt;', $content);

        // ////// Restore defaults ////// //
        if ($current_blog_id != $blog_id) restore_current_blog();
        add_shortcode('posts', __FUNCTION__);
        if ($the_content_tn_priority) add_filter('the_content', 'add_post_thumbnail', $the_content_tn_priority);
        if ($the_excerpt_tn_priority) add_filter('the_excerpt', 'add_post_thumbnail', $the_excerpt_tn_priority);
        $post = $post_orig;
        $wp_custom_attributes = NULL;

        return "\n" . $content . "\n";
    }
}
