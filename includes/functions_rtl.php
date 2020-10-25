<?php

/**
 * RTL Functions
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
 * @version $Id: functions_rtl.php,v 1.1 2005/10/07 18:08:22 skenow Exp $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (mb_strstr($_SERVER['PHP_SELF'], 'functions_rtl.php')) {
    print 'Why do you want to do that?';

    exit;
}

$SpecialChar = [' ', '.', ',', '"', '\'', '/', '\\', '|', ':', ';', '+', '&', '#', '@', '-', '=', '*', '%', '!', '?', '$', "\n"];
$SpecialPar = ['(', ')', '[', ']', '{', '}'];
$SpecialNum = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

$RTLOrd = [215, 216, 217, 218, 219];

/**
 * $HNN and $ANN are used in
 * RTLUndefined, check_NN, get_common_surnames, print_block_name_top10
 */
$HNN = "\x28\xd7\x9c\xd7\x90\x20\xd7\x99\xd7\x93\xd7\x95\xd7\xa2\x29";
$ANN = "\x28\xd8\xba\xd9\x8a\xd8\xb1\x20\xd9\x85\xd8\xb9\xd8\xb1\xd9\x88\xd9\x81\x29";

//$HNN = "(לא ידוע)";
//$ANN = "(غير معروف)";

/**
 * Use RTL functions
 *
 * this function returns true if the gedcom config $USE_RTL_FUNCTIONS is set to true.
 * This is inteded to cut down on unneeded processing for users who do not need
 * RTL functionality.
 * @return false if to process RTL specific text processing.
 * @todo Possibly return true if the users current lang is RTl even if the setting is set to false;
 */
function useRTLFunctions()
{
    global $USE_RTL_FUNCTIONS;

    return $USE_RTL_FUNCTIONS;
}

/**
 * Force a string in ltr direction
 *
 * This function returns a string in left-to-right direction.
 * To be used for non HTML string output (e.g. GD ImageTtfText function).
 *
 * @param string $name input string
 * @return string ltr string
 * @author opus27
 * @todo   more hebrew controls (check numbers or &rlm; tags)
 * @todo   other rtl languages
 */
function ltr_string($name)
{
    if (!useRTLFunctions()) {
        return $name;
    }

    // hebrew string => reverse

    global $RTLOrd;

    $found = false;

    foreach ($RTLOrd as $indexval => $ord) {
        if (false !== mb_strpos($name, chr($ord))) {
            $found = true;
        }
    }

    if ($found) {
        $ltrname = '';

        $i = 0;

        while ($i < mb_strlen($name)) {
            if (in_array(ord(mb_substr($name, $i, 1)), $RTLOrd, true)) {
                $ltrname = mb_substr($name, $i, 2) . $ltrname;

                $i += 2;
            } else {
                if (' ' == $name[$i]) {
                    $ltrname = ' ' . $ltrname;
                } elseif ('(' == $name[$i]) {
                    $ltrname = ')' . $ltrname;
                } elseif (')' == $name[$i]) {
                    $ltrname = '(' . $ltrname;
                } elseif ('[' == $name[$i]) {
                    $ltrname = ']' . $ltrname;
                } elseif (']' == $name[$i]) {
                    $ltrname = '[' . $ltrname;
                } elseif ('{' == $name[$i]) {
                    $ltrname = '}' . $ltrname;
                } elseif ('}' == $name[$i]) {
                    $ltrname = '{' . $ltrname;
                } else {
                    $ltrname = $name[$i] . $ltrname;
                }   //--- ???

                $i++;
            }
        }

        $ltrname = str_replace(';mrl&', '', $ltrname);

        $ltrname = str_replace(';mlr&', '', $ltrname);

        return $ltrname;
    }

    // other rtl languages => (to be completed)

    // else

    $ltrname = $name;

    $ltrname = str_replace('&lrm;', '', $ltrname);

    $ltrname = str_replace('&rlm;', '', $ltrname);

    return $ltrname;
}

/**
 * convert HTML entities to to their original characters
 *
 * original found at http://www.php.net/manual/en/function.get-html-translation-table.php
 * @see http://www.php.net/manual/en/function.get-html-translation-table.php
 * @param string $string the string to remove the entities from
 * @return string    the string with entities converted
 */
function unhtmlentities($string)
{
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);

    $trans_tbl = array_flip($trans_tbl);

    $ret = strtr($string, $trans_tbl);

    $ret = preg_replace('/&#(\d+);/me', "chr('\\1')", $ret);

    //- temporarily remove &lrm; until they can be better handled later

    $ret = preg_replace(['/&lrm;/', '/&rlm;/'], ['', ''], $ret);

    return $ret;
}

/**
 * process a string according to bidirectional rules
 *
 * this function will take a text string and reverse it for RTL languages
 * according to bidi rules.
 * @param string $text String to change
 * @return string    the new bidi string
 * @todo add other RTL langauges
 */
function bidi_text($text)
{
    if (!useRTLFunctions()) {
        return $text;
    }

    global $RTLOrd;

    // דו"ח אישי

    //קראטוןםפ שדגכעיחלךף זסבה� מצתץ עברי איתה מאיה (אתקה) שם משפחה ‎

    //מספר מזהה (SSN)

    $found = false;

    foreach ($RTLOrd as $indexval => $ord) {
        if (false !== mb_strpos($text, chr($ord))) {
            $found = true;
        }
    }

    if (!$found) {
        return $text;
    }

    $special_chars = [' ', '"', '\'', '(', ')', '[', ']', ':', "\n"];

    $newtext = '';

    $parts = [];

    $temp = '';

    $state = 0;

    $p = 0;

    for ($i = 0, $iMax = mb_strlen($text); $i < $iMax; $i++) {
        $letter = $text[$i];

        //print $letter.ord($letter).",";

        //-- handle Hebrew chars

        if (in_array(ord($letter), $RTLOrd, true)) {
            if (!empty($temp)) {
                //-- just in case the $temp is a Hebrew char push it onto the stack

                if (in_array(ord($temp[0]), $RTLOrd, true)) {
                } //-- if the $temp starts with a char in the special_chars array then remove the space and push it onto the stack seperately

                elseif (in_array($temp[mb_strlen($temp) - 1], $special_chars, true)) {
                    $char = mb_substr($temp, mb_strlen($temp) - 1);

                    $temp = mb_substr($temp, 0, -1);

                    if ('[' == $char) {
                        $char = ']';
                    } elseif ('(' == $char) {
                        $char = ')';
                    }

                    $parts[] = $temp;

                    $parts[] = $char;
                } //-- otherwise push it onto the begining of the stack

                else {
                    array_unshift($parts, $temp);
                }
            }

            $temp = $letter . $text[$i + 1];

            $i++;

            if ($i < mb_strlen($text) - 1) {
                $l = $text[$i + 1];

                if (in_array($l, $special_chars, true)) {
                    if (']' == $l) {
                        $l = '[';
                    } elseif (')' == $l) {
                        $l = '(';
                    }

                    $temp = $l . $temp;

                    $i++;
                }
            }

            $parts[] = $temp;

            $temp = '';
        } elseif (226 == ord($letter)) {
            if ($i < mb_strlen($text) - 2) {
                $l = $letter . $text[$i + 1] . $text[$i + 2];

                $i += 2;

                if (("\xe2\x80\x8f" == $l) || ("\xe2\x80\x8e" == $l)) {
                    if (!empty($temp)) {
                        $last = array_pop($parts);

                        if (')' == $temp[0]) {
                            $last = '(' . $last;
                        } elseif ('(' == $temp[0]) {
                            $last = ')' . $last;
                        } elseif (']' == $temp[0]) {
                            $last = '[' . $last;
                        } elseif ('[' == $temp[0]) {
                            $last = ']' . $last;
                        }

                        $parts[] = $last;

                        $temp = '';
                    }
                }
            }
        } else {
            $temp .= $letter;
        }
    }

    if (!empty($temp)) {
        if (in_array(ord($temp[0]), $RTLOrd, true)) {
            $parts[] = $temp;
        } else {
            $parts[] = $temp;
        }
    }

    //-- loop through and check if parenthesis are correct... if parenthesis were broken by

    //-- rtl text then they need to be reversed

    for ($i = 0, $iMax = count($parts); $i < $iMax; $i++) {
        $bef = '';

        $aft = '';

        $wt = preg_match("/^(\s*).*(\s*)$/", $parts[$i], $match);

        if ($wt > 0) {
            $bef = $match[1];

            $aft = $match[2];
        }

        $temp = trim($parts[$i]);

        if (!empty($temp)) {
            if ('(' == $temp[0] && ')' != $temp[mb_strlen($temp) - 1]) {
                $parts[$i] = $bef . mb_substr($temp, 1) . ')' . $aft;
            }

            if ('[' == $temp[0] && ']' != $temp[mb_strlen($temp) - 1]) {
                $parts[$i] = $bef . mb_substr($temp, 1) . ']' . $aft;
            }

            if ('(' != $temp[0] && ')' == $temp[mb_strlen($temp) - 1]) {
                $parts[$i] = $bef . '(' . mb_substr($temp, 0, -1) . $aft;
            }

            if ('[' != $temp[0] && ']' == $temp[mb_strlen($temp) - 1]) {
                $parts[$i] = $bef . '[' . mb_substr($temp, 0, -1) . $aft;
            }
        }
    }

    //print_r($parts);

    $parts = array_reverse($parts);

    $newtext = implode('', $parts);

    return $newtext;
}

/**
 * Verify if text is a RtL character
 *
 * This will verify if text is a RtL character
 * @param string $text to verify
 * @return bool
 * @return bool
 */
function oneRTLText($text)
{
    //--- What if gedcom in ANSI?

    if (!useRTLFunctions()) {
        return false;
    }

    global $RTLOrd;

    return (2 == mb_strlen($text) && in_array(ord($text), $RTLOrd, true));
}

/**
 * Verify if text starts by a RtL character
 *
 * This will verify if text starts by a RtL character
 * @param string $text to verify
 * @return bool
 * @return bool
 */
function begRTLText($text)
{
    //--- What if gedcom in ANSI?

    if (!useRTLFunctions()) {
        return false;
    }

    global $RTLOrd;

    return (in_array(ord(mb_substr(trim($text), 0, 2)), $RTLOrd, true) || in_array(ord(mb_substr(trim($text), 1, 2)), $RTLOrd, true));
}

/**
 * Verify if text ends by a RtL character
 *
 * This will verify if text ends by a RtL character
 * @param string $text to verify
 * @return bool
 * @return bool
 */
function endRTLText($text)
{
    //--- What if gedcom in ANSI? -- I believe that not used

    if (!useRTLFunctions()) {
        return false;
    }

    global $RTLOrd;

    return (in_array(ord(mb_substr(trim($text), mb_strlen(trim($text)) - 2, 2)), $RTLOrd, true) || in_array(ord(mb_substr(trim($text), mb_strlen(trim($text)) - 3, 2)), $RTLOrd, true));
}

/**
 * Verify if text is RtL
 *
 * This will verify if text has RtL characters
 * @param string $text to verify
 * @return bool
 * @return bool
 */
function hasRTLText($text)
{
    //--- What if gedcom in ANSI?

    // if (!(strpos($text, chr(215))=== false)) return true;  // OK?

    if (!useRTLFunctions()) {
        return false;
    }

    global $RTLOrd;

    for ($i = 0, $iMax = mb_strlen($text); $i < $iMax; $i++) {
        if (in_array(ord(mb_substr(trim($text), $i, 2)), $RTLOrd, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Verify if text is LtR
 *
 * This will verify if text has LtR characters that are not special characters
 * @param string $text to verify
 * @return bool
 * @return bool
 */
function hasLTRText($text)
{
    //--- What if gedcom in ANSI?

    //--- Should have one fullspecial characters array in PGV -

    if (!useRTLFunctions()) {
        return false;
    }

    global $SpecialChar, $SpecialPar, $SpecialNum, $RTLOrd;

    for ($i = 0, $iMax = mb_strlen($text); $i < $iMax; $i++) {
        if (in_array(ord(mb_substr(trim($text), $i, 2)), $RTLOrd, true) || in_array(ord(mb_substr(trim($text), $i - 1, 2)), $RTLOrd, true)) {
            $i++;
        } else {
            $byte = mb_substr(trim($text), $i, 1);

            if (!in_array($byte, $SpecialChar, true) && !in_array($byte, $SpecialPar, true) && !in_array($byte, $SpecialNum, true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Use Hebrew/Arabic undefined for names with Hebrew/Arabic characters on any PGV pages
 *
 * @param string $text to verify
 * @return string
 * @return string
 */
function RTLUndefined($text)
{
    global $HNN, $ANN;

    if (false !== mb_strpos($text, 215)) {
        $NN = $HNN;
    } //-- else Arabic - letter ord is 216,217,218,219

    else {
        $NN = $ANN;
    }

    return ($NN);
}

///**
// * Verify if text is RtL
// *
// * This will verify if text is RtL or not
// * @param string $text to verify
// */
//function isRTLText($text) {
//return (substr(trim($text),0,5)=="&rlm;"  || substr(trim($text),strlen(trim($text))-5,5)=="&rlm;"
// || ord(substr(trim($text),0,2))==215 || ord(substr(trim($text),strlen(trim($text))-2,2))==215
// || ord(substr(trim($text),1,2))==215 || ord(substr(trim($text),strlen(trim($text))-3,2))==215
// || ord(substr(trim($text),5,2))==215 || ord(substr(trim($text),strlen(trim($text))-8,2))==215
// || ord(substr(trim($text),6,2))==215 || ord(substr(trim($text),strlen(trim($text))-9,2))==215);
//}
