<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Post entity class
 *
 * @ORM\Entity
 * @ORM\Table(name="dizkus_posts")
 * @ORM\Entity(repositoryClass="Dizkus_Entity_Repository_PostRepository")
 */

namespace Dizkus\Entity;

use ServiceUtil;
use System;
use ModUtil;
use DateTime;
use UserUtil;

class PostEntity extends \Zikula_EntityAccess
{

    /**
     * post_id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_id;

    /**
     * post_time
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $post_time;

    /**
     * poster_ip
     *
     * @ORM\Column(type="string", length=50)
     */
    private $poster_ip = '';

    /**
     * msgid
     *
     * @ORM\Column(type="string", length=100)
     */
    private $msgid = '';

    /**
     * post_text
     *
     * @ORM\Column(type="text")
     */
    private $post_text = '';

    /**
     * attachSignature
     *
     * @ORM\Column(type="boolean")
     */
    private $attachSignature = false;

    /**
     * isFirstPost
     *
     * @ORM\Column(type="boolean")
     */
    private $isFirstPost = false;

    /**
     * title
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title = '';

    /**
     * @ORM\ManyToOne(targetEntity="Dizkus_Entity_ForumUser", cascade={"persist"})
     * @ORM\JoinColumn(name="poster_id", referencedColumnName="user_id")
     */
    private $poster;

    /**
     * @ORM\ManyToOne(targetEntity="Dizkus_Entity_Topic", inversedBy="posts")
     * @ORM\JoinColumn(name="topic_id", referencedColumnName="topic_id")
     * */
    private $topic;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (ModUtil::getVar('Dizkus', 'log_ip') == 'no') {
            // for privacy issues ip logging can be deactivated
            $this->poster_ip = 'unrecorded';
        } else {
            $request = ServiceUtil::getService('request');
            if (System::serverGetVar('HTTP_X_FORWARDED_FOR')) {
                $this->poster_ip = $request->server->get('REMOTE_ADDR') . '/' . $request->server->get('HTTP_X_FORWARDED_FOR');
            } else {
                $this->poster_ip = $request->server->get('REMOTE_ADDR');
            }
        }
    }

    public function getPost_id()
    {
        return $this->post_id;
    }

    public function getPost_text()
    {
        return $this->post_text;
    }

    public function setPost_text($text)
    {
        return $this->post_text = stripslashes($text);
    }

    public function getAttachSignature()
    {
        return $this->attachSignature;
    }

    public function setAttachSignature($attachSignature)
    {
        return $this->attachSignature = $attachSignature;
    }

    public function getIsFirstPost()
    {
        return $this->isFirstPost;
    }

    public function setIsFirstPost($first)
    {
        return $this->isFirstPost = $first;
    }

    /**
     * Is the post a first post in topic?
     * convenience naming
     *
     * @return boolean
     */
    public function isFirst()
    {
        return $this->isFirstPost;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        return $this->title = $title;
    }

    public function getPost_time()
    {
        return $this->post_time;
    }

    public function setPost_time(DateTime $time)
    {
        $this->post_time = $time;
    }

    public function updatePost_time(DateTime $time = null)
    {
        if (!isset($time)) {
            $time = new DateTime();
        }
        $this->post_time = $time;
    }

    public function getPoster_ip()
    {
        return $this->poster_ip;
    }

    public function getMsgid()
    {
        return $this->msgid;
    }

    /**
     * Get User who made post
     *
     * @return Dizkus_Entity_ForumUser
     */
    public function getPoster()
    {
        return $this->poster;
    }

    /**
     * set user who made the post
     *
     * @param Dizkus_Entity_ForumUser $poster
     */
    public function setPoster(Dizkus_Entity_ForumUser $poster)
    {
        $this->poster = $poster;
    }

    /**
     * convenience method to retrieve user id of poster
     *
     * @return integer
     */
    public function getPoster_id()
    {
        return $this->poster->getUser_id();
    }

    public function getPoster_data()
    {
        return array(
            'image' => 'a',
            'rank' => 'a',
            'rank_link' => 'a',
            'description' => 'a',
            'moderate' => 'a',
            'edit' => 'a',
            'reply' => 'a',
            'postCount' => 'a',
            'seeip' => 'a');
    }

    /**
     * get Post topic
     *
     * @return Dizkus_Entity_Topic
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Set post Topic
     *
     * @param Dizkus_Entity_Topic $topic
     */
    public function setTopic(Dizkus_Entity_Topic $topic)
    {
        $this->topic = $topic;
    }

    /**
     * convenience method to retreive topic ID
     *
     * @return integer
     */
    public function getTopic_id()
    {
        return $this->topic->getTopic_id();
    }

    /**
     * determine if a user is allowed to edit this post
     *
     * @param  integer $uid
     * @return boolean
     */
    public function getUserAllowedToEdit($uid = null)
    {
        // default to current user
        $uid = isset($uid) ? $uid : UserUtil::getVar('uid');
        $timeAllowedToEdit = ModUtil::getVar('Dizkus', 'timespanforchanges');
        // in hours
        $postTime = clone $this->post_time;
        $canEditUtil = $postTime->modify("+{$timeAllowedToEdit} hours");
        $now = new DateTime();
        if ($uid == $this->poster->getUser_id() && $now <= $canEditUtil) {
            return true;
        }

        return false;
    }

}