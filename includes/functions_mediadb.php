<?php

/**
 * Various functions used by the media DB interface
 *
 * phpGedView: Genealogy Viewer
 * Copyright (C) 2002 to 2005 Peter Dyson, John Finlay and Others
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
 * @version    $Id: functions_mediadb.php,v 1.2 2006/01/09 00:46:23 skenow Exp $
 */
if (mb_strstr($_SERVER['PHP_SELF'], 'functions')) {
    print "Now, why would you want to do that.  You're not hacking are you?";

    exit;
}

/**
 * Change Interface to media database
 *
 * Commits changes which may include linked media records to the database.
 * Should be called from the accept changes code. No other undo mechanism required
 * for the media db this way. By taking the whole gedcom record we isolate changes
 * which occur in implementation on either side of this interface. This back end
 * code takes what it needs from the gedrec.
 *
 * @param gedrec $gedrec Record to be checked for changes and commited to database.
 * @param bool   $delete Tells this routine that the record passed must be deleted.
 * @param mixed  $ged
 * @return bool
 * @return bool
 */
function commit_db_changes($ged, $gedrec, $delete = false)
{
    //-- TODO complete

    //-- TODO think about media attached to records other than direct on the indi or fam (i.e. > level 1)

    // does this contain anything of interest

    $ct = preg_match('/1 OBJE @(M.*)@/', $gedrec, $match);

    if ($ct > 0) {
        // get the level 0 gid

        $ct = preg_match('/0 @(.*)@/', $gedrec, $match);

        $indi = $match[1];

        $mapping = get_db_indi_mapping_list($indi);

        // split by level 1 tags

        $lvltags = preg_preg_split("/\n/", $gedrec);

        // anything of interest in each of the tags

        $am_interested = false;

        foreach ($lvltags as $indexval => $rec) {
            // is it a level 1 tag (keeping this format in case we do something with

            // media links indented past a level one item in future.

            $ct = preg_match("/(\d) OBJE @(M.*)@/", $rec, $match);

            if (($ct > 0) && ('1' == $match[1])) { // - we found something of interest
                //-- if we have found a new record while still interested in the old record

                //-- commit that first

                if ($am_interested) {
                    update_db_link($mediaid, $indi, $mediarec, $ged);

                    $mapping[$mediaid]['CHECK'] = true;
                }

                //-- now process the new record

                $mediaid = $match[2];

                $mediarec = trim($rec);

                $am_interested = true;
            } elseif ($am_interested) {
                // if we fall back to a level one item then commit this record

                // will not be a media level 1 record that is caught above.

                $items = preg_split(' ', $rec);

                if ('1' == $items[0]) {
                    update_db_link($mediaid, $indi, $mediarec, $ged);

                    $mapping[$mediaid]['CHECK'] = true;

                    $am_interested = false;
                } else {
                    //otherwise add this item to the media record and continue

                    $mediarec .= "\r\n" . trim($rec);
                }
            }
        }

        // if we fall out while interested then commit this item as it is the last lvl 1 in the record

        if ($am_interested) {
            update_db_link($mediaid, $indi, $mediarec, $ged);

            $mapping[$mediaid]['CHECK'] = true;
        }

        // - now we have updated the records in the gedcom record see if anything did not get

        //   updated, if so then it needs removing

        foreach ($mapping as $indexval => $map) {
            if (!$map['CHECK']) {
                unlink_db_item($map['XREF'], $indi, $ged);
            }
        }

        return true;
    }

    // nothing to do so return all ok

    return true;
}

/*
 *******************************
 * Database Interface functions
 *******************************/

/**
 * Removes a media item from this gedcom.
 *
 * Removes the main media record and any associated links
 * to individuals.
 *
 * @param string $media The gid of the record to be removed in the form Mxxxx.
 * @param string $ged   The gedcom file this action is to apply to.
 */
function remove_db_media($media, $ged)
{
    global $TBLPREFIX;

    // remove the media record

    $sql = 'DELETE FROM ' . $TBLPREFIX . "media WHERE m_id=$media AND m_gedfile=$ged";

    $res = dbquery($sql);

    // remove all links to this media item

    $sql = 'DELETE FROM ' . $TBLPREFIX . "media_mapping WHERE m_id=$media AND m_gedfile=$ged";

    $res = dbquery($sql);
}

/**
 * Removes a media item from a individual.
 *
 * Removes this link to an individual from the database.
 * All records attached to this link are lost also.
 *
 * @param string $media The gid of the record to be removed in the form Mxxxx.
 * @param string $indi  The gid that this media is linked to Ixxx Fxxx ect.
 * @param string $ged   The gedcom file this action is to apply to.
 */
function unlink_db_item($media, $indi, $ged)
{
    global $TBLPREFIX;

    // remove link to this media item

    $sql = 'DELETE FROM ' . $TBLPREFIX . "media_mapping WHERE (m_media='" . addslashes($media) . "' AND m_gedfile='" . addslashes($ged) . "' AND m_indi='" . addslashes($indi) . "')";

    $res = dbquery($sql);
}

/**
 * Queries the existence of a link in the db.
 *
 * @param string $media  The gid of the record to be removed in the form Mxxxx.
 * @param string $indi   The gid that this media is linked to Ixxx Fxxx ect.
 * @param mixed $ged
 * @return bool
 */
function exists_db_link($media, $indi, $ged)
{
    global $TBLPREFIX;

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media_mapping WHERE m_gedfile='" . addslashes($ged) . "' AND m_indi='" . addslashes($indi) . "' AND m_media='" . addslashes($media) . "'";

    $res = dbquery($sql);

    if ($res->numRows()) {
        return true;
    }

    return false;
}

/**
 * Updates any gedcom records associated with the media.
 *
 * Replace the gedrec for the media record.
 *
 * @param string $media  The gid of the record to be removed in the form Mxxxx.
 * @param string $gedrec The gedcom record as a string without the gid.
 * @param string $ged    The gedcom file this action is to apply to.
 */
function update_db_media($media, $gedrec, $ged)
{
    global $TBLPREFIX;

    // replace the gedrec for the media record

    $sql = 'UPDATE ' . $TBLPREFIX . "media SET m_gedrec = '" . addslashes($gedrec) . "' WHERE (m_id = '" . addslashes($media) . "' AND m_gedfile = '" . addslashes($ged) . "')";

    $res = dbquery($sql);
}

/**
 * Updates any gedcom records associated with the link.
 *
 * Replace the gedrec for an existing link record.
 *
 * @param string  $media  The gid of the record to be updated in the form Mxxxx.
 * @param string  $indi   The gid that this media is linked to Ixxx Fxxx ect.
 * @param string  $gedrec The gedcom record as a string without the gid.
 * @param string  $ged    The gedcom file this action is to apply to.
 * @param int $order  The order that this record should be displayed on the gid. If not supplied then
 *                        the order is not replaced.
 */
function update_db_link($media, $indi, $gedrec, $ged, $order = -1)
{
    global $TBLPREFIX;

    if (exists_db_link($media, $indi, $ged)) {
        // replace the gedrec for the media link record

        $sql = 'UPDATE ' . $TBLPREFIX . "media_mapping SET m_gedrec = '" . addslashes($gedrec) . "'";

        if ($order >= 0) {
            $sql .= ", m_order = $order";
        }

        $sql .= " WHERE (m_media = '" . addslashes($media) . "' AND m_gedfile = '" . addslashes($ged) . "' AND m_indi = '" . addslashes($indi) . "')";

        $res = dbquery($sql);
    } else {
        add_db_link($media, $indi, $gedrec, $ged, $order = -1);
    }
}

/**
 * Adds a new link into the database.
 *
 * Replace the gedrec for an existing link record.
 *
 * @param string  $media  The gid of the record to be updated in the form Mxxxx.
 * @param string  $indi   The gid that this media is linked to Ixxx Fxxx ect.
 * @param string  $gedrec The gedcom record as a string without the gid.
 * @param string  $ged    The gedcom file this action is to apply to.
 * @param int $order  The order that this record should be displayed on the gid. If not supplied then
 *                        the order is not replaced.
 */
function add_db_link($media, $indi, $gedrec, $ged, $order = -1)
{
    global $TBLPREFIX;

    // if no preference to order find the number of records and add to the end

    if ($order = -1) {
        $sql = 'SELECT * FROM ' . $TBLPREFIX . "media_mapping WHERE m_gedfile='" . addslashes($ged) . "' AND m_indi='" . addslashes($indi) . "'";

        $res = dbquery($sql);

        $ct = $res->numRows();

        $order = $ct + 1;
    }

    // add the new media link record

    $sql = 'INSERT INTO ' . $TBLPREFIX . "media_mapping VALUES(NULL,'" . addslashes($media) . "','" . addslashes($indi) . "','" . addslashes($order) . "','" . addslashes($ged) . "','" . addslashes($gedrec) . "')";

    $res = dbquery($sql);
}

/**
 * Update the media file location.
 *
 * When the user moves a file that is already imported into the db ensure the links are consistent.
 * This is the handler for media db injected items.
 *
 * @param string $oldfile The name of the file before the move.
 * @param string $newfile The new name for the file.
 * @param string $ged     The gedcom file this action is to apply to.
 * @return bool True if handled and record found in DB. False if not in DB so we can drop back to
 *                        media item handling for items not controlled by MEDIA_DB settings.
 */
function move_db_media($oldfile, $newfile, $ged)
{
    global $TBLPREFIX;

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media WHERE m_gedfile='" . addslashes($ged) . "' AND m_file='" . addslashes($oldfile) . "'";

    $res = dbquery($sql);

    if ($res->numRows()) {
        $row = &$res->fetchRow(DB_FETCHMODE_ASSOC);

        $m_id = $row['m_media'];

        $sql = 'UPDATE ' . $TBLPREFIX . "media SET m_file = '" . addslashes($newfile) . "' WHERE (m_gedfile='" . addslashes($ged) . "' AND m_file='" . addslashes($oldfile) . "')";

        $res = dbquery($sql);

        // if we in sql mode then update the PGV other table with this info

        $sql = 'SELECT * FROM ' . $TBLPREFIX . "other WHERE o_file='" . addslashes($ged) . "' AND o_id='" . $m_id . "'";

        $res1 = dbquery($sql);

        $srch = '/' . addcslashes($oldfile, '/.') . '/';

        $repl = addcslashes($newfile, '/.');

        if ($res1->numRows()) {
            $row1 = &$res1->fetchRow(DB_FETCHMODE_ASSOC);

            $gedrec = $row1['o_gedcom'];

            $newrec = stripcslashes(preg_replace($srch, $repl, $gedrec));

            $sql = 'UPDATE ' . $TBLPREFIX . "other SET o_gedcom = '" . addslashes($newrec) . "' WHERE o_file='" . addslashes($ged) . "' AND o_id='" . $m_id . "'";

            $res = dbquery($sql);
        }

        // alter the base gedcom file so that all is kept consistent

        $gedrec = find_record_in_file($m_id);

        $newrec = stripcslashes(preg_replace($srch, $repl, $gedrec));

        db_replace_gedrec($m_id, $newrec);

        return true;
    }

    return false;
}

/**
 * Update the media file location.
 *
 * When the user moves a file that is already imported into the db ensure the links are consistent.
 * This is the handling for non-ijected media items.
 *
 * @param string $oldfile The name of the file before the move.
 * @param string $newfile The new name for the file.
 * @param string $ged     The gedcom file this action is to apply to.
 */
function move_media_file($oldfile, $newfile, $ged)
{
    global $TBLPREFIX;
}

/*
 ****************************
 * general functions
 ****************************/

/**
 * Get the list of media from the database
 *
 * Searches the media table of the database for media items that
 * are associated with the currently active GEDCOM.
 *
 * The medialist that is returned contains the following elements:
 * - $media["ID"] the unique id of this media item in the table (Mxxxx)
 * - $media["GEDFILE"] the gedcom file the media item should be added to
 * - $media["FILE"] the filename of the media item
 * - $media["FORM"] the format of the item (ie JPG, PDF, etc)
 * - $media["TITL"] a title for the item, used for list display
 * - $media["NOTE"] gedcom record snippet
 *
 * @return mixed A media list array.
 */
function get_db_media_list()
{
    global $GEDCOM;

    global $TBLPREFIX;

    $medialist = [];

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media WHERE m_gedfile='$GEDCOM' ORDER BY m_id";

    $res = dbquery($sql);

    $ct = $res->numRows();

    while (false !== ($row = &$res->fetchRow(DB_FETCHMODE_ASSOC))) {
        $media = [];

        $media['ID'] = $row['m_id'];

        $media['XREF'] = stripslashes($row['m_media']);

        $media['GEDFILE'] = stripslashes($row['m_gedfile']);

        $media['FILE'] = stripslashes($row['m_file']);

        $media['FORM'] = stripslashes($row['m_ext']);

        $media['TITL'] = stripslashes($row['m_titl']);

        $media['NOTE'] = stripslashes($row['m_gedrec']);

        $medialist[] = $media;
    }

    return $medialist;
}

/**
 * Get the list of links to media from the database
 *
 * Searches the media table of the database for media items that
 * are associated with the currently active GEDCOM.
 *
 * The medialist that is returned contains the following elements:
 * - $mapping["ID"] Database id
 * - $mapping["INDI"] the gid of this media item is linked to (Ixxxx, Fxxx etc)
 * - $mapping["XREF"] the unique id of this media item in the table (Mxxxx)
 * - $mapping["ORDER"] the order the media item should be injected into the gedcom file
 * - $mapping["GEDFILE"] the gedcom file the media item should be added to
 * - $mapping["NOTE"] gedcom record snippet
 *
 * @return mixed A media list array.
 */
function get_db_mapping_list()
{
    global $GEDCOM, $TBLPREFIX;

    $mappinglist = [];

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media_mapping WHERE m_gedfile='$GEDCOM' ORDER BY m_indi,m_order";

    $res = dbquery($sql);

    $ct = $res->numRows();

    while (false !== ($row = &$res->fetchRow(DB_FETCHMODE_ASSOC))) {
        $mapping = [];

        $mapping['ID'] = $row['m_id'];

        $mapping['INDI'] = stripslashes($row['m_indi']);

        $mapping['XREF'] = stripslashes($row['m_media']);

        $mapping['GEDFILE'] = stripslashes($row['m_gedfile']);

        $mapping['ORDER'] = stripslashes($row['m_order']);

        $mapping['GEDFILE'] = stripslashes($row['m_gedfile']);

        $mapping['NOTE'] = stripslashes($row['m_gedrec']);

        $mappinglist[] = $mapping;
    }

    return $mappinglist;
}

/**
 * Get the list of links to media from the database for a person/family/source
 *
 * Searches the media table of the database for media items that
 * are associated with a person/family/source.
 *
 * The medialist that is returned contains the following elements:
 * - $mapping["ID"] Database id
 * - $mapping["INDI"] the gid of this media item is linked to (Ixxxx, Fxxx etc)
 * - $mapping["XREF"] the unique id of this media item in the table (Mxxxx)
 * - $mapping["ORDER"] the order the media item should be injected into the gedcom file
 * - $mapping["GEDFILE"] the gedcom file the media item should be added to
 * - $mapping["NOTE"] gedcom record snippet
 * - $mapping["CHECK"] boolean for calling routine use, false by default.
 *
 * @param string $indi The person/family/source item to find media links for
 * @return mixed A media list array.
 */
function get_db_indi_mapping_list($indi)
{
    global $GEDCOM, $TBLPREFIX;

    $mappinglist = [];

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media_mapping WHERE m_gedfile='$GEDCOM' AND m_indi='$indi' ORDER BY m_order";

    $res = dbquery($sql);

    $ct = $res->numRows();

    while (false !== ($row = &$res->fetchRow(DB_FETCHMODE_ASSOC))) {
        $mapping = [];

        $mapping['ID'] = $row['m_id'];

        $mapping['INDI'] = stripslashes($row['m_indi']);

        $mapping['XREF'] = stripslashes($row['m_media']);

        $mapping['GEDFILE'] = stripslashes($row['m_gedfile']);

        $mapping['ORDER'] = stripslashes($row['m_order']);

        $mapping['GEDFILE'] = stripslashes($row['m_gedfile']);

        $mapping['NOTE'] = stripslashes($row['m_gedrec']);

        $mapping['CHECK'] = false;

        $mappinglist[$row['m_media']] = $mapping;
    }

    return $mappinglist;
}

/**
 * This is a copy of the function from gdbi_functions.php
 *
 * The main difference is that this version stop the renumbering
 * of the XREF so it stays consistent with the database. Though
 * this version could replace the existing as the $renum parameter
 * is optional and existing code should work with this version.
 *
 * @param mixed $gedrec The gedcom record to append to the file
 * @param mixed $renum
 * @return mixed 0 xref or false
 */
function append_gedrec1($gedrec, $renum = true)
{
    global $fcontents, $GEDCOM, $pgv_changes;

    if (false !== ($gedrec = check_gedcom($gedrec))) {
        $ct = preg_match('/0 @(.*)@ (.*)/', $gedrec, $match);

        $gid = $match[1];

        $type = trim($match[2]);

        if ($renum) {
            $xref = get_new_xref($type);

            $gedrec = preg_replace("/0 @$gid@/", "0 @$xref@", $gedrec);
        } else {
            $xref = $gid;
        }

        $pos1 = mb_strrpos($fcontents, '0');

        $fcontents = mb_substr($fcontents, 0, $pos1) . trim($gedrec) . "\r\n" . mb_substr($fcontents, $pos1);

        $change = [];

        $change['gid'] = $xref;

        $change['gedcom'] = $GEDCOM;

        $change['type'] = 'append';

        $change['status'] = 'submitted';

        $change['user'] = getUserName();

        $change['time'] = time();

        $change['undo'] = '';

        if (!isset($pgv_changes[$xref . '_' . $GEDCOM])) {
            $pgv_changes[$xref . '_' . $GEDCOM] = [];
        }

        $pgv_changes[$xref . '_' . $GEDCOM][] = $change;

        AddToLog("Appending new $type record $gid ->" . getUserName() . '<-');

        if (write_file()) {
            return $xref;
        }

        return false;
    }

    return false;
}

/**
 * No change logging version of replace_gedrec.
 *
 * This function is and should only be used during the inject media phase
 * as it breaks all undo functionality.
 *
 * @param string $indi Gid of the individuals record to replace.
 * @param mixed  $gedrec
 * @return bool
 * @return bool
 */
function db_replace_gedrec($indi, $gedrec)
{
    global $fcontents;

    $pos1 = mb_strpos($fcontents, "0 @$indi@");

    if (false === $pos1) {
        print "ERROR 4: Could not find gedcom record with xref:$indi\n";

        return false;
    }

    if (false !== ($gedrec = check_gedcom($gedrec, false))) {
        $pos2 = mb_strpos($fcontents, '0 @', $pos1 + 1);

        if (false === $pos2) {
            $pos2 = mb_strlen($fcontents);
        }

        $fcontents = mb_substr($fcontents, 0, $pos1) . trim($gedrec) . "\r\n" . mb_substr($fcontents, $pos2);

        return write_file();
    }

    return false;
}

/**
 * Ensures the database files are present and correct for this version.
 */
function check_media_db()
{
    global $TBLPREFIX;

    $sql = 'SHOW TABLES';

    $res = dbquery($sql);

    $has_media = false;

    $has_media_mapping = false;

    while (false !== ($table = &$res->fetchRow())) {
        if ($table[0] == $TBLPREFIX . 'media') {
            $has_media = true;
        }

        if ($table[0] == $TBLPREFIX . 'media_mapping') {
            $has_media_mapping = true;
        }
    }

    if (!$has_media) {
        // no point in upgrade just call the create

        require_once 'upgrade_media_db.php';

        media_db_make_current_version($TBLPREFIX);
    } else {
        $ssql = 'DESCRIBE ' . $TBLPREFIX . 'media';

        $sres = dbquery($ssql);

        $has_id = false;

        $has_indiname = false;

        $has_m_indi = false;

        while (false !== ($field = &$sres->fetchRow())) {
            if ('m_id' == $field[0]) {
                $has_id = true;
            }                // indicates version > 3x

            if ('m_indiname' == $field[0]) {
                $has_indiname = true;
            }    // indicates 2x

            if ('m_indi' == $field[0]) {
                $has_m_indi = true;
            }            // indicates < 3.3
        }

        // 3x -> < 3.3

        if ($has_id && $has_m_indi) {
            require_once 'upgrade_media_db.php';

            media_db_upgrade_3x_33($TBLPREFIX);
        }

        // 2x

        if ($has_indiname) {
            require_once 'upgrade_media_db.php';

            media_db_upgrade_2x_33($TBLPREFIX);
        }

        // this appears to be latest version

        if ($has_id && !$has_m_indi) {
            //check for the mapping table, it should have it!

            if (!$has_media_mapping) {
                // this is a serious error as we appear to have lost data

                // but for now recreate the table and warn the user that

                // they need to get a back up restored as the table has been lost

                require_once 'upgrade_media_db.php';

                media_db_create_mapping($TBLPREFIX);
            }
        }
    }
}

/**
 * Converts a block of text into a gedcom NOTE record.
 *
 * @param int $level The indent number for the NOTE record.
 * @param string  $txt   Block of text to convert.
 * @return gedrec Gedcom NOTE record.
 */
function textblock_to_note($level, $txt)
{
    $newnote = $level . " NOTE\r\n";

    $indent = $level + 1;

    $newline = $indent . ' CONC ' . $txt;

    $newlines = preg_preg_split("/\r?\n/", $newline);

    for ($k = 0, $kMax = count($newlines); $k < $kMax; $k++) {
        if ($k > 0) {
            $newlines[$k] = $indent . ' CONT ' . $newlines[$k];
        }

        if (mb_strlen($newlines[$k]) > 255) {
            while (mb_strlen($newlines[$k]) > 255) {
                $newnote .= mb_substr($newlines[$k], 0, 255) . "\r\n";

                $newlines[$k] = mb_substr($newlines[$k], 255);

                $newlines[$k] = $indent . ' CONC ' . $newlines[$k];
            }

            $newnote .= trim($newlines[$k]) . "\r\n";
        } else {
            $newnote .= trim($newlines[$k]) . "\r\n";
        }
    }

    return $newnote;
}

/**
 * Get the list of media from the database
 *
 * Searches the media table of the database for media items that
 * are associated with the currently active GEDCOM.
 *
 * The medialist that is returned contains the following elements:
 * - $media["ID"] the unique id of this media item in the table (Mxxxx)
 * - $media["GEDFILE"] the gedcom file the media item should be added to
 * - $media["FILE"] the filename of the media item
 * - $media["FORM"] the format of the item (ie JPG, PDF, etc)
 * - $media["TITL"] a title for the item, used for list display
 * - $media["NOTE"] gedcom record snippet
 * - $media["LINKED"] Flag for front end to indicate this is linked
 * - $media["INDIS"] Array of gedcom ids that this is linked to
 *
 * @return mixed A media list array.
 */
function get_db_media_links()
{
    global $GEDCOM;

    global $TBLPREFIX;

    //-- sort these so the most recently added files show first

    $medialist = [];

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media WHERE m_gedfile='$GEDCOM' ORDER BY m_id desc";

    $res = dbquery($sql);

    $ct = $res->numRows();

    while (false !== ($row = &$res->fetchRow(DB_FETCHMODE_ASSOC))) {
        $media = [];

        $media['ID'] = $row['m_id'];

        $media['XREF'] = stripslashes($row['m_media']);

        $media['GEDFILE'] = stripslashes($row['m_gedfile']);

        $media['FILE'] = stripslashes($row['m_file']);

        $media['FORM'] = stripslashes($row['m_ext']);

        $media['TITL'] = stripslashes($row['m_titl']);

        $media['NOTE'] = stripslashes($row['m_gedrec']);

        $media['LINKED'] = false;

        $media['INDIS'] = [];

        $medialist[$media['XREF']] = $media;
    }

    $sql = 'SELECT * FROM ' . $TBLPREFIX . "media_mapping WHERE m_gedfile='$GEDCOM'";

    $res = dbquery($sql);

    $ct = $res->numRows();

    while (false !== ($row = &$res->fetchRow(DB_FETCHMODE_ASSOC))) {
        $medialist[stripslashes($row['m_media'])]['INDIS'][] = stripslashes($row['m_indi']);

        $medialist[stripslashes($row['m_media'])]['LINKED'] = true;
    }

    return $medialist;
}

/**
 * Removes /./  /../  from the middle of any given path
 * User function as the php variant will expand leading ./ to full path which is not
 * required and could be security issue.
 *
 * @param string $path Filepath to check.
 * @return string Cleaned up path.
 */
function real_path($path)
{
    if ('' == $path) {
        return false;
    }

    $path = trim(preg_replace('/\\\\/', '/', (string)$path));

    if (!preg_match("/(\.\w{1,4})$/", $path)
        && !preg_match("/\?[^\\/]+$/", $path)
        && !preg_match('/\\/$/', $path)) {
        $path .= '/';
    }

    $pattern = "/^(\\/|\w:\\/|https?:\\/\\/[^\\/]+\\/)?(.*)$/i";

    preg_match_all($pattern, $path, $matches, PREG_SET_ORDER);

    $path_tok_1 = $matches[0][1];

    $path_tok_2 = $matches[0][2];

    $path_tok_2 = preg_replace(
        ['/^\\/+/', '/\\/+/'],
        ['', '/'],
        $path_tok_2
    );

    $path_parts = explode('/', $path_tok_2);

    $real_path_parts = [];

    for ($i = 0, $real_path_parts = [], $iMax = count($path_parts); $i < $iMax; $i++) {
        if ('.' == $path_parts[$i]) {
            continue;
        } elseif ('..' == $path_parts[$i]) {
            if ((isset($real_path_parts[0]) && '..' != $real_path_parts[0])
                || ('' != $path_tok_1)) {
                array_pop($real_path_parts);

                continue;
            }
        }

        $real_path_parts[] = $path_parts[$i];
    }

    return $path_tok_1 . implode('/', $real_path_parts);
}
