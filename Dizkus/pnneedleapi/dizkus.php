<?php
/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 * @link http://www.dizkus.com
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Dizkus
 */

Loader::includeOnce('modules/Dizkus/common.php');

/**
 * Dizkus needle
 * @param $args['nid'] needle id
 * @return array()
 */
function Dizkus_needleapi_dizkus($args)
{
    $dom = ZLanguage::getModuleDomain('Dizkus');

    // Get arguments from argument array
    $nid = $args['nid'];
    unset($args);

    // cache the results
    static $cache;
    if (!isset($cache)) {
        $cache = array();
    } 

    if (!empty($nid)) {
        if (!isset($cache[$nid])) {
            // not in cache array
            // set the default
            $cache[$nid] = '';

            if (pnModAvailable('Dizkus')) {
                // nid is like F-## or T-##
                $temp = explode('-', $nid);
                $type = '';
                if (is_array($temp) && count($temp)==2) {
                    $type = $temp[0];
                    $id   = $temp[1];
                }

                pnModDBInfoLoad('Dizkus');
                $pntable = pnDBGetTables();

                switch ($type) {
                    case 'F':
                        $sql = 'SELECT forum_name,
                                       cat_id
                                FROM   ' . $pntable['dizkus_forums'] . '
                                WHERE  forum_id=' . (int)DataUtil::formatForStore($id);
                        $res = DBUtil::executeSQL($sql);
                        $colarray = array('forum_name', 'cat_id');
                        $result    = DBUtil::marshallObjects($res, $colarray);
                        
                        if (is_array($result) && !empty($result)) {
                            if (allowedtoreadcategoryandforum($result[0]['cat_id'], $id)) {
                                $url   = DataUtil::formatForDisplay(pnModURL('Dizkus', 'user', 'viewforum', array('forum' => $id)));
                                $title = DataUtil::formatForDisplay($result[0]['forum_name']);
                                $cache[$nid] = '<a href="' . $url . '" title="' . $title . '">' . $title . '</a>';
                            } else {
                                $cache[$nid] = '<em>' . __f('Error! You do not have the necessary authorisation for forum ID %s.', $id, $dom) . '</em>';
                            }
                        } else {
                            $cache[$nid] = '<em>' . __f('Error! The forum ID %s is unknown.', $id, $dom) . '</em>';
                        }
                        break;

                    case 'T':
                        $sql = 'SELECT    t.topic_title,
                                          t.forum_id,
                                          f.cat_id 
                                FROM      ' . $pntable['dizkus_topics'] . ' as t
                                LEFT JOIN ' . $pntable['dizkus_forums'] . ' as f
                                ON        f.forum_id=t.forum_id
                                WHERE     t.topic_id=' . DataUtil::formatForStore($id);
                        $res = DBUtil::executeSQL($sql);
                        $colarray = array('topic_title', 'forum_id', 'cat_id');
                        $result    = DBUtil::marshallObjects($res, $colarray);
                        
                        if (is_array($result) && !empty($result)) {
                            if (allowedtoreadcategoryandforum($result[0]['cat_id'], $result[0]['forum_id'])) {
                                $url   = DataUtil::formatForDisplay(pnModURL('Dizkus', 'user', 'viewtopic', array('topic' => $id)));
                                $title = DataUtil::formatForDisplay($result[0]['topic_title']);
                                $cache[$nid] = '<a href="' . $url . '" title="' . $title . '">' . $title . '</a>';
                            } else {
                                $cache[$nid] = '<em>' . __f('Error! You do not have the necessary authorisation for topic ID %s.', $id , $dom) . '</em>';
                            }
                        } else {
                            $cache[$nid] = '<em>' . __f('Error! The topic ID %s is unknown.', $id, $dom) .'</em>';
                        }
                        break;

                    default:
                        $cache[$nid] = '<em>' . __("Error! Unknown parameter at position #1 ('F' or 'T').", $dom) . '</em>';
                }
            } else {
                $cache[$nid] = '<em>' . __('Error! The Dizkus module is not available.', $dom) . '</em>';
            }    
        }
        $result = $cache[$nid];
    } else {
        $result = '<em>' . __('Error! No needle ID.', $dom) . '</em>';
    }

    return $result;
}
