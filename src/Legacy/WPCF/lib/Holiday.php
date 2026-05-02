<?php
// if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package     CodeIgniter
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license     http://codeigniter.com/user_guide/license.html
 * @link        http://codeigniter.com
 * @since       Version 1.0
 * @filesource
 */
 
// ------------------------------------------------------------------------
 
/**
 * Japanese Holiday Class
 * 
 * 日本の国民の休日・振替休日・定休日
 * 
 * 内閣府「国民の祝日に関する法律」
 * http://www8.cao.go.jp/chosei/shukujitsu/gaiyou.html
 *「春分の日」と「秋分の日」の『海上保安庁水路部 暦計算研究会編 新こよみ便利帳』
 * http://www.h3.dion.ne.jp/~sakatsu/sekki24_topic.htm
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Libraries
 * @author
 * @link        
 */
/*
// コントローラ内の記述例
$this->load->library(array('calendar'));
$config = array(1 => '休日A', 15 => '休日B');
$holidays = $this->holiday->get(2011, 6, $config, TRUE);
 
 
 
*/
class Holiday {
     
    /**
     * 該当月の休日と振替休日を取得
     * 
     * @access public
     * @param integer $year 西暦年
     * @param integer $month 月
     * @param array $data ユーザが定義した休日データ
     * @param bool $flg 振替休日を取得するか否かの真偽値
     * @param string $substitute_public_holiday_name 振替休日を表示する際の名称
     */
    public function get($year = '', $month = '', $data = array(), $flg = TRUE, $substitute_public_holiday_name = '振替休日')
    {
        // 引数を整形
        $year = (int)$year;
        $month = (int)$month;
         
        // 引数が不正の場合 FALSE を返す
        if (
                ! is_int($year) OR
                ! is_int($month) OR $month < 1 OR 12 < $month OR
                ! is_array($data) OR
                ! is_bool($flg) OR
                ! is_string($substitute_public_holiday_name)
            )
        {
            return FALSE;
        }
         
        // 該当月の休日をセット
        $res = $this->set($year, $month);
         
        // 振替休日が不要の場合
        if ( ! $flg)
        {
            // ユーザ定義データで上書きし結果を返す
            return $data + $res;
        }
        // 振替休日が必要 かつ 休日がある場合
        elseif( ! empty($res))
        {
            for($day = 1; $day < date('t',mktime(0, 0, 0, $month, 1, $year)); $day++)
            {
                $time = mktime(0, 0, 0, $month, $day, $year);
                // 日曜日の場合、翌日が振替休日かチェック
                if (date('w', $time) == 0)
                {
                    if(
                        ! empty($res[$day]) AND // 休日
                        empty($res[$day+1]) AND // かつ 翌日が休日ではない
                        $time >= mktime(0, 0, 0, 4, 12, 1973) // かつ 振替休日施行 以降
                        )
                    {
                        // 振替休日を追加
                        $res[$day+1] = $substitute_public_holiday_name;
                    }
                }
            }
        }
        // ユーザ定義データで上書きし結果を返す
        return $data + $res;
    }
     
    /**
     * 該当月の休日を取得
     * 
     * @access private
     * @param integer 西暦年
     * @param integer 月
     */
    private function set($year, $month)
    {
        $res = array();
        $second_monday = $this->second_monday($year, $month);
        $third_monday = $this->third_monday($year, $month);
         
        if(mktime(0,0,0, $month, 20, $year) < mktime(0, 0, 0, 7, 20, 1948))
        {
            return $res;   // 祝日法施行(1948年7月20日)以前
        }
        else
        {
            switch ($month)
            {
                case 1:
                    $res[1] = '元日';
                    if ($year >= 2000)
                    {
                        $res[$second_monday] = '成人の日';
                    }
                    else
                    {
                        $res[15] = '成人の日';
                    }
                    break;
                case 2:
                    if ($year >= 1967)
                    {
                        $res[11] = '建国記念の日';
                    }
                    if ($year == 1989)
                    {
                        $res[24] = '昭和天皇の大喪の礼';
                    }
                    break;
            case 3:
                $day = $this->get_spring_equinox_day($year);
                if ($day)
                {
                    $res[$day] = '春分の日';
                }
                break;
            case 4:
                if ($year >= 2007)
                {
                    $res[29] = '昭和の日';
                }
                else
                {
                    if ($year >= 1989)
                    {
                        $res[29] = 'みどりの日';
                    }
                    else
                    {
                        $res[29] = '天皇誕生日';
                    }
                }
                if ($year == 1959)
                {
                    $res[10] = '皇太子明仁親王の結婚の儀'; // 1959年4月10日
                }
                break;
            case 5:
                $res[3] = '憲法記念日';
                if ($year >= 2007)
                {
                    $res[4] = 'みどりの日';
                }
                else
                {
                    if ($year >= 1986)
                    {
                        // 5月4日が日曜日の場合は『只の日曜』､月曜日の場合は『憲法記念日の振替休日』(〜2006年)
                        if (date('w', mktime(0, 0, 0, 5, 4, $year)) > 1)
                        {
                            $res[4] = '国民の休日';
                        }
                    }
                }
                $res[5] = 'こどもの日';
                if ($year >= 2007)
                {
                    if (date('w',mktime(0, 0, 0, 5, 6, $year)) == 2 OR date('w',mktime(0, 0, 0, 5, 6, $year)) == 3)
                    {
                        $res[6] = '振替休日';    // 5月3日・5月4日が日曜日の場合のみここで判定
                    }
                }
                break;
            case 6:
                if ($year == 1993)
                {
                    $res[9] = '皇太子徳仁親王の結婚の儀';
                }
                break;
            case 7:
                if ($year >= 2003)
                {
                    $res[$third_monday] = '海の日';
                }
                else
                {
                    if ($year >= 1996)
                    {
                        $res[20] = '海の日';
                    }
                }
                break;
            case 9:
                //第3月曜日(15～21)と秋分日(22～24)が重なる事はない
                $day = $this->get_autumnal_equinox_day($year);
                if ($day)
                {
                    $res[$day] = '秋分の日';
                }
                if ($year >= 2003)
                {
                    $res[$third_monday] = '敬老の日';
                    if (date('w',mktime(0, 0, 0, 9, $day, $year)) == 3)
                    {
                        $res[$day-1] = '国民の休日';
                    }
                }
                else
                {
                    if ($year >= 1966)
                    {
                        $res[15] = '敬老の日';
                    }
                }
                break;
            case 10:
                if ($year >= 2000)
                {
                    $res[$second_monday] = '体育の日';
                }
                else
                {
                    if ($year >= 1966)
                    {
                        $res[10] = '体育の日';
                    }
                }
                break;
            case 11:
                $res[3] = '文化の日';
                $res[23] = '勤労感謝の日';
                if ($year == 1990)
                {
                    $res[12] = '即位礼正殿の儀';
                }
                break;
            case 12:
                if ($year >= 1989)
                {
                    $res[23] = '天皇誕生日';
                }
                break;
            }
            return $res;
        }
    }
     
    /**
     * 第2月曜日
     * 
     * @access private
     * @param integer 西暦年
     * @param integer 月
     */
    private function second_monday($year, $month)
    {
        $w = date('N', mktime(0, 0, 0, $month, 1, $year));
        switch($w)
        {
            case 1:
                return 8;
            default:
                return 14 - ($w - 2);  // 9～14
        }
    }
     
    /**
     * 第3月曜日
     * 
     * @access private
     * @param integer 西暦年
     * @param integer 月
     */
    private function third_monday($year, $month)
    {
        $w = date('N', mktime(0, 0, 0, $month, 1, $year));
        switch($w)
        {
            case 1 :
                return 15;
            default :
                return 21 - ($w - 2);  // 16～21
        }
    }
     
    /**
     * 春分の日
     * 
     * @access private
     * @param integer 西暦年
     */
    private function get_spring_equinox_day($year)
    {
        $res = FALSE;
        if ($year <= 1947)
        {
            $res = FALSE; // 祝日法施行前
        }
        else
        {
            if ($year <= 1979)
            {
                $res = intval(20.8357 + (0.242194 * ($year - 1980)) - intval(($year - 1983) / 4));
            }
            else
            {
                if ($year <= 2099)
                {
                    $res = intval(20.8431 + (0.242194 * ($year - 1980)) - intval(($year - 1980) / 4));
                }
                else
                {
                    if ($year <= 2150)
                    {
                        $res = intval(21.851 + (0.242194 * ($year - 1980)) - intval(($year - 1980) / 4));
                    }
                    else
                    {
                        $res = FALSE; // 2151年以降は略算式が無いため不明
                    }
                }
            }
        }
        return $res;
    }
     
    /**
     * 秋分の日
     * 
     * @access private
     * @param integer 西暦年
     */
    private function get_autumnal_equinox_day($year)
    {
        $res = FALSE;
        if ($year <= 1947)
        {
            $res = FALSE; // 祝日法施行前
        }
        else
        {
            if ($year <= 1979)
            {
                $res = intval(23.2588 + (0.242194 * ($year - 1980)) - intval(($year - 1983) / 4));
            }
            else
            {
                if ($year <= 2099)
                {
                    $res = intval(23.2488 + (0.242194 * ($year - 1980)) - intval(($year - 1980) / 4));
                }
                else
                {
                    if ($year <= 2150)
                    {
                        $res = intval(24.2488 + (0.242194 * ($year - 1980)) - (int) (($year - 1980) / 4));
                    }
                    else
                    {
                        $res = FALSE; // 2151年以降は略算式が無いため不明
                    }
                }
            }
        }
        return $res;
    }
     
    /**
     * 該当月の定休日を取得
     * 該当月内の指定された曜日の日を配列で返す
     * 
     * @access public
     * @param integer 西暦年
     * @param integer 月
     * @param array 定休日を指定する曜日（0:日, 1:月, 2:火, 3:水, 4:木, 5:金, 6:土）
     * @param array 定休日だが営業する日
     * @param string 定休日を表示する際の名称
     */
    public function get_regular_holidays($year = '', $month = '', $week_days = array(), $business_days = array(), $business_day_name = '定休日')
    {
        // 引数を整形
        $year = (int)$year;
        $month = (int)$month;
         
        // 引数が不正の場合 FALSE を返す
        if (
                ! is_int($year) OR
                ! is_int($month) OR $month < 1 OR 12 < $month OR
                ! is_array($week_days) OR
                ! is_array($business_days) OR
                ! is_string($business_day_name)
            )
        {
            return FALSE;
        }
         
        // 定休日を格納
        for ($i = 1; $i <= date('t',mktime(0, 0, 0, $month, 1, $year)); $i++)
        {
            $week_day = date('w',mktime(0, 0, 0, $month, $i, $year));
            if (in_array($week_day, $week_days) AND ! in_array($week_day, $business_days))
            {
                $res[$i] = '定休日';
            }
        }
        return $res;
    }
}
 
 
 
// END Holiday class
 
/* End of file Holiday.php */
/* Location: ./application/libraries/Holiday.php */