<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */
class Dizkus_Api_Notify extends Zikula_AbstractApi
{

    /**
     * Notify Subscribers by e-mail
     *
     * Sending notify e-mail to users subscribed to the topic or the forum
     *
     * @params $args['post'] Dizkus_Entity_Post
     *
     * @returns boolean
     */
    public function emailSubscribers($args)
    {
        setlocale(LC_TIME, System::getVar('locale'));
        $dizkusModuleInfo = ModUtil::getInfoFromName('Dizkus');
        $fromAddress = !empty(ModUtil::getVar('Dizkus', 'email_from')) ? ModUtil::getVar('Dizkus', 'email_from') : System::getVar('adminmail');

        /* @var $post Dizkus_Entity_Post */
        $post = $args['post'];

        $subject = ($post->isFirst()) ? '' : 'Re: ';
        $subject .= $post->getTopic()->getForum()->getForum_name() . ' :: ' . $post->getTopic()->getTopic_title();

        $this->view->assign('sitename', System::getVar('sitename'))
            ->assign('category_name', $post->getTopic()->getForum()->getParent()->getForum_name())
            ->assign('forum_name', $post->getTopic()->getForum()->getForum_name())
            ->assign('topic_subject', $post->getTopic()->getTopic_title())
            ->assign('poster_name', $post->getPoster()->getUser()->getUname())
            ->assign('topic_time_ml', DateUtil::formatDatetime($post->getTopic()->getTopic_time(), 'datetimebrief'))
            ->assign('post_message', $post->getPost_text())
            ->assign('topic_id', $post->getTopic_id())
            ->assign('forum_id', $post->getTopic()->getForum()->getForum_id())
            ->assign('reply_url', ModUtil::url('Dizkus', 'user', 'reply', array('topic' => $post->getTopic_id(), 'forum' => $post->getTopic()->getForum()->getForum_id()), null, null, true))
            ->assign('topic_url', ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $post->getTopic_id()), null, null, true))
            ->assign('subscription_url', ModUtil::url('Dizkus', 'user', 'prefs', array(), null, null, true))
            ->assign('base_url', System::getBaseUrl());
        $message = $this->view->fetch('mail/notifyuser.txt');

        $topicSubscriptions = $post->getTopic()->getSubscriptions();
        $forumSubscriptions = $post->getTopic()->getForum()->getSubscriptions();
        /* @var $subscriptions Doctrine\Common\Collections\ArrayCollection */
        $subscriptions = array_merge($topicSubscriptions, $forumSubscriptions);

        foreach ($subscriptions as $subscription) {
            // check permissions
            /* @var $subscriber Users\Entity\UserEntity */
            $subscriber = $subscription->getForumUser()->getUser();
            // we do not want to notify the current poster
            if ($subscriber->getUid() == $post->getPoster_id()) continue;
            if (SecurityUtil::checkPermission('Dizkus::', $post->getTopic()->getForum()->getParent()->getForum_name() . ':' . $post->getTopic()->getForum()->getForum_name() . ':', ACCESS_READ, $subscriber->getUid())) {
                if (empty($subscriber->getEmail())) continue;
                $args = array('fromname' => System::getVar('sitename'),
                    'fromaddress' => $fromAddress,
                    'toname' => $subscriber->getUname(),
                    'toaddress' => $subscriber->getEmail(),
                    'subject' => $subject,
                    'body' => $message,
                    'headers' => array('X-UserID: ' . md5(UserUtil::getVar('uid')),
                        'X-Mailer: Dizkus v' . $dizkusModuleInfo['version'],
                        'X-DizkusTopicID: ' . $post->getTopic_id()));
                ModUtil::apiFunc('Mailer', 'user', 'sendmessage', $args);                
            }
        }

        return true;
    }

}
