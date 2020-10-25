<?php

/**
 * Random Media Block
 *
 * This block will randomly choose media items and show them in a block
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
 * $Id: random_media.php,v 1.1 2005/10/07 18:08:13 skenow Exp $
 */

//-- only enable this block if multi media has been enabled
if ($MULTI_MEDIA) {
    $PGV_BLOCKS['print_random_media']['name'] = $pgv_lang['random_media_block'];

    $PGV_BLOCKS['print_random_media']['descr'] = $pgv_lang['random_media_descr'];

    $PGV_BLOCKS['print_random_media']['canconfig'] = false;

    //-- function to display a random picture from the gedcom

    function print_random_media($block, $config, $side, $index)
    {
        global $pgv_lang, $GEDCOM, $medialist, $MULTI_MEDIA, $TEXT_DIRECTION, $PGV_IMAGE_DIR, $PGV_IMAGES, $MEDIA_EXTERNAL, $MEDIA_DIRECTORY, $SHOW_SOURCES;

        if (!$MULTI_MEDIA) {
            return;
        }

        $medialist = [];

        get_media_list();

        $ct = count($medialist);

        if ($ct > 0) {
            $disp = false;

            $i = 0;

            while (!$disp && $i < 10) {
                $index = random_int(0, $ct - 1);

                $value = $medialist[$index];

                $links = $value['link'];

                $disp = true;

                if (0 != count($links)) {
                    foreach ($links as $id => $type) {
                        if ('INDI' == $type) {
                            $disp = $disp && displayDetailsByID($id);
                        } elseif ('FAM' == $type) {
                            $parents = find_parents($id);

                            $disp = $disp && displayDetailsByID($parents['HUSB']);

                            $disp = $disp && displayDetailsByID($parents['WIFE']);
                        } elseif ('SOUR' == $type) {
                            $disp = $disp && showFact('OBJE', $id) && ($SHOW_SOURCES >= getUserAccessLevel(getUserName()));
                        }
                    }
                }

                $i++;
            }

            if (!$disp) {
                return false;
            }

            print '<div id="random_picture" class="block">';

            print '<table class="blockheader" cellspacing="0" cellpadding="0" style="direction:ltr;"><tr>';

            print '<td class="blockh1" >&nbsp;</td>';

            print '<td class="blockh2" ><div class="blockhc">';

            print '<b>' . $pgv_lang['random_picture'] . '</b>';

            print_help_link('index_media_help', 'qm');

            print '</div></td>';

            print "<td class=\"blockh3\">&nbsp;</td></tr>\n";

            print '</table>';

            print '<div class="blockcontent" >';

            //			if ($block) print "<div class=\"small_inner_block\">\n";

            $imgwidth = 300;

            $imgheight = 300;

            if (preg_match("'://'", $value['file'])) {
                $image_type = ['bmp', 'gif', 'jpeg', 'jpg', 'pcx', 'png', 'tiff'];

                $path_end = mb_substr($value['file'], mb_strlen($value['file']) - 5);

                $type = mb_strtolower(mb_substr($path_end, mb_strpos($path_end, '.') + 1));

                if (in_array($type, $image_type, true)) {
                    $imgwidth = 400;

                    $imgheight = 500;
                } else {
                    $imgwidth = 800;

                    $imgheight = 400;
                }
            } elseif ((preg_match("'://'", $MEDIA_DIRECTORY) > 0) || (file_exists(filename_decode($value['file'])))) {
                $imgsize = getimagesize(filename_decode($value['file']));

                if ($imgsize) {
                    $imgwidth = $imgsize[0] + 50;

                    $imgheight = $imgsize[1] + 50;
                }
            }

            print '<table id="random_picture_box" width="95%"><tr><td valign="top"';

            if ($block) {
                print ' align="center" class="details1"';
            } else {
                print ' class="details2"';
            }

            print ' >';

            if (mb_stristr($value['file'], 'mailto:')) {
                if ($MEDIA_EXTERNAL) {
                    print '<a href="' . urlencode($value['file']) . '">';
                }
            } else {
                print "<a href=\"#\" onclick=\"return openImage('" . urlencode($value['file']) . "',$imgwidth, $imgheight);\">";
            }

            //-- generate a thumbnail if one does not exist

            if (!file_exists(filename_decode($value['thumb'])) && 'Y' != $value['THUM']) {
                if (is_writable($MEDIA_DIRECTORY . 'thumbs')) {
                    generate_thumbnail($value['file'], $value['thumb']);
                }
            }

            if ($block) {
                if (file_exists(filename_decode($value['thumb'])) || mb_strstr($value['thumb'], '://')) {
                    print '<img src="' . $value['thumb'] . '" border="0" class="thumbnail" alt="" ';

                    $imgsize = getimagesize(filename_decode($value['thumb']));

                    if ($imgsize[0] > 175) {
                        print 'width="175" ';
                    }

                    print '>';
                }
            } else {
                if (file_exists(filename_decode($value['file'])) || mb_strstr($value['file'], '://')) {
                    print '<img src="' . $value['file'] . '" border="0" class="thumbnail" alt="" ';

                    $imgsize = getimagesize(filename_decode($value['file']));

                    if ($imgsize[0] > 175) {
                        print 'width="175" ';
                    }

                    print '>';
                }
            }

            if (!($MEDIA_EXTERNAL) && mb_stristr($value['file'], 'mailto:')) {
            } else {
                print "</a>\n";
            }

            if ($block) {
                print '<br>';
            } else {
                print '</td><td class="details2">';
            }

            if (mb_stristr($value['file'], 'mailto:')) {
                if ($MEDIA_EXTERNAL) {
                    print '<a href="' . urlencode($value['file']) . '">';
                }
            } else {
                print "<a href=\"#\" onclick=\"return openImage('" . urlencode($value['file']) . "',$imgwidth, $imgheight);\">";
            }

            if ($value['title'] != $value['file']) {
                print '<b>' . PrintReady($value['title']) . '</b><br>';
            }

            if (!($MEDIA_EXTERNAL) && mb_stristr($value['file'], 'mailto:')) {
            } else {
                print '</a>';
            }

            $links = $value['link'];

            if (0 != count($links)) {
                foreach ($links as $id => $type) {
                    if (('INDI' == $type) && (displayDetailsByID($id))) {
                        print ' <a href="individual.php?pid=' . $id . '">' . $pgv_lang['view_person'] . ' -' . PrintReady(get_person_name($id)) . '</a><br>';
                    }

                    if ('FAM' == $type) {
                        print ' <a href="family.php?famid=' . $id . '">' . $pgv_lang['view_family'] . ' - ' . PrintReady(get_family_descriptor($id)) . '</a><br>';
                    }

                    if ('SOUR' == $type) {
                        print ' <a href="source.php?sid=' . $id . '">' . $pgv_lang['view_source'] . ' - ' . PrintReady(get_source_descriptor($id)) . '</a><br>';
                    }
                }
            }

            print '<br><div class="indent' . ('rtl' == $TEXT_DIRECTION ? '_rtl' : '') . '">';

            print_fact_notes($value['gedcom'], $value['level']);

            print '</div>';

            print "</td></tr></table>\n";

            //			if ($block) print "</div>\n";
            print '</div>'; // blockcontent
            print '</div>'; // block
        }
    }
}
