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

/**
 * Favorites entity class.
 *
 * @ORM\Entity
 * @ORM\Table(name="dizkus_forum_favorites", indexes={@ORM\Index(name="forum_idx", columns={"forum_id"}), @ORM\Index(name="user_idx", columns={"user_id"})})
 */

namespace Dizkus\Entity;

class ForumUserFavoriteEntity extends \Zikula_EntityAccess
{

    /**
     * forumUser
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Dizkus\Entity\ForumUserEntity", inversedBy="user", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     */
    private $forumUser;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Dizkus\Entity\ForumEntity", inversedBy="favorites")
     * @ORM\JoinColumn(name="forum_id", referencedColumnName="forum_id")
     * */
    private $forum;

    /**
     * Constructor
     * @param Dizkus\Entity\ForumUserEntity $forumUser
     * @param Dizkus\Entity\ForumEntity     $forum
     */
    public function __construct(Dizkus\Entity\ForumUserEntity $forumUser, Dizkus\Entity\ForumEntity $forum)
    {
        $this->forumUser = $forumUser;
        $this->forum = $forum;
    }

    /**
     * get the forum
     * @return Dizkus\Entity\ForumEntity
     */
    public function getForum()
    {
        return $this->forum;
    }

    /**
     * get the forumUser
     * @return Dizkus\Entity\ForumUserEntity
     */
    public function getForumUser()
    {
        return $this->forumUser;
    }

    /**
     * set the forum
     * @param Dizkus\Entity\ForumEntity $forum
     */
    public function setForum(Dizkus\Entity\ForumEntity $forum)
    {
        $this->forum = $forum;
    }

    /**
     * set the forumUser
     * @param Dizkus\Entity\ForumUserEntity $forumUser
     */
    public function setUser(Dizkus\Entity\ForumUserEntity $forumUser)
    {
        $this->forumUser = $forumUser;
    }

}
