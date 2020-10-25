<?php

/**
 * Date Functions that can be used by any page in PGV
 *
 * The functions in this file are common to all PGV pages and include date conversion
 * routines and sorting functions.
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2003  John Finlay and Others
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Id: functions_date.php,v 1.1 2005/10/07 18:08:21 skenow Exp $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (mb_strstr($_SERVER['PHP_SELF'], 'functions_date.php')) {
    print 'Why do you want to do that?';

    exit;
}

if ('hijri' == $CALENDAR_FORMAT || 'arabic' == $CALENDAR_FORMAT) {
    require_once 'includes/functions_date_hijri.php';
}

if ((false !== mb_stristr($CALENDAR_FORMAT, 'hebrew')) || (false !== mb_stristr($CALENDAR_FORMAT, 'jewish')) || $USE_RTL_FUNCTIONS) {
    require_once 'includes/functions_date_hebrew.php';
}

/**
 * convert a date to other languages or formats
 *
 * converts and translates a date based on the selected language and calendar format
 * @param string $dstr_beg prepend this string to the converted date
 * @param string $dstr_end append the string to the converted date
 * @param int    $day      the day of month for the date
 * @param string $month    the abbreviated month (ie JAN, FEB, MAR, etc)
 * @param int    $year     the year (ie 1900, 2004, etc)
 * @return string the new converted date
 */
function convert_date($dstr_beg, $dstr_end, $day, $month, $year)
{
    global $pgv_lang, $DATE_FORMAT, $LANGUAGE, $CALENDAR_FORMAT, $monthtonum, $TEXT_DIRECTION;

    $altDay = 30;

    $month = trim($month);

    $day = trim($day);

    $skipday = false;

    $skipmonth = false;

    if (empty($month) || !isset($monthtonum[mb_strtolower($month)])) {
        $dstr_beg .= ' ' . $month . ' ';

        $month = 'jan';

        $skipmonth = true;
    }

    if (empty($day)) {
        $day = 1;

        if ((!empty($month)) && (isset($monthtonum[$month]))) {
            $yy = $year;

            //-- make sure there is always a year

            if (empty($yy)) {
                $yy = date('Y');
            }

            if (function_exists('cal_days_in_month')) {
                $altDay = cal_days_in_month(CAL_GREGORIAN, $monthtonum[$month], $yy);
            } else {
                $altDay = 30;
            }
        }

        $skipday = true;
    }

    if ('jewish' == $CALENDAR_FORMAT && 'hebrew' != $LANGUAGE && !empty($year) && !(0 == preg_match("/^\d+$/", $year))) {
        $month = $monthtonum[$month];

        $jd = gregoriantojd($month, $day, $year);

        $hebrewDate = jdtojewish($jd);

        [$hebrewMonth, $hebrewDay, $hebrewYear] = preg_split('/', $hebrewDate);

        $altJd = gregoriantojd($month, $altDay, $year);

        $altHebrewDate = jdtojewish($altJd);

        [$altHebrewMonth, $altHebrewDay, $altHebrewYear] = preg_split('/', $altHebrewDate);

        $hebrewMonthName = getJewishMonthName($hebrewMonth, $hebrewYear);

        if ($skipday && !$skipmonth && 0 != $altHebrewMonth && 0 != $altHebrewYear && $hebrewMonth != $altHebrewMonth && $hebrewYear != $altHebrewYear) { //elul tishrai
            $hebrewMonthName .= ' ';

            $hebrewMonthName .= $hebrewYear;

            $hebrewYear = ' / ';

            $hebrewYear .= getJewishMonthName($altHebrewMonth, $altHebrewYear);

            $hebrewYear .= ' ';

            $hebrewYear .= $altHebrewYear;
        } elseif ($skipday && !$skipmonth && 0 != $altHebrewMonth && $hebrewMonth != $altHebrewMonth) {
            $hebrewMonthName .= ' / ';

            $hebrewMonthName .= getJewishMonthName($altHebrewMonth, $altHebrewYear);
        } elseif (0 != $altHebrewYear && $hebrewYear != $altHebrewYear && $skipday) {
            $hebrewYear .= ' / ';

            $hebrewYear .= $altHebrewYear;
        }

        if ($skipday) {
            $hebrewDay = '';
        }

        if ($skipmonth) {
            $hebrewMonthName = '';
        }

        if ('D. M Y' == $DATE_FORMAT && $skipday) {
            $newdate = str_replace("D", $hebrewDay, 'D M Y');
        } else {
            $newdate = str_replace("D", $hebrewDay, $DATE_FORMAT);
        }

        $newdate = str_replace("M", $hebrewMonthName, $newdate);

        $newdate = str_replace("Y", $hebrewYear, $newdate);

        $datestr = $dstr_beg . $newdate . $dstr_end;
    } elseif ('jewish_and_gregorian' == $CALENDAR_FORMAT && 'hebrew' != $LANGUAGE && !empty($year) && !(0 == preg_match("/^\d+$/", $year))) {
        $monthnum = $monthtonum[$month];

        $jd = gregoriantojd($monthnum, $day, $year);

        $hebrewDate = jdtojewish($jd);

        [$hebrewMonth, $hebrewDay, $hebrewYear] = preg_split('/', $hebrewDate);

        $altJd = gregoriantojd($monthnum, $altDay, $year);

        $altHebrewDate = jdtojewish($altJd);

        [$altHebrewMonth, $altHebrewDay, $altHebrewYear] = preg_split('/', $altHebrewDate);

        $hebrewMonthName = getJewishMonthName($hebrewMonth, $hebrewYear);

        if ($skipday && !$skipmonth && 0 != $altHebrewMonth && 0 != $altHebrewYear && $hebrewMonth != $altHebrewMonth && $hebrewYear != $altHebrewYear) { //elul tishrai
            $hebrewMonthName .= ' ';

            $hebrewMonthName .= $hebrewYear;

            $hebrewYear = ' / ';

            $hebrewYear .= getJewishMonthName($altHebrewMonth, $altHebrewYear);

            $hebrewYear .= ' ';

            $hebrewYear .= $altHebrewYear;
        } elseif ($skipday && !$skipmonth && 0 != $altHebrewMonth && $hebrewMonth != $altHebrewMonth) {
            $hebrewMonthName .= ' / ';

            $hebrewMonthName .= getJewishMonthName($altHebrewMonth, $altHebrewYear);
        } elseif (0 != $altHebrewYear && $hebrewYear != $altHebrewYear && $skipday) {
            $hebrewYear .= ' / ';

            $hebrewYear .= $altHebrewYear;
        }

        if ($skipday) {
            $hebrewDay = '';
        }

        if ($skipmonth) {
            $hebrewMonthName = '';
        }

        if (!empty($year)) {
            if ('D. M Y' == $DATE_FORMAT && $skipday) {
                $newdate = str_replace("D", $hebrewDay, 'D M Y');
            } else {
                $newdate = str_replace("D", $hebrewDay, $DATE_FORMAT);
            }

            $newdate = str_replace("M", $hebrewMonthName, $newdate);

            $newdate = str_replace("Y", $hebrewYear, $newdate);
        } else {
            $newdate = '';
        }

        if ($skipday) {
            $day = '';
        }

        if ($skipmonth) {
            $month = '';
        }

        if ('D. M Y' == $DATE_FORMAT && $skipday) {
            $gdate = str_replace("D", $day, 'D M Y');
        } else {
            $gdate = str_replace("D", $day, $DATE_FORMAT);
        }

        $gdate = str_replace("M", $month, $gdate);

        $gdate = str_replace("Y", $year, $gdate);

        $gdate = trim($gdate);

        $datestr = $dstr_beg . $newdate . " ($gdate)" . $dstr_end;
    } elseif (('hebrew' == $CALENDAR_FORMAT || ('jewish' == $CALENDAR_FORMAT && 'hebrew' == $LANGUAGE)) && !empty($year) && !(0 == preg_match("/^\d+$/", $year))) {
        $month = $monthtonum[$month];

        $jd = gregoriantojd($month, $day, $year);

        $hebrewDate = jdtojewish($jd);

        [$hebrewMonth, $hebrewDay, $hebrewYear] = preg_split('/', $hebrewDate);

        $altJd = gregoriantojd($month, $altDay, $year);

        $altHebrewDate = jdtojewish($altJd);

        [$altHebrewMonth, $altHebrewDay, $altHebrewYear] = preg_split('/', $altHebrewDate);

        if ($skipday) {
            $hebrewDay = '';
        }

        if ($skipmonth) {
            $hebrewMonth = '';
        }

        $newdate = getFullHebrewJewishDates($hebrewYear, $hebrewMonth, $hebrewDay, $altHebrewYear, $altHebrewMonth);

        $datestr = $dstr_beg . $newdate . $dstr_end;
    } elseif (('hebrew_and_gregorian' == $CALENDAR_FORMAT || ('jewish_and_gregorian' == $CALENDAR_FORMAT && 'hebrew' == $LANGUAGE)) && !empty($year) && !(0 == preg_match("/^\d+$/", $year))) {
        $monthnum = $monthtonum[$month];

        //if (preg_match("/^\d+$/", $year)==0) $year = date("Y");

        $jd = gregoriantojd($monthnum, $day, $year);

        $hebrewDate = jdtojewish($jd);

        [$hebrewMonth, $hebrewDay, $hebrewYear] = preg_split('/', $hebrewDate);

        $altJd = gregoriantojd($monthnum, $altDay, $year);

        $altHebrewDate = jdtojewish($altJd);

        [$altHebrewMonth, $altHebrewDay, $altHebrewYear] = preg_split('/', $altHebrewDate);

        if ($skipday) {
            $hebrewDay = '';
        }

        if ($skipmonth) {
            $hebrewMonth = '';
        }

        if (!empty($year)) {
            $newdate = getFullHebrewJewishDates($hebrewYear, $hebrewMonth, $hebrewDay, $altHebrewYear, $altHebrewMonth);
        } else {
            $newdate = '';
        }

        if ($skipday) {
            $day = '';
        }

        if ($skipmonth) {
            $month = '';
        }

        if ('D. M Y' == $DATE_FORMAT && $skipday) {
            $gdate = str_replace("D", $day, 'D M Y');
        } else {
            $gdate = str_replace("D", $day, $DATE_FORMAT);
        }

        $gdate = str_replace("M", $month, $gdate);

        $gdate = str_replace("Y", $year, $gdate);

        $gdate = trim($gdate);

        $datestr = $dstr_beg . ' ' . $newdate . " ($gdate) " . $dstr_end;
    } elseif ('julian' == $CALENDAR_FORMAT) {
        $monthnum = $monthtonum[$month];

        $jd = gregoriantojd($monthnum, $day, $year);

        $jDate = jdtojulian($jd);

        [$jMonth, $jDay, $jYear] = preg_split('/', $jDate);

        $jMonthName = jdmonthname($jd, 3);

        if ($skipday) {
            $jDay = '';
        }

        if ($skipmonth) {
            $jMonthName = '';
        }

        $newdate = str_replace("D", $jDay, $DATE_FORMAT);

        $newdate = str_replace("M", $jMonthName, $newdate);

        $newdate = str_replace("Y", $jYear, $newdate);

        $datestr = $dstr_beg . $newdate . $dstr_end;
    } elseif ('hijri' == $CALENDAR_FORMAT) {
        $monthnum = $monthtonum[$month];

        $hDate = getHijri($day, $monthnum, $year);

        [$hMonthName, $hDay, $hYear] = preg_split('/', $hDate);

        if ($skipday) {
            $hDay = '';
        }

        if ($skipmonth) {
            $hMonthName = '';
        }

        $newdate = str_replace("D", $hDay, $DATE_FORMAT);

        $newdate = str_replace("M", $hMonthName, $newdate);

        $newdate = str_replace("Y", $hYear, $newdate);

        $datestr = $dstr_beg . '<span dir="rtl" lang="ar-sa">' . $newdate . '</span>';

        if ('ltr' == $TEXT_DIRECTION) { //only do this for ltr languages
            $datestr .= '&lrm;'; //add entity to return to left to right direction
        }

        $datestr .= $dstr_end;
    } elseif ('arabic' == $CALENDAR_FORMAT) {
        $monthnum = $monthtonum[$month];

        $aDate = getArabic($day, $monthnum, $year);

        [$aMonthName, $aDay, $aYear] = preg_split('/', $aDate);

        if ($skipday) {
            $aDay = '';
        }

        if ($skipmonth) {
            $aMonthName = '';
        }

        $newdate = str_replace("D", $aDay, $DATE_FORMAT);

        $newdate = str_replace("M", $aMonthName, $newdate);

        $newdate = str_replace("Y", $aYear, $newdate);

        $datestr = $dstr_beg . '<span dir="rtl" lang="ar-sa">' . $newdate . '</span>';

        if ('ltr' == $TEXT_DIRECTION) { //only do this for ltr languages
            $datestr .= '&lrm;'; //add entity to return to left to right direction
        }

        $datestr .= $dstr_end;
    } elseif ('french' == $CALENDAR_FORMAT) {
        $monthnum = $monthtonum[$month];

        $jd = gregoriantojd($monthnum, $day, $year);

        $frenchDate = jdtofrench($jd);

        [$fMonth, $fDay, $fYear] = preg_split('/', $frenchDate);

        $fMonthName = jdmonthname($jd, 5);

        if ($skipday) {
            $fDay = '';
        }

        if ($skipmonth) {
            $fMonthName = '';
        }

        $newdate = str_replace("D", $fDay, $DATE_FORMAT);

        $newdate = str_replace("M", $fMonthName, $newdate);

        $newdate = str_replace("Y", $fYear, $newdate);

        $datestr = $dstr_beg . $newdate . $dstr_end;
    } else {
        $temp_format = '~' . $DATE_FORMAT;

        if ($skipday) {
            //-- if the D is before the M the get the substr of everthing after the M

            //-- if the D is after the M then just replace it

            //-- @TODO figure out how to replace D. anywhere in the string

            $pos1 = mb_strpos($temp_format, 'M');

            $pos2 = mb_strpos($temp_format, 'D');

            if ($pos2 < $pos1) {
                $temp_format = mb_substr($temp_format, $pos1 - 1);
            } else {
                $temp_format = str_replace("D", '', $temp_format);
            }
        }

        if ($skipmonth) {
            $month = '';

            $dpos_d_01 = mb_strpos($temp_format, 'M');

            $dpos_d_00 = $dpos_d_01;

            $dpos_d_02 = mb_strlen($temp_format);

            if ($dpos_d_01 > 0) {
                while (!mb_strpos('DY', $temp_format[$dpos_d_01])) {
                    $temp_format01 = mb_substr($temp_format, 0, $dpos_d_00);

                    $temp_format02 = mb_substr($temp_format, $dpos_d_01);

                    $temp_format = $temp_format01 . $temp_format02;

                    $dpos_d_02 = mb_strlen($temp_format);

                    $dpos_d_01++;

                    if ($dpos_d_01 >= $dpos_d_02) {
                        break;
                    }
                }
            }
        }

        $newdate = trim(mb_substr($temp_format, 1));

        if ('chinese' == $LANGUAGE) {
            $day = convert_number($day);

            $yearStr = '' . $year;

            $year = '';

            for ($i = 0, $iMax = mb_strlen($yearStr); $i < $iMax; $i++) {
                $year .= convert_number($yearStr[$i]);
            }
        }

        $newdate = str_replace("D", $day, $newdate);

        $newdate = str_replace("M", $month, $newdate);

        $newdate = str_replace("Y", $year, $newdate);

        $datestr = $dstr_beg . $newdate . $dstr_end;
    }

    return $datestr;
}

//-- end of Jewish date functions

//-- functions to take a date and display it in Finnish.
//-- provided by: KurtNorgaz
//-- updated by Meliza
function getFinnishDate($datestr, $day)
{
    global $pgv_lang;

    //-- the Finnish text of the value for one date is shown at the end of the date

    //-- the Finnish values of two dates are replaced by a -

    $array_short = ['aft', 'bet', 'from', 'to'];

    foreach ($array_short as $indexval => $value) {
        $oldDateStr = $datestr;

        $newdatestr = preg_replace("/$value([^a-zA-Z])/i", '' . '$1', $datestr);

        if ($newdatestr != $datestr) {
            $datestr = $newdatestr;

            switch ($value) {
                case 'from':
                    $datestr = trim($datestr);
                    $temp_date = mb_strtolower($datestr);
                    $pos_of_to = mb_strpos(' ' . $temp_date, 'to');
                    $newdatestr = str_replace("to", '', $temp_date);
                    if ($newdatestr != $temp_date) {
                        $datestr_01 = trim(mb_substr($datestr, 0, $pos_of_to - 2));

                        $datestr_02 = mb_substr($datestr, $pos_of_to + 1);

                        $datestr = $datestr_01 . ' - ' . $datestr_02 . ' ';
                    } else {
                        $datestr .= ' ' . $pgv_lang[$value];
                    }
                    break;
                case 'bet':
                    $datestr = trim($datestr);
                    $temp_date = mb_strtolower($datestr);
                    $pos_of_and = mb_strpos(' ' . $temp_date, 'and');
                    $datestr_01 = trim(mb_substr($datestr, 0, $pos_of_and - 2));
                    $datestr_02 = mb_substr($datestr, $pos_of_and + 2);
                    if (mb_strlen($datestr_01) > 0 && mb_strlen($datestr_02) > 0) {
                        $datestr = $datestr_01 . ' - ' . $datestr_02 . ' ';
                    }
                    break;
                case 'to':
                    $datestr = $newdatestr . ' ' . $pgv_lang[$value];
                    break;
                case 'aft':
                    $datestr = $newdatestr . ' ' . $pgv_lang[$value];
                    break;
                default:
                    $datestr = $oldDateStr;
                    break;
            }
        }
    }

    //-- the Finnish text of the value is shown bau before the date

    $array_short = ['abt', 'apx', 'bef', 'cal', 'est', 'int', 'cir'];

    foreach ($array_short as $indexval => $value) {
        $datestr = preg_replace("/^$value([^a-zA-Z])/i", $pgv_lang[$value] . '$1', $datestr);

        $datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", '$1' . $pgv_lang[$value] . '$2', $datestr);
    }

    //-- Constant 'ta' is appended to the Finnish month values, if a day value exists (for the last date)

    $array_short = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

    foreach ($array_short as $indexval => $value) {
        if ($day > 0) {
            $datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", '$1' . $pgv_lang[$value] . 'ta' . '$2', $datestr);

            $datestr = preg_replace("/^$value([^a-zA-Z])/i", $pgv_lang[$value] . 'ta' . '$1', $datestr);
        } else {
            $datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", '$1' . $pgv_lang[$value] . '$2', $datestr);

            $datestr = preg_replace("/^$value([^a-zA-Z])/i", $pgv_lang[$value] . '$1', $datestr);
        }
    }

    return $datestr;
}

//-- end of Finnish date functions

//-- functions to take a date and display it in Turkish.
//-- provided by: KurtNorgaz
function getTurkishDate($datestr)
{
    global $pgv_lang;

    $array_short = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'est'];

    foreach ($array_short as $indexval => $value) {
        $datestr = preg_replace("/$value([^a-zA-Z])/i", $pgv_lang[$value] . '$1', $datestr);
    }

    $array_short = ['abt', 'aft', 'and', 'bef', 'bet', 'cal', 'from', 'int', 'to', 'cir'];

    foreach ($array_short as $indexval => $value) {
        $oldDateStr = $datestr;

        $newdatestr = preg_replace("/$value([^a-zA-Z])/i", '' . '$1', $datestr);

        if ($newdatestr != $datestr) {
            $pos_of_value = mb_strpos(' ' . $datestr, $value);

            $datestr = $newdatestr;

            switch ($value) {
                case 'from':
                    $datestr = trim($datestr);
                    $pos_of_to = mb_strpos(' ' . $datestr, 'to');
                    $datestr_01 = trim(mb_substr($datestr, 0, $pos_of_to - 1));
                    $datestr_02 = mb_substr($datestr, $pos_of_to - 2);

                    if (mb_strlen($datestr_01) > 0) {
                        $last_char = $datestr[mb_strlen($datestr_01) - 1];
                    } else {
                        $last_char = '';
                    }
                    switch ($last_char) {
                        case '0':
                            if (mb_strlen($datestr_01) > 1) {
                                $last_two_char = mb_substr($datestr_01, -2);
                            } else {
                                $last_two_char = '';
                            }
                            switch ($last_two_char) {
                                case '00':
                                    $extension = 'den';
                                    break;
                                case '20':
                                    $extension = 'den';
                                    break;
                                case '50':
                                    $extension = 'den';
                                    break;
                                case '70':
                                    $extension = 'den';
                                    break;
                                case '80':
                                    $extension = 'den';
                                    break;
                                default:
                                    $extension = 'dan';
                                    break;
                            }
                            break;
                        case '6':
                            $extension = 'dan';
                            break;
                        case '9':
                            $extension = 'dan';
                            break;
                        default:
                            $extension = 'den';
                            break;
                    }
                    $datestr_01 .= stripslashes($pgv_lang[$value]);
                    $datestr_01 = str_replace('#EXT#', $extension, $datestr_01);

                    $datestr = $datestr_01 . $datestr_02;
                    break;
                case 'to':
                    $datestr = trim($datestr);
                    if (mb_strlen($datestr) > 0) {
                        $last_char = $datestr[mb_strlen($datestr) - 1];
                    } else {
                        $last_char = '';
                    }
                    switch ($last_char) {
                        case '0':
                            $extension = 'a';
                            break;
                        case '9':
                            $extension = 'a';
                            break;
                        case '2':
                            $extension = 'ye';
                            break;
                        case '7':
                            $extension = 'ye';
                            break;
                        case '6':
                            $extension = 'ya';
                            break;
                        default:
                            $extension = 'e';
                            break;
                    }
                    $datestr .= stripslashes($pgv_lang[$value]);
                    $datestr = str_replace('#EXT#', $extension, $datestr);
                    break;
                case 'bef':
                    $datestr = trim($datestr);
                    if (mb_strlen($datestr) > 0) {
                        $last_char = $datestr[mb_strlen($datestr) - 1];
                    } else {
                        $last_char = '';
                    }
                    switch ($last_char) {
                        case '0':
                            if (mb_strlen($datestr) > 1) {
                                $last_two_char = mb_substr($datestr, -2);
                            } else {
                                $last_two_char = '';
                            }
                            switch ($last_two_char) {
                                case '00':
                                    $extension = 'den';
                                    break;
                                case '20':
                                    $extension = 'den';
                                    break;
                                case '50':
                                    $extension = 'den';
                                    break;
                                case '70':
                                    $extension = 'den';
                                    break;
                                case '80':
                                    $extension = 'den';
                                    break;
                                default:
                                    $extension = 'dan';
                                    break;
                            }
                            break;
                        case '6':
                            $extension = 'dan';
                            break;
                        case '9':
                            $extension = 'dan';
                            break;
                        default:
                            $extension = 'den';
                            break;
                    }
                    $datestr .= stripslashes($pgv_lang[$value]);
                    $datestr = str_replace('#EXT#', $extension, $datestr);
                    break;
                case 'cir':
                    $datestr .= stripslashes($pgv_lang[$value]);
                    break;
                default:
                    $datestr = $oldDateStr;
                    break;
            }
        }
    }

    return $datestr;
}

//-- end of Turkish date functions

/**
 * parse a gedcom date
 *
 * this function will parse a gedcom date and convert it to the form defined by the language file
 * by calling the convert_date function
 * @param string $datestr the date string (ie everything after the DATE tag)
 * @return string the new date string
 */
function get_changed_date($datestr)
{
    global $pgv_lang, $DATE_FORMAT, $LANGUAGE, $CALENDAR_FORMAT, $monthtonum, $dHebrew;

    $checked_dates = [];

    $datestr = trim($datestr);

    // INFANT CHILD STILLBORN DEAD DECEASED Y ...

    if (0 == preg_match("/\d/", $datestr)) {
        if (isset($pgv_lang[$datestr])) {
            return $pgv_lang[$datestr];
        }

        return $pgv_lang[str2upper($datestr)] ?? $pgv_lang[str2lower($datestr)] ?? $datestr; // no digit ?
    }

    // need day of the week ?

    if (!mb_strpos($datestr, '#') && (mb_strpos($DATE_FORMAT, 'F') or mb_strpos($DATE_FORMAT, 'd') or mb_strpos($DATE_FORMAT, 'j'))) {
        $dateged = '';

        $pdate = parse_date($datestr);

        $i = 0;

        while (!empty($pdate[$i]['year'])) {
            $day = @$pdate[$i]['day'];

            $mon = @$pdate[$i]['mon'];

            $year = $pdate[$i]['year'];

            if (!empty($day)) {
                if (!defined('ADODB_DATE_VERSION')) {
                    require 'adodb-time.inc.php';
                }

                $fmt = $DATE_FORMAT; // D j F Y

                $adate = adodb_date($fmt, adodb_mktime(0, 0, 0, $mon, $day, $year));
            } elseif (!empty($mon)) {
                $adate = $pgv_lang[mb_strtolower($pdate[$i]['month'])] . ' ' . $year;
            } else {
                $adate = $year;
            }

            // already in english !

            if ('english' != $LANGUAGE) {
                foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $indexval => $item) {
                    // February => Février

                    $translated = $pgv_lang[mb_substr(mb_strtolower($item), 0, 3)];

                    $adate = str_replace($item, $translated, $adate);

                    // Feb => Fév

                    $item = mb_substr($item, 0, 3);

                    $translated = mb_substr($translated, 0, 3);

                    $adate = str_replace($item, $translated, $adate);
                }

                foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $indexval => $item) {
                    // Friday => Vendredi

                    $translated = $pgv_lang[mb_strtolower($item)];

                    $adate = str_replace($item, $translated, $adate);

                    // Fri => Ven

                    $item = mb_substr($item, 0, 3);

                    $translated = mb_substr($translated, 0, 3);

                    $adate = str_replace($item, $translated, $adate);
                }
            }

            // french first day of month

            if ('french' == $LANGUAGE) {
                $adate = str_replace(' 1 ', ' 1er ', $adate);
            }

            // french calendar from 22 SEP 1792 to 31 DEC 1805

            if ('french' == $LANGUAGE and !empty($day)) {
                if (($year > 1792 and $year < 1806) or (1792 == $year and ($mon > 9 or (9 == $mon and $day > 21)))) {
                    $jd = gregoriantojd($mon, $day, $year);

                    $frenchDate = jdtofrench($jd);

                    [$fMonth, $fDay, $fYear] = preg_split('/', $frenchDate);

                    $fMonthName = jdmonthname($jd, 5);

                    $adate .= " <nobr>&nbsp;<u>$fDay $fMonthName An $fYear</u>&nbsp;</nobr>";
                }
            }

            if (isset($pdate[$i]['ext'])) {
                $txt = mb_strtolower($pdate[$i]['ext']);

                $txt = $pgv_lang[$txt] ?? $pdate[$i]['ext'];

                $adate = $txt . ' ' . $adate . ' ';
            }

            $dateged .= $adate;

            $i++;
        }

        if (!empty($dateged)) {
            return trim($dateged);
        }
    }

    //-- Is the date a Hebrew date

    if (mb_stristr($datestr, '#DHEBREW')) {
        $dHebrew = 1;

        $datestr = preg_replace('/@([#A-Z]+)@/', '', $datestr);
    } else {
        $dHebrew = 0;
    }

    //-- check for DAY MONTH YEAR dates

    $Dt = '';

    $ct = preg_match_all("/(\d{1,2}\s)?([a-zA-Z]{3})?\s?(\d{4})?/", $datestr, $match, PREG_SET_ORDER);

    for ($i = 0; $i < $ct; $i++) {
        $match[$i][0] = trim($match[$i][0]);

        if ((!empty($match[$i][0])) && (!in_array($match[$i][0], $checked_dates, true))) {
            if (!empty($match[$i][1])) {
                $day = trim($match[$i][1]);
            } else {
                $day = '';
            }

            if (!empty($match[$i][2])) {
                $month = mb_strtolower($match[$i][2]);
            } else {
                $month = '';
            }

            if (isset($monthtonum[$month]) && (0 == preg_match('/' . $month . '[a-z]/i', $datestr))) {
                $checked_dates[] = $match[$i][0];

                if (!empty($match[$i][3])) {
                    $year = $match[$i][3];
                } else {
                    $year = '';
                }

                $pos1 = mb_strpos($datestr, $match[$i][0]);

                $pos2 = $pos1 + mb_strlen($match[$i][0]);

                $dstr_beg = mb_substr($datestr, 0, $pos1);

                $dstr_end = mb_substr($datestr, $pos2);

                //-- sometimes with partial dates a space char is found in the match and not added to the dstr_beg string

                //-- the following while loop will check for spaces at the start of the match and add them to the dstr_beg

                $j = 0;

                while (($j < mb_strlen($match[$i][0])) && (' ' == $match[$i][0][$j])) {
                    $dstr_beg .= ' ';

                    $j++;
                }

                //<-- Day zero-suppress

                if ($day > 0 && $day < 10) {
                    $day = str_replace("0", '' . '$1', $day);
                }

                if (!$dHebrew) {
                    $datestr = convert_date($dstr_beg, $dstr_end, $day, $month, $year);

                    if ('' != $day) {
                        $Dt = $day;
                    }
                } else {
                    if (!function_exists('convert_hdate')) {
                        require_once 'includes/functions_date_hebrew.php';
                    }

                    $datestr = convert_hdate($dstr_beg, $dstr_end, $day, $month, $year);

                    $Dt = '';
                }
            } else {
                $month = '';
            }
        }
    }

    if (!isset($month)) {
        $month = '';
    }

    //-- search for just years because the above code will only allow dates with a valid month to pass

    //-- this will make sure years get converted for non romanic alphabets such as hebrew

    $ct = preg_match_all("/.?(\d\d\d\d)/", $datestr, $match, PREG_SET_ORDER);

    if ((false !== mb_stristr($CALENDAR_FORMAT, 'hebrew')) || (false !== mb_stristr($CALENDAR_FORMAT, 'jewish')) || ($dHebrew)) {
        $checked_dates_str = implode(',', $checked_dates);

        for ($i = 0; $i < $ct; $i++) {
            $match[$i][0] = trim($match[$i][0]);

            if ((!empty($match[$i][0])) && (false === mb_stristr($checked_dates_str, $match[$i][0])) && (false === mb_strstr($match[$i][0], '#'))) {
                $checked_dates_str .= ', ' . $match[$i][0];

                $day = '';

                $month = '';

                $year = $match[$i][1];

                if ($year < 4000) {
                    $pos1 = mb_strpos($datestr, $match[$i][0]);

                    $pos2 = $pos1 + mb_strlen($match[$i][0]);

                    $dstr_beg = mb_substr($datestr, 0, $pos1);

                    $dstr_end = mb_substr($datestr, $pos2);

                    if (!$dHebrew) {
                        $datestr = convert_date($dstr_beg, $dstr_end, $day, $month, $year);
                    } else {
                        $datestr = convert_hdate($dstr_beg, $dstr_end, $day, $month, $year);
                    }
                }
            }
        }
    } elseif ('hijri' == $CALENDAR_FORMAT) {
        $checked_dates_str = implode(',', $checked_dates);

        for ($i = 0; $i < $ct; $i++) {
            $match[$i][0] = trim($match[$i][0]);

            if ((!empty($match[$i][0])) && (false === mb_stristr($checked_dates_str, $match[$i][0])) && (false === mb_strstr($match[$i][0], '#')) && (false === mb_stristr($datestr, $match[$i][0] . '</span>'))) {
                $checked_dates_str .= ', ' . $match[$i][0];

                $day = '';

                $month = '';

                $year = $match[$i][1];

                //if ($year<4000) {

                $pos1 = mb_strpos($datestr, $match[$i][0]);

                $pos2 = $pos1 + mb_strlen($match[$i][0]);

                $dstr_beg = mb_substr($datestr, 0, $pos1);

                $dstr_end = mb_substr($datestr, $pos2);

                $datestr = convert_date($dstr_beg, $dstr_end, $day, $month, $year);

                //}
            }
        }
    }

    if ('turkish' == $LANGUAGE) {
        $datestr = getTurkishDate($datestr);
    } else {
        if ('finnish' == $LANGUAGE) {
            $datestr = getFinnishDate($datestr, $Dt);
        } else {
            $array_short = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'abt', 'aft', 'and', 'bef', 'bet', 'cal', 'est', 'from', 'int', 'to', 'cir', 'apx'];

            foreach ($array_short as $indexval => $value) {
                $datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", '$1' . $pgv_lang[$value] . '$2', $datestr);

                $datestr = preg_replace("/^$value([^a-zA-Z])/i", $pgv_lang[$value] . '$1', $datestr);
            }
        }
    }

    return $datestr;
}

/**
 * create an anchor url to the calendar for a date
 *
 * create an anchor url to the calendar for a date and parses the date using the get changed date
 * function
 * @param string $datestr the date string (ie everything after the DATE tag)
 * @return string a converted date with anchor html tags around it <a href="">date</a>
 * @author Roland (botak)
 */
function get_date_url($datestr)
{
    global $monthtonum;

    if (!mb_stristr($datestr, '#DHEBREW')) {
        //		Commented out 2 lines as I don't know why they are here

        //		$datestrip = preg_replace("/[a-zA-Z]/", "", $datestr);

        //		$datestrip = trim($datestrip);

        $checked_dates = [];

        //-- added trim to datestr to fix broken links produced by matches to a single space char

        $ct = preg_match_all("/(\d{1,2}\s)?([a-zA-Z]{3})?\s?(\d{3,4})?/", trim($datestr), $match, PREG_SET_ORDER);

        for ($i = 0; $i < $ct; $i++) {
            if ('bet' == mb_substr(mb_strtolower(trim($datestr)), 0, 3)) {    // Checks if date begin with bet(ween)
                $cb = preg_match_all("/ (\d\d\d\d|\d\d\d)/", trim($datestr), $match_bet, PREG_SET_ORDER);

                if (!empty($match_bet[0][0])) {
                    $start_year = trim($match_bet[0][0]);
                } else {
                    $start_year = '';
                }

                if (!empty($match_bet[1][0])) {
                    $end_year = trim($match_bet[1][0]);
                } else {
                    $end_year = '';
                }

                if ($start_year > $end_year) {
                    $datelink = $start_year;

                    $start_year = $end_year;

                    $end_year = $datelink;
                }

                $datelink = '<a class="date" href="calendar.php?';

                if (isset($start_year) && mb_strlen($start_year) > 0) {
                    $datelink .= 'year=' . $start_year;
                }

                if ((isset($end_year) && mb_strlen($end_year) > 0) && (isset($start_year) && mb_strlen($start_year) > 0)) {
                    $datelink .= '-';
                }

                if (isset($end_year) && mb_strlen($end_year) > 0) {
                    $datelink .= $end_year;
                }

                $datelink .= '&amp;filterof=all&amp;action=year">';

                $datelink .= get_changed_date($datestr) . '</a>';
            } else {
                $match[$i][0] = trim($match[$i][0]);

                if ((!empty($match[$i][0])) && (!in_array($match[$i][0], $checked_dates, true))) {
                    $checked_dates[] = $match[$i][0];

                    if (!empty($match[$i][1])) {
                        $day = trim($match[$i][1]);
                    } else {
                        $day = '';
                    }

                    if (!empty($match[$i][2])) {
                        $month = mb_strtolower($match[$i][2]);
                    } else {
                        $month = '';
                    }

                    if (!isset($monthtonum[$month])) {
                        $month = '';
                    } // abt is not a month !

                    if (!empty($match[$i][3])) {
                        $year = $match[$i][3];
                    } else {
                        $year = '';
                    }

                    $datelink = '<a class="date" href="calendar.php?';

                    if (isset($day) && mb_strlen($day) > 0) {
                        $datelink .= 'day=' . $day . '&amp;';
                    }

                    if (isset($month) && mb_strlen($month) > 0) {
                        $datelink .= 'month=' . $month . '&amp;';
                    }

                    if (isset($year) && mb_strlen($year) > 0) {
                        $datelink .= 'year=' . $year . '&amp;';
                    }

                    $datelink .= 'filterof=all&amp;action=';

                    if (!empty($day)) {
                        $datelink .= 'today">';
                    } elseif (empty($day) && !empty($year)) {
                        $datelink .= 'year">';
                    } else {
                        $datelink .= '">';
                    }

                    $datelink .= get_changed_date($datestr) . '</a>';
                }
            }
        }

        if (!isset($datelink)) {
            $datelink = '';
        }

        return $datelink;
    }

    $datelink = get_changed_date($datestr);

    return $datelink;
}

/**
 * get an individuals age at the given date
 *
 * get an individuals age at the given date
 * @param string $indirec the individual record so that we can get the birth date
 * @param string $datestr the date string (everything after DATE) to calculate the age for
 * @param int    $style   optional style (default 1=HTML style)
 * @return string the age in a string
 */
function get_age($indirec, $datestr, $style = 1)
{
    global $pgv_lang, $monthtonum, $USE_RTL_FUNCTIONS;

    $estimates = ['abt', 'aft', 'bef', 'est', 'cir'];

    $realbirthdt = '';

    $bdatestr = '';

    //-- get birth date for age calculations

    $bpos1 = mb_strpos($indirec, '1 BIRT');

    if ($bpos1) {
        $index = 1;

        $birthrec = get_sub_record(1, '1 BIRT', $indirec, $index);

        while (!empty($birthrec)) {
            $hct = preg_match('/2 DATE.*(@#DHEBREW@)/', $birthrec, $match);

            if ($hct > 0) {
                $dct = preg_match('/2 DATE (.+)/', $birthrec, $match);

                $hebrew_birthdate = parse_date(trim($match[1]));

                if ($USE_RTL_FUNCTIONS && 1 == $index) {
                    $birthdate = jewishGedcomDateToGregorian($hebrew_birthdate);
                }
            } else {
                $dct = preg_match('/2 DATE (.+)/', $birthrec, $match);

                if ($dct > 0) {
                    $birthdate = parse_date(trim($match[1]));
                }
            }

            $index++;

            $birthrec = get_sub_record(1, '1 BIRT', $indirec, $index);
        }
    }

    $convert_hebrew = false;

    //-- check if it is a hebrew date

    $hct = preg_match('/@#DHEBREW@/', $datestr, $match);

    if ($USE_RTL_FUNCTIONS && $hct > 0) {
        if (isset($hebrew_birthdate)) {
            $birthdate = $hebrew_birthdate;
        } else {
            $convert_hebrew = true;
        }
    }

    if (('UNKNOWN' != mb_strtoupper(trim($datestr))) && (!empty($birthdate[0]['year']))) {
        $bt = preg_match("/(\d\d\d\d).*(\d\d\d\d)/", $datestr, $bmatch);

        if ($bt > 0) {
            $date = parse_date($datestr);

            if ($convert_hebrew) {
                $date = jewishGedcomDateToGregorian($date);
            }

            $age1 = $date[0]['year'] - $birthdate[0]['year'];

            $age2 = $date[1]['year'] - $birthdate[0]['year'];

            if ($style) {
                $realbirthdt = ' <span class="age">(' . $pgv_lang['age'] . ' ';
            }

            $realbirthdt .= $pgv_lang['apx'] . ' ' . convert_number($age1) . '-' . convert_number($age2);

            if ($style) {
                $realbirthdt .= ')</span>';
            }
        } else {
            $date = parse_date($datestr);

            if ($convert_hebrew) {
                $date = jewishGedcomDateToGregorian($date);
            }

            if (!empty($date[0]['year'])) {
                $age = $date[0]['year'] - $birthdate[0]['year'];

                if (!empty($birthdate[0]['mon'])) {
                    if (!empty($date[0]['mon'])) {
                        if ($date[0]['mon'] < $birthdate[0]['mon']) {
                            $age--;
                        } elseif (($date[0]['mon'] == $birthdate[0]['mon']) && (!empty($birthdate[0]['day']))) {
                            if (!empty($date[0]['day'])) {
                                if ($date[0]['day'] < $birthdate[0]['day']) {
                                    $age--;
                                }
                            }
                        }
                    }
                }

                if ($style) {
                    $realbirthdt = ' <span class="age">(' . $pgv_lang['age'];
                }

                $at = preg_match("/([a-zA-Z]{3})\.?/", $birthdate[0]['ext'], $amatch);

                if (0 == $at) {
                    $at = preg_match("/([a-zA-Z]{3})\.?/", $datestr, $amatch);
                }

                if ($at > 0) {
                    if (in_array(mb_strtolower($amatch[1]), $estimates, true)) {
                        $realbirthdt .= ' ' . $pgv_lang['apx'];
                    }
                }

                $realbirthdt .= ' ' . convert_number($age);

                if ($style) {
                    $realbirthdt .= ')</span>';
                }
            }
        }
    }

    if ($style) {
        return $realbirthdt;
    }

    return trim($realbirthdt);
}

/**
 * parse a gedcom date into an array
 *
 * parses a gedcom date IE 1 JAN 2002 into an array of month day and year values
 * @param string $datestr The date to parse
 * @return array        returns an array with indexes "day"=1 "month"=JAN "mon"=1 "year"=2002 "ext" = abt
 */
function parse_date($datestr)
{
    global $monthtonum;

    $dates = [];

    $dates[0]['day'] = ''; //1;
    $dates[0]['month'] = ''; //"JAN";
    $dates[0]['mon'] = ''; //1;
    $dates[0]['year'] = 0;

    $dates[0]['ext'] = '';

    $strs = preg_preg_split("/[\s\.,\-\\/\(\)\[\]]+/", $datestr);

    $index = 0;

    $longmonth = ['january' => 'jan', 'february' => 'feb', 'march' => 'mar', 'april' => 'apr', 'may' => 'may', 'june' => 'jun', 'july' => 'jul', 'august' => 'aug', 'september' => 'sep', 'october' => 'oct', 'november' => 'nov', 'december' => 'dec'];

    for ($i = 0, $iMax = count($strs); $i < $iMax; $i++) {
        if (isset($longmonth[mb_strtolower($strs[$i])])) {
            $strs[$i] = $longmonth[mb_strtolower($strs[$i])];
        }
    }

    for ($i = 0, $iMax = count($strs); $i < $iMax; $i++) {
        $ct = preg_match("/^\d+$/", $strs[$i]);

        if ($ct > 0) {
            if (isset($strs[$i + 1]) && ($strs[$i] < 32)) {
                $dates[$index]['day'] = $strs[$i];
            } else {
                $dates[$index]['year'] = (int)$strs[$i];

                $index++;
            }
        } else {
            if (isset($monthtonum[mb_strtolower($strs[$i])])) {
                $dates[$index]['month'] = $strs[$i];

                $dates[$index]['mon'] = $monthtonum[mb_strtolower($strs[$i])];
            } else {
                if (!isset($dates[$index]['ext'])) {
                    $dates[$index]['ext'] = '';
                }

                $dates[$index]['ext'] .= $strs[$i];
            }
        }
    }

    return $dates;
}

/* ---- function to search the day of the week
   ---- $sw_day = int  $sw_mont = int or alpha (like "jan" or "dec") $sw_year = int
   ---- $weekday (=int) will be returned (1 = monday)
   ---- calculations by Michael Gudaitis (in Java), converted by Jans Luder
*/
function search_weekday($sw_day, $sw_month, $sw_year)
{
    global $monthtonum;

    $sw_month = $monthtonum[mb_strtolower($sw_month)];

    if ($sw_month >= 3) {
        $sw_month -= 2;
    } else {
        $sw_month += 10;
    }

    if ((11 == $sw_month) || (12 == $sw_month)) {
        $sw_year--;
    }

    $centnum = floor($sw_year / 100);

    $yearnum = $sw_year % 100;

    $weekday = floor(2.6 * $sw_month - .2);

    $weekday += floor($sw_day + $yearnum);

    $weekday += $yearnum / 4;

    $weekday = floor($weekday);

    $weekday += floor($centnum / 4);

    $weekday -= floor(2 * $centnum);

    $weekday %= 7;

    if ($sw_year >= 1700 && $sw_year <= 1751) {
        $weekday -= 3;
    } elseif ($sw_year <= 1699) {
        $weekday -= 4;
    }

    if ($weekday < 0) {
        $weekday += 7;
    }

    return $weekday;
}

// end function search_weekday
