<?php

/**
 * Defines a protocol for interfacing remote requests over a http connection
 *
 * When $action is 'get' then the gedcom record with the given $xref is retrieved.
 * When $action is 'update' the gedcom record matching $xref is replaced with the data in $gedrec.
 * When $action is 'append' the gedcom record in $gedrec is appended to the end of the gedcom file.
 * When $action is 'delete' the gedcom record with $xref is removed from the file.
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
 * @version    $Id: client.php,v 1.1 2005/10/07 18:08:01 skenow Exp $
 */
require 'config.php';
require $PGV_BASE_DIRECTORY . 'includes/functions_edit.php';
header("Content-Type: text/plain; charset=$CHARACTER_SET");

$pgv_user = getUserName();
$READ_ONLY = 0;
if ((isset($_SESSION['readonly'])) && (true === $_SESSION['readonly'])) {
    $READ_ONLY = 1;
}
if (!empty($_REQUEST['GEDCOM'])) {
    if (!isset($GEDCOMS[$_REQUEST['GEDCOM']])) {
        addDebugLog('ERROR 21: Invalid GEDCOM specified.  Remember that the GEDCOM is case sensitive.');

        print "ERROR 21: Invalid GEDCOM specified.  Remember that the GEDCOM is case sensitive.\n";

        exit;
    }
}
if (empty($action)) {
    addDebugLog('ERROR 1: No action specified.');

    print "ERROR 1: No action specified.\n";
} elseif (0 == count($GEDCOMS)) {
    addDebugLog($action . ' ERROR 21: No Gedcoms available on this site.');

    print "ERROR 21: No Gedcoms available on this site.\n";

    exit;
} elseif (!check_for_import($GEDCOM)) {
    addDebugLog($action . " ERROR 22: Gedcom [$GEDCOM] needs to be imported.");

    print "ERROR 22: Gedcom [$GEDCOM] needs to be imported.\n";

    exit;
} elseif ('version' == $action) {
    addDebugLog($action . " SUCCESS\n$VERSION $VERSION_RELEASE\n");

    print "SUCCESS\n$VERSION $VERSION_RELEASE\n";
} elseif ('connect' == $action) {
    if (!empty($username)) {
        $userStat = authenticateUser($username, $password);

        if (!$userStat) {
            addDebugLog($action . " username=$username ERROR 10: Username and password key failed to authenticate.");

            print "ERROR 10: Username and password key failed to authenticate.\n";
        } else {
            $stat = newConnection();

            if (false !== $stat) {
                addDebugLog($action . " username=$username SUCCESS\n" . $stat);

                print "SUCCESS\n" . $stat;
            }

            $_SESSION['connected'] = $username;

            $canedit = userCanEdit($username);

            if (!$canedit) {
                AddToLog('Read-Only Client connection from ' . $username);
            }

            /*else {
                print "ERROR 11: Username $username does not have write permissions.\n";
            }*/
        }
    } else {
        $stat = newConnection();

        if (false !== $stat) {
            addDebugLog($action . " SUCCESS\n" . $stat);

            print "SUCCESS\n" . $stat;
        }

        AddToLog('Read-Only Anonymous Client connection.');

        $_SESSION['connected'] = 'Anonymous';

        $_SESSION['readonly'] = 1;

        //print "ERROR 9: Could not connect to GEDCOM.  No username specified.\n";
    }

    if (!empty($readonly)) {
        $_SESSION['readonly'] = 1;
    }
} elseif ('listgedcoms' == $action) {
    $out_msg = "SUCCESS\n";

    foreach ($GEDCOMS as $ged => $gedarray) {
        $out_msg .= "$ged\t" . $gedarray['title'] . "\n";
    }

    addDebugLog($action . ' ' . $out_msg);

    print $out_msg;
} elseif (empty($_SESSION['connected'])) {
    addDebugLog($action . " ERROR 12: use 'connect' action to initiate a session.");

    print "ERROR 12: use 'connect' action to initiate a session.\n";
} elseif ('get' == $action) {
    if (!empty($xref)) {
        $xrefs = preg_preg_split('/;/', $xref);

        $success = true;

        $gedrecords = '';

        foreach ($xrefs as $indexval => $xref1) {
            $gedrec = '';

            $xref1 = trim($xref1);

            $xref1 = clean_input($xref1);

            if (!empty($xref1)) {
                if (isset($pgv_changes[$xref1 . '_' . $GEDCOM])) {
                    $gedrec = find_record_in_file($xref1);
                }

                if (empty($gedrec)) {
                    $gedrec = find_gedcom_record($xref1);
                }

                if (!empty($gedrec)) {
                    $gedrec = trim($gedrec);

                    preg_match('/0 @(.*)@ (.*)/', $gedrec, $match);

                    $type = trim($match[2]);

                    if (displayDetails($gedrec)) {
                        $gedrecords .= "\n" . trim($gedrec);
                    } else {
                        //-- do not have full access to this record, so privatize it

                        $gedrec = privatize_gedcom($gedrec);

                        $gedrecords .= "\n" . trim($gedrec);

                        //$success=false;
                        //print "ERROR 18: Access denied for individual xref:$xref1.\n";
                    }
                }

                // finding nothing is not an error
            }
        } //-- end for loop

        if ($success) {
            if (empty($_REQUEST['keepfile'])) {
                $ct = preg_match_all('/ FILE (.*)/', $gedrecords, $match, PREG_SET_ORDER);

                for ($i = 0; $i < $ct; $i++) {
                    $mediaurl = $SERVER_URL . $MEDIA_DIRECTORY . extract_filename($match[$i][1]);

                    $gedrecords = str_replace($match[$i][1], $mediaurl, $gedrecords);
                }
            }

            addDebugLog($action . " xref=$xref " . $gedrecords);

            print "SUCCESS\n" . trim($gedrecords);
        }
    } else {
        addDebugLog($action . ' ERROR 3: No gedcom id specified.  Please specify a xref.');

        print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
    }
} elseif ('getvar' == $action) {
    if ((!empty($pgv_user)) && (!empty($var)) && (isset($$var)) && (!in_array($var, $CONFIG_VARS, true))) {
        addDebugLog($action . " var=$var SUCCESS\n" . $$var);

        print "SUCCESS\n" . $$var;
    } else {
        addDebugLog($action . " var=$var ERROR 13: Invalid variable specified.  Please provide a variable.");

        print "ERROR 13: Invalid variable specified.\n";
    }
} elseif ('update' == $action) {
    if (!empty($xref)) {
        if (!empty($gedrec)) {
            if ((empty($_SESSION['readonly'])) && (userCanEdit($pgv_user)) && (displayDetails($gedrec))) {
                $gedrec = preg_replace(['/\\\\+r/', '/\\\\+n/'], ["\r", "\n"], $gedrec);

                $success = replace_gedrec($xref, $gedrec);

                if ($success) {
                    addDebugLog($action . " xref=$xref gedrec=$gedrec SUCCESS");

                    print "SUCCESS\n";
                }
            } else {
                addDebugLog($action . " xref=$xref ERROR 11: No write privileges for this record.");

                print "ERROR 11: No write privileges for this record.\n";
            }
        } else {
            addDebugLog($action . " xref=$xref ERROR 8: No gedcom record provided.  Unable to process request.");

            print "ERROR 8: No gedcom record provided.  Unable to process request.\n";
        }
    } else {
        addDebugLog($action . ' ERROR 3: No gedcom id specified.  Please specify a xref.');

        print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
    }
} elseif ('append' == $action) {
    if (!empty($gedrec)) {
        if ((empty($_SESSION['readonly'])) && (userCanEdit($pgv_user))) {
            $gedrec = preg_replace(['/\\\\+r/', '/\\\\+n/'], ["\r", "\n"], $gedrec);

            $xref = append_gedrec($gedrec);

            if ($xref) {
                addDebugLog($action . " gedrec=$gedrec SUCCESS\n$xref");

                print "SUCCESS\n$xref\n";
            }
        } else {
            addDebugLog($action . " gedrec=$gedrec ERROR 11: No write privileges for this record.");

            print "ERROR 11: No write privileges for this record.\n";
        }
    } else {
        addDebugLog($action . ' ERROR 8: No gedcom record provided.  Unable to process request.');

        print "ERROR 8: No gedcom record provided.  Unable to process request.\n";
    }
} elseif ('delete' == $action) {
    if (!empty($xref)) {
        if ((empty($_SESSION['readonly'])) && (userCanEdit($pgv_user)) && (displayDetailsByID($xref))) {
            $success = delete_gedrec($xref);

            if ($success) {
                addDebugLog($action . " xref=$xref SUCCESS");

                print "SUCCESS\n";
            }
        } else {
            addDebugLog($action . " xref=$xref ERROR 11: No write privileges for this record.");

            print "ERROR 11: No write privileges for this record.\n";
        }
    } else {
        addDebugLog($action . ' ERROR 3: No gedcom id specified.  Please specify a xref.');

        print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
    }
} elseif ('getnext' == $action) {
    $myindilist = get_indi_list();

    $gedrec = '';

    if (!empty($xref)) {
        $xref1 = get_next_xref($xref);

        if (isset($pgv_changes[$xref1 . '_' . $GEDCOM])) {
            $gedrec = @find_record_in_file($xref1);
        }

        if (empty($gedrec)) {
            $gedrec = @find_gedcom_record($xref1);
        }

        if (!displayDetails($gedrec)) {
            //-- do not have full access to this record, so privatize it

            $gedrec = privatize_gedcom($gedrec);
        }

        addDebugLog($action . " xref=$xref SUCCESS\n" . trim($gedrec));

        print "SUCCESS\n" . trim($gedrec);
    } else {
        addDebugLog($action . ' ERROR 3: No gedcom id specified.  Please specify a xref.');

        print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
    }
} elseif ('getprev' == $action) {
    $myindilist = get_indi_list();

    $gedrec = '';

    if (!empty($xref)) {
        $xref1 = get_prev_xref($xref);

        if (isset($pgv_changes[$xref1 . '_' . $GEDCOM])) {
            $gedrec = @find_record_in_file($xref1);
        }

        if (empty($gedrec)) {
            $gedrec = @find_gedcom_record($xref1);
        }

        if (!displayDetails($gedrec)) {
            //-- do not have full access to this record, so privatize it

            $gedrec = privatize_gedcom($gedrec);
        }

        addDebugLog($action . " xref=$xref SUCCESS\n" . trim($gedrec));

        print "SUCCESS\n" . trim($gedrec);
    } else {
        addDebugLog($action . ' ERROR 3: No gedcom id specified.  Please specify a xref.');

        print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
    }
} elseif ('search' == $action) {
    if (!empty($query)) {
        $sindilist = search_indis($query);

        uasort($sindilist, 'itemsort');

        $msg_out = "SUCCESS\n";

        foreach ($sindilist as $xref => $indi) {
            if (displayDetailsByID($xref)) {
                $msg_out .= "$xref\n";
            }
        }

        addDebugLog($action . " query=$query " . $msg_out);

        print $msg_out;
    } else {
        addDebugLog($action . ' ERROR 15: No query specified.  Please specify a query.');

        print "ERROR 15: No query specified.  Please specify a query.\n";
    }
} elseif ('soundex' == $action) {
    if ((!empty($lastname)) || (!empty($firstname))) {
        $myindilist = get_indi_list();

        $sindilist = [];

        // -- only get the names who match soundex

        foreach ($myindilist as $key => $value) {
            $save = false;

            $name = preg_replace("/ [jJsS][rR]\.?,/", ',', $value['name']);

            $names = preg_preg_split('/,/', $name);

            if (soundex($names[0]) == soundex($lastname)) {
                $save = true;

                if (!empty($firstname)) {
                    $save = false;

                    $firstnames = preg_preg_split("/\s/", trim($firstname));

                    if (isset($names[1])) {
                        $fnames = preg_preg_split("/\s/", trim($names[1]));
                    } else {
                        $fnames = preg_preg_split("/\s/", trim($names[0]));
                    }

                    for ($i = 0, $iMax = count($fnames); $i < $iMax; $i++) {
                        for ($j = 0, $jMax = count($firstnames); $j < $jMax; $j++) {
                            if (soundex($fnames[$i]) == soundex($firstnames[$j])) {
                                $save = true;
                            }
                        }
                    }
                }
            }

            if ($save) {
                $sindilist[(string)$key] = $value;
            }
        }

        $msg_out = "SUCCESS\n";

        uasort($sindilist, 'itemsort');

        reset($sindilist);

        foreach ($sindilist as $xref => $indi) {
            if (displayDetailsByID($xref)) {
                $msg_out .= "$xref\n";
            }
        }

        addDebugLog($action . " lastname=$lastname firstname=$firstname " . $msg_out);

        print $msg_out;
    } else {
        addDebugLog($action . ' ERROR 16: No names specified.  Please specify a firstname or a lastname.');

        print "ERROR 16: No names specified.  Please specify a firstname or a lastname.\n";
    }
} elseif ('getxref' == $action) {
    if (empty($position)) {
        $position = 'first';
    }

    if (empty($type)) {
        $type = 'INDI';
    }

    if ((empty($type)) || (!in_array($type, ['INDI', 'FAM', 'SOUR', 'REPO', 'NOTE', 'OBJE', 'OTHER'], true))) {
        addDebugLog($action . " type=$type position=$position ERROR 18: Invalid \$type specification.  Valid types are INDI, FAM, SOUR, REPO, NOTE, OBJE, or OTHER");

        print "ERROR 18: Invalid \$type specification.  Valid types are INDI, FAM, SOUR, REPO, NOTE, OBJE, or OTHER\n";

        exit;
    }

    $myindilist = [];

    if ('OTHER' != $type) {
        $ct = preg_match_all("/0 @(.*)@ $type/", $fcontents, $match, PREG_SET_ORDER);

        for ($i = 0; $i < $ct; $i++) {
            $xref1 = trim($match[$i][1]);

            $myindilist[$xref1] = $xref1;
        }
    } else {
        $ct = preg_match_all('/0 @(.*)@ (.*)/', $fcontents, $match, PREG_SET_ORDER);

        for ($i = 0; $i < $ct; $i++) {
            $xref1 = trim($match[$i][1]);

            $xtype = trim($match[$i][2]);

            if (('INDI' != $xtype) && ('FAM' != $xtype) && ('SOUR' != $xtype)) {
                $myindilist[$xref1] = $xref1;
            }
        }
    }

    reset($myindilist);

    if ('first' == $position) {
        $xref = current($myindilist);

        addDebugLog($action . " type=$type position=$position SUCCESS\n$xref");

        print "SUCCESS\n$xref\n";
    } elseif ('last' == $position) {
        $xref = end($myindilist);

        addDebugLog($action . " type=$type position=$position SUCCESS\n$xref");

        print "SUCCESS\n$xref\n";
    } elseif ('next' == $position) {
        if (!empty($xref)) {
            $xref1 = get_next_xref($xref, $type);

            if (false !== $xref1) {
                addDebugLog($action . " type=$type position=$position xref=$xref SUCCESS\n$xref1");

                print "SUCCESS\n$xref1\n";
            }
        } else {
            addDebugLog($action . " type=$type position=$position ERROR 3: No gedcom id specified.  Please specify a xref.");

            print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
        }
    } elseif ('prev' == $position) {
        if (!empty($xref)) {
            $xref1 = get_prev_xref($xref, $type);

            if (false !== $xref1) {
                addDebugLog($action . " type=$type position=$position xref=$xref SUCCESS\n$xref1");

                print "SUCCESS\n$xref1\n";
            }
        } else {
            addDebugLog($action . " type=$type position=$position ERROR 3: No gedcom id specified.  Please specify a xref.");

            print "ERROR 3: No gedcom id specified.  Please specify a xref.\n";
        }
    } elseif ('all' == $position) {
        $msg_out = "SUCCESS\n";

        foreach ($myindilist as $key => $value) {
            $msg_out .= "$key\n";
        }

        addDebugLog($action . " type=$type position=$position " . $msg_out);

        print $msg_out;
    } elseif ('new' == $position) {
        if ((empty($_SESSION['readonly'])) && (userCanEdit($pgv_user))) {
            if ((empty($type)) || (!in_array($type, ['INDI', 'FAM', 'SOUR', 'REPO', 'NOTE', 'OBJE', 'OTHER'], true))) {
                addDebugLog($action . " type=$type position=$position ERROR 18: Invalid \$type specification.  Valid types are INDI, FAM, SOUR, REPO, NOTE, OBJE, or OTHER");

                print "ERROR 18: Invalid \$type specification.  Valid types are INDI, FAM, SOUR, REPO, NOTE, OBJE, or OTHER\n";

                exit;
            }

            $gedrec = "0 @REF@ $type";

            $xref = append_gedrec($gedrec);

            if ($xref) {
                addDebugLog($action . " type=$type position=$position SUCCESS\n$xref");

                print "SUCCESS\n$xref\n";
            }
        } else {
            addDebugLog($action . " type=$type position=$position ERROR 11: No write privileges for this record.");

            print "ERROR 11: No write privileges for this record.\n";
        }
    } else {
        addDebugLog($action . " type=$type position=$position ERROR 17: Unknown position reference.  Valid values are first, last, prev, next.");

        print "ERROR 17: Unknown position reference.  Valid values are first, last, prev, next.\n";
    }
} elseif ('uploadmedia' == $action) {
    $error = '';

    $upload_errors = [$pgv_lang['file_success'], $pgv_lang['file_too_big'], $pgv_lang['file_too_big'], $pgv_lang['file_partial'], $pgv_lang['file_missing']];

    if (isset($_FILES['mediafile'])) {
        if (!move_uploaded_file($_FILES['mediafile']['tmp_name'], $MEDIA_DIRECTORY . $_FILES['mediafile']['name'])) {
            $error .= 'ERROR 19: ' . $pgv_lang['upload_error'] . ' ' . $upload_errors[$_FILES['mediafile']['error']];
        } elseif (!isset($_FILES['thumbnail'])) {
            $filename = $MEDIA_DIRECTORY . $_FILES['mediafile']['name'];

            $thumbnail = $MEDIA_DIRECTORY . 'thumbs/' . $_FILES['mediafile']['name'];

            generate_thumbnail($filename, $thumbnail);

            //if (!$thumbgenned) $error .= "ERROR 19: ".$pgv_lang["thumbgen_error"].$filename;
        }
    }

    if (isset($_FILES['thumbnail'])) {
        if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], $MEDIA_DIRECTORY . 'thumbs/' . $_FILES['thumbnail']['name'])) {
            $error .= "\nERROR 19: " . $pgv_lang['upload_error'] . ' ' . $upload_errors[$_FILES['thumbnail']['error']];
        }
    }

    if (!empty($error)) {
        addDebugLog($action . " $error");

        print $error . "\n";
    } else {
        addDebugLog($action . ' SUCCESS');

        print "SUCCESS\n";
    }
} else {
    addDebugLog($action . ' ERROR 2: Unable to process request.  Unknown action.');

    print "ERROR 2: Unable to process request.  Unknown action.\n";
}
