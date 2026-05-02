(function($){
$.magnificPopup_implicitly = function(options){
 options = $.extend({
  "image_extentions":["jpg","jpeg","JPEG","JPG","gif","GIF","PNG","png"],
  "gallery_selector":undefined,
  "fade":300,
  "mainClass":"mfp-fade"
 },options)
 var selectors = [], selector
 for (var i in options["image_extentions"]) {
  selectors.push("a[href$=" + options["image_extentions"][i] + "]")
 }
 selector = selectors.join(",")
 $("body").magnificPopup({
  "delegate": selector,
  "type": 'image',
  "removalDelay": options["fade"],
  "mainClass":  options["mainClass"]
 })
 if (isString(options["gallery_selector"])) options["gallery_selector"] = [options["gallery_selector"]]
 if (isArray(options["gallery_selector"])) {
  for (var g in options["gallery_selector"]) {
   $(options["gallery_selector"][g]).magnificPopup({
    delegate: selector,
    "type": 'image',
    "gallery": { enabled:true },
    "removalDelay": options["fade"],
    "mainClass":  options["mainClass"]
   })
  }
 }
}
})(jQuery)

jQuery(function(){jQuery.magnificPopup_implicitly()})
