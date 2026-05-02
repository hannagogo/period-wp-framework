<?php

class WP_CustomUtility_Admin extends WP_CustomUtility_Class_Template
{
    // public function Change_menulabel() {
    //     global $menu;
    //     global $submenu;
    //     $name = 'お知らせ';
    //     $menu[5][0] = $name;
    //     $submenu['edit.php'][5][0] = $name.'一覧';
    //     $submenu['edit.php'][10][0] = '新規'.$name.'投稿';
    // }
    // function Change_objectlabel() {
    //     global $wp_post_types;
    //     $name = 'お知らせ';
    //     $labels = &$wp_post_types['post']->labels;
    //     $labels->name = $name;
    //     $labels->singular_name = $name;
    //     $labels->add_new = _x('追加', $name);
    //     $labels->add_new_item = $name.'の新規追加';
    //     $labels->edit_item = $name.'の編集';
    //     $labels->new_item = '新規'.$name;
    //     $labels->view_item = $name.'を表示';
    //     $labels->search_items = $name.'を検索';
    //     $labels->not_found = $name.'が見つかりませんでした';
    //     $labels->not_found_in_trash = 'ゴミ箱に'.$name.'は見つかりませんでした';
    // }
    // add_action( 'init', 'Change_objectlabel' );
    // add_action( 'admin_menu', 'Change_menulabel' );
    public function __construct()
    {
        add_action('init', array(&$this, 'setup_actions'));
    }
    function setup_actions()
    {
        foreach (
            array(
                'user_row_actions',
                'media_row_actions',
                'link_cat_row_actions',
                'post_row_actions',
                'page_row_actions',
                'cat_row_actions',
                'tag_row_actions',
                'comment_row_actions'
            ) as $filter
        ) {
            add_filter($filter, array(&$this, 'show_id'), '10', '2');
        }
    }
    public function show_id($actions, $object)
    {
        if (isset($actions['edit'])) {
            $id = '';
            if (property_exists($object, 'ID')) {
                $id =  intval($object->ID);
            } else if (property_exists($object, 'term_id')) {
                $id = intval($object->term_id);
            } else if (property_exists($object, 'comment_ID')) {
                $id = intval($object->comment_ID);
            }
            $actions['edit'] = '<span class="wp-ui-text-primary">' . "ID:" . $id . '</span>' . " | " . $actions['edit'];
        }
        return $actions;
    }
}

global $WP_CUSTOMUTILITY__ADMIN;
$WP_CUSTOMUTILITY__ADMIN = new WP_CustomUtility_Admin;
