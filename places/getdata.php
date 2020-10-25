<?php

/**
 * GET data from a server file to populate a contextual place list
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
 * @version    $Id: getdata.php,v 1.1 2005/10/07 18:12:20 skenow Exp $
 * @see        functions_places.php
 */
$field = @$_GET['field'];
//print $field."|";
$ctry = @$_GET['ctry'];
$stae = @$_GET['stae'];
$cnty = @$_GET['cnty'];
$city = @$_GET['city'];

if (empty($ctry)) {
    return;
}
$filename = '';
if ('PLAC_STAE' == $field) {
    $filename = $ctry . '/' . $ctry;
}
if ('PLAC_CNTY' == $field) {
    $filename = $ctry . '/' . $ctry . '_' . $stae;
}
if ('PLAC_CITY' == $field) {
    $filename = $ctry . '/' . $ctry . '_' . $stae . '_' . $cnty;
}
if (empty($filename)) {
    return;
}
$filename .= '.txt';
//print $filename."|";

/** examples:
 * list of USA states => USA/USA.txt
 * list of Rhode Island counties => USA/USA_Rhode-Island.txt
 **/
if (!file_exists($filename)) {
    return;
}

$data = file_get_contents($filename);
$data = str_replace("\r", '', $data);
$data = preg_replace("/<!--.*?-->\n/is", '', $data);
$data = str_replace("\n", '|', $data);
$data = trim($data, '|');
print $data;
