<?php

/**
 * MyGedView page allows a logged in user the abilty
 * to keep bookmarks, see a list of upcoming events, etc.
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
 * @version    $Id: index.php,v 1.2 2006/01/09 00:46:23 skenow Exp $
 */
if (isset($_REQUEST['mod'])) {
    require_once __DIR__ . '/module.php';

    exit;
}

require 'config.php';

if (!isset($CONFIGURED)) {
    print 'Unable to include the config.php file.  Make sure that . is in your PHP include path in the php.ini file.';

    exit;
}
require $PGV_BASE_DIRECTORY . $factsfile['english'];
if (file_exists($PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE])) {
    require $PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE];
}

/**
 * Block definition array
 *
 * The following block definition array defines the
 * blocks that can be used to customize the portals
 * their names and the function to call them
 * "name" is the name of the block in the lists
 * "descr" is the name of a $pgv_lang variable to describe this block
 * - eg: "whatever" here means that $pgv_lang["whatever"] describes this block
 * "type" the options are "user" or "gedcom" or undefined
 * - The type determines which lists the block is available in.
 * - Leaving the type undefined allows it to be on both the user and gedcom portal
 * @global $PGV_BLOCKS
 */
$PGV_BLOCKS = [];

//-- load all of the blocks
$d = dir('blocks');
while (false !== ($entry = $d->read())) {
    if ('.php' == mb_strstr($entry, '.')) {
        require_once 'blocks/' . $entry;
    }
}
$d->close();

if (isset($_SESSION['timediff'])) {
    $time = time() - $_SESSION['timediff'];
} else {
    $time = time();
}
$day = date('j', $time);
$month = date('M', $time);
$year = date('Y', $time);

if (!isset($action)) {
    $action = '';
}

//-- make sure that they have user status before they can use this page
//-- otherwise have them login again
$uname = getUserName();
if (empty($uname)) {
    if (!empty($command)) {
        if ('user' == $command) {
            header('Location: login.php?help_message=mygedview_login_help&url=' . urlencode('index.php?command=user'));

            exit;
        }
    }

    $command = 'gedcom';
} else {
    $user = getUser($uname);
}

if (empty($command)) {
    $command = 'user';
}

if (!empty($uname)) {
    //-- add favorites action

    if (('addfav' == $action) && (!empty($gid))) {
        $gid = mb_strtoupper($gid);

        $indirec = find_person_record($gid);

        if ($indirec) {
            $favorite = [];

            if ('user' == $command) {
                $favorite['username'] = $uname;
            } else {
                $favorite['username'] = $GEDCOM;
            }

            $favorite['gid'] = $gid;

            $favorite['type'] = 'INDI';

            $favorite['file'] = $GEDCOM;

            addFavorite($favorite);
        }
    }

    if (('deletefav' == $action) && (isset($fv_id))) {
        deleteFavorite($fv_id);
    } elseif ('deletemessage' == $action) {
        if (isset($message_id)) {
            if (!is_array($message_id)) {
                deleteMessage($message_id);
            } else {
                foreach ($message_id as $indexval => $mid) {
                    if (isset($mid)) {
                        deleteMessage($mid);
                    }
                }
            }
        }
    } elseif (('deletenews' == $action) && (isset($news_id))) {
        deleteNews($news_id);
    }
}

//-- get the blocks list
if ('user' == $command) {
    $ublocks = getBlocks($uname);

    if ((0 == count($ublocks['main'])) and (0 == count($ublocks['right']))) {
        $ublocks['main'][] = ['print_todays_events', ''];

        $ublocks['main'][] = ['print_user_messages', ''];

        $ublocks['main'][] = ['print_user_favorites', ''];

        $ublocks['right'][] = ['print_welcome_block', ''];

        $ublocks['right'][] = ['print_random_media', ''];

        $ublocks['right'][] = ['print_upcoming_events', ''];

        $ublocks['right'][] = ['print_logged_in_users', ''];
    }
} else {
    $ublocks = getBlocks($GEDCOM);

    if ((0 == count($ublocks['main'])) and (0 == count($ublocks['right']))) {
        $ublocks['main'][] = ['print_gedcom_stats', ''];

        $ublocks['main'][] = ['print_gedcom_news', ''];

        $ublocks['main'][] = ['print_gedcom_favorites', ''];

        $ublocks['main'][] = ['review_changes_block', ''];

        $ublocks['right'][] = ['print_gedcom_block', ''];

        $ublocks['right'][] = ['print_random_media', ''];

        $ublocks['right'][] = ['print_todays_events', ''];

        $ublocks['right'][] = ['print_logged_in_users', ''];
    }
}

//-- Set some behaviour controls that depend on which blocks are selected
$welcome_block_present = false;
$gedcom_block_present = false;
$top10_block_present = false;
$login_block_present = false;
foreach ($ublocks['right'] as $block) {
    if ('print_welcome_block' == $block[0]) {
        $welcome_block_present = true;
    }

    if ('print_gedcom_block' == $block[0]) {
        $gedcom_block_present = true;
    }

    if ('print_block_name_top10' == $block[0]) {
        $top10_block_present = true;
    }

    if ('print_login_block' == $block[0]) {
        $login_block_present = true;
    }
}
foreach ($ublocks['main'] as $block) {
    if ('print_welcome_block' == $block[0]) {
        $welcome_block_present = true;
    }

    if ('print_gedcom_block' == $block[0]) {
        $gedcom_block_present = true;
    }

    if ('print_block_name_top10' == $block[0]) {
        $top10_block_present = true;
    }

    if ('print_login_block' == $block[0]) {
        $login_block_present = true;
    }
}

if ('user' == $command) {
    $helpindex = 'index_myged_help';

    print_header($pgv_lang['mygedview']);
} else {
    print_header($GEDCOMS[$GEDCOM]['title']);
}
?>
    <script language="JavaScript" type="text/javascript">
        <!--
        function refreshpage() {
            window.location = 'index.php?command=<?php print $command; ?>';
        }

        function addnews(uname) {
            window.open('editnews.php?uname=' + uname, '', 'top=50,left=50,width=500,height=400,resizable=1,scrollbars=1');
        }

        function editnews(news_id) {
            window.open('editnews.php?news_id=' + news_id, '', 'top=50,left=50,width=500,height=400,resizable=1,scrollbars=1');
        }

        //-->
    </script>
<?php
//-- start of main content section
print '<table width="100%"><tr><td>';        // This is needed so that page footers print in the right place
if ('user' == $command) {
    print '<div align="center" style="width: 99%;">';

    print '<h1>' . $pgv_lang['mygedview'] . '</h1>';

    print $pgv_lang['mygedview_desc'];

    print "<br><br></div>\n";
}
if (0 != count($ublocks['main'])) {
    if (0 != count($ublocks['right'])) {
        print "\t<div id=\"index_main_blocks\">\n";
    } else {
        print "\t<div id=\"index_full_blocks\">\n";
    }

    foreach ($ublocks['main'] as $bindex => $block) {
        if (isset($DEBUG) && (true === $DEBUG)) {
            print_execution_stats();
        }

        if (function_exists($block[0])) {
            eval($block[0] . "(false, \$block[1], \"main\", $bindex);");
        }
    }

    print "</div>\n";
}
//-- end of main content section

//-- start of blocks section
if (0 != count($ublocks['right'])) {
    if (0 != count($ublocks['main'])) {
        print "\t<div id=\"index_small_blocks\">\n";
    } else {
        print "\t<div id=\"index_full_blocks\">\n";
    }

    foreach ($ublocks['right'] as $bindex => $block) {
        if (isset($DEBUG) && (true === $DEBUG)) {
            print_execution_stats();
        }

        if (function_exists($block[0])) {
            eval($block[0] . "(true, \$block[1], \"right\", $bindex);");
        }
    }

    print "\t</div>\n";
}
//-- end of blocks section

print '</td></tr></table><br>';        // Close off that table

if (('user' == $command) and (!$welcome_block_present)) {
    print '<div align="center" style="width: 99%;">';

    print "<a href=\"#\" onclick=\"window.open('index_edit.php?name=" . getUserName() . "&amp;command=user', '', 'top=50,left=50,width=600,height=550,scrollbars=1,resizable=1');\">" . $pgv_lang['customize_page'] . "</a>\n";

    print_help_link('mygedview_customize_help', 'qm');

    print '</div>';
}
if (('gedcom' == $command) and (!$gedcom_block_present)) {
    if (userIsAdmin(getUserName())) {
        print '<div align="center" style="width: 99%;">';

        print "<a href=\"#\" onclick=\"window.open('index_edit.php?name=$GEDCOM&amp;command=gedcom', '', 'top=50,left=50,width=600,height=550,scrollbars=1,resizable=1');\">" . $pgv_lang['customize_gedcom_page'] . "</a>\n";

        print '</div>';
    }
}

print_footer();
?>
