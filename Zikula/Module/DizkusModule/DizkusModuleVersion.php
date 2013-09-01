<?php

/**
 * general module informations
 * @author Andreas Krapohl
 * @copyright 2003 by Andreas Krapohl, 2004 by Frank Schummertz
 * @package Dizkus
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link https://github.com/zikula-modules/Dizkus
 */

namespace Zikula\Module\DizkusModule;

use HookUtil;
use ModUtil;
use Zikula_HookManager_SubscriberBundle;
use Zikula_HookManager_ProviderBundle;

/**
 * Provides metadata for this module to the Extensions module.
 */
class DizkusModuleVersion extends \Zikula_AbstractVersion
{

    const PROVIDER_UIAREANAME = 'provider.dizkus.ui_hooks.topic';

    /**
     * Assemble and return module metadata.
     *
     * @return array Module metadata.
     */
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname'] = $this->__('Dizkus forums');
        $meta['description'] = $this->__('An integrated discussion forum for Zikula.');
        $meta['url'] = $this->__('forums');
        $meta['version'] = '3.2.0';
        // will be 4.0.0 on release
        $meta['core_min'] = '1.3.6';
        // Fixed to 1.3.x range
        $meta['core_max'] = '1.3.99';
        // Fixed to 1.3.x range
        $meta['securityschema'] = array(
            'Dizkus::' => 'ForumID::',
            'Dizkus::CreateForum' => 'ForumID::');
        $meta['capabilities'] = array(
            HookUtil::SUBSCRIBER_CAPABLE => array(
                'enabled' => true),
            HookUtil::PROVIDER_CAPABLE => array(
                'enabled' => true));
        // module depedencies
        $meta['dependencies'] = array(
            array(
                'modname' => 'LuMicuLa',
                'minversion' => '0.1.0',
                'maxversion' => '',
                'status' => ModUtil::DEPENDENCY_RECOMMENDED),
            array(
                'modname' => 'Scribite',
                'minversion' => '5.0.0',
                'maxversion' => '',
                'status' => ModUtil::DEPENDENCY_RECOMMENDED),
            array(
                'modname' => 'BBCode',
                'minversion' => '3.0.0',
                'maxversion' => '',
                'status' => ModUtil::DEPENDENCY_RECOMMENDED),
            array(
                'modname' => 'BBSmile',
                'minversion' => '3.0.0',
                'maxversion' => '',
                'status' => ModUtil::DEPENDENCY_RECOMMENDED));

        return $meta;
    }

    /**
     * Define the hook bundles supported by this module.
     *
     * @return void
     */
    protected function setupHookBundles()
    {
        // Post Subscriber Hooks
        $bundle1 = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.dizkus.ui_hooks.post', 'ui_hooks', $this->__('Dizkus post hook'));
        $bundle1->addEvent('display_view', 'dizkus.ui_hooks.post.ui_view');
        $bundle1->addEvent('form_edit', 'dizkus.ui_hooks.post.ui_edit');
        $bundle1->addEvent('form_delete', 'dizkus.ui_hooks.post.ui_delete');
        $bundle1->addEvent('validate_edit', 'dizkus.ui_hooks.post.validate_edit');
        $bundle1->addEvent('validate_delete', 'dizkus.ui_hooks.post.validate_delete');
        $bundle1->addEvent('process_edit', 'dizkus.ui_hooks.post.process_edit');
        $bundle1->addEvent('process_delete', 'dizkus.ui_hooks.post.process_delete');
        $this->registerHookSubscriberBundle($bundle1);
        // Post Filter Hooks
        $bundle4 = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.dizkus.filter_hooks.post', 'filter_hooks', $this->__('Dizkus post filter'));
        $bundle4->addEvent('filter', 'dizkus.filter_hooks.post.filter');
        $this->registerHookSubscriberBundle($bundle4);
        // Topic Subscriber Hooks
        $bundle2 = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.dizkus.ui_hooks.topic', 'ui_hooks', $this->__('Dizkus topic hook'));
        $bundle2->addEvent('display_view', 'dizkus.ui_hooks.topic.ui_view');
        $bundle2->addEvent('form_edit', 'dizkus.ui_hooks.topic.ui_edit');
        $bundle2->addEvent('form_delete', 'dizkus.ui_hooks.topic.ui_delete');
        $bundle2->addEvent('validate_edit', 'dizkus.ui_hooks.topic.validate_edit');
        $bundle2->addEvent('validate_delete', 'dizkus.ui_hooks.topic.validate_delete');
        $bundle2->addEvent('process_edit', 'dizkus.ui_hooks.topic.process_edit');
        $bundle2->addEvent('process_delete', 'dizkus.ui_hooks.topic.process_delete');
        $this->registerHookSubscriberBundle($bundle2);
        // Forum Subscriber Hooks
        $bundle3 = new Zikula_HookManager_SubscriberBundle($this->name, 'subscriber.dizkus.ui_hooks.forum', 'ui_hooks', $this->__('Dizkus forum hook'));
        $bundle3->addEvent('display_view', 'dizkus.ui_hooks.forum.ui_view');
        $bundle3->addEvent('form_edit', 'dizkus.ui_hooks.forum.ui_edit');
        $bundle3->addEvent('form_delete', 'dizkus.ui_hooks.forum.ui_delete');
        $bundle3->addEvent('validate_edit', 'dizkus.ui_hooks.forum.validate_edit');
        $bundle3->addEvent('validate_delete', 'dizkus.ui_hooks.forum.validate_delete');
        $bundle3->addEvent('process_edit', 'dizkus.ui_hooks.forum.process_edit');
        $bundle3->addEvent('process_delete', 'dizkus.ui_hooks.forum.process_delete');
        $this->registerHookSubscriberBundle($bundle3);
        // Topic Provider Hooks
        $bundle5 = new Zikula_HookManager_ProviderBundle($this->name, self::PROVIDER_UIAREANAME, 'ui_hooks', $this->__('Dizkus topic provider hook'));
        $bundle5->addServiceHandler('display_view', 'Zikula\Module\DizkusModule\HookHandlers', 'uiView', 'dizkus.hooks.topic');
        $bundle5->addServiceHandler('form_edit', 'Zikula\Module\DizkusModule\HookHandlers', 'uiEdit', 'dizkus.hooks.topic');
        $bundle5->addServiceHandler('form_delete', 'Zikula\Module\DizkusModule\HookHandlers', 'uiDelete', 'dizkus.hooks.topic');
        $bundle5->addServiceHandler('validate_edit', 'Zikula\Module\DizkusModule\HookHandlers', 'validateEdit', 'dizkus.hooks.topic');
        $bundle5->addServiceHandler('validate_delete', 'Zikula\Module\DizkusModule\HookHandlers', 'validateDelete', 'dizkus.hooks.topic');
        $bundle5->addServiceHandler('process_edit', 'Zikula\Module\DizkusModule\HookHandlers', 'processEdit', 'dizkus.hooks.topic');
        $bundle5->addServiceHandler('process_delete', 'Zikula\Module\DizkusModule\HookHandlers', 'processDelete', 'dizkus.hooks.topic');
        $this->registerHookProviderBundle($bundle5);
    }

}