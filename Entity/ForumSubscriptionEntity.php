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
 * ForumSubscription entity class.
 *
 * @ORM\Entity
 * @ORM\Table(name="dizkus_subscription", indexes={@ORM\Index(name="forum_idx", columns={"forum_id"}), @ORM\Index(name="user_idx", columns={"user_id"})})
 */
class ForumSubscriptionEntity extends EntityAccess
{
    /**
     * The following are annotations which define the msg_id field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $msg_id;

    /**
     * @ORM\ManyToOne(targetEntity="ForumEntity", inversedBy="subscriptions")
     * @ORM\JoinColumn(name="forum_id", referencedColumnName="forum_id")
     */
    private $forum;

    /**
     * forumUser
     *
     * @ORM\ManyToOne(targetEntity="ForumUserEntity", inversedBy="forumSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     */
    private $forumUser;

    /**
     * Constructor
     */
    public function __construct(ForumUserEntity $forumUser, ForumEntity $forum)
    {
        $this->forumUser = $forumUser;
        $this->forum = $forum;
    }

    public function getMsg_id()
    {
        return $this->msg_id;
    }

    /**
     * get forum
     *
     * @return ForumEntity
     */
    public function getForum()
    {
        return $this->forum;
    }

    /**
     * set forum
     */
    public function setForum(ForumEntity $forum)
    {
        $this->forum = $forum;
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
     * set the forumUser
     */
    public function setUser(ForumUserEntity $forumUser)
    {
        $this->forumUser = $forumUser;
    }
}
