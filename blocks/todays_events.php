<?php

/**
 * On This Day Events Block
 *
 * This block will print a list of today's events
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
 * $Id: todays_events.php,v 1.1 2005/10/07 18:08:13 skenow Exp $
 */
$PGV_BLOCKS['print_todays_events']['name'] = $pgv_lang['todays_events_block'];
$PGV_BLOCKS['print_todays_events']['descr'] = $pgv_lang['todays_events_descr'];
$PGV_BLOCKS['print_todays_events']['canconfig'] = false;

//-- today's events block
//-- this block prints a list of today's upcoming events of living people in your gedcom
function print_todays_events($block, $config, $side, $index)
{
    global $pgv_lang, $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $SHOW_ID_NUMBERS, $command, $TEXT_DIRECTION, $SHOW_FAM_ID_NUMBERS;

    global $PGV_IMAGE_DIR, $PGV_IMAGES, $GEDCOM, $REGEXP_DB, $DEBUG, $ASC, $INDEX_DIRECTORY, $IGNORE_FACTS, $IGNORE_YEAR;

    $block = true;            // Always restrict this block's height

    if ('user' == $command) {
        $filter = 'living';
    } else {
        $filter = 'all';
    }

    $skipfacts = 'CHAN,BAPL,SLGC,SLGS,ENDL';

    $daytext = '';

    $action = 'today';

    $dayindilist = [];

    $dayfamlist = [];

    $found_facts = [];

    $cache_load = false;

    if (('user' == $command) && isset($_SESSION['todays_events'][$command][$GEDCOM]) && (!isset($DEBUG) || (false === $DEBUG))) {
        $found_facts = $_SESSION['todays_events'][$command][$GEDCOM];

        $cache_load = true;
    } elseif (('gedcom' == $command) && (file_exists($INDEX_DIRECTORY . $GEDCOM . '_todays.php')) && (!isset($DEBUG) || (false === $DEBUG))) {
        $modtime = filemtime($INDEX_DIRECTORY . $GEDCOM . '_todays.php');

        $mday = date('d', $modtime);

        if ($mday == $day) {
            $fp = fopen($INDEX_DIRECTORY . $GEDCOM . '_todays.php', 'rb');

            $fcache = fread($fp, filesize($INDEX_DIRECTORY . $GEDCOM . '_todays.php'));

            fclose($fp);

            $found_facts = unserialize($fcache);

            $cache_load = true;
        }
    }

    if (!$cache_load) {
        if ($REGEXP_DB) {
            $query = "2 DATE[^\n]*[^1-9]$day $month";
        } else {
            $query = "%2 DATE %$day $month%";
        }

        $dayindilist = search_indis($query);

        $dayfamlist = search_fams($query);
    }

    if ((count($dayindilist) > 0) || (count($dayfamlist) > 0)) {
        $query = "2 DATE[^\n]*[^1-9]$day $month";

        foreach ($dayindilist as $gid => $indi) {
            $disp = true;

            if (('living' == $filter) && (1 == is_dead_id($gid))) {
                $disp = false;
            } elseif ($HIDE_LIVE_PEOPLE) {
                $disp = displayDetailsByID($gid);
            }

            if ($disp) {
                $facts = get_all_subrecords($indi['gedcom'], $skipfacts, false, false);

                foreach ($facts as $index => $factrec) {
                    $ct = preg_match("/$query/i", $factrec, $match);

                    if ($ct > 0) {
                        $found_facts[] = [$gid, $factrec, 'INDI'];
                    }
                }
            }
        }

        foreach ($dayfamlist as $gid => $fam) {
            $disp = true;

            if ('living' == $filter) {
                $parents = find_parents_in_record($fam['gedcom']);

                if (1 == is_dead_id($parents['HUSB'])) {
                    $disp = false;
                } elseif ($HIDE_LIVE_PEOPLE) {
                    $disp = displayDetailsByID($parents['HUSB']);
                }

                if ($disp) {
                    if (1 == is_dead_id($parents['WIFE'])) {
                        $disp = false;
                    } elseif ($HIDE_LIVE_PEOPLE) {
                        $disp = displayDetailsByID($parents['WIFE']);
                    }
                }
            } elseif ($HIDE_LIVE_PEOPLE) {
                $disp = displayDetailsByID($gid, 'FAM');
            }

            if ($disp) {
                $facts = get_all_subrecords($fam['gedcom'], $skipfacts, false, false);

                foreach ($facts as $index => $factrec) {
                    $ct = preg_match("/$query/i", $factrec, $match);

                    if ($ct > 0) {
                        $found_facts[] = [$gid, $factrec, 'FAM'];
                    }
                }
            }
        }
    }

    if (count($found_facts) > 0 && is_array($found_facts)) {
        print '<div id="on_this_day_events" class="block">';

        print '<table class="blockheader" cellspacing="0" cellpadding="0" style="direction:ltr;"><tr>';

        print '<td class="blockh1" >&nbsp;</td>';

        print '<td class="blockh2" ><div class="blockhc">';

        print '<b>' . $pgv_lang['on_this_day'] . '</b>';

        print_help_link('index_onthisday_help', 'qm');

        print '</div></td>';

        print "<td class=\"blockh3\">&nbsp;</td></tr>\n";

        print '</table>';

        print '<div class="blockcontent" >';

        if ($block) {
            print "<div class=\"small_inner_block\">\n";
        }

        $ASC = 1;

        $IGNORE_FACTS = 1;

        $IGNORE_YEAR = 0;

        uasort($found_facts, 'compare_facts');

        $lastgid = '';

        foreach ($found_facts as $index => $factarray) {
            if ('INDI' == $factarray[2]) {
                $gid = $factarray[0];

                $factrec = $factarray[1];

                if ((displayDetailsByID($gid)) && (!FactViewRestricted($gid, $factrec))) {
                    $indirec = find_person_record($gid);

                    $text = get_calendar_fact($factrec, $action, $filter, $gid);

                    if ('filter' != $text) {
                        if ($lastgid != $gid) {
                            print '<img id="box-' . $gid . '-' . $index . "-sex\" src=\"$PGV_IMAGE_DIR/";

                            if (preg_match('/1 SEX M/', $indirec) > 0) {
                                print $PGV_IMAGES['sex']['small'] . '" title="' . $pgv_lang['male'] . '" alt="' . $pgv_lang['male'];
                            } elseif (preg_match('/1 SEX F/', $indirec) > 0) {
                                print $PGV_IMAGES['sexf']['small'] . '" title="' . $pgv_lang['female'] . '" alt="' . $pgv_lang['female'];
                            } else {
                                print $PGV_IMAGES['sexn']['small'] . '" title="' . $pgv_lang['sex'] . ' ' . $pgv_lang['unknown'] . '" alt="' . $pgv_lang['sex'] . ' ' . $pgv_lang['unknown'];
                            }

                            print '" class="sex_image">';

                            $name = check_NN(get_sortable_name($gid));

                            print "<a href=\"individual.php?pid=$gid&amp;ged=" . $GEDCOM . '"><b>' . PrintReady($name) . '</b>';

                            if ($SHOW_ID_NUMBERS) {
                                if ('ltr' == $TEXT_DIRECTION) {
                                    print " &lrm;($gid)&lrm;";
                                } else {
                                    print " &rlm;($gid)&rlm;";
                                }
                            }

                            print "</a><br>\n";

                            $lastgid = $gid;
                        }

                        print '<div class="indent' . ('rtl' == $TEXT_DIRECTION ? '_rtl' : '') . '">';

                        print $text;

                        print '</div><br>';
                    }
                }
            }

            if ('FAM' == $factarray[2]) {
                $gid = $factarray[0];

                $factrec = $factarray[1];

                if ((displayDetailsByID($gid, 'FAM')) && (!FactViewRestricted($gid, $factrec))) {
                    $famrec = find_family_record($gid);

                    $name = get_family_descriptor($gid);

                    $text = get_calendar_fact($factrec, $action, $filter, $gid);

                    if ('filter' != $text) {
                        if ($lastgid != $gid) {
                            print "<a href=\"family.php?famid=$gid&amp;ged=" . $GEDCOM . '"><b>' . PrintReady($name) . '</b>';

                            if ($SHOW_FAM_ID_NUMBERS) {
                                if ('ltr' == $TEXT_DIRECTION) {
                                    print " &lrm;($gid)&lrm;";
                                } else {
                                    print " &rlm;($gid)&rlm;";
                                }
                            }

                            print "</a><br>\n";

                            $lastgid = $gid;
                        }

                        print '<div class="indent' . ('rtl' == $TEXT_DIRECTION ? '_rtl' : '') . '">';

                        print $text;

                        print '</div><br>';
                    }
                }
            }
        }

        if ($block) {
            print "</div>\n";
        }

        print '</div>'; // blockcontent
        print '</div>'; // block
    }

    //-- store the results in the session to improve speed of future page loads

    if ('user' == $command) {
        $_SESSION['todays_events'][$command][$GEDCOM] = $found_facts;
    } elseif (('gedcom' == $command) && (is_writable($INDEX_DIRECTORY))) {
        $fp = fopen($INDEX_DIRECTORY . '/' . $GEDCOM . '_todays.php', 'wb');

        fwrite($fp, serialize($found_facts));

        fclose($fp);
    }
}
