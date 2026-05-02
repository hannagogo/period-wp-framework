<?php
/*////// Google Fonts / Material Icons //////*/
function googlefont_query()
{
    $tags = array(
        '<link rel="preconnect" href="https://fonts.googleapis.com">',
        '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    );

    $tags[] = '<link href="https://fonts.googleapis.com/css2?' . implode('&', func_get_args()) . '" rel="stylesheet">';
    return implode("\n", $tags) . "\n";
};

add_shortcode('material-icons', function ($name) {
    return html_span(array('class' => 'material-icons'), $name);
});


/*////// FONTAWESOME //////*/
add_shortcode('fontawesome', function ($attr) {
    if (empty($attr)) {
        return;
    }
    if (isset($attr[0])) {
        $attr = array(
            'name' => $attr[0]
        );
    }
    $attr = apply_filters('WPCU__Arguments', array(
        'name' => '',
        'style' => 'fas',
    ), $attr);
    $h = apply_filters('CF_HTML', 'span', array('class' => array($attr['style'], 'fa-' . $attr['name'])), '');
    return $h;
});


add_action('wp_head', function () {
    // echo '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.2/css/all.css" integrity="sha384-/rXc/GQVaYpyDdyxK+ecHPVYJSN9bmVFBvjA/9eOB+pb3F2w2N6fc5qB9Ew5yIns" crossorigin="anonymous">';
});
