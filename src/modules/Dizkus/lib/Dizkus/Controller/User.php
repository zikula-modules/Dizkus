<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

class Dizkus_Controller_User extends Zikula_AbstractController
{
    public function postInitialize()
    {
        $this->view->setCaching(false)->add_core_data();
    }
    /**
     * main
     * show all categories and forums a user may see
     *
     * @params 'viewcat' int only expand the category, all others shall be hidden / collapsed
     */
    public function main($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
        
        $viewcat   =  (int)$this->request->query->get('viewcat', (isset($args['viewcat'])) ? $args['viewcat'] : -1);
        $favorites = (bool)$this->request->query->get('favorites', (isset($args['favorites'])) ? $args['favorites'] : false);
    
        list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');
        $loggedIn = UserUtil::isLoggedIn();
    
        if (ModUtil::getVar('Dizkus', 'favorites_enabled') == 'yes') {
            if ($loggedIn && !$favorites) {
                $favorites = ModUtil::apiFunc('Dizkus', 'user', 'get_favorite_status');
            }
        }
        if ($loggedIn && $favorites) {
            $tree = ModUtil::apiFunc('Dizkus', 'user', 'getfavorites',
                                 array('user_id'    => (int)UserUtil::getVar('uid'),
                                       'last_visit' => $last_visit ));
        } else {
            $tree = ModUtil::apiFunc('Dizkus', 'user', 'readcategorytree',
                                 array('last_visit' => $last_visit ));
    
            if (ModUtil::getVar('Dizkus', 'slimforum') == 'yes') {
                // this needs to be in here because we want to display the favorites
                // not go to it if there is only one
                // check if we have one category and one forum only
                if (count($tree)==1) {
                    foreach ($tree as $catname => $forumarray) {
                        if (count($forumarray['forums']) == 1) {
                            return System::redirect(ModUtil::url('Dizkus', 'user', 'viewforum', array('forum'=>$forumarray['forums'][0]['forum_id'])));
                        } else {
                            $viewcat = $tree[$catname]['cat_id'];
                        }
                    }
                }
            }
        }
    
        $view_category_data = array();
        if ($viewcat <> -1) {
            foreach ($tree as $category) {
                if ($category['cat_id'] == $viewcat) {
                    $view_category_data = $category;
                    break;
                }
            }
        }
    
        $this->view->assign('favorites', $favorites);
        $this->view->assign('tree', $tree);
        $this->view->assign('view_category', $viewcat);
        $this->view->assign('view_category_data', $view_category_data);
        $this->view->assign('last_visit', $last_visit);
        $this->view->assign('last_visit_unix', $last_visit_unix);
        $this->view->assign('numposts', ModUtil::apiFunc('Dizkus', 'user', 'boardstats',
                                                array('id'   => '0',
                                                      'type' => 'all' )));
    
        return $this->view->fetch('user/main.tpl');
    }
    
    /**
     * viewforum
     *
     * opens a forum and shows the last postings
     *
     * @params 'forum' int the forum id
     * @params 'start' int the posting to start with if on page 1+
     *
     * @return string
     */
    public function viewforum($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        // get the input
        $forum_id = (int)$this->request->query->get('forum', (isset($args['forum'])) ? $args['forum'] : null);
        $start    = (int)$this->request->query->get('start', (isset($args['start'])) ? $args['start'] : 0);
    
        $subforums = $this->entityManager->getRepository('Dizkus_Entity_Subforums')
                                   ->findBy(array('parent_id' => $forum_id)); 
        $this->view->assign('subforums', $subforums);
        
        
        
        list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');
    
        $forum = ModUtil::apiFunc('Dizkus', 'user', 'readforum',
                              array('forum_id'        => $forum_id,
                                    'start'           => $start,
                                    'last_visit'      => $last_visit,
                                    'last_visit_unix' => $last_visit_unix));
    
        
        $this->view->assign('forum', $forum);
        $this->view->assign('hot_threshold', ModUtil::getVar('Dizkus', 'hot_threshold'));
        $this->view->assign('last_visit', $last_visit);
        $this->view->assign('last_visit_unix', $last_visit_unix);
        $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
    
        return $this->view->fetch('user/forum/view.tpl');
    }
    
    /**
     * viewtopic
     *
     */
    public function viewtopic($args=array())
    {        
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );

    
        // get the input
        $topic_id = (int)$this->request->query->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
        // begin patch #3494 part 1, credits to teb
        $post_id  = (int)$this->request->query->get('post', (isset($args['post'])) ? $args['post'] : null);
        // end patch #3494 part 1
        $start    = (int)$this->request->query->get('start', (isset($args['start'])) ? $args['start'] : 0);
        $view     = strtolower($this->request->query->get('view', (isset($args['view'])) ? $args['view'] : ''));
    
        list($last_visit, $last_visit_unix) = ModUtil::apiFunc($this->name, 'user', 'setcookies');
    
        if (!empty($view) && ($view=='next' || $view=='previous')) {
            $topic_id = ModUtil::apiFunc($this->name, 'user', 'get_previous_or_next_topic_id',
                                     array('topic_id' => $topic_id,
                                           'view'     => $view));
            return System::redirect(ModUtil::url($this->name, 'user', 'viewtopic',
                                array('topic' => $topic_id)));
        }
    
        // begin patch #3494 part 2, credits to teb
        if (!empty($post_id) && is_numeric($post_id) && empty($topic_id)) {
            $topic_id = ModUtil::apiFunc($this->name, 'user', 'get_topicid_by_postid', array('post_id' => $post_id));
            if ($topic_id <> false) {
                // redirect instad of continue, better for SEO
                return System::redirect(ModUtil::url($this->name, 'user', 'viewtopic', 
                                           array('topic' => $topic_id)));
            }
        }
        // end patch #3494 part 2
    
        $topic = ModUtil::apiFunc($this->name, 'Topic', 'read',
                              array('topic_id'   => $topic_id,
                                    'start'      => $start,
                                    'count'      => true));
    
        $this->view->assign('topic', $topic);
        $this->view->assign('post_count', count($topic['posts']));
        $this->view->assign('last_visit', $last_visit);
        $this->view->assign('last_visit_unix', $last_visit_unix);
        $this->view->assign('favorites', ModUtil::apifunc($this->name, 'user', 'get_favorite_status'));
    
        return $this->view->fetch('user/topic/view.tpl');
    }



    /**
     * Set a topic to solved
     *
     * @return boolean
     */
    public function topicsolved()
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );

        // get the input
        $topicId = (int)$this->request->query->get('topic');

        // set topic solved
        $this->entityManager->find('Dizkus_Entity_Topics', $topicId)->setSolved(true);
        $this->entityManager->flush();

        // redirect to viewtopic
        $url = ModUtil::url($this->name, 'User', 'viewtopic', array('topic' => $topicId));
        return System::redirect($url);
    }


    /**
     * Set a topic to solved
     *
     * @return boolean
     */
    public function topicunsolved()
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );

        // get the input
        $topicId = (int)$this->request->query->get('topic');

        // set topic solved
        $this->entityManager->find('Dizkus_Entity_Topics', $topicId)->setSolved(false);
        $this->entityManager->flush();

        // redirect to viewtopic
        $url = ModUtil::url($this->name, 'User', 'viewtopic', array('topic' => $topicId));
        return System::redirect($url);
    }
    
    /**
     * reply
     *
     */
    public function reply($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        // get the input
        $topic_id = (int)$this->request->request->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
        $post_id  = (int)$this->request->request->get('post', (isset($args['post'])) ? $args['post'] : null);
        $message  = $this->request->request->get('message', (isset($args['message'])) ? $args['message'] : '');
        $attach_signature = (int)$this->request->request->get('attach_signature', (isset($args['attach_signature'])) ? $args['attach_signature'] : 0);
        $subscribe_topic = (int)$this->request->request->get('subscribe_topic', (isset($args['subscribe_topic'])) ? $args['subscribe_topic'] : 0);
        $preview = $this->request->request->get('preview', (isset($args['preview'])) ? $args['preview'] : '');
        $submit = $this->request->request->get('submit', (isset($args['submit'])) ? $args['submit'] : '');
        $cancel = $this->request->request->get('cancel', (isset($args['cancel'])) ? $args['cancel'] : '');


        /**
         * if cancel is submitted move to topic-view
         */
        if (!empty($cancel)) {
            return System::redirect(ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $topic_id)));
        }
    
        $preview = (empty($preview)) ? false : true;
        $submit  = (empty($submit))  ? false : true;
    
        $message = dzkstriptags($message);
        // check for maximum message size
        if ((strlen($message) +  strlen('[addsig]')) > 65535) {
            LogUtil::registerStatus($this->__('Error! The message is too long. The maximum length is 65,535 characters.'));
            // switch to preview mode
            $preview = true;
        }
    
        if ($submit == true && $preview == false) {
            // Confirm authorisation code
            /*if (!SecurityUtil::confirmAuthKey()) {
                return LogUtil::registerAuthidError();
            }*/
    
            // ContactList integration: Is the user ignored and allowed to write an answer to this topic?
            $topic = ModUtil::apiFunc('Dizkus', 'user', 'readtopci0', $topic_id);
            $ignorelist_setting = ModUtil::apiFunc('Dizkus','user','get_settings_ignorelist',array('uid' => $topic['topic_poster']));
            if (ModUtil::available('ContactList') && ($ignorelist_setting == 'strict') && (ModUtil::apiFunc('ContactList','user','isIgnored',array('uid' => (int)$topic['topic_poster'], 'iuid' => UserUtil::getVar('uid'))))) {
                return LogUtil::registerError($this->__('Error! The user who started this topic is ignoring you, and does not want you to be able to write posts under this topic. Please contact the topic originator for more information.'), null, ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $topic_id)));
            }
    
            list($start,
                 $post_id ) = ModUtil::apiFunc('Dizkus', 'user', 'storereply',
                                           array('topic_id'         => $topic_id,
                                                 'message'          => $message,
                                                 'attach_signature' => $attach_signature,
                                                 'subscribe_topic'  => $subscribe_topic));
    
            return System::redirect(ModUtil::url('Dizkus', 'user', 'viewtopic',
                                array('topic' => $topic_id,
                                      'start' => $start)) . '#pid' . $post_id);
        } else {
            list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');
            $reply = ModUtil::apiFunc('Dizkus', 'user', 'preparereply',
                                  array('topic_id'   => $topic_id,
                                        'post_id'    => $post_id,
                                        'last_visit' => $last_visit,
                                        'reply_start'=> empty($message),
                                        'attach_signature' => $attach_signature,
                                        'subscribe_topic'  => $subscribe_topic));
            if ($preview == true) {
                $reply['message'] = dzkVarPrepHTMLDisplay($message);
                //list($reply['message_display']) = ModUtil::callHooks('item', 'transform', '', array($message));
                $reply['message_display'] = nl2br($reply['message_display']);
            }

            $this->view->assign('reply', $reply);
            $this->view->assign('preview', $preview);
            $this->view->assign('last_visit', $last_visit);
            $this->view->assign('last_visit_unix', $last_visit_unix);
            $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
    
            return $this->view->fetch('user/topic/reply.tpl');
        }
    }
    
    /**
     * newtopic
     *
     */
    public function newtopic()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/topic/new.tpl', new Dizkus_Form_Handler_User_NewTopic());
    }
    
    /**
     * editpost
     *
     */
    public function editpost($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        // get the input
        $topic_id = (int)$this->request->query->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
        $post_id  = (int)$this->request->query->get('post', (isset($args['post'])) ? $args['post'] : null);
        $subject  = $this->request->query->get('subject', (isset($args['subject'])) ? $args['subject'] : '');
        $message  = $this->request->query->get('message', (isset($args['message'])) ? $args['message'] : '');
        $attach_signature = (int)$this->request->query->get('attach_signature', (isset($args['attach_signature'])) ? $args['attach_signature'] : 0);
        $delete   = $this->request->query->get('delete', (isset($args['delete'])) ? $args['delete'] : '');
        $preview  = $this->request->query->get('preview', (isset($args['preview'])) ? $args['preview'] : '');
        $submit   = $this->request->query->get('submit', (isset($args['submit'])) ? $args['submit'] : '');
        $cancel   = $this->request->query->get('cancel', (isset($args['cancel'])) ? $args['cancel'] : '');
                    
        if (empty($post_id) || !is_numeric($post_id)) {
            return System::redirect(ModUtil::url('Dizkus', 'user', 'main'));
        }
    
        $post = ModUtil::apiFunc('Dizkus', 'user', 'readpost',
                             array('post_id' => $post_id));
    
        if (!allowedtomoderatecategoryandforum($post['cat_id'], $post['forum_id'])
           && ($post['poster_data']['uid'] <> UserUtil::getVar('uid')) ) {
            return LogUtil::registerPermissionError();
        }
    
        $preview = (empty($preview)) ? false : true;
    
        //  if cancel is submitted move to forum-view
        if (!empty($cancel)) {
            return System::redirect(ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $topic_id)));
        }
    
        $message = dzkstriptags($message);
        // check for maximum message size
        if ((strlen($message) + 8/*strlen('[addsig]')*/) > 65535) {
            LogUtil::registerStatus($this->__('Error! The message is too long. The maximum length is 65,535 characters.'));
            // switch to preview mode
            $preview = true;
        }
    
        list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');
    
        if ($submit && !$preview) {
    
            // Confirm authorisation code
            /*if (!SecurityUtil::confirmAuthKey()) {
                return LogUtil::registerAuthidError();
            }*/
    
            // store the new topic
            $redirect = ModUtil::apiFunc('Dizkus', 'user', 'updatepost',
                                     array('post_id'          => $post_id,
                                           'topic_id'         => $topid_id,
                                           'delete'           => $delete,
                                           'subject'          => $subject,
                                           'message'          => $message,
                                           'attach_signature' => ($attach_signature==1)));
    
            return System::redirect($redirect);
    
        } else {
            if (!empty($subject)) {
                $post['topic_subject'] = strip_tags($subject);
            }
    
            // if the current user is the original poster we allow to
            // edit the subject
            $firstpost = ModUtil::apiFunc('Dizkus', 'user', 'get_firstlast_post_in_topic',
                                      array('topic_id' => $post['topic_id'],
                                            'first'    => true));
    
            if ($post['poster_data']['uid'] == $firstpost['poster_data']['uid']) {
                $post['edit_subject'] = true;
            }
    
            if (!empty($message)) {
                $post['post_rawtext'] = $message;
//                list($post['post_textdisplay']) = ModUtil::callHooks('item', 'transform', '', array(nl2br($message)));
            }
    
            $this->view->assign('preview', $preview);
            $this->view->assign('post', $post);
            $this->view->assign('last_visit', $last_visit);
            $this->view->assign('last_visit_unix', $last_visit_unix);
            $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
    
            return $this->view->fetch('user/post/edit.tpl');
        }
    }

    /**
     * Delete topic
     *
     * @return string
     */
    public function deletetopic() {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/topic/delete.tpl', new Dizkus_Form_Handler_User_DeleteTopic());
    }



    /**
     * movetopic
     *
     * @return string
     */
    public function movetopic()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/topic/move.tpl', new Dizkus_Form_Handler_User_MoveTopic());
    }

    
    /**
     * topicadmin
     *
     */
    public function topicadmin($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );

	// get the input
	if ($this->request->isPost()) {
	    $topic_id = (int)$this->request->request->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
	    $post_id  = (int)$this->request->request->get('post', (isset($args['post'])) ? $args['post'] : null);
	    $forum_id = (int)$this->request->request->get('forum', (isset($args['forum'])) ? $args['forum'] : null);
	    $mode     = $this->request->request->get('mode', (isset($args['mode'])) ? $args['mode'] : '');
	    $submit   = $this->request->request->get('submit', (isset($args['submit'])) ? $args['submit'] : '');
	    $shadow   = $this->request->request->get('createshadowtopic', (isset($args['createshadowtopic'])) ? $args['createshadowtopic'] : '');
	} else {
	    $topic_id = (int)$this->request->query->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
	    $post_id  = (int)$this->request->query->get('post', (isset($args['post'])) ? $args['post'] : null);
	    $forum_id = (int)$this->request->query->get('forum', (isset($args['forum'])) ? $args['forum'] : null);
	    $mode     = $this->request->query->get('mode', (isset($args['mode'])) ? $args['mode'] : '');
	    $submit   = $this->request->query->get('submit', (isset($args['submit'])) ? $args['submit'] : '');
	    $shadow   = $this->request->query->get('createshadowtopic', (isset($args['createshadowtopic'])) ? $args['createshadowtopic'] : '');
	}
	$shadow   = (empty($shadow)) ? false : true;
        if (empty($topic_id) && !empty($post_id)) {
            $topic_id = ModUtil::apiFunc('Dizkus', 'user', 'get_topicid_by_postid',
                                     array('post_id' => $post_id));
        }
    
        $topic = ModUtil::apiFunc('Dizkus', 'user', 'readtopic',
                              array('topic_id' => $topic_id,
                                    'count'    => false));

	/* This does not work. Commenting out until we decide to fix or remove totally.
        if ($topic['access_moderate'] <> true) {
            return LogUtil::registerPermissionError();
        }
	*/
	
        if (empty($submit)) {
            switch ($mode)
            {
                case 'lock':
                case 'unlock':
                    $templatename = 'user/topic/lock.tpl';
                    break;
    
                case 'sticky':
                case 'unsticky':
                    $templatename = 'user/topic/sticky.tpl';
                    break;
    
                case 'viewip':
                    $this->view->assign('viewip', ModUtil::apiFunc('Dizkus', 'user', 'get_viewip_data', array('post_id' => $post_id)));
                    $templatename = 'user/viewip.tpl';
                    break;
    
                default:
                    return System::redirect(ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $topic_id)));
            }
    
        $this->view->add_core_data();
        $this->view->setCaching(false);
        $this->view->assign('mode', $mode);
        $this->view->assign('topic_id', $topic_id);
        $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));

	return $this->view->fetch($templatename);
    
        } else { // submit is set
            /*if (!SecurityUtil::confirmAuthKey()) {
                return LogUtil::registerAuthidError();
            }*/
    
            switch ($mode)
            {
                case 'lock':
                case 'unlock':
                    list($f_id, $c_id) = ModUtil::apiFunc('Dizkus', 'user', 'get_forumid_and_categoryid_from_topicid',
                                                      array('topic_id' => $topic_id));
                    if (!allowedtomoderatecategoryandforum($c_id, $f_id)) {
                        return LogUtil::registerPermissionError();
                    }
                    ModUtil::apiFunc('Dizkus', 'user', 'lockunlocktopic',
                                 array('topic_id' => $topic_id,
                                       'mode'     => $mode));
                    break;
    
                case 'sticky':
                case 'unsticky':
                    list($f_id, $c_id) = ModUtil::apiFunc('Dizkus', 'user', 'get_forumid_and_categoryid_from_topicid',
                                                      array('topic_id' => $topic_id));
                    if (!allowedtomoderatecategoryandforum($c_id, $f_id)) {
                        return LogUtil::registerPermissionError();
                    }
                    ModUtil::apiFunc('Dizkus', 'user', 'stickyunstickytopic',
                                 array('topic_id' => $topic_id,
                                       'mode'     => $mode));
                    break;
                default:
            }
    
	    return System::redirect(ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $topic_id)));
        }
    }
    
    /**
     * prefs
     *
     */
    public function prefs($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        if (!UserUtil::isLoggedIn()) {
            return ModUtil::func('Users', 'user', 'loginscreen', array('redirecttype' => 1));
        }
    
        // get the input
        $topic_id  = (int)$this->request->query->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
        $act       = $this->request->query->get('act', (isset($args['act'])) ? $args['act'] : '');
        $return_to = $this->request->query->get('return_to', (isset($args['return_to'])) ? $args['return_to'] : '');
        $forum_id  = (int)$this->request->query->get('forum', (isset($args['forum'])) ? $args['forum'] : null);
        $user_id   = (int)$this->request->query->get('user', (isset($args['user'])) ? $args['user'] : null);
    
        // user_id will only be used if we have admin permissions otherwise the
        // user can edit his prefs only but not others users prefs
    
        switch ($act)
        {
            case 'subscribe_topic':
                $return_to = (!empty($return_to))? $return_to : 'viewtopic';
                ModUtil::apiFunc('Dizkus', 'user', 'subscribe_topic',
                             array('topic_id' => $topic_id ));
                $params = array('topic' => $topic_id);
                break;
    
            case 'unsubscribe_topic':
                $return_to = (!empty($return_to))? $return_to : 'viewtopic';
                ModUtil::apiFunc('Dizkus', 'user', 'unsubscribe_topic',
                             array('topic_id' => $topic_id ));
                $params = array('topic' => $topic_id);
                break;
    
            case 'subscribe_forum':
                $return_to = (!empty($return_to))? $return_to : 'viewforum';
                ModUtil::apiFunc('Dizkus', 'user', 'subscribe_forum',
                             array('forum_id' => $forum_id ));
                $params = array('forum' => $forum_id);
                break;
    
            case 'unsubscribe_forum':
                $return_to = (!empty($return_to))? $return_to : 'viewforum';
                ModUtil::apiFunc('Dizkus', 'user', 'unsubscribe_forum',
                             array('forum_id' => $forum_id ));
                $params = array('forum' => $forum_id);
                break;
    
            case 'add_favorite_forum':
                if (ModUtil::getVar('Dizkus', 'favorites_enabled')=='yes') {
                    $return_to = (!empty($return_to))? $return_to : 'viewforum';
                    ModUtil::apiFunc('Dizkus', 'user', 'add_favorite_forum',
                                 array('forum_id' => $forum_id ));
                    $params = array('forum' => $forum_id);
                }
                break;
    
            case 'remove_favorite_forum':
                if (ModUtil::getVar('Dizkus', 'favorites_enabled')=='yes') {
                    $return_to = (!empty($return_to))? $return_to : 'viewforum';
                    ModUtil::apiFunc('Dizkus', 'user', 'remove_favorite_forum',
                                 array('forum_id' => $forum_id ));
                    $params = array('forum' => $forum_id);
                }
                break;
    
            case 'change_post_order':
                $return_to = (!empty($return_to))? $return_to : 'viewtopic';
                ModUtil::apiFunc('Dizkus', 'user', 'change_user_post_order');
                $params = array('topic' => $topic_id);
                break;
    
            case 'showallforums':
                $return_to = (!empty($return_to))? $return_to : 'main';
                $favorites = ModUtil::apiFunc('Dizkus', 'user', 'change_favorite_status');
                break;
            case 'showfavorites':
                if (ModUtil::getVar('Dizkus', 'favorites_enabled')=='yes') {
                    $return_to = (!empty($return_to))? $return_to : 'main';
                    $favorites = ModUtil::apiFunc('Dizkus', 'user', 'change_favorite_status');
                    $params = array();
                }
                break;

            case 'noautosubscribe':
            case 'autosubscribe':
                $return_to = (!empty($return_to))? $return_to : 'prefs';
                $nm = ModUtil::apiFunc('Dizkus', 'user', 'togglenewtopicsubscription');
                $params = array();
                break;
    
            default:
                list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');
    
                $this->view->assign('last_visit', $last_visit);
                $this->view->assign('autosubscribe', (bool)UserUtil::getVar('dizkus_autosubscription', -1, 0));
                $this->view->assign('favorites_enabled', ModUtil::getVar('Dizkus', 'favorites_enabled'));
                $this->view->assign('last_visit_unix', $last_visit_unix);
                $this->view->assign('signaturemanagement', ModUtil::getVar('Dizkus','signaturemanagement'));
                $this->view->assign('ignorelist_handling', ModUtil::getVar('Dizkus','ignorelist_handling'));
                $this->view->assign('contactlist_available', ModUtil::available('ContactList'));
                $this->view->assign('post_order', strtolower(ModUtil::apiFunc('Dizkus','user','get_user_post_order')));
                $this->view->assign('favorites', ModUtil::apiFunc('Dizkus','user','get_favorite_status'));
                $this->view->assign('tree', ModUtil::apiFunc('Dizkus', 'user', 'readcategorytree', array('last_visit' => $last_visit )));
    
                return $this->view->fetch('user/prefs.tpl');
        }
    
        return System::redirect(ModUtil::url('Dizkus', 'user', $return_to, $params));
    }
    
    /**
     * signature management
     * 
     */
    public function signaturemanagement()
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        if (!UserUtil::isLoggedIn()) {
            return ModUtil::func('Users', 'user', 'loginscreen', array('redirecttype' => 1));
        }
        // Security check
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_COMMENT) || (!(ModUtil::getVar('Dizkus','signaturemanagement') == 'yes'))) {
            return LogUtil::registerPermissionError();
        }
    
        // Create output and assign data
        $form = FormUtil::newForm($this->name, $this);
    
        // Return the output
        return $form->execute('user/signaturemanagement.tpl', new Dizkus_Form_Handler_User_SignatureManagement());
    }
    
    /**
     * ignorelist management
     * 
     */
    public function ignorelistmanagement()
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );

        if (!UserUtil::isLoggedIn()) {
            return ModUtil::func('Users', 'user', 'loginscreen', array('redirecttype' => 1));
        }
        // Security check
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_COMMENT)) {
            return LogUtil::registerPermissionError();
        }
    
        // check for Contactlist module and admin settings
        $ignorelist_handling = ModUtil::getVar('Dizkus','ignorelist_handling');
        if (!ModUtil::available('ContactList') || ($ignorelist_handling == 'none')) {
            return LogUtil::registerError($this->__("No 'ignore list' configuration is currently possible."), null, ModUtil::url('Dizkus', 'user', 'prefs'));
        }
    
        // Create output and assign data
        $render = FormUtil::newForm($this->name, $this);
    
        // Return the output
        return $render->execute('user/ignorelistmanagement.tpl', new Dizkus_Form_Handler_User_IgnoreListManagement());
    }
    
    /**
     * emailtopic
     *
     * @return string
     */
    public function emailtopic()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/topic/email.tpl', new Dizkus_Form_Handler_User_EmailTopic());
    }
    
    /**
     * latest
     *
     * @return string
     */
    public function viewlatest($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        if (useragent_is_bot() == true) {
            return System::redirect(ModUtil::url('Dizkus', 'user', 'main'));
        }
    
        // get the input
        $selorder   = (int)$this->request->query->get('selorder', (isset($args['selorder'])) ? $args['selorder'] : 1);
        $nohours    = (int)$this->request->query->get('nohours', (isset($args['nohours'])) ? $args['nohours'] : null);
        $unanswered = (int)$this->request->query->get('unanswered', (isset($args['unanswered'])) ? $args['unanswered'] : 0);
        $amount     = (int)$this->request->query->get('amount', (isset($args['amount'])) ? $args['amount'] : null);




        if (!empty($amount) && !is_numeric($amount)) {
         unset($amount);
         }





     // maximum last 100 posts maybe shown
     if (isset($amount) && $amount>100) {
         $amount = 100;
         }

     if (!empty($amount)) {
         $selorder = 7;
         }

     if (!empty($nohours) && !is_numeric($nohours)) {
         unset($nohours);
     }

     // maximum two weeks back = 2 * 24 * 7 hours
     if (isset($nohours) && $nohours > 336) {
         $nohours = 336;
     }

     if (!empty($nohours)) {
         $selorder = 5;
     }

     list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');

     list($posts, $m2fposts, $rssposts, $text) = ModUtil::apiFunc('Dizkus', 'user', 'get_latest_posts',
                                                              array('selorder'   => $selorder,
                                                                    'nohours'    => $nohours,
                                                                    'amount'     => $amount,
                                                                    'unanswered' => $unanswered,
                                                                    'last_visit' => $last_visit,
                                                                    'last_visit_unix' => $last_visit_unix));

     $this->view->assign('posts', $posts);
     $this->view->assign('m2fposts', $m2fposts);
     $this->view->assign('rssposts', $rssposts);
     $this->view->assign('text', $text);
     $this->view->assign('nohours', $nohours);
     $this->view->assign('last_visit', $last_visit);
     $this->view->assign('last_visit_unix', $last_visit_unix);
     $this->view->assign('numposts', ModUtil::apiFunc('Dizkus', 'user', 'boardstats',
                                             array('id'   => '0',
                                                   'type' => 'all' )));
     $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
    
        return $this->view->fetch('user/post/latest.tpl');
    }
    
    /**
     * Split topic
     *
     * @return string
     */
    public function splittopic()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/topic/split.tpl', new Dizkus_Form_Handler_User_SplitTopic());
    }
    
    /**
     * print
     *
     * prepare print view of the selected posting or topic
     *
     * @return string
     */
    public function printtopic($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        // get the input
        $post_id  = (int)$this->request->query->get('post', (isset($args['post'])) ? $args['post'] : null);
        $topic_id = (int)$this->request->query->get('topic', (isset($args['topic'])) ? $args['topic'] : null);
    
        if (useragent_is_bot() == true) {
            if ($post_id <> 0 ) {
                $topic_id = ModUtil::apiFunc('Dizkus', 'user', 'get_topicid_by_postid',
                                        array('post_id' => $post_id));
            }
            if (($topic_id <> 0) && ($topic_id<>false)) {
                return $this->viewtopic(array('topic' => $topic_id,
                                                    'start'   => 0));
            } else {
                return System::redirect(ModUtil::url('Dizkus', 'user', 'main'));
            }
        } else {
            $this->view->add_core_data();
            $this->view->setCaching(false);
            if ($post_id <> 0) {
                $post = ModUtil::apiFunc('Dizkus', 'user', 'readpost',
                                     array('post_id' => $post_id));
    
                $this->view->assign('post', $post);
    
                $output = $this->view->fetch('user/post/print.tpl');
            } elseif ($topic_id <> 0) {
                $topic = ModUtil::apiFunc('Dizkus', 'user', 'readtopic',
                                     array('topic_id'  => $topic_id,
                                           'complete' => true,
                                           'count' => false ));
    
                $this->view->assign('topic', $topic);
    
                $output = $this->view->fetch('user/topic/print.tpl');
            } else {
                return System::redirect(ModUtil::url('Dizkus', 'user', 'main'));
            }
    
            // FIXME backend_language is deprecated?
            $lang = System::getVar('backend_language');
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"$lang\" xml:lang=\"$lang\">\n";
            echo "<head>\n";
            echo "<title>" . DataUtil::formatForDisplay($topic['topic_title']) . "</title>\n";
            echo "<link rel=\"stylesheet\" href=\"" . System::getBaseUrl() . "modules/Dizkus/style/style.css\" type=\"text/css\" />\n";
            echo "<link rel=\"stylesheet\" href=\"" . System::getBaseUrl() . "themes/" . UserUtil::getTheme() . "/style/style.css\" type=\"text/css\" />\n";        
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
    
            global $additional_header;
            if (is_array($additional_header)) {
                foreach ($additional_header as $header) {
                    echo "$header\n";
                }
            }
            echo "</head>\n";
            echo "<body class=\"printbody\">\n";
            echo $output;
            echo "</body>\n";
            echo "</html>\n";
            System::shutDown();
        }
    }
    
    /**
     * movepost
     * 
     * Move a single post to another thread
     *
     * @return string
     */
    public function movepost()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/post/move.tpl', new Dizkus_Form_Handler_User_MovePost());
    }
    
    /**
     * jointopics
     * Join a topic with another toipic                                                                                                  ?>
     *
     */
    public function jointopics($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        // get the input
        $post_id       = (int)$this->request->query->get('post_id', (isset($args['post_id'])) ? $args['post_id'] : null);
        $submit        = $this->request->query->get('submit', (isset($args['submit'])) ? $args['submit'] : '', 'GETPOST');
        $to_topic_id   = (int)$this->request->query->get('to_topic_id', (isset($args['to_topic_id'])) ? $args['to_topic_id'] : null);
        $from_topic_id = (int)$this->request->query->get('from_topic_id', (isset($args['from_topic_id'])) ? $args['from_topic_id'] : null);
    
        $post = ModUtil::apiFunc('Dizkus', 'user', 'readpost', array('post_id' => $post_id));
    
        if (!allowedtomoderatecategoryandforum($post['cat_id'], $post['forum_id'])) {
            // user is not allowed to moderate this forum
            return LogUtil::registerPermissionError();
        }
    
        if (!$submit) {
            $this->view->assign('post', $post);
            $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
    
            return $this->view->fetch('user/topic/join.tpl');
    
        } else {
            /*if (!SecurityUtil::confirmAuthKey()) {
                return LogUtil::registerAuthidError();
            }*/
    
            // check if from_topic exists. this function will return an error if not
            $from_topic = ModUtil::apiFunc('Dizkus', 'user', 'readtopic', array('topic_id' => $from_topic_id, 'complete' => false, 'count' => false));
            // check if to_topic exists. this function will return an error if not
            $to_topic = ModUtil::apiFunc('Dizkus', 'user', 'readtopic', array('topic_id' => $to_topic_id, 'complete' => false, 'count' => false));
            // submit is set, we split the topic now
            //$post['new_topic'] = $totopic;
            //$post['old_topic'] = $old_topic;
            $res = ModUtil::apiFunc('Dizkus', 'user', 'jointopics', array('from_topic' => $from_topic,
                                                                       'to_topic'   => $to_topic));
    
            return System::redirect(ModUtil::url('Dizkus', 'user', 'viewtopic', array('topic' => $res)));
        }
    }
    
    /**
     * moderateforum
     *
     * Simple moderation of multiple topics.
     *
     * @param array $args The Arguments array.
     *
     * @return string
     */
    public function moderateforum($args=array())
    {
        // Permission check
        $this->throwForbiddenUnless(
            ModUtil::apiFunc($this->name, 'Permission', 'canRead')
        );
    
        // get the input
        $forum_id  = (int)FormUtil::getPassedValue('forum', (isset($args['forum'])) ? $args['forum'] : null, 'GETPOST');
        $start     = (int)FormUtil::getPassedValue('start', (isset($args['start'])) ? $args['start'] : 0, 'GETPOST');
        $mode      = FormUtil::getPassedValue('mode', (isset($args['mode'])) ? $args['mode'] : '', 'GETPOST');
        $submit    = FormUtil::getPassedValue('submit', (isset($args['submit'])) ? $args['submit'] : '', 'GETPOST');
        $topic_ids = FormUtil::getPassedValue('topic_id', (isset($args['topic_id'])) ? $args['topic_id'] : array(), 'GETPOST');
        $shadow    = FormUtil::getPassedValue('createshadowtopic', (isset($args['createshadowtopic'])) ? $args['createshadowtopic'] : '', 'GETPOST');
        $moveto    = (int)FormUtil::getPassedValue('moveto', (isset($args['moveto'])) ? $args['moveto'] : null, 'GETPOST');
        $jointo    = (int)FormUtil::getPassedValue('jointo', (isset($args['jointo'])) ? $args['jointo'] : null, 'GETPOST');
    
        $shadow = (empty($shadow)) ? false : true;
    
        list($last_visit, $last_visit_unix) = ModUtil::apiFunc('Dizkus', 'user', 'setcookies');
    
        // Get the Forum for Display and Permission-Check
        $forum = ModUtil::apiFunc('Dizkus', 'user', 'readforum',
                              array('forum_id'        => $forum_id,
                                    'start'           => $start,
                                    'last_visit'      => $last_visit,
                                    'last_visit_unix' => $last_visit_unix));
    
        if (!allowedtomoderatecategoryandforum($forum['cat_id'], $forum['forum_id'])) {
            // user is not allowed to moderate this forum
            return LogUtil::registerPermissionError();
        }
    
    
        // Submit isn't set'
        if (empty($submit)) {
            $this->view->assign('forum_id', $forum_id);
            $this->view->assign('mode',$mode);
            $this->view->assign('topic_ids', $topic_ids);
            $this->view->assign('last_visit', $last_visit);
            $this->view->assign('last_visit_unix', $last_visit_unix);
            $this->view->assign('forum',$forum);
            $this->view->assign('favorites', ModUtil::apifunc('Dizkus', 'user', 'get_favorite_status'));
            // For Movetopic
            $this->view->assign('forums', ModUtil::apiFunc('Dizkus', 'user', 'readuserforums'));
    
            return $this->view->fetch('user/forum/moderate.tpl');
    
        } else {
            // submit is set
            //if (!SecurityUtil::confirmAuthKey()) {
            //    return LogUtil::registerAuthidError();
            //}*/
            if (count($topic_ids) <> 0) {
                switch ($mode)
                {
                    case 'del':
                    case 'delete':
                        foreach ($topic_ids as $topic_id) {
                            $forum_id = ModUtil::apiFunc('Dizkus', 'user', 'deletetopic', array('topic_id' => $topic_id));
                        }
                        break;
    
                    case 'move':
                        if (empty($moveto)) {
                            return LogUtil::registerError($this->__('Error! You did not select a target forum for the move.'), null, ModUtil::url('Dizkus', 'user', 'moderateforum', array('forum' => $forum_id)));
                        }
                        foreach ($topic_ids as $topic_id) {
                            ModUtil::apiFunc('Dizkus', 'user', 'movetopic',
                                         array('topic_id' => $topic_id,
                                               'forum_id' => $moveto,
                                               'shadow'   => $shadow ));
                        }
                        break;
    
                    case 'lock':
                    case 'unlock':
                        foreach ($topic_ids as $topic_id) {
                            ModUtil::apiFunc('Dizkus', 'user', 'lockunlocktopic', array('topic_id'=> $topic_id, 'mode'=>$mode));
                        }
                        break;
    
                    case 'sticky':
                    case 'unsticky':
                        foreach ($topic_ids as $topic_id) {
                            ModUtil::apiFunc('Dizkus', 'user', 'stickyunstickytopic', array('topic_id'=> $topic_id, 'mode'=>$mode));
                        }
                        break;
    
                    case 'join':
                        if (empty($jointo)) {
                            return LogUtil::registerError($this->__('Error! You did not select a target topic to join.'), null, ModUtil::url('Dizkus', 'user', 'moderateforum', array('forum' => $forum_id)));
                        }
                        if (in_array($jointo, $topic_ids)) {
                            // jointo, the target topic, is part of the topics to join
                            // we remove this to avoid a loop
                            $fliparray = array_flip($topic_ids);
                            unset($fliparray[$jointo]);
                            $topic_ids = array_flip($fliparray);
                        }
                        foreach ($topic_ids as $from_topic_id) {
                            ModUtil::apiFunc('Dizkus', 'user', 'jointopics', array('from_topic_id' => $from_topic_id,
                                                                                'to_topic_id'   => $jointo));
                        }
                        break;
    
                    default:
                }
    
                // Refresh Forum Info
                $forum = ModUtil::apiFunc('Dizkus', 'user', 'readforum',
                                  array('forum_id'        => $forum_id,
                                        'start'           => $start,
                                        'last_visit'      => $last_visit,
                                        'last_visit_unix' => $last_visit_unix));
            }
        }
    
        return System::redirect(ModUtil::url('Dizkus', 'user', 'moderateforum', array('forum' => $forum_id)));
    }
    
    /**
     * report
     *
     * Notify a moderator about a posting.
     *
     * @return string
     */
    public function report()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/notifymod.tpl', new Dizkus_Form_Handler_User_Report());
    }
    
    /**
     * topicsubscriptions
     *
     * Manage the users topic subscription.
     *
     * @return string
     */
    public function topicsubscriptions()
    {
        $form = FormUtil::newForm($this->name, $this);
        return $form->execute('user/topic/subscriptions.tpl', new Dizkus_Form_Handler_User_TopicSubscriptions());
    }

}