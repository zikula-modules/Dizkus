<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

namespace Zikula\Module\DizkusModule\Api;

use ModUtil;
use SecurityUtil;
use Zikula\Module\DizkusModule\Entity\RankEntity;

class AdminApi extends \Zikula_AbstractApi
{

    /**
     * get available admin panel links
     *
     * @return array array of admin links
     */
    public function getlinks()
    {
        $links = array(
                );
        if (SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'tree'),
                'text' => $this->__('Edit forum tree'),
                'title' => $this->__('Create, delete, edit and re-order forums'),
                'icon' => 'list');
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'ranks', array(
                    'ranktype' => RankEntity::TYPE_POSTCOUNT)),
                'text' => $this->__('Edit user ranks'),
                'icon' => 'star-half-empty',
                'title' => $this->__('Create, edit and delete user rankings acquired through the number of a user\'s posts'),
                'links' => array(
                    array(
                        'url' => ModUtil::url($this->name, 'admin', 'ranks', array(
                            'ranktype' => RankEntity::TYPE_POSTCOUNT)),
                        'text' => $this->__('Edit user ranks'),
                        'title' => $this->__('Create, edit and delete user rankings acquired through the number of a user\'s posts')),
                    array(
                        'url' => ModUtil::url($this->name, 'admin', 'ranks', array(
                            'ranktype' => RankEntity::TYPE_HONORARY)),
                        'text' => $this->__('Edit honorary ranks'),
                        'title' => $this->__('Create, delete and edit special ranks for particular users')),
                    array(
                        'url' => ModUtil::url($this->name, 'admin', 'assignranks'),
                        'text' => $this->__('Assign honorary rank'),
                        'title' => $this->__('Assign honorary user ranks to users'))));
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'manageSubscriptions'),
                'text' => $this->__('Manage subscriptions'),
                'title' => $this->__('Remove a user\'s topic and forum subscriptions'),
                'icon' => 'envelope-alt');
            $links[] = array(
                'url' => ModUtil::url($this->name, 'admin', 'preferences'),
                'text' => $this->__('Settings'),
                'title' => $this->__('Edit general forum-wide settings'),
                'icon' => 'wrench');
        }

        return $links;
    }

}
