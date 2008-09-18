<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link http://www.dizkus.com
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

Loader::includeOnce('modules/Dizkus/common.php');

/*
 * param: objectid
 */

function Dizkus_commentsapi_News($args)
{
    extract($args);
    unset($args);

    list($dbconn, $pntable) = dzkOpenDB();
    $pnstoriestable = $pntable['stories'];
    $pnstoriescolumn = $pntable['stories_column'];
    $pntopicstable = $pntable['topics'];
    $pntopicscolumn = $pntable['topics_column'];

    $sql = "SELECT $pnstoriescolumn[bodytext],
                   $pnstoriescolumn[hometext],
                   $pnstoriescolumn[notes],
                   $pnstoriescolumn[title],
                   $pnstoriescolumn[topic],
                   $pnstoriescolumn[aid],
                   $pnstoriescolumn[format_type],
                   $pntopicscolumn[topicname]
            FROM   $pnstoriestable
            LEFT JOIN $pntopicstable ON $pnstoriescolumn[topic]=$pntopicscolumn[topicid]
            WHERE $pnstoriescolumn[sid] ='" . DataUtil::formatForStore($objectid) . "'";
    $result = dzkExecuteSQL($dbconn, $sql, __FILE__, __LINE__);
    //echo $sql;
    //exit;

    if(!$result->EOF) {
        list($bodytext,
             $hometext,
             $notes,
             $title,
             $topic,
             $authorid,
             $format_type,
             $topicname) = $result->fields;
        dzkCloseDB($result);
    } else {
        return false;
    }

    // workaround for bug in AddStories html fixed on 11-05-2005
    $authorid = (int)$authorid;

    $link  = pnGetBaseURL() . 'index.php?name=News&file=article&sid=' . $objectid;
    $title = ($topicname<>'' ? $topicname.' - '.$title : $title);

    if(pnModIsHooked('bbcode', 'Dizkus')) {
        $notes = '[i]' . $notes . '[/i]';
        $link  = '[url=' . $link . ']' . _DZK_BACKTOSUBMISSION . '[/url]';
    }

    $totaltext = $hometext . "\n\n" . $bodytext . "\n\n" . $notes . "\n\n" . $link . "\n\n";

    return array($title, $totaltext , $topic, $authorid);
}