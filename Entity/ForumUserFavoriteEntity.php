<?php

declare(strict_types=1);

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @see https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

namespace Zikula\DizkusModule\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zikula\Core\Doctrine\EntityAccess;

/**
 * ForumUserFavorite entity class.
 *
 * @ORM\Entity
 * @ORM\Table(name="dizkus_forum_favorites", indexes={@ORM\Index(name="forum_idx", columns={"forum_id"}), @ORM\Index(name="user_idx", columns={"user_id"})})
 */
class ForumUserFavoriteEntity extends EntityAccess
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * forumUser
     *
     * @ORM\ManyToOne(targetEntity="ForumUserEntity", inversedBy="favoriteForums", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     */
    private $forumUser;

    /**
     * @ORM\ManyToOne(targetEntity="ForumEntity", inversedBy="favorites")
     * @ORM\JoinColumn(name="forum_id", referencedColumnName="forum_id")
     * */
    private $forum;

    /**
     * Constructor
     */
    public function __construct(ForumUserEntity $forumUser, ForumEntity $forum)
    {
        $this->forumUser = $forumUser;
        $this->forum = $forum;
    }

    /**
     * get the forum
     * @return ForumEntity
     */
    public function getForum()
    {
        return $this->forum;
    }

    /**
     * get the forumUser
     * @return ForumUserEntity
     */
    public function getForumUser()
    {
        return $this->forumUser;
    }

    /**
     * set the forum
     */
    public function setForum(ForumEntity $forum)
    {
        $this->forum = $forum;
    }

    /**
     * set the forumUser
     */
    public function setUser(ForumUserEntity $forumUser)
    {
        $this->forumUser = $forumUser;
    }

    /**
     * get the table id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
