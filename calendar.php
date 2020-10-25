<?php

/**
 * Display Events on a Calendar
 *
 * Displays events on a daily, monthly, or yearly calendar.
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
 * $Id: calendar.php,v 1.1 2005/10/07 18:08:01 skenow Exp $
 */

/**
 * load the configuration and create the context
 */
require 'config.php';
require 'includes/adodb-time.inc.php';
require $PGV_BASE_DIRECTORY . $factsfile['english'];
if (file_exists($PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE])) {
    require $PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE];
}

if (empty($day)) {
    $day = adodb_date('j');
}
if (empty($month)) {
    $month = adodb_date('M');
}
if (empty($year)) {
    $year = adodb_date('Y');
}

if (empty($action)) {
    $action = 'today';
}
if (empty($filterev)) {
    $filterev = 'all';
}
if (empty($filterof)) {
    $filterof = 'all';
}
if (empty($filtersx)) {
    $filtersx = '';
}

$olddates = true;
if ('calendar' == $action) {
    $test = @adodb_mktime(1, 0, 0, 1, 1, 1960);

    if (-1 == $test) {
        $olddates = false;
    }
}
$endyear = '0';
if ('year' == $action) {
    $abbr = ["$pgv_lang[abt]", "$pgv_lang[aft]", "$pgv_lang[bef]", "$pgv_lang[bet]", "$pgv_lang[cal]", "$pgv_lang[est]", "$pgv_lang[from]", "$pgv_lang[int]", "$pgv_lang[cir]", "$pgv_lang[apx]", "$pgv_lang[and]", "$pgv_lang[to]"];

    $year = trim($year);

    for ($i = 0; $i <= mb_strlen($year); $i++) {
        if ('0' == mb_substr($year, 0, 1) && '-' != mb_substr($year, 1, 1)) {
            $year = mb_substr($year, 1);
        }
    }

    $pos1 = mb_strpos($year, ' ');

    if (0 == $pos1) {
        $pos1 = mb_strlen($year);
    }

    if (function_exists('str2lower')) {
        $in_year = str2lower(mb_substr($year, 0, $pos1));
    } else {
        $in_year = mb_substr($year, 0, $pos1);
    }

    if (in_array("$in_year", $abbr, true)) {
        if (function_exists('str2lower')) {
            $year = preg_replace(
                ["/$abbr[0]/", "/$abbr[1]/", "/$abbr[2]/", "/$abbr[3]/", "/$abbr[4]/", "/$abbr[5]/", "/$abbr[6]/", "/$abbr[7]/", "/$abbr[8]/", "/$abbr[9]/", "/ $abbr[10] /", "/ $abbr[11] /"],
                ['abt', 'aft', 'bef', 'bet', 'cal', 'est', 'from', 'int', 'cir', 'apx', ' and ', ' to '],
                str2lower($year)
            );
        } else {
            $year = preg_replace(["/$abbr[0]/", "/$abbr[1]/", "/$abbr[2]/", "/$abbr[3]/", "/$abbr[4]/", "/$abbr[5]/", "/$abbr[6]/", "/$abbr[7]/", "/$abbr[8]/", "/$abbr[9]/"], ['abt', 'aft', 'bef', 'bet', 'cal', 'est', 'from', 'int', 'cir', 'apx'], $year);
        }
    }

    if (mb_strlen($year) > 1 && preg_match("/\?/", $year)) {
        $year = preg_replace("/\?/", '[0-9]', $year);
    }

    $year = preg_replace(['/&lt;/', '/&gt;/', "/[?*+|&.,:'%_<>!#¤{}=^]/", '/\$/', "/\\\/", '/"/'], '', $year);

    if (preg_match("/[\D]{1,2}/", $year) && mb_strlen($year) <= 2) {
        $year = '';
    }

    if (empty($year)) {
        $year = adodb_date('Y');
    }

    $year = trim($year);

    $year_text = $year;

    $year_query = $year;

    $startyear = '0';

    if ((mb_strpos($year, '-') > '0') && !preg_match("/[\[\]]/", $year)) {
        if (mb_substr($year, 0, 1) > '9') {
            while (mb_substr($year, 0, 1) > '9') {
                $year = trim(mb_substr($year, 1));
            }
        }

        $pos1 = (mb_strpos($year, '-'));

        if (mb_strlen($year) == $pos1 + 2) {                    // endyear n
            $year_query = mb_substr($year, 0, ($pos1 - 1)) . '[' . mb_substr($year, ($pos1 - 1), 3) . ']';

            $year_text = mb_substr($year, 0, ($pos1 + 1)) . mb_substr($year, 0, ($pos1 - 1)) . mb_substr($year, ($pos1 + 1), 1);
        } elseif (mb_strlen($year) == $pos1 + 3) {                // endyear nn
            $year_text = mb_substr($year, 0, ($pos1 - 2));

            if (('0' == mb_substr($year, ($pos1 - 1), 1)) && ('9' == mb_substr($year, ($pos1 + 2), 1))) {
                $year_query = $year_text . '[' . mb_substr($year, ($pos1 - 2), 1) . '-' . mb_substr($year, ($pos1 + 1), 1) . '][0-9]';
            } else {
                $startyear = mb_substr($year, 0, $pos1);

                $endyear = mb_substr($year, 0, ($pos1 - 2)) . mb_substr($year, ($pos1 + 1), 2);
            }

            $year_text = mb_substr($year, 0, ($pos1)) . ' - ' . ('0' == $startyear ? '' : $year_text) . mb_substr($year, ($pos1 + 1), 2);
        } elseif ((mb_strlen($year) == $pos1 + 4) && (4 == $pos1)) {    // endyear nnn
            $year_text = mb_substr($year, 0, ($pos1 - 3));

            if (('00' == mb_substr($year, ($pos1 - 2), 2)) && ('99' == mb_substr($year, ($pos1 + 2), 2))) {
                $year_query = $year_text . '[' . mb_substr($year, ($pos1 - 3), 1) . '-' . mb_substr($year, ($pos1 + 1), 1) . '][0-9][0-9]';
            } else {
                $startyear = mb_substr($year, 0, $pos1);

                $endyear = mb_substr($year, 0, ($pos1 - 3)) . mb_substr($year, ($pos1 + 1), 3);
            }

            $year_text = mb_substr($year, 0, ($pos1)) . ' - ' . $year_text . mb_substr($year, ($pos1 + 1), 3);
        } else {                                            // endyear nnn(n)
            $startyear = mb_substr($year, 0, $pos1);

            $endyear = mb_substr($year, ($pos1 + 1));

            $year_text = $startyear . ' - ' . $endyear;
        }

        if ($startyear > $endyear) {
            $year_text = $startyear;

            $startyear = $endyear;

            $endyear = $year_text;

            $year = $startyear . '-' . $endyear;

            $year_text = $startyear . ' - ' . $endyear;
        }
    }

    if (mb_strpos($year, '[', 1) > '0') {
        $pos1 = (mb_strpos($year, '[', 0));

        $year_text = mb_substr($year, 0, $pos1);

        while (false !== ($pos1 = mb_strpos($year, '[', $pos1))) {
            $year_text .= mb_substr($year, ($pos1 + 1), 1);

            $pos1++;
        }

        $pos1 = mb_strpos($year, ']', $pos1);

        if (mb_strlen($year) > $pos1 && !mb_strpos($year, ']', $pos1 + 1)) {
            $year_add = mb_substr($year, $pos1 + 1, mb_strlen($year));
        }

        $pos1 = mb_strpos($year, ']', $pos1 + 1);

        if (mb_strlen($year) > $pos1 && !mb_strpos($year, ']', $pos1 + 1)) {
            $year_add = mb_substr($year, $pos1 + 1, mb_strlen($year));
        }

        if (isset($year_add)) {
            $year_text .= $year_add . ' ~ ';
        } else {
            $year_text .= ' - ';
        }

        if (mb_strpos($year, ' ', 0) > 0) {
            $pos1 = (mb_strpos($year, ' ', 0) + 1);
        } else {
            $pos1 = 0;
        }

        $year_text .= mb_substr($year, $pos1, (mb_strpos($year, '[', 0)) - $pos1);

        $pos1 = (mb_strpos($year, '[', 0));

        while (false !== ($pos1 = mb_strpos($year, ']', $pos1))) {
            $year_text .= mb_substr($year, ($pos1 - 1), 1);

            $pos1++;
        }

        if (isset($year_add)) {
            $year_text .= $year_add;
        }

        $year_query = $year;
    } elseif (mb_strlen($year) < 4 && preg_match("/[\d]{1,3}/", $year)) {
        if (mb_substr($year, 0, 2) <= mb_substr(adodb_date('Y'), 0, 2)) {
            for ($i = mb_strlen($year); $i < 4; $i++) {
                $year_text .= '0';
            }

            $startyear = $year_text;

            $year_text .= ' - ' . $year;

            for ($i = mb_strlen($year); $i < 4; $i++) {
                $year_text .= '9';
            }

            $endyear = $year;

            for ($i = mb_strlen($year); $i < 4; $i++) {
                $endyear .= '9';
            }
        } else {
            for ($i = mb_strlen($year); $i < 3; $i++) {
                $year_text .= '0';
            }

            for ($i = mb_strlen($year); $i < 3; $i++) {
                $year .= '0';
            }
        }

        $year_query = $year;
    }
} else {
    if (mb_strlen($year) < 3) {
        $year = adodb_date('Y');
    }

    if (mb_strlen($year) > 4) {
        if (mb_strpos($year, '[', 1) > '0') {
            $pos1 = (mb_strpos($year, '[', 0));

            $year_text = $year;

            $year = mb_substr($yy, 0, ($pos1));

            $year .= mb_substr($yy, ($pos1 + 1), 1);

            if (mb_strlen($year_text) == $pos1 + 10) {
                $year .= mb_substr($yy, ($pos1 + 6), 1);
            }
        } elseif (mb_strpos($year, '-', 1) > '0') {
            $year = mb_substr($year, 0, (mb_strpos($year, '-', 0)));
        } else {
            $year = adodb_date('Y');
        }
    }

    $year = trim($year);
}

// calculate leap year
if (mb_strlen($year) < 5 && preg_match("/[\d]{2,4}/", $year)) {
    if (checkdate(2, 29, $year)) {
        $leap = true;
    } else {
        $leap = false;
    }
} else {
    $leap = false;
}

// Check for invalid days
$m_days = 31;
$m_name = mb_strtolower($month);
if ('feb' == $m_name) {
    if (!$leap) {
        $m_days = 28;

        if ($day >= '28') {
            $day = '28';

            $pregquery = "2 DATE[^\n]*2[8|9] $month";

            if ($REGEXP_DB) {
                $query = "2 DATE[^\n]*2[8|9] $month";
            } else {
                $query = "%2 DATE%2%$month%";
            }
        }
    } else {
        $m_days = 29;

        if ($day >= '29') {
            $day = '29';

            $pregquery = "2 DATE[^\n]*29 $month";

            if ($REGEXP_DB) {
                $query = "2 DATE[^\n]*29 $month";
            } else {
                $query = "%2 DATE%29 $month%";
            }
        }
    }
} elseif ('apr' == $m_name || 'jun' == $m_name || 'sep' == $m_name || 'nov' == $m_name) {
    $m_days = 30;

    if ($day >= '30') {
        $day = '30';

        $pregquery = "2 DATE[^\n]*30 $month";

        if ($REGEXP_DB) {
            $query = "2 DATE[^\n]*30 $month";
        } else {
            $query = "%2 DATE%30 $month%";
        }
    }
}
if (!isset($query)) {
    if ($day < 10) {
        $pregquery = "2 DATE[^\n]*[ |0]$day $month";

        if ($REGEXP_DB) {
            $query = "2 DATE[^\n]*[ |0]$day $month";
        } else {
            $query = "%2 DATE%$day $month%";
        }
    } else {
        $pregquery = "2 DATE[^\n]*$day $month";

        if ($REGEXP_DB) {
            $query = "2 DATE[^\n]*$day $month";
        } else {
            $query = "%2 DATE%$day $month%";
        }
    }
}
print_header($pgv_lang['anniversary_calendar']);
print "<div style=\" text-align: center;\" id=\"calendar_page\">\n";

// Print top text
print '<span class="subheaders">';
if ('today' == $action) {
    print $pgv_lang['on_this_day'] . "<br>\n";

    //-- the year is needed for alternate calendars

    if ('gregorian' != $CALENDAR_FORMAT) {
        print get_changed_date("$day $month $year");
    } else {
        print get_changed_date("$day $month");
    }
} elseif ('calendar' == $action) {
    print $pgv_lang['in_this_month'] . "<br>\n";

    print get_changed_date(" $month $year ");
} elseif ('year' == $action) {
    print $pgv_lang['in_this_year'] . "<br>\n";

    print get_changed_date(" $year_text ");
}
print '</span><br>';
if ('preview' != $view) {
    // Print calender form

    print '<form name="dateform" method="get" action="calendar.php">';

    print "<input type=\"hidden\" name=\"action\" value=\"$action\">";

    print "\n\t\t<table class=\"list_table $TEXT_DIRECTION\" style=\" width: 100%;\">\n\t\t<tr>";

    print '<td class="list_label">' . $pgv_lang['day'] . "</td>\n";

    print '<td colspan="7" class="list_value">';

    for ($i = 1; $i < ($m_days + 1); $i++) {
        if (empty($dd)) {
            $dd = $day;
        }

        print "<a href=\"calendar.php?day=$i&amp;month=" . mb_strtolower($month) . "&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx&amp;action=today\">";

        if ($i == $dd) {
            print "<span class=\"error\">$i</span>";
        } else {
            print $i;
        }

        print '</a> | ';
    }

    $Dd = adodb_date('j');

    $Mm = adodb_date('M');

    $Yy = adodb_date('Y');

    //	print "<a href=\"calendar.php?filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\"><b>".get_changed_date("$Dd $Mm $Yy")."</b></a> | ";

    //-- for alternate calendars the year is needed

    if ('gregorian' != $CALENDAR_FORMAT) {
        $datestr = "$Dd $Mm $Yy";
    } else {
        $datestr = "$Dd $Mm";
    }

    print "<a href=\"calendar.php?filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx&amp;year=$year\"><b>" . get_changed_date($datestr) . '</b></a> | ';

    print_help_link('annivers_date_select_help', 'qm');

    print "</td>\n";

    print '</tr><tr>';

    print '<td class="list_label">' . $pgv_lang['month'] . "</td>\n";

    print '<td colspan="7" class="list_value">';

    foreach ($monthtonum as $mon => $num) {
        if (isset($pgv_lang[$mon])) {
            if (empty($mm)) {
                $mm = mb_strtolower($month);
            }

            print "<a href=\"calendar.php?day=$dd&amp;month=$mon&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx&amp;action=" . ('year' == $action ? 'calendar' : "$action") . '">';

            $monthstr = $pgv_lang[$mon];

            if ($mon == $mm) {
                print '<span class="error">' . $monthstr . '</span>';
            } else {
                print $monthstr;
            }

            print '</a> | ';
        }
    }

    print '<a href="calendar.php?month=' . mb_strtolower(adodb_date('M')) . "&amp;action=calendar&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\"><b>" . $pgv_lang[mb_strtolower(adodb_date('M'))] . ' ' . mb_strtolower(adodb_date('Y')) . '</b></a> | ';

    print_help_link('annivers_month_select_help', 'qm');

    print "</td>\n";

    print '</tr><tr>';

    print '<td class="list_label">' . $pgv_lang['year'] . "</td>\n";

    $username = getUserName();

    print '<td class="list_value"';

    print ' style=" width: 19%; vertical-align: middle;">';

    if (mb_strlen($year) < 5) {
        if ($year < 'AA') {
            print " <a href=\"calendar.php?day=$day&amp;month=$month&amp;year=" . ($year - 1) . '&amp;action=' . ('calendar' == $action ? 'calendar' : 'year') . "&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\" title=\"" . ($year - 1) . '" >-1</a> ';
        }
    }

    print "<input type=\"text\" name=\"year\" value=\"$year\" size=\"7\">";

    if (mb_strlen($year) < 5) {
        if ($year < (adodb_date('Y'))) {
            print " <a href=\"calendar.php?day=$day&amp;month=$month&amp;year=" . ($year + 1) . '&amp;action=' . ('calendar' == $action ? 'calendar' : 'year') . "&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\" title=\"" . ($year + 1) . '" >+1</a> |';
        } elseif ($year < 'AA') {
            print ' +1 |';
        }
    }

    print " <a href=\"calendar.php?day=$day&amp;month=$month&amp;year=" . adodb_date('Y') . '&amp;action=' . ('calendar' == $action ? 'calendar' : 'year') . "&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\"><b>" . mb_strtolower(adodb_date('Y')) . '</b></a> | ';

    print_help_link('annivers_year_select_help', 'qm');

    print "</td>\n ";

    if (!$HIDE_LIVE_PEOPLE || (!empty($username))) {
        print '<td class="list_label" style=" width: 3%;">&nbsp;' . $pgv_lang['show'] . ":&nbsp;</td>\n";

        print '<td class="list_value" style="vertical-align: middle;">';

        print "<input type=\"hidden\" name=\"filterof\" value=\"$filterof\">";

        print "<select class=\"list_value\" name=\"filterof\" onchange=\"document.dateform.submit();\">\n";

        print '<option value="all"';

        if ('all' == $filterof) {
            print ' selected="selected"';
        }

        print '>' . $pgv_lang['all_people'] . "</option>\n";

        print '<option value="living"';

        if ('living' == $filterof) {
            print ' selected="selected"';
        }

        print '>' . $pgv_lang['living_only'] . "</option>\n";

        print '<option value="recent"';

        if ('recent' == $filterof) {
            print ' selected="selected"';
        }

        print '>' . $pgv_lang['recent_events'] . "</option>\n";

        print "</select>\n";
    } else {
        print '<td class="list_label" style=" width: 18%;">' . $pgv_lang['showcal'] . "</td>\n";

        print '<td colspan="5" class="list_value" style="vertical-align: middle;">';

        if ('all' == $filterof) {
            print '<span class="error">' . $pgv_lang['all_people'] . '</span> | ';
        } else {
            $filt = 'all';

            print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterof=$filt&amp;filtersx=$filtersx&amp;action=$action\">" . $pgv_lang['all_people'] . '</a>' . ' | ';
        }

        if ('recent' == $filterof) {
            print '<span class="error">' . $pgv_lang['recent_events'] . '</span> | ';
        } else {
            $filt = 'recent';

            print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterof=$filt&amp;filtersx=$filtersx&amp;action=$action\">" . $pgv_lang['recent_events'] . '</a>' . ' | ';
        }
    }

    print_help_link('annivers_show_help', 'qm');

    if (!$HIDE_LIVE_PEOPLE || (!empty($username))) {
        print "</td>\n ";

        print '<td class="list_label" style=" width: 5%;">&nbsp;' . $pgv_lang['sex'] . ":&nbsp;</td>\n";

        print '<td class="list_value" style=" width: 12%; vertical-align: middle;">';

        if ('' == $filtersx) {
            print ' <img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sex']['small'] . '" title="' . $pgv_lang['all'] . '" alt="' . $pgv_lang['all'] . '" width="15" height="15" border="0" align="middle">';

            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sexf']['small'] . '" title="' . $pgv_lang['all'] . '" alt="' . $pgv_lang['all'] . '" width="15" height="15" border="0" align="middle">';

            print ' | ';
        } else {
            $fs = '';

            print " <a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$fs&amp;action=$action\">";

            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sex']['small'] . '" title="' . $pgv_lang['all'] . '" alt="' . $pgv_lang['all'] . '" width="9" height="9" border="0" align="middle">';

            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sexf']['small'] . '" title="' . $pgv_lang['all'] . '" alt="' . $pgv_lang['all'] . '" width="9" height="9" border="0" align="middle">';

            print '</a>' . ' | ';
        }

        if ('M' == $filtersx) {
            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sex']['small'] . '" title="' . $pgv_lang['male'] . '" alt="' . $pgv_lang['male'] . '" width="15" height="15" border="0" align="middle">';

            print ' | ';
        } else {
            $fs = 'M';

            print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$fs&amp;action=$action\">";

            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sex']['small'] . '" title="' . $pgv_lang['male'] . '" alt="' . $pgv_lang['male'] . '" width="9" height="9" border="0" align="middle">';

            print '</a>' . ' | ';
        }

        if ('F' == $filtersx) {
            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sexf']['small'] . '" title="' . $pgv_lang['female'] . '" alt="' . $pgv_lang['female'] . '" width="15" height="15" border="0" align="middle">';

            print ' | ';
        } else {
            $fs = 'F';

            print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$fs&amp;action=$action\">";

            print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sexf']['small'] . '" title="' . $pgv_lang['female'] . '" alt="' . $pgv_lang['female'] . '" width="9" height="9" border="0" align="middle">';

            print '</a>' . ' | ';
        }

        print_help_link('annivers_sex_help', 'qm');
    }

    if (!$HIDE_LIVE_PEOPLE || (!empty($username))) {
        print "</td>\n ";

        global $factarray;

        print '<td class="list_label" style=" width: 5%;">&nbsp;' . $factarray['EVEN'] . ":&nbsp;</td>\n";

        print '<td class="list_value"';

        if (!$HIDE_LIVE_PEOPLE || !empty($username)) {
            print '>';
        } else {
            print ' colspan="3">';
        }

        print "<input type=\"hidden\" name=\"filterev\" value=\"$filterev\">";

        print "<select class=\"list_value\" name=\"filterev\" onchange=\"document.dateform.submit();\">\n";

        print '<option value="all"';

        if ('all' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $pgv_lang['all'] . "</option>\n";

        print '<option value="BIRT"';

        if ('BIRT' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['BIRT'] . "</option>\n";

        print '<option value="CHR"';

        if ('CHR' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['CHR'] . "</option>\n";

        print '<option value="CHRA"';

        if ('CHRA' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['CHRA'] . "</option>\n";

        print '<option value="BAPM"';

        if ('BAPM' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['BAPM'] . "</option>\n";

        print '<option value="_COML"';

        if ('_COML' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['_COML'] . "</option>\n";

        print '<option value="MARR"';

        if ('MARR' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['MARR'] . "</option>\n";

        print '<option value="_SEPR"';

        if ('_SEPR' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['_SEPR'] . "</option>\n";

        print '<option value="DIV"';

        if ('DIV' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['DIV'] . "</option>\n";

        print '<option value="DEAT"';

        if ('DEAT' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['DEAT'] . "</option>\n";

        print '<option value="BURI"';

        if ('BURI' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['BURI'] . "</option>\n";

        print '<option value="IMMI"';

        if ('IMMI' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['IMMI'] . "</option>\n";

        print '<option value="EMIG"';

        if ('EMIG' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $factarray['EMIG'] . "</option>\n";

        print '<option value="EVEN"';

        if ('EVEN' == $filterev) {
            print ' selected="selected"';
        }

        print '>' . $pgv_lang['custom_event'] . "</option>\n";

        print "</select>\n";

        print_help_link('annivers_event_help', 'qm');
    }

    print "</td>\n";

    print '</tr>';

    print '</table><br>';

    print "<input type=\"hidden\" name=\"day\" value=\"$dd\">";

    print "<input type=\"hidden\" name=\"month\" value=\"$mm\">";

    print "<input type=\"hidden\" name=\"filtersx\" value=\"$filtersx\">";

    print '<input type="submit" value="' . $pgv_lang['viewday'] . "\" onclick=\"document.dateform.elements['action'].value='today';\">\n";

    print '<input type="submit" value="' . $pgv_lang['viewmonth'] . "\" onclick=\"document.dateform.elements['action'].value='calendar';\">\n";

    print '<input type="submit" value="' . $pgv_lang['viewyear'] . "\" onclick=\"document.dateform.elements['action'].value='year';\">\n";

    print_help_link('day_month_help', 'qm');

    print "</form><br>\n";
}
if (('today' == $action) || ('year' == $action)) {
    $myindilist = [];

    $myfamlist = [];

    if ('year' == $action) {
        if (isset($year_query)) {
            $year = $year_query;
        }

        $pregquery = "2 DATE[^\n]*(bet|$year)";

        if ($REGEXP_DB) {
            $query = "2 DATE[^\n]*(bet|$year)";
        } else {
            $query = '%2 DATE%bet%';
        }

        $pregquery1 = "2 DATE[^\n]*$year";

        if ($REGEXP_DB) {
            $query1 = "2 DATE[^\n]*$year";
        } else {
            $query = "%2 DATE%$year%";
        }

        if ($endyear > 0) {
            $myindilist = search_indis_year_range($startyear, $endyear);

            $myfamlist = search_fams_year_range($startyear, $endyear);
        }
    }

    if (0 == $endyear) {
        $myindilist = search_indis($query);

        $myfamlist = search_fams($query);
    }

    if (isset($query1)) {
        $query = $query1;

        $pregquery = $pregquery1;
    }

    if (!empty($filtersx)) {
        $add2myindilist = [];

        foreach ($myfamlist as $gid => $fam) {
            $parents = find_parents($gid);

            if ('M' == $filtersx) {
                $add2myindilist[$parents['HUSB']] = $fam['gedcom'];
            } else {
                $add2myindilist[$parents['WIFE']] = $fam['gedcom'];
            }
        }

        $myindilist = search_indis_fam($add2myindilist);
    }

    uasort($myindilist, 'itemsort');

    if (empty($filtersx)) {
        uasort($myfamlist, 'itemsort');
    }

    $count_private_indi = 0;

    $count_indi = 0;

    $count_male = 0;

    $count_female = 0;

    $count_unknown = 0;

    $text_indi = '';

    $sx = 1;

    foreach ($myindilist as $gid => $indi) {
        if (!empty($filtersx)) {
            $sx = preg_match("/1 SEX $filtersx/i", $indi['gedcom']);
        }

        if ((('living' != $filterof) || (1 != is_dead_id($gid))) && $sx > 0) {
            $filterout = false;

            $indilines = preg_split("\n", $indi['gedcom']);

            $factrec = '';

            $lct = count($indilines);

            $text_fact = '';

            for ($i = 1; $i <= $lct; $i++) {
                $text_temp = '';

                if ($i < $lct) {
                    $line = $indilines[$i];
                }

                if (empty($line)) {
                    $line = ' ';
                }

                if ($i == $lct || ('1' == $line[0])) {
                    if (!empty($factrec)) {
                        if ($endyear > 0) {
                            $j = $startyear;

                            $t1 = preg_match("/2 DATE.* (\d\d\d\d)/i", $factrec, $m1);

                            if (($t1 > 0) && ($m1[1] >= $startyear) && ($m1[1] <= $endyear)) {
                                $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                            } else {
                                $t2 = preg_match("/2 DATE.* (\d\d\d)/i", $factrec, $m2);

                                if (($t2 > 0) && ($m2[1] >= $startyear) && ($m2[1] <= $endyear)) {
                                    $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                                }
                            }
                        } else {
                            $ct = preg_match("/$pregquery/i", $factrec, $match);

                            if ('year' == $action) {
                                if (0 == $ct) {
                                    $cb = preg_match("/2 DATE[^\n]*(bet)/i", $factrec, $m1);

                                    if ($cb > 0) {
                                        $cy = preg_match("/DATE.* [\d]{3,4}/i", $factrec, $m2);

                                        if ($cy > 0) {
                                            $numbers = preg_preg_split("/[^\d]{4,9}/i", $m2[0]);

                                            $years = [];

                                            if (count($numbers) > 2) {
                                                $y = 0;

                                                foreach ($numbers as $key => $value) {
                                                    if (!($value > 0 && $value < 32) && '' != $value) {
                                                        $years[$y] = $value;

                                                        $y++;
                                                    }
                                                }
                                            }

                                            if (!isset($years[0])) {
                                                $years[0] = 0;
                                            }

                                            if (!isset($years[1])) {
                                                $years[1] = 0;
                                            }

                                            if ($years[0] < $year && $years[1] > $year) {
                                                $ct = 1;
                                            } else {
                                                $text_temp .= 'filter';
                                            }
                                        }
                                    }
                                }
                            }

                            if ($ct > 0) {
                                $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                            }
                        }
                    }

                    $factrec = '';
                }

                if ('filter' == $text_temp) {
                    $filterout = true;
                } else {
                    $text_fact .= $text_temp;
                }

                $factrec .= $line . "\n";
            }

            if (!empty($text_fact) && displayDetailsById($gid)) {
                $text_indi .= '<tr><td valign="top" style=" text-align: center; width: 16px;">';

                $text_indi .= "<img id=\"box-$gid.$lct-sex\" src=\"$PGV_IMAGE_DIR/";

                if (preg_match('/1 SEX M/', $indi['gedcom']) > 0) {
                    $count_male++;

                    $text_indi .= $PGV_IMAGES['sex']['small'] . '" title="' . $pgv_lang['male'] . '" alt="' . $pgv_lang['male'];
                } elseif (preg_match('/1 SEX F/', $indi['gedcom']) > 0) {
                    $count_female++;

                    $text_indi .= $PGV_IMAGES['sexf']['small'] . '" title="' . $pgv_lang['female'] . '" alt="' . $pgv_lang['female'];
                } else {
                    $count_unknown++;

                    $text_indi .= $PGV_IMAGES['sexn']['small'] . '" title="' . $pgv_lang['sex'] . ' ' . $pgv_lang['unknown'] . '" alt="' . $pgv_lang['sex'] . ' ' . $pgv_lang['unknown'];
                }

                $text_indi .= '" class="sex_image"></td>';

                $text_indi .= "<td><a href=\"individual.php?pid=$gid&amp;GEDCOM=" . $indi['file'] . '"><b>' . PrintReady(check_NN(get_sortable_name($gid))) . '</b></a>';

                if ($SHOW_ID_NUMBERS) {
                    if ('ltr' == $TEXT_DIRECTION) {
                        $text_indi .= " &lrm;($gid)&lrm;";
                    } else {
                        $text_indi .= " &rlm;($gid)&rlm;";
                    }
                }

                $text_indi .= "<br>\n\t\t";

                $text_indi .= '<div class="indent';

                if ('rtl' == $TEXT_DIRECTION) {
                    $text_indi .= '_rtl';
                }

                $text_indi .= '">';

                $text_indi .= $text_fact;

                $text_indi .= '</div>';

                if ($i <= $lct) {
                    $text_indi .= '<br>';
                }

                $text_indi .= "</td></tr>\n\t\t";

                $count_indi++;
            } elseif (!$filterout) {
                $count_private_indi++;
            }
        }
    }

    $count_private_fam = 0;

    $count_fam = 0;

    $text_fam = '';

    if ('' == $filtersx) {
        foreach ($myfamlist as $gid => $fam) {
            $display = true;

            if ('living' == $filterof) {
                $parents = find_parents($gid);

                if (is_dead_id($parents['HUSB']) || is_dead_id($parents['WIFE'])) {
                    $display = false;
                }
            }

            if ($display) {
                $filterout = false;

                $name = preg_replace(["/ [jJsS][rR]\.?,/", '/ I+,/', '/^[a-z. ]*/'], [',', ',', ''], $fam['name']);

                $names = preg_preg_split('/[,+]/', $name);

                $fam['name'] = check_NN($names);

                $indilines = preg_split("\n", $fam['gedcom']);

                $lct = count($indilines);

                $factrec = '';

                $text_fact = '';

                for ($i = 1; $i <= $lct; $i++) {
                    $text_temp = '';

                    if ($i < $lct) {
                        $line = $indilines[$i];
                    }

                    if (empty($line)) {
                        $line = ' ';
                    }

                    if ($i == $lct || ('1' == $line[0])) {
                        if (!empty($factrec)) {
                            if ($endyear > 0) {
                                $j = $startyear;

                                $t1 = preg_match("/2 DATE.* (\d\d\d\d)/i", $factrec, $m1);

                                if (($t1 > 0) && ($m1[1] >= $startyear) && ($m1[1] <= $endyear)) {
                                    $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                                } else {
                                    $t2 = preg_match("/2 DATE.* (\d\d\d)/i", $factrec, $m2);

                                    if (($t2 > 0) && ($m2[1] >= $startyear) && ($m2[1] <= $endyear)) {
                                        $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                                    }
                                }
                            } else {
                                $ct = preg_match("/$pregquery/i", $factrec, $match);

                                if ('year' == $action) {
                                    if (0 == $ct) {
                                        $cb = preg_match("/2 DATE[^\n]*(bet)/i", $factrec, $m1);

                                        if ($cb > 0) {
                                            $cy = preg_match("/DATE.* [\d]{3,4}/i", $factrec, $m2);

                                            if ($cy > 0) {
                                                $numbers = preg_preg_split("/[^\d]{4,9}/i", $m2[0]);

                                                $years = [];

                                                if (count($numbers) > 2) {
                                                    $y = 0;

                                                    foreach ($numbers as $key => $value) {
                                                        if (!($value > 0 && $value < 32) && '' != $value) {
                                                            $years[$y] = $value;

                                                            $y++;
                                                        }
                                                    }
                                                }

                                                if (!isset($years[0])) {
                                                    $years[0] = 0;
                                                }

                                                if (!isset($years[1])) {
                                                    $years[1] = 0;
                                                }

                                                if ($years[0] < $year && $years[1] > $year) {
                                                    $ct = 1;
                                                } else {
                                                    $text_temp = 'filter';
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($ct > 0) {
                                    $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                                }
                            }
                        }

                        $factrec = '';
                    }

                    if ('filter' == $text_temp) {
                        $filterout = true;
                    } else {
                        $text_fact .= $text_temp;
                    }

                    $factrec .= $line . "\n";
                }

                if (!empty($text_fact) && displayDetailsById($gid, 'FAM')) {
                    $text_fam .= "<li><a href=\"family.php?famid=$gid&amp;GEDCOM=" . $fam['file'] . '"><b>' . PrintReady(get_family_descriptor($gid)) . '</b>';

                    $text_fam .= "</a><br>\n\t\t";

                    $text_fam .= '<div class="indent';

                    if ('rtl' == $TEXT_DIRECTION) {
                        $text_fam .= '_rtl';
                    }

                    $text_fam .= '">';

                    $text_fam .= $text_fact;

                    $text_fam .= "<br></div></li>\n\t\t";

                    $count_fam++;
                } elseif (!$filterout) {
                    $count_private_fam++;
                }
            }
        }
    }

    // Print the day/year list(s)

    if (!empty($text_indi) || !empty($text_fam) || $count_private_indi > 0 || $count_private_fam > 0) {
        print "\n\t\t<table class=\"list_table; $TEXT_DIRECTION\" style=\" margin-left: auto; margin-right: auto;\">\n\t\t<tr>";

        if (!empty($text_indi) || ($count_private_indi > 0)) {
            print '<td class="list_label"><img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['indis']['large'] . '" border="0" width="25" title="' . $pgv_lang['individuals'] . '" alt="' . $pgv_lang['individuals'] . '"> ' . $pgv_lang['individuals'] . '</td>';
        }

        if (!empty($text_fam) || ($count_private_fam)) {
            print '<td class="list_label"><img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sfamily']['large'] . '" border="0" width="25" title="' . $pgv_lang['families'] . '" alt="' . $pgv_lang['families'] . '"> ' . $pgv_lang['families'] . '</td>';
        }

        print "</tr><tr>\n\t\t";

        if (!empty($text_indi) || ($count_private_indi > 0)) {
            print "<td class=\"list_value\">\n\t\t";

            if (!empty($text_indi)) {
                print '<br><table class="fact_value" style=" margin-left: 5px; margin-right: 15px;">';

                print $text_indi;

                print '</table><br>';
            }

            if ($count_private_indi > 0) {
                print '<ul><li><b>';

                print $pgv_lang['private'];

                print '</b> (' . $count_private_indi . ') ';

                print_help_link('privacy_error_help', 'qm');

                print "</li></ul>\n\t\t";
            }

            print '</td>';
        }

        if (!empty($text_fam) || ($count_private_fam)) {
            print "<td class=\"list_value\"><ul>\n\t\t";

            if (!empty($text_fam)) {
                print $text_fam;
            }

            if ($count_private_fam > 0) {
                print '<li><b>';

                print $pgv_lang['private'];

                print '</b> (' . $count_private_fam . ') ';

                print_help_link('privacy_error_help', 'qm');

                print "</li>\n\t\t";
            }

            print '</ul></td>';
        }

        print '</tr><tr valign="top">';

        if ($count_indi > 0 || $count_private_indi > 0) {
            print "<td>\n";

            if (($count_male + $count_female + $count_unknown + $count_private_indi) > 0) {
                print $pgv_lang['total_indis'] . ' ' . ($count_male + $count_female + $count_unknown + $count_private_indi) . '<br>&nbsp;&nbsp;';
            }

            if ($count_male > 0) {
                print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sex']['small'] . '" ';

                print 'title="' . $pgv_lang['male'] . '" alt="' . $pgv_lang['male'] . '" class="sex_image">&nbsp;';

                print $count_male;
            }

            if (($count_male > 0) && ($count_female > 0)) {
                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }

            if ($count_female > 0) {
                print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sexf']['small'] . '" ';

                print 'title="' . $pgv_lang['female'] . '" alt="' . $pgv_lang['female'] . '" class="sex_image" >&nbsp;';

                print $count_female;
            }

            if ($count_unknown > 0) {
                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

                print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['sexn']['small'] . '" ';

                print 'title="' . $pgv_lang['unknown'] . '" alt="' . $pgv_lang['unknown'] . '" class="sex_image">&nbsp;';

                print $count_unknown;
            }

            print '</td>';
        }

        if (($count_fam > 0) || ($count_private_fam > 0)) {
            print "<td>\n";

            if (($count_fam > 0) || ($count_private_fam > 0)) {
                print $pgv_lang['total_fams'];
            }

            print '&nbsp;&nbsp;' . ($count_fam + $count_private_fam);

            print '</td>';
        }

        print '</tr>';
    } else {
        print "\n\t<table class=\"list_table, $TEXT_DIRECTION\" style=\" margin-left: auto; margin-right: auto;\">\n\t\t<tr>";

        print '<td class="list_label">&nbsp;';

        print $pgv_lang['individuals'] . '  /  ' . $pgv_lang['families'];

        print '&nbsp;</td></tr><tr><td class="warning" style=" text-align: center;"><i>';

        print $pgv_lang['no_results'];

        print "</i><br>\n\t\t</td></tr>";
    }

    if ('preview' == $view) {
        print '<tr><td>';
    }
} elseif ('calendar' == $action) {
    if ('jewish' == $CALENDAR_FORMAT || 'hebrew' == $CALENDAR_FORMAT || 'hijri' == $CALENDAR_FORMAT) { //since calendar is based on gregorian it doesn't make sense to not display the gregorian caption
        print '<span class="subheaders">' . $pgv_lang[mb_strtolower($month)] . " $year</span> &#160; \n";
    }

    if (empty($WEEK_START)) {
        $WEEK_START = '0';
    }                //-- if the starting day for a week was not defined in the language file, then make it Sunday

    print "<table class=\"list_table, $TEXT_DIRECTION\" style=\" margin-left: auto; margin-right: auto;\">\n";

    print "\t<tr>\n";

    $days = [];

    $days[0] = 'sunday';

    $days[1] = 'monday';

    $days[2] = 'tuesday';

    $days[3] = 'wednesday';

    $days[4] = 'thursday';

    $days[5] = 'friday';

    $days[6] = 'saturday';

    $j = $WEEK_START;

    for ($i = 0; $i < 7; $i++) {
        print "\t\t<td class=\"list_label\">" . $pgv_lang[$days[$j]] . "</td>\n";

        $j++;

        if ($j > 6) {
            $j = 0;
        }
    }

    print "\t</tr>\n";

    $monthstart = adodb_mktime(1, 0, 0, $monthtonum[mb_strtolower($month)], 1, $year);

    $startday = adodb_date('w', $monthstart);

    $endday = adodb_dow($year, $monthtonum[mb_strtolower($month)], adodb_date('t', $monthstart));

    $lastday = adodb_date('t', $monthstart);

    $mmon = mb_strtolower(adodb_date('M', $monthstart));

    $monthstart = $monthstart - (60 * 60 * 24 * $startday);

    if ($WEEK_START <= $startday) {
        $monthstart += $WEEK_START * (60 * 60 * 24);
    } else { //week start > $startday
        $monthstart -= (7 - $WEEK_START) * (60 * 60 * 24);
    }

    if ((6 == $endday && 0 == $WEEK_START) || (0 == $endday && 1 == $WEEK_START)) {
        $show_no_day = 0;
    } else {
        $show_no_day = 6;
    }

    if (((0 == $startday && 0 == $WEEK_START) || (2 == $startday && 1 == $WEEK_START)) && 0 == $show_no_day) {
        $show_no_day = 6;
    }

    $show_not_set = false;

    $lastday -= 29;

    if ($lastday < 0) {
        $lastday = 0;
    }

    $myindilist = [];

    $myfamlist = [];

    $pregquery = "2 DATE[^\n]*$mmon";

    if ($REGEXP_DB) {
        $query = "2 DATE[^\n]*$mmon";
    } else {
        $query = "%2 DATE%$mmon%";
    }

    $myindilist = search_indis($query);

    $myfamlist = search_fams($query);

    if (!empty($filtersx)) {
        $add2myindilist = [];

        foreach ($myfamlist as $gid => $fam) {
            $parents = find_parents($gid);

            if ('M' == $filtersx) {
                $add2myindilist[$parents['HUSB']] = $fam['gedcom'];
            } else {
                $add2myindilist[$parents['WIFE']] = $fam['gedcom'];
            }
        }

        $myindilist = search_indis_fam($add2myindilist);
    }

    uasort($myindilist, 'itemsort');

    if (empty($filtersx)) {
        uasort($myfamlist, 'itemsort');
    }

    for ($k = 0; $k < 6; $k++) {
        print "\t<tr>\n";

        for ($j = 0; $j < 7; $j++) {
            $mday = adodb_date('j', $monthstart);

            $mmon = mb_strtolower(adodb_date('M', $monthstart));

            print "\t\t<td class=\"list_value_wrap\">\n";

            if (0 == $show_no_day && 0 == $j && 0 == $k) {
                $show_not_set = true;
            } elseif ($show_no_day == $k && 6 == $j) {
                $show_not_set = true;
            }

            if ($mmon == mb_strtolower($month) || ($show_not_set)) {
                if ($show_not_set) {
                    $pregquery = "2 DATE(|[^\n]*[^\d]+|[^\n]*( 0)|[^\n]*[3-9][$lastday-9]) $month";
                } else {
                    $day = $mday;

                    $currentDay = false;

                    if (($year == adodb_date('Y')) && (mb_strtolower($month) == mb_strtolower(adodb_date('M'))) && ($mday == adodb_date('j'))) { //current day
                        $currentDay = true;
                    }

                    print '<span class="cal_day' . ($currentDay ? ' current_day' : '') . '">' . $mday . '</span>';

                    if ('hebrew_and_gregorian' == $CALENDAR_FORMAT || 'hebrew' == $CALENDAR_FORMAT || (('jewish_and_gregorian' == $CALENDAR_FORMAT || 'jewish' == $CALENDAR_FORMAT) && 'hebrew' == $LANGUAGE)) {
                        $monthTemp = $monthtonum[mb_strtolower($month)];

                        $jd = gregoriantojd($monthTemp, $mday, $year);

                        $hebrewDate = jdtojewish($jd);

                        [$hebrewMonth, $hebrewDay, $hebrewYear] = preg_split('/', $hebrewDate);

                        print '<span class="rtl_cal_day' . ($currentDay ? ' current_day' : '') . '">';

                        print getHebrewJewishDay($hebrewDay) . ' ' . getHebrewJewishMonth($hebrewMonth, $hebrewYear) . '</span>';
                    } elseif ('jewish_and_gregorian' == $CALENDAR_FORMAT || 'jewish' == $CALENDAR_FORMAT) {
                        $monthTemp = $monthtonum[mb_strtolower($month)];

                        $jd = gregoriantojd($monthTemp, $mday, $year);

                        $hebrewDate = jdtojewish($jd);

                        [$hebrewMonth, $hebrewDay, $hebrewYear] = preg_split('/', $hebrewDate);

                        print '<span class="rtl_cal_day' . ($currentDay ? ' current_day' : '') . '">';

                        print $hebrewDay . ' ' . getJewishMonthName($hebrewMonth, $hebrewYear) . '</span>';
                    } elseif ('hijri' == $CALENDAR_FORMAT) {
                        $monthTemp = $monthtonum[mb_strtolower($month)];

                        $hDate = getHijri($mday, $monthTemp, $year);

                        [$hMonthName, $hDay, $hYear] = preg_split('/', $hDate);

                        print '<span class="rtl_cal_day' . ($currentDay ? ' current_day' : '') . '">';

                        print $hDay . ' ' . $hMonthName . '</span>';
                    }

                    print '<br style="clear: both">';

                    $dayindilist = [];

                    if ($mday < 10) {
                        $pregquery = "2 DATE[^\n]*[ |0]$mday $mmon";
                    } elseif (!$leap && 'feb' == $mmon && '28' == $mday) {
                        $pregquery = "2 DATE[^\n]*2[8|9] $mmon";
                    } else {
                        $pregquery = "2 DATE[^\n]*$mday $mmon";
                    }
                }

                $text_day = '';

                $count_private = 0;

                $sx = 1;

                foreach ($myindilist as $gid => $indi) {
                    if (!empty($filtersx)) {
                        $sx = preg_match("/1 SEX $filtersx/i", $indi['gedcom']);
                    }

                    if ((('living' != $filterof) || (1 != is_dead_id($gid))) && $sx > 0) {
                        if (preg_match("/$pregquery/i", $indi['gedcom']) > 0) {
                            $filterout = false;

                            $indilines = preg_split("\n", $indi['gedcom']);

                            $factrec = '';

                            $lct = count($indilines);

                            $text_fact = '';

                            for ($i = 1; $i <= $lct; $i++) {
                                $text_temp = '';

                                if ($i < $lct) {
                                    $line = $indilines[$i];
                                }

                                if (empty($line)) {
                                    $line = ' ';
                                }

                                if ($i == $lct || ('1' == $line[0])) {
                                    if (!empty($factrec)) {
                                        $ct = preg_match("/$pregquery/i", $factrec, $match);

                                        if ($ct > 0) {
                                            $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                                        }
                                    }

                                    $factrec = '';
                                }

                                if ('filter' == $text_temp) {
                                    $filterout = true;
                                } else {
                                    $text_fact .= $text_temp;
                                }

                                $factrec .= $line . "\n";
                            }

                            if (!empty($text_fact) && displayDetailsById($gid)) {
                                $text_day .= "<a href=\"individual.php?pid=$gid&amp;GEDCOM=" . $indi['file'] . '"><b>' . PrintReady(check_NN(get_sortable_name($gid))) . '</b>';

                                if ($SHOW_ID_NUMBERS) {
                                    if ('ltr' == $TEXT_DIRECTION) {
                                        $text_day .= " &lrm;($gid)&lrm;";
                                    } else {
                                        $text_day .= " &rlm;($gid)&rlm;";
                                    }
                                }

                                $text_day .= "</a><br>\n";

                                $text_day .= '<div class="indent';

                                if ('rtl' == $TEXT_DIRECTION) {
                                    $text_day .= '_rtl';
                                }

                                $text_day .= '">';

                                $text_day .= $text_fact;

                                $text_day .= "</div><br>\n";
                            } elseif (!$filterout) {
                                $count_private++;
                            }
                        }
                    }
                }

                $dayfamlist = [];

                reset($myfamlist);

                $count_private_fam = 0;

                if ('' == $filtersx) {
                    foreach ($myfamlist as $gid => $fam) {
                        $display = true;

                        if ('living' == $filterof) {
                            $parents = find_parents($gid);

                            if (is_dead_id($parents['HUSB']) || is_dead_id($parents['WIFE'])) {
                                $display = false;
                            }
                        }

                        if ($display) {
                            if (preg_match("/$pregquery/i", $fam['gedcom']) > 0) {
                                $filterout = false;

                                $name = preg_replace(["/ [jJsS][rR]\.?,/", '/ I+,/', '/^[a-z. ]*/'], [',', ',', ''], $fam['name']);

                                $names = preg_preg_split('/[,+]/', $name);

                                $fam['name'] = check_NN($names);

                                $indilines = preg_split("\n", $fam['gedcom']);

                                $factrec = '';

                                $lct = count($indilines);

                                $text_fact = '';

                                for ($i = 1; $i <= $lct; $i++) {
                                    $text_temp = '';

                                    if ($i < $lct) {
                                        $line = $indilines[$i];
                                    }

                                    if (empty($line)) {
                                        $line = ' ';
                                    }

                                    if ($i == $lct || ('1' == $line[0])) {
                                        if (!empty($factrec)) {
                                            $ct = preg_match("/$pregquery/i", $factrec, $match);

                                            if ($ct > 0) {
                                                $text_temp .= get_calendar_fact($factrec, $action, $filterof, $gid, $filterev);
                                            }
                                        }

                                        $factrec = '';
                                    }

                                    if ('filter' == $text_temp) {
                                        $filterout = true;
                                    } else {
                                        $text_fact .= $text_temp;
                                    }

                                    $factrec .= $line . "\n";
                                }

                                if (!empty($text_fact) && displayDetailsById($gid, 'FAM')) {
                                    $text_day .= "<a href=\"family.php?famid=$gid&amp;GEDCOM=" . $fam['file'] . '"><b>' . PrintReady(get_family_descriptor($gid)) . '</b>';

                                    $text_day .= "</a><br>\n";

                                    $text_day .= '<div class="indent';

                                    if ('rtl' == $TEXT_DIRECTION) {
                                        $text_day .= '_rtl';
                                    }

                                    $text_day .= '">';

                                    $text_day .= $text_fact;

                                    $text_day .= "</div><br>\n";
                                } elseif (!$filterout) {
                                    $count_private++;
                                }
                            }
                        }
                    }
                }

                // Print the calendar day list

                if ($show_not_set) {
                    if ('' != $text_day) {
                        print '<span class="cal_day"';

                        if ('rtl' == $TEXT_DIRECTION) {
                            print ' style="float: right;"';
                        }

                        print ' >' . $pgv_lang['day_not_set'] . '</span>';

                        print '<br style="clear: both">';
                    }

                    $show_not_set = false;

                    $show_no_day = -1;
                }

                print "<div id=\"day$k-$j\" class=\"details1\" style=\"width: 120px; height: ";

                if ('preview' == $view) {
                    print '100%;';
                } else {
                    print '150px; overflow: auto;';
                }

                print "\">\n";

                print $text_day;

                if ($count_private > 0) {
                    print '<a name="p">' . $pgv_lang['private'];

                    print '</a> (' . $count_private . ') ';

                    print_help_link('privacy_error_help', 'qm');

                    print "<br>\n";
                }

                if ('preview' == $view) {
                    print '<br>';

                    if (empty($text_day)) {
                        print '<br><br><br>';
                    }
                }

                print "</div>\n";
            } else {
                print "<br>\n";
            }

            print "\t\t</td>\n";

            $monthstart += (60 * 60 * 24);

            $mmon = mb_strtolower(adodb_date('M', $monthstart));

            if (($mmon != mb_strtolower($month)) && ($k > 2)) {
                if (6 == $show_no_day) {
                    $show_no_day = $k;

                    if (6 == $j) {
                        $show_no_day++;
                    }
                } elseif ($show_no_day < 0) {
                    $k = 6;
                }
            }
        } //-- end day for loop

        print "\t</tr>\n";
    } //-- end week for loop

    if ('preview' == $view) {
        print '<tr><td colspan="7">';
    }
}
if ('preview' == $view) {
    if (isset($myindilist[$gid]['file'])) {
        $showfile = $myindilist[$gid]['file'];
    } else {
        $showfile = $myfamlist[$gid]['file'];
    }

    $showfilter = '';

    if ('all' != $filterof) {
        $showfilter = ('living' == $filterof ? $pgv_lang['living_only'] : $pgv_lang['recent_events']);
    }

    if (!empty($filtersx)) {
        if (!empty($showfilter)) {
            $showfilter .= ' - ';
        }

        $showfilter .= ('M' == $filtersx ? $pgv_lang['male'] : $pgv_lang['female']);
    }

    if ('all' != $filterev) {
        if (!empty($showfilter)) {
            $showfilter .= ' - ';
        }

        $showfilter .= $factarray[$filterev];
    }

    print '<br>' . $showfile . ' (' . $pgv_lang['filter'] . ': ';

    if (!empty($showfilter)) {
        print $showfilter . ")\n";
    } else {
        print $pgv_lang['all'] . ")\n";
    }

    print '</td></tr>';
}
print "</table>\n";
print "</div><br>\n";
print_footer();
