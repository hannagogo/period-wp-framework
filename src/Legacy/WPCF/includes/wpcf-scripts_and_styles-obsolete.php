<?php
function wpcf_register_scripts_and_styles_obsolete() {
 register_file('js', 'jquery.ui.timepicker.addon', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.ui.timepicker.addon/jquery-ui-timepicker-addon.js', array('jquery-ui')); // deprecated
 register_file('css','jquery.ui.timepicker.addon', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.ui.timepicker.addon/jquery-ui-timepicker-addon.css'); // deprecated
 register_file('js', 'jquery.ui.sliderAccess', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.ui.sliderAccess.js', array('jquery-ui')); // deprecated
 // register_file('js', 'jsutil', JS_LIBRARY_URI_ROOT.'/jsutil.js'); 
 register_file('js', 'jsutil2', WPCF_JS_LIBRARY_URL_ROOT.'/jsutil2.min.js'); //deprecated
 register_file('js', 'jquery.jsutil', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.jsutil.1.8.min.js', array('jquery'));
 register_file('js', 'dommaker', WPCF_JS_LIBRARY_URL_ROOT.'/dommaker.js'); // by Dan Kogai http://blog.livedoor.jp/dankogai/archives/50642835.html
 register_file('js', 'dommaker.html5', WPCF_JS_LIBRARY_URL_ROOT.'/dommaker.html5.js'); // DOMMaker modified for HTML5
 register_file('js', 'jquery.timing', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.timing/jquery-timing.min.js', array('jquery')); 
 register_file('js', 'jquery.exResize', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.exresize.min.js', array('jquery')); 

 //// Debugging tools
 register_file('js', 'jkldumper', WPCF_JS_LIBRARY_URL_ROOT.'/jkl-dumper.js');

 //// Compatibility tools
 register_file('js', 'IE7', WPCF_JS_LIBRARY_URL_ROOT.'/IE7.js');
 register_file('js', 'IE8', WPCF_JS_LIBRARY_URL_ROOT.'/IE8.js');
 register_file('js', 'IE9', WPCF_JS_LIBRARY_URL_ROOT.'/IE9.js');

 //// Representative scripts
 register_file('js', 'jquery.corner', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.corner/jquery.corner.js', array('jquery'));
 register_file('js', 'easyzoom', WPCF_JS_LIBRARY_URL_ROOT.'/easyzoom/easyzoom.min.js', array('jquery'));
 register_file('js', 'jScrollbar', WPCF_JS_LIBRARY_URL_ROOT.'/jScrollbar_v1.0/jquery/jScrollbar.jquery.js', array('jquery-ui', 'jquery.mousewheel'));
 register_file('js', 'jScrollbar.init', WPCF_JS_LIBRARY_URL_ROOT.'/jScrollbar.init.js', array('jScrollbar'));

 register_file('css', 'jquery.fancybox2', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.fancybox-2.0.1/jquery.fancybox.min.css');
 register_file('js', 'jquery.fancybox2', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.fancybox-2.0.1/jquery.fancybox.pack.js', array('jquery.mousewheel', 'jquery.easing'));
 register_file('js', 'jquery.fancybox2.implicitly', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.fancybox2.implicitly.js', array('jquery.fancybox2'));
 register_file('js', 'jquery.treeview', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.treeview/jquery.treeview.js', array('jquery'));
 register_file('js', 'jquery.treeview.init', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.treeview.init.js', array('jquery.treeview'));
 register_file('css','jquery.treeview', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.treeview/jquery.treeview.css');
 register_file('css','jquery.treeview.async', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.treeview/jquery.treeview.async.css');
 register_file('css','jquery.treeview.sortable', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.treeview/jquery.treeview.sortable.css');
 register_file('js', 'jquery.raty', WPCF_JS_LIBRARY_URL_ROOT.'/raty/jquery.raty.min.js', array('jquery'));
 register_file('js', 'jquery.raty.apply', WPCF_JS_LIBRARY_URL_ROOT.'/raty/raty_apply.js', array('jquery.raty'));
 register_file('js', 'onepage-scroll', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.onepage-scroll.min.js', array('jquery'));
 register_file('js', 'jquery.scrollfix', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.scrollfix/scrollfix.min.js', array('jquery'));
 register_file('js', 'jquery.marquee', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.marquee-min.js', array('jquery'));
 register_file('js', 'jquery.peity', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.peity.min.js', array('jquery'));
 register_file('js', 'jquery.ripples', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.ripples.min.js', array('jquery'));
 register_file('js', 'jquery.imgr', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.imgr.min.js', array('jquery'));
 register_file('js', 'jquery.beforeafter', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.beforeafter/js/jquery.beforeafter-1.3.min.js', array('jquery'));
 register_file('js', 'reflection', WPCF_JS_LIBRARY_URL_ROOT.'/reflection/reflection.js');

 register_file('js', 'jquery.fixedtable', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.fixedtable.js', array('jquery'));
 register_file('js', 'jquery.illuminate', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.illuminate.0.7.min.js', array('jquery'));
 register_file('js', 'jquery.qrcode', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.qrcode/jquery.qrcode.min.js', array('jquery'));
 register_file('js', 'jquery.spotlight', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.spotlight/js/jquery.spotlight-1.1-min.js', array('jquery'));
 register_file('js', 'jquery.smart3d', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-smart3D/js/jquery.smart3d.js', array('jquery')); 
 register_file('js', 'jquery.easyListSplitter', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.easyListSplitter.js', array('jquery'));
 register_file('js', 'jquery.kwicks', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.kwicks/jquery.kwicks.min.js', array('jquery'));
 register_file('css', 'jquery.kwicks', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.kwicks/jquery.kwicks.min.css');
 register_file('js', 'FeedEK', WPCF_JS_LIBRARY_URL_ROOT.'/FeedEk/FeedEk.js', array('jquery'));
 register_file('js', 'fseditor', WPCF_JS_LIBRARY_URL_ROOT.'/fseditor/jquery.fseditor-min.js', array('jquery'));
 register_file('css', 'fseditor', WPCF_JS_LIBRARY_URL_ROOT.'/fseditor/fseditor.css');
 register_file('js', 'jquery.color', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.color/js/jquery.color.js', array('jquery'));
 register_file('js', 'jquery.arctext', WPCF_JS_LIBRARY_URL_ROOT.'/Arctext/js/jquery.arctext.js', array('jquery'));
 register_file('js', 'jquery.inputfocus', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-inputfocus-0.9/jquery.inputfocus-0.9.min.js', array('jquery'));

 register_file('js', 'FLAutoKerning', WPCF_JS_LIBRARY_URL_ROOT.'/FLAutoKerning/FLAutoKerning.js', array('jquery'));
 register_file('js', 'fseditor', WPCF_JS_LIBRARY_URL_ROOT.'/fseditor/jquery.fseditor-min.js', array('jquery'));
 register_file('css', 'fseditor', WPCF_JS_LIBRARY_URL_ROOT.'/fseditor/fseditor.css');

 /// MultiMedia
 register_file('js', 'jquery.jplayer', WPCF_JS_LIBRARY_URL_ROOT.'/jQuery.jPlayer.2.2.0/jquery.jplayer.min.js', array('jquery'));
 register_file('js', 'jquery.jplayer.apply_jplayer', WPCF_JS_LIBRARY_URL_ROOT.'/jQuery.jPlayer.2.2.0/apply_jplayer.js', array('dommaker', 'jsutil2', 'jquery.jplayer'));
 register_file('js', 'jquery.jplayer.playlist', WPCF_JS_LIBRARY_URL_ROOT.'/jQuery.jPlayer.2.2.0/add-ons/jplayer.playlist.min.js', array('jquery.jplayer'));
 register_file('css', 'jquery.jplayer.blue_monday', WPCF_JS_LIBRARY_URL_ROOT.'/jQuery.jPlayer/jPlayer.Blue.Monday.2.0.0/jplayer.blue.monday.css');
 register_file('js', 'jquery.media', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.media/jquery.media.min.js', array('jquery'));

 register_file('js', 'jquery.cookie', WPCF_JS_LIBRARY_URL_ROOT.'/jquery.cookie/jquery.cookie.min.js'); 
 register_file('js', 'klass', WPCF_JS_LIBRARY_URL_ROOT.'/klass/klass.min.js', array('jquery')); 
 register_file('js', 'jquery.ui.combobox', WPCF_JS_LIBRARY_URL_ROOT.'/jquery-ui.combobox.min.js', array('jquery-ui'));

 register_file('js',  'jquery.mobile-1.3.2', 'https://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.js', array('jquery')); 
 register_file('css', 'jquery.mobile-1.3.2', 'https://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.css'); 
 register_file('css', 'jquery.mobile.structure', 'https://code.jquery.com/mobile/1.3.2/jquery.mobile.structure-1.3.2.css'); 

 register_file('js', 'jquery.ui-1.9.2', '//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery-ui-1.10.3', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery-ui.1.11.1', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js', array('jquery'));
 register_file('js', 'jquery.easing.cloudflare', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js', array('jquery'));
 register_file('js', 'jquery.mousewheel.cloudflare', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.0.6/jquery.mousewheel.min.js', array('jquery'));

 /// Internet services
 register_file('js', 'googleplusone', 'https://apis.google.com/js/plusone.js');
 register_file('js', 'recaptcha', 'https://www.google.com/recaptcha/api.js');
// register_file('js', 'gmaps', JS_LIBRARY_URI_ROOT.'/gmaps/gmaps.js');
 
}