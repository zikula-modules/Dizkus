<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

namespace Zikula\Module\DizkusModule\Form\Handler\User;

use ModUtil;
use SecurityUtil;
use UserUtil;
use System;
use Zikula_Form_View;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\RouterInterface;

/**
 * This class provides a handler to manage topic subscriptions.
 */
class TopicSubscriptions extends \Zikula_Form_AbstractHandler
{

    /**
     * Setup form.
     *
     * @param Zikula_Form_View $view Current Zikula_Form_View instance.
     *
     * @return boolean
     *
     * @throws AccessDeniedException If the current user does not have adequate permissions to perform this function.
     */
    public function initialize(Zikula_Form_View $view)
    {
        if (!UserUtil::isLoggedIn()) {
            $url = $view->getContainer()->get('router')->generate('zikuladizkusmodule_user_managetopicsubscriptions', array(), RouterInterface::ABSOLUTE_URL);

            return ModUtil::func('Users', 'user', 'login', array('returnpage' => $url));
        }

        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_READ) || !ModUtil::apiFunc($this->name, 'Permission', 'canRead')) {
            throw new AccessDeniedException();
        }

        $subscriptions = ModUtil::apiFunc($this->name, 'topic', 'getSubscriptions');
        $view->assign('subscriptions', $subscriptions);

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
    public function handleCommand(Zikula_Form_View $view, &$args)
    {
        // check for valid form
        if (!$view->isValid()) {
            return false;
        }

        $data = $view->getValues();

        if (count($data['topicIds']) > 0) {
            foreach ($data['topicIds'] as $topicId => $selected) {
                if ($selected) {
                    ModUtil::apiFunc($this->name, 'Topic', 'unsubscribe', array('topic' => $topicId));
                }
            }
        }

        $url = $view->getContainer()->get('router')->generate('zikuladizkusmodule_user_managetopicsubscriptions', array(), RouterInterface::ABSOLUTE_URL);
        return $view->redirect($url);
    }

}
