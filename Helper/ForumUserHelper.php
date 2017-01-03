<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Zikula\DizkusModule\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManager;
use Zikula\UsersModule\Api\CurrentUserApi;
use Zikula\Common\Translator\TranslatorInterface;



/**
 * FavoritesHelper
 *
 * @author Kaik
 */
class ForumUserHelper {
    
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
     * @var TranslatorInterface
     */
    private $translator;
    
    
    public function __construct(
            RequestStack $requestStack,
            EntityManager $entityManager,
            CurrentUserApi $userApi,
            TranslatorInterface $translator
         ) {
        
        $this->name = 'ZikulaDizkusModule';
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getMasterRequest();
        $this->entityManager = $entityManager;
        $this->userApi = $userApi;
        $this->translator = $translator;
        
    }
    
    /**
     * lastvisit
     *
     * reads the cookie, updates it and returns the last visit date in unix timestamp
     *
     * @param none
     * @return unix timestamp last visit date
     *
     */
    public function getLastVisit()
    {
        /**
         * set last visit cookies and get last visit time
         * set LastVisit cookie, which always gets the current time and lasts one year
         */
        
        $path = $this->request->getBasePath();
        if (empty($path)) {
            $path = '/';
        } elseif (substr($path, -1, 1) != '/') {
            $path .= '/';
        }
        $time = time();
        
        //CookieUtil::setCookie('DizkusLastVisit', "{$time}", $time + 31536000, $path, null, null, false);
        $response = new Response();
        $cookie = new Cookie('DizkusLastVisit', $time, $time + 31536000);
        $response->headers->setCookie($cookie);        
        
        //$lastVisitTemp = CookieUtil::getCookie('DizkusLastVisitTemp', false, null);
        $lastVisitTemp = $this->request->cookies->get('DizkusLastVisit');
        
        $temptime = empty($lastVisitTemp) ? $time : $lastVisitTemp;
        // set LastVisitTemp cookie, which only gets the time from the LastVisit and lasts for 30 min
        //CookieUtil::setCookie('DizkusLastVisitTemp', "{$temptime}", time() + 1800, $path, null, null, false);
        
        $cookie2 = new Cookie('DizkusLastVisitTemp', $temptime, $time + 1800);
        $response->headers->setCookie($cookie2); 
        
        return $temptime;
    }
    
    /**
     * get_viewip_data
     *
     * @param array $args The argument array.
     *        int $args['pip] The posters IP.
     *
     * @return array with information.
     */
    public function get_viewip_data($args)
    {
        $pip = $args['pip'];
        $viewip = array(
            'poster_ip' => $pip,
            'poster_host' => ($pip <> 'unrecorded') ? gethostbyaddr($pip) : $this->__('Host unknown')
        );
        $dql = 'SELECT p
            FROM Zikula\DizkusModule\Entity\PostEntity p
            WHERE p.poster_ip = :pip
            GROUP BY p.poster';
        $query = $this->entityManager->createQuery($dql)->setParameter('pip', $pip);
        $posts = $query->getResult();
        foreach ($posts as $post) {
            /* @var $post \Zikula\Module\DizkusModule\Entity\PostEntity */
            $coreUser = $post->getPoster()->getUser();
            $viewip['users'][] = array(
                'uid' => $post->getPoster()->getUser_id(),
                'uname' => $coreUser['uname'],
                'postcount' => $post->getPoster()->getPostCount());
        }
        return $viewip;
    }    
    
    
    
    

     /**
      * 
      * Old userApi Below
      * 
      */ 
    

    /**
     * insert rss
     * @see rss2dizkus.php - only used there
     *
     * @param $args['forum']    array with forum data
     * @param $args['items']    array with feed data as returned from Feeds module
     * @return boolean true or false
     */
    public function insertrss($args)
    {
        if (!$args['forum'] || !$args['items']) {
            return false;
        }
        foreach ($args['items'] as $item) {
            // create the reference
            $dateTimestamp = $item->get_date('Y-m-d H:i:s');
            if (empty($dateTimestamp)) {
                $reference = md5($item->get_link());
                $dateTimestamp = date('Y-m-d H:i:s', time());
            } else {
                $reference = md5($item->get_link() . '-' . $dateTimestamp);
            }
            $topicTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateTimestamp);
            // Checking if the forum already has that news.
            $topic = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\TopicEntity')->findOneBy(array('reference' => $reference));
            if (!isset($topic)) {
                // Not found, add the feed item
                $subject = $item->get_title();
                // create message
                $message = '<strong>' . $this->__('Summary') . ' :</strong>\\n\\n' . $item->get_description() . '\\n\\n<a href="' . $item->get_link() . '">' . $item->get_title() . '</a>\\n\\n';
                // store message
                $newManagedTopic = new TopicManager();
                $data = array(
                    'title' => $subject,
                    'message' => $message,
                    'topic_time' => $topicTime,
                    'forum_id' => $args['forum']['forum_id'],
                    'attachSignature' => false,
                    'subscribe_topic' => false,
                    'reference' => $reference);
                $newManagedTopic->prepare($data);
                $topicId = $newManagedTopic->create();
                if (!$topicId) {
                    // An error occured
                    return false;
                }
            }
        }

        return true;
    }

    public function isSpam(PostEntity $post)
    {
        $user = $post->getPoster()->getUser();
        $args = array(
            'author' => $user['uname'], // use 'viagra-test-123' to test
            'authoremail' => $user['email'],
            'content' => $post->getPost_text()
        );
        // Akismet
        if (ModUtil::available('Akismet')) {
            return ModUtil::apiFunc('Akismet', 'user', 'isspam', $args);
        }

        return false;
    }

    /**
     * Check if the useragent is a bot (blacklisted)
     *
     * @return boolean
     */
    public function useragentIsBot()
    {
        // check the user agent - if it is a bot, return immediately
        $robotslist = array(
            'ia_archiver',
            'googlebot',
            'mediapartners-google',
            'yahoo!',
            'msnbot',
            'jeeves',
            'lycos');
        $request = ServiceUtil::get('request');
        $useragent = $request->server->get('HTTP_USER_AGENT');
        for ($cnt = 0; $cnt < count($robotslist); $cnt++) {
            if (strpos(strtolower($useragent), $robotslist[$cnt]) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * dzkVarPrepHTMLDisplay
     * removes the  [code]...[/code] before really calling DataUtil::formatForDisplayHTML()
     */
    public function dzkVarPrepHTMLDisplay($text)
    {
        // remove code tags
        $codecount1 = preg_match_all('/\\[code(.*)\\](.*)\\[\\/code\\]/si', $text, $codes1);
        for ($i = 0; $i < $codecount1; $i++) {
            $text = preg_replace('/(' . preg_quote($codes1[0][$i], '/') . ')/', " DIZKUSCODEREPLACEMENT{$i} ", $text, 1);
        }
        // the real work
        $text = nl2br(DataUtil::formatForDisplayHTML($text));
        // re-insert code tags
        for ($i = 0; $i < $codecount1; $i++) {
            // @todo should use htmlentities here???? dzkstriptags too vvv
            $text = preg_replace("/ DIZKUSCODEREPLACEMENT{$i} /", $codes1[0][$i], $text, 1);
        }

        return $text;
    }

    /**
     * dzkstriptags
     * strip all html tags outside of [code][/code]
     *
     * @param  $text     string the text
     * @return string    the sanitized text
     */
    public function dzkstriptags($text = '')
    {
        if (!empty($text) && ModUtil::getVar($this->name, 'striptags')) {
            // save code tags
            $codecount = preg_match_all('/\\[code(.*)\\](.*)\\[\\/code\\]/siU', $text, $codes);
            for ($i = 0; $i < $codecount; $i++) {
                $text = preg_replace('/(' . preg_quote($codes[0][$i], '/') . ')/', " DZKSTREPLACEMENT{$i} ", $text, 1);
            }
            // strip all html
            $text = strip_tags($text);
            // replace code tags saved before
            for ($i = 0; $i < $codecount; $i++) {
                // @todo should use htmlentities here???? dzkstriptagst too ^^^
                $text = preg_replace("/ DZKSTREPLACEMENT{$i} /", $codes[0][$i], $text, 1);
            }
        }

        return $text;
    }

    /**
     * get an array of users where uname matching text fragment(s)
     *
     * @param  array   $args['fragments']
     * @param  integer $args['limit']
     * @return array
     */
    public function getUsersByFragments($args)
    {
        $fragments = isset($args['fragments']) ? $args['fragments'] : null;
        $limit = isset($args['limit']) ? $args['limit'] : -1;
        if (empty($fragments)) {
            return array();
        }
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addEntityResult('Zikula\\UsersModule\\Entity\\UserEntity', 'u');
        $rsm->addFieldResult('u', 'uname', 'uname');
        $rsm->addFieldResult('u', 'uid', 'uid');
        $sql = 'SELECT u.uid, u.uname FROM users u WHERE ';
        $subSql = array();
        foreach ($fragments as $fragment) {
            $subSql[] = 'u.uname REGEXP \'(' . DataUtil::formatForStore($fragment) . ')\'';
        }
        $sql .= implode(' OR ', $subSql);
        $sql .= ' ORDER BY u.uname ASC';
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        $users = $this->entityManager->createNativeQuery($sql, $rsm)->getResult();

        return $users;
    }

    /**
     * Truncate text to desired length to nearest word
     * @see http://stackoverflow.com/a/9219884/2600812
     * @param  string  $text
     * @param  integer $chars
     * @return string
     */
    public static function truncate($text, $chars = 25)
    {
        $originalText = $text;
        $text = $text . ' ';
        $text = substr($text, 0, $chars);
        $text = substr($text, 0, strrpos($text, ' '));
        $text = strlen($originalText) == strlen($text) ? $text : $text . '...';

        return $text;
    }

    /**
     * getUserOnlineStatus
     *
     * Check if a user is online
     *
     * @param array $args Arguments array.
     *
     * @return boolean True if online
     */
    public function getUserOnlineStatus($args)
    {
        //int $uid The users id
        if (empty($args['uid'])) {
            $args['uid'] = UserUtil::getVar('uid');
        }
        if (array_key_exists($args['uid'], $this->_online)) {
            return $this->_online[$args['uid']];
        }
        $dql = 'SELECT s.uid
                FROM Zikula\\UsersModule\\Entity\\UserSessionEntity s
                WHERE s.lastused > :activetime
                AND s.uid = :uid';
        $query = $this->entityManager->createQuery($dql);
        $activetime = new DateTime();
        // maybe need to check TZ here
        $activetime->modify('-' . System::getVar('secinactivemins') . ' minutes');
        $query->setParameter('activetime', $activetime);
        $query->setParameter('uid', $args['uid']);
        $uid = $query->execute(null, \Doctrine\ORM\AbstractQuery::HYDRATE_SCALAR);
        $isOnline = !empty($uid) ? true : false;
        $this->_online[$args['uid']] = $isOnline;

        return $isOnline;
    }
    
}