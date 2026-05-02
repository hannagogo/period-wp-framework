(function($){
$.fn.apply_nanoScroller = function(options) {
 this.each(function(){
  var e = $(this);
  e.addClass('nanoscroller_content');
  e.wrap($('<div class=nanoscroller_wrapper />').height(e.height()));
  var wrapper = $(e.parent().get(0));
  (wrapper.height() == 0) && wrapper.height(
   e.height()==0 ? $(e.children().get(0)).height() :e.height()
  );
  if (typeof(options) != typeof({})) options = {};
  options['contentClass'] = "nanoscroller_content";
  wrapper.nanoScroller(options);
 });
}
})(jQuery);