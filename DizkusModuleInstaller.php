<?php

/**
 * Dizkus.
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 *
 * @see https://github.com/zikula-modules/Dizkus
 *
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */
namespace Zikula\DizkusModule;

use ModUtil;
use ZLanguage;
use System;
use Zikula\DizkusModule\Entity\ForumEntity;
use Zikula\DizkusModule\Entity\RankEntity;
use Zikula\DizkusModule\Entity\ForumUserEntity;
use Zikula\DizkusModule\Entity\ModeratorGroupEntity;
use Zikula\DizkusModule\Connection\Pop3Connection;
use Zikula\Core\AbstractExtensionInstaller;

class DizkusModuleInstaller extends AbstractExtensionInstaller
{
    /**
     * Module name
     * (needed for static methods).
     *
     * @var string
     */
    const MODULENAME = 'ZikulaDizkusModule';

    private $entities = [
        'Zikula\DizkusModule\Entity\ForumEntity',
        'Zikula\DizkusModule\Entity\PostEntity',
        'Zikula\DizkusModule\Entity\TopicEntity',
        'Zikula\DizkusModule\Entity\ForumUserFavoriteEntity',
        'Zikula\DizkusModule\Entity\ForumUserEntity',
        'Zikula\DizkusModule\Entity\ForumSubscriptionEntity',
        'Zikula\DizkusModule\Entity\TopicSubscriptionEntity',
        'Zikula\DizkusModule\Entity\RankEntity',
        'Zikula\DizkusModule\Entity\ModeratorUserEntity',
        'Zikula\DizkusModule\Entity\ModeratorGroupEntity',
    ];

    /**
     *  Initialize a new install of the Dizkus module.
     *
     *  This function will initialize a new installation of Dizkus.
     *  It is accessed via the Zikula Admin interface and should
     *  not be called directly.
     */
    public function install()
    {
        try {
            $this->schemaTool->create($this->entities);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return false;
        }
        // ToDo: create FULLTEXT index
        // set the module vars
        $this->setVars(self::getDefaultVars());
        $this->hookApi->installSubscriberHooks($this->bundle->getMetaData());
        $this->hookApi->installProviderHooks($this->bundle->getMetaData());
        // set up forum root (required)
        $forumRoot = new ForumEntity();
        $forumRoot->setName(ForumEntity::ROOTNAME);
        $forumRoot->lock();
        $this->entityManager->persist($forumRoot);
        // set up EXAMPLE forums
        $this->setUpExampleForums($forumRoot);
        // set up sample ranks
        $this->setUpSampleRanks();
        // Initialisation successful
        return true;
    }

    /**
     * Set up example forums on install.
     */
    private function setUpExampleForums($forumRoot)
    {
        $food = new ForumEntity();
        $food->setName('Food');
        $food->setParent($forumRoot);
        $food->lock();
        $this->entityManager->persist($food);
        $fruits = new ForumEntity();
        $fruits->setName('Fruits');
        $fruits->setParent($food);
        $this->entityManager->persist($fruits);
        $vegetables = new ForumEntity();
        $vegetables->setName('Vegetables');
        $vegetables->setParent($food);
        $this->entityManager->persist($vegetables);
        $carrots = new ForumEntity();
        $carrots->setName('Carrots');
        $carrots->setParent($vegetables);
        $this->entityManager->persist($carrots);
        $this->entityManager->flush();
    }

    private function setUpSampleRanks()
    {
        //title, description, minimumCount, maximumCount, type, image
        $ranks = [
            [
                'title' => 'Level 1',
                'description' => 'New forum user',
                'minimumCount' => 1,
                'maximumCount' => 9,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'zerostar.gif', ],
            [
                'title' => 'Level 2',
                'description' => 'Basic forum user',
                'minimumCount' => 10,
                'maximumCount' => 49,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'onestar.gif', ],
            [
                'title' => 'Level 3',
                'description' => 'Moderate forum user',
                'minimumCount' => 50,
                'maximumCount' => 99,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'twostars.gif', ],
            [
                'title' => 'Level 4',
                'description' => 'Advanced forum user',
                'minimumCount' => 100,
                'maximumCount' => 199,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'threestars.gif', ],
            [
                'title' => 'Level 5',
                'description' => 'Expert forum user',
                'minimumCount' => 200,
                'maximumCount' => 499,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'fourstars.gif', ],
            [
                'title' => 'Level 6',
                'description' => 'Superior forum user',
                'minimumCount' => 500,
                'maximumCount' => 999,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'fivestars.gif', ],
            [
                'title' => 'Level 7',
                'description' => 'Senior forum user',
                'minimumCount' => 1000,
                'maximumCount' => 4999,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'spezstars.gif', ],
            [
                'title' => 'Legend',
                'description' => 'Legend forum user',
                'minimumCount' => 5000,
                'maximumCount' => 1000000,
                'type' => RankEntity::TYPE_POSTCOUNT,
                'image' => 'adminstars.gif', ], ];
        foreach ($ranks as $rank) {
            $r = new RankEntity();
            $r->merge($rank);
            $this->entityManager->persist($r);
        }
        $this->entityManager->flush();
    }

    /**
     *  Deletes an install of the Dizkus module.
     *
     *  This function removes Dizkus from your
     *  Zikula install and should be accessed via
     *  the Zikula Admin interface
     */
    public function uninstall()
    {
        try {
            $this->schemaTool->drop($this->entities);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return false;
        }
        // remove module vars
        $this->delVars();
        // unregister hooks
        $this->hookApi->uninstallSubscriberHooks($this->bundle->getMetaData());
        $this->hookApi->uninstallProviderHooks($this->bundle->getMetaData());
        // Deletion successful
        return true;
    }

    public function upgrade($oldversion)
    {
        // Only support upgrade from version 3.1 and up. Notify users if they have a version below that one.
        if (version_compare($oldversion, '3.1', '<')) {
            // Inform user about error, and how he can upgrade to $modversion
            $upgradeToVersion = $this->bundle->getMetaData()->getVersion();

            $this->addFlash('error', $this->__f('Notice: This version does not support upgrades from versions of Dizkus less than 3.1. Please upgrade to 3.1 before upgrading again to version %s.', $upgradeToVersion));

            return false;
        }
        switch ($oldversion) {
            case '3.1':
            case '3.1.0':
                ini_set('memory_limit', '194M');
                ini_set('max_execution_time', 86400);
                if (!$this->upgrade_to_4_0_0()) {
                    return false;
                }
                break;
            case '4.0.0':
                if (!$this->upgrade_to_4_1_0()) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * get the default module var values.
     *
     * @return array
     */
    public static function getDefaultVars()
    {
        $dom = ZLanguage::getModuleDomain(self::MODULENAME);
        $relativePath = self::generateRelativePath(__DIR__, 'modules');

        return [
            'posts_per_page' => 15,
            'topics_per_page' => 15,
            'hot_threshold' => 20,
            'email_from' => System::getVar('adminmail'),
            'url_ranks_images' => "$relativePath/Resources/public/images/ranks",
            'post_sort_order' => 'ASC',
            'log_ip' => false,
            'extendedsearch' => false,
            'm2f_enabled' => false,
            'favorites_enabled' => true,
            'removesignature' => false,
            'striptags' => true,
            'deletehookaction' => 'lock',
            'rss2f_enabled' => false,
            'timespanforchanges' => 24,
            'forum_enabled' => true,
            'forum_disabled_info' => __('Sorry! The forums are currently off-line for maintenance. Please try later.', $dom),
            'signaturemanagement' => false,
            'signature_start' => '--',
            'signature_end' => '--',
            'showtextinsearchresults' => true,
            'minsearchlength' => 3,
            'maxsearchlength' => 30,
            'fulltextindex' => false,
            'solved_enabled' => true,
            'ajax' => true,
            'striptagsfromemail' => false,
            'indexTo' => '',
            'notifyAdminAsMod' => 2,
            'defaultPoster' => 2,
        ];
    }

    /**
     * find the relative path of this script from current full path.
     *
     * @param string $path default __DIR__
     * @param string $from default 'modules'
     *
     * @return string
     */
    public static function generateRelativePath($path = __DIR__, $from = 'modules')
    {
        $path = realpath($path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($parts as $part) {
            if ($part == $from) {
                return $path;
            } else {
                $path = substr($path, strlen($part.DIRECTORY_SEPARATOR));
            }
        }

        return $path;
    }

    /**
     * upgrade to 4.0.0.
     */
    private function upgrade_to_4_0_0()
    {
        // do a check here for tables containing the prefix and fail if existing tables cannot be found.
        $configPrefix = System::getVar('prefix');
        $prefix = !empty($configPrefix) ? $configPrefix.'_' : '';
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT * FROM '.$prefix.'dizkus_categories';
        $stmt = $connection->prepare($sql);
        try {
            $stmt->execute();
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage().$this->__f('There was a problem recognizing the existing Dizkus tables. Please confirm that your settings for prefix in $ZConfig[\'System\'][\'prefix\'] match the actual Dizkus tables in the database. (Current prefix loaded as `%s`)', $prefix));

            return false;
        }
        // remove the legacy hooks
        $sql = "DELETE FROM hooks WHERE tmodule='Dizkus' OR smodule='Dizkus'";
        $stmt = $connection->prepare($sql);
        $stmt->execute();

        $this->upgrade_to_4_0_0_removeTablePrefixes($prefix);
        // update dizkus_forums to prevent errors in column indexes
        $sql = 'ALTER TABLE dizkus_forums MODIFY forum_last_post_id INT DEFAULT NULL';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $sql = 'UPDATE dizkus_forums SET forum_last_post_id = NULL WHERE forum_last_post_id = 0';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        // get all the pop3 connections & hook references for later re-entry
        $sql = 'SELECT forum_id AS id, forum_moduleref as moduleref, forum_pop3_active AS active, forum_pop3_server AS server, forum_pop3_port AS port, forum_pop3_login AS login,
                forum_pop3_password AS password, forum_pop3_interval AS `interval`, forum_pop3_lastconnect AS lastconnect, forum_pop3_pnuser as userid
                FROM dizkus_forums
                WHERE forum_pop3_active = 1';
        $forumdata = $connection->fetchAll($sql);
        // fetch topic_reference and decode to migrate below (if possible)
        $sql = 'SELECT topic_id, topic_reference
                FROM dizkus_topics
                WHERE topic_reference <> \'\'';
        $hookedTopicData = $connection->fetchAll($sql);
        // delete orphaned topics with no posts to maintain referential integrity
        $sql = 'DELETE from dizkus_topics WHERE topic_last_post_id = 0';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        // NOTE: do not delete users from the dizkus_users table - they must remain for data integrity
        // change default value of rank in dizkus_users from `0` to NULL
        $sql = 'UPDATE dizkus_users SET rank=NULL WHERE rank=0';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        // set rank to NULL where rank no longer available
        $sql = 'UPDATE dizkus_users set rank=NULL WHERE rank NOT IN (SELECT DISTINCT rank_id from dizkus_ranks)';
        $stmt = $connection->prepare($sql);
        $stmt->execute();

        // @todo ?? should do ->? 'ALTER TABLE  `dizkus_forum_favorites` ADD  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST';
        if (!$this->upgrade_to_4_0_0_renameColumns()) {
            return false;
        }
        // update all the tables to 4.0.0
        try {
            $this->schemaTool->update([$this->entities[0]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[1]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[2]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[3]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[4]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[5]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[6]]);
            sleep(1);
            $this->schemaTool->update([$this->entities[7]]);
            sleep(2);
            $this->schemaTool->update([$this->entities[8]]);
            sleep(2);
            $this->schemaTool->update([$this->entities[9]]);
            sleep(2);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return false;
        }
        // migrate data from old formats
        $this->upgrade_to_4_0_0_migrateCategories();
        $this->upgrade_to_4_0_0_updatePosterData();
        $this->upgrade_to_4_0_0_migrateModGroups();
        $this->upgrade_to_4_0_0_migratePop3Connections($forumdata);
        // @todo use $forumdata to migrate forum modulerefs
        $this->upgrade_to_4_0_0_migrateHookedTopics($hookedTopicData);
        $sqls = [];
        $sqls[] = "UPDATE `dizkus_topics` SET `poster`=1 WHERE poster='-1'";
        $sqls[] = "UPDATE `dizkus_posts` SET `poster_id`=1 WHERE poster_id='-1'";
        $sqls[] = 'DELETE FROM `dizkus_subscription` WHERE `user_id` < 2';
        $sqls[] = 'DELETE FROM `dizkus_topic_subscription` WHERE `user_id` < 2';
        $sqls[] = 'DELETE FROM `dizkus_topic_subscription` WHERE topic_id NOT IN (SELECT topic_id from dizkus_topics)';
        foreach ($sqls as $sql) {
            $stmt = $connection->prepare($sql);
            try {
                $stmt->execute();
            } catch (\Exception $e) {
            }
        }
        $this->delVar('autosubscribe');
        $this->delVar('allowgravatars');
        $this->delVar('gravatarimage');
        $this->delVar('ignorelist_handling');
        $this->delVar('hideusers');
        $this->delVar('newtopicconfirmation');
        $this->delVar('slimforum');
        $defaultModuleVars = self::getDefaultVars();
        $this->setVar('url_ranks_images', $defaultModuleVars['url_ranks_images']);
        $this->setVar('fulltextindex', $defaultModuleVars['fulltextindex']); // disable until technology catches up with InnoDB
        $this->setVar('solved_enabled', $defaultModuleVars['solved_enabled']);
        $this->setVar('ajax', $defaultModuleVars['ajax']);
        $this->setVar('defaultPoster', $defaultModuleVars['defaultPoster']);
        $this->setVar('indexTo', $defaultModuleVars['indexTo']);
        $this->setVar('notifyAdminAsMod', $defaultModuleVars['notifyAdminAsMod']);
        //add note about url_ranks_images var
        $this->addFlash('status', $this->__('Double check your path variable setting for rank images in settings!'));

        // register new hooks and event handlers
        $this->hookApi->installSubscriberHooks($this->bundle->getMetaData());
        $this->hookApi->installProviderHooks($this->bundle->getMetaData());
        // update block settings
        $mid = $connection->fetchColumn("SELECT DISTINCT id from modules WHERE name='Dizkus' OR name='".$this->name."'");
        if (!empty($mid) && is_int($mid)) {
            $sql = "UPDATE blocks SET bkey='RecentPostsBlock', content='a:0:{}' WHERE bkey='CenterBlock' AND mid=$mid";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $sql = "UPDATE blocks SET content='a:0:{}' WHERE bkey='StatisticsBlock' AND mid=$mid";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }
        $repArray = ['Dizkus_Centerblock::', 'Dizkus_Statisticsblock', "{$this->name}::RecentPostsBlock", "{$this->name}::StatisticsBlock"];
        $this->addFlash('status', $this->__f('The permission schemas %1$s and %2$s were changed into %3$s and %4$s, respectively. If you were using them please modify your permission table.', $repArray));

        return true;
    }

    /**
     * remove all table prefixes.
     */
    private function upgrade_to_4_0_0_removeTablePrefixes($prefix)
    {
        $connection = $this->entityManager->getConnection();
        // remove table prefixes
        $dizkusTables = [
            'dizkus_categories',
            'dizkus_forum_mods',
            'dizkus_forums',
            'dizkus_posts',
            'dizkus_subscription',
            'dizkus_ranks',
            'dizkus_topics',
            'dizkus_topic_subscription',
            'dizkus_forum_favorites',
            'dizkus_users', ];
        foreach ($dizkusTables as $value) {
            $sql = 'RENAME TABLE '.$prefix.$value.' TO '.$value;
            $stmt = $connection->prepare($sql);
            try {
                $stmt->execute();
            } catch (\Exception $e) {
                $this->addFlash('error', $e);
            }
        }
    }

    /**
     * rename some table columns
     * This must be done before updateSchema takes place.
     */
    private function upgrade_to_4_0_0_renameColumns()
    {
        $connection = $this->entityManager->getConnection();
        $sqls = [];
        // a list of column changes
        $sqls[] = 'ALTER TABLE dizkus_forums CHANGE forum_desc description TEXT DEFAULT NULL';
        $sqls[] = 'ALTER TABLE dizkus_forums CHANGE forum_topics topicCount INT UNSIGNED NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_forums CHANGE forum_posts postCount INT UNSIGNED NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_forums CHANGE forum_moduleref moduleref INT UNSIGNED NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_forums CHANGE forum_name `name` VARCHAR(150) NOT NULL DEFAULT \'\'';
        $sqls[] = 'ALTER TABLE dizkus_forums CHANGE forum_last_post_id last_post_id INT DEFAULT NULL';
        $sqls[] = 'ALTER TABLE dizkus_users CHANGE user_posts postCount INT UNSIGNED NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_users CHANGE user_lastvisit lastvisit DATETIME DEFAULT NULL';
        $sqls[] = 'ALTER TABLE dizkus_users CHANGE user_post_order postOrder INT(1) NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_users CHANGE user_rank rank INT UNSIGNED NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_posts CHANGE post_title title VARCHAR(255) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_posts CHANGE post_msgid msgid VARCHAR(100) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_ranks CHANGE rank_title title VARCHAR(50) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_ranks CHANGE rank_desc description VARCHAR(255) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_ranks CHANGE rank_min minimumCount INT NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_ranks CHANGE rank_max maximumCount INT NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_ranks CHANGE rank_image image VARCHAR(255) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_ranks CHANGE rank_special `type` INT(2) NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_poster poster INT NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_title title VARCHAR(255) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_status status INT NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_views viewCount INT NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_replies replyCount INT UNSIGNED NOT NULL DEFAULT 0';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_reference reference VARCHAR(60) NOT NULL';
        $sqls[] = 'ALTER TABLE dizkus_topics CHANGE topic_last_post_id last_post_id INT DEFAULT NULL';
        foreach ($sqls as $sql) {
            $stmt = $connection->prepare($sql);
            try {
                $stmt->execute();
            } catch (\Exception $e) {
                $this->addFlash('error', $e);

                return false;
            }
        }

        return true;
    }

    /**
     * Migrate categories from 3.1 > 4.0.0.
     */
    private function upgrade_to_4_0_0_migrateCategories()
    {
        // set up forum root
        $forumRoot = new ForumEntity();
        $forumRoot->setName(ForumEntity::ROOTNAME);
        $forumRoot->lock();
        $this->entityManager->persist($forumRoot);
        $this->entityManager->flush();
        $connection = $this->entityManager->getConnection();
        // Move old categories into new tree as Forums
        $sql = 'SELECT * FROM dizkus_categories ORDER BY cat_order ASC';
        $categories = $connection->fetchAll($sql);
        $sqls = [];
        foreach ($categories as $category) {
            // create new category forum with old name
            $newCatForum = new ForumEntity();
            $newCatForum->setName($category['cat_title']);
            $newCatForum->setParent($forumRoot);
            $newCatForum->lock();
            $this->entityManager->persist($newCatForum);
            $this->entityManager->flush();
            // create sql to update parent on child forums
            $sqls[] = 'UPDATE dizkus_forums SET parent = '.$newCatForum->getForum_id().', lvl=2 WHERE cat_id = '.$category['cat_id'];
        }
        // update child forums
        foreach ($sqls as $sql) {
            $connection->executeQuery($sql);
        }
        // correct the forum tree MANUALLY
        // we know that the forum can only be two levels deep (root -> parent -> child)
        $count = 1;
        $sqls = [];
        $categories = $connection->fetchAll('SELECT * FROM dizkus_forums WHERE lvl = 1');
        foreach ($categories as $category) {
            $category['l'] = ++$count;
            $children = $connection->fetchAll("SELECT * FROM dizkus_forums WHERE parent = $category[forum_id]");
            foreach ($children as $child) {
                $left = ++$count;
                $right = ++$count;
                $sqls[] = "UPDATE dizkus_forums SET forum_order = $left, rgt = $right WHERE forum_id = $child[forum_id]";
            }
            $right = ++$count;
            $sqls[] = "UPDATE dizkus_forums SET forum_order = $category[l], rgt = $right WHERE forum_id = $category[forum_id]";
        }
        $right = ++$count;
        $sqls[] = "UPDATE dizkus_forums SET forum_order = 1, rgt = $right WHERE parent IS NULL";
        $sqls[] = 'UPDATE dizkus_forums SET cat_id = 1 WHERE 1';

        foreach ($sqls as $sql) {
            $connection->executeQuery($sql);
        }

        // drop the old categories table
        $sql = 'DROP TABLE dizkus_categories';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
    }

    /**
     * Update Poster Data from 3.1 > 4.0.0.
     */
    private function upgrade_to_4_0_0_updatePosterData()
    {
        $connection = $this->entityManager->getConnection();
        $Posters = $connection->executeQuery('SELECT DISTINCT poster_id from dizkus_posts WHERE poster_id NOT IN (SELECT DISTINCT user_id FROM dizkus_users)');
        $newUserCount = 0;
        foreach ($Posters as $poster) {
            $posterId = $poster['poster_id'];
            if ($posterId > 0) {
                $forumUser = $this->entityManager->getRepository('Zikula\DizkusModule\Entity\ForumUserEntity')->find($posterId);
                // if a ForumUser cannot be found, create one
                if (!$forumUser) {
                    $forumUser = new ForumUserEntity($posterId);
                    $this->entityManager->persist($forumUser);
                    ++$newUserCount;
                }
            }
            if ($newUserCount > 20) {
                $this->entityManager->flush();
                $newUserCount = 0;
            }
        }
        if ($newUserCount > 0) {
            $this->entityManager->flush();
        }
        ModUtil::apiFunc($this->name, 'Sync', 'all');
    }

    /**
     * Migrate the Moderator Groups out of the `dizkus_forum_mods` table and put
     * in the new `dizkus_forum_mods_group` table.
     */
    private function upgrade_to_4_0_0_migrateModGroups()
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT * FROM dizkus_forum_mods WHERE user_id > 1000000';
        $groups = $connection->fetchAll($sql);
        foreach ($groups as $group) {
            $groupId = $group['user_id'] - 1000000;
            $modGroup = new ModeratorGroupEntity();
            $coreGroup = $this->entityManager->find('Zikula\\GroupsModule\\Entity\\GroupEntity', $groupId);
            if ($coreGroup) {
                $modGroup->setGroup($coreGroup);
                $forum = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumEntity', $group['forum_id']);
                $modGroup->setForum($forum);
                $this->entityManager->persist($modGroup);
            }
        }
        $this->entityManager->flush();
        // remove old group entries
        $sql = 'DELETE FROM dizkus_forum_mods WHERE user_id > 1000000';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
    }

    /**
     * migrate pop3 connection data from multiple columns to one object.
     *
     * @param type $connections
     */
    private function upgrade_to_4_0_0_migratePop3Connections($connections)
    {
        foreach ($connections as $connectionData) {
            $connectionData['coreUser'] = $this->entityManager->getReference('Zikula\\UsersModule\\Entity\\UserEntity', $connectionData['userid']);
            $connection = new Pop3Connection($connectionData);
            $forum = $this->entityManager->find('Zikula\DizkusModule\Entity\ForumEntity', $connectionData['id']);
            $forum->setPop3Connection($connection);
        }
        $this->entityManager->flush();
    }

    /**
     * migrate hooked topics data to maintain hook connection with original object.
     *
     * This routine will only attempt to migrate references where the topic_reference field
     * looks like `moduleID-objectId` -> e.g. '14-57'. If the field contains any underscores
     * the topic will be locked and the reference left unmigrated. This is mainly because
     * modules that use that style of reference are not compatible with Core 1.4.0+
     * anyway and so migrating their references would be pointless.
     *
     * Additionally, if the subscriber module has more than one subscriber area, then migration is
     * also impossible (which to choose?) so the topic is locked and reference left
     * unmigrated also.
     *
     * @param array $rows
     */
    private function upgrade_to_4_0_0_migrateHookedTopics($rows)
    {
        $count = 0;
        foreach ($rows as $row) {
            $topic = $this->entityManager->find('Zikula\DizkusModule\Entity\TopicEntity', $row['topic_id']);
            if (isset($topic)) {
                if (strpos($row['topic_reference'], '_') !== false) {
                    // reference contains an unsupported underscore, lock the topic
                    $topic->lock();
                } else {
                    list($moduleId, $objectId) = explode('-', $row['topic_reference']);
                    $moduleInfo = ModUtil::getInfo($moduleId);
                    if ($moduleInfo) {
                        $searchCritera = [
                            'owner' => $moduleInfo['name'],
                            'areatype' => 's',
                            'category' => 'ui_hooks', ];
                        $subscriberArea = $this->entityManager->getRepository('Zikula\\Component\\HookDispatcher\\Storage\\Doctrine\\Entity\\HookAreaEntity')->findBy($searchCritera);
                        if (count($subscriberArea) != 1) {
                            // found either too many areas or none. cannot migrate
                            $topic->lock();
                        } else {
                            // finally set the information
                            $topic->setHookedModule($moduleInfo['name']);
                            $topic->setHookedAreaId($subscriberArea->getId());
                            $topic->setHookedObjectId($objectId);
                        }
                    }
                }
                ++$count;
                if ($count > 20) {
                    $this->entityManager->flush();
                    $count = 0;
                }
            }
        }
        // flush remaining
        $this->entityManager->flush();
    }

    /**
     * upgrade to 4.1.0.
     */
    private function upgrade_to_4_1_0()
    {
        $currentModVars = $this->getVars();
        $this->setVar('log_ip', $currentModVars['log_ip'] === 'yes' ? true : false);
        $this->setVar('extendedsearch', $currentModVars['extendedsearch'] === 'yes' ? true : false);
        $this->setVar('m2f_enabled', $currentModVars['m2f_enabled'] === 'yes' ? true : false);
        $this->setVar('favorites_enabled', $currentModVars['favorites_enabled'] === 'yes' ? true : false);
        $this->setVar('removesignature', $currentModVars['removesignature'] === 'yes' ? true : false);
        $this->setVar('striptags', $currentModVars['striptags'] === 'yes' ? true : false);
        $this->setVar('rss2f_enabled', $currentModVars['rss2f_enabled'] === 'yes' ? true : false);
        $this->setVar('forum_enabled', $currentModVars['forum_enabled'] === 'yes' ? true : false);
        $this->setVar('signaturemanagement', $currentModVars['signaturemanagement'] === 'yes' ? true : false);
        $this->setVar('showtextinsearchresults', $currentModVars['showtextinsearchresults'] === 'yes' ? true : false);
        $this->setVar('fulltextindex', $currentModVars['fulltextindex'] == 'yes' ? true : false); // disable until technology catches up with InnoDB

        return true;
    }
}
