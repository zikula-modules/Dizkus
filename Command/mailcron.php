<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @see https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

//
// This file was most probably called by os cron
// If so this should be moved
//

//
// store the absolute path to your Zikula folder here
//
//chdir('/opt/webdev/htdocs');
////
//// no changes necessary beyond this point!
////
//include 'lib/ZLoader.php';
//ZLoader::register();
//System::init();
//$debug = $this->request->query->get('debug', $this->request->request->get('debug', 0));
//$debug = $debug == 1 ? true : false;
//// user userId = 2 (site owner) to avoid perm limits
//$forums = ModUtil::apiFunc('ZikulaDizkusModule', 'forum', 'getForumIdsByPermission', array('userId' => 2));
//if (is_array($forums) && count($forums) > 0) {
//    echo count($forums) . ' forums read<br />';
//    foreach ($forums as $forum) {
//        if ($forum['externalsource'] == 1) {
//            // Mail
//            ModUtil::apiFunc('ZikulaDizkusModule', 'cron', 'mail', array('forum' => $forum, 'debug' => $debug));
//        }
//    }
//}
