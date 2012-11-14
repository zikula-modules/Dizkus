<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

/**
 * This class provides a handler to edit forums.
 */
class Dizkus_Form_Handler_Admin_ModifyForum extends Zikula_Form_AbstractHandler
{
    /**
     * forum
     *
     * @var statement
     */
    private $forum;


    /**
     * Setup form.
     *
     * @param Zikula_Form_View $view Current Zikula_Form_View instance.
     *
     * @return boolean
     *
     * @throws Zikula_Exception_Forbidden If the current user does not have adequate permissions to perform this function.
     */
    function initialize(Zikula_Form_View $view)
    {
        if (!SecurityUtil::checkPermission('Dizkus::', '::', ACCESS_ADMIN) ) {
            return LogUtil::registerPermissionError();
        }
        
        $id = $this->request->query->get('id');
        
        if ($id) {
            $view->assign('templatetitle', $this->__('Modify forum'));
            $forum = $this->entityManager->find('Dizkus_Entity_Forums', $id);

            if ($forum) {
                $this->view->assign($forum->toArray());
                $this->forum = $forum;
            } else {
                return LogUtil::registerError($this->__f('Article with id %s not found', $id));
            }

            $forum_mods = ModUtil::apiFunc('Dizkus', 'admin', 'readforummods', $forum->getforum_id());
            $view->assign('forum_mods', $forum_mods);

            if ($forum->getforum_pop3_active()) {
                $this->view->assign('extsource', 'mail2forum');
            } else {
                $this->view->assign('extsource', 'noexternal');
            }

            $this->view->assign('parent', $forum->getparent());

        } else {
            $view->assign('templatetitle', $this->__('Create forum'));
        }

        $users  = UserUtil::getAll();
        $groups = UserUtil::getGroups();

        $usersAndGroups = array();
        foreach ($users as $value) {
            $usersAndGroups[] = array(
                'value' => $value['uid'],
                'text'  => $value['uname'],
            );
        }
        foreach ($groups as $value) {
            $usersAndGroups[] = array(
                'value' => '100000'.$value['gid'],
                'text'  => $value['name'].' ('.$this->__('Group').')',
            );
        }

        $view->assign('usersAndGroups', $usersAndGroups)
             ->assign('parents', ModUtil::apiFunc($this->name, 'Forum', 'getTreeAsDropdownList'))
             ->assign('id', $id);

        $this->view->caching = Zikula_View::CACHE_DISABLED;

        return true;
    }




    /**
     * Handle form submission.
     *
     * @param Zikula_Form_View $view  Current Zikula_Form_View instance.
     * @param array            &$args Arguments.
     *
     * @return bool|void
     */
    function handleCommand(Zikula_Form_View $view, &$args)
    {
        $url = ModUtil::url('Dizkus', 'admin', 'tree');

        if ($args['commandName'] == 'cancel') {
            return $view->redirect($url);
        }

        // check for valid form and get data
        if (!$view->isValid()) {
            return false;
        }

        $data = $view->getValues();

        if ($data['extsource'] == 'mail2forum' && $data['pop3_passwordconfirm'] != $data['pop3_password']) {
            return LogUtil::registerError('Passwords are not matching!');
        } else {
            unset($data['pop3_passwordconfirm']);
        }

        if ($data['extsource'] == 'mail2forum' && $data['pnpasswordconfirm'] != $data['pnpassword']) {
            return LogUtil::registerError('Passwords are not matching!');
        } else {
            unset($data['pnpasswordconfirm']);
        }

        $forum_mods = $data['forum_mods'];
        unset($data['forum_mods']);

        // switch between edit and create mode
        $forum = $this->forum ? $this->forum : new Dizkus_Entity_Forums();
        $edit  = $forum->getforum_id();

        $forum->merge($data);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        if ($forum) {
            $moderators = $this->entityManager->getRepository('Dizkus_Entity_Moderators')
                               ->findBy(array('forum_id' => $forum->getforum_id()));

            // remove deselected moderators
            foreach ($moderators as $moderator) {
                $key = array_search($moderator->getuser_id(), $forum_mods);
                if ($key) {
                    unset($forum_mods[$key]);
                } else {
                    $this->entityManager->remove($moderator);
                }
            }
        }

        // insert added moderators
        foreach ($forum_mods as $forum_mod) {
            $newModerator = new Dizkus_Entity_Moderators2();
            $newModerator->setForum_id($forum->getforum_id());
            $newModerator->setUser_id($forum_mod);
            $this->entityManager->persist($newModerator);
        }

        $this->entityManager->flush();

        if ($edit) {
            LogUtil::registerStatus(__('Forum successfully updated.'));
        } else {
            LogUtil::registerStatus(__('Forum successfully created.'));
        }

        // redirect to the admin forum overview
        return $view->redirect($url);
    }
}
