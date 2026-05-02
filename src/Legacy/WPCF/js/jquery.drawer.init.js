(function($){$(function(){
/**
USAGE:
 $("#element_you_want_to_make_drawer_by_screen_width").init_drawer({
   "sr_text"                : "Toggle Navigation Button",
   "drawer_wrapper_class"   : "drawer-wrap",
   "width"                  : 192,
   "breakpoint"             : 768,
   "side"                   : "left", // or right
   "content_class"          : "drawer_content",
   "delay"                  : 20 // set resize debounce delay
   "open"                   : function(){},
   "close"                  : function(){}
 })
**/

 $.fn.init_drawer = function(option) {
  var w = $(window)
    , e = $(this).preserve_style()
    , o = $.extend({
   "sr_text" : "Toggle Navigation Button",
   "drawer_wrapper_class" : "drawer-wrap",
   "drawer_created_class"  : "drawer-created",
   "width" : 192,
   "breakpoint":768,
   "side":"left", // or right
   "content_class" : "drawer_content",
   "delay" : 20,
   "css" : "",
   "open" : function(){$("body").addClass("drawer-opened")},
   "close" : function(){$("body").removeClass("drawer-opened")}
  }, option)
    , css_mod = $("<style />")
    , wrapper = $("<div />").insertBefore(e).append($("<div class="+o["content_class"]+" />")).hide()
    , b = $("<div />").addClass("drawer-toggle drawer-hamburger")
       .append(
          $("<span class=sr-only >"+o["sr_text"]+"</span>")
        , $("<span class=drawer-hamburger-icon />")
       )
   
  wrapper.on('drawer.opened', o["open"]).on('drawer.closed', o["close"])
  w.resize($.debounce(o["delay"],function() {
   if ($(window).width() <= o["breakpoint"]) {
    b.appendTo(wrapper)
    var content_w = o["width"]
    css_mod.text(
       " .drawer--left.drawer-open .drawer-hamburger{left:"+content_w+"px}"
     + " .drawer--left .drawer-nav{left:-"+content_w+"px}"
     + " .drawer--right.drawer-open .drawer-hamburger{right:"+content_w+"px}"
     + " .drawer--right .drawer-nav{right:-"+content_w+"px}"
     + " .drawer-open{overflow:visible!important}"
//     + " .drawer-open,.drawer-hamburger,." + o["drawer_wrapper_class"] + "{z-index:999999}"
     + o["css"]
    ).appendTo($("head"))
    e.appendTo($("."+o["content_class"], wrapper).addClass("drawer-nav").width(o["width"]))
    wrapper
     .show()
//   .addClass("drawer")
     .addClass(o["drawer_wrapper_class"])
     .addClass("drawer--"+o["side"])
     .drawer()
    $("body").addClass(o["drawer_created_class"])
   }
   else {
    if (wrapper.hasClass(o["drawer_wrapper_class"])) {
     e.insertBefore(wrapper).restore_style()
     css_mod.text("")
     wrapper.drawer("destroy")
      .removeClass(o["drawer_wrapper_class"])
      .hide()
     $("body").removeClass(o["drawer_created_class"])
    }
   }
  })).trigger("resize")
  return this
 }
}) })(jQuery);
