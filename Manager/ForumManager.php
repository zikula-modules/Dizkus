<?php

/**
 * Copyright Dizkus Team 2012
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Dizkus
 * @see https://github.com/zikula-modules/Dizkus
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\DizkusModule\Manager;

use ServiceUtil;
use ModUtil;
use UserUtil;
use SecurityUtil;
use Zikula\DizkusModule\Entity\ForumEntity;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ForumManager
{
    /**
     * managed forum
     * @var ForumEntity
     */
    private $_forum;
    private $_itemsPerPage;
    private $_numberOfItems;
    protected $entityManager;
    protected $name;

    /**
     * construct
     */
    public function __construct($id = null, ForumEntity $forum = null)
    {
        $this->entityManager = ServiceUtil::get('doctrine.entitymanager');
        $this->name = 'ZikulaDizkusModule';
        $this->_itemsPerPage = ModUtil::getVar($this->name, 'topics_per_page');
        if (isset($forum)) {
            // forum has been injected
            $this->_forum = $forum;
        } elseif ($id > 0) {
            $this->_forum = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumEntity', $id);
        } else {
            $this->_forum = new ForumEntity();
        }
    }

    /**
     * Check if forum exists
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->_forum ? true : false;
    }

    /**
     * return page as array
     *
     * @return array|boolean false
     */
    public function toArray()
    {
        if (!$this->_forum) {
            return false;
        }

        return $this->_forum->toArray();
    }

    /**
     * return page as array
     *
     * @return integer
     */
    public function getId()
    {
        return $this->_forum->getForum_id();
    }

    /**
     * return forum as doctrine2 object
     *
     * @return ForumEntity
     */
    public function get()
    {
        return $this->_forum;
    }

    public function getPermissions()
    {
        return ModUtil::apiFunc($this->name, 'Permission', 'get', $this->_forum);
    }

    /**
     * get forum bread crumbs
     *
     * @param boolean $withoutCurrent show tree without the current item
     *
     * @return array
     */
    public function getBreadcrumbs($withoutCurrent = true)
    {
        if ($this->_forum->getLvl() == 0) {
            // already root
            return array();
        }
        $forums = $this->entityManager
            ->getRepository('Zikula\DizkusModule\Entity\ForumEntity')
            ->getPath($this->_forum);
        $output = array();
        foreach ($forums as $key => $forum) {
            if ($key == 0) {
                continue;
            }
            $url = ServiceUtil::get('router')->generate('zikuladizkusmodule_forum_viewforum', array('forum' => $forum->getForum_id()));
            $output[] = array(
                'url' => $url,
                'title' => $forum->getName());
        }
        if ($withoutCurrent) {
            // last element added in template instead
            array_pop($output);
        }

        return $output;
    }

    /**
     * return posts of a forum as doctrine2 object
     *
     * @return Paginator collection of paginated topics
     */
    public function getTopics($startNumber = 1)
    {
        $id = $this->_forum->getForum_id();
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from('Zikula\DizkusModule\Entity\TopicEntity', 'p')
            ->where('p.forum = :forumId')
            ->setParameter('forumId', $id)
            ->leftJoin('p.last_post', 'l')
            ->orderBy('p.sticky', 'DESC')
            ->addOrderBy('l.post_time', 'DESC')
            ->getQuery();
        $query->setFirstResult($startNumber - 1)
            ->setMaxResults($this->_itemsPerPage);
        $paginator = new Paginator($query, false);
        $this->_numberOfItems = count($paginator);

        return $paginator;
    }

    /**
     * get the pager
     *
     * @return array
     */
    public function getPager()
    {
        return array(
            'itemsperpage' => $this->_itemsPerPage,
            'numitems' => $this->_numberOfItems);
    }

    /**
     * increase read count
     *
     * @return boolean true
     */
    public function incrementReadCount()
    {
        $this->_forum->incrementCounter();
        $this->entityManager->flush();

        return true;
    }

    /**
     * Increase post count
     */
    public function incrementPostCount()
    {
        $this->_forum->incrementPostCount();
        $this->modifyParentCount($this->_forum->getParent());
        $this->entityManager->flush();
    }

    /**
     * decrease post count
     */
    public function decrementPostCount()
    {
        $this->_forum->decrementPostCount();
        $this->modifyParentCount($this->_forum->getParent(), 'decrement');
        $this->entityManager->flush();
    }

    /**
     * increase topic count
     */
    public function incrementTopicCount()
    {
        $this->_forum->incrementTopicCount();
        $this->modifyParentCount($this->_forum->getParent(), 'increment', 'Topic');
        $this->entityManager->flush();
    }

    /**
     * recursive method to modify parent forum's post or topic count
     */
    private function modifyParentCount(ForumEntity $parentForum, $direction = 'increment', $entity = 'Post')
    {
        $direction = in_array($direction, array('increment', 'decrement')) ? $direction : 'increment';
        $entity = in_array($entity, array('Post', 'Topic')) ? $entity : 'Post';
        $method = "{$direction}{$entity}Count";
        $parentForum->{$method}();
        $grandParent = $parentForum->getParent();
        if (isset($grandParent)) {
            $this->modifyParentCount($grandParent, $direction, $entity);
        }
    }

    public function setLastPost($post)
    {
        $this->_forum->setLast_post($post);
        $this->entityManager->flush();
    }

    /**
     * store the forum
     *
     * @param array $data page data
     */
    public function store($data)
    {
        $this->_forum->merge($data);
        $this->entityManager->persist($this->_forum);
        $this->entityManager->flush();
    }

    /**
     * Is the current user (provided user) a forum moderator?
     *
     * @param  integer $uid (optional, default: null)
     * @return boolean
     */
    public function isModerator($uid = null)
    {
        if (!isset($uid)) {
            $uid = UserUtil::getVar('uid');
            if($uid === null){
                return false;
            }
        }
        // check zikula perms
        if (SecurityUtil::checkPermission($this->name, $this->_forum->getForum_id() . '::', ACCESS_MODERATE)) {
         //   return true;
        }
        $moderatorUsers = $this->_forum->getModeratorUsersAsIdArray(true);
        if (in_array($uid, $moderatorUsers)) {
          //  return true;
        }
        $gids = $this->_forum->getModeratorGroupsAsIdArray(true);
        if (empty($gids)) {
         //   return false;
        }
        // is this user in any of the groups?
        $dql = 'SELECT m FROM Zikula\\UsersModule\\Entity\\UserEntity m
            WHERE m.uid = :uid';
        $user = $this->entityManager
            ->createQuery($dql)
            ->setParameter('uid', $uid)
            ->setMaxResults(1)
            ->getOneOrNullResult();
          
        $groupMembership = [];
        foreach($user->getGroups()->toArray() as $group){
              if(in_array($group->getGid(), $gids)){
                $groupMembership[] = $group->getGid();   
              }
        }
          
        return count($groupMembership) > 0 ? true : false;
    }

    /**
     * Is this forum a child of the provided forum?
     *
     * @param  ForumEntity $forum
     * @return boolean
     */
    public function isChildOf(ForumEntity $forum)
    {
        return $this->get()->getLft() > $forum->getLft() && $this->get()->getRgt() < $forum->getRgt();
    }
}
