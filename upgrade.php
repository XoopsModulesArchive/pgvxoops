<?php

/*=================================================
    Project: PhpGedView
    File: upgrade.php
    Author: Roland Dalmulder
    Comments:
        UI for upgrading PhpGedView
    Changes V0.10
        + Moved backupfolder inside the index folder
        + Recreate original folder structure when making a backup
    Changes V0.9
        + Added option to create a backup
    Changes V0.8
        + Changed language file requirements (now uses PGV language files)
        + Improved version checking
        + Added graceful exit if the download page cannot be accessed
        + Added seperate download link for tar and zip file if present
    Changes v0.7
        + Added language support
        + Added upgrade of doc folder
    Changes v0.6
        + Added comparison of the privacy files
        + Autodetect current PhpGedView location
        + Added message if there are no files to be upgraded/config.php is not changed
    Changes v0.5b
         + Updated variable declaration
          + Added handling for $action if the value is incorrect
    Changes v0.5a
        + Added support for older PHP versions for file_get_contents and variables
          that are not defined first
    Changes v0.5
        + Cosmetic changes by viewing data in tables
          + Incorporated PhpGedView looks and functions therefore
          requires PhpGedView installation
        + Fixed quite a few errors
          + Extended version checking
          + Added checking if file exists
          + Added date checking to see if file is newer
    Changes v.04a:
          + Changed layout of form by including tables
          + Added display of current version and download link
          + Updated comparison of the config.php file
    Changes v.04:
          + Added comparison of the config.php file
    Changes v.03:
          + Copy files from upgrade folder to existing folder
        + Added option to copy config file
        + Added option to copy PhpGedView
        + Optimized code
    Changes v.02
        + Added option to copy Languages
        + Added option to copy Themes
        + Added option to copy ResearchLog
    Changes v.01
        + Added copy files from old location to new location
    PhpGedView: Genealogy Viewer
    Copyright (C) 2002 to 2003  John Finlay and Others

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

===================================================*/
# $Id: upgrade.php,v 1.2 2006/01/09 00:46:23 skenow Exp $
// Version 0.20

// General requirements
$UPGRADE_VERSION = '3.1';
require('config.php');						// Included to be able to use PhpGedView functions
if (!userIsAdmin(getUserName())) {
    header('Location: login.php?url=upgrade.php');

    exit;
}
require $PGV_BASE_DIRECTORY . $pgv_language['english'];
if (isset($pgv_language[$LANGUAGE]) && (file_exists($PGV_BASE_DIRECTORY . $pgv_language[$LANGUAGE]))) {
    require $PGV_BASE_DIRECTORY . $pgv_language[$LANGUAGE];
}
require $PGV_BASE_DIRECTORY . $confighelpfile['english'];
if (file_exists($PGV_BASE_DIRECTORY . $confighelpfile[$LANGUAGE])) {
    require $PGV_BASE_DIRECTORY . $confighelpfile[$LANGUAGE];
}

print_header($pgv_lang['upgrade_util']);			// Print header of PhpGedView page

// Define variables
$_SESSION['core'] = false;			// Used for checking if core files are involved
$_SESSION['researchlog'] = false;	// Used for checking if researchlog is involved
if (empty($action)) {
    $action = '';
}		// Used by the form to validate action
$filestring = '';
$search = '';
$endposition = '';
$newversion = '';
$checkConfigured = '';
$new = [];
$notfound = [];
$configtext = '';
$_SESSION['configyes'] = false;
$_SESSION['backup'] = false;

// Check if file_get_contents() is supported
if (!function_exists('file_get_contents')) {
    function file_get_contents($filename)
    {
        $filestring = '';

        $file = @fopen($filename, 'rb');

        if ($file) {
            while (!feof($file)) {
                $filestring .= fread($file, 1024);
            }

            fclose($file);
        }

        return $filestring;
    }
}

function check_for_new_version()
{
    global $pgv_lang, $VERSION;

    // Retrieve latest version and inform user of a new download?>
<table>
<tr><td><?php print $pgv_lang['use_version']; ?></td><td><?php print $VERSION; ?></td></tr>
<?php

//Page to find current release
$homepage = 'http://sourceforge.net/project/showfiles.php?group_id=55456';

    // Open the selected page
$fd = @fopen($homepage, 'rb'); // Added @ to prevent error messages if homepage is not available
if (!$fd) {
    print '</table>';

    print '<table>';

    print '<tr><td>' . $pgv_lang['process_error'] . '</td></tr>';
} else {
    $filestring = file_get_contents($homepage); // Put contents of file into a string

    if (false !== $filestring) {
        // Finished. File can be closed.

        fclose($fd);

        // Find version

        // Data to search for

        $before_string = '15"> PhpGedView v';

        $after_string = '</a></b>';

        // Search file

        $search = mb_stristr($filestring, $before_string);

        $endposition = mb_strpos($search, $after_string);

        $newversion = mb_substr($search, mb_strlen($before_string), ($endposition - mb_strlen($before_string))); ?>
	<tr><td><?php print $pgv_lang['current_version']; ?></td><td><?php print $newversion; ?></td></tr>
	<?php
        if ($newversion > $VERSION) {
            print '<tr><td>' . $pgv_lang['upgrade_download'] . ' ';

            // Get tar file

            $location_string = '<td colspan="3"><dd>';

            $before_string = '<a href="http:';

            $after_string = '.gz?download';

            // Search file

            $search = mb_stristr($filestring, $location_string);

            $search = mb_stristr($search, $before_string);

            $endposition = (mb_strpos($search, $after_string) + mb_strlen($after_string));

            $zipversion = mb_substr($search, 0, $endposition);

            if (false !== mb_stristr($zipversion, $newversion)) {
                print $zipversion . '" target="_new">' . $pgv_lang['upgrade_tar'] . '</a> ';
            }

            // Get zip file

            $location_string = 'Source .gz';

            $before_string = '<a href="http:';

            $after_string = '.zip?download';

            // Search file

            $search = mb_stristr($filestring, $location_string);

            $search = mb_stristr($search, $before_string);

            $endposition = (mb_strpos($search, $after_string) + mb_strlen($after_string));

            $zipversion = mb_substr($search, 0, $endposition);

            if (false !== mb_strstr($zipversion, $newversion)) {
                print $zipversion . '" target="_new">' . $pgv_lang['upgrade_zip'] . '</a>';
            }

            print '</td></tr>';
        } else {
            print '</table>';

            print '<table>';

            print '<tr><td>' . $pgv_lang['latest'] . '</td></tr>';
        }
    } else {
        print '</table>';

        print '<table>';

        print '<tr><td>' . $pgv_lang['process_error'] . '</td></tr>';
    }
}

    print "</table>\n";
} //-- end check for new version

function print_upgrade_form()
{
    global $pgv_lang, $PGV_IMAGE_DIR, $PGV_IMAGES, $PHP_SELF; ?>
<script language="JavaScript" type="text/javascript">
	var helpWin;
	function helpPopup(which) {
		if ((!helpWin)||(helpWin.closed)) helpWin = window.open('editconfig_help.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
		else helpWin.location = 'editconfig_help.php?help='+which;
		return false;
	}
</script>
<form action="<?php print $PHP_SELF; ?>" method="post">
<input type="hidden" name="action" value="upgrade">
<table>
<tr><td><?php print $pgv_lang['location']; ?></td><td><input type="text" name="newphpGedView" size="50"></td></tr>
</table>
<table width="80%" border="0">
<tr><td colspan="4"><?php print $pgv_lang['include']; ?></td><td><?php print $pgv_lang['options']; ?></td></tr>
<tr><td><input type="checkbox" name="gedview"><?php print $pgv_lang['inc_phpgedview']; ?></td>
<td><input type="checkbox" name="languages"><?php print $pgv_lang['inc_languages']; ?></td>
<td><input type="checkbox" name="config"><?php print $pgv_lang['inc_config']; ?></td>
<td><input type="checkbox" name="docs"><?php print $pgv_lang['inc_docs']; ?></td>
<td><input type="checkbox" name="backup"><?php print $pgv_lang['inc_backup']; ?></td></tr>
<tr><td><input type="checkbox" name="index"><?php print $pgv_lang['inc_index']; ?></td>
<td><input type="checkbox" name="themes"><?php print $pgv_lang['inc_themes']; ?></td>
<td><input type="checkbox" name="privacy"><?php print $pgv_lang['inc_privacy']; ?></td>
<?php
if (file_exists('rs_folders.php')) {?>
	<td><input type="checkbox" name="researchlog"><?php print $pgv_lang['inc_researchlog'];
} else {
    print '<td>';
} ?></td>
<td><a href="#" onclick="return helpPopup('how_upgrade_help');"><?php print $pgv_lang['upgrade_help']; ?><b>?</b></a></td></tr>
<tr></tr>
<tr><td><button type="submit" name="submit"><?php print $pgv_lang['save']; ?></button></td></tr>
</table>
</form>
<?php
print '<img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['hline']['other'] . "\" width=\"100%\" height=\"4\" alt=\"\"><br><br>\n";
} //-- end print upgrade form

// Copy the config.php file from the old location to the new location
function copyconfig($oldphpGedView, $newphpGedView)
{
    global $pgv_lang;

    // Put config.php into array $configold

    $configold = file($oldphpGedView . 'config.php');

    $i = 0;

    $totalold[0] = '';

    foreach ($configold as $k => $inhoud) {
        // Filter out only lines starting with $

        if ('$' == mb_substr($inhoud, 0, 1)) {
            $new = [$i => $inhoud];

            $totalold = array_merge($totalold, $new);

            $i++;
        }
    }

    // Remove comments from array and put results in array $totalold

    $i = 0;

    foreach ($totalold as $k => $inhoud) {
        // put array in a string to be able to change text

        $text = $totalold[$i];

        //remove evertyhing behind the = from the textstring.

        $totalold[$i] = trim(mb_substr($text, 0, mb_strpos($text, '=')));

        // Go to the next record

        $i++;
    }

    // Put the new config.php into array $confignew

    $confignew = file($newphpGedView . 'config.php');

    $i = 0;

    $totalnew = [];

    foreach ($confignew as $k => $inhoud) {
        if ('$' == mb_substr($inhoud, 0, 1)) {
            $new = [$i => $inhoud];

            $totalnew = array_merge($totalnew, $new);

            $i++;
        }
    }

    // Remove comments from array and put results in array $totalnew

    $i = 0;

    foreach ($totalnew as $k => $inhoud) {
        // put array in a string to be able to change text

        $text = $totalnew[$i];

        //remove evertyhing behind the = from the textstring.

        $totalnew[$i] = trim(mb_substr($text, 0, mb_strpos($text, '=')));

        // Go to the next record

        $i++;
    }

    // Check for new entries in the new config.php file

    $i = 0;

    foreach ($totalnew as $indexval => $search) {
        if (!array_search($search, $totalold, true)) {
            $notfound[$i] = $confignew[array_search_bit($search, $confignew)];

            $i++;

            print '<span class="error">' . $pgv_lang['new_variable'] . " $search</span><br>";

            $_SESSION['configyes'] = true;
        }
    }

    if (true === $_SESSION['configyes']) {
        // Create a string with all new values

        foreach ($configold as $indexval => $content) {
            if ('$PGV_SESSION_TIME' == mb_substr($content, 0, 17)) {
                // Put the array into a string to be able to write it to a file

                $configtext .= $content;

                if (isset($notfound)) {
                    foreach ($notfound as $r => $content2) {
                        // Put the new variables also in the string

                        $configtext .= $content2;
                    }
                }
            } else {
                // Put the array into a string to be able to write it to a file

                if (!isset($configtext)) {
                    $configtext = '';
                }

                $configtext .= $content;
            }
        }

        $configtext = trim($configtext) . "\r\n";

        $fp = fopen($oldphpGedView . 'config.php', 'wb');

        if (!$fp) {
            print '<span class="error">' . $pgv_lang['config_open_error'] . '</span><br><br>';
        } else {
            if (!fwrite($fp, $configtext)) {
                print '<span class="error">' . $pgv_lang['pgv_config_write_error'] . '</span><br><br>';
            } else {
                print $pgv_lang['config_update_ok'] . '<br><br>';
            }

            fclose($fp);
        }
    } else {
        print $pgv_lang['config_uptodate'] . '<br><br>';
    }
}

// Function to update the privacy files
function copyprivacy($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $GEDCOMS, $REQUIRED_PRIVACY_VERSION;

    print '<p><b>' . $pgv_lang['heading_privacy'] . '</b><br><br>';

    // Put the values in a new array to be able to use a counter

    $values = array_values($GEDCOMS);

    $p = 0;

    $locations = [];

    // Put the names of the configuration files in an array

    while ($p < count($GEDCOMS)) {
        $locations[$p] = $values[$p]['privacy'];

        //-- only upgrade the privacy file if it is not the required privacy version

        if (get_privacy_file_version($locations[$p]) != $REQUIRED_PRIVACY_VERSION) {
            // Put gedcom configuration file into array $gedcomconfig

            // This is needed so we can extract the name of the privacy file

            $gedcomconfig = file($oldphpGedView . $locations[$p]);

            // Find the name of the privacy file

            $privacyfile = $locations[$p];

            print $pgv_lang['processing'] . ' ' . $locations[$p] . '<br><br>';

            // Put the new privacy into array $privacyold

            $privacyold = file($oldphpGedView . $privacyfile);

            $i = 0;

            $totalold = [];

            foreach ($privacyold as $k => $content) {
                if ('$' == mb_substr($content, 0, 1)) {
                    $new = [$i => $content];

                    $totalold = array_merge($totalold, $new);

                    $i++;
                }
            }

            // Put the new privacy into array $confignew

            $privacynew = file($newphpGedView . 'privacy.php');

            $i = 0;

            $totalnew = [];

            foreach ($privacynew as $k => $content) {
                if ('$' == mb_substr($content, 0, 1)) {
                    $new = [$i => $content];

                    $totalnew = array_merge($totalnew, $new);

                    $i++;
                }
            }

            // Look for new variables

            $i = 0;

            foreach ($totalnew as $indexval => $search) {
                if (!in_array($search, $totalold, true)) {
                    $notfound[$i] = $totalnew[array_search_bit($search, $totalnew)];

                    $i++;

                    #print "<span class=\"error\">".$pgv_lang["new_variable"]." $search</span><br>";
                }
            }

            $configtext = '';

            if (isset($notfound)) {
                // Create a string with all new values

                foreach ($privacyold as $indexval => $content) {
                    if ('$person_facts' == mb_substr($content, 0, 13)) {
                        // Put the array into a string to be able to write it to a file

                        $configtext .= $content;

                        if (isset($notfound)) {
                            foreach ($notfound as $r => $content2) {
                                // Put the new variables also in the string

                                $configtext .= $content2;
                            }
                        }
                    } else {
                        // Put the array into a string to be able to write it to a file

                        if (!isset($configtext)) {
                            $configtext = '';
                        }

                        $configtext .= $content;
                    }
                }

                $configtext = trim($configtext) . "\r\n";

                $fp = fopen($oldphpGedView . $privacyfile, 'wb');

                if (!$fp) {
                    print '<span class="error">' . str_replace('#PRIVACY_MODULE#', $privacyfile, $pgv_lang['privacy_open_error']) . '</span><br><br>';
                } else {
                    if (!fwrite($fp, $configtext)) {
                        print '<span class="error">' . str_replace('#PRIVACY_MODULE#', $privacyfile, $pgv_lang['privacy_write_error']) . '</span><br><br>';
                    } else {
                        print str_replace('#PRIVACY_MODULE#', $privacyfile, $pgv_lang['privacy_update_ok']) . '<br><br>';
                    }

                    fclose($fp);
                }
            } else {
                print str_replace('#PRIVACY_MODULE#', $privacyfile, $pgv_lang['privacy_uptodate']) . '<br><br>';
            }

            $p++;
        }
    }
}

// Copy the phpGedview directory (core files)
function copygedview($oldphpGedView, $newphpGedView)
{
    global $pgv_lang;

    $oldfolder = $oldphpGedView;

    $newfolder = $newphpGedView;

    $_SESSION['core'] = true;

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_phpgedview'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }

    $_SESSION['core'] = false;
}

function copyimages($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $PGV_IMAGE_DIR, $backupfolder;

    $oldfolder = $oldphpGedView . $PGV_IMAGE_DIR . '/';

    $newfolder = $newphpGedView . $PGV_IMAGE_DIR . '/';

    $location = $PGV_IMAGE_DIR;

    if (true === $_SESSION['backup']) {
        if (!file_exists($oldphpGedView . $backupfolder)) {
            mkdir($oldphpGedView . $backupfolder);
        }
    }

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_image'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder, $location);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }
}

// Copy the index directory
function copyindex($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $backupfolder;

    $oldfolder = $oldphpGedView . 'index/';

    $newfolder = $newphpGedView . 'index/';

    $location = 'index/';

    if (true === $_SESSION['backup']) {
        if (!file_exists($oldphpGedView . $backupfolder . $location)) {
            mkdir($oldphpGedView . $backupfolder . $location);
        }
    }

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_index'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder, $location);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }
}

// Copy the language files
function copylanguages($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $backupfolder;

    $oldfolder = $oldphpGedView . 'languages/';

    $newfolder = $newphpGedView . 'languages/';

    $location = 'languages/';

    if (true === $_SESSION['backup']) {
        if (!file_exists($oldphpGedView . $backupfolder . $location)) {
            mkdir($oldphpGedView . $backupfolder . $location);
        }
    }

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_language'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder, $location);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }
}

// Copy the theme files
function copythemes($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $backupfolder;

    $oldfolder = $oldphpGedView . 'themes/';

    $newfolder = $newphpGedView . 'themes/';

    $location = 'themes/';

    if (true === $_SESSION['backup']) {
        if (!file_exists($oldphpGedView . $backupfolder . $location)) {
            mkdir($oldphpGedView . $backupfolder . $location);
        }
    }

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_theme'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder, $location);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }
}

// Copy the documentation files
function copydocs($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $backupfolder;

    $oldfolder = $oldphpGedView . 'docs/';

    $newfolder = $newphpGedView . 'docs/';

    $location = 'docs/';

    if (true === $_SESSION['backup']) {
        if (!file_exists($oldphpGedView . $backupfolder . $location)) {
            mkdir($oldphpGedView . $backupfolder . $location);
        }
    }

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_docs'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder, $location);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }
}

// Copy the Research Log files
function copyresearchlog($oldphpGedView, $newphpGedView)
{
    global $pgv_lang, $PGV_IMAGE_DIR,$backupfolder;

    $_SESSION['researchlog'] = true;

    $_SESSION['core'] = true;

    $_SESSION['copyyes'] = false;

    // Copy the Research Log Files

    $oldfolder = $oldphpGedView;

    $newfolder = $newphpGedView;

    print '<p><b>' . $pgv_lang['heading_researchlog'] . '</b><br><br>';

    if (file_exists($oldfolder . $PGV_IMAGE_DIR . '/folder.gif')) {
        if (filemtime($newfolder . $PGV_IMAGE_DIR . '/folder.gif') > filemtime($oldfolder . $PGV_IMAGE_DIR . '/folder.gif')) {
            if (true === $_SESSION['backup']) {
                if (file_exists($oldfolder . $PGV_IMAGE_DIR . '/folder.gif')) {
                    if (copy($oldfolder . $PGV_IMAGE_DIR . '/folder.gif', $oldphpGedView . $backupfolder . $file)) {
                        print "$oldfolder$file " . $pgv_lang['backup_copied_success'] . '<br>';
                    }
                }
            }

            if (copy($newfolder . $PGV_IMAGE_DIR . '/folder.gif', $oldfolder . $PGV_IMAGE_DIR . '/folder.gif')) {
                print 'folder.gif ' . $pgv_lang['copied_success'] . '<br>';
            }
        }
    } else {
        if (true === $_SESSION['backup']) {
            if (file_exists($oldfolder . $PGV_IMAGE_DIR . '/folder.gif')) {
                if (copy($oldfolder . $PGV_IMAGE_DIR . '/folder.gif', $oldphpGedView . $backupfolder . $file)) {
                    print "$oldfolder$file " . $pgv_lang['backup_copied_success'] . '<br>';
                }
            }
        }

        if (copy($newfolder . $PGV_IMAGE_DIR . '/folder.gif', $oldfolder . $PGV_IMAGE_DIR . '/folder.gif')) {
            print 'folder.gif ' . $pgv_lang['copied_success'] . '<br>';
        }

        $_SESSION['copyyes'] = true;
    }

    copygeneral($newfolder, $oldfolder);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }

    // Copy the Research Log language files

    $oldfolder = $oldphpGedView . 'languages/';

    $newfolder = $newphpGedView . 'languages/';

    $_SESSION['copyyes'] = false;

    print '<p><b>' . $pgv_lang['heading_researchloglang'] . '</b><br><br>';

    copygeneral($newfolder, $oldfolder);

    if (false === $_SESSION['copyyes']) {
        print $pgv_lang['no_upgrade'];
    }

    // Restore variables

    $_SESSION['researchlog'] = false;

    $_SESSION['core'] = false;
}

// The general copy function
function copygeneral($newfolder, $oldfolder, $location = '')
{
    global $pgv_lang, $oldphpGedView, $backupfolder;

    // Check if the folder which is being copied exists in the current phpGedView setup

    // If not, create that folder.

    if (!file_exists($oldfolder)) {
        mkdir($oldfolder);
    }

    // The actual copying is done here

    if ($handle = opendir($newfolder)) {
        while ($file = readdir($handle)) {
            if ('.' != $file && '..' != $file) {
                chdir($newfolder);

                if (true === is_dir($file)) {
                    if (true !== $_SESSION['core']) {
                        chdir($newfolder . $file);

                        if (!file_exists($oldfolder . $file)) { 	// Check if the subfolder exists
                      mkdir($oldfolder . $file);						// If not, then create it.
                    chdir($oldfolder . $file);
                        } else {
                            chdir($oldfolder . $file);
                        }

                        copysub($newfolder . $file . '/', $oldfolder . $file . '/');
                    }
                } else {
                    if ('config.php' !== $file && 'authenticate.php' !== $file && 'privacy.php' !== $file) {
                        if ('rs' !== mb_substr($file, 0, 2) && true !== $_SESSION['researchlog']) {
                            if (file_exists($oldfolder . $file)) {
                                if (filemtime($newfolder . $file) > filemtime($oldfolder . $file)) {
                                    print '<span class="name1" >' . $file . '<br></span>';

                                    if (true === $_SESSION['backup']) {
                                        if (file_exists($oldfolder . $file)) {
                                            if (copy($oldfolder . $file, $oldphpGedView . $backupfolder . $location . $file)) {
                                                print $pgv_lang['backup_copied_success'] . '<br>';
                                            }
                                        }
                                    }

                                    if (copy($newfolder . $file, $oldfolder . $file)) {
                                        print $pgv_lang['copied_success'] . '<br>';

                                        $_SESSION['copyyes'] = true;
                                    }
                                }
                            } else {
                                print '<span class="name1" >' . $file . '<br></span>';

                                if (true === $_SESSION['backup']) {
                                    if (file_exists($oldfolder . $file)) {
                                        if (copy($oldfolder . $file, $oldphpGedView . $backupfolder . $location . $file)) {
                                            print $pgv_lang['backup_copied_success'] . '<br>';
                                        }
                                    }
                                }

                                if (copy($newfolder . $file, $oldfolder . $file)) {
                                    print $pgv_lang['copied_success'] . '<br>';

                                    $_SESSION['copyyes'] = true;
                                }
                            }
                        }

                        if ('rs' == mb_substr($file, 0, 2) && true === $_SESSION['researchlog']) {
                            if (file_exists($oldfolder . $file)) {
                                if (filemtime($newfolder . $file) > filemtime($oldfolder . $file)) {
                                    print '<span class="name1" >' . $file . '<br></span>';

                                    if (true === $_SESSION['backup']) {
                                        if (file_exists($oldfolder . $file)) {
                                            if (copy($oldfolder . $file, $oldphpGedView . $backupfolder . $location . $file)) {
                                                print $pgv_lang['backup_copied_success'] . '<br>';
                                            }
                                        }
                                    }

                                    if (copy($newfolder . $file, $oldfolder . $file)) {
                                        print $pgv_lang['copied_success'] . '<br>';

                                        $_SESSION['copyyes'] = true;
                                    }
                                }
                            } else {
                                print '<span class="name1" >' . $file . '<br></span>';

                                if (true === $_SESSION['backup']) {
                                    if (file_exists($oldfolder . $file)) {
                                        if (copy($oldfolder . $file, $oldphpGedView . $backupfolder . $location . $file)) {
                                            print $pgv_lang['backup_copied_success'] . '<br>';
                                        }
                                    }
                                }

                                if (copy($newfolder . $file, $oldfolder . $file)) {
                                    print $pgv_lang['copied_success'] . '<br>';

                                    $_SESSION['copyyes'] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        closedir($handle);
    }
}
// A subfolder copy function
function copysub($newfolder, $oldfolder)
{
    global $pgv_lang, $oldphpGedView,$backupfolder, $location;

    if ($handle = opendir($newfolder)) {
        while ($file = readdir($handle)) {
            if ('.' != $file && '..' != $file) {
                chdir($newfolder);

                if (true === is_dir($file)) {
                    chdir($newfolder);

                    if (!file_exists($oldfolder . $file)) {
                        mkdir($oldfolder . $file);

                        print '<b>' . $pgv_lang['folder_created'] . " $oldfolder$file</b><br>";

                        chdir($oldfolder . $file);
                    }

                    copysub($newfolder . $file . '/', $oldfolder . $file . '/');
                } else {
                    if (file_exists($oldfolder . $file)) {
                        if (filemtime($newfolder . $file) > filemtime($oldfolder . $file)) {
                            if (true === $_SESSION['backup']) {
                                if (file_exists($oldfolder . $file)) {
                                    if (copy($oldfolder . $file, $oldphpGedView . $backupfolder . $location . $file)) {
                                        print "$file " . $pgv_lang['backup_copied_success'] . '<br>';
                                    }
                                }
                            }

                            if (copy($newfolder . $file, $oldfolder . $file)) {
                                print "$file " . $pgv_lang['copied_success'] . '<br>';

                                $_SESSION['copyyes'] = true;
                            }
                        }
                    } else {
                        if (true === $_SESSION['backup']) {
                            if (file_exists($oldfolder . $file)) {
                                if (copy($oldfolder . $file, $oldphpGedView . $backupfolder . $location . $file)) {
                                    print "$file " . $pgv_lang['backup_copied_success'] . '<br>';
                                }
                            }
                        }

                        if (copy($newfolder . $file, $oldfolder . $file)) {
                            print "$file " . $pgv_lang['copied_success'] . '<br>';

                            $_SESSION['copyyes'] = true;
                        }
                    }
                }
            }
        }

        closedir($handle);
    }
}

// Function to search array
function array_search_bit($search, $array_in)
{
    foreach ($array_in as $key => $value) {
        if (false !== mb_strpos($value, $search)) {
            return $key;
        }
    }

    return false;
}

// Clean up all variables
function cleanup()
{
    $_GLOBALS = [];
}

if ('upgrade' == $action) {
    $oldphpGedView = getcwd();

    $newphpGedView = stripslashes($_POST['newphpGedView']);

    chdir($oldphpGedView);

    if (mb_strlen($oldphpGedView) > 0 && (mb_strlen($newphpGedView) > 0)) {
        // Check if the last character is a slash

        // if not, then add one

        if (!('\\' == mb_substr($oldphpGedView, -1)) || ('/' == mb_substr($oldphpGedView, -1))) {
            $oldphpGedView .= '/';
        }

        if (!('\\' == mb_substr($newphpGedView, -1)) || ('/' == mb_substr($newphpGedView, -1))) {
            $newphpGedView .= '/';
        }

        // Get contents of the config.php file

        // Open the file config.php

        if (true === is_readable($oldphpGedView . 'config.php')) {
            $fd = fopen($oldphpGedView . 'config.php', 'rb');

            $filestring = file_get_contents($oldphpGedView . 'config.php');

            // Finished. File can be closed.

            fclose($fd);
        } else {
            print $pgv_lang['cannot_read'] . $oldphpGedView . 'config.php';
        }

        // Check if user wants a backup of files

        if (true === isset($_POST['backup'])) {
            $_SESSION['backup'] = true;

            $oldfolder = $oldphpGedView;

            $backupfolder = $INDEX_DIRECTORY . '/backup-' . date('Ymd') . '/';

            if (!file_exists($oldfolder . $backupfolder)) {
                mkdir($oldfolder . $backupfolder);
            }
        }

        // Check if this is a configured version of phpGedView

        // If not configured, it is a fresh install and should not be copied

        $checkConfigured = mb_substr($filestring, (mb_strpos($filestring, 'CONFIGURED') + 13), 5);

        if (true === mb_stristr($checkConfigured, 'true')) {
            $checkConfigured = true;
        } else {
            $checkConfigured = false;
        }

        if (false === $checkConfigured) {
            print $pgv_lang['not_configured'];

            exit;
        }

        if (true === $checkConfigured) {
            if (true === isset($_POST['config'])) {
                copyconfig($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['gedview'])) {
                copygedview($oldphpGedView, $newphpGedView);

                copyimages($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['index'])) {
                copyindex($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['docs'])) {
                copydocs($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['languages'])) {
                copylanguages($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['themes'])) {
                copythemes($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['privacy'])) {
                copyprivacy($oldphpGedView, $newphpGedView);
            }

            if (true === isset($_POST['researchlog'])) {
                copyresearchlog($oldphpGedView, $newphpGedView);
            }

            cleanup();
        }
    } else {
        print $pgv_lang['location_upgrade'];
    }
} else {
    if ($UPGRADE_VERSION != $CONFIG_VERSION) {
        //-- Upgrading 2.65 to 3.x

        //- 1. write a new config.php file

        //- 2. move authenticate.php to index directory

        if ($CONFIG_VERSION < 3) {
            //-- write new gedcoms.php file

            store_gedcoms();

            //----- state of new config text

            $configtext = '<?php
//-- this config file was generated by the automated upgrade script
if (preg_match("/\Wconfig.php/", $_SERVER["PHP_SELF"])>0) {
	print "Got your hand caught in the cookie jar.";
	exit;
}

$PGV_BASE_DIRECTORY = "";						//-- path to phpGedView (Only needed when running as phpGedView from another php program such as postNuke, otherwise leave it blank)
$PGV_DATABASE = "' . $PGV_DATABASE . '";						//-- which database is being used, file indexes or mysql
$DBHOST = "' . $DBHOST . '";							//-- Host where MySQL database is kept
$DBUSER = "' . $DBUSER . '";								//-- MySQL database User Name
$DBPASS = "' . $DBPASS . '";							//-- MySQL database User Password
$DBNAME = "' . $DBNAME . '";							//-- The MySQL database name where you want PHPGedView to build its tables
$TBLPREFIX = "' . $TBLPREFIX . '";							//-- prefix to include on table names
$INDEX_DIRECTORY = "' . $INDEX_DIRECTORY . '";					//-- Readable and Writeable Directory to store index files (include the trailing "/")
$AUTHENTICATION_MODULE = "' . $AUTHENTICATION_MODULE . '";	//-- File that contains authentication functions
$PGV_STORE_MESSAGES = true;						//-- allow messages sent to users to be stored in the PGV system
';

            if ($USE_REGISTRATION_MODULE) {
                $configtext .= '$USE_REGISTRATION_MODULE = true;				//-- turn on the user self registration module
';
            } else {
                $configtext .= '$USE_REGISTRATION_MODULE = false;				//-- turn on the user self registration module
';
            }

            if ($ALLOW_USER_THEMES) {
                $configtext .= '$ALLOW_USER_THEMES = true;						//-- Allow user to set their own theme
';
            } else {
                $configtext .= '$ALLOW_USER_THEMES = false;						//-- Allow user to set their own theme
';
            }

            if ($ALLOW_CHANGE_GEDCOM) {
                $configtext .= '$ALLOW_CHANGE_GEDCOM = true;						//-- Allow user to set their own theme
';
            } else {
                $configtext .= '$ALLOW_CHANGE_GEDCOM = false;						//-- Allow user to set their own theme
';
            }

            $configtext .= '$LOGFILE_CREATE = "' . $LOGFILE_CREATE . '";					//-- set how often new log files are created, "none" turns logs off, "daily", "weekly", "monthly", "yearly"
$PGV_SESSION_SAVE_PATH = "' . $PGV_SESSION_SAVE_PATH . '";					//-- Path to save PHP session Files -- DO NOT MODIFY unless you know what you are doing
												//-- leaving it blank will use the default path for your php configuration as found in php.ini
$PGV_SESSION_TIME = "' . $PGV_SESSION_TIME . '";						//-- number of seconds to wait before an inactive session times out
$SERVER_URL = "' . $SERVER_URL . '";								//-- the URL used to access this server
$PGV_MEMORY_LIMIT = "' . $PGV_MEMORY_LIMIT . '";						//-- the maximum amount of memory that PGV should be allowed to consume
$CONFIG_VERSION = "3.1";						//-- the version this config file goes to

$CONFIGURED = true;
require_once $PGV_BASE_DIRECTORY."session.php";
?>';

            $fp = fopen('config.php', 'wb');

            if (!$fp) {
                print '<span class="error">' . $pgv_lang['pgv_config_write_error'] . "<br></span>\n";

                print_footer();

                exit;
            }

            fwrite($fp, $configtext);

            fclose($fp);

            print $pgv_lang['config_update_ok'] . '<br><br>';

            if (file_exists('authenticate.php')) {
                rename('authenticate.php', $INDEX_DIRECTORY . 'authenticate.php');
            }

            copyprivacy('./', './');
        }

        //-- Upgrading 3.0 to 3.1

        //- 1. if mysql, call setup_database();

        //- 2. update config.php $CONFIG_VERSION

        else {
            if ('index' != $PGV_DATABASE) {
                setup_database();
            }

            $configtext = implode('', file('config.php'));

            $configtext = preg_replace('/\$CONFIG_VERSION\s*=\s*".*";/', '$CONFIG_VERSION = "3.1";', $configtext);

            $fp = fopen('config.php', 'wb');

            if (!$fp) {
                print '<span class="error">' . $pgv_lang['pgv_config_write_error'] . "<br></span>\n";
            } else {
                fwrite($fp, $configtext);

                fclose($fp);

                print $pgv_lang['config_update_ok'] . '<br><br>';
            }

            copyprivacy('./', './');
        }

        print '<b>' . $pgv_lang['upgrade_completed'] . '</b><br>';

        print '<br><a href="index.php">' . $pgv_lang['start_using_upgrad'] . " $UPGRADE_VERSION</a><br><br>";
    } else {
        check_for_new_version();

        print_upgrade_form();
    }
}

print '<br><br><img src="' . $PGV_IMAGE_DIR . '/' . $PGV_IMAGES['hline']['other'] . "\" width=\"100%\" height=\"4\" alt=\"\"><br><br>\n";
print_footer();		// Print footer of PhpGedView page

?>
