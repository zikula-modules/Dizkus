<?php

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use DoctrineExtensions\StandardFields\Mapping\Annotation as ZK;


/**
 * Topics entity class.
 *
 * Annotations define the entity mappings to database.
 *
 * @ORM\Entity
 * @ORM\Table(name="dizkus_topics")
 */
class Dizkus_Entity_Topics extends Zikula_EntityAccess
{

    /**
     * The following are annotations which define the topic id field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $topic_id;


    /**
     * The following are annotations which define the topic_poster field.
     *
     * @ORM\Column(type="integer")
     * @ZK\StandardFields(type="userid", on="create")
     */
    private $topic_poster;




    /**
     * The following are annotations which define the topic_title field.
     *
     * @ORM\Column(type="string", length=255)
     */
    private $topic_title = '';

    /**
     * The following are annotations which define the topic time field.
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $topic_time;


    /**
     * The following are annotations which define the topic status field.
     *
     * @ORM\Column(type="integer")
     */
    private $topic_status = 0;


    /**
     * The following are annotations which define the topic views field.
     *
     * @ORM\Column(type="integer")
     */
    private $topic_views = 0;

    /**
     * The following are annotations which define the topic replies field.
     *
     * @ORM\Column(type="integer", length=10)
     */
    private $topic_replies = 0;


    /**
     * The following are annotations which define the sticky field.
     *
     * @ORM\Column(type="boolean")
     */
    private $sticky = false;


    /**
     * The following are annotations which define the forum id field.
     *
     * @ORM\Column(type="integer")
     */
    private $forum_id;

    /**
     * The following are annotations which define the topic reference field.
     *
     * @ORM\Column(type="string", length=60)
     */
    private $topic_reference = '';



    /**
     * @ORM\OneToOne(targetEntity="Dizkus_Entity_Posts",cascade={"persist"})
     * @ORM\JoinColumn(name="topic_last_post_id", referencedColumnName="post_id", nullable=true)
     */
    private $last_post;


    public function getlast_post()
    {
        return $this->last_post;
    }

    public function setlast_post($post)
    {
        return $this->last_post = $post;
    }





    /**
     * The following are annotations which define the solved field.
     *
     * @ORM\Column(type="boolean")
     */
    private $solved = false;



    /**
     * forum moderators
     *
     * @ORM\OneToMany(targetEntity="Dizkus_Entity_Moderators",
     *                mappedBy="forum_id", cascade={"all"},
     *                orphanRemoval=false)
     */
    private $forum_mods;


    public function getforum_mods()
    {
        return $this->forum_mods;
    }

    public function gettopic_id()
    {
        return $this->topic_id;
    }

    public function gettopic_poster()
    {
        return $this->topic_poster;
    }

    public function gettopic_title()
    {
        return $this->topic_title;
    }

    public function gettopic_status()
    {
        return $this->topic_status;
    }


    public function gettopic_time()
    {
        return $this->topic_time;
    }


    public function gettopic_replies()
    {
        return $this->topic_replies;
    }


    public function gettopic_views()
    {
        return $this->topic_views;
    }


    public function getsticky()
    {
        return $this->sticky;
    }

    public function getforum_id()
    {
        return $this->forum_id;
    }

    public function gettopic_reference()
    {
        return $this->topic_reference;
    }


    public function getSolved()
    {
        return $this->solved;
    }


    public function lock()
    {
        $this->topic_status = 1;
    }

    public function unlock()
    {
        $this->topic_status = 0;
    }


    public function sticky()
    {
        $this->sticky = true;
    }

    public function unsticky()
    {
        $this->sticky = false;
    }

    public function counter()
    {
        $this->topic_views++;
    }

    public function setTopic_title($title)
    {
        $this->topic_title = $title;
    }

    public function setTopic_poster($poster)
    {
        $this->topic_poster = $poster;
    }

    public function setForum_id($id)
    {
        $this->forum_id = $id;
    }


    public function setSolved($solved)
    {
        $this->solved = $solved;
    }
}