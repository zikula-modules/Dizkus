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

use UserUtil;
use DateTime;
use System;
use Doctrine;

/**
 * This class provides the userdata api functions
 */
class UserDataApi extends \Zikula_AbstractApi
{

    private $_online;

    public function initialize()
    {
        $this->_online = array();
    }

    /**
     * getUserOnlineStatus
     *
     * Check if a user is online
     *
     * @param array $args Arguments array.
     *
     * @return boolean True if online
     */
    public function getUserOnlineStatus($args)
    {
        if ($args['uid'] == \Zikula\Module\DizkusModule\Entity\ForumUserEntity::FAKE_USER_ID) {
            return false;
        }
        //int $uid The users id
        if (empty($args['uid'])) {
            $args['uid'] = UserUtil::getVar('uid');
        }
        if (array_key_exists($args['uid'], $this->_online)) {
            return $this->_online[$args['uid']];
        }
        $dql = 'SELECT s.uid
                FROM Zikula\\Module\\UsersModule\\Entity\\UserSessionEntity s
                WHERE s.lastused > :activetime
                AND s.uid = :uid';
        $query = $this->entityManager->createQuery($dql);
        $activetime = new DateTime();
        // maybe need to check TZ here
        $activetime->modify('-' . System::getVar('secinactivemins') . ' minutes');
        $query->setParameter('activetime', $activetime);
        $query->setParameter('uid', $args['uid']);
        $uid = $query->execute(null, \Doctrine\ORM\AbstractQuery::HYDRATE_SCALAR);
        $isOnline = !empty($uid) ? true : false;
        $this->_online[$args['uid']] = $isOnline;

        return $isOnline;
    }

}
