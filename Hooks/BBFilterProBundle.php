<?php

declare(strict_types=1);

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

use Zikula\Bundle\HookBundle\Category\FilterHooksCategory;
use Zikula\Bundle\HookBundle\Hook\FilterHook;
use Zikula\Bundle\HookBundle\HookSelfAllowedProviderInterface;
use Zikula\Bundle\HookBundle\ServiceIdTrait;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\ExtensionsModule\Api\VariableApi;

/**
 * BBFilterProBundle
 *
 * @author Kaik
 */
class BBFilterProBundle extends AbstractProBundle implements HookSelfAllowedProviderInterface
{
    use ServiceIdTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var VariableApi
     */
    private $variableApi;

    private $area = 'provider.dizkus.filter_hooks.bbcode';

    public function __construct(
        TranslatorInterface $translator,
        VariableApi $variableApi
    ) {
        $this->translator = $translator;
        $this->variableApi = $variableApi;

        parent::__construct();
    }

    public function getCategory()
    {
        return FilterHooksCategory::NAME;
    }

    public function getTitle()
    {
        return $this->translator->__('Dizkus BBFilter Provider');
    }

    public function getProviderTypes()
    {
        return [
            FilterHooksCategory::TYPE_FILTER => 'filter',
        ];
    }

    public function getSettingsForm()
    {
        return 'Zikula\\DizkusModule\\Form\\Type\\Hook\\BBFilterProviderSettingsType';
    }

    /**
     * get settings for hook
     * generates value if not yet set.
     *
     * @param $hook
     *
     * @return array
     */
    public function getHookConfig($module, $areaid = null)
    {
        //@todo add filter settings here
        $default = [];
        // module settings
        $settings = $this->variableApi->get($this->getOwner(), 'hooks', false);
        // this provider config
        $config = array_key_exists(str_replace('.', '-', $this->area), $settings['providers']) ? $settings['providers'][str_replace('.', '-', $this->area)] : null;
        // no configuration for this module return default
        if (null === $config) {
            return $default;
        }
        // here we can add provider global settings see Zikula\DizkusModule\Hooks\TopicProBundle

        // module provider area module area settings
        if (array_key_exists($module, $config['modules']) && array_key_exists('areas', $config['modules'][$module]) && array_key_exists(str_replace('.', '-', $areaid), $config['modules'][$module]['areas'])) {
            $subscribedModuleAreaSettings = $config['modules'][$module]['areas'][str_replace('.', '-', $areaid)];
            if (array_key_exists('settings', $subscribedModuleAreaSettings)) {
                // here we do specyfic provider - subscriber settings settings see Zikula\DizkusModule\Hooks\TopicProBundle
            }
        }

        return $default;
    }

    /**
     * @param FilterHook $hook the hook
     *
     * @return void (unused)
     */
    public function filter(FilterHook $hook)
    {
        $this->setSettings($this->getHookConfig($hook->getCaller(), $hook->getAreaId()));
        $message = $hook->getData();
        $hook->setData($this->transform($message));
    }

    /**
     * transform only [quote] and [code] tags.
     */
    public function transform($message)
    {
        //        if(!$this->settings['favorites_enabled']) {
//            return $message;
//        }

        // pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
        // This is important; encode_quote() and encode_code() depend on it.
        $message = ' ' . $message . ' ';

        // If there is a "[" and a "]" in the message, process it.
        if ((mb_strpos($message, '[') && mb_strpos($message, ']'))) {
            // [b] and [/b] for posting bold text in your posts.
            $message = $this->encode_bold($message);
            // [i] and [/i] for posting italic text in your posts. (you can post fa fonts with [i="fa fa-cogs"] [/i])
            $message = $this->encode_italic($message);
            // [url] and [/url] for posting url s in your posts.
            $message = $this->encode_url($message);

            // [CODE] and [/CODE] for posting code (HTML, PHP, C etc etc) in your posts.
            //$message = $this->encode_code($message);

            // [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
            $message = $this->encode_quote($message);
        }

        // Remove added padding from the string..
        $message = mb_substr($message, 1);
        $message = mb_substr($message, 0, -1);

        return $message;
    }

    /**
     * Note: This function assumes the first character of $message is a space, which is added by
     * transform().
     */
    private function encode_bold($message)
    {
        // First things first: If there aren't any "[quote=" or "[quote] strings in the message, we don't
        // need to process it at all.
        if (!mb_strpos(mb_strtolower($message), '[b]')) {
            return $message;
        }
        $boldbody = '<b class="dz-boldtext">%t</b>';

        $stack = [];
        $curr_pos = 1;
        while ($curr_pos && ($curr_pos < mb_strlen($message))) {
            $curr_pos = mb_strpos($message, '[', $curr_pos);

            // If not found, $curr_pos will be 0, and the loop will end.
            if ($curr_pos) {
                // We found a [. It starts at $curr_pos.
                // check if it's a starting or ending bold tag.
                $possible_start = mb_substr($message, $curr_pos, 3);
                $possible_end_pos = mb_strpos($message, ']', $curr_pos);
                $possible_end = mb_substr($message, $curr_pos, $possible_end_pos - $curr_pos + 1);
                if (0 === strcasecmp('[b]', $possible_start)) {
                    // We have a starting bold tag.
                    // Push its position on to the stack, and then keep going to the right.
                    $stack[] = $curr_pos;
                    ++$curr_pos;
                //dump($curr_pos);
                } elseif (0 === strcasecmp('[/b]', $possible_end)) {
                    // We have an ending quote tag.
                    // Check if we've already found a matching starting tag.
                    if (count($stack) > 0) {
                        // There exists a starting tag.
                        // We need to do 2 replacements now.
                        $start_index = array_pop($stack);
                        //dump($start_index);
                        // everything before the [quote=xxx] tag.
                        $before_start_tag = mb_substr($message, 0, $start_index);
                        //dump($before_start_tag);
                        // find the end of the start tag
                        $start_tag_end = mb_strpos($message, ']', $start_index);
                        $start_tag_len = $start_tag_end - $start_index + 1;
                        //dump($start_tag_end);
                        //dump($start_tag_len);
                        // everything after the [quote=xxx] tag, but before the [/quote] tag.
                        $between_tags = mb_substr($message, $start_index + $start_tag_len, $curr_pos - ($start_index + $start_tag_len));
                        //dump($between_tags);
                        // everything after the [/quote] tag.
                        $after_end_tag = mb_substr($message, $curr_pos + 4);
                        //dump($after_end_tag);

                        $boldtext = str_replace('%t', $between_tags, $boldbody);

                        $message = $before_start_tag . $boldtext . $after_end_tag;

                        // Now.. we've screwed up the indices by changing the length of the string.
                        // So, if there's anything in the stack, we want to resume searching just after it.
                        // otherwise, we go back to the start.
                        if (count($stack) > 0) {
                            $curr_pos = array_pop($stack);
                            $stack[] = $curr_pos;
                            ++$curr_pos;
                        } else {
                            $curr_pos = 1;
                        }
                    } else {
                        // No matching start tag found. Increment pos, keep going.
                        ++$curr_pos;
                    }
                } else {
                    // No starting tag or ending tag.. Increment pos, keep looping.,
                    ++$curr_pos;
                }
            }
        } // while

        return $message;
    }

    /**
     * Note: This function assumes the first character of $message is a space, which is added by
     * transform().
     */
    private function encode_italic($message)
    {
        // First things first: If there aren't any "[i=" or "[i] strings in the message, we don't
        // need to process it at all.
        if (!mb_strpos(mb_strtolower($message), '[i]') && !mb_strpos(mb_strtolower($message), '[i=')) {
            return $message;
        }
        $itextbody = '<i class="%c">%t</i>';
        $stack = [];
        $curr_pos = 1;
        while ($curr_pos && ($curr_pos < mb_strlen($message))) {
            $curr_pos = mb_strpos($message, '[', $curr_pos);

            // If not found, $curr_pos will be 0, and the loop will end.
            if ($curr_pos) {
                // We found a [. It starts at $curr_pos.
                // check if it's a starting or ending quote tag.
                $possible_start = mb_substr($message, $curr_pos, 2);
                $possible_end_pos = mb_strpos($message, ']', $curr_pos);
                $possible_end = mb_substr($message, $curr_pos, $possible_end_pos - $curr_pos + 1);
                if (0 === strcasecmp('[i', $possible_start)) {
                    // We have a starting italic tag.
                    // Push its position on to the stack, and then keep going to the right.
                    $stack[] = $curr_pos;
                    ++$curr_pos;
                } elseif (0 === strcasecmp('[/i]', $possible_end)) {
                    // We have an ending quote tag.
                    // Check if we've already found a matching starting tag.
                    if (count($stack) > 0) {
                        // There exists a starting tag.
                        // We need to do 2 replacements now.
                        $start_index = array_pop($stack);

                        // everything before the [quote=xxx] tag.
                        $before_start_tag = mb_substr($message, 0, $start_index);

                        // find the end of the start tag
                        $start_tag_end = mb_strpos($message, ']', $start_index);
                        $start_tag_len = $start_tag_end - $start_index + 1;

                        if ($start_tag_len > 5) {
                            $class = mb_substr($message, $start_index + 4, $start_tag_len - 5);
                        } else {
                            $class = 'dz-italictext';
                        }

                        // everything after the [quote=xxx] tag, but before the [/quote] tag.
                        $between_tags = mb_substr($message, $start_index + $start_tag_len, $curr_pos - ($start_index + $start_tag_len));
                        // everything after the [/quote] tag.
                        $after_end_tag = mb_substr($message, $curr_pos + 4);

                        $itext = str_replace('%c', $class, $itextbody);
                        $itext = str_replace('%t', $between_tags, $itext);

                        $message = $before_start_tag . $itext . $after_end_tag;

                        // Now.. we've screwed up the indices by changing the length of the string.
                        // So, if there's anything in the stack, we want to resume searching just after it.
                        // otherwise, we go back to the start.
                        if (count($stack) > 0) {
                            $curr_pos = array_pop($stack);
                            $stack[] = $curr_pos;
                            ++$curr_pos;
                        } else {
                            $curr_pos = 1;
                        }
                    } else {
                        // No matching start tag found. Increment pos, keep going.
                        ++$curr_pos;
                    }
                } else {
                    // No starting tag or ending tag.. Increment pos, keep looping.,
                    ++$curr_pos;
                }
            }
        } // while

        return $message;
    }

    /**
     * Note: This function assumes the first character of $message is a space, which is added by
     * transform().
     */
    private function encode_url($message)
    {
        // First things first: If there aren't any "[i=" or "[i] strings in the message, we don't
        // need to process it at all.
        if (!mb_strpos(mb_strtolower($message), '[url]') && !mb_strpos(mb_strtolower($message), '[url=')) {
            return $message;
        }
        $urlbody = '<a href="%u" class="dz_urltext">%t</a>';
        $stack = [];
        $curr_pos = 1;
        while ($curr_pos && ($curr_pos < mb_strlen($message))) {
            $curr_pos = mb_strpos($message, '[', $curr_pos);

            // If not found, $curr_pos will be 0, and the loop will end.
            if ($curr_pos) {
                // We found a [. It starts at $curr_pos.
                // check if it's a starting or ending quote tag.
                $possible_start = mb_substr($message, $curr_pos, 4);
                $possible_end_pos = mb_strpos($message, ']', $curr_pos);
                $possible_end = mb_substr($message, $curr_pos, $possible_end_pos - $curr_pos + 1);
                if (0 === strcasecmp('[url', $possible_start)) {
                    // We have a starting italic tag.
                    // Push its position on to the stack, and then keep going to the right.
                    $stack[] = $curr_pos;
                    ++$curr_pos;
                } elseif (0 === strcasecmp('[/url]', $possible_end)) {
                    // We have an ending quote tag.
                    // Check if we've already found a matching starting tag.
                    if (count($stack) > 0) {
                        // There exists a starting tag.
                        // We need to do 2 replacements now.
                        $start_index = array_pop($stack);

                        // everything before the [quote=xxx] tag.
                        $before_start_tag = mb_substr($message, 0, $start_index);

                        // find the end of the start tag
                        $start_tag_end = mb_strpos($message, ']', $start_index);
                        $start_tag_len = $start_tag_end - $start_index + 1;
                        // everything after the [quote=xxx] tag, but before the [/quote] tag.
                        $between_tags = mb_substr($message, $start_index + $start_tag_len, $curr_pos - ($start_index + $start_tag_len));

                        if ($start_tag_len > 5) {
                            $url = mb_substr($message, $start_index + 4, $start_tag_len - 6);
                        } else {
                            $url = $between_tags;
                        }

                        // everything after the [/quote] tag.
                        $after_end_tag = mb_substr($message, $curr_pos + 6);

                        $itext = str_replace('%u', $url, $urlbody);
                        $itext = str_replace('%t', $between_tags, $itext);

                        $message = $before_start_tag . $itext . $after_end_tag;

                        // Now.. we've screwed up the indices by changing the length of the string.
                        // So, if there's anything in the stack, we want to resume searching just after it.
                        // otherwise, we go back to the start.
                        if (count($stack) > 0) {
                            $curr_pos = array_pop($stack);
                            $stack[] = $curr_pos;
                            ++$curr_pos;
                        } else {
                            $curr_pos = 1;
                        }
                    } else {
                        // No matching start tag found. Increment pos, keep going.
                        ++$curr_pos;
                    }
                } else {
                    // No starting tag or ending tag.. Increment pos, keep looping.,
                    ++$curr_pos;
                }
            }
        } // while

        return $message;
    }

    /**
     * Nathan Codding - Jan. 12, 2001.
     * modified again in 2013 when inserted into Dizkus
     * Performs [quote][/quote] encoding on the given string, and returns the results.
     * Any unmatched "[quote]" or "[/quote]" token will just be left alone.
     * This works fine with both having more than one quote in a message, and with nested quotes.
     * Since that is not a regular language, this is actually a PDA and uses a stack. Great fun.
     *
     * Note: This function assumes the first character of $message is a space, which is added by
     * transform().
     */
    private function encode_quote($message)
    {
        // First things first: If there aren't any "[quote=" or "[quote] strings in the message, we don't
        // need to process it at all.
        if (!mb_strpos(mb_strtolower($message), '[quote=') && !mb_strpos(mb_strtolower($message), '[quote]')) {
            return $message;
        }

        // @todo we can use bootstrap blackquote template and for nested we can use blackquote-reverse class
        // http://getbootstrap.com/css/#type-blockquotes
        $quotebody = '
<div class="dz-quote">
    <i class="fa fa-quote-left fa-4x text-more-muted"></i>
    <div class="inner">
        <div class="dz-quoteheader">%u</div>
        <blockquote class="dz-quotetext">%t</blockquote>
    </div>
    <i class="fa fa-quote-right fa-4x text-more-muted"></i>
</div>';

        $stack = [];
        $curr_pos = 1;
        while ($curr_pos && ($curr_pos < mb_strlen($message))) {
            $curr_pos = mb_strpos($message, '[', $curr_pos);

            // If not found, $curr_pos will be 0, and the loop will end.
            if ($curr_pos) {
                // We found a [. It starts at $curr_pos.
                // check if it's a starting or ending quote tag.
                $possible_start = mb_substr($message, $curr_pos, 6);
                $possible_end_pos = mb_strpos($message, ']', $curr_pos);
                $possible_end = mb_substr($message, $curr_pos, $possible_end_pos - $curr_pos + 1);
                if (0 === strcasecmp('[quote', $possible_start)) {
                    // We have a starting quote tag.
                    // Push its position on to the stack, and then keep going to the right.
                    $stack[] = $curr_pos;
                    ++$curr_pos;
                } elseif (0 === strcasecmp('[/quote]', $possible_end)) {
                    // We have an ending quote tag.
                    // Check if we've already found a matching starting tag.
                    if (count($stack) > 0) {
                        // There exists a starting tag.
                        // We need to do 2 replacements now.
                        $start_index = array_pop($stack);

                        // everything before the [quote=xxx] tag.
                        $before_start_tag = mb_substr($message, 0, $start_index);

                        // find the end of the start tag
                        $start_tag_end = mb_strpos($message, ']', $start_index);
                        $start_tag_len = $start_tag_end - $start_index + 1;
                        if ($start_tag_len > 7) {
                            $username = mb_substr($message, $start_index + 7, $start_tag_len - 8);
                        } else {
                            $username = 'Quote'; //$this->__('Quote');
                        }

                        // everything after the [quote=xxx] tag, but before the [/quote] tag.
                        $between_tags = mb_substr($message, $start_index + $start_tag_len, $curr_pos - ($start_index + $start_tag_len));
                        // everything after the [/quote] tag.
                        $after_end_tag = mb_substr($message, $curr_pos + 8);

                        $quotetext = str_replace('%u', $username, $quotebody);
                        $quotetext = str_replace('%t', $between_tags, $quotetext);

                        $message = $before_start_tag . $quotetext . $after_end_tag;

                        // Now.. we've screwed up the indices by changing the length of the string.
                        // So, if there's anything in the stack, we want to resume searching just after it.
                        // otherwise, we go back to the start.
                        if (count($stack) > 0) {
                            $curr_pos = array_pop($stack);
                            $stack[] = $curr_pos;
                            ++$curr_pos;
                        } else {
                            $curr_pos = 1;
                        }
                    } else {
                        // No matching start tag found. Increment pos, keep going.
                        ++$curr_pos;
                    }
                } else {
                    // No starting tag or ending tag.. Increment pos, keep looping.,
                    ++$curr_pos;
                }
            }
        } // while

        return $message;
    }

    /**
     * Nathan Codding - Jan. 12, 2001.
     * Frank Schummertz - Sept. 2004ff
     * modified again in 2013 when inserted into Dizkus
     * Performs [code][/code] transformation on the given string, and returns the results.
     * Any unmatched "[code]" or "[/code]" token will just be left alone.
     * This works fine with both having more than one code block in a message, and with nested code blocks.
     * Since that is not a regular language, this is actually a PDA and uses a stack. Great fun.
     *
     * Note: This function assumes the first character of $message is a space, which is added by
     * transform().
     */
    private function encode_code($message)
    {
        $count = preg_match_all("#(\\[code=*(.*?)\\])(.*?)(\\[\\/code\\])#si", $message, $code);
        // with $message="[code=php,start=25]php code();[/code]" the array $code now contains
        // [0] [code=php,start=25]php code();[/code]
        // [1] [code=php,start=25]
        // [2] php,start=25
        // [3] php code();
        // [4] [/code]

        if ($count > 0 && is_array($code)) {
            $codebodyblock = '<!--code--><div class="dz-code"><div class="dz-codeheader">%h</div><div class="dz-codetext">%c</div></div><!--/code-->';
            $codebodyinline = '<!--code--><code>%c</code><!--/code-->';

            for ($i = 0; $i < $count; $i++) {
                // the code in between incl. code tags
                $str_to_match = '/' . preg_quote($code[0][$i], '/') . '/';

                $after_replace = trim($code[3][$i]);
                $containsLineEndings = !mb_strstr($after_replace, PHP_EOL) ? false : true;

                if ($containsLineEndings) {
                    $after_replace = '<pre class="pre-scrollable">' . $after_replace . '</pre>';
                    // replace %h with 'Code'
                    $codetext = str_replace('%h', $this->__('Code'), $codebodyblock);
                    // replace %c with code
                    $codetext = str_replace('%c', $after_replace, $codetext);
                    // replace %e with urlencoded code (prepared for javascript)
                    $codetext = str_replace('%e', urlencode(nl2br($after_replace)), $codetext);
                } else {
                    // replace %c with code
                    $codetext = str_replace('%c', $after_replace, $codebodyinline);
                    // replace %e with urlencoded code (prepared for javascript)
                    $codetext = str_replace('%e', urlencode(nl2br($after_replace)), $codetext);
                }

                $message = preg_replace($str_to_match, $codetext, $message);
            }
        }

        return $message;
    }
}
