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

/**
 * createbyitem
 * createhook function (open new topic)
 *
 * objectid, extrainfo
 */
function Dizkus_hookapi_createbyitem($args)
{
    extract($args);
    unset($args);

    if(!isset($args['extrainfo']['module']) || empty($args['extrainfo']['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $args['extrainfo']['module'];
    }

    if(isset($args['extrainfo']['itemid']) && !empty($args['extrainfo']['itemid'])) {
        $args['objectid'] = $args['extrainfo']['itemid'];
    }

    if(!isset($args['objectid']) || empty($args['objectid'])) {
        return showforumerror(_MODARGSERROR, __FILE__, __LINE__);
    }

    // we have an objectid now, we combine this with the module id now for the
    // reference and check if it already exists
    $modid = pnModGetIDFromName($modname);
    $reference =  $modid . '-' . $args['objectid'];


    $topic_id = pnModAPIFunc('Dizkus', 'user', 'get_topicid_by_reference',
                             array('reference' => $reference));
    if($topic_id == false) {
        $subject   = _DZK_AUTOMATICDISCUSSIONSUBJECT;
        $message   = _DZK_AUTOMATICDISCUSSIONMESSAGE;
        $pntopic   = 0;
        $authorid  = pnUserGetVar('uid');

        $functionfilename = DataUtil::formatForStore('modules/Dizkus/pncommentsapi/' . $modname . '.php');
        if(file_exists($functionfilename) && is_readable($functionfilename)) {
            list($subject, $message, $pntopic, $authorid) = pnModAPIFunc('Dizkus', 'comments', $modname, array('objectid' => $args['objectid']));
        }

        $forum_id = DBUtil::selectField('dizkus_forums', 'forum_id', "forum_moduleref='$modid'");
        pnModAPIFunc('Dizkus', 'user', 'storenewtopic',
                     array('forum_id'  => $forum_id,
                           'subject'   => $subject,
                           'message'   => $message,
                           'reference' => $reference,
                           'post_as'   => $authorid));
    }

    return $args['extrainfo'];
}

/**
 * updatebyitem
 * updatehook function
 *
 */
function Dizkus_hookapi_updatebyitem($args)
{
    if(!isset($args['extrainfo']['module']) || empty($args['extrainfo']['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $args['extrainfo']['module'];
    }

    if(isset($args['extrainfo']['itemid']) && !empty($args['extrainfo']['itemid'])) {
        $args['objectid'] = $args['extrainfo']['itemid'];
    }

    if(!isset($args['objectid']) || empty($args['objectid'])) {
        return showforumerror(_MODARGSERROR, __FILE__, __LINE__);
    }

    // we have an objectid now, we combine this with the module id now for the
    // reference and check if it already exists
    $modid = pnModGetIDFromName($modname);
    $reference =  $modid . '-' . $args['objectid'];

    $topic_id = pnModAPIFunc('Dizkus', 'user', 'get_topicid_by_reference',
                             array('reference' => $reference));

    if($topic_id <> false) {
        // found
        // get first post id
        $post_id = pnModAPIFunc('Dizkus', 'user', 'get_firstlast_post_in_topic',
                                array('topic_id' => $topic_id,
                                      'first'    => true,
                                      'id_only'  => true));

        if($post_id <> false) {
            $functionfilename = DataUtil::formatForStore('modules/Dizkus/pncommentsapi/' . $modname . '.php');
            if(file_exists($functionfilename) && is_readable($functionfilename)) {
                list($subject, $message, $pntopic, $authorid) = pnModAPIFunc('Dizkus', 'comments', $modname, array('objectid' => $args['objectid']));
            }
            pnModAPIFunc('Dizkus', 'user', 'updatepost',
                         array('post_id'  => $post_id,
                               'subject'  => $subject,
                               'message'  => $message));
        }

    }
    return $args['extrainfo'];
}

/**
 * deletebyitem
 * deletehook function (closes a topic or removes it depending on the setting)
 *
 */
function Dizkus_hookapi_deletebyitem($args)
{
    if(!isset($args['extrainfo']['module']) || empty($args['extrainfo']['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $args['extrainfo']['module'];
    }

    if(isset($args['extrainfo']['itemid']) && !empty($args['extrainfo']['itemid'])) {
        $args['objectid'] = $args['extrainfo']['itemid'];
    }

    if(!isset($args['objectid']) || empty($args['objectid'])) {
        return showforumerror(_MODARGSERROR, __FILE__, __LINE__);
    }

    // we have an objectid now, we combine this with the module id now for the
    // reference and check if it already exists
    $modid = pnModGetIDFromName($modname);
    $reference =  $modid . '-' . $args['objectid'];

    $topic_id = pnModAPIFunc('Dizkus', 'user', 'get_topicid_by_reference',
                             array('reference' => $reference));

    if($topic_id <> false) {
        if(pnModGetVar('Dizkus', 'deletehookaction') == 'remove') {
            pnModAPIFunc('Dizkus', 'user', 'deletetopic', array('topic_id' => $topic_id));
        } else {
            pnModAPIFunc('Dizkus', 'user', 'lockunlocktopic', array('topic_id'=> $topic_id, 'mode' => 'lock'));
        }
    }

    return $args['extrainfo'];
}
