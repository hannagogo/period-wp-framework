<?php
/* 
new WP_CustomUtility_MetaBox(array(
    'name' => 'post_settings',
    'title'=>__('Post Settings, Scripts and Styles', $custom_language_domain),
    'context' => 'side',
    'post_type' => 'any', // or any other PostType names. (give an array if plural)
    'create_box' => FALSE, // FALSE to not to display a box for the meta box in admin page
    'post_id' => '*',
    'fields' => array(
        'field_name' => array(
            'label' =>  'Test Input Field',
            'type'  =>  'text',
            'picker'=>  array(
                'picker' => 'datepicker',
                'options' => array(
                    'ampm'              => false,
                    'addSliderAccess'   => true,
                    'sliderAccessArgs'  => array('touchonly'=>false),
                    'stepHour'          => 1,
                    'stepMinute'        => 1,
                    'hourGrid'          => 3,
                    'minuteGrid'        => 10,
                    'dateFormat'        => "yy/mm/dd",
                    'yearRange'         => "-100:+100",
                    'changeMonth'       => true,
                    'changeYear'        => true
                ),
            )
        ),
        '__regular_form' => array(
            'label'=>__('CSS Files', $custom_language_domain),
            'type'=>'textarea',
            'rows'=>$rows,
            'cols'=>$cols,
            'default_title_value'=>array('truncate'=>32, 'overwrite'=>true) // use the value as #title value. set true overwrite
        ),
        '__using_post_type' => array(
            'type' => 'posts',
            'form_type' => 'checkbox', // or 'radio' for exclusive selection
            'label' => 'Using Post Type',
            'query' => array('post_type'=>'post', 'posts_per_page'=>get_option('posts_per_page'),
        ),
    )
) );

//*/
class WP_CustomUtility_MetaBox extends WP_CustomUtility_Class_Template
{

    public $image_form_params;

    public function __construct($atts = null)
    {
        global $CUSTOM_UTILITY;
        $this->param(
            array_merge(
                array(
                    'init_hook' => 'admin_xml_ns',
                    'create_box' => TRUE,
                    'post_id' => '*',
                ),
                (array) $atts
            )
        );

        $_post_types = $this->param('post_type');

        if (
            (is_string($_post_types) && $_post_types == 'any')
            ||
            (is_array($_post_types) && in_array('any', $_post_types))
        ) {
            $this->param(
                'post_type',
                array_merge(
                    get_post_types(array('public' => TRUE, '_builtin' => FALSE)),
                    get_post_types(array('_builtin' => TRUE, 'public' => TRUE))
                )
            );
        }
        $this->param('post_type', (array) $this->param('post_type'));

        $this->image_form_params = array(
            'image_size_suffix'     => '_size',
            'image_size_id_suffix' => '_image_size',
            'button_suffix'         => '_button',
            'button_delete_suffix' => '_delete',
            'image_view_box_suffix' => '_image_view',
        );
        $this->param('nonce', array(
            'action' => $this->param('name'),
            'name' => $this->param('name') . '_nonce'
        ));
        $this->setup();
    }

    function arguments()
    {
        $a = array();
        foreach ($this->accepted_param_keys as $k) $a[$k] = is_null($this->param($k)) ? null : $this->param($k);
        if (is_null($a['title']) && !is_null($a['name'])) {
            $a['title'] = sprintf('Title: %s', $a['name']);
        }
        return $a;
    }


    function _register_update_action($action = 'save_post')
    {
        add_action($action, array(&$this, 'update'));
    }


    function setup($meta_box_args = null)
    {
        if (!$meta_box_args) $meta_box_args = $this->arguments();
        if (is_array($meta_box_args) && isset($meta_box_args['name'])) {
            add_action($meta_box_args['init_hook'], array(&$this, 'setup_meta_box'));
        }
        add_action('save_post', array($this, 'update'), 100);
        add_action('wp_ajax_get_thumbnail', array(&$this, 'ajax_get_thumbnail'));
        add_action('wp_ajax_get_image_size', array(&$this, 'ajax_get_image_size'));

        return $this;
    }

    function setup_meta_box($meta_box_args = null)
    {
        if (!$meta_box_args) $meta_box_args = $this->arguments();
        if ($id = $this->param('post_id')) {
            if ($id != '*') {
                $post = get_post();
                if ($post && $post->ID != $id) {
                    $this->param('create_box', false);
                }
            }
        }
        if ($this->param('create_box')) {
            foreach ($this->param('post_type') as $pt) {
                add_meta_box(
                    get_class($this) . '_' . $meta_box_args['name'], // Box ID
                    $meta_box_args['title'], // title
                    array(&$this, 'meta_box_html'),
                    // Function that prints out the HTML for the edit screen section.
                    $pt,
                    // [ post | page | link | {custom_post_type} ] 
                    // The type of Write screen on which to show the edit screen section 
                    ($meta_box_args['context'] ? $meta_box_args['context'] : 'normal'),
                    // [ normal | advanced | side ]
                    // The part of the page where the edit screen section should be shown
                    ($meta_box_args['priority'] ? $meta_box_args['priority'] : 'high'),
                    // [ high | core | default | low ] 
                    // The priority within the context where the boxes should show 
                    $meta_box_args
                    // Arguments to pass into your callback function.
                    // The callback will receive the $post object and whatever parameters are passed through this variable.
                );
            }
        }
    }

    private function _metabox_html_build_multiplier_attrs($id, $plus_or_minus)
    {
        $plus_or_minus = ($plus_or_minus == '+') ? 'plus' : 'minus';
        $class_base = 'form_field_multiply';
        $a = array(
            'class' => array(
                __CLASS__ . '_' . $class_base,
                __CLASS__ . '_' . $class_base . '_' . $plus_or_minus,
                $this->param('name') . '_' . $class_base,
                $this->param('name') . '_' . $class_base . '_' . $plus_or_minus,
                $id . '_' . $class_base,
                $id . '_' . $class_base . '_' . $plus_or_minus
            ),
            'id' => $id . '_' . $plus_or_minus,
        );
        if ($plus_or_minus != '+')  $a['data-multiply_target'] = $id;
        return $a;
    }

    public function meta_box_html($post, $meta_box)
    {
        global
            $CUSTOM_UTILITY,
            $WP_CUSTOM_UTILITY,
            $WP_CUSTOM_UTILITY__TEMPLATE,
            $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN;

        $LF = $CUSTOM_UTILITY->Presets->LF;
        $post = get_post($post);
        $meta_args = isset($meta_box['args']) ? $meta_box['args'] : $meta_box;
        $field_id = preg_replace('/\s/', '', $meta_args['name']);
        $field_count = 0;
        $visible_field_count = 0;
        $box_script = '';
        $n = $this->param('nonce');
        if (isset($meta_args['row_group'])) {
            $meta_args['row_group'] = intval($meta_args['row_group']);
        } else {
            $meta_args['row_group'] = 0;
        }
        $h = wp_nonce_field($n['action'], $n['name'], true, false);

        $self_name = $this->param('name');
        $class_name = get_class($this);
        $form_types = array(
            'textfield' => 'input',
            'text'      => 'input',
            'checkbox'  => 'input',
            'hidden'    => 'input',
            'radio'     => 'input',
            'select'    => 'select',
            'textarea'  => 'textarea',
            'image'     => 'image'
        );
        $default_form_type = $CUSTOM_UTILITY->array_value($meta_args, 'type');
        if (!$default_form_type) {
            $default_form_type = $CUSTOM_UTILITY->array_value(array_keys($form_types), 0);
        }
        $box_script .= $meta_args['script']  . $LF;

        $group = is_array($meta_args['multiply_group']) ? $meta_args['multiply_group'] : explode(',', $meta_args['multiply_group']);
        if (isset($group[0]) && is_string($group[0])) {
            $group = array($group);
        }
        foreach ($group as $i => $n) {
            if (empty($n)) unset($group[$i]);
        }
        $group = array_merge(array(), $group);

        if (isset($meta_args['fields']) && $meta_args['fields']) {
            foreach ($meta_args['fields'] as $k => $v) {
                $values = $WP_CUSTOM_UTILITY->get_multi_post_meta($post->ID, $k, FALSE);
                $_obsolete = isset($v['obsolete']) && $v['obsolete'];
                $_hide_if_empty = !isset($v['hide_if_empty']) || (TRUE == (bool) $v['hide_if_empty']);
                $_hide_box = $_values_empty = $_empty = FALSE;

                if ($_obsolete) {
                    $_hide_box_filter = (bool) apply_filters('WPCF_MetaBox_Display_Obsolete_Field', array('meta_key' => $k, 'meta_value' => $v));
                    if (empty($values)) {
                        $_empty = TRUE;
                    } else {
                        $_has_value = FALSE;
                        foreach ($values as $_value) {
                            $_has_value = !empty($_value) || $_has_value;
                        }
                        $_empty = !$_has_value;
                    }
                    $_values_empty = $_empty;
                    $_hide_box = !$_hide_box_filter || ($_values_empty && $_hide_if_empty);
                    if ($_hide_box) continue; ////// CONTINUE'S HERE
                }

                $field_count++;
                if (isset($v['type']) && $v['type'] != 'hidden') $visible_field_count++;
                if (!isset($v['type'])) $v['type'] = $default_form_type;
                if ($v['type'] == 'textfield') $v['type'] = 'text';
                if (!isset($v['script'])) $v['script'] = '';
                if (!isset($v['values'])) $v['values'] = '';
                if (!isset($v['label'])) $v['label'] = '';
                if (!isset($v['default'])) $v['default'] = '';

                $v['values'] = (array) $v['values'];

                $v['picker'] = array_merge(
                    array(
                        'picker'    => null,
                        'options'   => null
                    ),
                    isset($v['picker']) ? $v['picker'] : array()
                );
                if ($v['picker']['picker']) {
                    $picker_default_args = array(
                        "ampm" => false,
                        // "addSliderAccess" => true, "sliderAccessArgs" => array("touchonly" => false),
                        "stepHour" => 1,
                        "stepMinute" => 1,
                        "hourGrid" => 3,
                        "minuteGrid" => 10,
                        "dateFormat" => "yy/mm/dd",
                        "yearRange" => "-100:+100",
                        "changeMonth" => true,
                        "changeYear" => true
                    );
                    $v['picker']['options'] = array_merge($picker_default_args, (array) $v['picker']['options']);
                }

                if (isset($v['_omit_field']) && !empty($v['_omit_field']) && in_array($k, (array) $v['_omit_field'])) {
                    continue; // EXITTING!
                }

                if ($v['type'] == 'select') {
                    $multi = false;
                    foreach ($values as $n => $m) {
                        if (is_array($m)) {
                            $multi = true;
                            break;
                        }
                    }
                    if (!$multi) {
                        $values = array($values);
                    }
                }
                $_is_multiple_form_element_type = isset($v['type']) && in_array($v['type'], array('radio', 'checkbox', 'select'));

                if (!isset($v['value_label'])) {
                    $v['value_label'] = null;
                } else {
                    $v['value_label'] = (array) $v['value_label'];
                }

                ($count = count($values)) == 0 && $count = 1;

                if (isset($v['increase_field']) && (bool) $v['increase_field']) {
                    $count++;
                }

                if (isset($v['multiply']) && $v['multiply'] > $count) $count = $v['multiply'];
                $field_name = $k . '[]';
                $label_for = $field_id . '_' . $k;
                $multipliable = (isset($v['multipliable']) && (bool) $v['multipliable']) ?
                    $CUSTOM_UTILITY->HTML->create_element('span', $this->_metabox_html_build_multiplier_attrs($label_for, '+'), '+')
                    :
                    '';
                $multipliable_wrap_start = $CUSTOM_UTILITY->HTML->create_element('div', 'start', array('class' => array($self_name . '_form_field_wrap', $label_for . '_form_field_wrap', $class_name . '_form_field_wrap'), 'id' => '%s_form_field_wrap'));
                $multipliable_wrap_end = $CUSTOM_UTILITY->HTML->create_element('div', 'end');

                $delete_field_dialog = $CUSTOM_UTILITY->parse_arguments(array(
                    'message'         => __('Are you sure you want to delete the field?', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'title'           => __('Delete Field Confirmation', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'message_media'   => __('Are you sure you want to delete the media field?', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'title_media'     => __('Delete Media Field Confirmation', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'confirm_button'  => __('Delete', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                    'cancel_button'   => __('Cancel', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                ), (isset($v['confirm_delete_dialog']) ? (array) $v['confirm_delete_dialog'] : array()));
                if ($v['type'] != 'hidden') {
                    $box_class = $label_class = array();
                    if ($_obsolete) {
                        $box_class[] = 'obsolete';
                    }
                    if ($_values_empty) {
                        $box_class[] = 'empty';
                    }
                    if ($_hide_if_empty && $_values_empty) {
                        $box_class[] = 'empty_hide';
                        $box_class[] = 'hide_box';
                    }
                    if (isset($v['class']) && !empty($v['class'])) {
                        if (!is_array($v['class'])) {
                            $bc = preg_split('/[,\x20]/', $v['class']);
                            foreach ($bc as $c) {
                                $box_class[] = $c . '_box';
                                $label_class[] = $c . '_title';
                            }
                        }
                    }
                    $box_class = array_merge($box_class, array(
                        $self_name,
                        $class_name,
                        $self_name . '_box',
                        $class_name . '_box',
                        $class_name . '_field_count_' . ($field_count % 2 ? 'odd' : 'even'),
                        $class_name . '_field_count_' . $field_count,
                        $class_name . '_visible_field_count_' . ($visible_field_count % 2 ? 'odd' : 'even'),
                        $class_name . '_visible_field_count_' . $visible_field_count,
                        ($meta_args['row_group'] ? $class_name . '_row_' . (intval(($field_count - 1) / $meta_args['row_group']) % 2  ? 'odd' : 'even') : '')
                    ));
                    if (isset($form_types[$v['type']])) {
                        $box_class[] = $self_name . '_' . ($form_types[$v['type']] == $v['type'] ? $form_types[$v['type']] : $form_types[$v['type']] . '_' . $v['type']) . '_box';
                        $box_class[] = $class_name . '_' . ($form_types[$v['type']] == $v['type'] ? $form_types[$v['type']] : $form_types[$v['type']] . '_' . $v['type']) . '_box';
                    };
                    $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'start', array('class' => $box_class, 'id' => $label_for . '_box'))
                        . $CUSTOM_UTILITY->HTML->create_element(
                            ($_is_multiple_form_element_type ? 'div' : 'label'),
                            array('for' => $label_for . '_' . ($count - 1), 'class' => array_merge(array($class_name . '_title', $self_name . '_title'), $label_class)),
                            $v['label']
                        )
                        . (
                            $multipliable ?
                            $CUSTOM_UTILITY->HTML->create_element(
                                'p',
                                array('id' => $label_for . '_delete_field_dialog', 'class' => array($label_for . '_delete_field_dialog_message', 'delete_field_dialog_message', 'delete_field_dialog')),
                                $delete_field_dialog['message']
                            )
                            :
                            ''
                        );
                }
                if ($multipliable) {
                    $v['script'] .= '
$.set_MetaBox_multiplier({
    className:  "' . __CLASS__ . '",
    target_id:  "' . $label_for . '",
    field_id:   "' . $field_id . '",
    minus_attr:     "' . $CUSTOM_UTILITY->HTML->make_attributes($this->_metabox_html_build_multiplier_attrs($label_for . '_1', '-'), "'") . '",
    dialog_args:    ' . json_encode($delete_field_dialog) . ',
    field_type:     "' . $v["type"] . '",
    picker:     ' . json_encode($v['picker']) . ',
    val:    "' . (isset($v["value"]) ? $v["value"] : 0) . '",
    multiply_group: ' . json_encode($group) . '
});
';
                }

                /* //////      FIELD TYPES      ////// */
                switch ($v['type']) {
                    case (''):
                        break;
                    case (NULL):
                        break;
                        /* //////      HIDDEN      ////// */
                    case ('hidden'):
                        for ($i = 0; $i < $count; $i++) {
                            $h .= $CUSTOM_UTILITY->HTML->create_element('input', array('type' => 'hidden', 'value' => (isset($values[$i]) ? $values[$i] : $v['default']), 'name' => $field_name, 'id' => $label_for . '_' . $i, 'class' => (isset($v['class']) ? $v['class'] : '')));
                        }
                        break;
                        /* //////      TEXT      ////// */
                    case ('text'):
                        if (isset($v['autocomplete_values']) && !empty($v['autocomplete_values'])) {
                            ////// Autocomplete //////
                            if ('_use_existing' === $v['autocomplete_values']) {
                                $limit = isset($v['autocomplete_limit']) ? $v['autocomplete_limit'] : -1;
                                $autocomplete_values = $this->existing_values($k);
                                // $autocomplete_values = apply_filters('WPCF_Existing_MetaBox_Values', $k);
                                // var_dump($autocomplete_values);
                                if (
                                    isset($v['autocomplete_values_order'])
                                    && !empty($v['autocomplete_values_order'])
                                    && in_array($v['autocomplete_values_order'],  array('asort', 'arsort', 'krsort', 'ksort', 'natcasesort', 'natsort', 'rsort', 'shuffle', 'sort', 'uasort', 'uksort', 'usort'))
                                ) {
                                    if (function_exists($v['autocomplete_values_order'])) {
                                        $v['autocomplete_values_order']($autocomplete_values);
                                    } else {
                                        natsort($autocomplete_values);
                                    }
                                }
                            } else {
                                $autocomplete_values = (array) $v['autocomplete_values'];
                            }
                            $v['script'] .= '$(".' . $label_for . '").autocomplete(source:' . json_encode($autocomplete_values) . ')';
                        }
                        for ($i = 0; $i < $count; $i++) {
                            $script = '';
                            $id = $label_for . '_' . $i;
                            $h .= sprintf($multipliable_wrap_start, $id);
                            $h .= $CUSTOM_UTILITY->HTML->create_element(
                                'input',
                                $CUSTOM_UTILITY->parse_arguments(
                                    array(
                                        'type' => 'text',
                                        'value' => (isset($values[$i]) ? $values[$i] : $v['default']),
                                        'name' => $field_name,
                                        'id' => $id,
                                        'size' => 32,
                                        'class' => array(
                                            $label_for,
                                            $self_name . '_input_text',
                                            $class_name . '_input_text',
                                            $label_for . '_form_field_input_text',
                                            ($multipliable ? 'multipliable' : '')
                                        )
                                    ),
                                    $v
                                )
                            );
                            if ($i >= 1 && (bool) $multipliable) {
                                $h .= $CUSTOM_UTILITY->HTML->create_element('span', $this->_metabox_html_build_multiplier_attrs($id, '-'), '-');
                            }
                            if ($v['picker']['picker']) {
                                $script .= '$("#' . $id . '").' . $v['picker']['picker'] . '(' . json_encode($v['picker']['options']) . ');' . $LF;
                            }
                            $h .= $multipliable_wrap_end;
                            $v['script'] .= $script . $LF;
                        }
                        $h .= $multipliable;
                        break;
                        /* //////      SELECT      ////// */
                    case 'select': // my_print_r($values);
                        for ($i = 0; $i < $count; $i++) {
                            $id = $label_for . '_' . $i;
                            $h .= sprintf($multipliable_wrap_start, $id);
                            $h .= $CUSTOM_UTILITY->HTML->select_element(array(
                                'name'      => sprintf('%s[%d][]', $k, $i),
                                'id'        => $id,
                                'class'     => array($self_name . '_select', $self_name . '_select_label', $class_name . '_select', $class_name . '_select_label', ($multipliable ? 'multipliable' : '')),
                                'values'    => $v['values'],
                                'labels'    => $v['value_label'],
                                'value'     => (isset($values[$i][0]) ? $values[$i][0] : (isset($v['default']) ? $v['default'] : ''))
                            ));
                            $h .= $multipliable_wrap_end;
                        }
                        $h .= $multipliable;
                        break;
                        /* //////      CHECKBOX      ////// */
                    case 'checkbox':
                        if (empty($v['values']) || (isset($v['values'][0]) && empty($v['values'][0]))) {
                            if ($v['value']) $v['values'] = $v['value'];
                            else $v['values'] = $v['label']; // Fallback to "label"
                        }
                        if (!isset($v['defaults'])) $v['defaults'] = null;
                        $v['values'] = (array) $v['values'];
                        $v['defaults'] = (array) $v['defaults'];

                        $value_groups = array();
                        if (empty($values)) $value_groups[] = array();
                        elseif (isset($values[0]) && (is_string($values[0]) || is_numeric($values[0]))) $value_groups[] = $values;
                        else $value_groups = $values;

                        foreach ($value_groups as $j => $group) {
                            $group_id = $label_for . '_' . $j;
                            $h .= sprintf($multipliable_wrap_start, $group_id);
                            $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'start', array('id' => $self_name . '_' . $label_for . '_values_' . $j, 'class' => array($self_name . '_values', $self_name . '_checkbox_values', $class_name . '_values', $class_name . '_checkbox_values')));
                            for ($i = 0; $i < count($v['values']); $i++) {
                                $id = $group_id . '_' . $i;
                                $h .= $CUSTOM_UTILITY->HTML->create_element(
                                    'label',
                                    array('class' => array($self_name . '_input_checkbox', $self_name . '_input_checkbox_label', $class_name . '_input_checkbox', $class_name . '_input_checkbox_label'), 'for' => $id),
                                    $CUSTOM_UTILITY->HTML->create_element(
                                        'input',
                                        array(
                                            'type' => 'checkbox',
                                            'name' => sprintf("%s[%d][]", $k, $j),
                                            'id' => $id,
                                            'class' => isset($v['class']) ? $v['class'] : '',
                                            'checked' => (
                                                (count($group) == 0 && in_array($v['values'][$i], $v['defaults']))
                                                ||
                                                (count($group) > 0 && in_array($v['values'][$i], $group))
                                            ) ? 'checked' : '',
                                            'value' => $v['values'][$i]
                                        )
                                    )
                                        . $CUSTOM_UTILITY->HTML->create_element('span', array('class' => 'label_text'), $this->get_value_label($v['values'], $v['value_label'], $i))
                                );
                            }
                            $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'end');
                            if ($j >= 1 && (bool) $multipliable) {
                                $h .= $CUSTOM_UTILITY->HTML->create_element('span', $this->_metabox_html_build_multiplier_attrs($group_id, '-'), '-');
                            }
                            $h .= $multipliable_wrap_end;
                        }
                        $h .= $multipliable;
                        break;
                        /* //////      RADIO      ////// */
                    case 'radio':
                        $default_set = false;
                        $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'start', array('id' => $self_name . '_' . $label_for . '_values', 'class' => array($self_name . '_values ', $self_name . '_radio_values', $class_name . '_values ', $class_name . '_radio_values')));
                        for ($i = 0; $i < count($v['values']); $i++) {
                            $h .= $CUSTOM_UTILITY->HTML->create_element(
                                'label',
                                array('class' => array($self_name . '_input_radio', $self_name . '_input_radio_label', $class_name . '_input_radio', $class_name . '_input_radio_label')),
                                $CUSTOM_UTILITY->HTML->create_element(
                                    'input',
                                    array(
                                        'type' => 'radio',
                                        'name' => $k,
                                        'id' => $label_for . '_' . $i,
                                        'checked' => (
                                            (count($values) == 0 && isset($v['default']) && $v['default'] === $v['values'][$i] && !$default_set)
                                            ||
                                            (count($values) > 0 && in_array($v['values'][$i], $values))
                                        ) ? $default_set = 'checked' : '',
                                        'value' => $v['values'][$i]
                                    )
                                ) . $CUSTOM_UTILITY->HTML->create_element('span', array('class' => 'label_text'), $this->get_value_label($v['values'], $v['value_label'], $i))
                            );
                        }
                        $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'end');
                        break;

                        /* //////      TEXTAREA      ////// */
                    case 'textarea':
                        for ($i = 0; $i < $count; $i++) {
                            $id = $label_for . '_' . $i;
                            $a = array_merge(
                                $CUSTOM_UTILITY->parse_arguments(array('rows' => 2, 'cols' => 47), $v),
                                array(
                                    'id' => $id,
                                    'name' => $k . '[]',
                                    'class' =>  array_merge(
                                        array($label_for, $self_name . '_textarea', $class_name . '_textarea'),
                                        ($multipliable ? array($self_name . '_textarea_multipliable', $class_name . '_textarea_multipliable', 'multipliable') : array())
                                    )
                                )
                            );
                            $h .= sprintf($multipliable_wrap_start, $id);
                            if (isset($v['tinymce']) && !empty($v['tinymce'])) {
                                $settings = $CUSTOM_UTILITY->parse_arguments(
                                    array(
                                        'wpautop' => FALSE,
                                        'media_buttons' => TRUE,
                                        'textarea_name' => $k . '[]',
                                        'textarea_rows' => $a['rows'],
                                        'tabindex' => FALSE,
                                        'editor_css' => NULL,
                                        'editor_class' => implode(' ', $a['class']),
                                        'editor_height' => NULL,
                                        'teeny' => FALSE,
                                        'dfw' => FALSE,
                                        'tinymce' => TRUE,
                                        'quicktags' => TRUE,
                                        'drag_drop_upload' => TRUE,
                                    ),
                                    isset($v['wp_editor_args']) ? $v['wp_editor_args'] : NULL
                                );
                                ob_start();
                                wp_editor((isset($values[$i]) ? $values[$i] : ''), $a['id'], $settings);
                                $editor = ob_get_clean();
                                $h .= $editor;
                            } else {
                                $h .= $CUSTOM_UTILITY->HTML->create_element('textarea', $a, empty($values[$i]) ? '' : $values[$i]);
                            }
                            if ($i >= 1 && (bool) $multipliable) {
                                $h .= $CUSTOM_UTILITY->HTML->create_element('span', $this->_metabox_html_build_multiplier_attrs($id, '-'), '-');
                            }
                            $h .= $multipliable_wrap_end;
                        }
                        $h .= $multipliable;
                        break;
                        /* //////      IMAGE      ////// */
                    case 'image':

                        $buttons = $CUSTOM_UTILITY->parse_arguments(array(
                            'button_pickup_name' => __('Select/Upload Image', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                            'button_delete_name' => __('Clear Image', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                        ), $v);
                        $image_size_id_suffix = $this->image_form_params['image_size_id_suffix'];
                        $delete_image_dialog = $CUSTOM_UTILITY->parse_arguments(array(
                            'message'   => __('Are you sure you want to delete the image?', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                            'title'     => __('Delete Image Confirmation', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                            'confirm_button'    => __('Clear Image', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                            'cancel_button'     => __('Cancel', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN),
                        ), ((isset($v['confirm_delete_dialog']) && is_array($v['confirm_delete_dialog'])) ? $v['confirm_delete_dialog'] : array()));
                        $error_ajax_message =
                            isset($v["error_ajax_message"]) ?
                            $v["error_ajax_message"]
                            :
                            __('Error occured while requesting image by ajax.', $WP_CUSTOM_UTILITY__LANGUAGE_DOMAIN);
                        $h .= $CUSTOM_UTILITY->HTML->create_element(
                            'p',
                            array('class' => 'dialog_confirm_delete', 'id' => 'dialog_confirm_delete_' . $label_for),
                            $delete_image_dialog['message']
                        );
                        $image_sizes = apply_filters('WPCF_Get_Post_Meta', $post->ID, $k . $this->image_form_params['image_size_suffix'], FALSE);

                        for ($i = 0; $i < $count; $i++) {
                            $image_id   = isset($values[$i]) ? $values[$i] : NULL;
                            $image_size = isset($image_sizes[$i]) ? $image_sizes[$i] : NULL;
                            $id_suffix  =  '_' . $i;
                            $id         = $label_for . $id_suffix;
                            $button_id  = $label_for . $this->image_form_params['button_suffix'] . $id_suffix;
                            $image_view_id = $label_for . $this->image_form_params['image_view_box_suffix'] . $id_suffix;
                            $image      = $WP_CUSTOM_UTILITY__TEMPLATE->attachment_image_html($image_id, array('size' => $WP_CUSTOM_UTILITY__TEMPLATE->get_custom_image_size($image_size), 'width' => '100%', 'height' => ''));
                            $image_full = wp_get_attachment_image_src($image_id, 'full');
                            $button_delete_id = $button_id . $this->image_form_params['button_delete_suffix'] . $id_suffix;

                            $h .= sprintf($multipliable_wrap_start, $id);
                            $h .= $CUSTOM_UTILITY->HTML->create_element('div', array('id' => $image_view_id, 'class' => array(
                                $self_name . '_image_view',
                                $label_for . '_image_view',
                                $id . '_image_view',
                                $class_name . '_image_view'
                            )), ($image ? $image : ''));
                            $h .= $CUSTOM_UTILITY->HTML->create_element(
                                'input',
                                array('type' => 'hidden', 'value' => $image_id, 'name' => $k . '[]', 'id' => $id, 'class' => array(
                                    $label_for,
                                    $label_for . '_image_id',
                                    $id . '_image_id',
                                    $self_name . '_image_id',
                                    $class_name . '_image_id'
                                ))
                            )
                                . $CUSTOM_UTILITY->HTML->create_element(
                                    'input',
                                    array(
                                        'type'  => 'hidden',
                                        'value' => $image_size,
                                        'name'  => $k . $this->image_form_params['image_size_suffix'] . '[]',
                                        'id'    => $label_for . $image_size_id_suffix . $id_suffix,
                                        'class' => array(
                                            $label_for . $image_size_id_suffix,
                                            $label_for . $image_size_id_suffix . $id_suffix,
                                            $self_name . $image_size_id_suffix,
                                            $class_name . $image_size_id_suffix
                                        )
                                    )
                                )
                                . $CUSTOM_UTILITY->HTML->create_element(
                                    'input',
                                    array(
                                        'type' => 'button',
                                        'value' => $buttons['button_pickup_name'],
                                        'name' => $button_id,
                                        'id' => $button_id,
                                        'class' => array(
                                            'pickup_button ui-button',
                                            $label_for . '_pickup_button',
                                            $id . '_pickup_button',
                                            $self_name . '_pickup_button',
                                            $class_name . '_pickup_button'
                                        )
                                    )
                                ) .
                                $CUSTOM_UTILITY->HTML->create_element(
                                    'input',
                                    array(
                                        'type' => 'button',
                                        'value' => $buttons['button_delete_name'],
                                        'id' => $button_delete_id,
                                        'class' => array(
                                            'delete_button ui-button',
                                            $label_for . '_delete_button',
                                            $id . '_delete_button',
                                            $self_name . '_delete_button',
                                            $class_name . '_delete_button'
                                        )
                                    )
                                );
                            if ($i >= 1 && (bool) $multipliable) {
                                $h .= $CUSTOM_UTILITY->HTML->create_element('span', $this->_metabox_html_build_multiplier_attrs($id, '-'), '-');
                            }
                            $h .= $multipliable_wrap_end;
                        } // end for
                        $h .= $multipliable;
                        $v['script'] .= '
$.set_MetaBox__Image({
        target_id:      "' . $label_for . '",
        dialog_messages:' . json_encode($delete_image_dialog) . ',
        image_size_id_suffix:"' . $image_size_id_suffix . '",
        label:          "' . $v["label"] . '",
        buttons:        ' . json_encode($buttons) . ',
        ajax_url:       "' . admin_url('admin-ajax.php') . '",
        error_ajax_message: "' . $error_ajax_message . '",
    }
);
';
                        break; /// IMAGE

                        /* //////      MEDIA BUTTON (UPLOAD ONLY)      ////// */
                    case 'media_button':
                        $h .= $CUSTOM_UTILITY->HTML->create_element(
                            'div',
                            array('id' => $label_for . '_media_button_container'),
                            $CUSTOM_UTILITY->HTML->create_element('a', array('id' => $label_for . '_media_button'), __('Add Media'))
                        );
                        $v['script'] .= apply_filters('Wrap_JavaScript', '
var button = $("#' . $label_for . '_media_button").button();

button.on("click", function(){
 var mediabox;
 if (mediabox) { mediabox.open(); return; }
 mediabox = wp.media({
  "state" : "' . $label_for . '_Media_Picker",
   button	 : { text : "' . __('Finish Upload') . '" },
 });
 mediabox.states.add([
  new wp.media.controller.Library({
   id		 : "' . $label_for . '_Media_Picker",
   title	 : "' . $v['label'] . '",
   filterable: "uploaded",
   ' . (empty($v['filetypes']) ? '' : 'library: "' . esc_attr(implode(",", $v['filetypes'])) . '",') . '
   multiple	 : mediabox.options.multiple ? "reset" : false,
   editable	 :   true,
   displayUserSettings	: false,
   contentUserSetting	: false,
   displaySettings		: true,
   allowLocalEdits		: true
  })
 ]);
 mediabox.open()
})
' . $LF, array('jquery' => TRUE));
                        break; /// MEDIA BUTTON
                        /* //////      POSTS      ////// */

                    case 'posts':
                        $posts = get_posts((array) $CUSTOM_UTILITY->array_value($v, 'query'));
                        $form_type = ($CUSTOM_UTILITY->array_value($v, 'form_type') == 'checkbox' ? 'checkbox' : 'radio');
                        $v['values'] = (array) $CUSTOM_UTILITY->array_value($v, 'values');
                        $v['defaults'] = (array) $CUSTOM_UTILITY->array_value($v, 'defaults');
                        $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'start', array(
                            'id' => $self_name . '_' . $label_for . '_values',
                            'class' => array(
                                $self_name . '_values ',
                                $self_name . '_' . $form_type . '_values',
                                $class_name . '_values ',
                                $class_name . '_' . $form_type . '_values'
                            )
                        ));
                        for ($i = 0; $i < count($posts); $i++) {
                            $id = $posts[$i]->ID;
                            $h .= $CUSTOM_UTILITY->HTML->create_element(
                                'label',
                                array(
                                    'class' => array(
                                        $self_name . '_input_' . $form_type,
                                        $self_name . '_input_' . $form_type . '_label',
                                        $class_name . '_input_' . $form_type,
                                        $class_name . '_input_radio_label'
                                    )
                                ),
                                $CUSTOM_UTILITY->HTML->create_element(
                                    'input',
                                    array(
                                        'type' => $form_type,
                                        'name' => $k . ($form_type == 'checkbox' ? '[]' : ''),
                                        'id' => $label_for . '_' . $i,
                                        'checked' => (
                                            ($WP_CUSTOM_UTILITY->is_edit_page('new')  && in_array($id, $v['defaults']))
                                            ||
                                            ($WP_CUSTOM_UTILITY->is_edit_page('edit') && count($values) > 0 && in_array($id, $values))
                                        ) ? 'checked' : '',
                                        'value' => $id
                                    )
                                )
                                    .
                                    $CUSTOM_UTILITY->HTML->create_element(
                                        'span',
                                        array('class' => 'label_text'),
                                        apply_filters('the_title', $posts[$i]->post_title)
                                            . $CUSTOM_UTILITY->HTML->create_element(
                                                'span',
                                                array('class' => $self_name . '_' . $label_for . '_values_label_post_id'),
                                                '(ID:' . $id . ')'
                                            )
                                    )
                            );
                        }
                        $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'end');
                        break;
                } // end switch

                if (isset($v['default_title_value']) && $v['default_title_value']) {
                    if (!is_array($v['default_title_value']))  $v['default_title_value'] = array('truncate' => false);
                    $truncate = isset($v['default_title_value']['truncate']) && $v['default_title_value']['truncate'];
                    $truncate_length = $truncate ? $v['default_title_value']['truncate'] : NULL;
                    $v['script'] .= '
//var field_values_orig
if (undefined === window.field_values_orig) { window.field_values_orig = new Object }
field_values_orig["' . $label_for . '"] = $("#' . $label_for . '_0").val()

$("#title")
 .css({
  "background-color": "#f1f1f1",
  "border": "none",
  "box-shadow": "none",
  "color" : "black"
 })
 .bind( "click", function(){ $("#' . $label_for . '_0").trigger("focus") } )

 var field = $("#' . $label_for . '_0")
   , title_value = $("#title").val()
   , title_value_orig = window.POST_TITLE_ORIGINAL
   , field_value_orig = window.field_values_orig["' . $label_for . '"]
 if ( (field_value_orig == title_value_orig) ) {
  field.on("keyup", function() {
   var v = $(this).val()
   $("#post_name, #title").val(v)
  })
 }


//$("#post").on("submit",function(){ return true });
' . $LF;
                }

                if (isset($v['script']) && $v['script']) {
                    $box_script .= $LF . $v['script'] . $LF;
                }
                if ($v['type'] != 'hidden') $h .= $CUSTOM_UTILITY->HTML->create_element('div', 'end');
            } // End of LOOP
        } // End if $meta_args['fields']

        $box_script = preg_replace('/[\x0a]+/', $LF, $box_script);
        $box_script = ($box_script = trim($box_script)) ?
            $CUSTOM_UTILITY->HTML->wrap_JavaScript(
                $box_script,
                array('jquery' => TRUE, 'jqueryready' => (isset($meta_args['domready']) && $meta_args['domready'] === FALSE ? FALSE : TRUE))
            )
            :
            $box_script;
        $h = apply_filters('WPCU__MetaBox_HTML_Modifier', $meta_args['prepend_html'] . $h . $box_script . $meta_args['append_html']);
        echo $h;
        return $this;
    }


    function ajax_get_thumbnail()
    {
        global $WP_CUSTOM_UTILITY__TEMPLATE;
        $img_html = $WP_CUSTOM_UTILITY__TEMPLATE->attachment_image_html($_POST['attachment_id']);
        die($img_html);
    }
    function ajax_get_image_size()
    {
        extract($_POST);
        die("get_image_sizes");
        $sizes_html = $CUSTOM_UTILITY->HTML->create_element(
            'select',
            'start',
            array(
                'name' => 'size',
                'id' => $label_for . $image_size_suffix . '_select'
            )
        );
        foreach (apply_filters('image_size_names_choose', array()) as $s) {
            if ($WP_CUSTOM_UTILITY__TEMPLATE->attachment_image_html($id, $s)) {
                $d = $WP_CUSTOM_UTILITY__TEMPLATE->get_custom_image_dimensions($s);
                $sizes_html .= $CUSTOM_UTILITY->HTML->create_element(
                    'option',
                    array('value' => $s),
                    sprintf("%s (%s x %s)", $s, $d[0], $d[1])
                );
            }
        }
        $sizes_html .= $CUSTOM_UTILITY->HTML->create_element('select', 'end');
    }

    function update($post_id = null)
    {
        global $post;
        !$post_id && $post_id = $post->ID;
        $meta_fields = $this->param('fields');

        $n = $this->param('nonce');
        if ($n && isset($_POST[$n['name']]) && !wp_verify_nonce($_POST[$n['name']], $n['action'])) return $post_id;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
        if (isset($_POST['action']) && $_POST['action'] == 'inline-save') {
            // クイックポストの時は何もしない
            return $post_id;
        }

        $pt = $this->param('post_type');
        if (isset($_POST['post_type']) && in_array($_POST['post_type'], $this->param('post_type'))) {
            if (!current_user_can('edit_post', $post_id)) return $post_id;
        } else return $post_id;
        $tmpfile = '/virtual/emy/tmp/php_test.tmp';
        file_put_contents($tmpfile, $this->name() . "\n", FILE_APPEND);
        foreach (array_keys($meta_fields) as $k) {
            $v = $_POST[$k];
            $existing_values = get_post_meta($post_id, $k);
            if (
                ($existing_values == $v)
                ||
                ($v == array('') && $existing_values == array())
            ) {
                if ($this->name() == 'coupons') {
                    //   file_put_contents($tmpfile, 'updating meta skipped: '.serialize($meta_fields), FILE_APPEND);
                }
                // If the value was not changed or empty value was posted, nothing is done.
                continue;
            }
            if ($v == array('')) {
                $v = '';
            }  // Saving Database Size, Enhance Visibility
            if ($meta_fields[$k]['type'] == 'checkbox') {
                // Each checkbox item is stored in single db record. This could be a bad old habit. Should be in serialized array and compiled in one record...
                if (count($existing_values) > 1) delete_post_meta($post_id, $k);
            }
            if (in_array($meta_fields[$k]['type'], explode(' ', 'checkbox text textarea select radio textfield hidden posts'))) {
                if ($this->name() == 'coupons') {
                    //   file_put_contents($tmpfile, 'updating meta', FILE_APPEND);
                }
                update_post_meta($post_id, $k, $v);
            } else if (in_array($meta_fields[$k]['type'], array('image'))) {
                $image_size_suffix = $this->image_form_params['image_size_suffix'];
                update_post_meta($post_id, $k, $v);
                update_post_meta($post_id, $k . $image_size_suffix, $_POST[$k . $image_size_suffix]);
            }
        }
        return $this;
    }


    function get_value_label($values, $labels, $index)
    {
        if (!isset($values) || !isset($index)) return;
        return
            isset($labels) ? (
                isset($labels[$values[$index]]) ? $labels[$values[$index]]
                : ((isset($labels[$index]) && $labels[$index]) ? $labels[$index] : $values[$index])
            )
            :
            $values[$index];
    }
    function get_value_label_array($field)
    {
        $l = array();
        if ($field) {
            $f = &$this->get_field($field);
            if (isset($f['value_label'])) {
                foreach ($f['values'] as $i => $v) {
                    $l[$v] = (isset($f['value_label'][$i])) ? $f['value_label'][$i] : $v;
                }
                return $l;
            }
            return;
        } else {
        }
    }
    function &get_all_labels_and_values()
    {
        $a = array();
        foreach ($this->get_field_names() as $k) {
            if ($this->get_value_label_array($k)) $a[$k] = $this->get_value_label_array($k);
        }
        return $a;
    }
    function get_keys($key = null)
    {
        return $this->get_field_names();
    }
    function &get_field_label($key)
    {
        $args = $this->arguments();
        $l = isset($args['fields'][$key]) && isset($args['fields'][$key]['label']) ? $args['fields'][$key]['label'] : '';
        return $l;
    }
    function &get_field($name)
    {
        $f = $this->param('fields');
        $n = NULL;
        if (isset($f[$name])) {
            $n = $f[$name];
            return $n;
        }
        return $n;
    }
    function get_field_names()
    {
        $args = $this->arguments();
        return array_keys($args['fields']);
    }
    function get_field_labels()
    {
        $a = array();
        foreach ($this->get_keys() as $f) {
            $a[$f] = $this->get_field_label($f);
        }
        return $a;
    }

    function name()
    {
        return $this->param('name');
    }
    function label()
    {
        return $this->title();
    }
    function title()
    {
        return $this->param('title');
    }
    // DEBUG 

    function existing_values($args)
    {
        global $wpdb, $CUSTOM_UTILITY;
        if (is_string($args)) {
            $k = $args;
            $args = array(
                'key' => $k,
                '_only_values' => TRUE,
                'exclude_revision' => TRUE,
                '_hide_empty' => TRUE,
            );
        }
        $args = $CUSTOM_UTILITY->parse_arguments(
            array(
                'key'   => NULL,
                'order' => TRUE,
                'exclude_revision' => FALSE,
                '_only_values' => FALSE,
                '_hide_empty' => TRUE,
            ),
            $args
        );
        // post_type=revision 
        // extract($args);
        $list = array();
        if (empty($args['key'])) {
            return $list;
        }

        $q = "SELECT * FROM $wpdb->postmeta WHERE meta_key='%s'";
        if ($args['exclude_revision']) {
            //  $q .= " AND post_type<>'revision'";
        }
        // $values = $wpdb->get_results($q); 
        $values = $wpdb->get_results($wpdb->prepare($q, $args['key']));
        foreach ((array) $values as $v) {
            $meta_value = maybe_unserialize($v->meta_value);
            if ($args['_hide_empty'] && empty($meta_value)) continue;
            foreach ((array) $meta_value as $mv) {
                if (is_array($mv) && isset($mv[0])) {
                    $mv = isset($mv[0]);
                }
                if (!isset($list[$mv])) {
                    $list[$mv] = 1;
                } else {
                    $list[$mv]++;
                }
            }
        }
        if ($args['order']) asort($list);
        return $args['_only_values'] ? array_keys($list) : $list;
    }



    function _test($d = null, $a = array())
    {
        global $_test;
        if (isset($d)) {
            if ($a['write_to_file']) {
                $testfile = TEMPLATEPATH . '/_test';
                if (is_array($d)) foreach ($d as $k => $v) file_put_contents($testfile, $k . '=>' . $v . ',' . "\n", FILE_APPEND);
                else file_put_contents($testfile, $d . "\n", FILE_APPEND);
            } else {
                $_test = $d;
            }
        }
    }

    /*/// CLASS PROPERTIES ///*/

    private $accepted_param_keys = array(
        'post_type',
        'nonce',
        'name',
        'fields',
        'title',
        'context',
        'priority',
        'init_hook',
        'multiply_group',
        'script',
        'row_group',
        'append_html',
        'prepend_html',
        'type',
        'ID',
    );
    var $image_params = array(); // ?
} // end of CLASS
