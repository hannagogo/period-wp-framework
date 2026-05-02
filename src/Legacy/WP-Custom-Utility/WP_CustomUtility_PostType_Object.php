<?php
class WP_CustomUtility_PostType_Object
{
    var $atts = array();
    var $meta_boxes = array();
    var $taxonomies = array();
    var $rules = array();
    var $name = '';
    var $default_taxonomy_terms = array();
    public $meta_box;
    public $label;

    /* //
// Create PostType:
new WP_CustomUtility_PostType_Object(
    'name' => 'post_type_name',
    'label' => 'My New Post Type',
    'singular_name' => 'Post',
    'show_submenu_on_dashboard' => array(
    ),
    'taxonomies' => array(
        array('name'=>'some_tax', 'label'=>'Some Taxonomy', 'limit_admin_box_height'=>FALSE, 'hide_pop-tab'=> TRUE, 'manage_columns' => TRUE, ),
    ),
    'meta_boxes' => array(
        array(
            'name'=>'my_meta_box_name',
            'title'=>'My Meta Box',
            'fields' => array(
                    'some_text'=>array('type'=>'text', 'label'=>'Some Text'),
                    'some_other_text'=>array('type'=>'text', 'label'=>'Some Other Text', 'autocomplete_values'=>'_use_existing'),
                    'some_other_text'=>array(
                    'type'=>'text',
                    'label'=>'Some Other Text',
                    'autocomplete_values'=>array('Value 1','Value 2', 'Value 3')
                ),
                'some_selection'=>array(
                    'type'=>'radio',
                    'label'=>'Some Selection',
                    'values'=>array('Selection A','Selection B','Selection C'), 
                    'default'=>'Selection A'
                ),
                    'some_other_selection'=>array(
                    'type'=>'checkbox',
                    'label'=>'Some Other Selection',
                    'values'=>array('sel_1','sel_2','sel_3'),
                    'labels'=>array('Selection 1','Selection 2','Selection 3'),
                ),
                'script'=>'var func = function(){ return false }'
            ),
        ),
    ) 
);

// To Reference PostType: 
global $wpcf_post_type
;
$post_type = $WP_CUSTOM_UTILITY__POST_TYPES['custom_post_type_name'];
$meta_box = $post_type->get_meta_box('meta_box_name');
$meta_box_field_label = $meta_box->get_field_label('field_name');

// */

    function __construct($atts)
    {
        global $CUSTOM_UTILITY;
        global $WP_CUSTOM_UTILITY__POST_TYPES;

        $this->name = $this->atts['name'] = $atts['name'];

        if (isset($atts['custom_rules']) && $atts['custom_rules']) {
            $this->add_custom_rules($atts['custom_rules']);
        }

        if ($WP_CUSTOM_UTILITY__POST_TYPES) {
            foreach ($WP_CUSTOM_UTILITY__POST_TYPES as $k => $p) {
                if ($p->atts['name'] == $this->atts['name']) {
                    return;
                }
            }
        }
        /* ////// Meta Boxes ////// */
        $this->atts['meta_boxes'] = (array) (isset($atts['meta_boxes']) ? $atts['meta_boxes'] : array());

        if (isset($atts['meta_box'])) {
            $this->atts['meta_boxes'][] = $atts['meta_box'];
        }

        /* ////// Taxonomies ////// */
        /**
         *  Taxonomies are set AFTER the PostType was registered via "register_taxonomy_for_object_type".
         *  Name(s) or arguments for class.Taxonomy accepted.
         *   'taxonomy' 'taxonomies' excepts an (associative) array for class.Taxonomy *OR* list of names of existing taxonomy.
         * 
         */

        // Initialize
        $this->atts['taxonomies'] = $this->atts['taxonomy'] = array();
        !isset($atts['taxonomies']) && $atts['taxonomies'] = array();
        !isset($atts['taxonomy']) && $atts['taxonomy'] = array();

        // Regularize
        $this->atts['taxonomies'] = (array) $atts['taxonomies'];

        if (!empty($atts['taxonomy'])) {
            if (isset($atts['taxonomy'][0])) {
                $this->atts['taxonomies'] = array_merge($this->atts['taxonomies'], $atts['taxonomy']);
            } else {
                $this->atts['taxonomies'][] = (array) $atts['taxonomy'];
            }
        }
        // Manage Columns
        foreach ($this->atts['taxonomies'] as $i => $tax) {
            if (isset($tax['manage_columns']) && (bool) $tax['manage_columns']) {
                $this->atts['taxonomies'][$i]['manage_columns']
                    = $CUSTOM_UTILITY->parse_arguments(array($tax['name'] => $tax['label']), $tax['manage_columns']);
            } else {
                if (!isset($tax['manage_columns']) || NULL === $tax['manage_columns']) {
                    $this->atts['taxonomies'][$i]['manage_columns'] = array($tax['name'] => $tax['label']);
                }
            }
        }

        $this->atts['nonce'] = array(
            'action' => $this->atts['name'],
            'name' => $this->atts['name'] . '_nonce'
        );

        $this->atts['post_type_args'] = $this->_post_type_args($atts);

        $this->atts['post_type_archive_prefix'] =
            (isset($atts['post_type_archive_prefix']) && $atts['post_type_archive_prefix']) ? $atts['post_type_archive_prefix'] : 'archive';

        $this->atts['show_pages'] = isset($atts['show_pages']) ? $atts['show_pages'] : TRUE;

        $this->atts['show_submenu_on_dashboard'] = isset($atts['show_submenu_on_dashboard']) ? $atts['show_submenu_on_dashboard'] : array();

        $this->setup($this->atts['post_type_args']);

        add_filter('gutenberg_can_edit_post_type', array(&$this, 'enable_gutenberg'), 10, 2);

        if (is_admin() && FALSE !== $this->atts['show_submenu_on_dashboard']) {
            add_action('wp_dashboard_setup', array(&$this, 'dashboard_box'));
        }
        return $this;
    }


    function setup($args)
    {
        global $wp_rewrite;
        $this->atts['post_type_args'] = $this->_post_type_args($args);
        register_post_type($this->atts['name'], $this->atts['post_type_args']);
        $this->setup_meta_boxes();
        $this->setup_taxonomies();
        $this->setup_rewrite_rules();
        $this->atts['manage_columns'] = array_merge(
            array(
                'cb' => '<input type="checkbox" />',
                'post_thumb' => __('Featured Image')
            ),
            isset($this->atts['manage_columns']) ? (array) $this->atts['manage_columns'] : array()
        );

        // add_filter('generate_rewrite_rules', array(&$this, 'add_rewrite_rules'));
        // add_filter('wp_title', array($this, 'post_type_title'), 10, 3);
        add_filter('rewrite_rules_array', array(&$this, 'add_rewrite_rules'));
        add_action('pre_get_posts', array(&$this, 'modify_query'));

        if (get_option("permalink_structure") != "") add_filter('post_type_link', array(&$this, 'set_permalink'), 10, 3);

        foreach (array('name', 'meta_box', 'taxonomies') as $v) $this->{$v} = &$this->atts[$v];
        foreach (array('label') as $v) $this->{$v} = &$this->atts['post_type_args'][$v];
        return $this;
    }

    function setup_taxonomies($array = NULL)
    {
        global $CUSTOM_UTILITY;

        if ($array === NULL) $array = $this->atts['taxonomies'];

        $taxes = array();
        if (!empty($array)) {
            foreach ($array as $i => $a) {
                $tax_name = '';
                if (is_array($a)) { // Suppose $a is arguments for class.Taxonomy
                    $tax_name = $CUSTOM_UTILITY->array_value($a, 'name');
                    $a['post_type'] = $this->name;
                    $this->taxonomies[$tax_name] = ($t = get_taxonomy($tax_name)) ? $t : new WP_CustomUtility_Taxonomy($a);
                    $taxes[] = $tax_name;
                } else if (is_string($a) && $t = get_taxonomy($a)) {
                    $tax_name = $a;
                    $this->taxonomies[$tax_name] = $t;
                    $taxes[] = $tax_name;
                }
                register_taxonomy_for_object_type($tax_name, $this->name);
            }
            $this->atts['taxonomies'] = array_splice($taxes, 0, count($taxes));
        }
        return $this;
    }

    function setup_meta_boxes($post = null, $boxes = null)
    {
        global $CUSTOM_UTILITY;
        if (!is_array($boxes)) {
            $boxes = $this->atts['meta_boxes'];
        }

        // $CUSTOM_UTILITY->var_dump_box($boxes);
        if (count($boxes)) {
            foreach ($boxes as $i => $b) {
                if (empty($b)) continue;
                $this->meta_boxes[$b['name']] = new WP_CustomUtility_MetaBox(
                    array_merge(
                        $b,
                        array('post_type' => $this->name())
                    )
                );
            }
        }
        return $this;
    }

    function &get_meta_box($name)
    {
        $r = isset($this->meta_boxes[$name]) ? $this->meta_boxes[$name] : NULL;
        return $r;
    }

    function &get_meta_boxes($name = NULL)
    {
        if ($name === NULL) return $this->meta_boxes;
        return $this->get_meta_box($name);
    }

    function _post_type_args($args = NULL)
    {
        global $CUSTOM_UTILITY;
        global $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;
        $domain = $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;
        if ($args === null) $args = $this->atts['post_type_args'];
        $type = (isset($args['hierarchical']) && $args['hierarchical']) ? 'page' : 'post';

        $labels_defaults = $CUSTOM_UTILITY->parse_arguments(array(
            'singular_name'     => (isset($args['label']) && !empty($args['label']) ? $args['label'] : $args['name']),
            // name for one object of this post type. Defaults to value of name
        ), $args);

        $args_defaults = $CUSTOM_UTILITY->parse_arguments(array(
            'label' => (isset($this->atts['label']) && !empty($this->atts['label']) ? $this->atts['label'] : $this->atts['name']),
            // A plural descriptive name for the post type marked for translation.
            'public' => true, // Meta argument used to define default values for
            'hierarchical' => false, // Whether the post type is hierarchical. Allows Parent to be specified.
            'capability_type' => $type, // The string to use to build the read, edit, and delete capabilities.
            // May be passed as an array to allow for alternative plurals when using this argument as a base to
            // construct the capabilities, e.g. array('story', 'stories'). By default the capability_type is used
            // as a base to construct capabilities.
            'gutenberg' => true,
        ), $args);

        $rewrite_defaults = $CUSTOM_UTILITY->parse_arguments(
            array(
                'slug' => $this->atts['name'],
                // prepend posts with this slug - defaults to $post_type - use array( 'slug' => $slug ) to customize permastruct
                'with_front' => true, // allowing permalinks to be prepended with front base (example: if your permalink structure 
                // is /blog/, then your links will be: false->/news/, true->/blog/news/) - defaults to true
                'feeds' => true,
                'pages' => true
            ),
            (isset($args['rewrite']) ? (array) $args['rewrite'] : array())
        );
        $label = $args_defaults['label'];
        $singular_name =  $labels_defaults['singular_name'];
        $labels = $CUSTOM_UTILITY->parse_arguments(array(
            // An array of labels for this post type. By default post labels are used for non-hierarchical types
            // and page labels for hierarchical ones.
            // Default: if empty, name is set to label value, and singular_name is set to name value
            //'name'				 => null, // general name for the post type, usually plural.
            // The same as, and overridden by $post_type_object->label
            'singular_name'     => $singular_name, // name for one object of this post type. Defaults to value of name
            'add_new'             => sprintf(__('Add New %s', $domain), $singular_name),
            // the add new text. The default is Add New for both hierarchical and
            // non-hierarchical types. When internationalizing this string, please use a gettext context
            // matching your post type. Example: _x('Add New', 'product');
            'all_items'         => $label, // the all items text used in the menu. Default is the Name label
            'add_new_item'         => sprintf(__('Add New %s', $domain), $singular_name), // the add new item text. Default is Add New Post/Add New Page
            'edit_item'         => sprintf(__('Edit %s', $domain), $singular_name), // the edit item text. Default is Edit Post/Edit Page
            'new_item'             => sprintf(__('New %s', $domain), $singular_name), // the new item text. Default is New Post/New Page
            'view_item'         => sprintf(__('View %s', $domain), $singular_name), // the view item text. Default is View Post/View Page
            'search_items'         => sprintf(__('Search %s', $domain), $label), // the search items text. Default is Search Posts/Search Pages
            'not_found'         => sprintf(__('No %ss found.', $domain), strtolower($singular_name)),
            // the not found text. Default is No posts found/No pages found
            'not_found_in_trash' => sprintf(__('No %ss found in trash.', $domain), strtolower($singular_name)),
            // the not found in trash text. Default is No posts found in
            // Trash/No pages found in Trash
            'parent_item_colon' => __('Parent Page', $domain), // the parent text. This string isn't used on non-hierarchical types.
            // In hierarchical ones the default is Parent Page
            'menu_name' => $args_defaults['label'], // the menu name text. This string is the name to give menu items.
            // Defaults to value of name
        ), $args);

        $args = $CUSTOM_UTILITY->parse_arguments(
            array( // default values of register_post_type().
                'label' => $this->atts['name'], // A plural descriptive name for the post type marked for translation.
                'labels' => $labels,
                'description' => '', // A short descriptive summary of what the post type is.
                'public' => $args_defaults['public'], // Meta argument used to define default values for
                // publicly_queriable, show_ui, show_in_nav_menus and exclude_from_search. Default: false
                'publicly_queryable' => $args_defaults['public'], // Whether post_type queries can be performed from the front end.
                'exclude_from_search' => !$args_defaults['public'], // Whether to exclude posts with this post type from search results.
                'show_ui' => $args_defaults['public'], // Whether to generate a default UI for managing this post type.
                // Note that _built-in post types, such as post and page, are intentionally set to false.
                'show_in_menu' => $args_defaults['public'],
                // Whether to show the post type in the admin menu and where to show that menu.
                // Note that show_ui must be true. Default: null
                'menu_position' => 5, // The position in the menu order the post type should appear.
                /* 5 - below Posts;		 10 - below Media;			 15 - below Links;		 20 - below Pages
     25 - below comments;	 60 - below first separator	 65 - below Plugins;	 70 - below Users
     75 - below Tools;		 80 - below Settings;		100 - below second separator */
                'taxonomy' => null,
                'menu_icon' => null, // The url to the icon to be used for this menu.
                'capability_type' => $args_defaults['capability_type'],
                /* 'capabilities', 'map_meta_cap' : THESE FEATURES ARE PENDING AND EXPECTING FOR FUTURE USE.
  'capabilities' => array('edit_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'read_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts'), // An array of the capabilities for this post type.
  'map_meta_cap' => false, // Whether to use the internal default meta capability handling. */
                'hierarchical' => false, // Whether the post type is hierarchical. Allows Parent to be specified.
                'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-fields', 'revisions', 'post-formats'),
                // An alias for calling add_post_type_support() directly.
                'permalink_epmask' => EP_PERMALINK, // The default rewrite endpoint bitmasks. For more info see Trac Ticket 12605.
                'has_archive' => true, // Enables post type archives. Will use string as archive slug.
                // Will generate the proper rewrite rules if rewrite is enabled.
                'rewrite' => $rewrite_defaults,
                'query_var' => false, // False to prevent queries, or string value of the query var to use for this post type.
                'can_export' => true, // Can this post_type be exported.
                'show_in_nav_menus' => $args_defaults['public'], // Whether post_type is available for selection in navigation menus.
                'show_in_rest' => FALSE,
            ),
            $args
        );
        return $args;
    }


    function add_rewrite_rules($rules)
    {
        return $rules = $this->setup_rewrite_rules() + $rules;
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }


    function setup_rewrite_rules()
    {
        global $wp_rewrite;
        $post_type = $this->atts['name'];
        $archive = $this->atts['post_type_archive_prefix'];
        $feed = '(feed|rdf|rss|rss2|atom)';
        $rule_templates = array(
            '/' => '&paged=1',
            '/([0-9]{1,})/'
            => '&p=$matches[1]',
            '/([0-9]{1,})/(?:[^/]+)/'
            => '&p=$matches[1]',
            '/page/([0-9]{1,})/'
            => '&paged=$matches[1]',
            '/' . $archive . '/([0-9]{4})/'
            => '&paged=1&year=$matches[1]',
            '/' . $archive . '/([0-9]{4})/page/([0-9]{1,})/'
            => '&year=$matches[1]&paged=$matches[2]',
            '/' . $archive . '/([0-9]{4})/([0-9]{2})/'
            => '&year=$matches[1]&monthnum=$matches[2]',
            '/' . $archive . '/([0-9]{4})/([0-9]{2})/page/([0-9]{1,})/'
            => '&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]',
            '/' . $archive . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/'
            => '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]',
            '/' . $archive . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/page/([0-9]{1,})/'
            => '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]',
            '/(?:feed/)?' . $feed . '/'
            => '&feed=$matches[1]',
        );

        foreach ($rule_templates as $re => $rule) {
            $this->rules[$this->name() . $re . '?$'] = 'index.php' . '?post_type=' . $this->name() . $rule;
        }
        return $this->rules;
    }

    function add_custom_rules($rules)
    {
        $this->rules = $this->rules + (array) $rules;
        return $this;
    }

    function set_rewrite_tags()
    {
        global $wp_rewrite;
        $wp_rewrite->add_rewrite_tag('%post_type%', '([^/]+)', 'post_type=');
    }

    function set_permalink($post_link, $post, $leavename)
    {
        global $wp_rewrite;
        if ($post->post_type != $this->name()) return $post_link;
        $draft_or_pending = isset($post->post_status) && in_array($post->post_status, array('draft', 'pending', 'auto-draft'));
        if ($draft_or_pending and !$leavename) return $post_link;

        $permalink = $wp_rewrite->get_extra_permastruct($this->name());
        $permalink = str_replace('%' . $this->name() . '%', $post->ID . '/%' . $this->name() . '%/', $permalink);
        if (!$leavename) $permalink = str_replace('%' . $this->name() . '%', $post->post_name, $permalink);

        $permalink = home_url(user_trailingslashit($permalink));
        $permalink = str_replace(rtrim(preg_replace("/%[a-z,_]*%/", "", get_option("permalink_structure")), '/'), "", $permalink);
        return $permalink;
    }



    function define_default_term($term, $taxonomy, $field = 'slug')
    {
        $t = get_term_by($field, $term, $taxonomy);
        if ($t) {
            $this->default_taxonomy_terms[$taxonomy] = array();
            $this->default_taxonomy_terms[$taxonomy][$t->slug] = $t;
        }
        return $this;
    }


    function set_default_term($post_id, $post)
    {
        if ('publish' === $post->post_status && $post->post_type === $this->name) {
            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ((array) $taxonomies as $tax) {
                $terms = wp_get_object_terms($post_id, $tax);
                if (empty($terms) && array_key_exists($tax, $this->default_taxonomy_terms)) {
                    wp_set_object_terms($post_id, array_keys($this->default_taxonomy_terms[$tax]), $tax);
                }
            }
        }
    }



    /* Ruler functions (not in use)
function add_rules($rules) { return array_merge($this->rules, $rules); }

function add_rewrite_rules_to_rewrite_object($wp_rewrite) {
 $wp_rewrite->rules = $wp_rewrite->rules + $this->setup_rewrite_rules();
// global $wp_rewrite; $wp_rewrite->flush_rules();
 return $wp_rewrite->rules;
}
*/

    function modify_query()
    {
        global $wp_query;
        if (!$this->atts['show_pages']) {
            $wp_query->set('posts_per_page', -1);
        }
    }

    function dashboard_box()
    {
        wp_add_dashboard_widget('dashboard_submenu_' . $this->name() . '_db', sprintf(__('Edit %s'), $this->label()), array(&$this, 'dashboard_box_html'));
    }

    function dashboard_box_html($args = NULL)
    {
        global $submenu, $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN, $CUSTOM_UTILITY;
        $edit_url = 'edit.php?post_type=' . $this->name();
        $sub = isset($submenu[$edit_url]) ? $submenu[$edit_url] : array();
        /* //
// $sub WILL BE LIKE:
Array
(
    [5] => Array
        (
            [0] => サイトデータ
            [1] => edit_posts
            [2] => edit.php?post_type=wpcf_site_options
        )
    [10] => Array
        (
            [0] => サイトデータ項目を追加
            [1] => edit_posts
            [2] => post-new.php?post_type=wpcf_site_options
        )
    [11] => Array
        (
            [0] => ページヘッダ
            [1] => install_plugins
            [2] => edit_page_header_wpcf_site_options
            [3] => ページヘッダを編集
        )
    [12] => Array
        (
            [0] => 並び替え
            [1] => publish_pages
            [2] => order-post-types-wpcf_site_options
            [3] => 並び替え
        )
)
 // */

        $args = $CUSTOM_UTILITY->parse_arguments(array(
            'omit_submenu_on_dashboard' => array('edit_page_header', 'order-post-types'),
            'posts_per_page' => get_option('posts_per_page'),
            'recent_posts_title' => __('Recent %s', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
        ), $this->atts['show_submenu_on_dashboard']);
        $args['omit_submenu_on_dashboard'] = (array) $args['omit_submenu_on_dashboard'];
        $submenu_col = array();
        if (!empty($sub)) {
            foreach ($sub as $m) {
                if (
                    !in_array($m[2], $args['omit_submenu_on_dashboard'])
                    &&
                    !preg_match('/^(' . implode('|', $args['omit_submenu_on_dashboard']) . ')(?:[-_]' . $this->name() . ')?$/', $m[2])
                ) {
                    $submenu_col[] = $m;
                }
            }
        }
        if (empty($submenu_col)) {
            return;
        }
        $html = apply_filters(WPCU_PREFIX . 'HTML_Element', 'ul', 'start', array('class' => 'dashboard_submenu', 'id' => 'dashboard_submenu_' . $this->name()));
        foreach ($submenu_col as $s) {
            if (preg_match('/^edit.php\x3f/', $s[2])) {
                $s[0] = sprintf(__('List %s', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN), $this->label());
            }
            $html .= apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                'li',
                array('class' => 'dashboard_submenu_li'),
                apply_filters(WPCU_PREFIX . 'HTML_Element', 'a', array('href' => $s[2]), $s[0])
            );
        }
        $html .= apply_filters(WPCU_PREFIX . 'HTML_Element', 'ul', 'end');
        $posts = get_posts(array('post_type' => $this->name, 'posts_per_page' => $args['posts_per_page']));
        if (!empty($posts)) {
            $html .= apply_filters(WPCU_PREFIX . 'HTML_Element', 'ul', 'start', array('class' => 'dashboard_submenu_posts', 'id' => 'dashboard_submenu_posts_' . $this->name()));
            $html .= apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                'li',
                array('class' => 'dashboard_submenu_posts_li dashboard_submenu_posts_title'),
                sprintf($args['recent_posts_title'], $this->label())
            );
            foreach ($posts as $p) {
                $html .= apply_filters(
                    WPCU_PREFIX . 'HTML_Element',
                    'li',
                    array('class' => 'dashboard_submenu_posts_li'),
                    apply_filters(WPCU_PREFIX . 'HTML_Element', 'a', array('href' => get_edit_post_link($p->ID)), $p->post_title)
                );
            }
            $html .= apply_filters(WPCU_PREFIX . 'HTML_Element', 'ul', 'end');
        }
        echo apply_filters(
            WPCU_PREFIX . 'HTML_Element',
            'div',
            array('id' => 'dashboard_submenu_box_' . $this->name() . '_content', 'class' => 'dashboard_submenu_box_content'),
            $html
                . apply_filters(
                    'CF_Wrap_JavaScript',
                    '
$("#dashboard_submenu_' . $this->name() . ' a").button()
',
                    array('jquery' => TRUE)
                )
        );
    }

    function name()
    {
        return $this->atts['name'];
    }
    function label()
    {
        return $this->atts['post_type_args']['label'];
    }

    function __call($method, $args)
    {
        if (isset($this->atts[$method])) return $this->atts[$method];
        if (isset($this->{$method})) return $this->{$method};
        if (isset($this->atts['post_type_args'][$method])) return $this->atts['post_type_args'][$method];
        return null;
    }

    function enable_gutenberg()
    {
        return $this->atts['gutenberg'];
    }

    function edit_post_type_widget()
    {
        $args_orig = func_get_args();
        isset($args_orig[1]) && isset($args_orig[1]['args']) && $args = $args_orig[1]['args'];
        isset($args_orig[1]) && isset($args_orig[1]['id']) && $id = $args_orig[1]['id'];
        $args = apply_filters(WPCU_PREFIX . 'Arguments', array('post', get_option('posts_per_page')), $args);
        $posts = get_posts(array('post_type' => $args[0], 'posts_per_page' => $args[1]));

        if (!empty($posts)) {
            echo apply_filters(
                WPCU_PREFIX . 'HTML_Element',
                'ul',
                array('id' => $id),
                apply_filters(WPCU_PREFIX . 'HTML_Element', 'li', NULL, array_map(
                    function ($p) {
                        return apply_filters(
                            WPCU_PREFIX . 'HTML_Element',
                            "a",
                            array("href" => admin_url("/post.php?post=" . $p->ID . "&action=edit")),
                            strip_tags(apply_filters("the_title", $p->post_title))
                        );
                    },
                    $posts
                ))
            );
        }
    }

    // 未整理

    function store_PostType(&$post_type) // ex wpcf_add_post_type
    {
        global $WP_CUSTOM_UTILITY;
        if (!is_object($post_type) || (get_class($post_type) != 'PostType')) return;
        return $wpcf_post_types[$post_type->name()] = $post_type;
    }

    function &wpcf_get_post_type($post_type)
    {
        global $wpcf_post_types;
        return $wpcf_post_types[$post_type];
    }

    function &wpcf_post_types()
    {
        global $wpcf_post_types;
        return $wpcf_post_types;
    }


    function is_specific_custom_post_type($type = '', $p = NULL, $post_types_args = array())
    {
        global $CUSTOM_UTILITY, $WP_CUSTOM_UTILITY;
        $is_specific_post_type = NULL;
        $post_types_args = $CUSTOM_UTILITY->parse_arguments(
            array('_builtin' => false),
            $post_types_args
        );
        $p = get_post($p);
        if ($type) {
            if ($WP_CUSTOM_UTILITY->is_specific_post_type($type, $p)) return $type;
        }
        $post_types = get_post_types($post_types_args);
        foreach ($post_types as $pt) {
            if ($WP_CUSTOM_UTILITY->is_specific_post_type($pt, $p)) {
                $is_specific_post_type = $pt;
                break;
            }
        }
        return $is_specific_post_type;
    }



    function get_post_id_from_db_value($guid, $column = 'guid')
    {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT ID FROM `wp_posts` WHERE " . $column . " LIKE '%s'",
            array($guid)
        );
        $result = $wpdb->get_results($query);
        if ((bool) $result && is_array($result) && get_class($result[0])) {
            return $result[0]->ID;
        }
        return NULL;
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


    function wpcf_get_featured_posts($args = array())
    {
        global $wpdb;
        global $CUSTOM_UTILITY;
        $a = $CUSTOM_UTILITY->parse_arguments(
            array(
                'post_type' => 'post',
                'posts_per_page' => get_option('posts_per_page'),
                'post_status' => 'publish,future',
                'field' => 'post_id', // COULD BE "*"
                'search_value' => '1',
                'meta_key' => 'featured_post'
            ),
            $args
        );
        $field = preg_replace('/[^a-z,\x2e\x2d\x5f\s]/', '', $a['field']);
        $query = "SELECT $field FROM $wpdb->postmeta WHERE meta_key='{$a['meta_key']}' AND meta_value='a:1:{i:0;a:1:{i:0;s:1:\"1\";}}' "
            . ($a['posts_per_page'] < 0 ? '' : "LIMIT " . $a['posts_per_page']);
        $result = $wpdb->get_results($query);
        $r = NULL;
        if ((bool) $result && is_array($result) && get_class($result[0])) {
            if ($a['field'] == '*') {
                return $result;
            }
            foreach ($result as $obj) {
                $r[] = $obj->post_id;
            }
            return $r;
        }
        return NULL;
    }


    function wpcf_get_post_id_in_admin_page()
    {
        $post_id = isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : NULL);
        return $post_id;
    }
} // end of CLASS PostType_Object
