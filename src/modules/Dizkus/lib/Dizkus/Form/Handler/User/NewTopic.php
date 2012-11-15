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
 * This class provides a handler to create a new topic.
 */
class Dizkus_Form_Handler_User_NewTopic extends Zikula_Form_AbstractHandler
{
    /**
     * forum id
     *
     * @var integer
     */
    private $forum_id;

    /**
     * topic poster uid
     *
     * @var integer
     */
    private $topic_poster;

    /**
     * Setup form.
     *
     * @param Zikula_Form_View $view Current Zikula_Form_View instance.
     *
     * @return boolean
     *
     * @throws Zikula_Exception_Forbidden If the current user does not have adequate permissions to perform this function.
     */
    function initialize(Zikula_Form_View $view)
    {
        if (!ModUtil::apiFunc($this->name, 'Permission', 'canRead')) {
            throw new Zikula_Exception_Forbidden(LogUtil::getErrorMsgPermission());
        }
    
        // get the input
        $this->forum_id = (int)$this->request->query->get('forum');

        if (!isset($this->forum_id)) {
            return LogUtil::registerError($this->__('Error! Missing forum id.'), null, ModUtil::url('Dizkus','user', 'main'));
        }

        $forum = $this->entityManager->find('Dizkus_Entity_Forums', $this->forum_id)->toArray();

        $view->assign('forum', $forum)
             ->assign('preview', false);

        return true;
    }

    /**
     * Handle form submission.
     *
     * @param Zikula_Form_View $view  Current Zikula_Form_View instance.
     * @param array            &$args Arguments.
     *
     * @return bool|void
     */
    function handleCommand(Zikula_Form_View $view, &$args)
    {
        if ($args['commandName'] == 'cancel') {
            $url = ModUtil::url('Dizkus', 'user', 'viewforum', array('forum' => $this->forum_id));
            return $view->redirect($url);
        }

        // check for valid form
        if (!$view->isValid()) {
            return false;
        }

        $data = $view->getValues();
        $data['forum_id'] = $this->forum_id;
        $data['topic_start'] = false;

        // check for maximum message size
        if ((strlen($data['message']) +  strlen('[addsig]')) > 65535) {
            return LogUtil::registerStatus($this->__('Error! The message is too long. The maximum length is 65,535 characters.'));
            // switch to preview mode
        }

        list($lastVisit, $lastVisitUnix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');

        $newtopic = ModUtil::apiFunc('Dizkus', 'user', 'preparenewtopic', $data);

        // show preview
        if ($args['commandName'] == 'preview') {
            $view->assign('preview', true);
            $view->assign('newtopic', $newtopic);
            $view->assign('last_visit', $lastVisit);
            $view->assign('last_visit_unix', $lastVisitUnix);
            $view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
            return true;
        }

        // store new topic
        $topicId = ModUtil::apiFunc('Dizkus', 'user', 'storenewtopic', $data);

        // redirect to the new topic
        $url = ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $topicId));
        return $view->redirect($url);
    }
}
