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

use Zikula\Module\DizkusModule\Manager\ForumUserManager;
use ModUtil;
use LogUtil;
use UserUtil;
use Zikula_Form_View;
use Zikula_Exception_Forbidden;

/**
 * This class provides a handler to create a new topic.
 */
class Prefs extends \Zikula_Form_AbstractHandler
{

    /**
     * forum id
     *
     * @var integer
     */
    private $_forumUser;

    /**
     * Setup form.
     *
     * @param Zikula_Form_View $view Current Zikula_Form_View instance.
     *
     * @return boolean
     *
     * @throws Zikula_Exception_Forbidden If the current user does not have adequate permissions to perform this function.
     */
    public function initialize(Zikula_Form_View $view)
    {

        if (!UserUtil::isLoggedIn()) {
            throw new Zikula_Exception_Forbidden(LogUtil::getErrorMsgPermission());
        }

        // get the input
        $this->_forumUser = new ForumUserManager(UserUtil::getVar('uid'));

        $view->assign($this->_forumUser->toArray());
        $orders = array(
            0 => array(
                'text' => 'newest submissions at top',
                'value' => 1
            ),
            1 => array(
                'text' => 'oldest submissions at top',
                'value' => 0
            )
        );
        $view->assign('orders', $orders);

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
        if ($args['commandName'] == 'cancel') {
            $url = ModUtil::url($this->name, 'user', 'prefs');

            return $view->redirect($url);
        }

        // check for valid form
        if (!$view->isValid()) {
            return false;
        }

        $data = $view->getValues();
        $this->_forumUser->store($data);

        return true;
    }

}
