(function($){
$.extend({
 appendPluginCSS : function(src_re, path_re, css_path) {
  var css, s = $('script').each(function(){
   var src = $(this).attr('src');
   if ((src != '' && isString(src)) && src.match(new RegExp(src_re))) {
    css = src.replace(new RegExp(path_re), css_path);
   }
  });
  $('link').each(function(css){
   if ($(this).attr('href') == css) return false;
  });
  $($('head').children().get(0)).before($('<link />')
   .attr({
    'rel':  "stylesheet",
    'type': "text/css",
    'href': css,
    'media': "screen"
   })
  );
  return this;
 }
 ,
 isIE6 : function() {
  return !$.support.opacity && !$.support.style && (typeof document.documentElement.style.maxHeight === "undefined");
 }
 ,
 getBackgroundImageURL: function(e) {
  return $(e).css("background-image").replace(/url\x28(?:\x22|\x27)?(.*?)(?:\x22|\x27)?\x29/, "$1")
 }
 ,
 load_utility : function(src) {
  if (window.utility === undefined) {
   var s = document.getElementsByTagName("script");
   var f = src ? src : s[s.length - 1].src.replace(new RegExp(/[^\/]+?$/), 'javascript.utility.min.js');
   $(function(){
    var script = document.createElement("script");
    script.setAttribute("type", "text/javascript");
    script.setAttribute("src", f);
    document.getElementsByTagName("head")[0].appendChild(script);
   });
  }
 },

 firstVisit : function(option){
  if ($.cookie) {
   var url = window.location.href;
   option = $.extend({
    expires	 : 1,
    path	 : url
   }, option);
   if (!$.cookie(url)) { 
    $.cookie(url, parseInt((parseInt(new Date().getTime(), 10) + 500)/1000) ) // UNIX Epoc
    return false;
   }
   return $.cookie(url);
  }
 },

 icons: {
  'icon_arrow_left_right':'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAA5FBMVEUAAACAgICAgICAgICAgICAgICAgICAgICAgICAgIChoaHDw8Pl5OT7+/uenp6AgICEhIS6urry8fH////29va2traAgICAgICnp6fu7u6AgICAgIDQ0NC+vr75+fmAgICAgICDg4Pp6emoqKgQDg8KCAm1tbV0c3SAgICBgYHs6+uAgICAgICgoKCkpKSAgIDCwsIaGRmAgIDV1dUoJyeAgIAwLy/i4eGcnJzg39/Qz886Ojq8vLydnZ1ISEiCgoLa2dmura2AgIDd3d20tLSvrq7c29uwr6/Y19eop6eXl5eAgIAIOEeHAAAAQ3RSTlMAP4fJzPwACYHq//////94////////Hpb//6gb////ewz/////////A///Bkv//5P//8b///n///////////////8PVZfE9wAAAcdJREFUeAFt089vEkEYxvHn2e1uWQy7lEJpqSHRRENCg9rEGI0XT/7NnrwYvXIhafyBCUaWlFp+NYIMM+Muw7Jb2s/OZd/5Ht8hUoxBR5zM0M3eEoSGtkUU7QQeI0iQQs+zQZHkjbvMfK7WerwNxMnEwi4VhM4mqGhrgrsCxaEJDlziHqPicrQOTv6t8rjXcj8E3Ro5hRGQwz2kfK37dK36NQxVImDN/2Kr1FNskAMLKj7OA0LDHprf+Pg6zzOGChsViciVha2His/YA9KiMCvM0mK/qnnOPlJ+HpGfHjZqmp5dQLYoSMD+nhQzyZd/csiS6757jLXFYRTgFicOrK4P45Bea4jU5WmAOOhUsFZt8zw3uMRRck7LwhGO7IuZGbQWrD5pI9EclxFZ3xvPv/HsYNlpbr69loQt4/tk8mLEZplzGJ6SNuRAiDqSib6ia71ewFD7AH5PjgIkcl8U3bfypgiDv6pu26/msTG+fvQpXpi6C0POf2DxClvLXmhWrkXch+1RsrSP93DXqrsab9e+KbDL6YRO5uE8lT6ypvbX9OHEvFJjkibT4EKFO4+3NuA78RkNXOCN81Ef95EGCUHyPT7cfv7/ATZ+vW1tqKTEAAAAAElFTkSuQmCC', // 32px 32px angular arrow with circle
  'icon_arrow_top_bottom':'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAA4VBMVEUAAACAgICAgICAgICAgICAgICAgICAgICAgICAgIChoaHDw8Pl5OT8/Pyenp6AgICEhIS6urr19fW3t7eAgICAgICmpqbv7u6AgICAgIDQ0NC+vr75+fmAgICAgICDg4Pp6en39/e2traAgICBgYGAgICAgICjo6Py8fHe3d3c29uAgIDCwsKampoLCgpmZWXs6+tWVlaAgIDY19cYFxcjIiLV1dWAgIDi4eEsKyvg39/Qz886Ojq8vLytra2cnJyCgoLa2dm9vb2AgICdnJyurq6vrq6dnZ2np6eWlpaAgICKMXsiAAAARHRSTlMAP4fJzPwACYHq//////94/////x6W//+oG////3sM/////wP/Bkv/////k////////8b/////+f//////////////DwdBF3IAAAHHSURBVHgBddPLbxJRFMfx32+Ay1x5Ky0FNIYo9VGN2ia6MnHlH+3KxJ2JEqNGrbWJ2hbG1LQD4TUPrvcGBkY6/cxdHb4Jm3OIFRpQWiY2FPFfCUJBpXwdrQWSpI9IhqEax4MiyRHiriil+svArw0srJsVnMwiuKasAS4qzPh3HpQEkcAtei5MUJsKJPOyDii2SCKZUqpHYTWUi0uUT2a8xaCPyxSV4DbPKzAmUiFijezoT7jDANrZJsHePLUZoncVZrqhuMsjGDc8gO4YgKyEQHZwBu1mQJnKQatnpgIe2a2fV1KeALs+tGHIZ04ZRpiF5vOgnYL2S1Shndd0AAOnLRuaBwHtp8hPYNQoH/WiIo/Ij9xIwmh0uCs/oHpqHlr5FELz9oUvzQjV5pgbdzuItEowrN+lA8w9+crt+vTTg+gbpnOAdVi1FwPsddlucoylwyYq+1UbC1IdU1hPA6yc0C02EEm/nVE8D0cDJCs4t9+YhblzjGTNb8585R4fEQmud9xoadtpXBR8D/rLtX8YYF36o5OJHc79YII4O/9+dTiGLN8brBK78GXmrB3v1h++GL7DDj5jL/dabfawCiI+yZd49f/5/wNIpatTzHUtdQAAAABJRU5ErkJggg==' // 32px 32px angular arrow with circle
 }
 ,
 throttle : function( delay, no_trailing, callback, debounce_mode ) {
  // e.g. $(window).resize($.throttle(delay=100, function(){})); Triggers the Function EVERY {delay} milisecs.
  var timeout_id,
    last_exec = 0;
  if ( typeof no_trailing !== 'boolean' ) {
    debounce_mode = callback;
    callback = no_trailing;
    no_trailing = undefined;
  }
  ;
  var wrapper = function() {
   var that = this,
       elapsed = +new Date() - last_exec,
       args = arguments;
   var exec = function() {
    last_exec = +new Date();
    callback.apply( that, args );
   }
   ;
   var clear = function() { timeout_id = undefined }
   
   if ( debounce_mode && !timeout_id ) { exec() }
   timeout_id && clearTimeout( timeout_id );
   if ( debounce_mode === undefined && elapsed > delay ) {
    exec()
   }
   else if ( no_trailing !== true ) {
    timeout_id = setTimeout( debounce_mode ? clear : exec, debounce_mode === undefined ? delay - elapsed : delay );
   }
  }
  ;
  if ( $.guid ) { wrapper.guid = callback.guid = callback.guid || $.guid++; }
  return wrapper;
 },

 debounce : function( delay, at_begin, callback ) {
  // e.g. $(window).resize($.debounce(delay=100, function(){})); Triggers the Function {delay} milisecs AFTER the latest event triggered.
  return callback === undefined
    ? $.throttle( delay, at_begin, false )
    : $.throttle( delay, callback, at_begin !== false );
 }
 ,
 appendUnit: window.appendUnit || new Function,
 removeUnit: window.removeUnit || new Function,
 getNumericProperties : function(e,p,r,rnd){
  e = $(e);
  r = (r === undefined)? 1 : parseFloat(r);
  var css_orig={}
  , properties = p ? p : new Array(
     "font-size", "letter-spacing", "line-height",
     "padding-top-width", "padding-right-width", "padding-bottom-width", "padding-left-width",
     "border-top-width", "border-right-width", "border-bottom-width", "border-left-width",
     "width", "height"
    );
  for (var p in properties) {
   if (undefined === e.css(properties[p])) continue
   if (p == "line-height" || p == "letter-spacing") {
    var f = e.css("font-size"), l = e.css(p);
    e.css("line-height", parseFloat(removeUnit(l)/removeUnit(f)))
   }
   var i = parseFloat(e.css(properties[p]))*r; if (rnd) i = Math.round(i)
   css_orig[properties[p]] = appendUnit(i)
  }
  return css_orig
 },
 createKeyEvent:function(keycode) {
  var evt = $.Event('keydown'); evt.keyCode = keycode; return evt;
 },
 storedElement:function(selector) {
  if ( undefined === selector ) { return undefined }
  if ( undefined !== window.jqueryUtility.elements[selector] ) { return window.jqueryUtility.elements[selector] }
  return undefined
 },
 getFormElementValueByName: function(n) {
  n = n.replace(/(\x5b)/, '\\$1').replace(/(\x5d)/, '\\$1')
  var name_selector = "[name=\x22"+n+"\x22]"
    , el = $(name_selector)
    , e0 = $(el.get(0))
  if (e0.get(0) == undefined) { return false }
  var tag = e0.get(0).tagName
    , attr_type = e0.attr("type")
  if (
   (tag == "INPUT" && $.inArray(attr_type, ["text","email","number","tel","search", "url", "password", "datetime", "date", "month", "week", "time", "datetime-local", "range", "color", "file", "submit", "image", "reset", "button"]) != -1)
   ||
   (tag == "SELECT")
  ) {
   return e0.val()
  }
  if ( attr_type == "checkbox" ) { 
   var va = []
   el.filter(":checked").each(function(){ va.push($(this).val()) })
   return va.length ? va : undefined
  }
  if ( attr_type == "radio" ) {
   var v = undefined
   return el.filter(":checked").val()
  }
  return false
 }


}) 

$.load_utility()

if ( undefined === window.jqueryUtility ) { window.jqueryUtility = new Object() }
if ( undefined === window.jqueryUtility.elements ) { window.jqueryUtility.elements = new Object() }

/** please use $.debounce instead
$.fn.extend({
 "delayResize" : function(f,delay,args,target) {
  if (undefined == target) target = $(window);
  return f ?
   $(window).bind("resize", (function(fn,d,a,t) {
    var timeout; 
    return function() {
     if (timeout) clearTimeout(timeout);
     timeout = setTimeout(function(){ fn.apply(t, arguments); timeout = null }, d);
    }
   })(f,delay,args,target))
  :
   target.trigger("delayResize")
 }
});

*/

$.fn.prependEventHandler = function(type, fn) {
 return this.each(function() {
  var funcs = ($.data(this, "events") || {})[type];
  var copyOfFuncs = new Array();
  for (var i in funcs) {
   var func = funcs[i];
   if (typeof func !== "function") { // for jQuery1.4.3
    func = func.handler;
   } else { // for jQuery1.3.2
    func.guid = null;
   }
   copyOfFuncs.push(func);
  }
  var self = $(this).unbind(type).bind(type, fn);
  for (var i = 0, l = copyOfFuncs.length; i < l; i++) {
   self.bind(type, copyOfFuncs[i]);
  }
 })
};

$.fn.isVisible = function() {
 return $.expr.filters.visible(this[0]);
};

$.fn.isHidden = function() {
 return this.is(':hidden');
}


$.fn.outerSize = function(xory) {
 var x = this.outerWidth(true)
   , y = this.outerHeight(true)
 if (xory == 'x') return x;
 if (xory == 'y') return y;
 return [x,y]
}


$.fn.captionOverlay = function (options) {
 options = $.extend({
  'min_opacity':0, // default opacity of caption
  'max_opacity'	: 0.7,
  'background-color' : 'black',
  'color'	 : 'white',
  'left'	 : 0,
  'bottom'	 : 0,
  'fadeIn'	 :500,
  'fadeOut'	 :500,
  'selector' : '.caption',
  'attr'	 : false, // or 'title', 'alt', etc.
  'caption_style' : { 'overflow' : 'hidden', 'font-size' : '85%' }
 }, options);
 
 var is_wrapper = this.find('img').length
   , o = $(this)
   , img = o.find('img')

 if (!is_wrapper) {
  var wrapper = $('<div class="caption_wrapper" />');
  if (o.parent().get(0).tagName == 'A') $(o.parent().get(0)).wrap(wrapper);
  else o.wrap(wrapper);
  options['selector'] = this.attr('alt') ? 'alt' : 'title';
  o = wrapper;
 }
 
 if (options['selector'] == 'title' || options['selector'] == 'alt') {
  var caption = $('<p class="caption">').text(img.attr(options['selector']));
  caption.wrap(o);
 }
 
 o.children(options['selector']).each(function(){
  var c = $(this)
    , className = c.attr('class')
    , imageWidth = $(c.parent().children('img').get(0)).width()
    , caption_width = o.width() - (c.outerSize('x') - c.width())
  c.css( {
   'opacity'	: options['min_opacity'],
   'width'		: caption_width,
   'display'	: 'block',
   'position'	: 'absolute',
   'bottom'		: options['bottom'] + c.css('padding-bottom'),
   'left'		: options['left'],
   'background-color': options['background-color'],
   'color'		: options['color']
  } );
  if (typeof({}) == typeof(options['caption_style'])) c.css(options['caption_style']);
  
  $(c.parent().get(0)).
   css({ 'overflow': 'hidden' }) .
   hover(
    function(){ c.stop().fadeTo(options['fadeIn'], options['max_opacity']); },
    function(){ c.stop().fadeTo(options['fadeOut'], options['min_opacity']); }
   )
 });
 return this;
}



$.fn.flowList = function(options) {
 var images = {
  right:$.icons['icon_arrow_left_right'],
  down:$.icons['icon_arrow_top_bottom']
 }
 , dir = $.extend({
  'direction':'down'
 }, options);
  
 options = $.extend({
  'image'		: images[dir['direction']],
  'position'	: 'center',
  'width'		: 35,
  'direction'	: dir['direction'],
  'height'		: 35,
  'keep_padding': true,
  'target'		: 'children' //, or "elements" if target is selected elements
 }, options);
 
 var padding_orig
   , target = (options["target"] == "children") ? this.children() : this
   , number = target.length;
 target.each(function(){
  if (this.nodeName.toString().match(/li/i)) $(this).css('list-style','none');
  if (arguments[0] == number - 1) return false;
  if (options['direction'] == 'down') {
   padding_orig = removeUnit($(this).css('padding-bottom'),'px');
   $(this).css({
    'background-image' : 'url("' + options['image'] + '")',
    'background-position' : options['position'] + ' bottom',
    'background-repeat' : ' no-repeat',
    'padding-bottom' : (options['keep_padding']?padding_orig:0) + options['height']
   });
  }
  else if (options['direction'] == 'right') {
   padding_orig = removeUnit($(this).css('padding-right'),'px');
   $(this).css({
    'background-image' : 'url(' + options['image'] + ')',
    'background-position' : ' right ' + options['position'],
    'background-repeat' : ' no-repeat',
    'padding-right' : (options['keep_padding']?padding_orig:0) + options['width'],
	'float': 'left'
   });
  }
 });
 return this;
}



$.fn.centerFloatedList = function () {
// USAGE: $('#id_of_ul').centerFloatedList();
 var e = this, h = e.outerHeight() - e.height(), div, m = 0;
// e.clearfix();
 e.children().each(function(){ h = h < $(this).outerHeight() ? h+$(this).outerHeight() : h })
 div = $('<div />').css({ 'position':'relative', 'overflow':'hidden', height:h })
 e.appendTo(
  div.insertBefore(e)).css({
   'position': 'relative',
   'left':'50%',
   'float':'left',
   'overflow': 'visible'
  }).children().css({
	'position': 'relative',
   'left': '-50%',
   'float': 'left',
   'list-style': 'none'
  });
 return e;
};
$.fn.centerFloatedItems = function () {
 return this.centerFloatedList();
};



$.fn.sameWidth = function(options) {
 var w = 0, rest_width = 0, e = this;
 options = $.extend({
  adjustToParent: true,
  group: 'children'
 }, options);

 var parseWidth = function() {
  var o = $(this);
  rest_width += 
   removeUnit(o.css('border-left-width'),'px') +
   removeUnit(o.css('border-right-width'),'px') +
   removeUnit(o.css('padding-left'),'px') +
   removeUnit(o.css('padding-right'),'px')
  ;
  if (o.width() > w) { w = o.width(); }
 }
 
 if (e.children().length && options['group'] == 'children') {
  e.children().each(parseWidth);
  if (options['adjustToParent']) {
   e.children().width(Math.floor(e.parent().width() - rest_width) / e.children().length);
  }
  e.children().width(w);
 }
 else {
  e.each(parseWidth);
  e.each(function(){ $(e).width(w); });
 }
 return e;
}

$.fn.sameHeight = function(options){
 options = $.extend({
  columns	 : 0,
  clear		 : 0,
  height	 : 'minHeight',
  reset_style: '',
  descend	 : function descend (a,b){ return b-a; }
 },options || {});

 var self = $(this)
   , n = 0
   , hList = new Array()
   , hListLine = new Array()
 ;
 hListLine[n] = 0;

 self.each(function(i){ // getting heights
  var e = $(this);
  if (options.reset_style == 'reset') e.removeAttr('style');
  var h = e.height();
  hList[i] = h;
  if (options.columns > 1) {
   if (h > hListLine[n]) hListLine[n] = h;
   if ( (i > 0) && (((i+1) % options.columns) == 0) ) {
    n++;
    hListLine[n] = 0;
   };
  }
 });

 hList = hList.sort(options.descend); // sorting heights descendent
 
 // 高さの最大値を要素に適用
 if (options.columns > 1) {
  for (var j=0; j<hListLine.length; j++) {
   for (var k=0; k<options.columns; k++) {
    $.support.boxModel ?
     self.eq(j*options.columns+k).css(options.height,hListLine[j])
	 :
	 self.eq(j*options.columns+k).height(hListLine[j])
	;
	if (k == 0 && options.clear != 0) self.eq(j*options.columns+k).css('clear','both');
   }
  }
 }
 else {
  $.support.boxModel ? self.css(options.height,hList[0]) : self.height(hList[0]);
 }

 return self;
};
$.fn.autoHeight = $.fn.sameHeight;

$.fn.storeElement = function(e) {
 if ("destroy" === e) {
  delete window.jqueryUtility.elements[$(this).selector]
  return $(this)
 }
 if (undefined === e) e = $(this)
 window.jqueryUtility.elements[e.selector] = e.clone()
 return e
}

$.fn.slideContent = function(options){
 var e = $(this)
 if ( isString(options) && options === "destroy" ) {
  var e_orig = $.storedElement(e.selector)
  if (e_orig) {
   e_orig = e_orig.clone()
   e.after(e_orig).remove()
   e = e_orig
   e.storeElement("destroy")
  }
  return e
 }
 e.storeElement();
 options = $.extend({
  'width'		 : e.width(),
  'height'		 : $(e.children().get(0)).height(),
  'adjustHeight' : false,
  'adjustWidth'	 : false,
  'duration'	 : 1000,
  'speed'		 : 500,
  'dissolve'	 : true,
  'direction'	 : 'none', // 'left' | 'right' | 'up' | 'down'
  'control'		 : true,
  'jump_control' : true,
  'hidecontrol'	 : true,
  'overlap'		 : true,
  'circulate'	 : true,
  'ended'		 : undefined,
  'easing'		 : 'swing',
  'interval'	 : 0,
  'auto'		 : true,
  'control_css'	 : { width: 32, height: 32, border: '1px solid red' },
  'start'		 : 0,
  'startedFlag'	 : 'slideContent_started'
 }, options);
 
 options['interval'] = options['overlap'] ? 0 : options['interval'];
 options['motionTime'] = options['overlap'] ? options['speed'] : options['speed'] * 2 + options['interval'];
 options['cycle'] = options['duration'] + options['motionTime'];
 options['direction_xy'] = 
  (options['direction'] == 'left' || options['direction'] == 'right')? 'x' : 
   (options['direction'] == 'up' || options['direction'] == 'down')? 'y' : 'none';
 options['maxWidth'] = 0;
 options['maxHeight'] = 0;
 if (options['auto'] === false) options['control'] = true;
 
 var isList = (e.children().get(0) && e.children().get(0).nodeName.toLowerCase() == 'li') ? true : false,
  c = $((isList ? '<ul />' : '<div />')).attr('class', e.attr('id') + '_slide_container').css({ 'display':'block' }),
  o = e.css({ 'display':'block' }),
  n = c.children().length,
  waitForMove = undefined,
  control_box,
  turns = 0
 ;
 
 options['start'] = parseInt(options['start']);
 var o_length = o.children().length;
 options['start'] = (options['start'] >= o_length) ? (options['start'] + 1) % o_length : options['start'];
 
 var Moving = false;
 var inMotion = function(torf) {
  if (torf === undefined) return Moving;
  Moving = (torf)? true : false;
  return Moving;
 };
 
 e.children().each(function(){
  var o = $(this);
  o.css({ 'display':'block' });
  options['maxHeight'] = compareNumber(options['height'], compareNumber(o.height(), options['maxHeight'], 'gt'), 'lt');
  options['maxWidth'] = compareNumber(options['width'], compareNumber(o.width(), options['maxWidth'], 'gt'), 'lt');
 });

 var animate_options =  { 'duration': options['speed'], 'easing': options['easing'] };

 e.children().appendTo((isList ? c.appendTo($('<li />').css({ 'display':'block' }).appendTo(e)) : c.appendTo(e)));

 // Control settings
 var setControl = function(){
  options['control_css'] = $.extend((function(){
   if (options['direction_xy'] == 'x') {}
  })(), options['control_css']);
  
  if (options['control_css']['opacity'] === undefined) {
   if (options['control_image'] !== undefined) options['control_css']['opacity'] = 1;
   else options['control_css']['opacity'] = 0.7;
  }
  
  control_box =
   $('<div />').attr({ 'id': o.attr('id') + '_control_box' }).appendTo((isList ? $('<li />').css({ 'display':'block' }).appendTo(o) : o)).
    css({ 'position': 'absolute', 'bottom': 0, 'right': 0 }).
    width((options['direction_xy'] == 'x' || options['direction_xy'] == 'none')? options['maxWidth'] : options['control_css']['width']).
    height((options['direction_xy'] == 'y')? options['maxHeight'] : options['control_css']['height']);

  if (options['hidecontrol']) {
   control_box.
    animate({ 'opacity': 0 },  { 'duration': options['speed'] }).
    hover(
     function(){ var o = $(this); if (o.css('opacity') == 0) o.animate({ 'opacity': options['control_css']['opacity']}, { 'duration': options['speed'] / 2 }); },
     function(){ $(this).animate({ 'opacity': 0 }, { 'duration': options['speed'] / 2 }); });
  }
  else control_box.css({ 'opacity': options['control_css']['opacity'] });
  
  var default_control_css = {
   'border': appendUnit(options['control_css']['width']/2,'px') + ' solid black',
   'border-radius': options['control_css']['width'] / 2
  };
  var control_tmp = $('<a />').
   attr({ 'href': '#', 'class' : o.attr('id') + '_control'}).
   css({
    'position': 'absolute',
	'display': 'block',
	'height': options['control_css']['height'],
	'width': options['control_css']['width'],
	'text-decoration': 'none'
   }).
   css(options['control_css']);
    
  var control_prev = control_tmp.clone().
   attr({ 'id': o.attr('id') + '_control_prev' }).
   appendTo(control_box).
   bind('click', function(){ if (!inMotion()) { Move('backward'); clearTimeout(waitForMove); return false; } }).
   css({ 'left': 0, 'top': 0 });
  var control_next = control_tmp.clone().
   attr({ 'id': o.attr('id') + '_control_next' }).
   appendTo(control_box).
   bind('click', function(){ if (!inMotion()) { Move('forward'); clearTimeout(waitForMove); return false; } }).
   css({ 'right': 0, 'bottom': 0 });

  var default_control_content = $('<span />').css({
   'color': 'white',
   'position': 'absolute',
   'top': options['control_css']['height'] * -0.3 ,
   'left': options['control_css']['width'] / -4,
   'display': 'block'
  });

  if (options['control_image'] && options['control_image']['prev'] )
   control_prev.css('background', 'url(' + options['control_image']['prev'] + ') left top no-repeat');
  else
   control_prev.
    css(default_control_css).
    append(
     default_control_content.clone().text((options['direction_xy'] == 'x' || options['direction_xy'] == 'none')? '◀' : '▲')
    ).css({ width: 0, height: 0 });

  if (options['control_image'] && options['control_image']['next'])
   control_next.css('background', 'url(' + options['control_image']['next'] + ') left top no-repeat');
  else
   control_next.
    css(default_control_css).
    append(
     default_control_content.clone().text((options['direction_xy'] == 'x' || options['direction_xy'] == 'none')? '▶' : '▼')
    ).css({ width: 0, height: 0 });
 } /// end of Control settings


 var getIndex = function(idx, pos) {
  n = c.children().length;
  if (n == 1 || n <= 0) return 0;
  switch (pos) {
   case 'prev' : 
    if (idx == 0) return n - 1;
	else return idx - 1;
   case 'next' :
    if (idx == n - 1) return 0;
	else return idx + 1;
  }
 }


 var setContent = function() {
  c.children().each(function(){
   var child = $(this)
   child.width(compareNumber(child.width(), options['maxWidth'], 'lt'));
   if (arguments[0] == found - 1) { // case current
    child.css(
     (function(){
      var css = { 'left': 0, 'top': 0 };
      return css;
     })()
    );
    if (child.isHidden()) child.show();
   }
   else {
    if (options['direction_xy'] == 'x') { // horizontal
     if (arguments[0] == getIndex(found - 1, 'prev')) child.css({ // case previous
       left: removeUnit(c.outerSize('x'),'px').gt(child.outerSize('x')) * -1,
       top: 0
      });
     else if (arguments[0] == getIndex(found - 1, 'next')) child.css({ // case next
      left: (options['adjustWidth']) ? $(c.children().get(getIndex(arguments[0], 'prev'))).outerSize('x') : c.outerSize('x'),
      top: 0 });
    }
    else if (options['direction_xy'] == 'y') { // vertical
     if (arguments[0] == getIndex(found - 1, 'prev')) child.css({ // case previous
      left: 0, 
      top: ((options['adjustHeight'])? removeUnit(child.outerSize('y'),'px') * -1 : removeUnit(c.outerSize('y'),'px') * -1) });
     else if (arguments[0] == getIndex(found - 1, 'next')) child.css({ // case next
      left: 0,
      top: ((options['adjustHeight'])? $(c.children().get(getIndex(arguments[0], 'prev'))).outerSize('y') : c.outerSize('y')) });
    }
	else child.css({ opacity : 0 }); // dissolve, no motion
   }
  });
 }

 if (options['control']) setControl();
 
 c.children().each(function(){
  var o = $(this).css({'position':'absolute'});
  if (options['dissolve']) o.css({ 'opacity' : 0 });
  if (options['direction'] == 'none') o.css({ 'top' : 0, 'left': 0 });
  else o.css({ top: options['maxHeight'], left: options['maxWidth'] * -1 });
  o.hide();
 });
///
 var current = $(c.children().get(options['start'])).css({ top: 0, left: 0 });
 var found = options['start']+1;
 c.height((options['adjustHeight'])? current.outerSize('y') : options['height']).
   width((options['adjustWidth'])? current.outerSize('x') : options['width']);

 setContent()
 if (options['dissolve']) current.animate({ 'opacity': 1 }, animate_options);

 e.
  css({
   height: (options['adjustHeight'])? options['maxHeight'] : options['height'],
   width: (options['adjustWidth'])? options['maxWidth'] : options['width'],
   position:'relative',
   overflow:'hidden'
  })

 // Motion core Func.
 var Move = function(dir) {

  inMotion(true);; setTimeout(function(){ inMotion(false) }, options['motionTime']);

  var next = $(c.children().get(getIndex(found - 1, 'next'))); next.isHidden() && next.show();
  var prev = $(c.children().get(getIndex(found - 1, 'prev'))); prev.isHidden() && prev.show();   

  var dirs = { 'forward' : 'forward', 'backward' : 'backward' };
  dir = dirs[dir] ? dir : 'forward';
  
  var m = { 'current': {}, 'appearing': {}, 'disappearing': {} } // animation params
    , appearing = (
   ((options['direction'] == 'left' || options['direction'] == 'up' || options['direction'] == 'none') && dir == 'forward') ||
   ((options['direction'] == 'right' || options['direction'] == 'down') && dir == 'backward')
  )? next : prev
    , disappearing = (
   ((options['direction'] == 'left' || options['direction'] == 'up' || options['direction'] == 'none') && dir == 'backward') ||
   ((options['direction'] == 'right' || options['direction'] == 'down') && dir == 'forward')
  )? prev : next;
  
  if (options['direction'] != 'none') {
   disappearing.hide();
   m['appearing']['left'] = 0;
   m['appearing']['top'] = 0;
  }
  if (options['direction_xy'] == 'x' || options['direction_xy'] == 'y')
   var moveToHome = setTimeout(function(){ disappearing.css({'top':options['maxHeight'], 'left':options['maxWidth']*-1}) }, options['motionTime']);

  if (options['dissolve']) {
   m['current']['opacity'] = 0;
   m['appearing']['opacity'] = 1;
  }
  if ( (options['direction'] == 'left') && (dir == 'forward') || (options['direction'] == 'right') && (dir == 'backward') )
   m['current']['left'] =
    (options['adjustWidth']) ? removeUnit(current.outerSize('x'), 'px') * -1 : removeUnit(c.outerSize('x'), 'px') * -1;

  if ( (options['direction'] == 'left') && (dir == 'backward') || (options['direction'] == 'right') && (dir == 'forward') )
   m['current']['left'] = (options['adjustWidth']) ? current.outerSize('x') : c.outerSize('x');

  if ( (options['direction'] == 'up') && (dir == 'forward') || (options['direction'] == 'down') && (dir == 'backward') ) 
   m['current']['top'] = 
    (options['adjustHeight']) ? removeUnit(current.outerSize('y'), 'px') * -1 : removeUnit(c.outerSize('y'), 'px') * -1;

  if ( (options['direction'] == 'up') && (dir == 'backward') || (options['direction'] == 'down') && (dir == 'forward') )
   m['current']['top'] =
    (options['adjustHeight']) ? removeUnit(current.outerSize('y'),'px') : c.outerSize('y');

  current.animate(m['current'], animate_options);
  if (options['direction'] != 'none') setTimeout(function(){ current.hide() }, options['speed']);

  if (!options['dissolve']) {
   setTimeout(function(){ current.hide() }, options['speed']);
   setTimeout(function(){ appearing.show() }, options['overlap'] ? 0 : options['motionTime'] - options['speed']);
  }

  setTimeout(function(){
   appearing.show().
    animate(m['appearing'], animate_options);
  }, (options['overlap']) ? 0 : options['motionTime'] - options['speed'] );

  if (options['adjustHeight'] || options['adjustWidth'])
   setTimeout(function(){
    o.animate(
     (function(){
      var ao = {};
      if (options['adjustHeight']) ao['height'] = appearing.outerSize('y');//appearing.height();
      if (options['adjustWidth'])  ao['width'] = appearing.outerSize('x');//appearing.width();
      return ao;
     })(),
     { 'duration': options['speed'] }
    );
    control_box.animate(
     (function(){
      var ao = {};
      if (options['adjustHeight'] && options['direction_xy'] == 'y') ao['height'] = appearing.outerSize('y');
      if (options['adjustWidth'] && (options['direction_xy'] == 'x' || options['direction_xy'] == 'none'))  ao['width'] = appearing.outerSize('x');
      return ao;
     })(),
     { 'duration': options['speed'] }
    );
    c.animate(
     (function(){
      var ao = {};
      if (options['adjustHeight']) ao['height'] = appearing.outerSize('y');
      if (options['adjustWidth'])  ao['width'] = appearing.outerSize('x');
      return ao;
     })(),
     { 'duration': options['speed'] }
    );
   }, ((options['overlap'])? 0 : options['speed'] + options['interval']) );

  if (
   ((options['direction'] == 'left' || options['direction'] == 'up' || options['direction'] == 'none') && dir == 'forward') ||
   ((options['direction'] == 'right' || options['direction'] == 'down' || options['direction'] == 'none') && dir == 'backward')
  ) {
   if (found == n) { found = 1 }
   else {
    ++found; if (found == n) turns++;
   }
  }
  else {
   if (found == 1) { found = n }
   else --found; if (found == 1) turns++;
  }
  current = $(c.children().get(found -1));
  setTimeout(setContent, options['cycle'] - options['duration']);
  if ( (options['circulate'] || (!options['circulate'] && turns == 0 && n >= 1 )) && options['auto'] ) {
   waitForMove = setTimeout(arguments.callee, options['cycle']);
  }
  else {
   if (typeof(options['ended']) == "function") setTimeout(options['ended'], options['duration']); 
  };
  e.addClass(options['startedFlag'])
 } /// End of Move;

 if (options['auto']) {
  setTimeout(Move, options['duration']);
 }
 return this;
}



$.fn.slideSpriteImage = function(options) {
 var e = this,
  default_img = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAWgAAACQAgMAAABYPRmGAAAADFBMVEX////Q0NDn5+fz8/M376MIAAABr0lEQVR4Xu3asUrDUBjF8ZOEKyTQrcU+QGJXlyoKSh4hQ780gQ5ZujnkEe4juBQsODiLg7M49CEsdHB10RdwU268j5ADBr7f0G5nSEK55F/8D0oppVRSP4AjlLVYUGQ3iCswREs3vwPBqHUfDQgyOBUI3lDWOY5BUEAkh0H/grabHlnCXbTddEi4jwbAJgcK9O7IT9+id6mfPqB3Yz89Ru+mfjrlTc/Qu3c/fa7Tfw6Ea018QvjPdeqnp+jdmZ++H9Qv38h202GL3gW5m3Zf/Su6abND/+666RPSOeTRogJBzDs9RQvamQ/ZDa4qUIRSiwVHsn3CwCmllFJKKaWUSta85FMTk88FMflMdgN7aZEh3D6zks9eSlLyiUQkN+hf2AYi0jCST2yNYFMwXiEamAVMxUk+WeGmCcln5qenlORzivmKlXwwKZByMkQkDVJOPIklZ3WZfQnSdCgrVvKJxbKST1aClXw2BUjJJ5IcpOQTCEBKPvESICUfs/z5+WYk+iCfi8iCk3wyN01JPoeJm+Ykn+vPl6/XwSWfCsAHLfkk1eD+UoXL+gn/gVJKKfULA3dpWjXVXXwAAAAASUVORK5CYII=';
  
 options = $.extend({
  image: default_img,
  slide_option: {},
  slide_number: 2,
  width:e.width(),
  height:e.height(),
  empty_content: false, // true to empty default (dummy) content inside the container
  'background-size': "100%"
 }, options);
 e.height(options['slide_option']['height'] ? options['slide_option']['height'] : options['height']);
 e.width(options['slide_option']['width'] ? options['slide_option']['width'] : options['width']);
 options['empty_content'] && e.empty();
 for (var i = 1; i <= options['slide_number']; i++)
  e.append(
   $("<div class='sprite-block' id='" + e.attr('id') + "_" + i +"'></div>").
    css({
	 background: "url("+options['image']+") 0 " + (e.height() * -1 * (i - 1)) + "px no-repeat",
	 "background-size": options['background-size'],
	 height: e.height(),
	 width: e.width()
	})
  );
 options['slide_option']['control'] = false;
 return e.slideContent(options['slide_option']);
}



/*
// Thanks to http://tico-jpn.com/464/$-smooth-scroll
$.fn.smoothScroll = function(options) {
 options = $.extend({ speed : 500, exclude: '', "exclude_class":"smoothScroll-exclude" }, options);
 // set easing
 $.easing.quart = function (x, t, b, c, d) { return -c * ((t=t/d-1)*t*t*t - 1) + b; };
 // set scroll motion to segment link (href="#***")
 $(options["exclude"]).each(function(){ $(this).addClass(options["exclude_class"]) })
 $('a[href*="#"]').bind("click", function() {
  if ($(this).hasClass(options["exclude_class"])) return true;
  if (
   new String(location.pathname).replace(/^\//,'') == this.pathname.replace(/^\//,'')
    && location.hostname == this.hostname
   ) {
   var $target = $(this.hash);
   $target = $target.length && $target || $('[name=' + this.hash.slice(1) +']');
   if ($target.length) {
    var targetOffset = $target.offset().top;
    $('html,body').animate({ scrollTop: targetOffset }, options['speed'], 'quart');
    return false;
   }
  }
 });
 return this;
};
*/
$.fn.smoothScroll = function(options) {
 options = $.extend({
  "speed" : 500,
  "easing" : "swing"
 }, options)
 $('a').each(function(){
  var href = $(this).attr("href")
  if (!isString(href)) return true
  var re = new RegExp('(#[^\x2f]+)$')
    , m = href.match(re)
  if (m) {
   $(this).click(function() {
    $('body,html').animate({scrollTop:$(m[1]).offset().top}, options["speed"], options["easing"])
    return false;
   })
  }
 })
}






$.fn.appendjQueryPluginCSS = function(src_re, path_re, css_path) {
  var s = $('script').each(function(){
  var src = $(this).attr('src');
  if (!(src === undefined) && src.match(new RegExp(src_re))) {
   css = src.replace(new RegExp(path_re), css_path);
   return false;
  }
 });
 $('link').each(function(css){
  if ($(this).attr('href') == css) return false;
 });
 $($('head').children().get(0)).before($('<link />')
  .attr({
   'rel':  "stylesheet",
   'type': "text/css",
   'href': css,
   'media': "screen"
  })
 );
 return this;
}




// Based on http://fredibach.ch/jquery-plugins/autoanchors.php
$.fn.autoPageAnchors = function(options){
 options = $.extend({
  anchor	: 'h2',		 // element to anchor
  title		: '',		 // navigation title
  numbering	: false,	 // append number count before link
  anchortype: 'text'	 // 'html' to use original elements. otherwise use text
 }, options);
 
 var mcnt = 0, e = this;
 e.each( function() {
  mcnt++;
  
  var links = $('<div class="autoanchors" />'),
   cnt = 0, o = $(this)
   ;
  links.append($(options['title'])).append($('<ul />'));
  
  o.find(options['anchor']).each( function(){
   cnt++;
   var oo = $(this), anchor_text = oo.text(),
    filteredtitle = anchor_text.replace(/[^a-zA-Z0-9\s]+/g,'').replace(/\s/g,'_'),
    numbering = ''
	;
   if (options['numbering']) { numbering = $('<span class="numbering"></span>').text(cnt); }
   
   var anchor_id = mcnt + '-' + cnt + '-' + filteredtitle;
   links.children('ul').append(
    $('<li />').append(
     $('<a />').append(
	  numbering,
	  (options['anchortype'] == 'html')? oo.html() : anchor_text
	 ).attr({ 'href' : '#' + anchor_id })
    )
   );
   oo.attr("id", anchor_id);
  });
  
  if (cnt > 0) { o.prepend(links); }
 });
 return e;
};





$.fn.scrollContent = function(options) {
 var o = this,
  isMouseOver = false;

 options = $.extend({
  'height' : o.height(),
  'width' : '100%',
  'speed' : 30 // in MS
 }, options);
 o.css({
  'overflow' : 'hidden'
 }); 
 
 o.height(options['height']);
 o.width(options['width']);
 $(o.children().get(0)).css('margin-top', options['height']);
 $(o.children().get(o.children().length - 1)).css('margin-bottom', options['height'])
 var scrollHeight = 0 + options['height'] * 2;
 o.children().each(function(){ scrollHeight += removeUnit($(o).outerHeight(false),'px') });
 o.bind('mouseenter mouseleave', function(e) { isMouseOver = (e.type == 'mouseenter') });
 
 var scroll = 0;
 setInterval(function(){
  if (isMouseOver) return;
  scroll++;
  o.scrollTop(scroll % scrollHeight);
 }, options['speed']);
}



$.fn.twcarousel = function(option){
 option = $.extend({
  fade : true,
  speed : 500,
  duration : 1200,
  easing : "swing",
  direction : "up",
  loop : true,
  overflow: "hidden"
 }, option || {});
 var e = this,
 m = 0,
 motion_directions = {
  up : "marginTop",
  down : "marginBottom",
  left : "marginLeft",
  right : "marginRight"
 },
 start = function(){
  var c1 = $(e.children().get(0))
    , m = (option["direction"]=="up") ? c1.outerHeight(false) : c1.outerWidth(false)
    , args = {};
  args[motion_directions[option["direction"]]] = m * -1 + "px"
  args["opacity"] = option["fade"]? 0:1
  c1.animate(args, option["speed"], option["easing"], reset)
 },
 reset = function(){
  var c1 = $(e.children().get(0))
  c1.appendTo(e).animate({opacity:1}, option["speed"], option["easing"]).css(motion_directions[option["direction"]],m+"px")
  m = 0;
  if (option["loop"]) setTimeout(start, option["duration"]);
 }
 e.css({ overflow: option["overflow"] })
 setTimeout(start, option["duration"])
 return e
}



$.fn.carousel = function(option){
 option = $.extend({
  fade : true,
  speed : [500],
  duration : [1200],
  easing : "swing",
  direction : "up",
  loop : true,
  overflow: "hidden",
  current_class: "appearing", //
  remain: 0, // Number of elements to leave behind the current element
  relative: false
 }, option || {})
 var e = this
   , wrap_w = wrap_h = 0
   , loop_n = 0
   , speed = option["speed"]
   , duration = option["duration"]
   , number_of_elements = e.children().length
   , wrap = $("<div />").attr({"class":"carousel_wrap"}).appendTo(e)
 if (!$.isArray(speed)) { speed = [speed] }
 if (!$.isArray(duration)) { duration = [duration] }
 
 e.children().each(function(){
  var i=$(this)
  if (i.hasClass('carousel_wrap')) return false
  i.appendTo(wrap).css({"float":"left", "position":"relative"})
  var h = i.outerHeight(true), w = i.outerWidth(true)
  wrap_w += (option["direction"]=="up" || option["direction"]=="down") ? h : w
  wrap_h = wrap_h > h ? wrap_h : h
 })
  if (option["direction"]=="up" || option["direction"]=="down") wrap.height(wrap_w) 
  else { wrap.width(wrap_w).height(wrap_h) }
 var remain = option["remain"] > wrap.children().length ? wrap.children().length -1 : parseInt(option["remain"])
   , motion_directions = {
  up : "marginTop",
  down : "marginBottom",
  left : "marginLeft",
  right : "marginRight"
 },
 margin_prop = motion_directions[option["direction"]] ? motion_directions[option["direction"]] : motion_directions['left'],
 start = function(){
  var current
  loop_n++

  if ($("."+option["current_class"]).length) { current = $($("."+option["current_class"]).get(0)) }
  else { current = $(wrap.children().get(0)).addClass(option["current_class"]) }
  var args = {}
    , margin = 0
  for (var i = 0; i <= wrap.children().index(current); i++) {
   var e_i = $(wrap.children().get(i))
     , m_i = (option["direction"]=="up" || option["direction"]=="down") ? e_i.outerHeight(true) : e_i.outerWidth(true)
   margin += m_i
  }
  args[margin_prop] = margin * -1
  wrap.animate(args, (speed[loop_n - 1] ? speed[loop_n - 1] : speed[speed.length - 1]), option["easing"], reset)
  if (loop_n == number_of_elements) { loop_n = 0 }
 },
 reset = function(){
  var current = $($("."+option["current_class"]).get(0))
  current.removeClass(option["current_class"]).next().addClass(option["current_class"])
  if (wrap.children().index(current) >= remain) {
   var wrap_m = parseFloat(wrap.css(margin_prop))
     , e_0 = $(wrap.children().get(0))
     , m_0 = (option["direction"]=="up" || option["direction"]=="down") ? e_0.outerHeight(true) : e_0.outerWidth(true)
   wrap.css(margin_prop, wrap_m + m_0)
   e_0.remove().appendTo(wrap)
  }
  if (option["loop"]) setTimeout(start, (duration[loop_n] ? duration[loop_n] : duration[duration.length - 1]) );
 }
 e.css({ overflow: option["overflow"] })
 setTimeout(start, duration[0])
  
 return e
}


$.fn.slideDownList = function(options) {
 options = $.extend({
  speed		 : 250,
  arrow		 : $('<span class="slideDownList_arrow">&raquo;</span>'),
  autoStyle	 : true,
  childOffset: false,
  zIndex	 : 1000,
  direction	 : 'horizontal',
  width		 : 180,
  height	 : 36,
  trigger	 : 'hover',
  hideDelay	 : 500
 }, options || {});
 
 var self = $(this);
 
 $('li:has(ul) > a', self).append(options['arrow']);
 var horizontal = (options['direction'] == 'horizontal')? true : false;
 var vertical = !horizontal;
 self.css((horizontal ? { height: options['height'] } : { width: options['width'] } ));

 if (options['autoStyle']) {
  self.parent().first().css({ 'overflow': 'visible' });
  self.css({ 'display': 'block', 'position': 'relative', margin: 0, padding: 0 });
  $('li',self).css({ 'display': 'block', 'list-style': 'none', 'margin': 0, 'padding': 0, 'float': (horizontal ? 'left' : 'none'), 'position': 'relative' });
  $('a',self).css({ 'display': 'block' });
  $('ul',self).
   css({ 'display': 'none', position: 'absolute', left: 0, margin: 0, padding: 0 }).
   find('li').css({ 'float': 'none' });
  $('ul' + (horizontal ? ' ul' : ''),self).css({ 'top': 0 });
 }
 
 self.each(function() {
  
  var root = $(this), zIndex = options['zIndex'];
  
  var getSubnav = function(e) {
   if ($(e).get(0).nodeName.toLowerCase() == 'li') {
    var subnav = $('> ul', e);
    return subnav.length ? subnav[0] : null;
   }
   else return e;
  }
  
  $('ul' + (horizontal ? ' ul' : ''), root).each(function(){
   var w = (options['childOffset'])? options['childOffset'] : $(this).parents('ul').width();
   $(this).css({ 'left': w, 'width': w });
  });

  var slideDownAction = { 
   show:function() { // show
    var subnav = getSubnav(this);
    if (!subnav) return;
    $.data(subnav, 'cancelHide', true);
    $(subnav).css({zIndex: zIndex++}).slideDown(options['speed']);
    if (this.nodeName.toLowerCase() == 'ul') {
     var li = (this.nodeName.toLowerCase() == 'ul') ? $(this).parents('li')[0] : this;
     $(li).addClass('hover');
     $('> a', li).addClass('hover');
    }
   },
   hide:function() { // hide
    var subnav = getSubnav(this);
    if (!subnav) return;
    $.data(subnav, 'cancelHide', false);
    setTimeout(function() {
     if (!$.data(subnav, 'cancelHide')) $(subnav).slideUp(options['speed']);
    }, options['hideDelay']);
   }
  };
  var slideDownClasses = {
   over:function() { $(this).addClass('hover'); $('> a', this).addClass('hover'); },
   out:function() { $(this).removeClass('hover'); $('> a', this).removeClass('hover'); }
  }
  
  $('li', this).hover(slideDownClasses.over, slideDownClasses.out);
  
  if (options['trigger'] == 'hover') {
   if ($(document).hoverIntent != undefined) {
    $('ul, li', this).hoverIntent({over:slideDownAction.show, out:slideDownAction.hide});
   }
   else {
    $('ul, li', this).hover(slideDownAction.show, slideDownAction.hide);
   }
   $('li', this).hover(slideDownClasses.over, slideDownClasses.out);
  }
  else {
   $('li', this).toggle(slideDownAction.show, slideDownAction.hide);
  }
 });
 return this;
};

$.fn.make_drawer = function(option) {
 var o = $.extend({
  "image"    :"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPAAAAB4BAMAAAA+mGIUAAAAFVBMVEUAAAD///////////////////////9Iz20EAAAABnRSTlMAzxAw77/zqYuqAAABDklEQVR4Xu3ZMW7CQBgFYSsRHCAFNbkBRXICpNRIXIBI0X//I1BOSbXvycrMBdYfMvbu720nmZmZmZmZ2XFe9vjvCyv+PsOgt/tq8fvPBwz6/Ltsa/uaX8iAT3PdlgR4BjLgmcXkw8xABjwztzVUloAMmMtZFGsQVxMmA86SAYfJgLNkwGEy4CwZcJgMOEsGHCYDzpIBh8mAs2TAYTLgNBlwmAw4TAYcJgOObOghAw4sDBlwRAwZcHXh/k/dv7n6f6f+A6T/yOy/JPqvxf5GoL/16W/2+ttbwJB7R5jaoa13TK0dzHujiNrwpTduqg3YaiPF2hC1NjZuDcp7nwb6H0P87rR/8f4zMzMzMzOzJ9oxk9BmqKq4AAAAAElFTkSuQmCC",
  "duration"   : 500,
  "delay"      : 100,
  "easing"     : "swing",
  "side"       : "left",
  "breakpoint" : 640,
  "button_css" : {},
  "wrapper_class" : "drawer_wrapper",
  "class_drawer_open":"drawer_open",
  "class_drawer_close":"drawer_close",
  "overflow"   : "hidden",
  "width"      : 192,
  "height"     : 192,
  "top"        : 0,
  "fullscreen" : true,
  "wrapped_class" : "wrapped"
 }, option)
   , s = o["side"]
   , b_css_base = {
  "width":36, "height":36, "top":0,
  "position":"absolute",
  "background-image" : "url("+o["image"]+")",
  "background-size"  : "200%",
  "background-repeat": "no-repeat",
  "background-color":"gray",
  "background-position":"0",
  "display":"none",
  "z-index": 99999,
  "cursor":"pointer",
 }
 
 b_css_base[s] = 0
 var b_css = $.extend(b_css_base, o["button_css"])
   , e = $(this)
   , b = $("<div id=drawer_menu_button />").css(b_css)
   , wh = (o["side"] == "top" || o["side"]=="bottom") ? "height":"width"
   , wrapper = $("<div class="+o["wrapper_class"]+" />").css({
    wh : o[wh],
    "overflow" : o["overflow"]
   })

 b.insertBefore(e).click(function() {
   var a = {}; a[wh] = "toggle"
   wrapper.animate(
    a,
    {
     "duration":o["duration"],
     "eaasing":o["easing"],
	 "complete": function(){
      b.toggleClass(o["class_drawer_open"]).toggleClass(o["class_drawer_close"])
	  wrapper.toggleClass(o["class_drawer_open"]).toggleClass(o["class_drawer_close"])
	  if (b.hasClass(o["class_drawer_open"])) { b.css({"background-position":"100% 0"}) }
	  else { b.css({"background-position":"0"}) }
     }
    }
   )
  }
 ).after(wrapper)

 var w_css_base = {"overflow":"hidden","z-index":9999,"position":"absolute","top":b.height()}
 w_css_base[s] = 0
 var wrapper_css = $.extend(w_css_base, o)
 $(window).resize(
   function() {
    var w = $(this)
    if (w.width() < o["breakpoint"]) {
	 if (!wrapper.hasClass(o["wrapped_class"])) {
	  e.appendTo(wrapper)
	  wrapper.addClass(o["wrapped_class"])
	  e.preserve_style().width(o["width"])
	 }
     if (s == "left" || s == "right") {
      wrapper.css({"height": $(window).height()})
     }
	 wrapper.css(wrapper_css).addClass(o["class_drawer_close"]).removeClass(o["class_drawer_open"])
	 if (o["fullscreen"]) {
      if (s=="right" || s=="left") {
       wrapper.height( w.height() )
      }
      else {
       wrapper.width( w.width() )
      }
	 }
	 wrapper.hide();
	 b.css(b_css).addClass(o["class_drawer_close"]).removeClass(o["class_drawer_open"]).show()
    }
    else {
	 wrapper.removeClass(o["class_drawer_close"]+" "+o["class_drawer_open"]+" "+o["wrapped_class"])
	 e.restore_style().insertAfter(wrapper)
	 b.removeClass(o["class_drawer_close"]+" "+o["class_drawer_open"])
     b.hide()
    }
   }
 ).trigger("resize")
 return this
}

$.fn.alterSelectOptions = function(options) {
/*
// Usage:
 var select_element = "#selectlist";
 $(select_element).aleterSelectOptions({
  label			 :'#box1_label',	 // Element to display selected option (selector or object)
  list_wrapper	 :"#box1",			 // Wrap option elements with an element (e.g. div) 
  list_item		 :"#box1 .option"	 // Option elements (selector or object)
 });
// while the HTML is like this:
<select id="selectlist">
	<option value="opt1" >option 1</option>
	<option value="opt2"> option 2</option>
	<option value="opt3" >option 3</option>
</select>

<div id="box1_label">お選びください</div>
<div id="box1" >
	<div class="option" id="option1" >ああああああ</span>
	<div class="option" id="option2" >いいいいいい</span>
	<div class="option" id="option3" >うううううう</span>
</div>
*/

 options = $.extend({
  'fadeInSpeed'		: 100,
  'fadeOutSpeed'	: 100,
  'slideDownSpeed'	: 200,
  'slideUpSpeed'	: 100
 }, options);

 var e = $('option', this);
 var items = $(options['list_item']);
 var wrapper = $(options['list_wrapper']);
 var label = $(options['label']);
 if (!items) { return false; }
 
 $([wrapper,label,items]).each(function(){$(this).css({'cursor':'pointer'})});;
 
 slideAction = {
  show:function(){ wrapper.slideDown(options['slideDownSpeed']); },
  hide:function(){ wrapper.slideUp(options['slideUpSpeed']); }
 }
 slideAction.hide();
 wrapper.css({'position':'absolute', 'z-index':1000});
 items.each(function(){ 
  var i = arguments[0];
  var item = $(this);
  item.hover(function(){$(this).addClass('hover')}, function(){$(this).removeClass('hover')});
  $(this).bind('click', function(){
   var o = $(e.get(i));
   e.each(function(){$(this).removeAttr("selected");});
   o.attr("selected","selected");
   label_old = $('<div class=alterSelectOptions_label_disappear />');
   $(label.contents()).appendTo(label_old);
   label_old.appendTo(label);
   label_old.fadeOut(options['fadeOutSpeed'], function(){label.fadeIn().html(item.html());$(this).remove();});
   label.click();
  });
 });
 slideAction.hide();
 label.toggle(slideAction.show, slideAction.hide);
 return this.hide();
}

$.fn.autoCompleteComboBox = function(o){
 o = $.extend({
  "message_show_all" : "Show All Items",
  "message_no_match" : "\"%s\" didn't match any item"
 }, o)

 $.widget("custom.combobox", {
  _create: function() {
   this.wrapper = $("<span>").addClass("custom-combobox").insertAfter(this.element);
   this.element.hide();
   this._createAutocomplete();
   this._createShowAllButton();
  },
  _createAutocomplete: function() {
   var selected = this.element.children(":selected")
     , value = selected.val() ? selected.text() : ""
   ;
   this.input = $("<input>")
     .appendTo(this.wrapper).val(value).attr("title", "")
     .addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left")
     .autocomplete({ delay: 0, minLength: 0, source: $.proxy(this, "_source")})
     .tooltip({ classes: {"ui-tooltip": "ui-state-highlight"} });
   ;
   this._on(this.input, {
    autocompleteselect: function(event, ui) {
     ui.item.option.selected = true
     this._trigger("select", event, { item: ui.item.option })
    },
    autocompletechange: "_removeIfInvalid"
   });
  },
 
  _createShowAllButton: function() {
   var input = this.input
     , wasOpen = false; 
   $("<a>").attr({"tabIndex":-1, "title":o["message_show_all"]})
    .tooltip()
    .appendTo(this.wrapper)
    .button({ icons: { "primary":"ui-icon-triangle-1-s" }, "text": false })
    .removeClass("ui-corner-all")
    .addClass("custom-combobox-toggle ui-corner-right")
    .on("mousedown", function() {
      wasOpen = input.autocomplete("widget").is(":visible");
     })
    .on("click", function() {
     input.trigger("focus");
     // Close if already visible
     if (wasOpen) { return }
     // Pass empty string as value to search for, displaying all results
     input.autocomplete("search", "")
    })
   },
   _source: function(request, response) {
    var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
    response(this.element.children("option").map(function() {
     var text = $(this).text();
     if (this.value && (!request.term || matcher.test(text)))
     return { "label": text, "value": text, "option": this };
    }))
   },

  _removeIfInvalid: function(event, ui) {
   // Selected an item, nothing to do
   if (ui.item) return
   // Search for a match (case-insensitive)
   var value = this.input.val()
     , valueLowerCase = value.toLowerCase()
     , valid = false;
   this.element.children("option").each(function() {
    if ($(this).text().toLowerCase() === valueLowerCase) {
     this.selected = valid = true
     return false
    }
   })
   // Found a match, nothing to do
   if (valid) return
   // Remove invalid value
   this.input
    .val("")
	.attr("title", sprintf(o["message_no_match"], value))
    .tooltip("open");
   this.element.val("");
   this._delay(function() { this.input.tooltip("close").attr("title", "") }, 2500)
   this.input.autocomplete("instance").term = "";
  },
  _destroy: function() {
   this.wrapper.remove();
   this.element.show();
  }
 });

 var e = $(this)
 e.combobox()
 $("#toggle").on("click", function() { e.toggle() })
 e.wrap('<div class="ui-widget">')
 $("head").append($("<style id='autoCompleteSelectBox_style' class='jquery.utility_style dynamically_added_style'>").text(
 '.custom-combobox { position: relative; display: inline-block; } \
  .custom-combobox-toggle { position: absolute; top: 0; bottom: 0; margin-left: -1px; padding: 0; } \
  .custom-combobox-input { margin: 0; padding: 5px 10px; } \
 '))
 return e
}


$.fn.hoverResize = function(options) {
if (this.length > 0) {
 options = $.extend({
  resize_width: false,
  resize_height: false,
  width: false,
  height: false,
  container_height: 0,
  easing: 'swing',
  duration: 100,
  delay: 100,
  wrapper : false
 }, options);

 if (options['wrapper']) this.wrapAll(options['wrapper']);
 var single = (this.length == 1) ? true : false;
 var container = $(this.parent().get(0));
 if (!single || (single && options['wrapper'])) container.css({'overflow':'visible', 'position':'relative'});
 var dimensions = [];
 this.each(function(){
  dimensions[arguments[0]] = {
   y : $(this).offset().top - container.offset().top,
   x : $(this).offset().left - container.offset().left,
   width  : ((options['width']  === false)? $(this).width()  : options['width']),
   height : ((options['height'] === false)? $(this).height() : options['height'])
  }
 });
 var setContainerHeight = function() {
  if (!single || (single && options['wrapper']))
  return container.height(
   options['container_height'] ?
    options['container_height']
    :
    (dimensions[dimensions.length -1]['y'] - dimensions[0]['y'] + dimensions[dimensions.length -1]['height'])
  ).height();
  return container.height();
 }

 setContainerHeight();
 this.each(function(){
  var self = $(this);
  if (options['resize_width'] === false) options['resize_width'] = self.width();
  if (options['resize_height'] === false) options['resize_height'] = self.height();
  var d = dimensions[arguments[0]];
  self.css({
   'top':d['y'],
   'left':d['x'],
   'overflow':'hidden',
   'position':'absolute'
  });
  
  var f1 = function(){
   container.css({'z-index': 9999 });
   $(this).css({'z-index': 10000 }).
    animate({
     height: options['resize_height'],
     width: options['resize_width']
	}, options['duration'], options['easing'] )
  };
  var f2 = function(){ 
   container.css({'z-index': 1 });
   $(this).css({'z-index': 1}).
    animate({
     height:d['height'], width:d['width']
    }, options['duration'], options['easing'], setContainerHeight)
  };
  if ($(document).hoverIntent != undefined) self.hoverIntent({over:f1, out:f2, timeout:options['delay']});
  else self.hover(f1,f2);
 });
}
 return this;
}




$.fn.blockLink = function(){
 this.each(function(){
  var a = $(this).find("a:first");
  if (a.length == 1) $("<span class='blockLink-spacer'></span>").css({
   'position'	 : 'absolute',
   'width'		 : '100%',
   'height'		 : '100%',
   'top'		 : 0,
   'left'		 : 0,
   'z-index'	 : 10
  }).appendTo($(a));
 });
 return this;
};




$.fn.clearfix = function() {
 return this.append($('<div></div>').css({'clear':'both','width':0}));
}



$.fn.liveSearch = function (search_list,search_element,option) {
 if (!search_list) return this;
 if (!search_element) search_element = 'li';
 option = $.extend({
  "clear_on_escape" : true
 },option);
 $.extend($.expr[':'], {
  'containsi': function(elem, i, match, array) {
   return $(elem).text().toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
  }
 });

 var e = this;
 var search = function(evt) {
  if (e.val() != "") {
   if (option["clear_on_escape"] && evt.keyCode == 27) {
    e.val("");
	search();
   }
   var keywords = e.val().replace(/(?:\x20|　)+/, ' ').replace(/^\x20/,'').split(/\x20/), result = new Array();
   if (keywords.length == 1 && keywords[0] == "") return true;
   for (var i in keywords) {
    var v = keywords[i];
    $(search_element+':containsi("' + v + '")', search_list).each(function(){
     result.push($(this));
    });
   }
   $(search_element, search_list).hide();
   for (var r in result) result[r].show();
  }
  else $(search_element, search_list).show();
 }
 e.bind('keypress', search);
 e.bind('keydown', search);
 e.bind('keyup', search);
 e.bind('blur', search);
 return this;
}



$.fn.autoKana = function (options){
 options = $.extend({ kana: '' }, options);
 var kana_field = 'kana', o = this;
 if (ruby == '') return;
 var base = "";
 var set = function() {
  var v = o.val();
  if (base == v || v == "") return;
  
  var a;
  for(var i = base.length; i >= 0; i--) {
   if (v.substr(0,i) == base.substr(0,i)) {
    a = v.substr(i); break;
   }
  }
  base = v;
  $(options[kana_field]).val($(options[kana_field]).val() + a.replace(/[^ 　ぁあ-んー]/g, ""));
 }
 $(this).bind('keyup', set);
 return this;
}



$.fn.fixedTitle = function(options) {
/**
 // Based on:
 * jquery.persistentheaders.js
 * Release 0.0.1 (Mar 27, 2012)
 * http://css-tricks.com/examples/PersistantHeaders/
**/
 var defaults = {
   "slideUpSpeed"	 : 0,
   "slideDownSpeed"	 : 100,
   "isHidden"		 : true,
   "z-index"		 : 100,
   "flag"			 : "_fixedTitle_fixed",
   "addFlag"		 : true
 }
   , settings = $.extend(defaults, options)
   , clonedHeaderRow = $(settings['title'], this)
 $(this).each(function(){
  var o = $(this)
	, c = o.clone().insertBefore(o)
	, offset = c.offset()
	, outerHeight = c.outerHeight()
	, height = c.height
  c.css({"width":o.width(), "height":height, "position":"fixed", "top":0, "left":offset.left})
  $(window).scroll(function () {
   var scrollTop  = $(window).scrollTop()

   if (scrollTop > offset.top + outerHeight) {//((scrollTop > offset.top) && (scrollTop < offset.top + outerHeight)) {
    c.css({
	 "visibility": "visible", 'z-index': settings["z-index"]
    })
	if (settings["addFlag"]) {
     c.addClass(settings["flag"])
	 o.addClass(settings["flag"]+"_orig")
    }
   }
   else if ((scrollTop > offset.top)) {
//	if (settings['isHidden']) c.css("top",offset.top + outerHeight)
   }
   else {
    c.css({ "visibility": "hidden" })
	if (settings["addFlag"]) {
     c.removeClass(settings["flag"])
	 o.removeClass(settings["flag"]+"_orig")
    }
   }
  }).trigger("scroll")
 })
 return this;
}


$.fn.ceilBox = function(option){
 option = $.extend({
  "top" : 0,
  "fixed_class": "_position_fixed",
  "consider_margin": true,
  "throttle" : false
 }, option) 
 var e = $(this)
   , w = $(window)
   , e_mt = e.css("margin-top")
   , e_top = e.css("top")
   , fixed_class = option["fixed_class"]
   , e_pos = e.offset().top
   , f = function() {
    var diff = e_pos - w.scrollTop()
	if (option["consider_margin"]) { diff -= parseNumber(e_mt) } 
  if( diff < 0 ) {
	e.css("top", w.scrollTop() - e_pos + option["top"] + (option["consider_margin"]? parseNumber(e_mt) : 0 )).addClass(fixed_class)
  } else {
    e.css('top', e_top).removeClass(fixed_class)
  }
 }
 w.bind("scroll",f)
 return this
}


$.fn.foldContent = function(options) {
 var default_imgs = {
  'horizontal' : 'data:image/png;base64,iVBORw0KGgo=AAAADUlIRFIAAAAkAAAAJAgGAAAA4QCYmAAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAABTSURBVFhH7c4JCg==wCAMBEAFe/3/wdZCCg==S39QGGHZIBKnzznbOqOyrezVR81Pn9FX9Ttnvnf5Lnfk7vxzdCAgICAgICAgICAgICCgBgQEBAT0I9ANkkePBWWA6oIAAAAASUVORK5CYII=',
  'vertical' : 'data:image/png;base64,iVBORw0KGgo=AAAADUlIRFIAAAAwAAAAMAgGAAAAVwL5hwAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAABiSURBVGje7drRDcAgCEBBYqndf2Icwo+GcGxweXyhWVXReTIiVndAdgc8Cg==AAwHvAAAAL0BWwEFhgM+BQCskAIKjC5ghRRQQAEFZhewQgo=TAc4qygAAABwBfBKCXAJ8NXgzznGYAS74cRDxgAAAABJRU5ErkJggg=='
 };
 if (!options) options = {};
 var dir = (options['direction'] == 'horizontal')? 'horizontal' : 'vertical';
 options['direction'] = dir;
 var is_vertical = (dir == 'vertical');
 var align = (options['align'] == 'right')? 'right' : 'left';
 
 $(this).wrap($('<div class=foldContent_wrapper />').css('position','relative'));
 var e = $(this);

 wrapper = $(e.parent('.foldContent_wrapper').get(0));
 options = $.extend({
  'direction' : dir,
  'control' : $('<div class=foldContent_control />').css({
    'background-color' :'#ededed',
    'border'	 : '1px solid #ccc',
    'height'	 : (is_vertical ? '32px' : e.height()),
	'width'		 : (is_vertical ? 'auto' : '36px'),
    'margin'	 : '0.5em',
    'text-align' : 'center',
	'float'		 : (is_vertical ? 'none' : align),
	'padding'	 : "2px"
   }).append(
	$('<img class=foldContent_control_arrow />').attr("src", $.icons["icon_arrow_"+(is_vertical?"top_bottom":"left_right")])
   ),
  'overlay' : $("<div class=foldContent_overlay />").css({
   "background-image" : "url("+default_imgs[dir]+")",
   "background-repeat": (is_vertical?"repeat-x":"repeat-y"),
   "background-position": "100% 100%"
  }),
  'speed'		 : [500,300], // down speed (appearing), up speed (disappearing)
  'folded_width' : 0,
  'clearfix'	 : true,
  'complete_open': function(){},
  'complete_close': function(){},
  'easing'		 : 'swing',
  'class_open'	 : 'foldContent_open',
  'class_close'	 : 'foldContent_close'
 }, options);
 // Regularization
 options['control'] = $(options['control']);
 options['overlay'] = $(options['overlay']);
 var oh = e.outerHeight() + removeUnit(e.css("margin-top"), 'px') + removeUnit(e.css("margin-bottom"), 'px');
 var ow = e.width()
   + removeUnit(e.css("margin-left"),'px') + removeUnit(e.css("margin-right"), 'px')
   - (is_vertical ? 0 : removeUnit(options['control'].outerWidth(false), 'px'));

 if (typeof(options['speed']) === typeof('')) {
  var s = parseInt(options['speed'].replace(new RegExp('[^\d]'),''));
  options['speed'] = [s,s];
 }
 else if (typeof(options['speed']) === typeof(0)) {
  var s = parseInt(options['speed']);
  options['speed'] = [s,s];
 }

 options['overlay'].css({'position':'absolute','bottom':0,'right':0,'z-index':99});
 if (options['overlay'].height() == 0) options['overlay'].height(oh);
 if (options['overlay'].width()  == 0) options['overlay'].width(ow);

 wrapper.css({"overflow":"hidden",'float':(is_vertical?'none':align)}).
  height(is_vertical?options['folded_width']:oh).
  width(is_vertical?ow:options['folded_width'])
 ;
 wrapper.before($(options['control']));
 wrapper.append(options['overlay']);
 if (!is_vertical && options['clearfix']) { wrapper.after($('<div style="clear:both" />')); }

 $([wrapper,options['control']]).each(function(){ $(this).addClass(options['class_close']); });
 var toggle_control_class = function() {
  var class_open = options['class_open'];
  var class_close = options['class_close'];
  $([wrapper,options['control']]).toggleClass(class_open);
  $([wrapper,options['control']]).toggleClass(class_close);
 }
 var complete_open = function(){ toggle_control_class.call(); options['complete_open'].call(); }
 var complete_close = function(){ toggle_control_class.call(); options['complete_close'].call(); }
 var expand = function(){ wrapper.animate({height:oh, width:ow}, options['speed'][0], options['easing'], complete_open); options['overlay'].fadeOut(options['speed'][0]) };
 var fold = function(){ wrapper.animate({height:(is_vertical?options['folded_width']:oh), width:(is_vertical?ow:options['folded_width'])}, options['speed'][1], options['easing'], complete_close); options['overlay'].fadeIn(options['speed'][1]); };

 options['control'].toggle(expand, fold);
 return this;
}

$.fn.simpleTab = function(options) {
  options = $.extend({
  "tab_width"		 : "auto", // "fit"
  "tab_height"		 : "auto",
  "tab_selector"	 : "",
  "default"			 : 0,
  "fadeIn_speed"	 : 300,
  "fadeOut_speed"	 : 50,
  "class_hidden"	 : "tab_hidden",
  "class_exposed"	 : "tab_exposed",
  "class_content"	 : "tab_content",
  "class_tab"		 : "tab_tab",
  "fadeIn_complete"	 : function(){},
  "adjust_container_height" : true
 }, options);
 if (options["tab_selector"] == "") return this;

 var tabs = $(this), h = 0, full_width = tabs.outerWidth(false), position_x = 0;
 $(tabs.parent().get(0)).css("position","relative");
 tabs.css("position", "absolute");
 
 var controller = $("<div class=tab_controller />").css({
  "position": "relative",
  "width" : full_width
 });

 tabs.each(function(){
  var c = $(this), t = $($(options["tab_selector"], c).get(0)),  i = arguments[0];
  var cw_rest = c.outerWidth(false) - c.width(); 
  c.addClass(options["class_content"]+"_"+i).addClass(options["class_content"]);
  t.addClass(options["class_tab"]+"_"+i).addClass(options["class_tab"]);

  var tw = t.outerWidth(false), tw_rest = tw - t.width();
  if (parseInt(options["tab_width"])) { t.width(parseInt(options["tab_width"])); }
  else if (options["tab_width"] == "fit") {
   t.width( (full_width / tabs.length - (tw - t.width())).roundDecimal() );
  }

  var th = t.outerHeight(false), th_rest = th - t.height();
  if (h < th) h = th;

  t.bind("click", function(){
   if ($("."+options["class_content"]+"_"+i).hasClass(options["class_exposed"])) return false;
   tabs.fadeOut(options["fadeOut_speed"]);
   $("."+options["class_content"]+"_"+i).fadeIn(options["fadeIn_speed"], options["fadeIn_complete"]);
   $("."+options["class_exposed"]).toggleClass(options["class_hidden"]).toggleClass(options["class_exposed"]);
   $(c).toggleClass(options["class_hidden"]).toggleClass(options["class_exposed"]);
  });
  
  t.css({
   "position":"absolute",
   "left" : position_x
  });
  position_x += t.outerWidth(false); 
  t.appendTo(controller);
  c.addClass(options["class_hidden"]).hide().width(full_width - cw_rest);;
 });

 controller.height(h).insertBefore($(tabs).get(0));
 if (options["tab_height"] == "auto") {
  $(controller.children()).each(function(){
   var t = $(this);
   t.height(h - (t.outerHeight(false) - t.height(false)));
  });
 }
 $(tabs.get(options["default"])).fadeIn(options["fadeIn_speed"],options["fadeIn_complete"]).toggleClass(options["class_hidden"]).toggleClass(options["class_exposed"]);
 return this;
}

$.fn.clickableWrapper = function(option) {
 this.each(function(){
  var c = $(this); 
  c.bind("click", function(event){
   var checkbox = $("input", c)
     , type = checkbox.attr("type");
   if (type!="radio" && type!="checkbox") return;
   if ($(event.target).is("th")) {
    checkbox.attr("checked", (checkbox.attr("checked") == "checked" ? false : "checked") );
   }
  });
 });
 return this;
}


$.fn.labelize = function(options) {
 if (document["jquery.jsutil.labelizerItems"] === undefined) document["jquery.jsutil.labelizerItems"] = {};
 if (typeof(options) == typeof("")) {
  options = { "label": options } // treated as label text if a string is passed
 }
 
 var settings = $.extend({
  "label"		 : "<Input Text Here>",
  "initialize"	 : true,
  "leave_label"	 : false,
  "bind_order"	 : "append" // or "prepend"
 }, options)
 , obj = $(this)
 , form = $(this).parent("form").first()
 ;
 if (settings["initialize"]) obj.val(settings["label"]);
 document["jquery.jsutil.labelizerItems"][obj] = '';
 var value_holder = '';
 obj.bind( "focus", function(){ obj.val(value_holder)} ); 
 obj.bind( "blur", function() {
  obj.val(obj.val().fastTrim());
  if (obj.val() != '') value_holder = obj.val();
  else value_holder = '';
  obj.val(obj.val() != '' ? obj.val() : settings["label"]);
 } );
 if (form.length == 1) {
  var fn = function() {
   if ( (obj.val() == settings["label"]) && (settings["leave_label"] == false) ) { 
    obj.val("");
   }
  }
  if (settings["bind_order"] == "prepend") form.prependEventHandler("submit", fn);
  else form.bind("submit", fn);
 }
 return obj;
}

$.fn.framedMotion = function(option){
 if (! $.framedMotion ) {
  function framedMotion(){ 
   this.objects = new Array();
  }
  framedMotion.prototype = {
   add	 : function(obj) { this.objects.push(new _framedMotionCtl(obj)) },
   count : function(){ return this.objects.length },
   object: function(i){ if (!isNaN(i=parseInt(i))) { return this.objects[i] } }
  }

  function _framedMotionCtl(obj) {
   this.data = {}
   this.object(obj)
  }
  _framedMotionCtl.prototype = {
   param: function(key,value) {
    if (value !== null && value !== undefined) { this.data[key] = value }
    return this.data[key]
   }
  }
  $.extend(_framedMotionCtl.prototype, {
   id		: function(v) { return this.param("id", v) },
   object	: function(v) { return this.param("object", v) },
   start	: function(v) { return this.param("start", v) },
   init		: function(v) { return this.param("init", v) }
  })
  $.extend({ framedMotion: new framedMotion() });
 }

 $.framedMotion.add(this);
 var smid = $.framedMotion.count()
   , e = this, wrapper = $("<div id=framedMotion_wrapper_"+smid+" class=framedMotion_wrapper />");

 wrapper.insertAfter(e).append(e).css({
  "position"	: "relative",
  "padding"		: 0,
  "margin"		: 0,
  "opacity"		: 0
 });

 $.framedMotion.object(smid-1).init( function(){
  var width = e.width(), height = e.height()
    , opt_tmp = $.extend(true, {
       "viewport"	: [wrapper.width(), wrapper.height()],
       "fadeInSpeed": 200,
      }, option)
	, wrapper_w = opt_tmp["viewport"][0], wrapper_h = opt_tmp["viewport"][1]
  option = $.extend(true, {
   "from"		: [0,0],
   "to"			: [wrapper_w - width, wrapper_h - height],
   "viewport"	: [wrapper_w, wrapper_h],
   "duration"	: 1000,
   "easing"		: "linear",
   "loop"		: true,
   "fade"		: true,
   "fadeInSpeed": 200,
   "fadeOutSpeed":opt_tmp["fadeInSpeed"],
   "opacity"	: parseFloat(e.css("opacity")),
   "interval"	: 0,
   "step"		: null,
   "complete"	: null
  }, option);
  wrapper.width(option['viewport'][0]).height(option['viewport'][1])
  e.css({"display":"block","position":"absolute"});

  $.framedMotion.object(smid-1).start( function(){
   if (option["fade"]) {
	wrapper.animate({
	 "opacity"	: option["opacity"]
	}, {
     "duration"	: option["fadeInSpeed"]
	})
    wrapper.delay(option["duration"]-option["fadeInSpeed"]-option["fadeOutSpeed"])
     .animate({"opacity":0}, {"duration":option["fadeOutSpeed"]});
   }
   else { wrapper.css({"opacity":option["opacity"]}) }
   var css_orig = {}
   for (p in new Array(
    "font-size",
    "padding-top-width", "padding-right-width", "padding-bottom-width", "padding-left-width",
    "border-top-width", "border-right-width", "border-bottom-width", "border-left-width",
    "width", "height"
   )) {
    css_orig[p] = e.css(p)
   }

   e.css({ "top":option["from"][0], "left":option["from"][1] })
    .animate( {
     "left"		: option["to"][0],
     "top"		: option["to"][1]
    }, {
     "duration"	: option["duration"],
     "easing"	: option["easing"],
	 "step"		: option["step"],
     "complete"	: function(){
       e.css({ "top":option["from"][0], "left":option["from"][1] })
       if (option["fade"]) wrapper.stop().css({"opacity":0})
	   if (option["loop"]) setTimeout($.framedMotion.object(smid-1).start(), option["interval"])
	   if (isFunction(option["complete"])) option["complete"].call();
     }
    } )
  } )
  e = $.extend(e, { framedMotion: new Object() })
 } )

 e.bind('load', function(){
  $.framedMotion.object(smid-1).init().call();
  e.framedMotion.init = $.framedMotion.object(smid-1).init();
  $.framedMotion.object(smid-1).start().call();
  e.framedMotion.start = $.framedMotion.object(smid-1).start();
 });
 return e;
}

$.fn.autoGrowInput = function(options){ /* Thanks to: https://github.com/Pixabay/jQuery-autoGrowInput */
    var o = $.extend({ maxWidth: 500, minWidth: 20, comfortZone: 0 }, options),
        event = 'oninput' in document.createElement('input') ? 'input' : 'keydown';
    this.filter('input:text').each(function(){
        var input = $(this),
            minWidth = o.minWidth || input.width(),
            val = ' ',
            comfortZone = (options && 'comfortZone' in options) ? o.comfortZone : parseInt(input.css('fontSize')),
            span = $('<span/>').css({
                position: 'absolute',
                top: -9999,
                left: -9999,
                width: 'auto',
                fontSize: input.css('fontSize'),
                fontFamily: input.css('fontFamily'),
                fontWeight: input.css('fontWeight'),
                letterSpacing: input.css('letterSpacing'),
                whiteSpace: 'nowrap',
                ariaHidden: true
            }),
            check = function(e){
                if (val === (val = input.val()) && !e.type == 'autogrow') return;
                if (!val) val = input.attr('placeholder') || '';
                span.html(val.replace(/&/g, '&amp;').replace(/\s/g, '&nbsp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'));
                var newWidth = span.width() + comfortZone, mw = typeof(o.maxWidth) == "function" ? o.maxWidth() : o.maxWidth;
                if (newWidth > mw) newWidth = mw;
                else if (newWidth < o.minWidth) newWidth = o.minWidth;
                if (newWidth != input.width()) input.width(newWidth);
            };
        span.insertAfter(input);
        input.on(event+'.autogrow autogrow', check);
        // init on page load
        check();
    });
    return this;
}

$.scrollUp = function (options) {
/*
scrollUp v1.0.0
Author: Mark Goodyear - http://www.markgoodyear.com
Git: https://github.com/markgoodyear/scrollup

Copyright 2013 Mark Goodyear
Licensed under the MIT license
http://www.opensource.org/licenses/mit-license.php

Twitter: @markgdyr
*/
 var settings = $.extend({
  'button_id'	 : 'scrollUp', // Element ID
  'threshold'	 : 300, // Distance from top before showing element (px)
  'speed'		 : 300, // Speed back to top (ms)
  'effect'		 : 'fade', // Fade, slide, none
  'in_speed'	 : 200, // Animation in speed (ms)
  'out_speed'	 : 200, // Animation out speed (ms)
  'button_text'	 : 'Scroll to top' // Text for element
  
 }, options)
 , zi = '2147483647'
 , f_set = false
 , animations = {
   'fade'	 : { 'in' : 'fadeIn', 'out' : 'fadeOut' },
   'slide'	 : { 'in' : 'slideDown', 'out' : 'slideUp' },
   'none'	 : { 'in' : 'show', 'out' : 'hide' }
  }

 for (f in animations) {
  if (settings['effect'] == f) { f_set = true; break; }
 }
 if (!f_set) settings['effect'] = 'none';

 var button = $('<a/>', {
  id	 : settings['button_id'],
  href	 : '#top',
  title	 : settings['button_text'],
  text	 : settings['button_text']
 })
 .css({
  'display':'none', 'position': 'fixed', 'z-index': zi, 'bottom': 20, 'right': 20
 })
 .bind('click', function() {
  $('html, body').animate({scrollTop:0}, settings['speed']);
  return false;
 })
 .appendTo('body');

 $("<div id='"+ settings['button_id'] +"-active'></div>")
  .css({ 'position': 'absolute', 'top': settings['threshold'], 'width': '100%', 'z-index': zi })
  .appendTo('body');

 $(window).scroll(function(){
  ($(window).scrollTop() > settings['threshold']) ?
   (button[animations[settings['effect']]['in']])(settings['in_speed'])
   :
   (button[animations[settings['effect']]['out']])(settings['out_speed'])
  ;
 });
}


$.fn.fitText = function(option) {
 var self = this;
 option = $.extend({
  maxFontSize : undefined,
  minFontSize : undefined,
  responsive : true,
//  relative_length : false,
  resize_delay : 1,
  callback : null
 }, option);

/** // original delay code
 $.fn.extend({
  "delayResize":function(f) {
   return f ?
    this.bind("resize", (function(fn) {
     var timeout;
     return function() {
      if (timeout) clearTimeout(timeout);
      timeout = setTimeout(function () { fn.apply(self, arguments); timeout = null }, option["resize_delay"]);
     }
    })(f))
   :
    this.trigger("delayResize")
  }
 });
*/
 var f = function(){
  var e = $(this)
    , c = e.clone().css({"white-space":"nowrap","position":"absolute","height":"auto","width":"auto"}).insertAfter(e)
    , font_size = c.css("font-size").match(/^([\d\x2e]+)([a-zA-Z]+)$/)
    , size = parseFloat(font_size[1]) * (e.width() / c.width())
  ;
  if (undefined !== option["maxFontSize"] && parseFloat(option["maxFontSize"]) < size) {
   size = option["maxFontSize"];
  } 
  if (undefined !== option["minFontSize"] && parseFloat(option["minFontSize"]) > size) {
   size = option["minFontSize"];
  }
  e.css({
   "font-size"	 : parseInt(size) + font_size[2],
   "border"		 : c.css("border"),
   "border-width": c.css("border-width"),
   "padding"	 : c.css("padding"),
   "margin"		 : c.css("margin")
  });
  c.remove();
 }
 var fit = function(){ self.each(f) };
 if (option["responsive"]) {
  $(window).bind("resize", $.debounce(option['resize_delay'], fit));
 }
 fit();
 return this;
}


$.fn.fitBackground = function(option) {
 var e = this;
 option = $.extend({
  "src"			: undefined,
  "x_align"		: "center", // "left" | "right"
  "y_align"		: "center",
  "offset-x"	: 0,
  "offset-y"	: 0,
  "padding_top" : 0, "padding_right": 0, "padding_bottom": 0, "padding_left": 0, 
  "height"		: "",
  "image_aspect": true,
  "element_aspect" : false,
  "img_class"	: "fitBackground_Image",
  "responsive"	: true,
  "delay"		: 30,
  "background-image" : null,
  "callback"	: null,
  "z_index"		: -999,
  "_if_body_fit_to_window": true
 }, option);
 var bg = $.getBackgroundImageURL(e), img
   , wrapper = $("<div />").attr({"class":option["img_class"]+"_wrap"}).appendTo(e).css({
     'position': 'absolute',
     'top': 0,
     'left': 0,
	 'z-index': option["z_index"],
     'overflow': 'hidden'
    })
   , _body = option["_if_body_fit_to_window"] && e.get(0).tagName.match(/body/i) != null
   , _original_height = e.height()
   , ow = null, oh = null
 ;
 if (bg || option["background-image"]) {
  img = $('<img />')
   .attr({'src': bg ? bg : option["background-image"], "class": option["img_class"]})
   .css({'position': 'absolute'})
   .appendTo(wrapper)
 }
 else { img = $(option["img_class"], e); }
 if (!img) { return false; }
 var fit = function() {
  if (_body) {
   $("body").height(_original_height);
   if ($(window).height() > _original_height) { e.height($(window).height()) }
  }
  if (!ow) ow = img.width(); if (!oh) oh = img.height();
  var iw = img.width(), ih = img.height(), w, h
    , aspect = option["image_aspect"]
	, element_aspect = option["element_aspect"]
	, wrapper_w = e.width() + removeUnit(e.css("padding-left"), 'px') + removeUnit(e.css("padding-right"),'px')
    , wrapper_h = e.height() + removeUnit(e.css("padding-top"),'px') + removeUnit(e.css("padding-bottom"),'px')
	, rx = wrapper_w / (ow + option["padding_left"] + option["padding_right"])
	, ry = wrapper_h / (oh + option["padding_top"] + option["padding_bottom"])
    , r = (rx >= ry) ? rx : ry
    , x, y
    , bg_w = ow * (aspect ? r : rx)
    , bg_h = oh * (aspect ? r : ry)
    , offset_x = option["padding_left"] * (aspect ? r : rx)
    , offset_y = option["padding_top"] * (aspect ? r : ry)
  ;
  wrapper.width(wrapper_w).height(wrapper_h);
  img.width(bg_w).height(bg_h);
  ;//console.log(wrapper_w,wrapper_h);//console.log(rx,ry,offset_x);//console.log(offset_x,offset_y);
  if (!aspect) {
   img.css({"top": offset_y, "left": offset_x}); return e;
  }
  else {
   switch (option["x_align"]) {
    case "center"	: x = offset_x + ( bg_w + offset_x - wrapper_w ) / -2 ; break;
    case "right"	: x = wrapper_w - bg_w; break;
    default			: x = offset_x; break;
   }
   switch (option["y_align"]) {
    case "center"	: y = offset_y + ( bg_h + offset_y - wrapper_h ) / -2 ; break;
    case "bottom"	: y = wrapper_h - bg_h; break;
    default			: y = offset_y; break;
   }
   img.css({"top":y, "left":x})
   if (typeof(option["callback"]) == typeof(function(){})) { e.bind("fitBackground", option["callback"]) }
   e.trigger("fitBackground");
  }
 } 
 img.bind('load', fit);
 e.css('background-image','none');
 if (option["responsive"]) {
  $(window).resize($.debounce(option["delay"],fit));
 }
 return this;
}


$.fn.swapSpriteImage = function(o) {
 var o = $.extend({
  'decimal' : 'ceil',
  'resize_delay' : 25,
  'max-width' : undefined,
  'swap_image' : undefined,
  'swap_image_ratio' : undefined
 }, o);
 this.each(function(){
  var e = $(this).css({
    'display'  : 'block',
    'overflow' : 'hidden'
   })
   , p = $(e.parent().get(0))
   , src = o['swap_image'] ? o['swap_image'] : e.css('background-image').replace(/url\x28(?:[\x22\x27])?(.*?)(?:[\x22\x27])?\x29$/,'$1')
   , ie6 = e.css('background-position') === undefined
   , x = ie6 ? e.css('background-position-x') : e.css('background-position').split(' ')[0]
   , y = ie6 ? e.css('background-position-y') : e.css('background-position').split(' ')[1]
   , w = parseFloat( e.width() )
   , h = parseFloat( e.height() )
   , r = parseFloat(w) / parseFloat(h)
   , img = $("<img src="+src+" />").css( {
      'display'	 : 'block',
      'position' : 'relative',
      'top'		 : y,
      'left'	 : x
     } )
   , pw = parseFloat(p.width())
  ;
  if (!src) return e;
  if (o['max-width']) { pw = o['max-width'] }
  else {
   e.parents().each(function(){
    mw = $(this).css('max-width');
    if ( mw !== 'none' && mw !== undefined) {
     pw = parseFloat( mw );
     return false;
    }
   })
  }
  ;
  e.css({
   'background'	 : 'none',
   'max-width'	 : ( ( w / pw ) * 100 ) + '%',
   'text-indent' : 0,
   'padding'	 : 0,
   'white-spzce' : 'wrap'
  }).html(img);
  img.bind('load', function(){
   img.css({
    'width': Math[o['decimal']]( parseFloat(img.width()) / w * 100 ) + '%'
   });
   $(window).bind('resize', $.debounce(o['resize_delay'], function(){
    var yi = parseFloat(y)
     , xi = parseFloat(x)
     , pow = parseFloat( e.width() / w )
    ;
    img.css({
     'top'	 : yi * pow,
     'left'	 : xi * pow
    });
    e.css({ 'height' : Math[o['decimal']](h * pow) })
   }) );
   $(window).trigger('resize');
  });
 });
 return this;
}


$.fn.parallaxScroll = function(option){
 option = $.extend({
  speed : 0.2,
  unit  : 'px'
 }, option);

 var o = new Object(),
  bg = $(this);
 for (var k in option) {
  o[k] = option[k];
 } 

 option["styles"] = $.extend({
  "position" : "fixed",
  "width"	 : "100%",
  "height"	 : "300%",
  "top"		 : 0,
  "left"	 : 0,
  "z-index"	 : -1
 }, o.hasOwnProperty("styles") ? o["styles"] : {} );
 bg.css(option["styles"]);
 $(window).scroll(function(){
  var scrolled = $(window).scrollTop(); 
  bg.css('top', -(scrolled*option["speed"]) + option['unit']);
 });
}


$.fn.popList = function(option) {
 option = $.extend({
  "nodeTag"		 : "li",
  "wrapperTag"	 : "ul",
  "titleTag"	 : "a",
  "easing"		 : "swing",
  "speed"		 : [500,300],
  "z-index"		 : 100,
  "trigger"		 : 'hover',
  "fixChildNodes": true,
  "offset_x"	 : 0,
  "offset_y"	 : 0,
  "ornaments"	 : true
 }, option);
 var fadeInSpeed, fadeOutSpeed, easing = option["easing"];
 if (isArray(option["speed"])) {
  fadeInSpeed = option["speed"][0], fadeOutSpeed = option["speed"][1]
 }
 else {
  var s = parseFloat( option["speed"] );
  fadeInSpeed = s, fadeOutSpeed = s
 }
 var nodeTag = option["nodeTag"], wrapperTag = option["wrapperTag"], titleTag = option["titleTag"]
  , wholeWidth = this.width(), totalHeight = this.height();

 if (option["fixChildNodes"]) {
  var horizontal = $(this.children(nodeTag).get(1)).position().left > 0
   , p = this.position();

  $(wrapperTag,this).css("position","absolute");
  if (this.get(0).tagName == wrapperTag) this.css("position","relative");
  else $(this.children(wrapperTag).get(0)).css("position","relative");

  this.children(nodeTag).each(function(){
   var n = $(wrapperTag, this)
	 , w = n.css("dislpay","inline").width();
   n.css("display","block");
   if (horizontal) {
	if (wholeWidth - p.left < w) n.css({"left": (wholeWidth - p.left - w) * -1});
   }
   else {
	n.css('left', wholeWidth);
	n.css('top', totalHeight);
   }
  })
 }
 $( nodeTag, this ).each(function(){
  var li = $(this).prepend($('<div class="popList_ornament_box popList_ornament_box_prepend" >')).append($('<div class="popList_ornament_box popList_ornament_box_append" >'))
    , ul = li.children(wrapperTag).get(0)
    , hidden = {'visibility':'hidden','opacity':0}
  if (ul) {
   ul = $(ul).css(hidden); 
   var uls = $(wrapperTag+":first", li.siblings());
   var fade = function(e,s){
    s = s ? s : 0;
    uls.css(hidden); 
    if (ul.css("opacity") == 1) ul.animate({"opacity":0}, fadeOutSpeed,easing,function(){$(this).css('visibility','hidden')})
	else ul
	 .css({"opacity":0,"visibility":"visible","z-index":option["z-index"]})
	 .animate({"opacity":1}, fadeInSpeed)
   }
   if (undefined != li.hoverIntent) li.hoverIntent(fade,fade);
   else li.bind(option["trigger"], fade, fade);
  }
 })
}

$.fn.AddGreyImage = function(options) {
options = $.extend({
 R_bias: 0.3,
 G_bias: 0.59,
 B_bias: 0.11
}, options);
if (document.createElement('canvas').getContext != true) {
 var imgs = ($(this).get(0).tagName == "img") ? $(this) : $("img", this)
   , items = imgs.length;
 imgs.each(function(){
  $(this).bind("load", function() {
   var i = $(this);
   var classname = "grey_image"
    + " grey_image_src:" + i.attr("src")
	+ " grey_image_name:" + i.attr("src").replace(new RegExp(/^.*?\x2f?([^\x2f]*?)$/), "$1")
	+ " grey_image_name_" + i.attr("src").replace(new RegExp(/^.*?\x2f?([^\x2f]*?)(?:\x2e[^\x2e]*)$/), "$1")
     , w = i.outerWidth(), h = i.outerHeight()
	 , canvas = $('<canvas />').attr( { "width": w, "height": h, "class":classname } )
	 , c = canvas.get(0).getContext("2d")
	;
   c.drawImage(i.get(0),0,0,w,h);
   var d = c.getImageData(0,0,w,h)
	 , px = d.data
	 , j = 0
	 , grey;
   for (; j<px.length; j+=4) {
	px[j] = px[j+1] = px[j+2] = px[j] * options["R_bias"] + px[j+1] * options["G_bias"] + px[j+2] * options["B_bias"];
   }
   c.putImageData(d,0,0);
   i.after(canvas);
   items--;
   if (items == 0) $(window).trigger("greyImageLoaded");
  });
 })
}
return this;
}

// Just Implementations.
if (undefined===$.fn.hoverIntent)
$.fn.hoverIntent = function(f,g) {
 var cfg = $.extend(cfg = { sensitivity : 7,  interval : 100,  timeout : 0 }, g ? { over: f, out: g } : f )
 , cX, cY, pX, pY
 , track = function(ev) {
  cX = ev.pageX;
  cY = ev.pageY;
 }
 , compare = function(ev,ob) {
  ob.hoverIntent_t = clearTimeout(ob.hoverIntent_t);
  if ( ( Math.abs(pX-cX) + Math.abs(pY-cY) ) < cfg.sensitivity ) {
   $(ob).unbind("mousemove",track);
   ob.hoverIntent_s = 1;
   return cfg.over.apply(ob,[ev]);
  } else {
   pX = cX; pY = cY;
   ob.hoverIntent_t = setTimeout( function(){compare(ev, ob);} , cfg.interval );
  }
 }
 , delay = function(ev,ob) {
  ob.hoverIntent_t = clearTimeout(ob.hoverIntent_t);
  ob.hoverIntent_s = 0;
  return cfg.out.apply(ob,[ev]);
 }
 , handleHover = function(e) {
  var p = (e.type == "mouseover" ? e.fromElement : e.toElement) || e.relatedTarget;
  while ( p && p != this ) { try { p = p.parentNode; } catch(e) { p = this } }
  if ( p == this ) { return false }
  var ev = jQuery.extend({},e), ob = this;

  if (ob.hoverIntent_t) { ob.hoverIntent_t = clearTimeout(ob.hoverIntent_t) }
  if (e.type == "mouseover") {
   pX = ev.pageX; pY = ev.pageY;
   $(ob).bind("mousemove",track);
   if (ob.hoverIntent_s != 1) { ob.hoverIntent_t = setTimeout( function(){compare(ev,ob)} , cfg.interval ) }
  } else {
   $(ob).unbind("mousemove",track);
   if (ob.hoverIntent_s == 1) { ob.hoverIntent_t = setTimeout( function(){delay(ev,ob)} , cfg.timeout ) }
  }
 }
 return this.mouseover(handleHover).mouseout(handleHover);
}

if (undefined === $.fn.autosize) $.fn.autosize = function (className) {
 var  hidden = 'hidden',
 copy = '<textarea style="position:absolute; top:-9999px; left:-9999px; right:auto; bottom:auto; word-wrap:break-word; height:0 !important; min-height:0 !important; overflow:hidden">',
 copyStyle = [ 'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'letterSpacing', 'textTransform', 'wordSpacing' ];

 return this.each(function () {
  var ta = this
    , $ta = $(ta).css({ overflow: hidden, overflowY: hidden, wordWrap: 'break-word' })
	, mirror = $(copy).addClass(className || 'autosizejs')[0]
	, minHeight = $ta.height()
	, maxHeight = parseInt($ta.css('maxHeight'), 10)
	, active
	, i = copyStyle.length
   ;
  // Opera returns '-1px' when max-height is set to 'none'.
  maxHeight = maxHeight && maxHeight > 0 ? maxHeight : 9e4;
  var adjust = function() {
   var height, overflow;
   if (!active) {
    active = true;
    mirror.value = ta.value;
    mirror.style.overflowY = ta.style.overflowY;
    mirror.style.width = $ta.css('width');
    mirror.scrollTop = 0;
    mirror.scrollTop = 9e4;

    height = mirror.scrollTop;
    overflow = hidden;
    if (height > maxHeight) { height = maxHeight; overflow = 'scroll'; }
    else if (height < minHeight) { height = minHeight }
    ta.style.overflowY = overflow;

	ta.style.height = ta.style.minHeight = ta.style.maxHeight = height + 'px';
	active = false//    setTimeout(function () { active = false }, 1);
   }
  }

  while (i--) { mirror.style[copyStyle[i]] = $ta.css(copyStyle[i]) }

  $('body').append(mirror);
  $(window).resize(adjust);

  if ('onpropertychange' in ta) {
   if ('oninput' in ta) {
    ta.oninput = ta.onkeyup = adjust;
   } else {
    // IE7 / IE8
	ta.onpropertychange = adjust;
   }
  } else {
   // Modern Browsers
   ta.oninput = adjust;
  }
  adjust();
 });
};

if (undefined === $.fn.bottom) $.fn.bottom = function(options) {
 var defaults = {
  // how close to the scrollbar is to the bottom before triggering the event
  proximity: 0
 }
 , options = $.extend(defaults, options);

 return this.each(function() {
  var obj = this;
  $(obj).bind("scroll", function() {
   if (obj == window) { scrollHeight = $(document).height() }
   else { scrollHeight = $(obj)[0].scrollHeight }
   scrollPosition = $(obj).height() + $(obj).scrollTop();
   if ( (scrollHeight - scrollPosition) / scrollHeight <= options.proximity) $(obj).trigger("bottom");
  });
  return false;
 });
}

$.fn.classifyTable = function(options) {
 options = $.extend({
  "rows" : true,
  "columns" : true,
  "row_prefix" : "table_row_",
  "column_prefix" : "table_col_",
  "table" : "table",
  "tr" : "tr",
  "td" : "th,td"
 }, options)
 var e = this
   , r = options["rows"]
   , c = options["columns"]
   , r_p = options["row_prefix"]
   , c_p = options["column_prefix"]
 e.each(function(){
  var t = $(this)
  $(options["tr"], t).each(function(){
   var tr = $(this)
     , tr_c = arguments[0]
   if (r) tr.addClass(r_p + (tr_c+1))
   $(options["td"], tr).each(function(){
    var td = $(this)
	  , td_c = arguments[0]
	  , td_c_is_even = td_c % 2
	if (c) td.addClass(c_p + (td_c+1)).addClass(c_p + (td_c_is_even ? "even" : "odd"))
   })
  })
 })
}

$.fn.set_table_col_content_height = function(option) {
 var e = $(this)
   , destroy = (option === "destroy") ? option : undefined
   , option = $.extend({
  "table_row":".table_row",
  "table_col":".table_col",
  "table_col_content":".table_col_content",
  "reset_on_resize" : false
 }, option)
  , tr = option["table_row"]
  , td = option["table_col"]
  , c = option["table_col_content"]
  , d = parseInt(option["reset_on_resize"])
  , reset_delay = d ? d : false
 e.each(function(){
  var table = $(this)
  if (destroy === "destroy") {
   var cc = $(c, table).css("height","")
  }
  else {
   $(tr, table).each(function(){
    $(c, this).sameHeight();
   })
   if (reset_delay) {
    $(window).resize($.debounce(reset_delay, function(){
 	table.set_table_col_content_height("destroy").set_table_col_content_height(option)
    }))
   }
  }
 })
 return this
}

$.fn.textfield_autocomplete = function(list, option) {
 /* code from http://jqueryui.com/autocomplete/#multiple with some modification */
 if ( undefined === list || !isArray(list) ) return false
 option = $.extend({
  'multiple'	 : false,
  'concat'		 : ", ",
  'regex'		 : /,\s*/,
  'open_on_focus': true,
  'open_on_foocus_delay' : 50,
  'keyCode'		 : 9 // $.ui.keyCode.TAB // 27 for ESC; 32 for SPACE
 }, option)
 var split = function(val) { return val.split(option['regex']) }
   , extractLast = function(term) { return split( term ).pop() }
   , multiple = option['multiple']
   , input_el = $(this)
 if (option['open_on_focus']) { input_el.focus(function(){ input_el.autocomplete("search", "")}) }
 if (multiple) {
  input_el.on( "keydown", function(event) {
   if ( (event.keyCode === option['keyCode']) && $(this).autocomplete( "instance" ).menu.active ) { event.preventDefault() }
  } )
 }
 input_el.autocomplete({
  minLength: 0,
  source: function( request, response ) {
   response( $.ui.autocomplete.filter(list, extractLast( request.term ) ) )
  },
  focus: function() { return false },
  select: function( event, ui ) {
   if (multiple) {
    var terms = split( this.value );
    terms.pop(); // remove the current input
    terms.push( ui.item.value ); // add the selected item
    terms.push( "" ); // add placeholder to get the comma-and-space at the end
    this.value = terms.join(option['concat']);
   }
   else 
    this.value =  ui.item.value ;
   return false;
  }
 })
}


$.fn.html_select_placeholder = function(option) {
 var default_text = 'Select...'
   , selected = false

 if (isString(option)) {
  option = { 'text' : default_text }
 }
 else {
  option = $.extend({
   'text' : default_text
  }, option)
 }
 var opt_placeholder = $('<option />').val("").text(option['text']).attr({
  'disabled': 'disabled'
 }).css('display', 'none')

 $('option',this).each(function(){
  if ($(this).attr('selected')=='selected') {
   selected = true
   return true
  }
 })
 if (!selected) {
  opt_placeholder.attr('selected','selected')
 }
 $(this.children().get(0)).before(opt_placeholder)
 return this
}


$.fn.textfield_complement_from_select = function(list, option) {
 if (isArray(window.jqueryUtility.textfield_complement_from_select)) {
  window.jqueryUtility.textfield_complement_from_select.push
 }
 option = $.extend({
  'use_button'		 : true, // or automatically input when changed
  'button_text'		 : 'Apply',
  'make_ui_button'	 : true,
  'order'			 : 'before',
  'ignore_empty'	 : true,
  'placeholder'		 : 'Select...', // or null for no placeholder
 }, option)

 var input_el = $(this)
   , button_el = $("<a class=textfield_complement_from_select_apply_button />").text(option['button_text'])
   , select_el = $("<select class=textfield_complement_from_select_select>")
   , option_el_skel = $("<option />")
 if (option['make_ui_button']) button_el.button()

 for (i in list) {
  select_el.append(option_el_skel.clone().val(list[i]).text(list[i]))
 }
 if (option['placeholder']) select_el.html_select_placeholder(option['placeholder'])
 if (option['use_button']) {
  button_el.bind('click', function(){ 
   var v = select_el.val()
   if (v || (!v && option['ignore_empty']) ) { input_el.val(v) }
  })
 }
 else {
  select_el.bind('change', function(){ input_el.val(select_el.val()) })
 }
 if (option['order'] == 'before') {
  input_el.before(select_el)
  if (option['use_button']) select_el.after(button_el)
 }
 else {
  input_el.after(select_el)
  if (option['use_button']) select_el.before(button_el)
 }
 return input_el
}

$.fn.relativeHeight = function(option){
/**
$("selector").relativeHeight("150%")
$("selector1").relativeHeight("2:3")
$("selector2").relativeHeight({"percent":"1:2","throttle":100})
$("selector3").relativeHeight({"ratio":0.83,"debounce":200})
*/

 var e = this
   , percent = null
   , parse_ratio = function(n) {
  if (!isNaN(n)) pc = n
  else if (n.toString().match(/[\d\.]+%$/)) pc = parseFloat(n) / 100
  else if (m = n.toString().match(/^([\d\.]+):([\d\.]+)$/)) {
   pc = parseFloat(m[1]) / parseFloat(m[2])
  }
  return pc
 }
   , param = $.extend({
  "throttle"	 : false,
  "debounce"	 : false,
  "ratio"		 : null,
  "percent"	 : null
 }, option)

 if (isObject(option)) {
  if (!option["ratio"] && !option["percent"]) return e
  if (option["ratio"]) percent = parse_ratio(option["ratio"])
  else if (option["percent"]) percent = parse_ratio(option["percent"])
 }
 else {
  percent = parse_ratio(option)
 }

 var f = function() { e.height(e.width()*percent) }
   , w = $(window)
 if (t = parseFloat(param["throttle"])) w.bind("resize", $.throttle(t, f)).trigger("resize")
 else if (t = parseFloat(param["debounce"])) w.bind("resize", $.debounce(t, f)).trigger("resize")
 else w.bind("resize",f).trigger("resize")
 return e
}

$.fn.fitBodyToViewport = function(option) {
 if (isString(option)) {
  if ("destoy"===option) {
   return this
  }
 }
 option = $.extend({
  "to" : window // Selector or Element
 }, option)
 
 var rest = $(option["to"]).height() - $("body").outerHeight()
 if (rest > 0) {
  this.height(this.height() + rest)
 }
 return this
}


$.fn.input_text_clear_button = function(option) {
 option = $.extend({
  "opacity"	 :"0.75",
  "width"	 :".8em",
  "height"	 :".8em",
  "position" :"absolute",
  "right"	 :".25em",
  "top"		 :".25em",
  "display"	 :"block",
  "class"	 :"close_button"
 }, option)
 var e = $(this)
   , image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHAAAABwCAMAAADxPgR5AAAAM1BMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACjBUbJAAAAEXRSTlMAFiQ6T1Zlcx0HK2wyXQ5BSOQOSNgAAAIKSURBVHgB3NXBruMgDEBRB4BboPT1/792WIwijdQOjh02764jHRnFslzqCDHlwlnJKYZD9vSImS/l+LgZqy2xKLV6GxcSqlK4ZbhYUFdidXOdS3U16ef85LNjqj9N3DEwNwy7GXEVL3KvgbPxuuK1jrve9N4Pt/Sj5GriplJVeYPbGtXhOUS/5xf9nkL0e34xsaG0ff/U+9jYVPvsvTqb6q+P4GBbw3+P/NfqYGuH/0Gdj/pkc89/vdr53ruhqr35Xq/qP+at3NAm8j8xagec3hRV3hSVI8aFN0WFtxCjYsDTm6LKm6JmxLj2pqjypqgYsSy8pTg9hVjkb2HhLcXpqcSwOrtNFKL6s/MUV1CLLg/q+YFadHjntwmPqPfONwWTaPBAZg+MosHjISIRg2jwzt3PGESjRxYRMIhGD0QOLKLV45CASTR6BInYRJtHlIRNtHkkyfhFvUeWgl/UexQBv9jQJ/jFxi6Q5vYQvGJjJ0jTe/vB3/Ckf3q3EyIAgBgGgf5dY4JZD/ekgdpD46/FcvH908Yfb/498Q+YRwweonhM9EEYR30/zPhxzQ+kfOTmpQKvTXwxxKsvX+7x+pIXtL6C5iW7xwgclGgUBGCXxnkaWHIkq6GzxupaHOBqhJY/tN7iBR6vKHkJy2tmXqTzqqCXIb3u6YVWr+x6Kdlr114s9+q8Xw7g6w8Brdry8aLC2JQAAAAASUVORK5CYII='
  , b = $("<div class='"+option["class"]+"' ></span>")
         .css(option)
         .hover(function(){$(this).css("opacity",1)}, function(){$(this).css("opacity",option["opacity"])})
  , i = $("<img />").attr("src",image).css({
     "width":"100%",
     "height":"100%",
     "display":"block"
    }).appendTo(b)
  , f = function(){ e.val(""); b.css({"display":"none"}); e.focus() }
  , f_show = function() { b.css({"display":"block"}) }
  , f_hide = function() { b.css({"display":"none"}) }
  , f_show_hide = function() {
     if (e.val() !== "") { f_show.call() }
     else { f_hide.call() }
    }
 b.click(f)
 e.after(b)
  .bind("keyup", f_show_hide)
  .bind("focus", f_show_hide)
  .bind("click", f_show_hide)
  .trigger("keyup")
 return e
}


$.fn.fadeInView = function(options) {
 options = $.extend({
  "duration": "1s",
  "translate": 100
 }, options)
 var e = $(this)
  .css({
   "opacity"     : 0,
   "transform"   : "translate(0, "+options["translate"]+"px)"
  })
  .each(function(){
   var _e = $(this) 
   _e.one("inview", function(evt, isInView) {
    if (isInView) {
     _e.css({
      "transition"  : options["duration"],
      "opacity": 1,
      "transform": "translate(0, 0)"
     })
    }
   } )
  } )
 return e
}


/** Preserves style (CSS) before modified by JS (jQuery) **/
var preserve_style_attr_key = "jqutil_restored_style"
$.fn.preserve_style = function(attr_key) {
 if (attr_key == undefined) { attr_key = preserve_style_attr_key }
 if (this.data(attr_key) === undefined) { this.data(attr_key, this.attr("style")) }
 return this
}
$.fn.restore_style = function(attr_key) {
 if (attr_key === undefined) { attr_key = preserve_style_attr_key }
 var s = this.data(attr_key)
 this.attr("style", s ? s : '')
 return this
}

})(jQuery);
