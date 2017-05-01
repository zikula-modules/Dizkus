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
//use Doctrine\Common\Collections\AbstractLazyCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\DizkusModule\Entity\PostEntity;
use Zikula\DizkusModule\Helper\SynchronizationHelper;
use Zikula\DizkusModule\Security\Permission;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\UsersModule\Api\CurrentUserApi;

/**
 * Post manager
 *
 */
class PostManager
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
     * @var VariableApi
     */
    private $forumManagerService;

    /**
     * @var VariableApi
     */
    private $topicManagerService;

    /**
     * @var synchronizationHelper
     */
    private $synchronizationHelper;

    /**
     * Managed post
     *
     * @var PostEntity
     */
    private $_post;

    public function __construct
    (
        TranslatorInterface $translator,
        RouterInterface $router,
        RequestStack $requestStack,
        EntityManager $entityManager,
        CurrentUserApi $userApi,
        Permission $permission,
        VariableApi $variableApi,
        ForumUserManager $forumUserManagerService,
        ForumManager $forumManagerService,
        TopicManager $topicManagerService,
        SynchronizationHelper $synchronizationHelper
    )
    {
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
        $this->topicManagerService = $topicManagerService;
        $this->synchronizationHelper = $synchronizationHelper;
    }

    /**
     * Start managing
     *
     * @return PostManager
     */
    public function getManager($id = null, PostEntity $post = null)
    {
        if ($post instanceof PostEntity){
            $this->_post = $post;
            return $this;
        }

        if ($id > 0) {
            $this->_post = $this->entityManager->find('Zikula\DizkusModule\Entity\PostEntity', $id);
        } else {
            $this->_post = new PostEntity();
        }

        return $this;
    }

    /**
     * Check if topic exists
     *
     * @return bool
     */
    public function exists()
    {
        return $this->_post ? true : false;
    }

    /**
     * Get the Post entity
     *
     * @return PostEntity
     */
    public function get()
    {
        return $this->_post;
    }

    /**
     * Get post id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->_post->getId();
    }

    /**
     * Get post as array
     *
     * @return mixed array or false
     */
    public function toArray()
    {
        if (!$this->_post) {
            return [];
        }

        $post = $this->_post->toArray();

        return $post;
    }

    /**
     * Create a post from provided data but do not yet persist
     *
     * @todo Add create validation
     * @todo event
     *
     * @return bool
     */
    public function create($data = null)
    {
        if (!is_null($data)) {
            $this->_topic = $this->topicManagerService->getManager($data['topic_id']);
            $this->_post->setTopic($this->_topic->get());
            unset($data['topic_id']);
            $this->_post->merge($data);
        } else {
            throw new \InvalidArgumentException($this->translator->__('Cannot create Post, no data provided.'));
        }
        $managedForumUser = $this->forumUserManagerService->getManager();
        $this->_post->setPoster($managedForumUser->get());

        return $this;
    }

    /**
     * Update post
     *
     * @param array/object $data Post data or post object to save
     *
     * @return bool
     */
    public function update($data = null)
    {
        if (is_null($data)) {
            throw new \InvalidArgumentException($this->translator->__('Cannot create Post, no data provided.'));
        } elseif ($data instanceof PostEntity) {
            $this->_post = $data;
        } elseif (is_array($data)) {
            $this->_post->merge($data);

        }

        return $this;
    }

    /**
     * Persist the post and update related entities to reflect new post
     *
     * @todo Add validation ?
     * @todo event
     *
     * @return ...
     */
    public function store()
    {
        //$this->_post->getPoster()->incrementPostCount();
        // increment topic posts
        //$this->_topic->setLastPost($this->_post);
        //$this->_topic->incrementRepliesCount();
        // update topic time to last post time
        //$this->_topic->get()->setTopic_time($this->_post->getPost_time());
        // increment forum posts
        //$managedForum = $this->forumManagerService->getManager(null, $this->_topic->get()->getForum());
        //$managedForum->incrementPostCount();
       // $managedForum->setLastPost($this->_post);
        $this->entityManager->persist($this->_post);
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Delete post
     *
     * @return $this
     */
    public function delete()
    {
        // preserve post_id
        $id = $this->_post->getId();
        $postArray = $this->toArray();
        $managedTopic = $this->getManagedTopic();
        $topicLastPost = $managedTopic->get()
                ->decrementReplyCount()
                ->getLast_post();

        $managedForum = $managedTopic->getManagedForum();
        $forumLastPost = $managedForum->get()
                ->decrementPostCount()
                ->getLast_post();

        $this->_post
            ->getPoster()
                ->decrementPostCount();

        // remove the post
        $this->entityManager->remove($this->_post);
        $this->entityManager->flush();

        if (!$topicLastPost instanceof PostEntity || $topicLastPost->getId() == $id) {
            $managedTopic->resetLastPost(true);
        }

        if (!$forumLastPost instanceof PostEntity || $forumLastPost->getId() == $id) {
            $managedForum->resetLastPost(true);
        }

        return $postArray;
    }

    /**
     * Move post
     *
     *
     * @param TopicEntity $topic
     *
     * @throws \InvalidArgumentException Thrown if the parameters do not meet requirements
     *
     * @return int count of posts in destination topic
     */
    public function move($topic)
    {
        if (!$topic instanceof TopicEntity) {
            return $this;
        }



        $managedOriginTopic = $this->getManagedTopic();
//                                        ->decrementRepliesCount()
//                                         ->
//                                        ->store()
//                                        ->getManagedForum()->;

        $this-

        $managedDestinationTopic = $this->topicManagerService->getManager(null, $topic)
                                        ->incrementRepliesCount()
                                        ->store();

        $this->_post->setTopic($managedDestinationTopic->get());


        // ??
        //$managedPost->get()->updatePost_time();
        // this will be done by update
        //  $managedPost->get()->setTopic($managedDestinationTopic->get());

        //$this->synchronizationHelper->topicLastPost($managedDestinationTopic->get(), true);
        //$managedOriginTopic->

        //$this->entityManager->flush();

        return $this;
    }

    /**
     * Get topic id
     *
     * @return integer
     */
    public function getTopicId()
    {
        return $this->_post->getTopicId();
    }

    /**
     * Get topic as managedObject
     *
     * @return TopicManager
     */
    public function getManagedTopic()
    {
        return $this->topicManagerService->getManager($this->_post->getTopicId());
    }

    /**
     * Get the Poster as managedObject
     *
     * @return ForumUserManager
     */
    public function getManagedPoster()
    {
        return $this->forumUserManagerService->getManager($this->_post->getPosterId());
    }
}
