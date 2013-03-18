<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

/**
 * allowedhtml plugin
 * lists all allowed html tags
 *
 */
function smarty_function_lastTopicUrl($params, Zikula_View $view)
{
    $topic = $params['topic'];
    $params = array(
        'topic' => $topic->getTopic_id(),
        'start' => ModUtil::apiFunc('Dizkus', 'user', 'getTopicPage', array('topic_replies' => $topic->getTopic_replies())),
    );
    $url = new \Zikula\Core\ModUrl('Dizkus', 'user', 'viewtopic', ZLanguage::getLanguageCode(), $params, $topic->getLast_post()->getPost_id());
    return $url->getUrl();
}
