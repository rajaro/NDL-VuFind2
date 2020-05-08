<?php
/**
 * View helper for feed tabs.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2019.
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
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\View\Helper\Root;

/**
 * View helper for LinkedEvents tabs.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class LinkedEventsTabs extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Returns HTML for the widget.
     *
     * @param array $params e.g. ['tabs' => [
     *                      ['title' => 'Music', 'params' => [
     *                      'keyword' => 'music', 'page_size' => 6]],
     *                      ['title' => 'Sports', 'params' => [
     *                      'keyword' => 'sports', 'page_size' => 6]]
     *                      ], 'searchTools' => 'show'].
     *
     * @return string
     */
    public function __invoke($params)
    {
        $tabs = $params['tabs'] ?? [];
        $active = $params['active'] ?? $tabs[0]['title'];
        $moreLink = $params['link'] ?? '';
        $searchTools = $params['searchTools'] ?? 'show';

        return $this->getView()->render(
            'Helpers/linkedeventstabs.phtml',
            [
                'tabs' => $tabs,
                'active' => $active,
                'moreLink' => $moreLink,
                'searchTools' => $searchTools,
                'id' => md5(json_encode($tabs)),
            ]
        );
    }
}
