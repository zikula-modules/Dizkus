<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

class Dizkus_Api_Prefs extends Zikula_AbstractApi {



    /**
     * get available admin panel links
     *
     * @return array array of admin links
     */
    public function getlinks()
    {
        $links = array();
        if (SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url'   => ModUtil::url('Dizkus', 'user', 'prefs'),
                'text'  => $this->__('Personal settings'),
                'title' => $this->__('Modify personal settings'),
                'class' => 'z-icon-es-options',
            );
            $links[] = array(
                'url'   => ModUtil::url('Dizkus', 'user', 'manageForumSubscriptions'),
                'text'  => $this->__('Forum subscriptions'),
                'title' => $this->__('Manage forum subscriptions'),
                'class' => 'z-icon-es-options',
            );
            $links[] = array(
                'url'   => ModUtil::url('Dizkus', 'user', 'manageTopicSubscriptions'),
                'text'  => $this->__('Topic subscriptions'),
                'title' => $this->__('Manage topic subscriptions'),
                'class' => 'z-icon-es-options',
            );
            if (ModUtil::getVar('Dizkus','signaturemanagement') == 'yes') {
                $links[] = array(
                    'url'   => ModUtil::url('Dizkus', 'user', 'signaturemanagement'),
                    'text'  => $this->__('Signature'),
                    'title' => $this->__('Manage signature'),
                    'class' => 'z-icon-es-options',
                );
            }
            if ($this->getVar('ignorelist_handling') != "none" && ModUtil::available('ContactList')) {
                $links[] = array(
                    'url'   => ModUtil::url('Dizkus', 'user', 'ignorelistmanagement'),
                    'text'  => $this->__('Ignore list'),
                    'title' => $this->__('Manage ignore list'),
                    'class' => 'z-icon-es-options',
                );
            }


        }
    
        return $links;
    }
}