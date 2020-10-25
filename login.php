<?php

/*=================================================
    Project: phpGedView
    File: login.php
    Author: John Finlay
    Comments:
        Ask for usename and password

    phpGedView: Genealogy Viewer
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
2004-01-11 Added ? for Help Pop-ups by Jans B. Luder
2004-01-12 Added routine for different Button text in Authent. mode
===================================================*/

# $Id: login.php,v 1.1 2005/10/07 18:08:01 skenow Exp $

use Xmf\Request;

require 'config.php';
$message = '';
if (!isset($action)) {
    $action = '';

    $username = '';

    $password = '';
}

if (!isset($type)) {
    $type = 'full';
}

if ('login' == $action) {
    $username = $_POST['username'] ?? '';

    $password = $_POST['password'] ?? '';

    $remember = $_POST['remember'] ?? 'no';

    $auth = authenticateUser($username, $password);

    if ($auth) {
        if (!empty($_POST['usertime'])) {
            $_SESSION['usertime'] = @strtotime($_POST['usertime']);
        } else {
            $_SESSION['usertime'] = time();
        }

        $_SESSION['timediff'] = time() - $_SESSION['usertime'];

        // START - added by Kurt for support of the userlanguage

        $MyUserName = getUserName();

        $MyUser = $users[$MyUserName];

        if (isset($MyUser['language'])) {
            if (isset($_SESSION['CLANGUAGE'])) {
                $_SESSION['CLANGUAGE'] = $MyUser['language'];
            } elseif (isset($HTTP_SESSION_VARS['CLANGUAGE'])) {
                $HTTP_SESSION_VARS['CLANGUAGE'] = $MyUser['language'];
            }
        }

        // END - added by Kurt for support of the userlanguage

        session_write_close();

        $url = preg_replace('/logout=1/', '', $url);

        if ('yes' == $remember) {
            setcookie('pgv_rem', $username, time() + 60 * 60 * 24 * 7);
        } else {
            setcookie('pgv_rem', '', time() - 60 * 60 * 24 * 7);
        }

        header("Location: $url");

        exit;
    }

    $message = $pgv_lang['no_login'];
} else {
    $tSERVER_URL = preg_replace(["'https?://'", "'www.'", "'/$'"], ['', '', ''], $SERVER_URL);

    $tLOGIN_URL = preg_replace(["'https?://'", "'www.'", "'/$'"], ['', '', ''], $LOGIN_URL);

    if (empty($url)) {
        if ((isset(Request::getString('HTTP_REFERER', '', 'SERVER'))) && ((false !== mb_stristr(Request::getString('HTTP_REFERER', '', 'SERVER'), $tSERVER_URL)) || (false !== mb_stristr(Request::getString('HTTP_REFERER', '', 'SERVER'), $tLOGIN_URL)))) {
            $url = basename(Request::getString('HTTP_REFERER', '', 'SERVER'));

            if (false === mb_stristr($url, '.php')) {
                $url = "index.php?command=gedcom&ged=$GEDCOM";
            }
        } else {
            if (isset($url)) {
                if (false !== mb_stristr($url, $SERVER_URL)) {
                    $url = $SERVER_URL;
                }
            } else {
                $url = $SERVER_URL;
            }
        }
    } elseif (mb_stristr($url, 'index.php') && !mb_stristr($url, 'command=')) {
        $url .= '&command=gedcom';
    }
}

if ('full' == $type) {
    print_header($pgv_lang['login_head']);
} else {
    print_simple_header($pgv_lang['login_head']);
}
print "<div align=\"center\">\n";
print '<h2>' . $pgv_lang['login_head'] . '</h2>';
if ($_SESSION['cookie_login']) {
    print "<div style=\"width:70%\" align=\"left\">\n";

    print_text('cookie_login_help');

    print "</div><br><br>\n";
}
if ($REQUIRE_AUTHENTICATION) {
    require $PGV_BASE_DIRECTORY . $helptextfile['english'];

    if (file_exists($PGV_BASE_DIRECTORY . $helptextfile[$LANGUAGE])) {
        require $PGV_BASE_DIRECTORY . $helptextfile[$LANGUAGE];
    }

    print "<table width=\"60%\" class=\"$TEXT_DIRECTION\"><tr><td>";

    if (empty($help_message) || !isset($help_message)) {
        if (!empty($GEDCOM)) {
            require $INDEX_DIRECTORY . $GEDCOM . '_conf.php';
        }

        switch ($WELCOME_TEXT_AUTH_MODE) {
            case '1':
                $help_message = 'welcome_text_auth_mode_1';
                print_text($help_message);
                break;
            case '2':
                $help_message = 'welcome_text_auth_mode_2';
                print_text($help_message);
                break;
            case '3':
                $help_message = 'welcome_text_auth_mode_3';
                print_text($help_message);
                break;
            case '4':
                if ('true' == $WELCOME_TEXT_CUST_HEAD) {
                    $help_message = 'welcome_text_cust_head';

                    print_text($help_message);
                }
                print $WELCOME_TEXT_AUTH_MODE_4;
                break;
        }

        print "</td></tr></table><br><br>\n";
    } else {
        print_text($help_message);
    }
} else {
    if (!empty($help_message) || isset($help_message)) {
        require $PGV_BASE_DIRECTORY . $helptextfile['english'];

        if (file_exists($PGV_BASE_DIRECTORY . $helptextfile[$LANGUAGE])) {
            require $PGV_BASE_DIRECTORY . $helptextfile[$LANGUAGE];
        }

        print "<table width=\"60%\" class=\"$TEXT_DIRECTION\"><tr><td>";

        print_text($help_message);

        print "</td></tr></table><br><br>\n";
    }
}

?>
    <form name="loginform" method="post" action="<?php print $LOGIN_URL; ?>" onsubmit="t = new Date(); document.loginform.usertime.value=t.getFullYear()+'-'+(t.getMonth()+1)+'-'+t.getDate()+' '+t.getHours()+':'+t.getMinutes()+':'+t.getSeconds(); return true;">
        <?php $i = 0; ?>
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="url" value="<?php print $url; ?>">
        <input type="hidden" name="ged" value="<?php if (isset($ged)) {
    print $ged;
} else {
    print $GEDCOM;
} ?>">
        <input type="hidden" name="pid" value="<?php if (isset($pid)) {
    print $pid;
} ?>">
        <input type="hidden" name="type" value="<?php print $type; ?>">
        <input type="hidden" name="usertime" value="">
        <span class="error"><b><?php print $message ?></b></span>
        <!--table-->
        <table class="center">
            <tr>
                <td <?php write_align_with_textdir_check('right'); ?>><?php print $pgv_lang['username'] ?></td>
                <td><input type="text" tabindex="<?php $i++;
                    print $i ?>" name="username" value="<?php print $username ?>" size="20" class="formField"></td>
                <td><?php print_help_link('username_help', 'qm'); ?></td>
            </tr>
            <tr>
                <td <?php write_align_with_textdir_check('right'); ?>><?php print $pgv_lang['password'] ?></td>
                <td><input type="password" tabindex="<?php $i++;
                    print $i ?>" name="password" size="20" class="formField"></td>
                <td><?php print_help_link('password_help', 'qm'); ?></td>
            </tr>
            <?php if ($ALLOW_REMEMBER_ME) { ?>
                <tr>
                    <td colspan="2"><input type="checkbox" tabindex="<?php $i++;
                        print $i ?>" name="remember" value="yes" <?php if (!empty($_COOKIE['pgv_rem'])) {
                            print 'checked="checked"';
                        } ?> class="formField"> <?php print $pgv_lang['remember_me'] ?>
                    </td>
                    <td><?php print_help_link('remember_me_help', 'qm'); ?></td>
                </tr>
            <?php } ?>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" tabindex="<?php $i++;
                    print $i ?>" value="<?php print $pgv_lang['login']; ?>">&nbsp;
                    <input type="submit" tabindex="<?php $i++;
                    print $i ?>" value="<?php print $pgv_lang['admin']; ?>" onclick="document.loginform.url.value='admin.php';">
                </td>
                <td <?php write_align_with_textdir_check('left'); ?>>
                    <?php
                    if ($SHOW_CONTEXT_HELP) {
                        if ($REQUIRE_AUTHENTICATION) {
                            print_help_link('login_buttons_aut_help', 'qm');
                        } else {
                            print_help_link('login_buttons_help', 'qm');
                        }
                    } ?>
                </td>
            </tr>
        </table>
    </form><br><br>
<?php
if ($USE_REGISTRATION_MODULE) {
                        print_text('no_account_yet');

                        print '<br><a href="login_register.php?action=register">';

                        print_text('requestaccount');

                        print '</a>';

                        print_help_link('new_user_help', 'qm');

                        print '<br><br>';

                        print_text('lost_password');

                        print '<br><a href="login_register.php?action=pwlost">';

                        print_text('requestpassword');

                        print '</a>';

                        print_help_link('new_password_help', 'qm');
                    }
print '</div><br><br>';
?>
    <script language="JavaScript" type="text/javascript">
        document.loginform.username.focus();
    </script>
<?php
if ('full' == $type) {
    print_footer();
} else {
    print_simple_footer();
}
?>
