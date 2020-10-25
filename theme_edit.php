<?php

/**
 * Modifies the themes by means of a user friendly interface
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
 * @version    $Id: theme_edit.php,v 1.2 2006/01/09 00:46:23 skenow Exp $
 */

// -- include config file

require 'config.php';
require $PGV_BASE_DIRECTORY . $factsfile['english'];
if (file_exists($PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE])) {
    require $PGV_BASE_DIRECTORY . $factsfile[$LANGUAGE];
}

if (!isset($action)) {
    $action = '';
}
if (!isset($choose_theme)) {
    $choose_theme = '';
}

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
$uname = getUserName();
if (empty($uname)) {
    header('Location: login.php?url=theme_edit.php');

    exit;
}
$user = getUser($uname);

// -- print html header information
print_header('Theme editor');

?>
    <form name="editform" method="post" ;">
    <input type="hidden" name="oldusername" value="<?php print $uname; ?>">
    <table class="list_table, <?php print $TEXT_DIRECTION; ?>">
        <tr>
            <td class="facts_label"><?php print $pgv_lang['user_theme'];
                print_help_link('edituser_user_theme_help', 'qm'); ?></td>
            <td class="facts_value" valign="top">
                <select name="choose_theme">
                    <option value=""><?php print $pgv_lang['site_default']; ?></option>
                    <?php
                    $themes = get_theme_names();
                    foreach ($themes as $indexval => $themedir) {
                        print '<option value="' . $themedir['dir'] . '"';

                        if ($themedir['dir'] == $choose_theme) {
                            print ' selected="selected"';
                        }

                        print '>' . $themedir['name'] . "</option>\n";
                    }
                    ?>
                </select>
            </td>
        </tr>
    </table>
    <input type="submit" value="Change stylesheet">
    </form>
<?php
if (0 == mb_strlen($choose_theme)) {
                        $choose_theme = $THEME_DIR;
                    }
$output = file($choose_theme . '/style.css');
$start = false;
$empty = true;
$level = '';
foreach ($output as $l => $tag) {
    if (mb_stristr($tag, '.something') || true === $empty) {
        if (true === mb_stristr($tag, '{')) {
            $pos = mb_strpos($tag, '{');

            $level = mb_substr($tag, 0, $pos);

            $tags[$level]['id'][] = '.something {';

            $tags[$level]['names'][] = '.something';

            $tags[$level]['definitions'][] = '/*empty style to make sure that the BODY style is not ignored */';

            $tags[$level]['close'] = '}';
        }

        if (true === mb_stristr($tag, '}')) {
            $empty = false;
        }
    } else {
        if (true === mb_stristr($tag, '{') && true === mb_stristr($tag, '}')) {
            $pos = mb_strpos($tag, '{');

            $level = mb_substr($tag, 0, $pos);

            $class = mb_substr($tag, $pos + 1);

            // Continue
            // 			$items = preg_preg_split("/;/", $tag);
            //?>
            <pre><?php
            // 			print_r ($items);
            // 			exit;
            //?></pre><?php
            if (true !== mb_stristr($level, '{')) {
                $heading = $level . '{';
            }

            if (true === mb_stristr($class, '}')) {
                $class = mb_substr(trim($class), 0, -1);
            }

            $tags[$level]['id'][] = $heading;

            $tags[$level]['names'][] = $heading;

            $tags[$level]['definitions'][] = $class;

            $tags[$level]['close'] = '}';

            $level = '';

            $start = false;
        } elseif (true === $start && true !== mb_stristr($tag, '}')) {
            $tagnamepos = mb_strpos(trim($tag), ':');

            $tagname = mb_substr(trim($tag), 0, $tagnamepos);

            $tagdef = mb_substr(trim($tag), $tagnamepos + 1);

            if (';' == mb_substr($tagdef, -1)) {
                $tagdef = mb_substr($tagdef, 0, -1);
            }

            $names[] = $tagname;

            $defs[] = $tagdef;
        } elseif (mb_stristr($tag, '{')) {
            $start = true;

            $level = trim(preg_replace('/{/', '', $tag));

            $tags[$level]['id'] = $tag;
        } elseif (mb_stristr($tag, '}')) {
            $start = false;

            if (true === mb_stristr($tag, '}') && mb_strlen(trim($tag) > '1')) {
                $class = mb_substr(trim($tag), 0, -1);

                $tagnamepos = mb_strpos(trim($class), ':');

                $tagname = mb_substr(trim($class), 0, $tagnamepos);

                $tagdef = mb_substr(trim($class), $tagnamepos + 1);

                if (';' == mb_substr($tagdef, -1)) {
                    $tagdef = mb_substr($tagdef, 0, -1);
                }
            } else {
                $tagname = trim($tag);

                $tagdef = trim($tag);
            }

            $names[] = $tagname;

            $defs[] = $tagdef;

            $tags[$level]['names'] = $names;

            $tags[$level]['definitions'] = $defs;

            $tags[$level]['close'] = '}';

            $level = '';

            $names = [];

            $defs = [];
        } else {
            $level = '';

            $names = [];

            $defs = [];

            $start = false;
        }
    }
}
print '<table width="50%" class="facts_table" border="3" cellspacing="0" cellpadding="0">';
foreach ($tags as $l => $tag) {
    print '<tr><th class="label" colspan="3">' . trim($l) . '</th></tr>';

    $i = 0;

    foreach ($tag['names'] as $n => $name) {
        print "<tr><td width=\"15%\">$name</td><td width=\"10%\">" . $tag['definitions'][$n] . '</td>';

        // Loop is only entered first time array is accessed

        if ('0' == $i) {
            $t = 0;

            while (count($tag['names']) > $t) {
                if (!isset($style)) {
                    $style = 'style="';
                }

                $style .= $tag['names'][$t] . ':' . $tag['definitions'][$t] . '; ';

                $t++;
            }

            $i = 1;

            $style .= '">PhpGedView';

            // Build up third block

            $message = '<td rowspan="' . count($tag['names']) . '" valign="top" width="25%" ';

            $message .= $style . "</td></tr>\r\n";

            print $message;

            unset($style);
        } else {
            print '<td width="25%"></td></tr>';
        }
    }

    print "<tr><td><br></td><td><br></td><td><br></td></tr>\r\n";
}
print '</table>';
print_footer();
print "\n\t</div>\n</body>\n</html>";
?>
