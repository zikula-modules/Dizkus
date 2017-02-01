<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @see https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */
//
// store the absolute path to your Zikula folder here
//
chdir('/opt/webdev/htdocs/z121');
// NOTE : This will work with the Zikula backend... I did not
// try other rss feed (1.0, 2.0, Atom)... RSS mod could
// return a different information (timestamp - array keys like title, etc.
//
// start Zikula
//
include 'lib/ZLoader.php';
ZLoader::register();
System::init();
/* @var $em \Doctrine\ORM\EntityManager */
$em = ServiceUtil::get('doctrine.entitymanager');
//
// Checking if RSS2Forum is enabled
//
if (!ModUtil::getVar('ZikulaDizkusModule', 'rss2f_enabled')) {
    return;
}
//
// Checking Feeds module availability
//
if (!ModUtil::available('Feeds')) {
    return;
}
//
// Getting All forums where RSS2DIZKUS is SET...
//
$forums = $em->getRepository('Zikula\DizkusModule\Entity\ForumEntity')->getRssForums();
// this may return some forums intented for mail2forum cron stuff... I don't know.
if (!$forums) {
    return;
}
$loggedin = false;
$lastuser = '';
foreach ($forums as $forum) {
    $connection = $forum->getPop3Connection()->getConnection();
    $forum = $forum->toArray();
    if ($lastuser != $connection['coreUser']->getUid()) {
        UserUtil::logOut();
        $loggedin = false;
        // login the correct user
        if (UserUtil::logIn($connection['coreUser']->getUname(), base64_decode($connection['coreUser']->getPass()), false)) {
            $lastuser = $connection['coreUser']->getUid();
            $loggedin = true;
        } else {
        }
    } else {
        // we have been here before
        $loggedin = true;
    }
    if ($loggedin == true) {
        $rss = ModUtil::apiFunc('Feeds', 'user', 'get', array(
                    'fid' => $connection['server']));
        if (!$rss) {
            // this feed does not exist
            die;
        }
        // Get the feed
        $dump = ModUtil::apiFunc('Feeds', 'user', 'getfeed', array(
                    'fid' => $rss['fid'],
                    'url' => $rss['url']));
        if (!$dump) {
            // this feed does not exist
            die;
        }
        // Sorting ascending to store in the right order in the forum.
        // I tried to sort by the timestamp at first and lost my mind why it wasn't working...
        // Finally decided that since it was working with the link, the link was good enough
        // Change it to your liking. It probably won't work on other type of feed.
        // Important information is in the $dump->items
        $items = $dump['feed']->get_items();
        // See the function below...
        $insert = ModUtil::apiFunc('ZikulaDizkusModule', 'user', 'insertrss', array(
                    'items' => $items,
                    'forum' => $forum));
        if (!$insert) {
        }
    }
}
