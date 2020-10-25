<?php

/**
 * PopUp Window to provide editing features.
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2005  John Finlay and Others
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
 * @version    $Id: edit_interface.php,v 1.1 2005/10/07 18:08:01 skenow Exp $
 */
require 'config.php';
require 'includes/functions_edit.php';
require $PGV_BASE_DIRECTORY . $factsfile['english'];
if (file_exists($PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE])) {
    require $PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE];
}

require $PGV_BASE_DIRECTORY . 'languages/countries.en.php';
if (file_exists($PGV_BASE_DIRECTORY . 'languages/countries.' . $lang_short_cut[$LANGUAGE] . '.php')) {
    require $PGV_BASE_DIRECTORY . 'languages/countries.' . $lang_short_cut[$LANGUAGE] . '.php';
}
asort($countries);

if ($_SESSION['cookie_login']) {
    header("Location: login.php?type=simple&ged=$GEDCOM&url=edit_interface.php" . urlencode('?' . $QUERY_STRING));

    exit;
}

// Remove slashes
if (isset($text)) {
    foreach ($text as $l => $line) {
        $text[$l] = stripslashes($line);
    }
}

if (!isset($action)) {
    $action = '';
}
if (!isset($linenum)) {
    $linenum = '';
}
$uploaded_files = [];

$templefacts = ['SLGC', 'SLGS', 'BAPL', 'ENDL', 'CONL'];
$nonplacfacts = ['SLGC', 'SLGS', 'ENDL'];
$nondatefacts = ['ABBR', 'ADDR', 'AUTH', 'EMAIL', 'FAX', 'NAME', 'NOTE', 'OBJE', 'PHON', 'PUBL', 'REPO', 'SEX', 'SOUR', 'TEXT', 'TITL', 'WWW', '_EMAIL'];

// items for ASSO RELA selector :
$assokeys = [
    'attendant',
    'attending',
    'circumciser',
    'civil_registrar',
    'friend',
    'godfather',
    'godmother',
    'godparent',
    'informant',
    'lodger',
    'nurse',
    'priest',
    'rabbi',
    'registry_officer',
    'servant',
    'twin',
    'twin_brother',
    'twin_sister',
    'witness',
    '', // DO NOT DELETE
];
$assorela = [];
foreach ($assokeys as $indexval => $key) {
    if (isset($pgv_lang[(string)$key])) {
        $assorela[(string)$key] = $pgv_lang[(string)$key];
    } else {
        $assorela[(string)$key] = "? $key";
    }
}
natsort($assorela);

print_simple_header("Edit Interface $VERSION");

?>
    <script type="text/javascript">
        <!--
        function findIndi(field, indiname) {
            pastefield = field;
            window.open('findid.php?name_filter=' + indiname, '', 'top=55,left=55,width=500,height=500,scrollbars=1,resizeable=1');
            return false;
        }

        function findPlace(field) {
            pastefield = field;
            window.open('findplace.php?place=' + field.value, '', 'top=55,left=55,width=500,height=500,scrollbars=1,resizeable=1');
            return false;
        }

        function findMedia(field) {
            pastefield = field;
            window.open('findmedia.php', '', 'top=55,left=55,width=500,height=500,scrollbars=1,resizeable=1');
            return false;
        }

        function findSource(field) {
            pastefield = field;
            window.open('findsource.php', '', 'top=55,left=55,width=500,height=500,scrollbars=1,resizeable=1');
            return false;
        }

        function findRepository(field) {
            pastefield = field;
            window.open('findrepo.php', '', 'top=55,left=55,width=500,height=500,scrollbars=1,resizeable=1');
            return false;
        }

        function findFamily(field) {
            pastefield = field;
            window.open('findfamily.php', '', 'top=55,left=55,width=500,height=500,scrollbars=1,resizeable=1');
            return false;
        }

        function addnewsource(field) {
            pastefield = field;
            window.open('edit_interface.php?action=addnewsource&pid=newsour', '', 'top=70,left=70,width=600,height=500,resizable=1,scrollbars=1');
            return false;
        }

        function addnewrepository(field) {
            pastefield = field;
            window.open('edit_interface.php?action=addnewrepository&pid=newrepo', '', 'top=70,left=70,width=600,height=500,resizable=1,scrollbars=1');
            return false;
        }

        function openerpasteid(id) {
            window.opener.paste_id(id);
            window.close();
        }

        function paste_id(value) {
            pastefield.value = value;
        }

        //-->
    </script>
<?php
//-- check if user has acces to the gedcom record
$disp = false;
$factdisp = true;
$factedit = true;
if (!empty($pid)) {
    $pid = clean_input($pid);

    if (('newsour' != $pid) and ('newrepo' != $pid)) {
        if (!isset($pgv_changes[$pid . '_' . $GEDCOM])) {
            $gedrec = find_gedcom_record($pid);
        } else {
            $gedrec = find_record_in_file($pid);
        }

        $ct = preg_match("/0 @$pid@ (.*)/", $gedrec, $match);

        if ($ct > 0) {
            $type = trim($match[1]);

            //-- if the record is for an INDI then check for display privileges for that indi

            if ('INDI' == $type) {
                $disp = displayDetailsByID($pid);

                //-- if disp is true, also check for resn access

                if (true === $disp) {
                    $subs = get_all_subrecords($gedrec, '', false, false);

                    foreach ($subs as $indexval => $sub) {
                        if (true === FactViewRestricted($pid, $sub)) {
                            $factdisp = false;
                        }

                        if (true === FactEditRestricted($pid, $sub)) {
                            $factedit = false;
                        }
                    }
                }
            } //-- for FAM check for display privileges on both parents

            elseif ('FAM' == $type) {
                //-- check if there are restrictions on the facts

                $subs = get_all_subrecords($gedrec, '', false, false);

                foreach ($subs as $indexval => $sub) {
                    if (true === FactViewRestricted($pid, $sub)) {
                        $factdisp = false;
                    }

                    if (true === FactEditRestricted($pid, $sub)) {
                        $factedit = false;
                    }
                }

                //-- check if we can display both parents

                $parents = find_parents_in_record($gedrec);

                $disp = displayDetailsByID($parents['HUSB']);

                if ($disp) {
                    $disp = displayDetailsByID($parents['WIFE']);
                }
            } else {
                $disp = true;
            }
        }
    } else {
        $disp = true;
    }
} elseif (!empty($famid)) {
    $famid = clean_input($famid);

    if ('new' != $famid) {
        if (!isset($pgv_changes[$famid . '_' . $GEDCOM])) {
            $gedrec = find_gedcom_record($famid);
        } else {
            $gedrec = find_record_in_file($famid);
        }

        $ct = preg_match("/0 @$famid@ (.*)/", $gedrec, $match);

        if ($ct > 0) {
            $type = trim($match[1]);

            //-- if the record is for an INDI then check for display privileges for that indi

            if ('INDI' == $type) {
                $disp = displayDetailsByID($famid);

                //-- if disp is true, also check for resn access

                if (true === $disp) {
                    $subs = get_all_subrecords($gedrec, '', false, false);

                    foreach ($subs as $indexval => $sub) {
                        if (true === FactViewRestricted($famid, $sub)) {
                            $factdisp = false;
                        }

                        if (true === FactEditRestricted($famid, $sub)) {
                            $factedit = false;
                        }
                    }
                }
            } //-- for FAM check for display privileges on both parents

            elseif ('FAM' == $type) {
                //-- check if there are restrictions on the facts

                $subs = get_all_subrecords($gedrec, '', false, false);

                foreach ($subs as $indexval => $sub) {
                    if (true === FactViewRestricted($famid, $sub)) {
                        $factdisp = false;
                    }

                    if (true === FactEditRestricted($famid, $sub)) {
                        $factedit = false;
                    }
                }

                //-- check if we can display both parents

                $parents = find_parents_in_record($gedrec);

                $disp = displayDetailsByID($parents['HUSB']);

                if ($disp) {
                    $disp = displayDetailsByID($parents['WIFE']);
                }
            } else {
                $disp = true;
            }
        }
    }
} elseif (('addchild' != $action) && ('addchildaction' != $action)) {
    print "<span class=\"error\">The \$pid variable was empty.	Unable to perform $action.</span>";

    print_simple_footer();

    $disp = true;
} else {
    $disp = true;
}

if ((!userCanEdit(getUserName())) || (!$disp) || (!$ALLOW_EDIT_GEDCOM)) {
    //print "pid: $pid<br>";

    //print "gedrec: $gedrec<br>";

    print $pgv_lang['access_denied'];

    //-- display messages as to why the editing access was denied

    if (!userCanEdit(getUserName())) {
        print '<br>' . $pgv_lang['user_cannot_edit'];
    }

    if (!$ALLOW_EDIT_GEDCOM) {
        print '<br>' . $pgv_lang['gedcom_editing_disabled'];
    }

    if (!$disp) {
        print '<br>' . $pgv_lang['privacy_prevented_editing'];

        if (!empty($pid)) {
            print '<br>' . $pgv_lang['privacy_not_granted'] . " pid $pid.";
        }

        if (!empty($famid)) {
            print '<br>' . $pgv_lang['privacy_not_granted'] . " famid $famid.";
        }
    }

    print_simple_footer();

    exit;
}

if (!isset($type)) {
    $type = '';
}
if ('INDI' == $type) {
    print '<b>' . PrintReady(get_person_name($pid)) . '</b><br>';
} elseif ('FAM' == $type) {
    print '<b>' . PrintReady(get_person_name($parents['HUSB'])) . ' + ' . PrintReady(get_person_name($parents['WIFE'])) . '</b><br>';
} elseif ('SOUR' == $type) {
    print '<b>' . PrintReady(get_source_descriptor($pid)) . '</b><br>';
}
if (mb_strstr($action, 'addchild')) {
    if (empty($famid)) {
        print '<b>' . $pgv_lang['add_unlinked_person'] . "</b>\n";

        print_help_link('edit_add_unlinked_person_help', 'qm');
    } else {
        print '<b>' . $pgv_lang['add_child'] . "</b>\n";

        print_help_link('edit_add_child_help', 'qm');
    }
} elseif (mb_strstr($action, 'addspouse')) {
    print '<b>' . $pgv_lang['add_' . mb_strtolower($famtag)] . "</b>\n";

    print_help_link('edit_add_spouse_help', 'qm');
} elseif (mb_strstr($action, 'addnewparent')) {
    if ('WIFE' == $famtag) {
        print '<b>' . $pgv_lang['add_mother'] . "</b>\n";
    } else {
        print '<b>' . $pgv_lang['add_father'] . "</b>\n";
    }

    print_help_link('edit_add_parent_help', 'qm');
} else {
    if (isset($factarray[$type])) {
        print '<b>' . $factarray[$type] . '</b>';
    }
}

if ('delete' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if (!empty($linenum)) {
        if (0 == $linenum) {
            if (delete_gedrec($pid)) {
                print $pgv_lang['gedrec_deleted'];
            }
        } else {
            $gedlines = preg_preg_split("/\n/", $gedrec);

            $newged = '';

            for ($i = 0; $i < $linenum; $i++) {
                $newged .= $gedlines[$i] . "\n";
            }

            if (isset($gedlines[$linenum])) {
                $fields = preg_preg_split("/\s/", $gedlines[$linenum]);

                $glevel = $fields[0];

                $i++;

                if ($i < count($gedlines)) {
                    while ((isset($gedlines[$i])) && ($gedlines[$i][0] > $glevel)) {
                        $i++;
                    }

                    while ($i < count($gedlines)) {
                        $newged .= $gedlines[$i] . "\n";

                        $i++;
                    }
                }
            }

            if (replace_gedrec($pid, $newged)) {
                print '<br><br>' . $pgv_lang['gedrec_deleted'];
            }
        }
    }
} //-- print a form to edit the raw gedcom record in a large textarea
elseif ('editraw' == $action) {
    if (!$factedit) {
        print '<br>' . $pgv_lang['privacy_prevented_editing'];

        if (!empty($pid)) {
            print '<br>' . $pgv_lang['privacy_not_granted'] . " pid $pid.";
        }

        if (!empty($famid)) {
            print '<br>' . $pgv_lang['privacy_not_granted'] . " famid $famid.";
        }

        print_simple_footer();

        exit;
    }

    print '<br><b>' . $pgv_lang['edit_raw'] . '</b>';

    print_help_link('edit_edit_raw_help', 'qm');

    print "<form method=\"post\" action=\"edit_interface.php\">\n";

    print "<input type=\"hidden\" name=\"action\" value=\"updateraw\">\n";

    print "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";

    print_specialchar_link('newgedrec', true);

    print '<textarea name="newgedrec" id="newgedrec" rows="20" cols="82" dir="ltr">' . $gedrec . "</textarea>\n<br>";

    print '<input type="submit" value="' . $pgv_lang['save'] . "\"><br>\n";

    print "</form>\n";
} //-- edit a fact record in a form
elseif ('edit' == $action) {
    init_calendar_popup();

    print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">\n";

    print "<input type=\"hidden\" name=\"action\" value=\"update\">\n";

    print "<input type=\"hidden\" name=\"linenum\" value=\"$linenum\">\n";

    print "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";

    print '<table class="facts_table">';

    $gedlines = preg_split("\n", $gedrec);    // -- find the number of lines in the record

    $fields = preg_preg_split("/\s/", $gedlines[$linenum]);

    $glevel = $fields[0];

    $level = $glevel;

    $type = trim($fields[1]);

    $level1type = $type;

    if (count($fields) > 2) {
        $ct = preg_match('/@.*@/', $fields[2]);

        $levellink = $ct > 0;
    } else {
        $levellink = false;
    }

    $tags = [];

    $i = $linenum;

    // Loop on existing tags :

    do {
        $text = '';

        for ($j = 2, $jMax = count($fields); $j < $jMax; $j++) {
            if ($j > 2) {
                $text .= ' ';
            }

            $text .= $fields[$j];
        }

        $iscont = false;

        while (($i + 1 < count($gedlines)) && (preg_match('/' . ($level + 1) . " (CON[CT])\s?(.*)/", $gedlines[$i + 1], $cmatch) > 0)) {
            $iscont = true;

            if ('CONT' == $cmatch[1]) {
                $text .= "\n";
            } elseif ($WORD_WRAPPED_NOTES) {
                $text .= ' ';
            }

            $text .= $cmatch[2];

            $i++;
        }

        $text = trim($text);

        $tags[] = $type;

        add_simple_tag($level . ' ' . $type . ' ' . $text);

        if ('DATE' == $type and !mb_strpos(@$gedlines[$i + 1], ' TIME')) {
            add_simple_tag(($level + 1) . ' TIME');
        }

        if ('MARR' == $type and !mb_strpos(@$gedlines[$i + 1], ' TYPE')) {
            add_simple_tag(($level + 1) . ' TYPE');
        }

        $i++;

        if (isset($gedlines[$i])) {
            $fields = preg_preg_split("/\s/", $gedlines[$i]);

            $level = $fields[0];

            if (isset($fields[1])) {
                $type = trim($fields[1]);
            }
        }
    } while (($level > $glevel) && ($i < count($gedlines)));

    // Now add some missing tags :

    if (in_array($tags[0], $templefacts, true)) {
        // 2 TEMP

        if (!in_array('TEMP', $tags, true)) {
            add_simple_tag('2 TEMP');
        }

        // 2 STAT

        if (!in_array('STAT', $tags, true)) {
            add_simple_tag('2 STAT');
        }
    }

    if ('GRAD' == $level1type) {
        // 1 GRAD

        // 2 TYPE

        if (!in_array('TYPE', $tags, true)) {
            add_simple_tag('2 TYPE');
        }
    }

    if ('EDUC' == $level1type or 'GRAD' == $level1type or 'OCCU' == $level1type) {
        // 1 EDUC|GRAD|OCCU

        // 2 CORP

        if (!in_array('CORP', $tags, true)) {
            add_simple_tag('2 CORP');
        }
    }

    if ('DEAT' == $level1type) {
        // 1 DEAT

        // 2 CAUS

        if (!in_array('CAUS', $tags, true)) {
            add_simple_tag('2 CAUS');
        }
    }

    if ('SOUR' == $level1type) {
        // 1 SOUR

        // 2 PAGE

        // 2 DATA

        // 3 TEXT

        if (!in_array('PAGE', $tags, true)) {
            add_simple_tag('2 PAGE');
        }

        if (!in_array('TEXT', $tags, true)) {
            add_simple_tag('3 TEXT');
        }
    }

    if ('REPO' == $level1type) {
        //1 REPO

        //2 CALN

        if (!in_array('CALN', $tags, true)) {
            add_simple_tag('2 CALN');
        }
    }

    if (!in_array($level1type, $nondatefacts, true)) {
        // 2 DATE

        // 3 TIME

        if (!in_array('DATE', $tags, true)) {
            add_simple_tag('2 DATE');

            add_simple_tag('3 TIME');
        }

        // 2 PLAC

        if (!in_array('PLAC', $tags, true) && !in_array($level1type, $nonplacfacts, true) && !in_array('TEMP', $tags, true)) {
            add_simple_tag('2 PLAC');
        }
    }

    if ('BURI' == $level1type) {
        // 1 BURI

        // 2 CEME

        if (!in_array('CEME', $tags, true)) {
            add_simple_tag('2 CEME');
        }
    }

    if ('BIRT' == $level1type or 'DEAT' == $level1type or 'EDUC' == $level1type or 'GRAD' == $level1type or 'OCCU' == $level1type or 'ORDN' == $level1type or 'RESI' == $level1type) {
        // 1 BIRT|DEAT|EDUC|GRAD|ORDN|RESI

        // 2 ADDR

        if (!in_array('ADDR', $tags, true)) {
            add_simple_tag('2 ADDR');
        }
    }

    if ('OCCU' == $level1type or 'RESI' == $level1type) {
        // 1 OCCU|RESI

        // 2 PHON|FAX|EMAIL|URL

        if (!in_array('PHON', $tags, true)) {
            add_simple_tag('2 PHON');
        }

        if (!in_array('FAX', $tags, true)) {
            add_simple_tag('2 FAX');
        }

        if (!in_array('EMAIL', $tags, true)) {
            add_simple_tag('2 EMAIL');
        }

        if (!in_array('URL', $tags, true)) {
            add_simple_tag('2 URL');
        }
    }

    if ('OBJE' == $level1type) {
        // 1 OBJE

        if (!$levellink) {
            // 2 FORM

            if (!in_array('FORM', $tags, true)) {
                add_simple_tag('2 FORM');
            }

            // 2 FILE

            if (!in_array('FILE', $tags, true)) {
                add_simple_tag('2 FILE');
            }

            // 2 TITL

            if (!in_array('TITL', $tags, true)) {
                add_simple_tag('2 TITL');
            }
        }

        // 2 _PRIM

        if (!in_array('_PRIM', $tags, true)) {
            add_simple_tag('2 _PRIM');
        }

        // 2 _THUM

        if (!in_array('_THUM', $tags, true)) {
            add_simple_tag('2 _THUM');
        }
    }

    // 2 RESN

    if (!in_array('RESN', $tags, true)) {
        add_simple_tag('2 RESN');
    }

    print '</table>';

    if ('SEX' != $level1type) {
        if ('ASSO' != $level1type && 'REPO' != $level1type) {
            print_add_layer('ASSO');
        }

        if ('SOUR' != $level1type && 'REPO' != $level1type) {
            print_add_layer('SOUR');
        }

        if ('NOTE' != $level1type) {
            print_add_layer('NOTE');
        }

        if ('OBJE' != $level1type && 'REPO' != $level1type && 'NOTE' != $level1type && $MULTI_MEDIA) {
            print_add_layer('OBJE');
        }
    }

    print '<br><input type="submit" value="' . $pgv_lang['save'] . "\"><br>\n";

    print "</form>\n";
} elseif ('add' == $action) {
    // Start of add section...

    // handle  MARRiage TYPE

    $type_val = '';

    if ('MARR_' == mb_substr($fact, 0, 5)) {
        $type_val = mb_substr($fact, 5);

        $fact = 'MARR';
    }

    $tags = [];

    $tags[0] = $fact;

    init_calendar_popup();

    print "<form method=\"post\" action=\"edit_interface.php\" enctype=\"multipart/form-data\">\n";

    print "<input type=\"hidden\" name=\"action\" value=\"update\">\n";

    print "<input type=\"hidden\" name=\"linenum\" value=\"new\">\n";

    print "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";

    print '<table class="facts_table">';

    if ('SOUR' == $fact) {
        add_simple_tag('1 SOUR @');
    } else {
        add_simple_tag('1 ' . $fact);
    }

    if ('EVEN' == $fact or 'GRAD' == $fact or 'MARR' == $fact) {
        // 1 EVEN|GRAD|MARR

        // 2 TYPE

        add_simple_tag('2 TYPE ' . $type_val);
    }

    if (in_array($fact, $templefacts, true)) {
        // 2 TEMP

        add_simple_tag('2 TEMP');

        // 2 STAT

        add_simple_tag('2 STAT');
    }

    if ('OBJE' == $fact) {
        // 1 OBJE

        // 2 FORM

        add_simple_tag('2 FORM');

        // 2 FILE

        add_simple_tag('2 FILE');

        // 2 TITL

        add_simple_tag('2 TITL');

        // 2 _PRIM

        add_simple_tag('2 _PRIM');

        // 2 _THUM

        add_simple_tag('2 _THUM');
    }

    if ('SOUR' == $fact) {
        // 1 SOUR

        // 2 PAGE

        add_simple_tag('2 PAGE');

        // 2 DATA

        // 3 TEXT

        add_simple_tag('3 TEXT');
    }

    if ('EDUC' == $fact or 'GRAD' == $fact or 'OCCU' == $fact) {
        // 1 EDUC|GRAD|OCCU

        // 2 CORP

        add_simple_tag('2 CORP');
    }

    if (!in_array($fact, $nondatefacts, true)) {
        // 2 DATE

        add_simple_tag('2 DATE');

        // 3 TIME

        add_simple_tag('3 TIME');

        // 2 PLAC

        if (!in_array($fact, $nonplacfacts, true)) {
            add_simple_tag('2 PLAC');
        }
    }

    if ('BURI' == $fact) {
        // 1 BURI

        // 2 CEME

        add_simple_tag('2 CEME');
    }

    if ('BIRT' == $fact or 'DEAT' == $fact or 'EDUC' == $fact or 'OCCU' == $fact or 'ORDN' == $fact or 'RESI' == $fact) {
        // 1 BIRT|DEAT|EDUC|OCCU|ORDN|RESI

        // 2 ADDR

        add_simple_tag('2 ADDR');
    }

    if ('OCCU' == $fact or 'RESI' == $fact) {
        // 1 OCCU|RESI

        // 2 PHON|FAX|EMAIL|URL

        add_simple_tag('2 PHON');

        add_simple_tag('2 FAX');

        add_simple_tag('2 EMAIL');

        add_simple_tag('2 URL');
    }

    if ('DEAT' == $fact) {
        // 1 DEAT

        // 2 CAUS

        add_simple_tag('2 CAUS');
    }

    if ('REPO' == $fact) {
        //1 REPO

        //2 CALN

        add_simple_tag('2 CALN');
    }

    // 2 RESN

    add_simple_tag('2 RESN');

    print '</table>';

    if (('ASSO' != $fact) && ('REPO' != $fact)) {
        print_add_layer('ASSO');
    }

    if (('SOUR' != $fact) && ('REPO' != $fact)) {
        print_add_layer('SOUR');
    }

    if ('NOTE' != $fact) {
        print_add_layer('NOTE');
    }

    if (('OBJE' != $fact) && ('REPO' != $fact)) {
        print_add_layer('OBJE');
    }

    print '<br><input type="submit" value="' . $pgv_lang['add'] . "\"><br>\n";

    print "</form>\n";
} elseif ('addchild' == $action) {
    print_indi_form('addchildaction', $famid);
} elseif ('addspouse' == $action) {
    print_indi_form('addspouseaction', $famid, '', '', $famtag);
} elseif ('addnewparent' == $action) {
    print_indi_form('addnewparentaction', $famid, '', '', $famtag);
} elseif ('addfamlink' == $action) {
    print "<form method=\"post\" name=\"addchildform\" action=\"edit_interface.php\">\n";

    print "<input type=\"hidden\" name=\"action\" value=\"linkfamaction\">\n";

    print "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";

    print "<input type=\"hidden\" name=\"famtag\" value=\"$famtag\">\n";

    print '<table class="facts_table">';

    print '<tr><td class="facts_label">' . $pgv_lang['family'] . '</td>';

    print '<td class="facts_value"><input type="text" name="famid" size="8"> ';

    print_findfamily_link('famid');

    print "\n</td></tr>";

    print "</table>\n";

    print '<input type="submit" value="' . $pgv_lang['set_link'] . "\"><br>\n";

    print "</form>\n";
} elseif ('linkfamaction' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if (!isset($pgv_changes[$famid . '_' . $GEDCOM])) {
        $famrec = find_gedcom_record($famid);
    } else {
        $famrec = find_record_in_file($famid);
    }

    $famrec = trim($famrec);

    if (!empty($famrec)) {
        $itag = 'FAMC';

        if ('HUSB' == $famtag || 'WIFE' == $famtag) {
            $itag = 'FAMS';
        }

        //-- update the individual record for the person

        if (0 == preg_match("/1 $itag @$famid@/", $gedrec)) {
            $gedrec = trim($gedrec) . "\r\n1 $itag @$famid@";

            if ($GLOBALS['DEBUG']) {
                print "<pre>$gedrec</pre>";
            }

            replace_gedrec($pid, $gedrec);
        }

        //-- if it is adding a new child to a family

        if ('CHIL' == $famtag) {
            if (0 == preg_match("/1 $famtag @$pid@/", $famrec)) {
                $famrec = trim($famrec) . "\r\n1 $famtag @$pid@";

                if ($GLOBALS['DEBUG']) {
                    print "<pre>$famrec</pre>";
                }

                replace_gedrec($famid, $famrec);
            }
        } //-- if it is adding a husband or wife

        else {
            //-- check if the family already has a HUSB or WIFE

            $ct = preg_match("/1 $famtag @(.*)@/", $famrec, $match);

            if ($ct > 0) {
                //-- get the old ID

                $spid = trim($match[1]);

                //-- only continue if the old husb/wife is not the same as the current one

                if ($spid != $pid) {
                    //-- change a of the old ids to the new id

                    $famrec = preg_replace("/1 $famtag @$spid@/", "1 $famtag @$pid@", $famrec);

                    if ($GLOBALS['DEBUG']) {
                        print "<pre>$famrec</pre>";
                    }

                    replace_gedrec($famid, $famrec);

                    //-- remove the FAMS reference from the old husb/wife

                    if (!empty($spid)) {
                        if (!isset($pgv_changes[$spid . '_' . $GEDCOM])) {
                            $srec = find_gedcom_record($spid);
                        } else {
                            $srec = find_record_in_file($spid);
                        }

                        if ($srec) {
                            $srec = preg_replace("/1 $itag @$famid@\s*/", '', $srec);

                            if ($GLOBALS['DEBUG']) {
                                print "<pre>$srec</pre>";
                            }

                            replace_gedrec($spid, $srec);
                        }
                    }
                }
            } else {
                $famrec .= "\r\n1 $famtag @$pid@";

                if ($GLOBALS['DEBUG']) {
                    print "<pre>$famrec</pre>";
                }

                replace_gedrec($famid, $famrec);
            }
        }
    }
} //-- add new source
elseif ('addnewsource' == $action) {
    ?>
    <script type="text/javascript">
        <!--
        function check_form(frm) {
            if (frm.TITL.value == "") {
                alert('<?php print $pgv_lang['must_provide'] . $factarray['TITL']; ?>');
                frm.TITL.focus();
                return false;
            }
            return true;
        }

        //-->
    </script>
    <b><?php print $pgv_lang['create_source'];

    $tabkey = 1; ?></b>
    <form method="post" action="edit_interface.php" onSubmit="return check_form(this);">
        <input type="hidden" name="action" value="addsourceaction">
        <input type="hidden" name="pid" value="newsour">
        <table class="facts_table">
            <tr>
                <td class="facts_label"><?php print $factarray['ABBR']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="ABBR" id="ABBR" value="" size="40" maxlength="255"> <?php print_specialchar_link('ABBR', false); ?></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['TITL']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="TITL" id="TITL" value="" size="60"> <?php print_specialchar_link('TITL', false); ?></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['AUTH']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="AUTH" id="AUTH" value="" size="40" maxlength="255"> <?php print_specialchar_link('AUTH', false); ?></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['PUBL']; ?></td>
                <td class="facts_value"><?php print_specialchar_link('PUBL', true); ?> <textarea tabindex="<?php print $tabkey; ?>" name="PUBL" id="PUBL" rows="5" cols="60"></textarea></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['REPO']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="REPO" id="REPO" value="" size="<?php print(mb_strlen($REPO_ID_PREFIX) + 4); ?>"> <?php print_findrepository_link('REPO');

    print_addnewrepository_link('REPO'); ?></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['CALN']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="CALN" id="CALN" value=""></td>
            </tr>
        </table>
        <input type="submit" value="<?php print $pgv_lang['create_source']; ?>">
    </form>
    <?php
} //-- create a source record from the incoming variables
elseif ('addsourceaction' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    $newgedrec = "0 @XREF@ SOUR\r\n";

    if (!empty($ABBR)) {
        $newgedrec .= "1 ABBR $ABBR\r\n";
    }

    if (!empty($TITL)) {
        $newgedrec .= "1 TITL $TITL\r\n";
    }

    if (!empty($AUTH)) {
        $newgedrec .= "1 AUTH $AUTH\r\n";
    }

    if (!empty($PUBL)) {
        $newgedrec .= "1 PUBL $PUBL\r\n";
    }

    if (!empty($REPO)) {
        $newgedrec .= "1 REPO @$REPO@\r\n";

        if (!empty($CALN)) {
            $newgedrec .= "2 CALN $CALN\r\n";
        }
    }

    $newlines = preg_preg_split("/\r?\n/", $newgedrec);

    $newged = $newlines[0] . "\r\n";

    for ($k = 1, $kMax = count($newlines); $k < $kMax; $k++) {
        if (((0 == preg_match("/\d .... .*/", $newlines[$k])) and 0 != mb_strlen($newlines[$k]))) {
            $newlines[$k] = '2 CONT ' . $newlines[$k];
        }

        if (mb_strlen($newlines[$k]) > 255) {
            while (mb_strlen($newlines[$k]) > 255) {
                $newged .= mb_substr($newlines[$k], 0, 255) . "\r\n";

                $newlines[$k] = mb_substr($newlines[$k], 255);

                $newlines[$k] = '2 CONC ' . $newlines[$k];
            }

            $newged .= trim($newlines[$k]) . "\r\n";
        } else {
            $newged .= trim($newlines[$k]) . "\r\n";
        }
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$newged</pre>";
    }

    $xref = append_gedrec($newged);

    if ($xref) {
        print "<br><br>\n" . $pgv_lang['new_source_created'] . '<br><br>';

        print "<a href=\"javascript:// SOUR $xref\" onclick=\"openerpasteid('$xref'); return false;\">" . $pgv_lang['paste_id_into_field'] . " <b>$xref</b></a>\n";
    }
} //-- add new repository
elseif ('addnewrepository' == $action) {
    ?>
    <script type="text/javascript">
        <!--
        function check_form(frm) {
            if (frm.NAME.value == "") {
                alert('<?php print $pgv_lang['must_provide'] . ' ' . $factarray['NAME']; ?>');
                frm.NAME.focus();
                return false;
            }
            return true;
        }

        //-->
    </script>
    <b><?php print $pgv_lang['create_repository'];

    $tabkey = 1; ?></b>
    <form method="post" action="edit_interface.php" onSubmit="return check_form(this);">
        <input type="hidden" name="action" value="addrepoaction">
        <input type="hidden" name="pid" value="newrepo">
        <table class="facts_table">
            <tr>
                <td class="facts_label"><?php print $factarray['NAME']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="NAME" id="NAME" value="" size="40" maxlength="255"> <?php print_specialchar_link('NAME', false); ?></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['ADDR']; ?></td>
                <td class="facts_value"><textarea tabindex="<?php print $tabkey; ?>" name="ADDR" id="ADDR" rows="5" cols="60"></textarea><?php print_specialchar_link('ADDR', true); ?> </td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['PHON']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="PHON" id="PHON" value="" size="40" maxlength="255"></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['FAX']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="FAX" id="FAX" value="" size="40"></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['EMAIL']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="EMAIL" id="EMAIL" value="" size="40" maxlength="255"></td>
            </tr>
            <?php $tabkey++; ?>
            <tr>
                <td class="facts_label"><?php print $factarray['WWW']; ?></td>
                <td class="facts_value"><input tabindex="<?php print $tabkey; ?>" type="text" name="WWW" id="WWW" value="" size="40" maxlength="255"></td>
            </tr>
        </table>
        <input type="submit" value="<?php print $pgv_lang['create_repository']; ?>">
    </form>
    <?php
} //-- create a repository record from the incoming variables
elseif ('addrepoaction' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    $newgedrec = "0 @XREF@ REPO\r\n";

    if (!empty($NAME)) {
        $newgedrec .= "1 NAME $NAME\r\n";
    }

    if (!empty($ADDR)) {
        $newgedrec .= "1 ADDR $ADDR\r\n";
    }

    if (!empty($PHON)) {
        $newgedrec .= "1 PHON $PHON\r\n";
    }

    if (!empty($FAX)) {
        $newgedrec .= "1 FAX $FAX\r\n";
    }

    if (!empty($EMAIL)) {
        $newgedrec .= "1 EMAIL $EMAIL\r\n";
    }

    if (!empty($WWW)) {
        $newgedrec .= "1 WWW $WWW\r\n";
    }

    $newlines = preg_preg_split("/\r?\n/", $newgedrec);

    $newged = $newlines[0] . "\r\n";

    for ($k = 1, $kMax = count($newlines); $k < $kMax; $k++) {
        if ((0 == preg_match("/\d (.....|....|...) .*/", $newlines[$k])) and (0 != mb_strlen($newlines[$k]))) {
            $newlines[$k] = '2 CONT ' . $newlines[$k];
        }

        if (mb_strlen($newlines[$k]) > 255) {
            while (mb_strlen($newlines[$k]) > 255) {
                $newged .= mb_substr($newlines[$k], 0, 255) . "\r\n";

                $newlines[$k] = mb_substr($newlines[$k], 255);

                $newlines[$k] = '2 CONC ' . $newlines[$k];
            }

            $newged .= trim($newlines[$k]) . "\r\n";
        } else {
            $newged .= trim($newlines[$k]) . "\r\n";
        }
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$newged</pre>";
    }

    $xref = append_gedrec($newged);

    if ($xref) {
        print "<br><br>\n" . $pgv_lang['new_repo_created'] . '<br><br>';

        print "<a href=\"javascript:// REPO $xref\" onclick=\"openerpasteid('$xref'); return false;\">" . $pgv_lang['paste_rid_into_field'] . " <b>$xref</b></a>\n";
    }
} //-- get the new incoming raw gedcom record and store it in the file
elseif ('updateraw' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$newgedrec</pre>";
    }

    $newgedrec = trim($newgedrec);

    if (!empty($newgedrec) && (replace_gedrec($pid, $newgedrec))) {
        print '<br><br>' . $pgv_lang['update_successful'];
    }
} //-- reconstruct the gedcom from the incoming fields and store it in the file
elseif ('update' == $action) {
    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    // add or remove Y

    if ('Y' == $text[0] or 'y' == $text[0]) {
        $text[0] = '';
    }

    if (in_array($tag[0], $emptyfacts, true) && array_unique($text) == [''] && !$islink[0]) {
        $text[0] = 'Y';
    }

    //-- check for photo update

    if (count($_FILES) > 0) {
        $uploaded_files = [];

        $upload_errors = [$pgv_lang['file_success'], $pgv_lang['file_too_big'], $pgv_lang['file_too_big'], $pgv_lang['file_partial'], $pgv_lang['file_missing']];

        foreach ($_FILES as $upload) {
            if (!empty($upload['tmp_name'])) {
                if (!move_uploaded_file($upload['tmp_name'], $MEDIA_DIRECTORY . basename($upload['name']))) {
                    $error .= '<br>' . $pgv_lang['upload_error'] . '<br>' . $upload_errors[$upload['error']];

                    $uploaded_files[] = '';
                } else {
                    $filename = $MEDIA_DIRECTORY . basename($upload['name']);

                    $uploaded_files[] = $MEDIA_DIRECTORY . basename($upload['name']);

                    $thumbnail = $MEDIA_DIRECTORY . 'thumbs/' . basename($upload['name']);

                    generate_thumbnail($filename, $thumbnail);

                    if (!empty($error)) {
                        print '<span class="error">' . $error . '</span>';
                    }
                }
            } else {
                $uploaded_files[] = '';
            }
        }
    }

    $gedlines = preg_preg_split("/\n/", trim($gedrec));

    //-- for new facts set linenum to number of lines

    if ('new' == $linenum) {
        $linenum = count($gedlines);
    }

    $newged = '';

    for ($i = 0; $i < $linenum; $i++) {
        $newged .= $gedlines[$i] . "\n";
    }

    //-- for edits get the level from the line

    if (isset($gedlines[$linenum])) {
        $fields = preg_preg_split("/\s/", $gedlines[$linenum]);

        $glevel = $fields[0];

        $i++;

        while (($i < count($gedlines)) && ($gedlines[$i][0] > $glevel)) {
            $i++;
        }
    }

    if (!isset($glevels)) {
        $glevels = [];
    }

    if (!empty($NAME)) {
        $newged .= '1 NAME ' . trim($NAME) . "\r\n";
    }

    if (!empty($NPFX)) {
        $newged .= "2 NPFX $NPFX\r\n";
    }

    if (!empty($GIVN)) {
        $newged .= "2 GIVN $GIVN\r\n";
    }

    if (!empty($NICK)) {
        $newged .= "2 NICK $NICK\r\n";
    }

    if (!empty($SPFX)) {
        $newged .= "2 SPFX $SPFX\r\n";
    }

    if (!empty($SURN)) {
        $newged .= "2 SURN $SURN\r\n";
    }

    if (!empty($NSFX)) {
        $newged .= "2 NSFX $NSFX\r\n";
    }

    if (!empty($_MARNM)) {
        $newged .= "2 _MARNM $_MARNM\r\n";
    }

    if (!empty($_HEB)) {
        $newged .= "2 _HEB $_HEB\r\n";
    }

    if (!empty($ROMN)) {
        $newged .= "2 ROMN $ROMN\r\n";
    }

    $newged = handle_updates($newged);

    while ($i < count($gedlines)) {
        $newged .= trim($gedlines[$i]) . "\r\n";

        $i++;
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$newged</pre>";
    }

    if (replace_gedrec($pid, $newged)) {
        print '<br><br>' . $pgv_lang['update_successful'];
    }
} elseif ('addchildaction' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    $gedrec = "0 @REF@ INDI\r\n1 NAME " . trim($NAME) . "\r\n";

    if (!empty($NPFX)) {
        $gedrec .= "2 NPFX $NPFX\r\n";
    }

    if (!empty($GIVN)) {
        $gedrec .= "2 GIVN $GIVN\r\n";
    }

    if (!empty($NICK)) {
        $gedrec .= "2 NICK $NICK\r\n";
    }

    if (!empty($SPFX)) {
        $gedrec .= "2 SPFX $SPFX\r\n";
    }

    if (!empty($SURN)) {
        $gedrec .= "2 SURN $SURN\r\n";
    }

    if (!empty($NSFX)) {
        $gedrec .= "2 NSFX $NSFX\r\n";
    }

    if (!empty($_MARNM)) {
        $gedrec .= "2 _MARNM $_MARNM\r\n";
    }

    if (!empty($_HEB)) {
        $gedrec .= "2 _HEB $_HEB\r\n";
    }

    if (!empty($ROMN)) {
        $gedrec .= "2 ROMN $ROMN\r\n";
    }

    $gedrec .= "1 SEX $SEX\r\n";

    if ((!empty($BIRT_DATE)) || (!empty($BIRT_PLAC))) {
        $gedrec .= "1 BIRT\r\n";

        if (!empty($BIRT_DATE)) {
            $gedrec .= "2 DATE $BIRT_DATE\r\n";
        }

        if (!empty($BIRT_PLAC)) {
            $gedrec .= "2 PLAC $BIRT_PLAC\r\n";
        }
    }

    if ((!empty($DEAT_DATE)) || (!empty($DEAT_PLAC))) {
        $gedrec .= "1 DEAT\r\n";

        if (!empty($DEAT_DATE)) {
            $gedrec .= "2 DATE $DEAT_DATE\r\n";
        }

        if (!empty($DEAT_PLAC)) {
            $gedrec .= "2 PLAC $DEAT_PLAC\r\n";
        }
    }

    if (!empty($famid)) {
        $gedrec .= "1 FAMC @$famid@\r\n";
    }

    $gedrec = handle_updates($gedrec);

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    $xref = append_gedrec($gedrec);

    if ($xref) {
        print '<br><br>' . $pgv_lang['update_successful'];

        $gedrec = '';

        if (!empty($famid)) {
            if (!isset($pgv_changes[$famid . '_' . $GEDCOM])) {
                $gedrec = find_gedcom_record($famid);
            } else {
                $gedrec = find_record_in_file($famid);
            }

            if (!empty($gedrec)) {
                $gedrec = trim($gedrec);

                $gedrec .= "\r\n1 CHIL @$xref@";

                if ($GLOBALS['DEBUG']) {
                    print "<pre>$gedrec</pre>";
                }

                replace_gedrec($famid, $gedrec);
            }
        }
    }
} elseif ('addspouseaction' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    $gedrec = "0 @REF@ INDI\r\n1 NAME " . trim($NAME) . "\r\n";

    if (!empty($NPFX)) {
        $gedrec .= "2 NPFX $NPFX\r\n";
    }

    if (!empty($GIVN)) {
        $gedrec .= "2 GIVN $GIVN\r\n";
    }

    if (!empty($NICK)) {
        $gedrec .= "2 NICK $NICK\r\n";
    }

    if (!empty($SPFX)) {
        $gedrec .= "2 SPFX $SPFX\r\n";
    }

    if (!empty($SURN)) {
        $gedrec .= "2 SURN $SURN\r\n";
    }

    if (!empty($NSFX)) {
        $gedrec .= "2 NSFX $NSFX\r\n";
    }

    if (!empty($_MARNM)) {
        $gedrec .= "2 _MARNM $_MARNM\r\n";
    }

    if (!empty($_HEB)) {
        $gedrec .= "2 _HEB $_HEB\r\n";
    }

    if (!empty($ROMN)) {
        $gedrec .= "2 ROMN $ROMN\r\n";
    }

    $gedrec .= "1 SEX $SEX\r\n";

    if ((!empty($BIRT_DATE)) || (!empty($BIRT_PLAC))) {
        $gedrec .= "1 BIRT\r\n";

        if (!empty($BIRT_DATE)) {
            $gedrec .= "2 DATE $BIRT_DATE\r\n";
        }

        if (!empty($BIRT_PLAC)) {
            $gedrec .= "2 PLAC $BIRT_PLAC\r\n";
        }
    }

    if ((!empty($DEAT_DATE)) || (!empty($DEAT_PLAC))) {
        $gedrec .= "1 DEAT\r\n";

        if (!empty($DEAT_DATE)) {
            $gedrec .= "2 DATE $DEAT_DATE\r\n";
        }

        if (!empty($DEAT_PLAC)) {
            $gedrec .= "2 PLAC $DEAT_PLAC\r\n";
        }
    }

    $gedrec = handle_updates($gedrec);

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    $xref = append_gedrec($gedrec);

    if ($xref) {
        print '<br><br>' . $pgv_lang['update_successful'];
    } else {
        exit;
    }

    if ('new' == $famid) {
        $famrec = "0 @new@ FAM\r\n";

        if ('M' == $SEX) {
            $famtag = 'HUSB';
        }

        if ('F' == $SEX) {
            $famtag = 'WIFE';
        }

        if ('HUSB' == $famtag) {
            $famrec .= "1 HUSB @$xref@\r\n";

            $famrec .= "1 WIFE @$pid@\r\n";
        } else {
            $famrec .= "1 WIFE @$xref@\r\n";

            $famrec .= "1 HUSB @$pid@\r\n";
        }

        if ($GLOBALS['DEBUG']) {
            print "<pre>$famrec</pre>";
        }

        $famid = append_gedrec($famrec);
    } elseif (!empty($famid)) {
        $famrec = '';

        $famrec = find_record_in_file($famid);

        if (!empty($famrec)) {
            $famrec = trim($famrec);

            $famrec .= "\r\n1 $famtag @$xref@\r\n";

            if ($GLOBALS['DEBUG']) {
                print "<pre>$famrec</pre>";
            }

            replace_gedrec($famid, $famrec);
        }
    }

    if ((!empty($famid)) && ('new' != $famid)) {
        $gedrec = '';

        $gedrec = find_record_in_file($xref);

        $gedrec = trim($gedrec);

        $gedrec .= "\r\n1 FAMS @$famid@\r\n";

        if ($GLOBALS['DEBUG']) {
            print "<pre>$gedrec</pre>";
        }

        replace_gedrec($xref, $gedrec);
    }

    if (!empty($pid)) {
        $indirec = '';

        if (!isset($pgv_changes[$famid . '_' . $GEDCOM])) {
            $indirec = find_gedcom_record($pid);
        } else {
            $indirec = find_record_in_file($pid);
        }

        if ($indirec) {
            $indirec = trim($indirec);

            $indirec .= "\r\n1 FAMS @$famid@\r\n";

            if ($GLOBALS['DEBUG']) {
                print "<pre>$indirec</pre>";
            }

            replace_gedrec($pid, $indirec);
        }
    }
} elseif ('addnewparentaction' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    $gedrec = "0 @REF@ INDI\r\n1 NAME " . trim($NAME) . "\r\n";

    if (!empty($NPFX)) {
        $gedrec .= "2 NPFX $NPFX\r\n";
    }

    if (!empty($GIVN)) {
        $gedrec .= "2 GIVN $GIVN\r\n";
    }

    if (!empty($NICK)) {
        $gedrec .= "2 NICK $NICK\r\n";
    }

    if (!empty($SPFX)) {
        $gedrec .= "2 SPFX $SPFX\r\n";
    }

    if (!empty($SURN)) {
        $gedrec .= "2 SURN $SURN\r\n";
    }

    if (!empty($NSFX)) {
        $gedrec .= "2 NSFX $NSFX\r\n";
    }

    if (!empty($_MARNM)) {
        $gedrec .= "2 _MARNM $_MARNM\r\n";
    }

    if (!empty($_HEB)) {
        $gedrec .= "2 _HEB $_HEB\r\n";
    }

    if (!empty($ROMN)) {
        $gedrec .= "2 ROMN $ROMN\r\n";
    }

    $gedrec .= "1 SEX $SEX\r\n";

    if ((!empty($BIRT_DATE)) || (!empty($BIRT_PLAC))) {
        $gedrec .= "1 BIRT\r\n";

        if (!empty($BIRT_DATE)) {
            $gedrec .= "2 DATE $BIRT_DATE\r\n";
        }

        if (!empty($BIRT_PLAC)) {
            $gedrec .= "2 PLAC $BIRT_PLAC\r\n";
        }
    }

    if ((!empty($DEAT_DATE)) || (!empty($DEAT_PLAC))) {
        $gedrec .= "1 DEAT\r\n";

        if (!empty($DEAT_DATE)) {
            $gedrec .= "2 DATE $DEAT_DATE\r\n";
        }

        if (!empty($DEAT_PLAC)) {
            $gedrec .= "2 PLAC $DEAT_PLAC\r\n";
        }
    }

    $gedrec = handle_updates($gedrec);

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    $xref = append_gedrec($gedrec);

    if ($xref) {
        print '<br><br>' . $pgv_lang['update_successful'];
    } else {
        exit;
    }

    if ('new' == $famid) {
        $famrec = "0 @new@ FAM\r\n";

        if ('HUSB' == $famtag) {
            $famrec .= "1 HUSB @$xref@\r\n";

            $famrec .= "1 CHIL @$pid@\r\n";
        } else {
            $famrec .= "1 WIFE @$xref@\r\n";

            $famrec .= "1 CHIL @$pid@\r\n";
        }

        if ($GLOBALS['DEBUG']) {
            print "<pre>$famrec</pre>";
        }

        $famid = append_gedrec($famrec);
    } elseif (!empty($famid)) {
        $famrec = '';

        $famrec = find_record_in_file($famid);

        if (!empty($famrec)) {
            $famrec = trim($famrec);

            $famrec .= "\r\n1 $famtag @$xref@\r\n";

            if ($GLOBALS['DEBUG']) {
                print "<pre>$famrec</pre>";
            }

            replace_gedrec($famid, $famrec);
        }
    }

    if ((!empty($famid)) && ('new' != $famid)) {
        $gedrec = '';

        $gedrec = find_record_in_file($xref);

        $gedrec = trim($gedrec);

        $gedrec .= "\r\n1 FAMS @$famid@\r\n";

        if ($GLOBALS['DEBUG']) {
            print "<pre>$gedrec</pre>";
        }

        replace_gedrec($xref, $gedrec);
    }

    if (!empty($pid)) {
        $indirec = '';

        if (!isset($pgv_changes[$famid . '_' . $GEDCOM])) {
            $indirec = find_gedcom_record($pid);
        } else {
            $indirec = find_record_in_file($pid);
        }

        $indirec = trim($indirec);

        if ($indirec) {
            $ct = preg_match("/1 FAMC @$famid@/", $indirec);

            if (0 == $ct) {
                $indirec = trim($indirec);

                $indirec .= "\r\n1 FAMC @$famid@\r\n";

                if ($GLOBALS['DEBUG']) {
                    print "<pre>$indirec</pre>";
                }

                replace_gedrec($pid, $indirec);
            }
        }
    }
} elseif ('deleteperson' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    if (!$factedit) {
        print '<br>' . $pgv_lang['privacy_prevented_editing'];

        if (!empty($pid)) {
            print '<br>' . $pgv_lang['privacy_not_granted'] . " pid $pid.";
        }

        if (!empty($famid)) {
            print '<br>' . $pgv_lang['privacy_not_granted'] . " famid $famid.";
        }
    } else {
        if (!empty($gedrec)) {
            $success = true;

            $ct = preg_match_all('/1 FAM. @(.*)@/', $gedrec, $match, PREG_SET_ORDER);

            for ($i = 0; $i < $ct; $i++) {
                $famid = $match[$i][1];

                if (!isset($pgv_changes[$famid . '_' . $GEDCOM])) {
                    $famrec = find_gedcom_record($famid);
                } else {
                    $famrec = find_record_in_file($famid);
                }

                $lines = preg_preg_split("/\n/", $famrec);

                $newfamrec = '';

                foreach ($lines as $indexval => $line) {
                    if (0 == preg_match("/@$pid@/", $line)) {
                        $newfamrec .= $line . "\n";
                    }
                }

                //-- if there is not at least two people in a family then the family is deleted

                $pt = preg_match_all('/1 .{4} @(.*)@/', $newfamrec, $pmatch, PREG_SET_ORDER);

                if ($pt < 2) {
                    for ($j = 0; $j < $pt; $j++) {
                        $xref = $pmatch[$j][1];

                        if ($xref != $pid) {
                            if (!isset($pgv_changes[$xref . '_' . $GEDCOM])) {
                                $indirec = find_gedcom_record($xref);
                            } else {
                                $indirec = find_record_in_file($xref);
                            }

                            $indirec = preg_replace("/1.*@$famid@.*/", '', $indirec);

                            if ($GLOBALS['DEBUG']) {
                                print "<pre>$indirec</pre>";
                            }

                            replace_gedrec($xref, $indirec);
                        }
                    }

                    $success = $success && delete_gedrec($famid);
                } else {
                    $success = $success && replace_gedrec($famid, $newfamrec);
                }
            }

            if ($success) {
                $success = $success && delete_gedrec($pid);
            }

            if ($success) {
                print '<br><br>' . $pgv_lang['gedrec_deleted'];
            }
        }
    }
} elseif ('deletesource' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    if (!empty($gedrec)) {
        $success = true;

        $query = "SOUR @$pid@";

        // -- array of names

        $myindilist = [];

        $myfamlist = [];

        $myindilist = search_indis($query);

        foreach ($myindilist as $key => $value) {
            if (!isset($pgv_changes[$key . '_' . $GEDCOM])) {
                $indirec = $value['gedcom'];
            } else {
                $indirec = find_record_in_file($key);
            }

            $lines = preg_preg_split("/\n/", $indirec);

            $newrec = '';

            $skipline = false;

            $glevel = 0;

            foreach ($lines as $indexval => $line) {
                if ((0 == preg_match("/@$pid@/", $line)) && (!$skipline)) {
                    $newrec .= $line . "\n";
                } else {
                    if (!$skipline) {
                        $glevel = $line[0];

                        $skipline = true;
                    } else {
                        if ($line[0] <= $glevel) {
                            $skipline = false;

                            $newrec .= $line . "\n";
                        }
                    }
                }
            }

            if ($GLOBALS['DEBUG']) {
                print "<pre>$newrec</pre>";
            }

            $success = $success && replace_gedrec($key, $newrec);
        }

        $myfamlist = search_fams($query);

        foreach ($myfamlist as $key => $value) {
            if (!isset($pgv_changes[$key . '_' . $GEDCOM])) {
                $indirec = $value['gedcom'];
            } else {
                $indirec = find_record_in_file($key);
            }

            $lines = preg_preg_split("/\n/", $indirec);

            $newrec = '';

            $skipline = false;

            $glevel = 0;

            foreach ($lines as $indexval => $line) {
                if ((0 == preg_match("/@$pid@/", $line)) && (!$skipline)) {
                    $newrec .= $line . "\n";
                } else {
                    if (!$skipline) {
                        $glevel = $line[0];

                        $skipline = true;
                    } else {
                        if ($line[0] <= $glevel) {
                            $skipline = false;

                            $newrec .= $line . "\n";
                        }
                    }
                }
            }

            if ($GLOBALS['DEBUG']) {
                print "<pre>$newrec</pre>";
            }

            $success = $success && replace_gedrec($key, $newrec);
        }

        if ($success) {
            $success = $success && delete_gedrec($pid);
        }

        if ($success) {
            print '<br><br>' . $pgv_lang['gedrec_deleted'];
        }
    }
} elseif ('deleterepo' == $action) {
    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    if (!empty($gedrec)) {
        $success = true;

        $query = "REPO @$pid@";

        // -- array of names

        $mysourlist = [];

        $mysourlist = search_sources($query);

        foreach ($mysourlist as $key => $value) {
            if (!isset($pgv_changes[$key . '_' . $GEDCOM])) {
                $sourrec = $value['gedcom'];
            } else {
                $sourrec = find_record_in_file($key);
            }

            $lines = preg_preg_split("/\n/", $sourrec);

            $newrec = '';

            $skipline = false;

            $glevel = 0;

            foreach ($lines as $indexval => $line) {
                if ((0 == preg_match("/@$pid@/", $line)) && (!$skipline)) {
                    $newrec .= $line . "\n";
                } else {
                    if (!$skipline) {
                        $glevel = $line[0];

                        $skipline = true;
                    } else {
                        if ($line[0] <= $glevel) {
                            $skipline = false;

                            $newrec .= $line . "\n";
                        }
                    }
                }
            }

            if ($GLOBALS['DEBUG']) {
                print "<pre>$newrec</pre>";
            }

            $success = $success && replace_gedrec($key, $newrec);
        }

        if ($success) {
            $success = $success && delete_gedrec($pid);
        }

        if ($success) {
            print '<br><br>' . $pgv_lang['gedrec_deleted'];
        }
    }
} elseif ('editname' == $action) {
    $gedlines = preg_preg_split("/\n/", trim($gedrec));

    $fields = preg_preg_split("/\s/", $gedlines[$linenum]);

    $glevel = $fields[0];

    $i = $linenum + 1;

    $namerec = $gedlines[$linenum];

    while (($i < count($gedlines)) && ($gedlines[$i][0] > $glevel)) {
        $namerec .= "\n" . $gedlines[$i];

        $i++;
    }

    print_indi_form('update', '', $linenum, $namerec);
} elseif ('addname' == $action) {
    print_indi_form('update', '', 'new', 'NEW');
} elseif ('copy' == $action) {
    $gedlines = preg_preg_split("/\n/", trim($gedrec));

    $fields = preg_preg_split("/\s/", $gedlines[$linenum]);

    $glevel = $fields[0];

    $i = $linenum + 1;

    $factrec = $gedlines[$linenum];

    while (($i < count($gedlines)) && ($gedlines[$i][0] > $glevel)) {
        $factrec .= "\n" . $gedlines[$i];

        $i++;
    }

    if (!isset($_SESSION['clipboard'])) {
        $_SESSION['clipboard'] = [];
    }

    $ft = preg_match('/1 (_?[A-Z]{3,5})(.*)/', $factrec, $match);

    if ($ft > 0) {
        $fact = trim($match[1]);

        if ('EVEN' == $fact || 'FACT' == $fact) {
            $ct = preg_match('/2 TYPE (.*)/', $factrec, $match);

            if ($ct > 0) {
                $fact = trim($match[1]);
            }
        }

        if (count($_SESSION['clipboard']) > 4) {
            array_pop($_SESSION['clipboard']);
        }

        $_SESSION['clipboard'][] = ['type' => $type, 'factrec' => $factrec, 'fact' => $fact];

        print '<b>' . $pgv_lang['record_copied'] . "</b>\n";
    }
} elseif ('paste' == $action) {
    $gedrec .= "\r\n" . $_SESSION['clipboard'][$fact]['factrec'];

    if ($GLOBALS['DEBUG']) {
        phpinfo(32);
    }

    if ($GLOBALS['DEBUG']) {
        print "<pre>$gedrec</pre>";
    }

    $success = replace_gedrec($pid, $gedrec);

    if ($success) {
        print '<br><br>' . $pgv_lang['update_successful'];
    }
} elseif ('reorder_children' == $action) {
    ?>
    <br><b><?php print $pgv_lang['reorder_children']; ?></b>
    <?php print_help_link('reorder_children_help', 'qm'); ?>
    <form name="reorder_form" method="post" action="edit_interface.php">
        <input type="hidden" name="action" value="reorder_update">
        <input type="hidden" name="pid" value="<?php print $pid; ?>">
        <input type="hidden" name="option" value="bybirth">
        <table>
            <?php
            $children = [];

    $ct = preg_match_all('/1 CHIL @(.+)@/', $gedrec, $match, PREG_SET_ORDER);

    for ($i = 0; $i < $ct; $i++) {
        $child = trim($match[$i][1]);

        $irec = find_person_record($child);

        if (false === $irec) {
            $irec = find_record_in_file($child);
        }

        if (isset($indilist[$child])) {
            $children[$child] = $indilist[$child];
        }
    }

    if ((!empty($option)) && ('bybirth' == $option)) {
        uasort($children, 'compare_date');
    }

    $i = 0;

    foreach ($children as $pid => $child) {
        print "<tr>\n<td>\n";

        print "<select name=\"order[$pid]\">\n";

        for ($j = 0; $j < $ct; $j++) {
            print '<option value="' . ($j) . '"';

            if ($j == $i) {
                print ' selected="selected"';
            }

            print '>' . ($j + 1) . "</option>\n";
        }

        print "</select>\n";

        print '</td><td class="facts_value">';

        print PrintReady(get_person_name($pid));

        print '<br>';

        print_first_major_fact($pid);

        print "</td>\n</tr>\n";

        $i++;
    } ?>
        </table>
        <input type="submit" value="<?php print $pgv_lang['save']; ?>">
        <input type="button" value="<?php print $pgv_lang['sort_by_birth']; ?>" onclick="document.reorder_form.action.value='reorder_children'; document.reorder_form.submit();">
    </form>
    <?php
} elseif ('reorder_update' == $action) {
        if ($GLOBALS['DEBUG']) {
            phpinfo(32);
        }

        asort($order);

        reset($order);

        $lines = preg_preg_split("/\n/", $gedrec);

        $newgedrec = '';

        for ($i = 0, $iMax = count($lines); $i < $iMax; $i++) {
            if (0 == preg_match('/1 CHIL/', $lines[$i])) {
                $newgedrec .= $lines[$i] . "\n";
            }
        }

        foreach ($order as $child => $num) {
            $newgedrec .= '1 CHIL @' . $child . "@\r\n";
        }

        if ($GLOBALS['DEBUG']) {
            print "<pre>$newgedrec</pre>";
        }

        if (replace_gedrec($pid, $newgedrec)) {
            print '<br><br>' . $pgv_lang['update_successful'];
        }
    } elseif ('reorder_fams' == $action) {
        ?>
    <br><b><?php print $pgv_lang['reorder_families']; ?></b>
    <?php print_help_link('reorder_families_help', 'qm'); ?>
    <form name="reorder_form" method="post" action="edit_interface.php">
        <input type="hidden" name="action" value="reorder_fams_update">
        <input type="hidden" name="pid" value="<?php print $pid; ?>">
        <input type="hidden" name="option" value="bymarriage">
        <table>
            <?php
            $fams = [];

        $ct = preg_match_all('/1 FAMS @(.+)@/', $gedrec, $match, PREG_SET_ORDER);

        for ($i = 0; $i < $ct; $i++) {
            $famid = trim($match[$i][1]);

            $frec = find_family_record($famid);

            if (false === $frec) {
                $frec = find_record_in_file($famid);
            }

            if (isset($famlist[$famid])) {
                $fams[$famid] = $famlist[$famid];
            }
        }

        if ((!empty($option)) && ('bymarriage' == $option)) {
            $sortby = 'MARR';

            uasort($fams, 'compare_date');
        }

        $i = 0;

        foreach ($fams as $famid => $fam) {
            print "<tr>\n<td>\n";

            print "<select name=\"order[$famid]\">\n";

            for ($j = 0; $j < $ct; $j++) {
                print '<option value="' . ($j) . '"';

                if ($j == $i) {
                    print ' selected="selected"';
                }

                print '>' . ($j + 1) . "</option>\n";
            }

            print "</select>\n";

            print '</td><td class="facts_value">';

            print PrintReady(get_family_descriptor($famid));

            print '<br>';

            print_simple_fact($fam['gedcom'], 'MARR', $famid);

            print "</td>\n</tr>\n";

            $i++;
        } ?>
        </table>
        <input type="submit" value="<?php print $pgv_lang['save']; ?>">
        <input type="button" value="<?php print $pgv_lang['sort_by_marriage']; ?>" onclick="document.reorder_form.action.value='reorder_fams'; document.reorder_form.submit();">
    </form>
    <?php
    } elseif ('reorder_fams_update' == $action) {
        if ($GLOBALS['DEBUG']) {
            phpinfo(32);
        }

        asort($order);

        reset($order);

        $lines = preg_preg_split("/\n/", $gedrec);

        $newgedrec = '';

        for ($i = 0, $iMax = count($lines); $i < $iMax; $i++) {
            if (0 == preg_match('/1 FAMS/', $lines[$i])) {
                $newgedrec .= $lines[$i] . "\n";
            }
        }

        foreach ($order as $famid => $num) {
            $newgedrec .= '1 FAMS @' . $famid . "@\r\n";
        }

        if ($GLOBALS['DEBUG']) {
            print "<pre>$newgedrec</pre>";
        }

        if (replace_gedrec($pid, $newgedrec)) {
            print '<br><br>' . $pgv_lang['update_successful'];
        }
    }

print '<center><br><br><br>';
print '<a href="javascript:// ' . $pgv_lang['close_window'] . '" onclick="if (window.opener.showchanges) window.opener.showchanges(); window.close();">' . $pgv_lang['close_window'] . "</a><br></center>\n";
print_simple_footer();

?>
