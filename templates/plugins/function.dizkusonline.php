<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link      https://github.com/zikula-modules/Dizkus
 * @license   GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package   Dizkus
 */

/**
 * Renderer plugin
 * This file is a plugin for Renderer, the Zikula implementation of Smarty
 *
 * @package      Xanthia_Templating_Environment
 * @subpackage   Renderer
 * @version      $Id$
 * @author       The Zikula development team
 * @link         http://www.zikula.org  The Zikula Home Page
 * @copyright    Copyright (C) 2002 by the Zikula Development Team
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */


/**
 * Smarty function to read the users who are online
 * This function returns an array (ig assign is used) or four variables
 * numguests : number of guests online
 * numusers: number of users online
 * total: numguests + numusers
 * unames: array of 'uid', (int, userid), 'uname' (string, username) and 'admin' (boolean, true if users is a moderator)
 * Available parameters:
 *   - assign:       If set, the results are assigned to the corresponding variable
 *   - checkgroups:  If set, checks if the users found are in the moderator groups (perforance issue!) default is no group check
 * Example
 *   {dizkusonline assign="islogged"}
 *
 * @author       Frank Chestnut
 * @since        10/10/2005
 *
 * @param        array       $params    All attributes passed to this function from the template
 * @param        object      &$view     Reference to the Smarty object
 *
 * @return       array
 */

function smarty_function_dizkusonline($params, Zikula_View $view)
{
    if (!isset($params['category_id'])) {
        $params['category_id'] = (isset($view->_tpl_vars['viewcat']) && $view->_tpl_vars['viewcat'] != -1) ? $view->_tpl_vars['viewcat'] : '';
    }
    if (!isset($params['forum_id'])) {
        $params['forum_id'] = isset($view->_tpl_vars['forum']) ? $view->_tpl_vars['forum'] : '';
    }

    $params['checkgroups'] = (isset($params['checkgroups'])) ? true : false;

    $ztable = DBUtil::getTables();

    $sessioninfocolumn = $ztable['session_info_column'];
    $sessioninfotable = $ztable['session_info'];

    $activetime = DateUtil::getDateTime(time() - (System::getVar('secinactivemins') * 60));

    // set some defaults
    $numguests = 0;
    $numusers = 0;
    $unames = array();

    $moderators = ModUtil::apiFunc('Dizkus', 'user', 'get_moderators', array());

    /** @var $em Doctrine\ORM\EntityManager */
    $em = $view->getContainer()->get('doctrine.entitymanager');

    if (System::getVar('anonymoussessions')) {
        $anonwhere = "AND s.uid >= '0'";
    } else {
        $anonwhere = "AND s.uid > '0'";
    }
    $dql = "SELECT s.uid, s.uname
            FROM Users\Entity\UserSessionEntity s, Users\Entity\UserEntity u
            WHERE s.lastused > '$activetime'
            $anonwhere
            AND      IF (s.uid='0','1',s.uid) = u.uid
            GROUP BY s.ipaddr, s.uid";

    $onlineusers = $em->createQuery($dql)->execute(null, \Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
    if (is_array($onlineusers)) {
        $total = count($onlineusers);
        foreach ($onlineusers as $onlineuser) {
            if ($onlineuser['uid'] != 0) {
                $params['user_id'] = $onlineuser['uid'];
                $onlineuser['admin'] = (isset($moderators[$onlineuser['uid']]) && $moderators[$onlineuser['uid']] == $onlineuser['uname']) || ModUtil::apiFunc('Dikus', 'Permission', 'canAdministrate', $params);
                $unames[$onlineuser['uid']] = $onlineuser;
                $numusers++;
            } else {
                $numguests++;
            }
        }
    }

    if ($params['checkgroups'] == true) {
        foreach ($unames as $user) {
            if ($user['admin'] == false) {
                $groups = ModUtil::apiFunc('Groups', 'user', 'getusergroups', array('uid' => $user['uid']));

                foreach ($groups as $group) {
                    if (isset($moderators[$group['gid'] + 1000000])) {
                        $user['admin'] = true;
                    } else {
                        $user['admin'] = false;
                    }
                }
            }

            $users[$user['uid']] = array('uid'   => $user['uid'],
                                         'uname' => $user['uname'],
                                         'admin' => $user['admin']);

        }
        $unames = $users;
    }
    usort($unames, 'cmp_userorder');

    $dizkusonline['numguests'] = $numguests;

    $dizkusonline['numusers'] = $numusers;
    $dizkusonline['total'] = $total;
    $dizkusonline['unames'] = $unames;

    if (isset($params['assign'])) {
        $view->assign($params['assign'], $dizkusonline);
    } else {
        $view->assign($dizkusonline);
    }

    return;
}
