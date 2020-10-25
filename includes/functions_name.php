<?php

/**
 * Name Specific Functions
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
 * @version $Id: functions_name.php,v 1.2 2006/01/09 00:46:23 skenow Exp $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (mb_strstr($_SERVER['PHP_SELF'], 'functions_name.php')) {
    print 'Why do you want to do that?';

    exit;
}

/**
 * Get array of common surnames from index
 *
 * This function returns a simple array of the most common surnames
 * found in the individuals list.
 * @param mixed $ged
 * @return array
 * @return array
 */
function get_common_surnames_index($ged)
{
    global $GEDCOMS;

    if (empty($GEDCOMS[$ged]['commonsurnames'])) {
        store_gedcoms();
    }

    $surnames = [];

    if (empty($GEDCOMS[$ged]['commonsurnames']) || (',' == $GEDCOMS[$ged]['commonsurnames'])) {
        return $surnames;
    }

    $names = preg_preg_split('/[,;]/', $GEDCOMS[$ged]['commonsurnames']);

    foreach ($names as $indexval => $name) {
        $name = trim($name);

        if (!empty($name)) {
            $surnames[$name]['name'] = stripslashes($name);
        }
    }

    return $surnames;
}

/**
 * Get array of common surnames
 *
 * This function returns a simple array of the most common surnames
 * found in the individuals list.
 * @param int $min the number of times a surname must occur before it is added to the array
 * @return array
 * @return array
 */
function get_common_surnames($min)
{
    global $TBLPREFIX, $GEDCOM, $indilist, $CONFIGURED, $GEDCOMS, $COMMON_NAMES_ADD, $COMMON_NAMES_REMOVE, $pgv_lang, $HNN, $ANN;

    $surnames = [];

    if (!$CONFIGURED || !adminUserExists() || (0 == count($GEDCOMS)) || (!check_for_import($GEDCOM))) {
        return $surnames;
    }

    //-- this line causes a bug where the common surnames list is not properly updated

    // if ((!isset($indilist))||(!is_array($indilist))) return $surnames;

    $surnames = get_top_surnames(100);

    arsort($surnames);

    $topsurns = [];

    $i = 0;

    foreach ($surnames as $indexval => $surname) {
        $surname['name'] = trim($surname['name']);

        if (!empty($surname['name'])
            && false === mb_stristr($surname['name'], '@N.N') && false === mb_stristr($surname['name'], $HNN) && false === mb_stristr($surname['name'], $ANN . ',')
            && false === mb_stristr($COMMON_NAMES_REMOVE, $surname['name'])) {
            if ($surname['match'] >= $min) {
                $topsurns[$surname['name']] = $surname;
            }

            $i++;
        }
    }

    $addnames = preg_preg_split('/[,;] /', $COMMON_NAMES_ADD);

    if ((0 == count($addnames)) && (!empty($COMMON_NAMES_ADD))) {
        $addnames[] = $COMMON_NAMES_ADD;
    }

    foreach ($addnames as $indexval => $name) {
        if (!empty($name) && !isset($topsurns[str2upper($name)])) {
            $topsurns[$name]['name'] = $name;

            $topsurns[$name]['match'] = $min;
        }
    }

    $delnames = preg_preg_split('/[,;] /', $COMMON_NAMES_REMOVE);

    if ((0 == count($delnames)) && (!empty($COMMON_NAMES_REMOVE))) {
        $delnames[] = $COMMON_NAMES_REMOVE;
    }

    foreach ($delnames as $indexval => $name) {
        if (!empty($name)) {
            unset($topsurns[$name]);
        }
    }

    uasort($topsurns, 'itemsort');

    return $topsurns;
}

/**
 * Get the name from the raw gedcom record
 *
 * @param string $indirec the raw gedcom record to get the name from
 * @return string|string[]
 * @return string|string[]
 */
function get_name_in_record($indirec)
{
    $name = '';

    $nt = preg_match('/1 NAME (.*)/', $indirec, $ntmatch);

    if ($nt > 0) {
        $name = trim($ntmatch[1]);

        $name = preg_replace(['/__+/', "/\.\.+/", "/^\?+/"], ['', '', ''], $name);

        //-- check for a surname

        $ct = preg_match('~/(.*)/~', $name, $match);

        if ($ct > 0) {
            $surname = trim($match[1]);

            $surname = preg_replace(['/__+/', "/\.\.+/", "/^\?+/"], ['', '', ''], $surname);

            if (empty($surname)) {
                $name = preg_replace('~/(.*)/~', '/@N.N./', $name);
            }
        } else {
            //-- check for the surname SURN tag

            $ct = preg_match('/2 SURN (.*)/', $indirec, $match);

            if ($ct > 0) {
                $pt = preg_match('/2 SPFX (.*)/', $indirec, $pmatch);

                if ($pt > 0) {
                    $name .= ' ' . trim($pmatch[1]);
                }

                $surname = trim($match[1]);

                $surname = preg_replace(['/__+/', "/\.\.+/", "/^\?+/"], ['', '', ''], $surname);

                if (empty($surname)) {
                    $name .= ' /@N.N./';
                } else {
                    $name .= ' /' . $surname . '/';
                }
            } else {
                $name .= ' /@N.N./';
            }
        }

        $givens = preg_replace('~/.*/~', '', $name);

        if (empty($givens)) {
            $name = '@P.N. ' . $name;
        }
    } else {
        /*-- this is all extraneous to the 1 NAME tag and according to the gedcom spec
        -- the 1 NAME tag should take preference
        */

        $name = '';

        //-- check for the given names

        $gt = preg_match('/2 GIVN (.*)/', $indirec, $gmatch);

        if ($gt > 0) {
            $name .= trim($gmatch[1]);
        } else {
            $name .= '@P.N.';
        }

        //-- check for the surname

        $ct = preg_match('/2 SURN (.*)/', $indirec, $match);

        if ($ct > 0) {
            $pt = preg_match('/2 SPFX (.*)/', $indirec, $pmatch);

            if ($pt > 0) {
                $name .= ' ' . trim($pmatch[1]);
            }

            $surname = trim($match[1]);

            if (empty($surname)) {
                $name .= ' /@N.N./';
            } else {
                $name .= ' /' . $surname . '/';
            }
        }

        if (empty($name)) {
            $name = '@P.N. /@N.N./';
        }

        $st = preg_match('/2 NSFX (.*)/', $indirec, $smatch);

        if ($st > 0) {
            $name .= ' ' . trim($smatch[1]);
        }

        $pt = preg_match('/2 SPFX (.*)/', $indirec, $pmatch);

        if ($pt > 0) {
            $name = mb_strtolower(trim($pmatch[1])) . ' ' . $name;
        }
    }

    // handle PAF extra NPFX [ 961860 ]

    $ct = preg_match('/2 NPFX (.*)/', $indirec, $match);

    if ($ct > 0) {
        $npfx = trim($match[1]);

        if (false === mb_strpos($name, $npfx)) {
            $name = $npfx . ' ' . $name;
        }
    }

    return $name;
}

/**
 * get the person's name as surname, given names
 *
 * This function will return the given person's name is a format that is good for sorting
 * Surname, given names
 * @param string $pid     the gedcom xref id for the person
 * @param string $alpha   only get the name that starts with a certain letter
 * @param string $surname only get the name that has this surname
 * @return string the sortable name
 */
function get_sortable_name($pid, $alpha = '', $surname = '')
{
    global $TBLPREFIX, $SHOW_LIVING_NAMES, $PRIV_PUBLIC;

    global $GEDCOM, $indilist, $pgv_lang;

    if (empty($pid)) {
        return '@N.N., @P.N.';
    }

    //-- first check if the person is in the cache

    if ((isset($indilist[$pid]['names'])) && ($indilist[$pid]['file'] == $GEDCOM)) {
        $names = $indilist[$pid]['names'];
    } else {
        //-- cache missed, so load the person into the cache with the find_person_record function

        //-- and get the name from the cache again

        $gedrec = find_person_record($pid);

        if (empty($gedrec)) {
            $gedrec = find_record_in_file($pid);
        }

        if (!empty($gedrec)) {
            $names = $indilist[$pid]['names'];
        } else {
            return '@N.N., @P.N.';
        }
    }

    foreach ($names as $indexval => $name) {
        if ('' != $surname && $name[2] == $surname) {
            return sortable_name_from_name($name[0]);
        } elseif ('' != $alpha && $name[1] == $alpha) {
            return sortable_name_from_name($name[0]);
        }
    }

    return sortable_name_from_name($names[0][0]);
}

/**
 * get the sortable name from the gedcom name
 * @param string $name the name from the 1 NAME gedcom line including the /
 * @return string    The new name in the form Surname, Given Names
 */
function sortable_name_from_name($name)
{
    //-- remove any unwanted characters from the name

    if (preg_match("/^\.(\.*)$|^\?(\?*)$|^_(_*)$|^,(,*)$/", $name)) {
        $name = preg_replace(['/,/', "/\./", '/_/', "/\?/"], ['', '', '', ''], $name);
    }

    $ct = preg_match('~(.*)/(.*)/(.*)~', $name, $match);

    if ($ct > 0) {
        $surname = trim($match[2]);

        if (empty($surname)) {
            $surname = '@N.N.';
        }

        $givenname = trim($match[1]);

        $othername = trim($match[3]);

        if (empty($givenname) && !empty($othername)) {
            $givenname = $othername;

            $othername = '';
        }

        if (empty($givenname)) {
            $givenname = '@P.N.';
        }

        $name = $surname;

        if (!empty($othername)) {
            $name .= ' ' . $othername;
        }

        $name .= ', ' . $givenname;
    }

    if (!empty($name)) {
        return $name;
    }

    return '@N.N., @P.N.';
}

/**
 * get the name for a person
 *
 * returns the name in the form Given Name Surname
 * If the <var>$NAME_FROM_GEDCOM</var> variable is true then the name is retrieved from the
 * gedcom record not from the database index.
 * @param string $pid the xref gedcom id of the person
 * @return string the person's name (Given Name Surname)
 */
function get_person_name($pid)
{
    global $NAME_REVERSE;

    global $NAME_FROM_GEDCOM;

    global $indilist;

    global $GEDCOM;

    $name = '';

    //-- get the name from the gedcom record

    if ($NAME_FROM_GEDCOM) {
        $indirec = find_person_record($pid);

        if (!$indirec) {
            $indirec = find_record_in_file($pid);
        }

        $name = get_name_in_record($indirec);
    } else {
        //-- first check if the person is in the cache

        if ((isset($indilist[$pid]['name'][0][0])) && ($indilist[$pid]['file'] == $GEDCOM)) {
            $name = $indilist[$pid]['name'][0][0];
        } else {
            //-- cache missed, so load the person into the cache with the find_person_record function

            //-- and get the name from the cache again

            $gedrec = find_person_record($pid);

            if (empty($gedrec)) {
                $gedrec = find_record_in_file($pid);
            }

            if (!empty($gedrec)) {
                if (isset($indilist[$pid]['names'])) {
                    $name = $indilist[$pid]['names'][0][0];
                } else {
                    $names = get_indi_names($gedrec);

                    $name = $names[0][0];
                }
            }
        }
    }

    $name = check_NN($name);

    return $name;
}

/**
 * get the descriptive title of the source
 *
 * @param string $sid the gedcom xref id for the source to find
 * @return string the title of the source
 */
function get_source_descriptor($sid)
{
    global $TBLPREFIX, $WORD_WRAPPED_NOTES;

    global $GEDCOM, $sourcelist;

    $title = '';

    if ('' == $sid) {
        return false;
    }

    if (isset($sourcelist[$sid]['name'])) {
        return $sourcelist[$sid]['name'];
    }

    $gedrec = find_source_record($sid);

    if (!empty($gedrec)) {
        return $sourcelist[$sid]['name'];
    }

    return false;
}

/**
 * get the descriptive title of the repository
 *
 * @param string $rid the gedcom xref id for the repository to find
 * @return string the title of the repository
 */
function get_repo_descriptor($rid)
{
    global $TBLPREFIX, $WORD_WRAPPED_NOTES;

    global $GEDCOM, $repo_id_list;

    if ('' == $rid) {
        return false;
    }

    if (isset($repo_id_list[$rid]['name'])) {
        return $repo_id_list[$rid]['name'];
    }

    $repo_id_list = get_repo_id_list();

    if ((!empty($repo_id_list)) && (isset($repo_id_list[$rid]))) {
        return $repo_id_list[$rid]['name'];
    }

    return false;
}

//==== MA
/**
 * get the additional descriptive title of the source
 *
 * @param string $sid the gedcom xref id for the source to find
 * @return string the additional title of the source
 */
function get_add_source_descriptor($sid)
{
    global $TBLPREFIX, $WORD_WRAPPED_NOTES;

    global $GEDCOM, $sourcelist;

    $title = '';

    if ('' == $sid) {
        return false;
    }

    $gedrec = find_source_record($sid);

    if (!empty($gedrec)) {
        $ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);

        if ($ct > 0) {
            return ($match[1]);
        }

        $ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);

        if ($ct > 0) {
            return ($match[1]);
        }
    }

    return false;
}

function get_family_descriptor($fid)
{
    global $pgv_lang;

    $parents = find_parents($fid);

    if ($parents['HUSB']) {
        if (displayDetailsByID($parents['HUSB']) || showLivingNameByID($parents['HUSB'])) {
            $hname = get_sortable_name($parents['HUSB']);
        } else {
            $hname = $pgv_lang['private'];
        }
    } else {
        $hname = '@N.N., @P.N.';
    }

    if ($parents['WIFE']) {
        if (displayDetailsByID($parents['WIFE']) || showLivingNameByID($parents['WIFE'])) {
            $wname = get_sortable_name($parents['WIFE']);
        } else {
            $wname = $pgv_lang['private'];
        }
    } else {
        $wname = '@N.N., @P.N.';
    }

    if (!empty($hname) && !empty($wname)) {
        return check_NN($hname) . ' + ' . check_NN($wname);
    } elseif (!empty($hname) && empty($wname)) {
        return check_NN($hname);
    } elseif (empty($hname) && !empty($wname)) {
        return check_NN($wname);
    }
}

function get_family_add_descriptor($fid)
{
    global $pgv_lang;

    $parents = find_parents($fid);

    if ($parents['HUSB']) {
        if (displayDetailsByID($parents['HUSB']) || showLivingNameByID($parents['HUSB'])) {
            $hname = get_sortable_add_name($parents['HUSB']);
        } else {
            $hname = $pgv_lang['private'];
        }
    } else {
        $hname = '@N.N., @P.N.';
    }

    if ($parents['WIFE']) {
        if (displayDetailsByID($parents['WIFE']) || showLivingNameByID($parents['WIFE'])) {
            $wname = get_sortable_add_name($parents['WIFE']);
        } else {
            $wname = $pgv_lang['private'];
        }
    } else {
        $wname = '@N.N., @P.N.';
    }

    if (!empty($hname) && !empty($wname)) {
        return check_NN($hname) . ' + ' . check_NN($wname);
    } elseif (!empty($hname) && empty($wname)) {
        return check_NN($hname);
    } elseif (empty($hname) && !empty($wname)) {
        return check_NN($wname);
    }
}

// -- find and return a given individual's second name in format: firstname lastname
function get_add_person_name($pid)
{
    global $NAME_REVERSE;

    global $NAME_FROM_GEDCOM;

    //-- get the name from the indexes

    $record = find_person_record($pid);

    $name_record = get_sub_record(1, '1 NAME', $record);

    return get_add_person_name_in_record($name_record);
}

function get_add_person_name_in_record($name_record, $keep_slash = false)
{
    global $NAME_REVERSE;

    global $NAME_FROM_GEDCOM;

    // Check for ROMN name

    $romn = preg_match('/(2 ROMN (.*)|2 _HEB (.*))/', $name_record, $romn_match);

    if ($romn > 0) {
        if ($keep_slash) {
            return trim($romn_match[count($romn_match) - 1]);
        }

        $names = preg_preg_split("/\//", $romn_match[count($romn_match) - 1]);

        if (count($names) > 1) {
            if ($NAME_REVERSE) {
                $name = trim($names[1]) . ' ' . trim($names[0]);
            } else {
                $name = trim($names[0]) . ' ' . trim($names[1]);
            }
        } else {
            $name = trim($names[0]);
        }
    } else {
        $name = '';
    }

    return $name;
}

// -- find and return a given individual's second name in sort format: familyname, firstname
function get_sortable_add_name($pid)
{
    global $NAME_REVERSE;

    global $NAME_FROM_GEDCOM;

    //-- get the name from the indexes

    $record = find_person_record($pid);

    $name_record = get_sub_record(1, '1 NAME', $record);

    // Check for ROMN name

    $romn = preg_match('/(2 ROMN (.*)|2 _HEB (.*))/', $name_record, $romn_match);

    if ($romn > 0) {
        $names = preg_preg_split("/\//", $romn_match[count($romn_match) - 1]);

        if ('' == $names[0]) {
            $names[0] = '@P.N.';
        }    //-- MA

        if (empty($names[1])) {
            $names[1] = '@N.N.';
        }    //-- MA

        if (count($names) > 1) {
            $fullname = trim($names[1]) . ',';

            $fullname .= ',# ' . trim($names[0]);

            if (count($names) > 2) {
                $fullname .= ',% ' . trim($names[2]);
            }
        } else {
            $fullname = $romn_match[1];
        }

        if (!$NAME_REVERSE) {
            $name = trim($names[1]) . ', ' . trim($names[0]);
        } else {
            $name = trim($names[0]) . ' ,' . trim($names[1]);
        }
    } else {
        $name = get_sortable_name($pid);
    }

    return $name;
}

/**
 * strip name prefixes
 *
 * this function strips the prefixes of lastnames
 * get rid of jr. Jr. Sr. sr. II, III and van, van der, de lowercase surname prefixes
 * a . and space must be behind a-z to ensure shortened prefixes and multiple prefixes are removed
 * @param string $lastname The name to strip
 * @return string    The updated name
 */
function strip_prefix($lastname)
{
    $name = preg_replace(["/ [jJsS][rR]\.?,/", '/ I+,/', '/^[a-z. ]*/'], [',', ',', ''], $lastname);

    $name = trim($name);

    return $name;
}

/**
 * Extract the surname from a name
 *
 * This function will extract the surname from an individual name in the form
 * Surname, Given Name
 * All surnames are stored in the global $surnames array
 * It will only get the surnames that start with the letter $alpha
 * For names like van den Burg, it will only return the "Burg"
 * It will work if the surname is all lowercase
 * @param string $indiname the name to extract the surname from
 * @param mixed  $count
 * @return string|string[]|null
 * @return string|string[]|null
 */
function extract_surname($indiname, $count = true)
{
    global $surnames, $alpha, $surname, $show_all, $i, $testname;

    if (!isset($testname)) {
        $testname = '';
    }

    $nsurname = '';

    //-- get surname from a standard name

    if (preg_match('~/([^/]*)/~', $indiname, $match) > 0) {
        $nsurname = trim($match[1]);
    } //-- get surname from a sortable name

    else {
        $names = preg_preg_split('/,/', $indiname);

        if (1 == count($names)) {
            $nsurname = '@N.N.';
        } else {
            $nsurname = trim($names[0]);
        }

        $nsurname = preg_replace(["/ [jJsS][rR]\.?/", '/ I+/'], ['', ''], $nsurname);
    }

    if ($count) {
        surname_count($nsurname);
    }

    return $nsurname;
}

/**
 * add a surname to the surnames array for counting
 * @param string $nsurname
 * @return string
 */
function surname_count($nsurname)
{
    global $surnames, $alpha, $surname, $show_all, $i, $testname;

    // Match names with chosen first letter

    $lname = strip_prefix($nsurname);

    if (empty($lname)) {
        $lname = $nsurname;
    }

    $sort_letter = get_first_letter($lname);

    if (('yes' == $show_all) || empty($alpha) || ($alpha == $sort_letter)) {
        $tsurname = preg_replace(["/ [jJsS][rR]\.?/", '/ I+/'], ['', ''], $nsurname);

        $tsurname = str2upper($tsurname);

        if (empty($surname) || (str2upper($surname) == $tsurname)) {
            if (!isset($surnames[$tsurname])) {
                $surnames[$tsurname] = [];

                $surnames[$tsurname]['name'] = $nsurname;

                $surnames[$tsurname]['match'] = 1;

                $surnames[$tsurname]['fam'] = 1;

                $surnames[$tsurname]['alpha'] = get_first_letter($tsurname);
            } else {
                $surnames[$tsurname]['match']++;

                if (0 == $i || $testname != $tsurname) {
                    $surnames[$tsurname]['fam']++;
                }
            }

            if (0 == $i) {
                $testname = $tsurname;
            }
        }

        return $nsurname;
    }

    return false;
}

/**
 * get first letter
 *
 * get the first letter of a UTF-8 string
 * @param string $text the text to get the first letter from
 * @param mixed $import
 * @return string    the first letter UTF-8 encoded
 */
function get_first_letter($text, $import = false)
{
    global $LANGUAGE, $CHARACTER_SET;

    $text = trim(str2upper($text));

    if (true === $import) {
        $hungarianex = ['CS', 'DZ', 'GY', 'LY', 'NY', 'SZ', 'TY', 'ZS', 'DZS'];

        $danishex = ['OE', 'AE', 'AA'];

        if ('DZS' == mb_substr($text, 0, 3)) {
            $letter = mb_substr($text, 0, 3);
        } elseif (in_array(mb_substr($text, 0, 2), $hungarianex, true) || in_array(mb_substr($text, 0, 2), $danishex, true)) {
            $letter = mb_substr($text, 0, 2);
        } else {
            $letter = mb_substr($text, 0, 1);
        }
    } elseif ('hungarian' == $LANGUAGE) {
        $hungarianex = ['CS', 'DZ', 'GY', 'LY', 'NY', 'SZ', 'TY', 'ZS', 'DZS'];

        if ('DZS' == mb_substr($text, 0, 3)) {
            $letter = mb_substr($text, 0, 3);
        } elseif (in_array(mb_substr($text, 0, 2), $hungarianex, true)) {
            $letter = mb_substr($text, 0, 2);
        } else {
            $letter = mb_substr($text, 0, 1);
        }
    } elseif ('danish' == $LANGUAGE || 'norwegian' == $LANGUAGE) {
        $danishex = ['OE', 'AE', 'AA'];

        $letter = str2upper(mb_substr($text, 0, 2));

        if (in_array($letter, $danishex, true)) {
            /* Who did this change and why from the well working codes below? - eikland
            *			if ($letter == "AA") $text = "\x00\xc5";
            *			else if ($letter == "OE") $text = "\x00\xd8";
            *			else if ($letter == "AE") $text = "\x00\xc6";
            */

            if ('AA' == $letter) {
                $text = 'Å';
            } elseif ('OE' == $letter) {
                $text = 'Ø';
            } elseif ('AE' == $letter) {
                $text = 'Æ';
            }
        }

        $letter = mb_substr($text, 0, 1);
    } else {
        $letter = mb_substr($text, 0, 1);
    }

    //-- if the letter is an other character then A-Z or a-z

    //-- define values of characters to look for

    $ord_value2 = [92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219];

    $ord_value3 = [228, 229, 230, 232, 233];

    $ord = ord(mb_substr($letter, 0, 1));

    if (in_array($ord, $ord_value2, true)) {
        $letter = stripslashes(mb_substr($text, 0, 2));
    } elseif (in_array($ord, $ord_value3, true)) {
        $letter = stripslashes(mb_substr($text, 0, 3));
    }

    //	else if (strlen($letter) == 1) $letter = str2upper($letter);

    return $letter;
}

/**
 * This function replaces @N.N. and @P.N. with the language specific translations
 * @param mixed $names $names could be an array of name parts or it could be a string of the name
 * @return string
 */
function check_NN($names)
{
    global $pgv_lang, $HNN, $ANN, $UNDERLINE_NAME_QUOTES;

    $fullname = '';

    $NN = $pgv_lang['NN'];

    $PN = $pgv_lang['PN'];

    if (!is_array($names)) {
        if (hasRTLText($names)) {
            $NN = RTLUndefined($names);

            $PN = $NN;
        }

        $names = stripslashes($names);

        $names = preg_replace(['~ /~', '~/,~', '~/~'], [' ', ',', ' '], $names);

        $names = preg_replace(['/@N.N.?/', '/@P.N.?/'], [$NN, $PN], trim($names));

        //-- underline names with a * at the end

        //-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165

        $names = preg_replace("/([^ ]+)\*/", '<span class="starredname">$1</span>', $names);

        if ($UNDERLINE_NAME_QUOTES) {
            $names = preg_replace('/"(.+)"/', '<span class="starredname">$1</span>', $names);
        }

        return $names;
    }

    if (2 == count($names) && mb_stristr($names[0], '@N.N') && mb_stristr($names[1], '@N.N')) {
        $fullname = $pgv_lang['NN'] . ' + ' . $pgv_lang['NN'];
    } else {
        for ($i = 0, $iMax = count($names); $i < $iMax; $i++) {
            if (hasRTLText($names[$i])) {
                $NN = RTLUndefined($names[$i]);

                $PN = $NN;
            }

            for ($i = 0, $iMax = count($names); $i < $iMax; $i++) {
                if (mb_stristr($names[$i], '@N.N')) {
                    $names[$i] = preg_replace('/@N.N.?/', $NN, trim($names[$i]));
                }

                if (mb_stristr($names[$i], '@P.N')) {
                    $names[$i] = $PN;
                }

                if ('@P.N.' == mb_substr(trim($names[$i]), 0, 5) && mb_strlen(trim($names[$i])) > 5) {
                    $names[$i] = mb_substr(trim($names[$i]), 5, (mb_strlen($names[$i]) - 5));
                }

                if (1 == $i && (mb_stristr($names[0], $pgv_lang['NN']) || mb_stristr($names[0], $HNN) || mb_stristr($names[0], $ANN)) && 3 == count($names)) {
                    $fullname .= ', ';
                } elseif (2 == $i && (mb_stristr($names[2], $pgv_lang['NN']) || mb_stristr($names[2], $HNN) || mb_stristr($names[2], $ANN)) && 3 == count($names)) {
                    $fullname .= ' + ';
                } elseif (2 == $i && mb_stristr($names[2], 'Individual ') && 3 == count($names)) {
                    $fullname .= ' + ';
                } elseif (2 == $i && count($names) > 3) {
                    $fullname .= ' + ';
                } else {
                    $fullname .= ', ';
                }

                $fullname .= trim($names[$i]);
            }
        }
    }

    if (empty($fullname)) {
        return $pgv_lang['NN'];
    }

    if (',' === mb_substr(trim($fullname), -1)) {
        $fullname = mb_substr($fullname, 0, mb_strlen(trim($fullname)) - 1);
    }

    if (', ' === mb_substr(trim($fullname), 0, 2)) {
        $fullname = mb_substr($fullname, 2, mb_strlen(trim($fullname)));
    }

    return $fullname;
}

/**
 * Put all characters in a string in lowercase
 *
 * This function is a replacement for strtolower() and will put all characters in lowercase
 *
 * @param string $value the text to be converted to lowercase
 * @return    string the converted text in lowercase
 * @author    eikland
 * @todo      look at function performance as it is much slower than strtolower
 */
function str2lower($value)
{
    global $language_settings, $LANGUAGE, $ALPHABET_upper, $ALPHABET_lower;

    global $all_ALPHABET_upper, $all_ALPHABET_lower;

    //-- get all of the upper and lower alphabets as a string

    if (!isset($all_ALPHABET_upper)) {
        $all_ALPHABET_upper = '';

        $all_ALPHABET_lower = '';

        foreach ($ALPHABET_upper as $l => $up_alphabet) {
            $lo_alphabet = $ALPHABET_lower[$l];

            $ll = mb_strlen($lo_alphabet);

            $ul = mb_strlen($up_alphabet);

            if ($ll < $ul) {
                $lo_alphabet .= mb_substr($up_alphabet, $ll);
            }

            if ($ul < $ll) {
                $up_alphabet .= mb_substr($lo_alphabet, $ul);
            }

            $all_ALPHABET_lower .= $lo_alphabet;

            $all_ALPHABET_upper .= $up_alphabet;
        }
    }

    $value_lower = '';

    $len = mb_strlen($value);

    //-- loop through all of the letters in the value and find their position in the

    //-- upper case alphabet.  Then use that position to get the correct letter from the

    //-- lower case alphabet.

    $ord_value2 = [92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219];

    $ord_value3 = [228, 229, 230, 232, 233];

    for ($i = 0; $i < $len; $i++) {
        $letter = mb_substr($value, $i, 1);

        $ord = ord($letter);

        if (in_array($ord, $ord_value2, true)) {
            $i++;

            $letter .= mb_substr($value, $i, 1);
        } elseif (in_array($ord, $ord_value3, true)) {
            $i++;

            $letter .= mb_substr($value, $i, 2);

            $i++;
        }

        $pos = mb_strpos($all_ALPHABET_upper, $letter);

        if (false !== $pos) {
            $letter = mb_substr($all_ALPHABET_lower, $pos, mb_strlen($letter));
        }

        $value_lower .= $letter;
    }

    return $value_lower;
}

// END function str2lower

/**
 * Put all characters in a string in uppercase
 *
 * This function is a replacement for strtoupper() and will put all characters in uppercase
 *
 * @param string $value the text to be converted to uppercase
 * @return    string the converted text in uppercase
 * @author    botak
 * @todo      look at function performance as it is much slower than strtoupper
 */
function str2upper($value)
{
    global $language_settings, $LANGUAGE, $ALPHABET_upper, $ALPHABET_lower;

    global $all_ALPHABET_upper, $all_ALPHABET_lower;

    //-- get all of the upper and lower alphabets as a string

    if (!isset($all_ALPHABET_upper)) {
        $all_ALPHABET_upper = '';

        $all_ALPHABET_lower = '';

        foreach ($ALPHABET_upper as $l => $up_alphabet) {
            $lo_alphabet = $ALPHABET_lower[$l];

            $ll = mb_strlen($lo_alphabet);

            $ul = mb_strlen($up_alphabet);

            if ($ll < $ul) {
                $lo_alphabet .= mb_substr($up_alphabet, $ll);
            }

            if ($ul < $ll) {
                $up_alphabet .= mb_substr($lo_alphabet, $ul);
            }

            $all_ALPHABET_lower .= $lo_alphabet;

            $all_ALPHABET_upper .= $up_alphabet;
        }
    }

    $value_upper = '';

    $len = mb_strlen($value);

    //-- loop through all of the letters in the value and find their position in the

    //-- lower case alphabet.  Then use that position to get the correct letter from the

    //-- upper case alphabet.

    $ord_value2 = [92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219];

    $ord_value3 = [228, 229, 230, 232, 233];

    for ($i = 0; $i < $len; $i++) {
        $letter = mb_substr($value, $i, 1);

        $ord = ord($letter);

        if (in_array($ord, $ord_value2, true)) {
            $i++;

            $letter .= mb_substr($value, $i, 1);
        } elseif (in_array($ord, $ord_value3, true)) {
            $i++;

            $letter .= mb_substr($value, $i, 2);

            $i++;
        }

        $pos = mb_strpos($all_ALPHABET_lower, $letter);

        if (false !== $pos) {
            $letter = mb_substr($all_ALPHABET_upper, $pos, mb_strlen($letter));
        }

        $value_upper .= $letter;
    }

    return $value_upper;
}

// END function str2upper

/**
 * Convert a string to UTF8
 *
 * This function is a replacement for utf8_decode()
 *
 * @author    http://www.php.net/manual/en/function.utf8-decode.php
 * @param string $in_str the text to be converted
 * @return    string the converted text
 */
function smart_utf8_decode($in_str)
{
    $new_str = html_entity_decode(htmlentities($in_str, ENT_COMPAT, 'UTF-8'));

    $new_str = str_replace('&oelig;', "\x9c", $new_str);

    $new_str = str_replace('&OElig;', "\x8c", $new_str);

    return $new_str;
    /**
     * // Replace ? with a unique string
     * $new_str = str_replace("?", "q0u0e0s0t0i0o0n", $in_str);
     *
     * // Try the utf8_decode
     * $new_str=utf8_decode($new_str);
     *
     * // if it contains ? marks
     * if (strpos($new_str,"?") !== false)
     * {
     * // Something went wrong, set new_str to the original string.
     * $new_str=$in_str;
     * }
     * else
     * {
     * // If not then all is well, put the ?-marks back where is belongs
     * $new_str = str_replace("q0u0e0s0t0i0o0n", "?", $new_str);
     * }
     * return $new_str;
     **/
}

/**
 * get an array of names from an indivdual record
 * @param string $indirec The raw individual gedcom record
 * @param mixed $import
 * @return array    The array of individual names
 */
function get_indi_names($indirec, $import = false)
{
    $names = [];

    //-- get all names

    $namerec = get_sub_record(1, '1 NAME', $indirec, 1);

    if (empty($namerec)) {
        $names[] = ['@P.N /@N.N./', '@', '@N.N.'];
    } else {
        $j = 1;

        while (!empty($namerec)) {
            $name = get_name_in_record($namerec);

            $surname = extract_surname($name, false);

            if (empty($surname)) {
                $surname = '@N.N.';
            }

            $lname = preg_replace("/^[a-z0-9 \.]+/", '', $surname);

            if (empty($lname)) {
                $lname = $surname;
            }

            $letter = get_first_letter($lname, $import);

            $letter = str2upper($letter);

            if (empty($letter)) {
                $letter = '@';
            }

            if (0 == preg_match('~/~', $name)) {
                $name .= ' /@N.N./';
            }

            $names[] = [$name, $letter, $surname, 'A'];

            //-- check for _HEB or ROMN name sub tags

            $addname = get_add_person_name_in_record($namerec, true);

            if (!empty($addname)) {
                $surname = extract_surname($addname, false);

                if (empty($surname)) {
                    $surname = '@N.N.';
                }

                $lname = preg_replace("/^[a-z0-9 \.]+/", '', $surname);

                if (empty($lname)) {
                    $lname = $surname;
                }

                $letter = get_first_letter($lname, $import);

                $letter = str2upper($letter);

                if (empty($letter)) {
                    $letter = '@';
                }

                if (0 == preg_match('~/~', $addname)) {
                    $addname .= ' /@N.N./';
                }

                $names[] = [$addname, $letter, $surname, 'A'];
            }

            //-- check for _MARNM name subtags

            $ct = preg_match_all("/\d _MARNM (.*)/", $namerec, $match, PREG_SET_ORDER);

            for ($i = 0; $i < $ct; $i++) {
                $marriedname = trim($match[$i][1]);

                $surname = extract_surname($marriedname, false);

                if (empty($surname)) {
                    $surname = '@N.N.';
                }

                $lname = preg_replace("/^[a-z0-9 \.]+/", '', $surname);

                if (empty($lname)) {
                    $lname = $surname;
                }

                $letter = get_first_letter($lname, $import);

                $letter = str2upper($letter);

                if (empty($letter)) {
                    $letter = '@';
                }

                if (0 == preg_match('~/~', $marriedname)) {
                    $marriedname .= ' /@N.N./';
                }

                $names[] = [$marriedname, $letter, $surname, 'C'];
            }

            //-- check for _AKA name subtags

            $ct = preg_match_all("/\d _AKA (.*)/", $namerec, $match, PREG_SET_ORDER);

            for ($i = 0; $i < $ct; $i++) {
                $marriedname = trim($match[$i][1]);

                $surname = extract_surname($marriedname, false);

                if (empty($surname)) {
                    $surname = '@N.N.';
                }

                $lname = preg_replace("/^[a-z0-9 \.]+/", '', $surname);

                if (empty($lname)) {
                    $lname = $surname;
                }

                $letter = get_first_letter($lname, $import);

                $letter = str2upper($letter);

                if (empty($letter)) {
                    $letter = '@';
                }

                if (0 == preg_match('~/~', $marriedname)) {
                    $marriedname .= ' /@N.N./';
                }

                $names[] = [$marriedname, $letter, $surname, 'A'];
            }

            $j++;

            $namerec = get_sub_record(1, '1 NAME', $indirec, $j);
        }
    }

    return $names;
}
