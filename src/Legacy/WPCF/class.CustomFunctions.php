<?php
class CustomFunctions {

public function __construct($attr = NULL) {
 define('CF_PREFIX', 'CF_');
 $this->_setup_custom_functions_filters();
}



private function _setup_custom_functions_filters() {
 $i = 10;
 add_filter('CF_Format_Price', 'format_price', 3, 1);
 add_filter( CF_PREFIX.'HTML', 'createHTMLElement', $i, 5 );
 add_filter( CF_PREFIX.'Wrap_JavaScript', 'wrapJavaScript', $i, 2 );
 add_filter( CF_PREFIX.'HTML_Select', 'html_select_element', $i, 1 );
 add_filter( CF_PREFIX.'Default_Value', 'default_value', $i, 3 );
 add_filter( CF_PREFIX.'Remove_HTML_Attribute', 'remove_html_attribute', $i, 2 );
 add_filter( CF_PREFIX.'Truncate_HTML', 'truncate_html', $i, 4 );
 add_filter( CF_PREFIX.'Array_Value', 'array_value', $i, 3 );
}
}
