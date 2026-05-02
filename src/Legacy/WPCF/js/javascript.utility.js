if (!window.console) window.console = { log: function(){} };
if (!window.useDefault) window.useDefault = function(data,def) { return (data === undefined) ? def : data }
if (!window.addFunction) {
 window.addFunction = function(name, func, overwrite) {
  overwrite = useDefault(overwrite, true);
  if (undefined === func || undefined == name) return;
  if (overwrite || !window[name]) window[name] = func;
 }
}

addFunction('in_array', function() {
 var l = arguments.length;
 var x = (l > 0) ? arguments[0] : '';     // string X
 var a = (l > 1) ? arguments[1] : [];     // array
 var s = (l > 2) ? arguments[2] : false;  // strict
 for (var i = 0; i < a.length; i++){
  if ((s && a[i]===x) || (!s && a[i]==x)) { return true; }
 }
 return false;
});

addFunction('parse_args', function(a,b,r){
 var o = typeof({});
 if ((o != typeof(a)) || (o != typeof(b))) return {};
 option = {}
 for (var k in a) {
  if (undefined !== b[k]) {
   if (r && isObject(a[k])) {
    option[k] = parse_args(a[k], b[k], r)
   }
   else {
    option[k] = b[k]
   }
  }
  else {
   option[k] = a[k];
  }
 }
 return option;
})

addFunction('parseNumber', function(n,i){
 n = n.toString()
 var negative = n.match(/^-\d/) ? true : false;
 n = n.replace(/[^\d\x2e]/g, '');
 n = ( n == '' ? 0 : ( i ? parseInt(n) : parseFloat(n) ) ) * (negative ? -1 : 1)
 return n
})

//
addFunction("sprintf", function(){
  //  discuss at: http://phpjs.org/functions/sprintf/
  // original by: Ash Searle (http://hexmen.com/blog/)
  // improved by: Michael White (http://getsprink.com)
  // improved by: Jack
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: Dj
  // improved by: Allidylls
  //    input by: Paulo Freitas
  //    input by: Brett Zamir (http://brett-zamir.me)
  //   example 1: sprintf("%01.2f", 123.1);
  //   returns 1: 123.10
  //   example 2: sprintf("[%10s]", 'monkey');
  //   returns 2: '[    monkey]'
  //   example 3: sprintf("[%'#10s]", 'monkey');
  //   returns 3: '[####monkey]'
  //   example 4: sprintf("%d", 123456789012345);
  //   returns 4: '123456789012345'
  //   example 5: sprintf('%-03s', 'E');
  //   returns 5: 'E00'

  var regex = /%%|%(\d+\$)?([-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g
    , a = arguments
    , i = 0
    , format = a[i++]
  // pad()
  var pad = function (str, len, chr, leftJustify) {
    if (!chr) { chr = ' ' }
    var padding = (str.length >= len) ? '' : new Array(1 + len - str.length >>> 0)
      .join(chr)
    return leftJustify ? str + padding : padding + str
  }
  // justify()
  , justify = function (value, prefix, leftJustify, minWidth, zeroPad, customPadChar) {
    var diff = minWidth - value.length;
    if (diff > 0) {
      if (leftJustify || !zeroPad) {
        value = pad(value, minWidth, customPadChar, leftJustify);
      } else {
        value = value.slice(0, prefix.length) + pad('', diff, '0', true) + value.slice(prefix.length);
      }
    }
    return value;
  }
  // formatBaseX()
  , formatBaseX = function (value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
    // Note: casts negative numbers to positive ones
    var number = value >>> 0;
    prefix = prefix && number && {
      '2': '0b',
      '8': '0',
      '16': '0x'
    }[base] || '';
    value = prefix + pad(number.toString(base), precision || 0, '0', false);
    return justify(value, prefix, leftJustify, minWidth, zeroPad);
  }
  // formatString()
  , formatString = function (value, leftJustify, minWidth, precision, zeroPad, customPadChar) {
    if (precision != null) {
      value = value.slice(0, precision);
    }
    return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar);
  }
  // doFormat()
  , doFormat = function (substring, valueIndex, flags, minWidth, _, precision, type) {
    var number, prefix, method, textTransform, value;

    if (substring === '%%') {
      return '%';
    }
    // parse flags
    var leftJustify = false,	 positivePrefix = '',	 zeroPad = false,
	    prefixBaseX = false,	 customPadChar = ' ',	 flagsl = flags.length
    for (var j = 0; flags && j < flagsl; j++) {
      switch (flags.charAt(j)) {
      case ' ':
        positivePrefix = ' '
        break
      case '+':
        positivePrefix = '+'
        break
      case '-':
        leftJustify = true
        break
      case "'":
        customPadChar = flags.charAt(j + 1)
        break
      case '0':
        zeroPad = true
        customPadChar = '0'
        break
      case '#':
        prefixBaseX = true
        break
      }
    }

    // parameters may be null, undefined, empty-string or real valued
    // we want to ignore null, undefined and empty-string values
    if (!minWidth) { minWidth = 0 }
    else if (minWidth === '*') { minWidth = +a[i++] }
    else if (minWidth.charAt(0) == '*') { minWidth = +a[minWidth.slice(1, -1)] }
    else { minWidth = +minWidth }

    // Note: undocumented perl feature:
    if (minWidth < 0) {
      minWidth = -minWidth;
      leftJustify = true;
    }

    if (!isFinite(minWidth)) {
      throw new Error('sprintf: (minimum-)width must be finite')
    }

    if (!precision) {
      precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type === 'd') ? 0 : undefined;
    } else if (precision === '*') {
      precision = +a[i++];
    } else if (precision.charAt(0) == '*') {
      precision = +a[precision.slice(1, -1)];
    } else {
      precision = +precision;
    }

    // grab value using valueIndex if required?
    value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++];

    switch (type) {
    case 's':
      return formatString(String(value), leftJustify, minWidth, precision, zeroPad, customPadChar)
    case 'c':
      return formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad)
    case 'b':
      return formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
    case 'o':
      return formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
    case 'x':
      return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
    case 'X':
      return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
        .toUpperCase()
    case 'u':
      return formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
    case 'i':
    case 'd':
      number = +value || 0
      // Plain Math.round doesn't just truncate
      number = Math.round(number - number % 1)
      prefix = number < 0 ? '-' : positivePrefix
      value = prefix + pad(String(Math.abs(number)), precision, '0', false)
      return justify(value, prefix, leftJustify, minWidth, zeroPad)
    case 'e':
    case 'E':
    case 'f': // Should handle locales (as per setlocale)
    case 'F':
    case 'g':
    case 'G':
      number = +value
      prefix = number < 0 ? '-' : positivePrefix
      method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())]
      textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2]
      value = prefix + Math.abs(number)[method](precision)
      return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]()
    default:
      return substring
    }
  }
  return format.replace(regex, doFormat)
})
addFunction( "vsprintf", function(format, args) {
  //  discuss at: http://phpjs.org/functions/vsprintf/
  // original by: ejsanders
  //  depends on: sprintf
  //   example 1: vsprintf('%04d-%02d-%02d', [1988, 8, 1]);
  //   returns 1: '1988-08-01'
  return this.sprintf.apply(this, [format].concat(args))
})

window.utility = new Object({
 options : {
  'do_not_overwrite' : undefined,
  'auto_init' : true
 }
 ,
 init : function(option) {
  this.options = parse_args(option, this.options);
  if ((this.options['do_not_overwrite'] === undefined) || !(this.options['do_not_overwrite'] instanceof Array)) this.options['do_not_overwrite'] = [];
  for (var k in this.functions) {
   if (!in_array(k, this.options['do_not_overwrite'])) window[k] = this.functions[k];
  }
  
  for (var type in this.prototype_functions) {
   for (var fn in this.prototype_functions[type]) {
    window[type].prototype[fn] = this.prototype_functions[type][fn]
   }
  }
  var LF = "\x0a", CR = "\x0d", CRLF = CR+LF, ESC = "\x1b", TAB = "\x09", NULLSTR = ""
  ;
  utility.loaded = true;
 }
 ,
 loaded : false
 ,
 setup : function(options) { this.options = parse_args(options, this.options); return this; }
});

utility.functions = {
'isFunction'	 : function(f) { return Object.prototype.toString.call(f) === "[object Function]" },
'isString'		 : function(s) { return Object.prototype.toString.call(s) === "[object String]" },
'isArray'		 : function(a) { return Object.prototype.toString.call(a) === "[object Array]" },
'isNumber'		 : function(n) { return !isNaN(n); },
'isObject'		 : function(o) { return ((o !== undefined) && (typeof(o) != typeof(false)) && !isFunction(o) && !isString(o) && !isArray(o) && !isNumber(o)) },
'isTrue' : function(b) {
 if (
  (b === undefined) ||
  (b === null) ||
  (b === 0) ||
  (b === false) ||
  (b === "") ||
  (isArray(b) && b.length == 0) ||
  (isObject(b) && (function(o){ var i = 0; for (var k in o) i++; return i })(b) == 0)
 ) return false;
 return true;
},
'queryString' : function(f) {
/****** usage ******
// in HTML:
    <script src="/path/to/javascript.utility.js?key=value" ></script>
// in script:
	(new queryString()).param('key') // returns a value
    var q = new queryString(); q.param('key'); // same as above
*********************/
 f = useDefault(f, 'javascript.utility.js');
 this.scripts = document.getElementsByTagName('script'); // GET LIST OF THE SCRIPT FILES
 this.script;
 this.params = {};  
   
 for (var i=0; i < this.scripts.length; i++) {
  if ( this.scripts.item(i).src.indexOf(f) != -1 ) {  
   this.script = this.scripts.item(i);
   break;  
  }
 }  
 if (this.script) {  
  if (this.script.src.match( /(.*)(\?)(.*)/ )[3] ) {
   var a = this.script.src.match( /(.*)(\?)(.*)/ )[3].split('&');  
   if (a) {  
    for(var k = 0; k < a.length; k++) {  
     var p = a[k].split('=');  
     if(p[0] !== undefined) this.params[p[0]] = p[1];
    }  
   }
  }
 }
 this.param = function(k) { return this.params[k]; }
 return this;  
},

'virtualImage' : function(src,f) {
/****** USAGE: ******
 var img = virtualImage('/ImageFiles/box_round_full.png');
 var fn = function(){ alert (img.width) };
 document.body.onload = fn;
 // with jQuery
 jQuery.event.add(window, "load", fn);
**********************/
 var e = new Image();
 e.src = src; 
 e.onload = function() {
  var set_dimensions = function() { e.width = this.width; e.height = this.height }
  return set_dimensions.apply(
   (function(){this.i = new Image();i.src=arguments[0];return i;})(e.src), arguments
  );
  if (isFunction(f)) f();
 }
 return e;
},

'T_or_F' : function(d,def) {
 var b = (d === undefined) ? 
  (def === undefined) ? false : ( (isTrue(def))? true : false )
  :
  (isTrue(d)) ? true : false
 ;
 return b;
},

'compareNumber' : function(n1,n2,gl) {
 isString(n1) && n1.toNumber();
 isString(n2) && n2.toNumber();
 var gt = 'gt', lt = 'lt',
  op = {
   'gt' : gt, '>' : gt,
   'lt' : lt, '<' : lt
  };
 switch (op[gl]) {
  case 'gt' : 
   if (n1 > n2) return n1;
   else return n2;
  case 'lt' :
   if (n1 > n2) return n2;
   else return n1;
 }
},

'typeOf' : function(that){
 if (that === null)      return 'Null';
 if (that === undefined) return 'Undefined';
 var tc = that.constructor;
 return typeof(tc) === 'function'
  ? tc.getFunctionName() 
  : tc /* [object HTMLDocumentConstructor] など */;
},

'addFigure' : function(n, place, comma) {
 if (place === undefined) place = 3;
 if (comma === undefined) comma = ',';
 var num = new Number(parseNumber(n)).toString()
   , re_str = /^(-?\d+)(\d{3})/
   , re = new RegExp(re_str)
 ;
 while (num != (num = num.replace(re, "$1"+comma+"$2"))) {;};
 return num;
},

'UniqueId' : function(_,p) {
 _ = new String(_?_:'_');
 p = new Number(p?p:6); 
 return (
  (new Date()).getTime().toString()
  + _
  + Math.floor(Math.random() * Math.pow(10, ((p<=0 || p>10) ? 6 : p)))
 );
},

'appendUnit' : function(n,u) {
 u = new String(u ? u : 'px');
 return new String(n).replace(new RegExp('(?:' + u + ')?$'), u);
},
'removeUnit' : function(n,u) {
 return parseFloat(
  new String(n).replace(new RegExp('(?:' + new String(u ? u : 'px') + ')?$'), '')
 )
}
,
'getColorCode' : function(rgb_or_r,g,b){ // original name : parseColorCode
 var r = null;
 if (rgb_or_r instanceof Array) { b = rgb_or_r[2], g = rgb_or_r[1], r = rgb_or_r[0] }
 else { r = rgb_or_r }
 var rgb = [r,g,b]
 return '#' + (((256 + rgb[0] << 8) + rgb[1] << 8) + rgb[2]).toString(16).slice(1);
}
,
'getRGB' : function(color) { 
 var result;
 //* Check if we're already dealing with an array of colors
 if ( color && isArray(color) && color.length == 3 ) return color;
 //* old - rgb(num,num,num) => new - rgb(num,num,num) or rgba(num,num,num,num)
 if (result = /rgba?\(\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d(?![0-9])|\d{1}(?![0-9]))\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d(?![0-9])|\d{1}(?![0-9]))\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d(?![0-9])|\d{1}(?![0-9]))\s*(?:,\s*(25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d(?![0-9])|\d{1}(?![0-9]))\s*)?\)/.exec(color))
  return [+result[1], +result[2], +result[3]];
 //* rgb(num%,num%,num%) num = 100 ~ 0
 if (result = /rgb\(\s*((?:100|(?:[1-9]\d|\d)(?:\.[0-9]+)?))\%\s*,\s*((?:100|(?:[1-9]\d|\d)(?:\.[0-9]+)?))\%\s*,\s*((?:100|(?:[1-9]\d|\d)(?:\.[0-9]+)?))\%\s*\)/.exec(color))
  return [+result[1]*255/100, +result[2]*255/100, +result[3]*255/100];
   //old ↓ 100% = 254.99999999999997
   //return [parseFloat(result[1])*2.55, parseFloat(result[2])*2.55, parseFloat(result[3])*2.55];
 //* #a0b1c2 style string
 if (result = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
  return [parseInt(result[1],16), parseInt(result[2],16), parseInt(result[3],16)];
 //* #fff style string
 if (result = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
  return [parseInt(result[1]+result[1],16), parseInt(result[2]+result[2],16), parseInt(result[3]+result[3],16)];
 // Otherwise, we're most likely dealing with a named color
 return colorNames[ color.fastTrim().toLowerCase() ];
}
} // END OF utility.functions


utility.prototype_functions = {
'String' : {
 'qw' : function() {
  return this.replace(/(^[\s]+)|([\s]+$)/g, "").replace('/[\s]+/', "\x20").split("\x20");
 },

 'toNumber' : function(re) {
  re = useDefault(re, new RegExp(/^(?:.*?)(\d+(?:\x2e\d+)?)(?:.*?)$/));
  var r = this.replace(re, "$1");
  return new Number(r);;
 },

 'roundDecimal' : function(p) {
  var n = new Number(this.toNumber()); return n.roundDecimal(p);
 },

 'fastTrim' : function () {
  var 	str = this.replace(/^\s\s*/, ''),
  		ws = /\s/,
  		i = str.length;
  while (ws.test(str.charAt(--i)));
  return str.slice(0, i + 1);
 },

 'camelize' : function () {
  return this.replace (/(?:^|[-_])(\w)/g, function (_, c) { return c ? c.toUpperCase () : ''; })
 },

 'repeat' : function(n) {
  var rv = [], i = 0, sz = n || 1, s = this.toString();
  for (; i < sz; ++i) { rv.push(s); }
  return rv.join("");
 },
 /** tiny sprintf
 * format:
 *    "%"[arg-index-specifier"$"][sign-specifier][width-specifier][precision-specifier]type-specifier
 *
 * sign-specifier:
 *    "#": add "0", "0x", "0X" mark
 *       : typeが"o"なら先頭に"0"を追加します。
 *       : typeが"x"なら先頭に"0x"を追加します。
 *       : typeが"X"なら先頭に"0X"を追加します。
 *
 * width-specifier:
 *     n: minimize field width(0 to 9)
 *      : 最低何桁表示するかを指定します。指定可能な値は0〜9です。0で非表示になります。
 *
 * precision-specifier:
 *    "."n: floating-point limit width(0 to 9) for "f". string limit width(0 to 9) for "s"
 *        : ドットと数値を指定することで小数点以下の桁数や文字列の長さを指定できます。指定可能な値は0〜9です。
 *        : typeが"f"なら、小数点以下の桁数を指定します。浮動小数点値が丸められることがあります。0で小数点以下が非表示になります。
 *        : typeが"s"なら、文字列の長さを指定します。指定した長さ以上の文字は切り捨てられます。0で非表示になります。
 *
 * type-specifier:
 *    "d": signed decimal number
 *    "u": unsigned decimal number
 *    "o": unsigned octet number
 *    "x": unsigned hex number(lower case)
 *    "X": unsigned hex number(upper case)
 *    "f": floating-point number
 *    "c": the character with that ASCII value
 *    "s": string
 *    "%": "%"
 *
 * arg-index-specifier:
 *     n : arguments index
 *       : 引数のインデックスを指定します。引数の再利用と、引数の順序を指定することによりi18n化をサポートします。
 *
 */
 'sprintf' : function(args___) {
    var rv = [], i = 0, v, width, precision, sign, idx, argv = arguments, next = 0;
    var s = (this + "     ").split(""); // add dummy 5 chars.
    var unsign = function(val) { return (val >= 0) ? val : val % 0x100000000 + 0x100000000; };
    var getArg = function() { return argv[idx ? idx - 1 : next++]; };

    for (; i < s.length - 5; ++i) {
      if (s[i] !== "%") { rv.push(s[i]); continue; }

      ++i, idx = 0, precision = undefined;

      // arg-index-specifier
      if (!isNaN(parseInt(s[i])) && s[i + 1] === "$") { idx = parseInt(s[i]); i += 2; }
      // sign-specifier
      sign = (s[i] !== "#") ? false : ++i, true;
      // width-specifier
      width = (isNaN(parseInt(s[i]))) ? 0 : parseInt(s[i++]);
      // precision-specifier
      if (s[i] === "." && !isNaN(parseInt(s[i + 1]))) { precision = parseInt(s[i + 1]); i += 2; }

      switch (s[i]) {
      case "d": v = parseInt(getArg()).toString(); break;
      case "u": v = parseInt(getArg()); if (!isNaN(v)) { v = unsign(v).toString(); } break;
      case "o": v = parseInt(getArg()); if (!isNaN(v)) { v = (sign ? "0"  : "") + unsign(v).toString(8); } break;
      case "x": v = parseInt(getArg()); if (!isNaN(v)) { v = (sign ? "0x" : "") + unsign(v).toString(16); } break;
      case "X": v = parseInt(getArg()); if (!isNaN(v)) { v = (sign ? "0X" : "") + unsign(v).toString(16).toUpperCase(); } break;
      case "f": v = parseFloat(getArg()).toFixed(precision); break;
      case "c": width = 0; v = getArg(); v = (typeof v === "number") ? String.fromCharCode(v) : NaN; break;
      case "s": width = 0; v = getArg(); if(undefined===v)v=""; v.toString(); if (precision) { v = v.substring(0, precision); } break;
      case "%": width = 0; v = s[i]; break; 
      default:  width = 0; v = "%" + ((width) ? width.toString() : "") + s[i].toString(); break;
      }
      if (isNaN(v)) { v = v.toString(); }
      (v.length < width) ? rv.push(" ".repeat(width - v.length), v) : rv.push(v);
    }
    return rv.join("");
 }
}, // END OF String prototype func.


'Number' : {
 'roundDecimal' : function(p){
  p = p? parseInt(p) : 0;
  var place = Math.pow(10, p);
  return new Number(Math.round(this*place)/place);
 },

 'gt' : function(n2) { return compareNumber(this,n2,'gt') },

 'lt' : function(n2) { return compareNumber(this,n2,'lt') }
}, // END OF Number prototype func.


'Function' : {
 'getFunctionName' : function() {
  return ('name' in this) ? this.name
   : (''+this).replace(/^\s*function\s*([^\x28]*)[\S\s]+$/im, '$1');
 }
}//, // END OF Function prototype func.

} // END OF utility.prototype_functions

if (!window.colorNames) window.colorNames = {
 aqua: [0, 255, 255],
 azure: [240, 255, 255],
 beige: [245, 245, 220],
 black: [0, 0, 0],
 blue: [0, 0, 255],
 brown: [165, 42, 42],
 cyan: [0, 255, 255],
 darkblue: [0, 0, 139],
 darkcyan: [0, 139, 139],
 darkgray: [169, 169, 169],
 darkgreen: [0, 100, 0],
 darkkhaki: [189, 183, 107],
 darkmagenta: [139, 0, 139],
 darkolivegreen: [85, 107, 47],
 darkorange: [255, 140, 0],
 darkorchid: [153, 50, 204],
 darkred: [139, 0, 0],
 darksalmon: [233, 150, 122],
 darkviolet: [148, 0, 211],
 fuchsia: [255, 0, 255],
 gold: [255, 215, 0],
 green: [0, 128, 0],
 indigo: [75, 0, 130],
 khaki: [240, 230, 140],
 lightblue: [173, 216, 230],
 lightcyan: [224, 255, 255],
 lightgreen: [144, 238, 144],
 lightgrey: [211, 211, 211],
 lightpink: [255, 182, 193],
 lightyellow: [255, 255, 224],
 lime: [0, 255, 0],
 magenta: [255, 0, 255],
 maroon: [128, 0, 0],
 navy: [0, 0, 128],
 olive: [128, 128, 0],
 orange: [255, 165, 0],
 pink: [255, 192, 203],
 purple: [128, 0, 128],
 violet: [128, 0, 128],
 red: [255, 0, 0],
 silver: [192, 192, 192],
 white: [255, 255, 255],
 yellow: [255, 255, 0],
 //add colors
 aliceblue: [240, 248, 255],
 antiquewhite: [250, 235, 215],
 aquamarine: [127, 255, 212],
 bisque: [255, 228, 196],
 blanchedalmond: [255, 235, 205],
 blueviolet: [138, 43, 226],
 burlywood: [222, 184, 135],
 cadetblue: [95, 158, 160],
 chartreuse: [127, 255, 0],
 chocolate: [210, 105, 30],
 coral: [255, 127, 80],
 cornflowerblue: [100, 149, 237],
 cornsilk: [255, 248, 220],
 crimson: [220, 20, 60],
 darkgoldenrod: [184, 134, 11],
 darkseagreen: [143, 188, 143],
 darkslateblue: [72, 61, 139],
 darkslategray: [47, 79, 79],
 darkturquoise: [0, 206, 209],
 deeppink: [255, 20, 147],
 deepskyblue: [0, 191, 255],
 dimgray: [105, 105, 105],
 dodgerblue: [30, 144, 255],
 feldspar: [209, 146, 117],
 firebrick: [178, 34, 34],
 floralwhite: [255, 250, 240],
 forestgreen: [34, 139, 34],
 gainsboro: [220, 220, 220],
 ghostwhite: [248, 248, 255],
 goldenrod: [218, 165, 32],
 gray: [128, 128, 128],
 greenyellow: [173, 255, 47],
 honeydew: [240, 255, 240],
 hotpink: [255, 105, 180],
 indianred: [205, 92, 92],
 ivory: [255, 255, 240],
 lavender: [230, 230, 250],
 lavenderblush: [255, 240, 245],
 lawngreen: [124, 252, 0],
 lemonchiffon: [255, 250, 205],
 lightcoral: [240, 128, 128],
 lightgoldenrodyellow: [250, 250, 210],
 lightsalmon: [255, 160, 122],
 lightseagreen: [32, 178, 170],
 lightskyblue: [135, 206, 250],
 lightslateblue: [132, 112, 255],
 lightslategray: [119, 136, 153],
 lightsteelblue: [176, 196, 222],
 limegreen: [50, 205, 50],
 linen: [250, 240, 230],
 mediumaquamarine: [102, 205, 170],
 mediumblue: [0, 0, 205],
 mediumorchid: [186, 85, 211],
 mediumpurple: [147, 112, 216],
 mediumseagreen: [60, 179, 113],
 mediumslateblue: [123, 104, 238],
 mediumspringgreen: [0, 250, 154],
 mediumturquoise: [72, 209, 204],
 mediumvioletred: [199, 21, 133],
 midnightblue: [25, 25, 112],
 mintcream: [245, 255, 250],
 mistyrose: [255, 228, 225],
 moccasin: [255, 228, 181],
 navajowhite: [255, 222, 173],
 oldlace: [253, 245, 230],
 olivedrab: [107, 142, 35],
 orangered: [255, 69, 0],
 orchid: [218, 112, 214],
 palegoldenrod: [238, 232, 170],
 palegreen: [152, 251, 152],
 paleturquoise: [175, 238, 238],
 palevioletred: [216, 112, 147],
 papayawhip: [255, 239, 213],
 peachpuff: [255, 218, 185],
 peru: [205, 133, 63],
 plum: [221, 160, 221],
 powderblue: [176, 224, 230],
 rosybrown: [188, 143, 143],
 royalblue: [65, 105, 225],
 saddlebrown: [139, 69, 19],
 salmon: [250, 128, 114],
 sandybrown: [244, 164, 96],
 seagreen: [46, 139, 87],
 seashell: [255, 245, 238],
 sienna: [160, 82, 45],
 skyblue: [135, 206, 235],
 slateblue: [106, 90, 205],
 slategray: [112, 128, 144],
 snow: [255, 250, 250],
 springgreen: [0, 255, 127],
 steelblue: [70, 130, 180],
 tan: [210, 180, 140],
 teal: [0, 128, 128],
 thistle: [216, 191, 216],
 tomato: [255, 99, 71],
 turquoise: [64, 224, 208],
 violetred: [208, 32, 144],
 wheat: [245, 222, 179],
 whitesmoke: [245, 245, 245],
 yellowgreen: [154, 205, 50]
}
;
if (utility.options['auto_init']) utility.init();
