<?php

/**
 * Copyright Dizkus Team 2012.
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 *
 * @see https://github.com/zikula-modules/Dizkus
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\DizkusModule\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Bundle\HookBundle\Hook\Hook;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\Hook\ProcessHook;
use Zikula\DizkusModule\Entity\PostEntity;
use Zikula\DizkusModule\Entity\TopicEntity;
use Zikula\DizkusModule\Entity\ForumEntity;
use Zikula\DizkusModule\Helper\SynchronizationHelper;
use Zikula\DizkusModule\Security\Permission;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\UsersModule\Api\CurrentUserApi;

/**
 * Topic manager
 */
class TopicManager
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

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

    /**
     * @var Permission
     */
    private $permission;

    /**
     * @var VariableApi
     */
    private $variableApi;

    /**
     * @var forumUserManagerService
     */
    private $forumUserManagerService;

    /**
     * @var ForumManagerService
     */
    private $forumManagerService;

    /**
     * @var synchronizationHelper
     */
    private $synchronizationHelper;

    /**
     * Managed topic
     *
     * @var TopicEntity
     */
    private $_topic;

    /**
     * Managed topic posts
     *
     * @var ArrayCollection
     */
    private $posts;

    /**
     * @var string
     */
    protected $name;

    /**
     * Construct the manager
     *
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     * @param RequestStack $requestStack
     * @param EntityManager $entityManager
     * @param CurrentUserApi $userApi
     * @param Permission $permission
     * @param VariableApi $variableApi
     * @param ForumUserManager $forumUserManagerService
     * @param ForumManager $forumManagerService
     * @param SynchronizationHelper $synchronizationHelper
     */
    public function __construct(
            TranslatorInterface $translator,
            RouterInterface $router,
            RequestStack $requestStack,
            EntityManager $entityManager,
            CurrentUserApi $userApi,
            Permission $permission,
            VariableApi $variableApi,
            ForumUserManager $forumUserManagerService,
            ForumManager $forumManagerService,
            SynchronizationHelper $synchronizationHelper
         ) {
        $this->name = 'ZikulaDizkusModule';
        $this->translator = $translator;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getMasterRequest();
        $this->entityManager = $entityManager;
        $this->userApi = $userApi;
        $this->permission = $permission;
        $this->variableApi = $variableApi;

        $this->forumUserManagerService = $forumUserManagerService;
        $this->forumManagerService = $forumManagerService;
        $this->synchronizationHelper = $synchronizationHelper;
    }

    /**
     * Start managing
     *
     * @param integer $id
     * @param TopicEntity $topic
     *
     * @return TopicManager
     */
    public function getManager($id = null, TopicEntity $topic = null, $create = true)
    {
        if (isset($topic)) {
            // topic has been injected
            $this->_topic = $topic;
        } elseif ($id > 0) {
            // find existing topic
            $this->_topic = $this->entityManager->find('Zikula\DizkusModule\Entity\TopicEntity', $id);
            if ($this->exists()) {
                $this->managedForum = $this->forumManagerService->getManager(null, $this->_topic->getForum());
            }
        } elseif ($create) {
            // create new topic
            $this->create();
        }

        return $this;
    }

    /**
     * Start managing by hook
     *
     * @param Hook $hook
     * @param boolean $create
     *
     * @return TopicManager
     */
    public function getHookedTopicManager(Hook $hook, $create = true)
    {
        $topic = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\TopicEntity')->getHookedTopic($hook);
        if ($topic) {
            // topic has been injected
            $this->_topic = $topic;
        } elseif ($create) {
            // create new topic
            $this->create();
        }

        return $this;
    }

    /**
     * Check if topic exists
     *
     * @return bool
     */
    public function create()
    {
        $this->_topic = new TopicEntity();

        return $this;
    }

    /**
     * Check if topic exists
     *
     * @return bool
     */
    public function exists()
    {
        return $this->_topic ? true : false;
    }

    /**
     * Return topic as doctrine2 object
     *
     * @return TopicEntity
     */
    public function get()
    {
        return $this->_topic;
    }

    /**
     * Return topic id
     *
     * @return int
     */
    public function getId()
    {
        return $this->_topic->getId();
    }

    /**
     * Update topic
     *
     * @param $topic
     */
    public function update($topic = null)
    {
        if ($topic instanceof TopicEntity) {
            $this->_topic = $topic;
        }

        return $this;
    }

    /**
     * Store topic
     *
     * @return TopicManager $this
     */
    public function store($noSync = false)
    {
        // write topic
        if ($noSync) {
            $this->noSync();
        }

        $this->entityManager->persist($this->_topic);
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Delete a topic
     *
     * This function deletes a topic given by id or object
     *
     * @param $topic The topic's id or object
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return int the forum's id for redirecting
     */
    public function delete()
    {
        $this->entityManager->remove($this->_topic);
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Return topic as array
     *
     * @return mixed array or false
     */
    public function toArray()
    {
        if (!$this->_topic) {
            return false;
        }

        return $this->_topic->toArray();
    }

    /**
     * Return topic id
     *
     * @return int
     */
    public function noSync()
    {
        $this->_topic->noSync();

        return $this;
    }

    /**
     * Return topic title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_topic->getTitle();
    }

    /**
     * Set topic title
     *
     * @param string $title Topic title
     *
     * @return TopicManager
     */
    public function setTitle($title)
    {
        $this->_topic->setTitle($title);

        return $this;
    }

    /**
     * Return topic forum id
     *
     * @return int
     */
    public function getForumId()
    {
        return $this->_topic->getForum()->getId();
    }

    /**
     * Return topic forum as doctrine 2 object
     *
     * @return ForumEntity
     */
    public function getForum()
    {
        return $this->_topic->getForum();
    }

    /**
     * Return managed topic forum
     *
     * @return ForumManager
     */
    public function getManagedForum()
    {
        return $this->forumManagerService->getManager(null, $this->_topic->getForum());
    }

    /**
     * Get the Poster as managed forum user
     *
     * @return ForumUserManager
     */
    public function getManagedPoster()
    {
        return $this->forumUserManagerService->getManager($this->_topic->getPosterId());
    }

    /**
     * Get the Poster as managed forum user
     *
     * @return ForumUserManager
     */
    public function getFirstPost()
    {
        return $this->_topic->getFirstPost();
    }

    /**
     * Set topic last post
     *
     * @param PostEntity $lastPost Last post entity
     *
     * @return TopicManager
     */
    public function setLastPost(PostEntity $lastPost)
    {
        $this->_topic->setLast_post($lastPost);

        return $this;
    }

    /**
     * Load paged posts
     *
     * @param integer $start Start position
     *
     * @param string|bool $postsOrder
     *
     * @return Collection
     */
    public function loadPosts($start = 0, $postsOrder = null)
    {
        if (empty($postsOrder)) {
            $postsOrder = $this->variableApi->get($this->name, 'post_sort_order');
        }

        // Do a new query in order to limit maxresults, firstresult, order, etc.
        // @todo move to topic repository
        $query = $this->entityManager->createQueryBuilder()
            ->select('p, u, r')
            ->from('Zikula\DizkusModule\Entity\PostEntity', 'p')
            ->where('p.topic = :topicId')
            ->setParameter('topicId', $this->_topic->getId())
            ->leftJoin('p.poster', 'u')
            ->leftJoin('u.rank', 'r')
            ->orderBy('p.post_time', $postsOrder)
            ->getQuery();
        $query->setFirstResult($start)
              ->setMaxResults($this->variableApi->get($this->name, 'posts_per_page'));

        $this->posts = new Paginator($query, false);

        return $this;
    }

    /**
     * Return posts of a topic as doctrine2 collection
     *
     * @return ArrayCollection
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * Posts count
     *
     * @return integer
     */
    public function getPostsCount()
    {
        return $this->posts->count();
    }

    /**
     * Get the reply count
     *
     * @return integer
     */
    public function getReplyCount()
    {
        return $this->_topic->getReplyCount();
    }

    /**
     * Increment topic replies count
     *
     * @return TopicManager
     */
    public function incrementRepliesCount()
    {
        $this->_topic->incrementReplyCount();

        return $this;
    }

    /**
     * Decrement topic replies count
     */
    public function decrementRepliesCount()
    {
        $this->_topic->decrementReplyCount();

        return $this;
    }

    /**
     * Increment topic views count
     *
     * @return TopicManager
     */
    public function incrementViewsCount()
    {
        $this->_topic->incrementViewCount();

        return $this;
    }

    /**
     * Get topic preview
     *
     * @param ProcessHook $hook
     */
    public function getPreview()
    {
        return $this->_firstPost;
    }

    /**
     * Get the next topic (by time) in the same Forum
     *
     * @return int
     */
    public function getNext()
    {
        return $this->getAdjacent('>', 'ASC');
    }

    /**
     * Get the previous topic (by time) in the same Forum
     *
     * @return int
     */
    public function getPrevious()
    {
        return $this->getAdjacent('<', 'DESC');
    }

    /**
     * Get the adjacent topic (by time) in the same Forum
     *
     * @param $oper string less than or greater than operator < or >
     * @param $dir string Sort direction ASC/DESC
     *
     * @return int
     */
    private function getAdjacent($oper, $dir)
    {
        $dql = "SELECT t.id FROM Zikula\DizkusModule\Entity\TopicEntity t
            WHERE t.topic_time {$oper} :time
            AND t.forum = :forum
            AND t.sticky = 0
            ORDER BY t.topic_time {$dir}";
        $result = $this->entityManager->createQuery($dql)
            ->setParameter('time', $this->_topic->getTopic_time())
            ->setParameter('forum', $this->_topic->getForum())
            ->setMaxResults(1)
            ->getScalarResult();
        if ($result) {
            return $result[0]['id'];
        } else {
            return $this->_topic->getId(); // return current value (checks in template for this)
        }
    }

    /**
     * Get topic page
     *
     * Uses the number of replyCount and the posts_per_page settings to determine the page
     * number of the last post in the thread. This is needed for easier navigation.
     *
     * @param $replyCount int number of topic replies
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return int page number of last posting in the thread
     */
    public function getTopicPage($replyCount)
    {
        if (!isset($replyCount) || !is_numeric($replyCount) || $replyCount < 0) {
            throw new \InvalidArgumentException();
        }
        // get some environment
        $posts_per_page = $this->variableApi->get($this->name, 'posts_per_page');
        if ($this->userApi->isLoggedIn()) {
            $user_id = $this->request->getSession()->get('uid');
            $managedForumUser = $this->forumUserManagerService->getManager($user_id);
            $postSortOrder = $managedForumUser->getPostOrder();
        } else {
            $postSortOrder = $this->variableApi->get($this->name, 'post_sort_order');
        }

        $last_page = 1;
        if ('ASC' == $postSortOrder) {
            // +1 for the initial posting
            $last_page = floor($replyCount / $posts_per_page) * $posts_per_page + 1;
        }
        // if not ASC then DESC which means latest topic is on top anyway...
        return $last_page;
    }

    /**
     * set topic sticky.
     *
     * @return bool
     */
    public function sticky()
    {
        $this->_topic->sticky();

        return $this;
    }

    /**
     * Set topic unsticky
     *
     * @return bool
     */
    public function unsticky()
    {
        $this->_topic->unsticky();

        return $this;
    }

    /**
     * Lock topic
     *
     * @return bool
     */
    public function lock()
    {
        $this->_topic->lock();

        return $this;
    }

    /**
     * Unlock topic
     *
     * @return bool
     */
    public function unlock()
    {
        $this->_topic->unlock();

        return $this;
    }

    /**
     * Set topic solved
     *
     * @param int $status
     *
     * @return bool
     */
    public function solve($status)
    {
        $this->_topic->setSolved($status);

        return $this;
    }

    /**
     * Set topic unsolved
     *
     * @return bool
     */
    public function unsolve($status = -1)
    {
        $this->_topic->setSolved($status);

        return $this;
    }

    /**
     * Move topic
     *
     * This function moves a given topic to another forum
     *
     * @param $forum ForumEntity the destination forum
     * @param $createshadowtopic   boolean true = create shadow topic
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return void
     */
    public function move(ForumEntity $forum, $createshadowtopic = false)
    {
        if ($this->getForumId() != $forum->getId()) {
            if (true === $createshadowtopic) {
                // prepare shadow data
                $newUrl = $this->router->generate('zikuladizkusmodule_topic_viewtopic', ['topic' => $this->getId()]);
                $title = $this->translator->__f('*** Moved:* \'%title\' * to * \'%forum\' ***', ['%title' => $this->getTitle(), '%forum' => $forum->getName()]);
                $message = $this->translator->__('The original topic has been moved').' <a title="'.$this->translator->__('moved').'" href="'.$newUrl.'">'.$this->translator->__('here').'</a>.';
                // moderator that performs move action
                $poster = $this->forumUserManagerService->getManager();
                // create shadow topic
                $shadowTopic = new TopicEntity();
                // update shadow topic with new data
                $shadowTopic->setTitle($title);
                $shadowTopic->setForum($this->getForum());
                $shadowTopic->setTopic_time($this->get()->getTopic_time());
                $shadowTopic->setPoster($poster->get());
                $shadowTopic->lock();
                // create shadow first post
                $shadowFirstPost = new PostEntity();
                $shadowFirstPost->setIsFirstPost(true);
                $shadowFirstPost->setAttachSignature(false);
                $shadowFirstPost->setTitle($title);
                $shadowFirstPost->setPostText($message);
                $shadowFirstPost->setPoster($poster->get());
                $shadowFirstPost->setTopic($shadowTopic);
                // shadow topic set shadow post
                $shadowTopic->addPost($shadowFirstPost);
                $this->entityManager->persist($shadowTopic);
                $this->entityManager->flush();
            }
            $this->_topic->setForum($forum);
        }

        return $this;
    }

    /**
     * Split the topic at the provided post
     *
     * @param PostEntity        $post post before which split will occur
     * @param string|null       $subject new topic subject optional default null
     * @param ForumEntity|null  $destinationForum Destination forum optional default null
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return $this
     */
    public function split(PostEntity $post, $subject = null, ForumEntity $destinationForum = null)
    {
        // prepare data
        $title = !is_null($subject)
            ? $subject
            : $this->translator->__('Split') . ': ' . $this->getTitle();

        $forum = !is_null($destinationForum)
            ? $destinationForum
            : $this->getForum();

        // prepare first post
        $post->setIsFirstPost(true);
        $post->setTitle($title);

        // create new topic
        $newTopic = new TopicEntity();
        $newTopic->setPoster($post->getPoster());
        $newTopic->setTitle($title);
        $newTopic->setForum($forum);

        // add first post
        $newTopic->addPost($post);

        // update posts
        $dql = 'SELECT p from Zikula\DizkusModule\Entity\PostEntity p
            WHERE p.topic = :topic
            AND p.post_time > :post_time
            ORDER BY p.post_time ASC';

        $query = $this->entityManager
                    ->createQuery($dql)
                    ->setParameter('topic', $this->get())
                    ->setParameter('post_time', $post->getPost_time());

        /* @var $posts Array of Zikula\Module\DizkusModule\Entity\PostEntity */
        $posts = $query->getResult();
//        dump($posts);
//        // update the topic_id in the postings
        foreach ($posts as $post) {
            $newTopic->addPost($post);
        }

        $this->entityManager->persist($newTopic);
        // must flush here so sync listener gets correct information
        $this->entityManager->flush();

        $this->sync();
        $this->store(true);

        // $post will have new topic id
        return $this;
    }

    /**
     * Joins two topics together
     *
     * @param $destinationTopic object the target topic that will contain the posts (destination)
     *
     * @param boolean $createshadowtopic true = leave shadow topic (locked)
     *
     * @param boolean $append true add posts add the end of destination by changing post dates false = mix
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return int Destination topic ID
     */
    public function join(TopicEntity $destinationTopic, $createshadowtopic = false, $append = true)
    {
        // save last post time of destination topic as reference where start
        $previousLastPostTime = $destinationTopic->getLast_post()->getPost_time();
        // move posts from Origin to Destination topic
        $originPosts = $this->get()->getPosts();
        foreach ($originPosts as $post) {
            //this way is important!
            $destinationTopic->addPost($post);
//            $post->setTopic($destinationTopic);
            if ($append && $post->getPost_time() <= $previousLastPostTime) {
                $post->setPost_time($previousLastPostTime->modify('+1 minute'));
            }
            $previousLastPostTime = $post->getPost_time();
        }

        // @todo this line should be in SyncListener as it belongs to sync domain
        // it is here to trigger preUpdate on TopicEntity when posts are added
        $destinationTopic->setReplyCount($destinationTopic->getPosts()->count() - 1);

        $this->entityManager->persist($destinationTopic);
        $this->entityManager->flush();

        if (true === $createshadowtopic) {
            // prepare shadow data
            $newUrl = $this->router->generate('zikuladizkusmodule_topic_viewtopic', ['topic' => $destinationTopic->getId()]);
            $title = $this->translator->__f('*** Joined with * \'%topic\' ', ['%topic' => $destinationTopic->getTitle()]);
            $message = $this->translator->__('The original postings from this topic has been ').' <a title="'.$this->translator->__('joined with').'" href="'.$newUrl.'">'.$this->translator->__('here').'</a>.';
            // moderator that performs move action
            $poster = $this->forumUserManagerService->getManager();
            //update shadow topic with new data
            $this->lock();
            // create shadow first post
            $shadowFirstPost = new PostEntity();
            $shadowFirstPost->setIsFirstPost(true);
            $shadowFirstPost->setAttachSignature(false);
            $shadowFirstPost->setTitle($title);
            $shadowFirstPost->setPostText($message);
            $shadowFirstPost->setPoster($poster->get());
//            $shadowFirstPost->setTopic($this->get());
            //shadow topic set shadow post
            $this->setTitle($title);
            $this->get()->addPost($shadowFirstPost);
            $this->store();
        }

        return $this;
    }

    /**
     * manually sync topic
     *
     * @todo it might be moved
     *
     * @return $this
     */
    public function sync()
    {
        $firstPost = $this->get()->getPosts()->first();
        $lastPost = $this->get()->getPosts()->last();
        $totalPosts = $this->get()->getPosts()->count();
        foreach ($this->get()->getPosts() as $post) {
            $post->setIsFirstPost(false);
        }
        // post sync
        $firstPost->setIsFirstPost(true);
        //topic sync
        $this->get()->setLast_Post($lastPost);
        $this->get()->setReplyCount($totalPosts - 1);

        return $this;
    }

    /**
     * Add hook data to topic
     *
     * @param ProcessHook $hook
     */
    public function setHookData(ProcessHook $hook)
    {
        $this->_topic->setHookedModule($hook->getCaller());
        $this->_topic->setHookedObjectId($hook->getId());
        $this->_topic->setHookedAreaId($hook->getAreaId());
        $this->_topic->setHookedUrlObject($hook->getUrl());

        return $this;
    }

    /**
     * topic by reference
     *
     * Gets a topic reference as parameter and delivers the internal topic id used for Dizkus as comment module
     *
     * @param string $reference the reference
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return array Topic data as array
     */
    public function getIdByReference($reference)
    {
        if (empty($reference)) {
            throw new \InvalidArgumentException();
        }

        return $this->entityManager->getRepository('Zikula\DizkusModule\Entity\TopicEntity')->findOneBy(['reference' => $reference])->toArray();
    }
}

// split sync
//        $this->synchronizationHelper->topicLastPost($managedTopic->get(), true);
        //ModUtil::apiFunc($this->name, 'sync', 'topicLastPost', ['topic' => $managedTopic->get(), 'flush' => true]);
//        $oldReplyCount = $managedTopic->get()->getReplyCount();
//        $managedTopic->get()->setReplyCount($oldReplyCount - count($posts));
        // update new topic with post data
        //
//        $newTopic->setLast_post($post);
//        $newTopic->setReplyCount(count($posts) - 1);
//        $newTopic->setTopic_time($post->getPost_time());
//
        // resync topic totals, etc
        //
//        $this->synchronizationHelper->forum($newTopic->getForum(), false);
        //ModUtil::apiFunc($this->name, 'sync', 'forum', ['forum' => $newTopic->getForum(), 'flush' => false]);

//        if (!$managedDestinationTopic instanceof self) {
//            $this->request->getSession()->getFlashBag()->add('error', $this->translator->__f(' Join function requires "%1$s" and "%2$s" to be instance of TopicManager.', ['managedOriginTopic', 'managedDestinationTopic']));
//
//            throw new \InvalidArgumentException();
//        }
        // resync destination topic and all forums
//        $this->synchronizationHelper->topic($managedDestinationTopic->get(), true);
//        $this->synchronizationHelper->forum($originTopicForum, false);
//        $this->synchronizationHelper->forumLastPost($originTopicForum, true);
//        $this->synchronizationHelper->forum($managedDestinationTopic->get()->getForum(), false);
//        $this->synchronizationHelper->forumLastPost($managedDestinationTopic->get()->getForum(), true);
            // set new forum
//            $this->_topic->getForum()->setLast_post(null);
//            $oldForum; // needed for sync and consistency
//            $this->entityManager->persist($oldForum);
//            $this->entityManager->flush();
//        if (!isset($topic) || !$topic instanceof TopicEntity) {
//            if (!isset($topic_id)) {
//                throw new \InvalidArgumentException();
//            }
//            $topic = $this->entityManager->find('Zikula\DizkusModule\Entity\TopicEntity', $topic_id);
//        }
//        $managedTopic = $this->getManager(null, $topic);
            // re-sync all forum counts and last posts
//            $previousForumLocation = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumEntity', $oldForumId);
            // we need to set it to null here so it can be easly synchronized later forumLastPost id is unique
//            $previousForumLocation->setLast_post();
//            $this->synchronizationHelper->forumLastPost($previousForumLocation, false);
//            $this->synchronizationHelper->forumLastPost($forum, false);
//            $this->synchronizationHelper->forum($oldForumId, false);
//            $this->synchronizationHelper->forum($forum, true);
        // because posts are owning side it is not that simple
        // need to check what events are fired and
        //lets remove posts first
        ////        $posts = $topic->getPosts();
//        foreach ($posts as $post) {
//            $post->getPoster()->decrementPostCount();
//            $forum->decrementPostCount();
//        }
        // decrement topicCount
//        $forum->decrementTopicCount();
        // update the db
        // this should fire lots of events
//        $this->_topic->getPosts()->clear();
//        $this->entityManager->flush();

//        if (is_numeric($topic)) {
//            // @todo what if topic not found?
//            $topic = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\TopicEntity')->find($topic);
//        }
//
//        if (!$topic instanceof TopicEntity) {
//            throw new \InvalidArgumentException();
//        }

//        $posts = $topic->getPosts();
//        foreach ($posts as $post) {
//            $post->getPoster()->decrementPostCount();
//            $forum->decrementPostCount();
//        }
        // decrement topicCount
//        $forum->decrementTopicCount();
        // update the db
//        $this->entityManager->flush();
        // delete the topic
//        // call sync event
//        $this->synchronizationHelper->forum($forum, false);
//        $this->synchronizationHelper->forumLastPost($forum, true);

//
//    /**
//     * Prepare new topic from recived data
//     *
//     * @param int    $data['forum_id']
//     * @param string $data['message']
//     * @param bool   $data['attachSignature']
//     * @param string $data['title']
//     * @param bool   $data['subscribe_topic']
//     */
//    public function create($data)
//    {
        // @todo this should be done in post event
        //$data['message'] = ModUtil::apiFunc($this->name, 'user', 'dzkstriptags', $data['message']);
        //$data['title'] = ModUtil::apiFunc($this->name, 'user', 'dzkstriptags', $data['title']);

        //$this->_firstPost->setTitle($data['title']);
        //$this->_firstPost->setTopic($this->_topic);

        //$this->_subscribe = $data['subscribeTopic'];
        //unset($data['subscribeTopic']);

        //$this->_forumId = $data['forum_id'];
        //$this->managedForum = $this->forumManagerService->getManager($this->_forumId);
        //$this->_topic->setForum($this->managedForum->get());

        //unset($data['forum_id']);
        //$solveStatus = isset($data['isSupportQuestion']) && ($data['isSupportQuestion'] == 1) ? -1 : 0; // -1 = support request
        //$this->_topic->setSolved($solveStatus);
        //unset($data['isSupportQuestion']);

        //$this->_topic->setLast_post($this->_firstPost);
        //$this->_topic->merge($data);

//        $managedForumUser = $this->forumUserManagerService->getManager();
//        if($managedForumUser->isAnonymous()){
//            $managedForumUser = $this->forumUserManagerService->getManager($this->variableApi->get($this->name, 'defaultPoster', 2));
//        }
//
//        $this->_firstPost->setPoster($managedForumUser->get());
//        $this->_topic->setPoster($managedForumUser->get());
//    }
//
       //$this->entityManager->persist($this->_firstPost);
        // increment post count
        //$managedForumUser = $this->forumUserManagerService->getManager();
        //$managedForumUser->incrementPostCount();
        //$this->getManagedForum()->incrementPostCount();
        //$this->getManagedForum()->incrementTopicCount();
        //$this->getManagedForum()->setLastPost($this->_firstPost);
        // subscribe
//        if ($this->_subscribe) {
//            $managedForumUser->subscribeTopic($this->_topic);
//        }

//    /**
//     * Find last post by post_time and set
//     */
//    public function resetLastPost($flush = false)
//    {
////        $posts = $this->_topic
////                        ->getPosts()
////                            ->matching(
////                                Criteria::create()
////                                ->orderBy(['post_time' => Criteria::DESC])
////                                ->setMaxResults(1)
////                            );
////
////        $this->setLastPost($posts->first());
//        // update topic time...
//        // topic time is set on create, then updated here (and reply etc) to last post date
//        // information about when topic was created is kind of lost (same as first post)
//        // we use this property for ordering but this should be another field @todo
//        // recent topics can mean recently replayed or recently added
////        $this->_topic->setTopic_time($posts->first()->getPost_time());
//
////        if ($flush) {
////            $this->entityManager->flush();
////        }
//
//        return $this;
//    }
