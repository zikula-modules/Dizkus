<?php

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 *
 * @see https://github.com/zikula-modules/Dizkus
 *
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

namespace Zikula\DizkusModule\Hooks;

use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Bundle\HookBundle\Category\UiHooksCategory;
use Zikula\Bundle\HookBundle\HookSubscriberInterface;

/**
 * PostSubBundle
 *
 * @author Kaik
 */
class PostSubBundle extends AbstractSubBundle implements HookSubscriberInterface
{
    const EDIT_DISPLAY = 'dizkus.ui_hooks.post.display_view';
    const EDIT_FORM = 'dizkus.ui_hooks.post.form_edit';
    const EDIT_VALIDATE = 'dizkus.ui_hooks.post.validate_edit';
    const EDIT_PROCESS = 'dizkus.ui_hooks.post.process_edit';
    const DELETE_FORM = 'dizkus.ui_hooks.post.form_delete';
    const DELETE_VALIDATE = 'dizkus.ui_hooks.post.validate_delete';
    const DELETE_PROCESS = 'dizkus.ui_hooks.post.process_delete';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct();
    }

    public function getCategory()
    {
        return UiHooksCategory::NAME;
    }

    public function getTitle()
    {
        return $this->translator->__('Dizkus post subscriber');
    }

    public function getEvents()
    {
        return [
            UiHooksCategory::TYPE_DISPLAY_VIEW => self::EDIT_DISPLAY,
            UiHooksCategory::TYPE_FORM_EDIT => self::EDIT_FORM,
            UiHooksCategory::TYPE_VALIDATE_EDIT => self::EDIT_VALIDATE,
            UiHooksCategory::TYPE_PROCESS_EDIT => self::EDIT_PROCESS,
            UiHooksCategory::TYPE_FORM_DELETE => self::DELETE_FORM,
            UiHooksCategory::TYPE_VALIDATE_DELETE => self::DELETE_VALIDATE,
            UiHooksCategory::TYPE_PROCESS_DELETE => self::DELETE_PROCESS,
        ];
    }
}
