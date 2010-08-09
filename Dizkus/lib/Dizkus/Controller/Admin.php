<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link http://code.zikula.org/dizkus
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

class Dizkus_Controller_Admin extends Zikula_Controller
{

    public function postInitialize()
    {
        $this->view->setCaching(false)->add_core_data();
    }
    /**
     * the main administration function
     *
     */
    public function main()
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        return $this->view->fetch('dizkus_admin_main.html');
    }
    
    /**
     * preferences
     *
     */
    public function preferences()
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        // Create output object
        $form = FormUtil::newForm('Dizkus');
    
        // Return the output that has been generated by this function
        return $form->execute('dizkus_admin_preferences.html', new Dizkus_Form_Handler_Admin_Prefs());
    }
    
    /**
     * syncforums
     */
    public function syncforums()
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
        $silent = FormUtil::getPassedValue('silent', 0);
    
        $messages = array();
    
        ModUtil::apiFunc('Dizkus', 'admin', 'sync',
                     array('type' => 'all users'));
    
        $messages[] = DataUtil::formatForDisplay($this->__('Done! Synchronized Zikula users and Dizkus users.'));
    
        ModUtil::apiFunc('Dizkus', 'admin', 'sync',
                     array('type' => 'all forums'));
    
        $messages[] = DataUtil::formatForDisplay($this->__('Done! Synchronized forum index.'));
    
        ModUtil::apiFunc('Dizkus', 'admin', 'sync',
                     array('type' => 'all topics'));
    
        $messages[] = DataUtil::formatForDisplay($this->__('Done! Synchronized topics.'));
    
        ModUtil::apiFunc('Dizkus', 'admin', 'sync',
                     array('type' => 'all posts'));
    
        $messages[] = DataUtil::formatForDisplay($this->__('Done! Synchronized posts counter.'));
    
        if ($silent != 1) {
            LogUtil::registerStatus($messages);
        }
    
        return System::redirect(ModUtil::url('Dizkus', 'admin', 'main'));
    }
    
    /**
     * ranks
     */
    public function ranks()
    {
    
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $submit   = FormUtil::getPassedValue('submit', null, 'GETPOST');
        $ranktype = (int)FormUtil::getPassedValue('ranktype', 0, 'GETPOST');
    
        if (!$submit) {
            list($rankimages, $ranks) = ModUtil::apiFunc('Dizkus', 'admin', 'readranks',
                                                      array('ranktype' => $ranktype));
    
            $this->view->assign('ranks', $ranks);
            $this->view->assign('ranktype', $ranktype);
            $this->view->assign('rankimages', $rankimages);
    
            if ($ranktype == 0) {
                return $this->view->fetch('dizkus_admin_ranks.html');
            } else {
                return $this->view->fetch('dizkus_admin_honoraryranks.html');
            }
        } else {
            $ranks = FormUtil::getPassedValue('ranks');
            ModUtil::apiFunc('Dizkus', 'admin', 'saverank', array('ranks' => $ranks));
        }
    
        return System::redirect(ModUtil::url('Dizkus','admin', 'ranks', array('ranktype' => $ranktype)));
    }
    
    /**
     * ranks
     */
    public function assignranks()
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $submit     = FormUtil::getPassedValue('submit');
        $letter     = FormUtil::getPassedValue('letter');
        $lastletter = FormUtil::getPassedValue('lastletter');
        $page       = (int)FormUtil::getPassedValue('page', 1, 'GETPOST');
    
        // sync the current user, so that new users
        // get into the Dizkus database
        ModUtil::apiFunc('Dizkus', 'admin', 'sync', array('type' => 'all users')); 
    
        // check for a letter parameter
        if (!empty($lastletter)) {
            $letter = $lastletter;
        }
    
        // count users and forbid '*' if more than 1000 users are present
        $usercount = DBUtil::selectObjectCount('users');
        if (empty($letter) || strlen($letter) != 1 || (($usercount > 1000) && $letter == '*')) {
            $letter = 'a';
        }
        $letter = strtolower($letter);
    
        if (!$submit) {
            list($rankimages, $ranks) = ModUtil::apiFunc('Dizkus', 'admin', 'readranks',
                                                     array('ranktype' => 1));
    
            $tables = DBUtil::getTables();
    
            $userscol  = $tables['users_column'];
            $where     = 'LEFT('.$userscol['uname'].',1) LIKE \''.DataUtil::formatForStore($letter).'%\'';
            $orderby   = $userscol['uname'].' ASC';
            $usercount = DBUtil::selectObjectCount('users', $where);
    
            $perpage = 50;
            if ($page <> -1 && $perpage <> -1) {
                $start = ($page-1) * $perpage;
                $users = DBUtil::selectObjectArray('users', $where, $orderby, $start, $perpage);
            }
    
            $allusers = array();
            foreach ($users as $user)
            {
                if ($user['uid'] == 1)  continue;
    
                $alias = '';
                if (!empty($user['name'])) {
                    $alias = ' (' . $user['name'] . ')';
                }
    
                $user['name'] = $user['uname'] . $alias;
    
                $user['rank_id'] = 0;
                for ($cnt = 0; $cnt < count($ranks); $cnt++) {
                    if (in_array($user['uid'], $ranks[$cnt]['users'])) {
                        $user['rank_id'] = $ranks[$cnt]['rank_id'];
                    }
                }
                array_push($allusers, $user);
            }
    /*
            $inlinecss = '<style type="text/css">' ."\n";
            $rankpath = ModUtil::getVar('Dizkus', 'url_ranks_images') .'/';
            foreach ($ranks as $rank) {
                $inlinecss .= '#dizkus_admin option[value='.$rank['rank_id'].']:before { content:url("'.System::getBaseUrl() . $rankpath . $rank['rank_image'].'"); }' . "\n";
            }
            $inlinecss .= '</style>' . "\n";
            PageUtil::addVar('rawtext', $inlinecss);
    */        
            //usort($allusers, 'cmp_userorder');
    
            unset($users);
    
            $this->view->assign('ranks', $ranks);
            $this->view->assign('rankimages', $rankimages);
            $this->view->assign('allusers', $allusers);
            $this->view->assign('letter', $letter);
            $this->view->assign('page', $page);
            $this->view->assign('perpage', $perpage);
            $this->view->assign('usercount', $usercount);
            $this->view->assign('allow_star', ($usercount < 1000));
    
            return $this->view->fetch('dizkus_admin_assignranks.html');
    
        } else {
            // avoid some vars in the url of the pager
            unset($_GET['submit']);
            unset($_POST['submit']);
            unset($_REQUEST['submit']);
            $setrank = FormUtil::getPassedValue('setrank');
            ModUtil::apiFunc('Dizkus', 'admin', 'assignranksave', 
                         array('setrank' => $setrank));
        }
    
        return System::redirect(ModUtil::url('Dizkus','admin', 'assignranks',
                                   array('letter' => $letter,
                                         'page'   => $page)));
    }
    
    /** 
     * reordertree
     *
     */
    public function reordertree()
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $categorytree = ModUtil::apiFunc('Dizkus', 'user', 'readcategorytree');
        $catids = array();
        $forumids = array();
        if (is_array($categorytree) && count($categorytree) > 0) {
            foreach ($categorytree as $category) {
                $catids[] = $category['cat_id'];
                if (is_array($category['forums']) && count($category['forums']) > 0) {
                    foreach ($category['forums'] as $forum) {
                        $forumids[] = $forum['forum_id'];
                    }
                }
            }
        }
    
        $this->view->assign('categorytree', $categorytree);
        $this->view->assign('catids', $catids);
        $this->view->assign('forumids', $forumids);
    
        return $this->view->fetch('dizkus_admin_reordertree.html');
    }
                    
    /**
     * managesubscriptions
     *
     */
    public function managesubscriptions()
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    
        $submit     = FormUtil::getPassedValue('submit');
        $pnusername = FormUtil::getPassedValue('pnusername');
    
        $pnuid = 0;
        $topicsubscriptions = array();
        $forumsubscriptions = array();
    
        if (!empty($pnusername)) {
            $pnuid = UserUtil::getIDFromName($pnusername);
        }
        if (!empty($pnuid)) {
            $topicsubscriptions = ModUtil::apiFunc('Dizkus', 'user', 'get_topic_subscriptions', array('user_id' => $pnuid));
            $forumsubscriptions = ModUtil::apiFunc('Dizkus', 'user', 'get_forum_subscriptions', array('user_id' => $pnuid));
        }
    
        if (!$submit) {
            // submit is empty
            $this->view->assign('pnusername', $pnusername);
            $this->view->assign('pnuid', $pnuid = UserUtil::getIDFromName($pnusername));
            $this->view->assign('topicsubscriptions', $topicsubscriptions);
            $this->view->assign('forumsubscriptions', $forumsubscriptions);
    
            return $this->view->fetch('dizkus_admin_managesubscriptions.html');
    
        } else {  // submit not empty
            $pnuid      = FormUtil::getPassedValue('pnuid');
            $allforums  = FormUtil::getPassedValue('allforum');
            $forum_ids  = FormUtil::getPassedValue('forum_id');
            $alltopics  = FormUtil::getPassedValue('alltopic');
            $topic_ids  = FormUtil::getPassedValue('topic_id');
    
            if ($allforums == '1') {
                ModUtil::apiFunc('Dizkus', 'user', 'unsubscribe_forum', array('user_id' => $pnuid));
            } elseif (count($forum_ids) > 0) {
                for($i = 0; $i < count($forum_ids); $i++) {
                    ModUtil::apiFunc('Dizkus', 'user', 'unsubscribe_forum', array('user_id' => $pnuid, 'forum_id' => $forum_ids[$i]));
                }
            }
    
            if ($alltopics == '1') {
                ModUtil::apiFunc('Dizkus', 'user', 'unsubscribe_topic', array('user_id' => $pnuid));
            } elseif (count($topic_ids) > 0) {
                for($i = 0; $i < count($topic_ids); $i++) {
                    ModUtil::apiFunc('Dizkus', 'user', 'unsubscribe_topic', array('user_id' => $pnuid, 'topic_id' => $topic_ids[$i]));
                }
            }
        }
    
        return System::redirect(ModUtil::url('Dizkus', 'admin', 'managesubscriptions', array('pnusername' => UserUtil::getVar('uname', $pnuid))));
    }

}