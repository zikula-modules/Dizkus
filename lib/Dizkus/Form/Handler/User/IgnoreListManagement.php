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
 * This class provides a handler to manage the ignore list.
 */
class Dizkus_Form_Handler_User_IgnoreListManagement extends Zikula_Form_AbstractHandler
{

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
        // prepare list    
        $ignorelist_handling = ModUtil::getVar('Dizkus', 'ignorelist_handling');
        $ignorelist_options = array();
        switch ($ignorelist_handling) {
            case 'strict':
                $ignorelist_options[] = array('text' => $this->__('Strict'), 'value' => 'strict');
                break;
            case 'medium':
                $ignorelist_options[] = array('text' => $this->__('Medium'), 'value' => 'medium');
                break;
            default:
                $ignorelist_options[] = array('text' => $this->__('None'), 'value' => 'none');
        }

        // get user's configuration
        $view->caching = false;
        $view->add_core_data();

        // assign data
        $view->assign('ignorelist_options', $ignorelist_options);
        $view->assign('ignorelist_myhandling', ModUtil::apiFunc('Dizkus', 'user', 'get_settings_ignorelist', array('uid' => UserUtil::getVar('uid'))));
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
        if ($args['commandName'] == 'update') {
            // Security check 
            if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_COMMENT)) {
                return LogUtil::registerPermissionError();
            }

            // get the Form data and do a validation check
            $obj = $view->getValues();
            if (!$view->isValid()) {
                return false;
            }

            // update user's attributes
            UserUtil::setVar('dzk_ignorelist_myhandling', $obj['ignorelist_myhandling']);

            LogUtil::registerStatus($this->__('Done! Updated the \'Ignore list\' settings.'));

            $url = ModUtil::url('Dizkus', 'user', 'prefs');
            return $view->redirect($url);
        }

        return true;
    }

}
