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

use ModUtil;
use SecurityUtil;

class PrefsApi extends \Zikula_AbstractApi
{
    /**
     * get available user pref panel links
     *
     * @return array array of admin links
     */
    public function getLinks()
    {
        $links = array(
                );
        if (SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_OVERVIEW)) {
            $links[] = array(
                'url' => $this->get('router')->generate('zikuladizkusmodule_user_prefs'),
                'text' => $this->__('Personal settings'),
                'title' => $this->__('Modify personal settings'),
                'icon' => 'wrench');
            $links[] = array(
                'url' => $this->get('router')->generate('zikuladizkusmodule_user_manageforumsubscriptions'),
                'text' => $this->__('Forum subscriptions'),
                'title' => $this->__('Manage forum subscriptions'),
                'icon' => 'envelope-alt');
            $links[] = array(
                'url' => $this->get('router')->generate('zikuladizkusmodule_user_managetopicsubscriptions'),
                'text' => $this->__('Topic subscriptions'),
                'title' => $this->__('Manage topic subscriptions'),
                'icon' => 'envelope-alt');
            if (ModUtil::getVar($this->name, 'signaturemanagement')) {
                $links[] = array(
                    'url' => $this->get('router')->generate('zikuladizkusmodule_user_signaturemanagement'),
                    'text' => $this->__('Signature'),
                    'title' => $this->__('Manage signature'),
                    'icon' => 'pencil');
            }
        }

        return $links;
    }
}
