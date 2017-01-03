<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Zikula\DizkusModule\Helper;

use Zikula\DizkusModule\Entity\RankEntity;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;
use Zikula\UsersModule\Api\CurrentUserApi;


/**
 * CronHelper
 *
 * @author Kaik
 */
class RankHelper {
    
    /**
     * @var RequestStack
     */    
    private $requestStack;      
    
    /**
     * @var EntityManager
     */
    private $entityManager;    
    
    /**
     * @var CurrentUserApi
     */    
    private $userApi;      

    private $cache = [];  
    
    
    public function __construct(
            RequestStack $requestStack,
            EntityManager $entityManager,
            CurrentUserApi $userApi        
         ) {
        
        $this->name = 'ZikulaDizkusModule';
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getMasterRequest();
        $this->entityManager = $entityManager;
        $this->userApi = $userApi;    
    }
    
     private $_userRanks = array();

    /**
     * Get all ranks
     *
     * @param array $args Arguments array.
     *
     * @return array
     *
     */
    public function getAll($args)
    {
        // read images
        $path = $this->getVar('url_ranks_images');
        $handle = opendir($path);
        $filelist = array();
        while ($file = readdir($handle)) {
            if ($this->dzk_isimagefile($path . '/' . $file)) {
                $filelist[] = $file;
            }
        }
        asort($filelist);
        if ($args['ranktype'] == RankEntity::TYPE_POSTCOUNT) {
            $orderby = 'minimumCount';
        } else {
            $orderby = 'title';
        }
        $ranks = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\RankEntity')
            ->findBy(array('type' => $args['ranktype']), array($orderby => 'ASC'));

        return array($filelist, $ranks);
    }

    /**
     * Modify a rank
     *
     * @param array $args Argument array
     *
     * @return boolean
     */
    public function save($args)
    {
        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }
        //title, description, minimumCount, maximumCount, type, image
        foreach ($args['ranks'] as $rankid => $rank) {
            if ($rankid == '-1') {
                $r = new RankEntity();
                $r->merge($rank);
                $this->entityManager->persist($r);
            } else {
                $r = $this->entityManager->find('Zikula\DizkusModule\Entity\RankEntity', $rankid);
                if ((isset($rank['rank_delete'])) && ($rank['rank_delete'] == '1')) {
                    $this->entityManager->remove($r);
                    // update users that are assigned the rank to null
                    $users = $this->entityManager->getRepository('ZikulaDizkusModule:RankEntity')->findBy(array('rank' => $rankid));
                    foreach ($users as $user) {
                        $user->clearRank();
                    }
                } else {
                    $r->merge($rank);
                    $this->entityManager->persist($r);
                }
            }
        }
        $this->entityManager->flush();

        return true;
    }

    /**
     * assignranksave
     *
     * setrank array(uid) = rank_id
     */
    public function assign($args)
    {
        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }
        if (is_array($args['setrank'])) {
            foreach ($args['setrank'] as $userId => $rankId) {
                $rankId = $rankId == 0 ? null : $rankId;
                $managedForumUser = new ForumUserManager($userId);
                if (isset($rankId)) {
                    $rank = $this->entityManager->getReference('Zikula\DizkusModule\Entity\RankEntity', $rankId);
                    $managedForumUser->get()->setRank($rank);
                } else {
                    $managedForumUser->get()->clearRank();
                }
            }
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * Get user rank data
     *
     * @param array $args Arguments array.
     *
     * @return array The rank data of the poster
     */
    public function getData($args)
    {
        $data = array();
        if (!isset($args['poster'])) {
            return $data;
        }
        // user has assigned rank
        $userRank = $args['poster']->getRank();
        if (isset($userRank)) {
            $data = $userRank->toArray();

            return $data = $this->addImageAndLink($data);
        }
        // check if rank by number of posts is cached
        $uid = $args['poster']->getUser_id();
        if (array_key_exists($uid, $this->_userRanks)) {
            return $this->_userRanks[$uid];
        }
        // get rank by number of post
        $userRank = $this->entityManager
            ->createQueryBuilder()
            ->select('r')
            ->from('Zikula\DizkusModule\Entity\RankEntity', 'r')
            ->where('r.minimumCount <= :posts and r.maximumCount >= :posts')
            ->setParameter('posts', $args['poster']->getPostCount())
            ->getQuery()
            ->setMaxResults(1)
            ->getArrayResult();
        if (isset($userRank[0])) {
            $data = $this->addImageAndLink($userRank[0]);
        }
        // cache rank by number of posts
        $this->_userRanks[$uid] = $data;

        return $data;
    }

    private function addImageAndLink($data)
    {
        $data['rank_link'] = substr($data['description'], 0, 7) == 'http://' ? $data['description'] : '';
        if (!empty($data['image'])) {
            $data['image'] = $this->getVar('url_ranks_images') . '/' . $data['image'];
            $data['image_attr'] = function_exists('getimagesize') ? @getimagesize($data['image']) : null;
        }

        return $data;
    }

    /**
     * dzk is an image
     * check if a filename is an image or not
     */
    private function dzk_isimagefile($filepath)
    {
        if (function_exists('getimagesize') && @getimagesize($filepath) != false) {
            return true;
        }
        if (preg_match('/^(.*)\\.(gif|jpg|jpeg|png)/si', $filepath)) {
            return true;
        }

        return false;
    }

    
}