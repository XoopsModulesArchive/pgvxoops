<?php

ob_start();
require_once dirname(__DIR__, 2) . '/mainfile.php';
if ((true === mb_stristr($_SERVER['SCRIPT_NAME'], 'addmedia'))
    || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'admin')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'changelanguage')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'downloadgedcom')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'edit_merge')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'edit_privacy')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'editconfig')) || // also catches editconfig_gedcom
    (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'editgedcoms')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'editlang')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'importgedcom')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'linkmedia')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'pgvinfo')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'uploadgedcom')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'uploadmedia')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'useradmin')) || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'usermigrate'))
    || (true === mb_stristr($_SERVER['SCRIPT_NAME'], 'validategedcom'))) {
    require_once XOOPS_ROOT_PATH . '/include/cp_functions.php';

    xoops_cp_header();

    print '{pgvsplit}';

    xoops_cp_footer();

    $output = ob_get_contents();

    ob_end_clean();

    $output = str_replace('</head>', "\n{pgvsplit}\n</head>", $output);
} else {
    require_once XOOPS_ROOT_PATH . '/header.php';

    $xoopsTpl->assign('xoops_pagetitle', '{pgvtitle}');

    $xoopsTpl->assign('xoops_module_header', '{pgvsplit}');

    print '{pgvsplit}';

    require_once XOOPS_ROOT_PATH . '/footer.php';

    $output = ob_get_contents();

    ob_end_clean();
}
$output = explode('{pgvsplit}', $output);
$GLOBALS['wrapper'] = [
    'header1' => $output[0],
'header2' => $output[1],
'footer' => $output[2],
];
