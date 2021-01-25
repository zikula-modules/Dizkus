<?php

declare(strict_types=1);

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @see https://github.com/zikula-modules/Dizkus
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

// @todo when MultiHook will be ready

//namespace Zikula\DizkusModule\Needles;

//use ModUtil;
//use DataUtil;
//use Zikula\DizkusModule\Manager\TopicManager;
//use Zikula\DizkusModule\Manager\ForumManager;

//class Dizkus extends \Zikula_AbstractHelper
//{
//    const NAME = 'ZikulaDizkusModule';
//
//    public function info()
//    {
//        $info = [
//            'module' => self::NAME,
//            'info' => 'DIZKUS{F-forumid|T-topicid}',
//            'inspect' => true];
//        //reverse lookup possible, needs MultiHook_needleapi_dizkus_inspect() function
//        return $info;
//    }
//
//    /**
//     * Dizkus needle
//     * @param $args['nid'] needle id
//     * @return array()
//     */
//    public static function needle($args)
//    {
//        $dom = \ZLanguage::getModuleDomain(self::NAME);
//        // Get arguments from argument array
//        $nid = $args['nid'];
//        unset($args);
//        // cache the results
//        static $cache;
//        if (!isset($cache)) {
//            $cache = [];
//        }
//        if (!empty($nid)) {
//            if (!isset($cache[$nid])) {
//                // not in cache array
//                // set the default
//                $cache[$nid] = '';
//                if (ModUtil::available(self::NAME)) {
//                    // nid is like F-## or T-##
//                    $temp = explode('-', $nid);
//                    $type = '';
//                    if (is_array($temp) && 2 == count($temp)) {
//                        $type = $temp[0];
//                        $id = $temp[1];
//                    }
//                    if (!empty($id)) {
//                        switch ($type) {
//                            case 'F':
//                                $managedForum = new ForumManager($id);
//                                if (!empty($managedForum)) {
//                                    if (ModUtil::apiFunc(self::NAME, 'Permission', 'canRead', $managedForum->get())) {
//                                        $url = \ServiceUtil::getService('router')->generate('zikuladizkusmodule_user_viewforum', ['forum' => $id]);
//                                        $title = DataUtil::formatForDisplay($managedForum->get()->getName());
//                                        $cache[$nid] = '<a href="' . $url . '" title="' . $title . '">' . $title . '</a>';
//                                    } else {
//                                        $cache[$nid] = '<em>' . __f('Error! You do not have the necessary authorisation for forum ID %s.', $id, $dom) . '</em>';
//                                    }
//                                } else {
//                                    $cache[$nid] = '<em>' . __f('Error! The forum ID %s is unknown.', $id, $dom) . '</em>';
//                                }
//
//                                break;
//                            case 'T':
//                                $managedTopic = new TopicManager($id);
//                                if (!empty($managedTopic)) {
//                                    if (ModUtil::apiFunc(self::NAME, 'Permission', 'canRead', $managedTopic->get()->getForum())) {
//                                        $url = \ServiceUtil::getService('router')->generate('zikuladizkusmodule_user_viewtopic', ['topic' => $id]);
//                                        $title = DataUtil::formatForDisplay($managedTopic->get()->getTitle());
//                                        $cache[$nid] = '<a href="' . $url . '" title="' . $title . '">' . $title . '</a>';
//                                    } else {
//                                        $cache[$nid] = '<em>' . __f('Error! You do not have the necessary authorisation for topic ID %s.', $id, $dom) . '</em>';
//                                    }
//                                } else {
//                                    $cache[$nid] = '<em>' . __f('Error! The topic ID %s is unknown.', $id, $dom) . '</em>';
//                                }
//
//                                break;
//                            default:
//                                $cache[$nid] = '<em>' . __('Error! Unknown parameter at position #1 (\'F\' or \'T\').', $dom) . '</em>';
//                        }
//                    }
//                } else {
//                    $cache[$nid] = '<em>' . __('Error! The Dizkus module is not available.', $dom) . '</em>';
//                }
//            }
//            $result = $cache[$nid];
//        } else {
//            $result = '<em>' . __('Error! No needle ID.', $dom) . '</em>';
//        }
//
//        return $result;
//    }
//}
