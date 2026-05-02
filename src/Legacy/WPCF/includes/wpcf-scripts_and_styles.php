<?php
/* ////// Scripts and Styles ////// */
function register_file( $var=NULL, $handle=NULL, $src=NULL, $deps=NULL, $ver=NULL, $rest=NULL ) {
 if (!$var || !$handle || !$src) return;
 global $wp_custom_script_handles, $wp_custom_style_handles;
 if (!isset($wp_custom_script_handles)) $wp_custom_script_handles= array();
 if (!isset($wp_custom_style_handles)) $wp_custom_style_handles  = array();
 
 if ($var == 'css') {
  wp_register_style(
   $wp_custom_style_handles[array_push($wp_custom_style_handles, $handle)-1],
   $src, $deps, $ver, ($rest? $rest : 'screen')
  );
  return $handle;
 }
 if ($var == 'js') {
  wp_register_script(
   $wp_custom_script_handles[array_push($wp_custom_script_handles, $handle)-1],
   $src, $deps, $ver, $rest
  );
  return $handle;
 }
 return false;
}


function register_custom_scripts_and_styles() {
 global $wp_custom_functions, $wp_version
 ;
 $_is_version_4point5_higher = version_compare( $wp_version, '4.5' ) >= 0;
 $mtime_format = 'Y-m-d-h-i-s';
 //// CORE CSS Files /// WPCF Common and Base //// common.css is obsolete.
 register_file('css', 
  $_is_version_4point5_higher ? 'wpcf-common' : 'common',
  WPCF_CSS_LIBRARY_URL_ROOT . '/common.min.css' . wpcf_mdstring(WPCF_PLUGIN_DIR . 'css/common.min.css'),
  NULL, NULL, 'screen,print');
 register_file('css', 'wpcf-base', 
  WPCF_CSS_LIBRARY_URL_ROOT . '/base.min.css' . wpcf_mdstring(WPCF_PLUGIN_DIR . 'css/base.min.css'),
  NULL, NULL, 'screen,print');
 $jqueryui_version = apply_filters(WPCF_PREFIX.'Modify_JQueryUI_Version', NULL);
 if (empty($jqueryui_version)) { $jqueryui_version = WPCF_JQUERYUI_VERSION; }
 /// Theme
 $theme_css_path = get_stylesheet_directory() . preg_replace('/^.*?(\x2f[^\x2f]+?)$/', '$1', get_bloginfo('stylesheet_url'));
 register_file('css', 'theme-style', get_bloginfo('stylesheet_url') . wpcf_mdstring($theme_css_path), (array) 'wpcf-base', NULL, 'screen,print');

 /// Admin
 register_file('js',  'theme_admin',	 WPCF_JS_LIBRARY_URL_ROOT . '/admin/admin.js', array('jquery')); //// THIS IS OBSOLETE.
 register_file('css', 'wpcf-admin',		 WPCF_CSS_LIBRARY_URL_ROOT . '/admin.css');
 register_file('js',  'wpcf-admin',		 WPCF_JS_LIBRARY_URL_ROOT . '/admin/admin.js', array('jquery'));
 
 if (file_exists( $f = trailingslashit( get_stylesheet_directory() ) . 'admin.css') ) {
  register_file('css', 'theme-admin-style',
   trailingslashit( get_stylesheet_directory_uri() ) . 'admin.css' . wpcf_mdstring($f), array('wpcf-admin'), NULL, 'screen');
 }

 //// jQuery Optimization
 register_file('js',  'jquery-2.1.1', 'https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js'); 
 register_file('js',  'jquery.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-1.11.1.min.js'); 
 register_file('js',  'jquery-2.1.1.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-2.1.1.min.js'); 
 register_file('js',  'jquery-3.3.1', 'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js'); 
 register_file('js',  'jquery-3.3.1.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-3.3.1.min.js'); 
 register_file('js',  'jquery.mobile', 'https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.3/jquery.mobile.min.css', array('jquery')); 
 register_file('css', 'jquery.mobile', 'https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.3/jquery.mobile.min.js'); 
 register_file('js',  'jquery.cookie', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.cookie/jquery.cookie.min.js'); 
 register_file('js',  'js-cookie', WPCF_JS_LIBRARY_URL_ROOT.'/js-cookie/js.cookie.js'); 
 //// jQuery Plugins
 register_file('js', 'jquery.easing.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.easing/jquery.easing.1.4.1.min.js', array('jquery')); 
 register_file('js', 'jquery.mousewheel.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.mousewheel/jquery.mousewheel.min.js', array('jquery'));
 register_file('js', 'jquery.easing.compatibility', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.compatibility.min.js', array('jquery'));
 register_file('js', 'jquery.easing', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js', array('jquery.easing.compatibility'));
 register_file('js', 'jquery.mousewheel', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.6/jquery.mousewheel.min.js', array('jquery'));
 /// jQuery-UI
 register_file('js', 'jquery-ui', 'https://code.jquery.com/ui/'.$jqueryui_version.'/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery.ui.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-'.$jqueryui_version.'/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery-ui.1.11.4', 'https://code.jquery.com/ui/1.11.4/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery-ui.1.11.4.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-1.11.4/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery-ui.1.12.1', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery-ui.1.12.1.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-1.12.1/jquery-ui.min.js', array('jquery'));

 register_file('css', 'jquery-ui', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-'.$jqueryui_version.'/jquery-ui.min.css'); 
 register_file('css', 'jquery-ui-structure', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-'.$jqueryui_version.'/jquery-ui.structure.min.css'); 
 register_file('css', 'jquery-ui.1.11.4', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-1.11.4/jquery-ui.min.css'); 
 register_file('css', 'jquery-ui-structure.1.11.4', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-1.11.4/jquery-ui.structure.min.css'); 
 register_file('css', 'jquery-ui.1.12.1', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-1.12.1/jquery-ui.min.css'); 
 register_file('css', 'jquery-ui-structure.1.12.1', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-1.12.1/jquery-ui.structure.min.css'); 

 register_file('js', 'jquery-ui.timepicker-addon',	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui.timepicker.addon/jquery-ui-timepicker-addon.min.js', array('jquery-ui.sliderAccess'));
 register_file('css','jquery-ui.timepicker-addon',	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui.timepicker.addon/jquery-ui-timepicker-addon.css');
 register_file('js', 'jquery-ui.sliderAccess',		 WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui.timepicker.addon/jquery-ui-sliderAccess.min.js', array('jquery-ui'));
 //// localization files is at the bottom.

 // UI
 register_file('js', 'jquery.multisortable', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.multisortable.min.js', array('jquery-ui'));
 register_file('js', 'jquery.stepper',		 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.stepper/jquery.stepper.min.js', array('jquery'));
 register_file('css', 'jquery.stepper',		 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.stepper/jquery.stepper.min.css');

 //// Utilities
 register_file('js', 'javascript.utility',
  WPCF_JS_LIBRARY_URL_ROOT.'/javascript.utility.min.js' . wpcf_mdstring(WPCF_PLUGIN_DIR.'js/javascript.utility.min.js')
 );
 register_file('js', 'jquery.utility', 
  WPCF_JS_LIBRARY_URL_ROOT.'/jquery.utility.min.js' . wpcf_mdstring(WPCF_PLUGIN_DIR.'js/jquery.utility.min.js')
  , array('jquery')
 );
 register_file('js', 'phpjs.datetime', WPCF_JS_LIBRARY_URL_ROOT.'/phpjs.datetime.min.js', array('jquery')); 

 register_file('css', 'welcart-common', WPCF_CSS_LIBRARY_URL_ROOT.'/welcart.css', array('usces_default_css','usces_cart_css'));
 register_file('css', 'event-schedule', WPCF_CSS_LIBRARY_URL_ROOT . '/event-schedule.css');

 register_file('css', 'usces-custom', get_bloginfo('template_url').'/usces_cart.css', array('welcart-common'));
 register_file('css', 'usces-theme-custom', get_stylesheet_directory_uri().'/usces_cart.css', array('welcart-common'));
 register_file('css', 'welcart-custom', 
  get_bloginfo('template_url').'/welcart.css' . wpcf_mdstring(trailingslashit(get_template_directory()).'/welcart.css'),
  array('welcart-common')
 );
 register_file('css', 'welcart-theme-custom',
  get_stylesheet_directory_uri().'/welcart.css' . wpcf_mdstring(trailingslashit(get_template_directory()).'/welcart.css'),
  array('welcart-common')
 );

 //// Representative scripts
 register_file('js', 'jquery.imageBox',		 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.imageBox.1.8.min.js', array('jquery'));
 register_file('js', 'jquery.customBox',	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.customBox.min.js', array('jquery'));
 register_file('js', 'jquery.anystretch', 	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.anystretch/jquery.anystretch.min.js', array('jquery'));
 register_file('js', 'jquery.masonry.2.1.0', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.masonry/jquery.masonry.min.js', array('jquery'));
 register_file('js', 'jquery.masonry',		 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.masonry/masonry.pkgd.min.js', array('jquery'));
 register_file('js', 'jquery.dotdotdot',	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.dotdotdot/jquery.dotdotdot-1.5.1.min.js', array('jquery'));
 register_file('js', 'jquery.nanoScrollerJS',WPCF_JS_LIBRARY_URL_ROOT.'/nanoScroller/jquery.nanoscroller.min.js', array('jquery')); 
 register_file('css', 'jquery.nanoScrollerJS',WPCF_JS_LIBRARY_URL_ROOT.'/nanoScroller/nanoscroller.css'); 
 register_file('js', 'jquery.apply_nanoScroller',	 WPCF_JS_LIBRARY_URL_ROOT.'/nanoScroller/nanoscroller_init.min.js', array('jquery.nanoScrollerJS')); 
 register_file('css', 'jquery.apply_nanoScroller',	 WPCF_JS_LIBRARY_URL_ROOT.'/nanoScroller/nanoscroller_init.min.css'); 
 register_file('js', 'jquery.antiscroll', 	WPCF_JS_LIBRARY_URL_ROOT.'/antiscroll/antiscroll.min.js', array('jquery.mousewheel')); 
 register_file('js', 'jquery.antiscroll.init', WPCF_JS_LIBRARY_URL_ROOT.'/antiscroll/antiscroll.init.min.js', array('jquery.antiscroll')); 
 register_file('css', 'jquery.antiscroll', 	 WPCF_JS_LIBRARY_URL_ROOT.'/antiscroll/antiscroll.css'); 
 register_file('js', 'mason',				 WPCF_JS_LIBRARY_URL_ROOT.'/mason/mason.min.js', array('jquery'));
 register_file('js', 'freewall',			 WPCF_JS_LIBRARY_URL_ROOT.'/freewallfreewall.min.js', array('jquery'));
 register_file('js', 'jquery.dotdotdot',	 WPCF_JS_LIBRARY_URL_ROOT.'/dotdotdot/jquery.dotdotdot-1.4.2-packed.js', array('jquery'));
 register_file('js', 'jquery.vgrid',		 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.vgrid/jquery.vgrid.min.js', array('jquery'));
 register_file('js', 'FLAutoKerning',		 WPCF_JS_LIBRARY_URL_ROOT.'/FLAutoKerning/FLAutoKerning.js');

 register_file('js', 'prettyPhoto',			 WPCF_JS_LIBRARY_URL_ROOT.'/prettyphoto/js/jquery.prettyPhoto.min.js', array('jquery'));
 register_file('js', 'prettyPhoto.init',	 WPCF_JS_LIBRARY_URL_ROOT.'/prettyphoto/jquery.prettyPhoto.init.min.js', array('prettyPhoto', 'jquery.utility'));
 register_file('css', 'prettyPhoto',		 WPCF_JS_LIBRARY_URL_ROOT.'/prettyphoto/css/prettyPhoto.min.css');

 register_file('js', 'jquery.magnific-popup', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.magnific-popup/jquery.magnific-popup.min.js', array('jquery'));
 register_file('css', 'jquery.magnific-popup',WPCF_JS_LIBRARY_URL_ROOT.'/jquery.magnific-popup/magnific-popup.css');
 register_file('js', 'jquery.magnific-popup.implicitly',	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.magnific-popup.implicitly.js', array('jquery.magnific-popup'));
 register_file('css', 'jquery.magnific-popup.implicitly',	 WPCF_JS_LIBRARY_URL_ROOT.'/jquery.magnific-popup.implicitly.css', array('jquery.magnific-popup'));

 register_file('js', 'fitty', WPCF_JS_LIBRARY_URL_ROOT.'/fitty/fitty.min.js');

 register_file('js', 'iScroll', WPCF_JS_LIBRARY_URL_ROOT.'/iScroll/iscroll.js');
 register_file('js', 'jquery.drawer', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.drawer/js/drawer.min.js', array('jquery','iScroll'));
 register_file('js', 'jquery.drawer.init', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.drawer.init.js', array('jquery.utility', 'jquery.drawer'));
 register_file('css', 'jquery.drawer', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.drawer/css/drawer.min.css');

 register_file('js', 'jquery.inview.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.inview/jquery.inview.min.js', array('jquery'));
 register_file('js', 'jquery.inview', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inview/1.0.0/jquery.inview.min.js', array('jquery'));

 register_file('js', 'jquery.bxslider.local', WPCF_JS_LIBRARY_URL_ROOT.'/bxslider-4-4.2.12/dist/jquery.bxslider.min.js', array('jquery'));
 register_file('css', 'jquery.bxslider.local', WPCF_JS_LIBRARY_URL_ROOT.'/bxslider-4-4.2.12/dist/jquery.bxslider.min.css');
 register_file('js', 'jquery.bxslider', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js', array('jquery'));
 register_file('css', 'jquery.bxslider', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css');
 
 /// Representation CSS
 /* // FONTS // */
 /// Font Awesome
 register_file('css', 'fontawesome-4.7.0', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
 register_file('js', 'fontawesome', WPCF_JS_LIBRARY_URL_ROOT.'/fontawesome-free-5.0.8/svg-with-js/js/fontawesome-all.js');
 /// Material Icons
 register_file('css', 'fonts.material-icons', '');

 //// UI
 register_file('js', 'ajaxzip3', WPCF_JS_LIBRARY_URL_ROOT.'/ajaxzip3/ajaxzip3.js'); 
 register_file('js', 'ajaxzip3.init', WPCF_JS_LIBRARY_URL_ROOT.'/ajaxzip3/ajaxzip3.init.js', array('ajaxzip3')); 
 register_file('js', 'YubinBango-js', 'https://yubinbango.github.io/yubinbango/yubinbango.js'); 
 register_file('js', 'YubinBango-js.local', WPCF_JS_LIBRARY_URL_ROOT.'/yubinbango.js'); 
 register_file('js', 'jquery.chosen', WPCF_JS_LIBRARY_URL_ROOT.'/chosen/chosen.jquery.min.js', array('jquery')); 
 register_file('css', 'jquery.chosen', WPCF_JS_LIBRARY_URL_ROOT.'/chosen/chosen.min.css'); 
 register_file('js', 'jquery.colorpicker', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.colorpicker/jquery.colorpicker.min.js', array('jquery-ui')); 
 register_file('css', 'jquery.colorpicker', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.colorpicker/jquery.colorpicker.min.css'); 
 register_file('css', 'jquery.tag-editor', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.tag-editor.min.js'); 
 register_file('js', 'autokana', WPCF_JS_LIBRARY_URL_ROOT.'/autokana.js');
 register_file('js', 'jquery.floater', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-floater/js/jquery.floater.2.0.min.js', array('jquery','hoverIntent'));
 register_file('js', 'craftmap', WPCF_JS_LIBRARY_URL_ROOT.'/craftmap/js/craftmap.min.js', array('jquery')); 
 //// jQuery UI Themes
 $jqueryui_cdn_root = '//ajax.googleapis.com/ajax/libs/jqueryui/';
 $jqueryui_theme_cdn_root = $jqueryui_cdn_root.$jqueryui_version.'/themes/';
 $jqueryui_theme_root = WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-'.$jqueryui_version.'/themes/';
 register_file('css', 'jquery-ui.structure', $jqueryui_cdn_root.$jqueryui_version.'/jquery-ui.structure.min.css', NULL, 'screen');
 register_file('css', 'jquery-ui.structure.local', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui-'.$jqueryui_version.'/jquery-ui.structure.min.css', NULL, 'screen');
 foreach (array(
  'black-tie',		'blitzer',		'cupertino',	'dark-hive',	'dot-luv',		'eggplant',			'excite-bike',	'flick',
  'hot-sneaks',		'humanity',		'le-frog',		'mint-choc',	'overcast',		'pepper-grinder',	'redmond',		'smoothness',
  'south-street',	'start',		'sunny',		'swanky-purse',	'trontastic',	'ui-darkness',		'ui-lightness',	'vader'
 ) as $t) {
  register_file('css', 'jquery-ui-theme.'.$t.'.theme', $jqueryui_theme_cdn_root.$t.'/theme.css', array('jquery-ui.structure'), NULL, 'screen');
  register_file('css', 'jquery-ui-theme.'.$t.'.theme.local', $jqueryui_theme_root.$t.'/theme.css', array('jquery-ui.structure.local'), NULL, 'screen');
 }

 /// jQuery TimePiker Language Files
 foreach (array('af','bg','ca','cs','da','de','el','es','et','eu','fi','fr','gl','he','hr','hu','id','it','ja','ko','lt','nl','no','pl','pt-BR','pt','ro','ru','sk','sr-RS','sr-YU','sv','th','tr','uk','vi','zh-CN','zh-TW') as $lang) {
  register_file('js', 'jquery-ui.timepicker-addon.'.$lang, WPCF_JS_LIBRARY_URL_ROOT.'/jquery.ui.timepicker.addon/i18n/jquery-ui-timepicker-'.$lang.'.js', array('jquery-ui.timepicker-addon'));
 }
 /// jQuery DatePicker Language Files
 foreach (array('af','ar','az','bg','bs','ca','cs','da','de-CH','de','el','en-GB','eo','es','et','eu','fa','fi','fo','fr-CH','fr','he','hr','hu','hy','id','is','it','ja','ko','lt','lv','ms','nl-BE','nl','no','pl','pt-BR','ro','ru','sk','sl','sq','sr-SR','sr','sv','ta','th','tr','uk','vi','zh-CN','zh-HK','zh-TW') as $lang) {
  register_file('js', 'jquery-ui.datepicker.'.$lang, WPCF_JS_LIBRARY_URL_ROOT.'/jquery.ui.datepicker.l10n/jquery.ui.datepicker-'.$lang.'.js', array('jquery-ui'));
 }
}


function enqueue_jquery_code($script = NULL)		 { enqueue_code('jquery_codes', $script); }
function enqueue_javascript_code($script = NULL)	 { enqueue_code('javascript_codes', $script); }
function enqueue_admin_javascript_code($script = NULL) { enqueue_code('admin_javascript_codes', $script); }
function enqueue_admin_jquery_code($script = NULL)	 { enqueue_code('admin_jquery_codes', $script); }
function enqueue_js_library_handle($handle = NULL)	 { enqueue_handle('js_libraries', $handle); }
function enqueue_admin_js_library_handle($handle = NULL) { enqueue_handle('admin_js_libraries', $handle); }
function enqueue_css_handle($handle = NULL)			 { enqueue_handle('css_handles', $handle); }
function enqueue_admin_css_handle($handle = NULL)	 { enqueue_handle('admin_css_handles', $handle); }
function enqueue_admin_css_code($code = NULL)		 { if ($code) enqueue_code('admin_css_codes', $code); }
function enqueue_css_code($code = NULL)				 { if ($code) enqueue_code('css_codes', $code); }

function enqueue_code($global_name = NULL, $code = NULL) {
 $names = array('javascript_codes', 'jquery_codes', 'css_codes', 'admin_javascript_codes', 'admin_jquery_codes', 'admin_css_codes');
 return enqueue_codes_and_handles($names, $global_name, $code);
}

function enqueue_handle($global_name = NULL, $handle = NULL) {
 $names = array('js_libraries', 'css_handles', 'admin_css_handles', 'admin_js_libraries');
 return enqueue_codes_and_handles($names, $global_name, $handle);
}

function enqueue_codes_and_handles($names, $global_name = NULL, $data = NULL) {
 $names = (array) $names;
 if (!$global_name || !$data || !in_array($global_name, $names)) return;
 global ${$global_name};
 $data = (array) $data;
 foreach ($data as $d) { ///
  if (trim($d)) {
   $f = false;
   foreach (${$global_name} as $s) {
    if ($s == $d) { $f = true; break; }
   }
   if (!$f) ${$global_name}[] = $d;
  }
 }
 return ${$global_name};
}

function is_queued_handle($global_name, $handle) {
 if (!$handle || !isset($GLOBALS[$global_name])) return;
 return (in_array($handle, $GLOBALS[$global_name]));
}

function enqueue_scripts_and_styles() {
 global $javascript_files, $javascript_codes, $js_libraries, $jquery_codes, $css_files, $css_handles, $css_codes,
  $admin_javascript_files, $admin_javascript_codes, $admin_js_libraries, $admin_jquery_codes, $admin_css_files, $admin_css_handles, $admin_css_codes
 ;
 $lang = get_option("WPLANG"); //$lang = preg_replace('/^(.*?)[-_].*?$/', '$1', $lang);
 $dependencies = array(
  'prettyPhoto'		 => 'prettyPhoto.init',
  'jquery.fancybox'	 => array('jquery.easing', 'jquery.mousewheel'),
  'ajaxzip2'		 => 'ajaxzip2.init',
  'ajaxzip3'		 => 'ajaxzip3.init',
  'jquery.capSlide'	 => 'jquery.capSlide.init.wp',
  'jquery.jcontent'	 => 'jquery.jcontent.init',
  'jquery.treeview'	 => 'jquery.treeview.init',
  'jquery.vegas'	 => 'jquery.vegas.init',
  'jquery.toggleElements'	 => 'jquery.toggleElements.init',
  'jScrollbar'		 => 'jScrollbar.init',
  'jquery.fancybox2' => 'jquery.fancybox2.implicitly',
  'jquery-ui.timepicker-addon' => 'jquery-ui.timepicker-addon.'.$lang,
  'jquery-ui' => 'jquery-ui.datepicker.'.$lang,
  'jquery.magnific-popup' => 'jquery.magnific-popup.implicitly',
 );

 $css_dependencies = array(
  'jquery.fancybox'	 => 'jquery.fancybox',
  'jquery.fancybox.implicitly.nocss' => 'jquery.fancybox',
  'jquery.fancybox2' => 'jquery.fancybox2',
  'iebngfix'		 => 'iebngfix',
  'jquery.slider'	 => 'jquery.slider',
  'jquery.jplayer'	 => 'jquery.jplayer',
  'jquery-ui'		 => 'jquery-ui',
  'jquery-ui.1.11.4' => 'jquery-ui.1.11.4',
  'jquery-ui.1.12.1' => 'jquery-ui.1.12.1',
  'jquery.ui.datetimepicker' => 'jquery.ui.datetimepicker',
  'Elastislide'		 => 'Elastislide',
  'jquery.chosen'	 => 'jquery.chosen',
  'jquery.treeview'	 => 'jquery.treeview',
  'jquery.shadow'	 => 'jquery.shadow',
  'jquery.nanoScrollerJS' => 'jquery.nanoScrollerJS',
  'jquery.apply_nanoScroller' => 'jquery.apply_nanoScroller',
  'powertip'		 => 'powertip',
  'jquery.antiscroll'=> 'jquery.antiscroll',
  'photoswipe'		 => 'photoswipe',
  'jquery.kwicks'	 => 'jquery.kwicks',
  'jquery.stepper'	 => 'jquery.stepper',
  'prettyPhoto'		 => 'prettyPhoto',
  'fseditor'		 => 'fseditor',
  'jquery.antiscroll'=> 'jquery.antiscroll',
  'jquery.colorpicker' => 'jquery.colorpicker',
  'jquery.magnific-popup' => 'jquery.magnific-popup',
  'jquery.magnific-popup.implicitly' => 'jquery.magnific-popup.implicitly',
  'jquery.drawer'	 => 'jquery.drawer',
 );
 
 enqueue_post_scripts_and_styles();

 $css_handles_tmp = NULL;
 $js_libraries_tmp = NULL;

 if (!is_admin()) {
  $css_handles_tmp = $css_handles;
  $js_libraries_tmp = $js_libraries;
 }
 else {
  $css_handles_tmp = $admin_css_handles;
  $js_libraries_tmp = $admin_js_libraries;
 } ;//my_print_r($js_libraries_tmp);
 foreach ($js_libraries_tmp as $v) {
  if (!$v) continue;
  wp_enqueue_script($v);
  if (array_key_exists($v, $dependencies)) {
   foreach ((array) $dependencies[$v] as $d) wp_enqueue_script($d);
  }
  if (array_key_exists($v, $css_dependencies)) {
   foreach ((array) $css_dependencies[$v] as $d) wp_enqueue_style($d);
  }
 }
 foreach ($css_handles_tmp as $c) {
  wp_enqueue_style($c);
 }
}

function cdn_fallback_js_library($handle, $resource_name) {
 if (is_queued_handle($group, $handle)) return;
}

function wpcf_set_jqueryui($args=array()) {
 if (is_string($args)) {
  $args['theme'] = $args;
 }
 $args = apply_filters('WPCF_Parse_Arguments', array(
  'ui'=>'jquery-ui',
  'theme'=>'jquery-ui-theme.smoothness.theme'
 ), $args);
 do_action('WPCF_Set_JS_Handle', array($args['ui_handle']));
 do_action('WPCF_Set_CSS_Handle', array($args['theme_handle']));
}

function js_init_script($key) {
 $scripts = array();
 
 $scripts['jquery.fancybox.init'] = '
 $.fn.applyFancyBox = function(options) {
  this.each(function(){
   $($(this).attr("href")).wrap($("<div ></div>").css("display","none"));
   $(this).fancybox(options);
  });
 };';
 $scripts['jquery.fancybox.implicitly'] = '$("a").each(function(){
   var a = $(this);
   var ext = "(jpg|gif|tif|jpeg|tiff|png|bmp|bm|ico|dwg|dxf|svg|jpe|pct|pict|mng)";
   var href = a.attr("href");
   if (!href) return;
   if (!a.attr("title")) {
    var c = $(a.children("img").get(0));
    if (c.attr("title")) a.attr("title", c.attr("title"));
    else if (c.attr("alt")) a.attr("title", c.attr("alt"));
   }
   if (href.match(new RegExp("\." + ext + "$", "i"))) a.fancybox({
    "titlePosition"	 : "inside"
   });
  });';
 $scripts['jquery.smoothscroll.init'] = '$(document).ready(function(){$(document).smoothScroll();});';
 
 return $scripts[$key];
}


function enqueue_post_scripts_and_styles($posts=NULL) {
 global 
  $javascript_files, $javascript_codes, $js_libraries, $jquery_codes, $css_files, $css_handles, $css_codes
 ;
 if ($posts == NULL) global $posts;
 if (is_admin_page()) { return ; }
 if ($posts) {
  foreach ($posts as $p) {
   $id = $p->ID;
   if ($css = apply_filters('WPCF_Get_Post_Meta', $id, 'css', FALSE)) {
//    while (isset($css[0]) && is_array($css[0])) $css = $css[0];
    foreach ( array_flatten($css) as $tmp) if ($tmp = trim($tmp)) $css_codes[] = do_shortcode($tmp);
   }
   if ($js  = apply_filters('WPCF_Get_Post_Meta', $id, 'js',  FALSE)) {
//    while (isset($js[0]) && is_array($js[0])) $js = $js[0];
    foreach ( array_flatten($js)  as $tmp) if ($tmp = trim($tmp)) $javascript_codes[] = $tmp;
   }
   if ($jq = apply_filters('WPCF_Get_Post_Meta', $id, 'jquerycode', FALSE)) {
//    while (isset($jq[0]) && is_array($jq[0])) $jq = $jq[0];
    foreach ( array_flatten($jq) as $tmp) if ($tmp = trim($tmp)) $jquery_codes[] = $tmp;
   }
   if ($cssfile_list = apply_filters('WPCF_Get_Post_Meta', $id, 'cssfile', FALSE)) {
//    while (isset($cssfile_list[0]) && is_array($cssfile_list[0])) $cssfile_list = $cssfile_list[0];
    foreach ( array_flatten($cssfile_list) as $tmp) {
     foreach (explode("\n", $tmp) as $f) { if ($f = trim($f)) $css_files[] = do_shortcode($f); }
    }
   }
   if ($jsfile_list = apply_filters('WPCF_Get_Post_Meta', $id, 'jsfile', FALSE)) {
//    while (isset($jsfile_list[0]) && is_array($jsfile_list[0])) $jsfile_list = $jsfile_list[0];
    foreach ( array_flatten($jsfile_list) as $tmp) {
	 foreach (explode("\n", $tmp) as $f) { if ($f = trim($f)) $javascript_files[] = do_shortcode($f); }
	}
   }
   foreach ( array_flatten(apply_filters('WPCF_Get_Post_Meta', $id, 'css_handles')) as $c) $css_handles[] = $c;
   foreach ( array_flatten(apply_filters('WPCF_Get_Post_Meta', $id, 'js_libraries')) as $j) $js_libraries[] = $j;
  } 
 }
}


function document_scripts_and_styles() {
 global 
  $javascript_files, $javascript_codes, $js_libraries, $jquery_codes, $css_files, $css_handles, $css_codes
 ;

 if ($css_files) foreach ($css_files as $c)
  echo createHTMLElement('link', array('rel'=>'stylesheet', 'href'=>$c, 'media'=>'all', 'class'=>'custom_css_files')) . LF;
 if ($css_codes)
  echo createHTMLElement('style', array('type'=>'text/css', 'class'=>'custom_css_code'),
   createHTMLElement('_comment', LF . implode(LF, $css_codes) . LF)
  ) . LF;
 if ($javascript_files) foreach ($javascript_files as $j)
  echo createHTMLElement('script', array('src'=>$j, 'type'=>'text/javascript', 'class'=>'custom_javascript_files'), NULL) . LF;
 if ($javascript_codes)
  echo wrapJavaScript(str_replace(';;',';',implode(';'.LF, $javascript_codes)), NULL, array('class'=>'custom_javascript_code')) . LF;
 if ($jquery_codes)
  echo wrapJavaScript(str_replace(';;',';',implode(';'.LF, $jquery_codes)) . LF, array('jquery'=>true, 'jqueryready'=>true), array('class'=>'custom_jquery_code')) . LF;
}


function admin_scripts_and_styles() {
 global 
  $admin_javascript_files, $admin_javascript_codes, $admin_js_libraries, $admin_jquery_codes, $admin_css_files, $admin_css_handles, $admin_css_codes
 ;
 $expander_button_html = '<div class=\'custom_ui_button expander_button\' ><span class=\'expander_button_icon custom_ui_button_icon\' >&nbsp;</span></div>';
 $close_button_html = '<div class=\'custom_ui_button close_button\' ><span class=\'close_button_icon custom_ui_button_icon\' >&nbsp;</span></div>';
 wp_localize_script('admin-custom', 'expander_button_html', "<div class='custom_ui_button expander_button' ><span class='expander_button_icon custom_ui_button_icon' >&nbsp;</span></div>");
 wp_localize_script('admin-custom', 'close_button_html', "<div class='custom_ui_button close_button' ><span class='close_button_icon custom_ui_button_icon' >&nbsp;</span></div>");
 
 $categry_expand_button_ids = array_map(function($c) { return $c."-tabs"; }, apply_filters('WPCF_Add_Admin_Taxonomy_Expander_Button', NULL));
 $func_make_tab_list = function($c) { return '"'.$c."-tabs".'"'; };
 enqueue_admin_jquery_code(array(
'var close_button = $("'.$close_button_html.'").css("opacity","0.5").hover(function(){$(this).css("opacity",1)}, function(){$(this).css("opacity","0.5")})
   , category_expand_button_taxes = ' . sprintf('[%s]', implode(', ', array_map($func_make_tab_list, apply_filters('WPCF_Add_Admin_Taxonomy_Expander_Button', NULL)))) . '
   , category_search_box_taxes = ' . sprintf('[%s]', implode(', ', array_map($func_make_tab_list, apply_filters('WPCF_Add_Admin_Taxonomy_Term_Search_Box', NULL)))) . '

$(".categorydiv").each(function(){
 var e = $(this);
 e.css({"z-index":99});
 var full_h = 0;
 $(".categorychecklist", e).each(function(){ if ($(this).is(":hidden")) return ; full_h=$(this).height(); });
 var min_h = $($(".categorychecklist", e).parent(0)).height();
 if (full_h < min_h) full_h = min_h;
 var tax_name = e.attr("id").replace(/^taxonomy-(.*?)$/,"$1");
 if (in_array(tax_name, category_expand_button_taxes)) {
  $(".tabs-panel", e).css({"max-height":full_h}).height(min_h);;
  var expander = $("<li class=\'category-tabs_expand custom_ui_button\' />").css({"float":"right", "margin":0, "padding":0}).
   append($("'.$expander_button_html.'").css({
  }));
  expander.toggle(
   function(){ $(".tabs-panel", e).height(full_h); $(".expander_button_icon", expander).addClass("open") },
   function(){ $(".tabs-panel", e).height(min_h); $(".expander_button_icon", expander).removeClass("open") }
  )
  $(".category-tabs", e).append(expander)
 }
 var category_search_text_id = "category_search_text_"+arguments[0];
 var categorychecklist_id = (function(e){
  var i = "";
  $(".categorychecklist", e).each(function(){ if ($(this).is(":hidden")) return; i = $(this).attr("id"); });
  return i;
 })(e);
' . '
 if (in_array(tax_name, category_search_box_taxes)) {
  var textbox = $("<input class=category_serch_text id="+category_search_text_id+" type=text size=24 />").
   liveSearch("#"+categorychecklist_id).
   on("keyup", function(){
    var b = $(".close_button", $(this).parent());
    if ($(this).val() == "") b.hide();
    else b.show();
   }).
   keydown(function(evt){ if (evt.keyCode == 27) $(this).val(""); }) ;
  var cs_close_button = close_button.clone().on("click", function(){$("#"+category_search_text_id).val("").trigger("keyup").focus()});
  e.before(textbox);
  textbox.wrap($("<label for=category_div_search_text class=category_div_search_text_label >'.__('Search').'</label>"));
  textbox.after(cs_close_button.hide());
 }
});',
 '$("#post_settings_js_libraries_box, #post_settings_css_handles_box").each(function(){
   var exp = $("'.$expander_button_html.'").addClass("meta_box_expander");
   exp.insertAfter($(".post_settings_title", this));
   var box=$(this), values=$(".MetaBox_values", box), h=values.height(), min_h="8em", w_h=h+16, w_min_h="9em";
   values.height(min_h).apply_nanoScroller();
   exp.toggle(
    function(){ values.height(h); box.height(w_h); $(".nanoscroller_wrapper", box).height(h).nanoScroller(); $(".expander_button_icon", this).addClass("open"); },
    function(){ values.height(min_h); box.height(w_min_h); $(".nanoscroller_wrapper", box).height(min_h).nanoScroller(); $(".expander_button_icon", this).removeClass("open"); }
   );
 })',
 '$("#wp-content-editor-tools, #wp-content-media-buttons").height(48);',
 '$(".wp-switch-editor").height(36)',
 '$(".wp-media-buttons .add_media span.wp-media-buttons-icon").css("background-image", "url('.WPCF_PLUGIN_URL.'/images/icn_Media_32x32.png)").width(32).height(32)',
 '$(".wp-media-buttons .add_media span.wp-media-buttons-icon:before").css("content","")',
 '$(".button.insert-media.add_media").height(42)',
 '$("#view-post-btn a").attr("target","_blank")',
 '$(".tablenav.top .actions:last").after($("<input id=posts_search_text type=text size=24 />").
  liveSearch(".wp-list-table tbody","tr").
  on("keyup", function(){
   var b = $(".close_button", $(this).parent());
   if ($(this).val() == "") b.hide();
   else b.show();
  }).
  keydown(function(evt){ if (evt.keyCode == 27) $(this).val(""); }) );
 $("#posts_search_text").wrap($("<label id=posts_search_label for=posts_search_text>'.__('Search').'</label>"));
 $("#posts_search_label").wrap($("<div class=alignleft></div>").addClass("options"));
 $("#posts_search_text").after($(close_button).on("click",function(){$("#posts_search_text").val("").trigger("keyup").focus()}).hide())'
// '$(document).on("hover",function(){$("#media-category input", this).css("width","auto").apply_nanoScroller();})'
 ) );

 if (is_admin_page()) {
  if ($admin_css_files) foreach ($admin_css_files as $c)
   echo createHTMLElement('link', array('rel'=>'stylesheet', 'href'=>$c, 'media'=>'all', 'class'=>'custom_admin_css_file'));
  if ($admin_css_codes)
   echo createHTMLElement('style', array('type'=>'text/css', 'class'=>'custom_admin_css_code'),
    createHTMLElement('_comment', LF . implode(LF, $admin_css_codes) . LF)
   ) . LF;
  if ($admin_javascript_files) foreach ($admin_javascript_files as $j)
   echo createHTMLElement('script', array('src'=>$j, 'type'=>'text/javascript', 'class'=>'custom_admin_javascript_file'), NULL) . LF;
  if ($admin_javascript_codes)
   echo wrapJavaScript(implode(';'.LF, $admin_javascript_codes), NULL, array('class'=>'custom_admin_javascript_code')) . LF;
  if ($admin_jquery_codes)
   echo wrapJavaScript(implode(';'.LF, $admin_jquery_codes) . LF, array('jquery'=>true, 'jqueryready'=>true), array('class'=>'custom_admin_jquery_code')) . LF; 
 }
 
}


function set_common_js_values() {
 enqueue_javascript_code('
 var TEMPLATE_URL = "'.TEMPLATE_URL.'/",
     TEMPLATE_URL_RELATIVE = "'.TEMPLATE_URL_RELATIVE.'/",
     WP_CONTENT_URL = "'.site_url().'/",
     STYLESHEET_URL = "'.STYLESHEET_URL.'/",
     STYLESHEET_URL_RELATIVE = "'.STYLESHEET_URL_RELATIVE.'/",
     DEVICE_MOBILE = '.(is_smartphone()?'true':'false').',
     DEVICE_NON_MOBILE = '.(is_smartphone()?'false':'true').'
'
 );
 enqueue_admin_jquery_code('window.POST_TITLE_ORIGINAL = jQuery("#title").val();');
}
 
function set_wpcf_common_js_values() {
 enqueue_javascript_code('
 var IS_ARCHIVE = '.(is_archive()?'true':'false').',
     CURRENT_POST_TYPE = "'. apply_filters('WPCF_Current_PostType', NULL, NULL, FALSE) .'"
'
 );
}
