<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @see https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

namespace Zikula\DizkusModule\Api;

use UserUtil;

/**
 * This class provides the account api functions
 */
class AccountApi extends \Zikula_AbstractApi
{
    /**
     * Return an array of items to show in the your account panel.
     *
     * @param array $args Arguments array.
     *        string $args['uname'] User name.
     *
     * @return array array of items
     */
    public function getall($args)
    {
        // the array that will hold the options
        $items = array();
        // show link for users only
        if (!UserUtil::isLoggedIn()) {
            // not logged in
            return $items;
        }
        $uname = isset($args['uname']) ? $args['uname'] : UserUtil::getVar('uname');
        // does this user exist?
        if (UserUtil::getIDFromName($uname) == false) {
            // user does not exist
            return $items;
        }
        $items[] = array(
            'url' => $this->get('router')->generate('zikuladizkusmodule_user_prefs'),
            'title' => $this->__('Forum'),
            'icon' => 'icon_forumprefs.gif');
        // Return the items
        return $items;
    }
}
