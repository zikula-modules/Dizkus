<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Zikula\DizkusModule\Helper;

use ModUtil;
use Zikula\DizkusModule\Entity\ForumEntity;
use Zikula\DizkusModule\Entity\TopicEntity;
use Zikula\DizkusModule\Entity\ForumUserEntity;


use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;
use Zikula\UsersModule\Api\CurrentUserApi;


/**
 * CronHelper
 *
 * @author Kaik
 */
class SynchronizationHelper {
    
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
     * perform sync on all forums, topics and posters
     *
     * @param Boolean $silentMode (unused)
     */
    public function all($silentMode = false)
    {
        $this->forums();
        $this->topics();
        $this->posters();
    }

    /**
     * perform sync on all forums
     *
     * @return Boolean
     */
    public function forums()
    {
        // reset count to zero
        $dql = 'UPDATE Zikula\DizkusModule\Entity\ForumEntity f SET f.topicCount = 0, f.postCount = 0';
        $this->entityManager->createQuery($dql)->execute();
        // order by level asc in order to do the parents first, down to children. This SHOULD keep the count accurate.
        $forums = $this->entityManager
            ->getRepository('Zikula\DizkusModule\Entity\ForumEntity')
            ->findBy(array(), array('lvl' => 'ASC'));
        foreach ($forums as $forum) {
            $this->forum(array('forum' => $forum));
        }

        return true;
    }

    /**
     * recalculate topicCount and postCount counts
     *
     * @param ForumEntity $args['forum']
     * @param Boolean             $args['flush']
     *
     * @return boolean
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
    public function forum($args)
    {
        if (!isset($args['forum'])) {
            throw new \InvalidArgumentException();
        }
        if ($args['forum'] instanceof ForumEntity) {
            $id = $args['forum']->getForum_id();
        } else {
            $id = $args['forum'];
            $args['forum'] = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumEntity', $id);
        }
        // count topics of a forum
        $topicCount = ModUtil::apiFunc($this->name, 'user', 'countstats', array(
                    'type' => 'forumtopics',
                    'id' => $id,
                    'force' => true));
        $args['forum']->setTopicCount($topicCount);
        // count posts of a forum
        $postCount = ModUtil::apiFunc($this->name, 'user', 'countstats', array(
                    'type' => 'forumposts',
                    'id' => $id,
                    'force' => true));
        $args['forum']->setPostCount($postCount);
        $this->entityManager->flush();
        $this->addToParentForumCount($args['forum'], 'Post');
        $this->addToParentForumCount($args['forum'], 'Topic');

        return true;
    }

    /**
     * recursive function to add counts to parents
     * @param ForumEntity $forum
     * @param string              $entity
     */
    private function addToParentForumCount(ForumEntity $forum, $entity = 'Post')
    {
        $parent = $forum->getParent();
        if (!isset($parent)) {
            return;
        }
        $entity = in_array($entity, array('Post', 'Topic')) ? $entity : 'Post';
        $getMethod = "get{$entity}Count";
        $currentParentCount = $parent->{$getMethod}();
        $forumCount = $forum->{$getMethod}();
        $setMethod = "set{$entity}Count";
        $parent->{$setMethod}($currentParentCount + $forumCount);
        $this->entityManager->flush();
        $grandParent = $parent->getParent();
        if (isset($grandParent)) {
            $this->addToParentForumCount($parent, $entity);
        }
    }

    /**
     * perform sync on all topics
     *
     * @return boolean
     */
    public function topics()
    {
        $topics = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\TopicEntity')->findAll();
        foreach ($topics as $topic) {
            $this->topic(array(
                'topic' => $topic,
                'type' => 'forum'));
        }
        // flush?
        return true;
    }

    /**
     * recalcluate Topic replies for one topic
     *
     * @param TopicEntity $args['topic']
     * @param Boolean             $args['flush']
     *
     * @return boolean
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
    public function topic($args)
    {
        if (!isset($args['topic'])) {
            throw new \InvalidArgumentException();
        }
        if ($args['topic'] instanceof TopicEntity) {
            $id = $args['topic']->getTopic_id();
        } else {
            $id = $args['topic'];
            $args['topic'] = $this->entityManager->find('Zikula\DizkusModule\Entity\TopicEntity', $id);
        }
        $flush = isset($args['flush']) ? $args['flush'] : true;
        // count posts of a topic
        $qb = $this->entityManager->createQueryBuilder();
        $replies = $qb->select('COUNT(p)')
            ->from('Zikula\DizkusModule\Entity\PostEntity', 'p')
            ->where('p.topic = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();
        $replies = (int)$replies - 1;
        $args['topic']->setReplyCount($replies);
        if ($flush) {
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * recalculate user posts for all users
     *
     * @return boolean
     */
    public function posters()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $posts = $qb->select('count(p)', 'IDENTITY(d.user) as user_id')
            ->from('Zikula\DizkusModule\Entity\PostEntity', 'p')
            ->leftJoin('p.poster', 'd')
            ->groupBy('d.user')
            ->getQuery()
            ->getArrayResult();
        foreach ($posts as $post) {
            $forumUser = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumUserEntity', $post['user_id']);
            if (!$forumUser) {
                $forumUser = new ForumUserEntity($post['user_id']);
            }
            $forumUser->setPostCount($post[1]);
        }
        $this->entityManager->flush();

        return true;
    }

    /**
     * reset the last post in a forum due to movement
     * @param ForumEntity $args['forum']
     * @param Boolean             $args['flush'] default: true
     *
     * @return boolean|void
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
    public function forumLastPost($args)
    {
        if (!isset($args['forum']) || !$args['forum'] instanceof ForumEntity) {
            throw new \InvalidArgumentException();
        }
        $flush = isset($args['flush']) ? $args['flush'] : true;
        // get the most recent post in the forum
        $dql = 'SELECT t FROM Zikula\DizkusModule\Entity\TopicEntity t
            WHERE t.forum = :forum
            ORDER BY t.topic_time DESC';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('forum', $args['forum']);
        $query->setMaxResults(1);
        $topic = $query->getOneOrNullResult();
        if (isset($topic)) {
            $args['forum']->setLast_post($topic->getLast_post());
        }
        // recurse up the tree
        $parent = $args['forum']->getParent();
        if (isset($parent)) {
            $this->forumLastPost(array(
                'forum' => $parent,
                'flush' => false));
        }
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * reset the last post in a topic due to movement
     * @param TopicEntity $args['topic']
     * @param Boolean             $args['flush']
     *
     * @return boolean|void
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     */
    public function topicLastPost($args)
    {
        if (!isset($args['topic']) || !$args['topic'] instanceof TopicEntity) {
            throw new \InvalidArgumentException();
        }
        $flush = isset($args['flush']) ? $args['flush'] : true;
        // get the most recent post in the topic
        $dql = 'SELECT p FROM Zikula\DizkusModule\Entity\PostEntity p
            WHERE p.topic = :topic
            ORDER BY p.post_time DESC';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('topic', $args['topic']);
        $query->setMaxResults(1);
        $post = $query->getSingleResult();
        $args['topic']->setLast_post($post);
        $args['topic']->setTopic_time($post->getPost_time());
        if ($flush) {
            $this->entityManager->flush();
        }
    }
    
    
}