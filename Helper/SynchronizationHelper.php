<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\DizkusModule\Helper;

use Doctrine\ORM\EntityManager;
use Zikula\DizkusModule\Entity\ForumEntity;
use Zikula\DizkusModule\Entity\ForumUserEntity;
use Zikula\DizkusModule\Entity\TopicEntity;

/**
 * SynchronizationHelper
 *
 * @author Kaik
 */
class SynchronizationHelper
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CountHelper
     */
    private $countHelper;

    public function __construct(
            EntityManager $entityManager,
            CountHelper $countHelper
         ) {
        $this->name = 'ZikulaDizkusModule';
        $this->entityManager = $entityManager;
        $this->countHelper = $countHelper;
    }

    /**
     * Recalculate topicCount and postCount counts
     *
     * @param ForumEntity $forum
     * @param bool        $flush
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return bool
     */
    public function forum($forum, $flush = false)
    {
        if (!isset($forum)) {
            throw new \InvalidArgumentException();
        }
        if ($forum instanceof ForumEntity) {
            $id = $forum->getForum_id();
        } else {
            $id = $forum;
            $forum = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumEntity', $id);
        }

        $topicCount = $this->countHelper->getForumTopicsCount($id, $force = true);
        $forum->setTopicCount($topicCount);

        $postCount = $this->countHelper->getForumPostsCount($id, $force = true);
        $forum->setPostCount($postCount);

        if ($flush) {
            $this->entityManager->flush();
        }

        $this->addToParentForumCount($forum, 'Post');
        $this->addToParentForumCount($forum, 'Topic');

        return true;
    }

    /**
     * Recursive function to add counts to parents
     *
     * @param string      $entity
     */
    private function addToParentForumCount(ForumEntity $forum, $entity = 'Post')
    {
        $parent = $forum->getParent();
        if (!isset($parent)) {
            return;
        }
        $entity = in_array($entity, ['Post', 'Topic']) ? $entity : 'Post';
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
     * Reset the last post in a forum due to movement
     *
     * @param ForumEntity $forum
     * @param bool        $flush default: true
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return bool|void
     */
    public function forumLastPost($forum, $flush = true)
    {
        if (!isset($forum) || !$forum instanceof ForumEntity) {
            throw new \InvalidArgumentException();
        }

        // get the most recent post in the forum
        $dql = 'SELECT t FROM Zikula\DizkusModule\Entity\TopicEntity t
            WHERE t.forum = :forum
            ORDER BY t.topic_time DESC';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('forum', $forum);
        $query->setMaxResults(1);
        $topic = $query->getOneOrNullResult();
        if (isset($topic)) {
            $forum->setLast_post($topic->getLast_post());
        }
        // recurse up the tree
        $parent = $forum->getParent();
        if (isset($parent)) {
            $this->forumLastPost($parent, false);
        }
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Recalculate Topic replies for one topic
     *
     * @param TopicEntity $topic
     * @param bool        $flush
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return bool
     */
    public function topic($topic, $flush = true)
    {
        if (!isset($topic)) {
            throw new \InvalidArgumentException();
        }
        if ($topic instanceof TopicEntity) {
            $id = $topic->getId();
        } else {
            $id = $topic;
            $topic = $this->entityManager->find('Zikula\DizkusModule\Entity\TopicEntity', $id);
        }
        // count posts of a topic
        $qb = $this->entityManager->createQueryBuilder();
        $posts = $qb->select('COUNT(p)')
            ->from('Zikula\DizkusModule\Entity\PostEntity', 'p')
            ->where('p.topic = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();
        $replies = (int) $posts - 1;
        $topic->setReplyCount($replies);
        if ($flush) {
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * Reset the last post in a topic due to movement
     *
     * @param TopicEntity $topic
     * @param bool        $flush
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return bool|void
     */
    public function topicLastPost($topic, $flush = true)
    {
        if (!isset($topic) || !$topic instanceof TopicEntity) {
            throw new \InvalidArgumentException();
        }

        // get the most recent post in the topic
        $dql = 'SELECT p FROM Zikula\DizkusModule\Entity\PostEntity p
            WHERE p.topic = :topic
            ORDER BY p.post_time DESC';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('topic', $topic);
        $query->setMaxResults(1);
        $post = $query->getSingleResult();
        $topic->setLast_post($post);
        $topic->setTopic_time($post->getPost_time());
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
// removed
//    /**
//     * Recalculate user posts for all users
//     *
//     * @return bool
//     */
//    public function posters()
//    {
//        $qb = $this->entityManager->createQueryBuilder();
//        $posts = $qb->select('count(p)', 'IDENTITY(d.user) as user')
//            ->from('Zikula\DizkusModule\Entity\PostEntity', 'p')
//            ->leftJoin('p.poster', 'd')
//            ->groupBy('d.user')
//            ->getQuery()
//            ->getArrayResult();
//
//        foreach ($posts as $post) {
//            $forumUser = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumUserEntity', $post['user']);
//            if (!$forumUser) {
//                $forumUser = new ForumUserEntity($post['user']);
//            }
//            $forumUser->setPostCount($post[1]);
//        }
//        $this->entityManager->flush();
//
//        return true;
//    }
//    /**
//     * Perform sync on all forums, topics and posters.
//     *
//     * @param bool $silentMode (unused)
//     */
//    public function all($silentMode = false)
//    {
//        $this->forums();
//        $this->topics();
//        $this->posters();
//    }

//    /**
//     * Perform sync on all forums
//     *
//     * @return bool
//     */
//    public function forums()
//    {
//        // reset count to zero
//        $dql = 'UPDATE Zikula\DizkusModule\Entity\ForumEntity f SET f.topicCount = 0, f.postCount = 0';
//        $this->entityManager->createQuery($dql)->execute();
//        // order by level asc in order to do the parents first, down to children. This SHOULD keep the count accurate.
//        $forums = $this->entityManager
//            ->getRepository('Zikula\DizkusModule\Entity\ForumEntity')
//            ->findBy([], ['lvl' => 'ASC']);
//        foreach ($forums as $forum) {
//            $this->forum($forum);
//        }
//
//        return true;
//    }
//    /**
//     * Perform sync on all topics
//     *
//     * @return bool
//     */
//    public function topics()
//    {
//        $topics = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\TopicEntity')->findAll();
//        foreach ($topics as $topic) {
//            $this->topic($topic, 'forum');
//        }
//        // flush?
//        return true;
//    }
