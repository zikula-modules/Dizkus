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

use Doctrine\Common\Collections\ArrayCollection;
use Zikula\Bundle\HookBundle\Bundle\ProviderBundle;

/**
 * AbstractProBundle
 *
 * @author Kaik
 */
abstract class AbstractProBundle extends ProviderBundle implements \ArrayAccess
{
    private $baseName;

    private $areaData;

    private $modules;

    private $settings = [];

    public function __construct($owner, $area, $category, $title)
    {
        parent::__construct($owner, $area, $category, $title);
        $this->baseName= str_replace('ProBundle', 'Provider', str_replace('Zikula\DizkusModule\Hooks\\', '', get_class($this)));
        $this->modules = new ArrayCollection();
    }

    public function getSettingsForm()
    {
        return 'Zikula\\DizkusModule\\Form\\Type\\Hook\\ProviderSettingsType';
    }

    public function getBindingForm()
    {
        return false;
    }

    public function getBaseName()
    {
        return $this->baseName;
    }

    public function setAreaObject($area)
    {
        $this->areaData = $area;
    }

    public function setSettings($hooked)
    {
        $this->settings = $hooked;
    }

    public function setModules($modules)
    {
        $this->modules = $modules;

        return $this;
    }

    public function getModules()
    {
        return $this->modules;
    }

    public function offsetExists($offset)
    {
        switch ($offset) {
            case 'modules':

                return true;
            default:
                if (strpos($offset, 'Module') === true) {
                    return $this->modules->offsetExists($offset);
                } else {
                    return array_key_exists($offset, $this->settings);
                }
        }
    }

    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'modules':

                return $this->getModules();
            default:
                if (strpos($offset, 'Module') === true) {
                    return $this->modules->offsetGet($offset);
                } else {
                    return $this->offsetExists($offset) ? $this->settings[$offset] : false;
                }
        }
    }

    public function offsetSet($offset, $value)
    {
        switch ($offset) {
            case 'modules':

                return $this->setModules($value);

            default:
                if (strpos($offset, 'Module') === true) {
                    return $this->modules->offsetSet($offset, $value);
                } else {
                    return $this->offsetExists($offset) ? $this->settings[$offset] = $value : false;
                }
        }
    }

    public function offsetUnset($offset)
    {
        switch ($offset) {
            case 'modules':

                return $this->getModules()->clear();

            default:
                if (strpos($offset, 'Module') === true) {
                    return $this->modules->offsetUnset($offset);
                } else {
                    return true;
                }
        }
    }
}
