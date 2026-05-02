<?php
class PresetData extends ClassTemplate {
function __construct() {
$this->param( array(
'zodiac'=>array('Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'),
'zodiac_uc'=>array('ARIES','TAURUS','GEMINI','CANCER','LEO','VIRGO','LIBRA','SCORPIO','SAGITTARIUS','CAPRICORN','AQUARIUS','PISCES'),
'blood_type_abo'=>array('A','B','O','AB'),
'blood_type_abo_group'=>array('Group A','Group B','Group O','Group AB'),
'blood_type_rh'=>array('Rh+','Rh-'),
'monthnames'=>array('January','February','March','April','May','June','July','August','September','October','November','December'),
'monthnames_uc'=>array('JANUARY','FEBRUARY','MARCH','APRIL','MAY','JUNE','JULY','AUGUST','SEPTEMBER','OCTOBER','NOVEMBER','DECEMBER'),
'monthnames_shortened'=>array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
'monthnames_shortened_uc'=>array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'),
'monthnames_uc_shortened'=>array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'),
'daynames'=>array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
'daynames_shortened'=>array('Sun','Mon','Tue','Wed','Thu','Fri','Sat'),
'daynames_uc'=>array('SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY'),
'daynames_uc_shortened'=>array('SUN','MON','TUE','WED','THU','FRI','SAT'),

'zodiac_ja'=>array('牡羊座','牡牛座','双子座','蟹座','獅子座','乙女座','天秤座','蠍座','射手座','山羊座','水瓶座','魚座'),
 'zodiac_ja_kana'=>array('おひつじ座','おうし座','ふたご座','かに座','しし座','おとめ座','てんびん座','さそり座','いて座','やぎ座','みずがめ座','うお座'),
 'zodiac_cn'=>array('白羊宮','金牛宮','双児宮','巨蟹宮','獅子宮','処女宮','天秤宮','天蝎宮','人馬宮','磨羯宮','宝瓶宮','双魚宮'),
 'daynames_ja'=>array('日曜日','月曜日','火曜日','水曜日','木曜日','金曜日','土曜日'),
 'daynames_ja_shortened'=>array('日','月','火','水','木','金','土'),

) );


}
}