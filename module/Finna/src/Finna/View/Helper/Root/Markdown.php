<?php
/**
 * Markdown view helper
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\View\Helper\Root;

use HTMLPurifier;

/**
 * Markdown view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Markdown extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Allowed elements & attributes
     *
     * @var array
     */
    protected $allowedElements = [
        'details',
        'summary',
        'a[href]',
        'ol',
        'ul',
        'li',
        'blockquote',
        'pre',
        'code',
        'img[src|alt]',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'b',
        'i',
        'br',
    ];

    /**
     * Return HTML.
     *
     * @param string $markdown Markdown
     *
     * @return string
     */
    public function toHtml($markdown)
    {
        $parser = new \Parsedown();
        $parser->setBreaksEnabled(true);
        $text = $parser->text($markdown);
        if (strpos($text, '<') !== -1) {
            $purifierConfig = \HTMLPurifier_Config::createDefault();
            $allowed = implode(',', $this->allowedElements);
            $purifierConfig->set('HTML.Allowed', $allowed);
            $def = $purifierConfig->getHTMLDefinition(true);

            // Details & summary elements not supported by default, add them:
            $def->addElement(
                'details',
                'Block',
                'Flow',
                'Common',
                ['open' => new \HTMLPurifier_AttrDef_HTML_Bool(true)]
            );
            $def->addElement('summary', 'Inline', 'Inline', 'Common');
            $purifier = new HTMLPurifier($purifierConfig);
            return $purifier->purify($text);
        }
        return $text;
    }
}
