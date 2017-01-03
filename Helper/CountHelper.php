<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Zikula\DizkusModule\Helper;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;
use Zikula\UsersModule\Api\CurrentUserApi;


/**
 * FavoritesHelper
 *
 * @author Kaik
 */
class CountHelper {
    
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
    
    /**
     * Counts posts in forums, topics
     * or counts forum users
     *
     * @param $id int the id, forum id
     * @param $type string, defines the id parameter
     * @param $force boolean, default false, if true, do not use cached
     * @return int (depending on type and id)
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
    public function getAllPostsCount($force = false)
    {
        if (!isset($this->cache['Post']['all'])) {
            $this->cache['Post']['all'] = $this->countEntity('Post');
        }

        return $this->cache['Post']['all'];            
    }   
    
    /**
     * Count the number of items in a provided entity
     *
     * @param $entityname
     * @param null $where
     * @param null $parameter
     * @return int
     */
    private function countEntity($entityname, $where = null, $parameter = null)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(a)')->from("Zikula\\DizkusModule\\Entity\\{$entityname}Entity", 'a');
        if (isset($where) && isset($parameter)) {
            $qb->andWhere('a.' . $where . ' = :parameter')->setParameter('parameter', $parameter);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

}
    
    
    /**
     * Counts posts in forums, topics
     * or counts forum users
     *
     * @param $id int the id, forum id
     * @param $type string, defines the id parameter
     * @param $force boolean, default false, if true, do not use cached
     * @return int (depending on type and id)
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
//    public function getStats($id = null, $type = null, $force = false)
//    {
//
//        static $cache = [];
//        
////        switch ($type) {
////            case 'all':
////            case 'allposts':
////                if (!isset($cache[$type])) {
////                    $cache[$type] = $this->countEntity('Post');
////                }
////
////                //return $cache[$type];
////                //break;
////            case 'forum':
////                if (!isset($cache[$type])) {
////                    $cache[$type] = $this->countEntity('Forum');
////                }
////
//////                return $cache[$type];
//////                break;
////            case 'topic':
////                if (!isset($cache[$type][$id])) {
////                    $cache[$type][$id] = $this->countEntity('Post', 'topic', $id);
////                }
////
////                return $cache[$type][$id];
////                break;
////            case 'forumposts':
////                if ($force || !isset($cache[$type][$id])) {
////                    $dql = 'SELECT count(p)
////                        FROM Zikula\DizkusModule\Entity\PostEntity p
////                        WHERE p.topic IN (
////                            SELECT t.topic_id
////                            FROM Zikula\DizkusModule\Entity\TopicEntity t
////                            WHERE t.forum = :forum)';
////                    $query = $this->entityManager->createQuery($dql)->setParameter('forum', $id);
////                    $cache[$type][$id] = $query->getSingleScalarResult();
////                }
////
//////                return $cache[$type][$id];
//////                break;
////            case 'forumtopics':
////                if ($force || !isset($cache[$type][$id])) {
////                    $cache[$type][$id] = $this->countEntity('Topic', 'forum', $id);
////                }
////
//////                return $cache[$type][$id];
//////                break;
////            case 'alltopics':
////                if (!isset($cache[$type])) {
////                    $cache[$type] = $this->countEntity('Topic');
////                }
////
//////                return $cache[$type];
//////                break;
//////            case 'allmembers':
//////                if (!isset($cache[$type])) {
//////                    $cache[$type] = count(UserUtil::getUsers());
//////                }
////
//////                return $cache[$type];
//////                break;
////            case 'lastmember':
////            case 'lastuser':
////                if (!isset($cache[$type])) {
////                    $qb = $this->entityManager->createQueryBuilder();
////                    $qb->select('u')->from('Zikula\DizkusModule\Entity\ForumUserEntity', 'u')->orderBy('u.user_id', 'DESC')->setMaxResults(1);
////                    $forumUser = $qb->getQuery()->getSingleResult();
////                    $user = $forumUser->getUser();
////                    $cache[$type] = $user['uname'];
////                }
////
//////                return $cache[$type];
//////                break;
////            default:
////                throw new \InvalidArgumentException($this->__('Error! Wrong parameters in countstats().'));
////        }
////        
//        return $cache;
//    }
    /**
     * Counts posts in forums, topics
     * or counts forum users
     *
     * @param $args['id'] int the id, depends on 'type' parameter
     * @param $args['type'] string, defines the id parameter
     * @param $args['force'] boolean, default false, if true, do not use cached
     * @return int (depending on type and id)
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
//    public function countstats($args)
//    {
//        $id = isset($args['id']) ? $args['id'] : null;
//        $type = isset($args['type']) ? $args['type'] : null;
//        $force = isset($args['force']) ? (bool) $args['force'] : false;
//        static $cache = array();
//        switch ($type) {
//            case 'all':
//            case 'allposts':
//                if (!isset($cache[$type])) {
//                    $cache[$type] = $this->countEntity('Post');
//                }
//
//                return $cache[$type];
//                break;
//            case 'forum':
//                if (!isset($cache[$type])) {
//                    $cache[$type] = $this->countEntity('Forum');
//                }
//
//                return $cache[$type];
//                break;
//            case 'topic':
//                if (!isset($cache[$type][$id])) {
//                    $cache[$type][$id] = $this->countEntity('Post', 'topic', $id);
//                }
//
//                return $cache[$type][$id];
//                break;
//            case 'forumposts':
//                if ($force || !isset($cache[$type][$id])) {
//                    $dql = 'SELECT count(p)
//                        FROM Zikula\DizkusModule\Entity\PostEntity p
//                        WHERE p.topic IN (
//                            SELECT t.topic_id
//                            FROM Zikula\DizkusModule\Entity\TopicEntity t
//                            WHERE t.forum = :forum)';
//                    $query = $this->entityManager->createQuery($dql)->setParameter('forum', $id);
//                    $cache[$type][$id] = $query->getSingleScalarResult();
//                }
//
//                return $cache[$type][$id];
//                break;
//            case 'forumtopics':
//                if ($force || !isset($cache[$type][$id])) {
//                    $cache[$type][$id] = $this->countEntity('Topic', 'forum', $id);
//                }
//
//                return $cache[$type][$id];
//                break;
//            case 'alltopics':
//                if (!isset($cache[$type])) {
//                    $cache[$type] = $this->countEntity('Topic');
//                }
//
//                return $cache[$type];
//                break;
//            case 'allmembers':
//                if (!isset($cache[$type])) {
//                    $cache[$type] = count(UserUtil::getUsers());
//                }
//
//                return $cache[$type];
//                break;
//            case 'lastmember':
//            case 'lastuser':
//                if (!isset($cache[$type])) {
//                    $qb = $this->entityManager->createQueryBuilder();
//                    $qb->select('u')->from('Zikula\DizkusModule\Entity\ForumUserEntity', 'u')->orderBy('u.user_id', 'DESC')->setMaxResults(1);
//                    $forumUser = $qb->getQuery()->getSingleResult();
//                    $user = $forumUser->getUser();
//                    $cache[$type] = $user['uname'];
//                }
//
//                return $cache[$type];
//                break;
//            default:
//                throw new \InvalidArgumentException($this->translator->__('Error! Wrong parameters in countstats().'));
//        }
//    }
    
    /**
     * setcookies
     *
     * reads the cookie, updates it and returns the last visit date in unix timestamp
     *
     * @param none
     * @return unix timestamp last visit date
     *
     */
//    public function setcookies()
//    {
//        /**
//         * set last visit cookies and get last visit time
//         * set LastVisit cookie, which always gets the current time and lasts one year
//         */
//        $request = ServiceUtil::getManager()->get('request');
//        $path = $request->getBasePath();
//        if (empty($path)) {
//            $path = '/';
//        } elseif (substr($path, -1, 1) != '/') {
//            $path .= '/';
//        }
//        $time = time();
//        CookieUtil::setCookie('DizkusLastVisit', "{$time}", $time + 31536000, $path, null, null, false);
//        $lastVisitTemp = CookieUtil::getCookie('DizkusLastVisitTemp', false, null);
//        $temptime = empty($lastVisitTemp) ? $time : $lastVisitTemp;
//        // set LastVisitTemp cookie, which only gets the time from the LastVisit and lasts for 30 min
//        CookieUtil::setCookie('DizkusLastVisitTemp', "{$temptime}", time() + 1800, $path, null, null, false);
//
//        return $temptime;
//    }  
    

//    /**
//     * Count the number of items in a provided entity
//     *
//     * @param $entityname
//     * @param null $where
//     * @param null $parameter
//     * @return int
//     */
//    private function countEntity($entityname, $where = null, $parameter = null)
//    {
//        $qb = $this->entityManager->createQueryBuilder();
//        $qb->select('count(a)')->from("Zikula\\DizkusModule\\Entity\\{$entityname}Entity", 'a');
//        if (isset($where) && isset($parameter)) {
//            $qb->andWhere('a.' . $where . ' = :parameter')->setParameter('parameter', $parameter);
//        }
//
//        return (int)$qb->getQuery()->getSingleScalarResult();
//    }