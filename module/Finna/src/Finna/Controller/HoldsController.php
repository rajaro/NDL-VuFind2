<?php
/**
 * Holds Controller
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2021.
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\Controller;

/**
 * Controller for the user holds area.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class HoldsController extends \VuFind\Controller\HoldsController
{
    use FinnaUnsupportedFunctionViewTrait;
    use FinnaPersonalInformationSupportTrait;

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function listAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        if ($view = $this->createViewIfUnsupported('getMyHolds')) {
            return $view;
        }
        $view = parent::listAction();
        if (isset($view->recordList)) {
            $sort = $this->params()->fromQuery('sort', 'available');
            if ($sort !== 'available') {
                $view->recordList = $this->sortHolds($view->recordList, $sort);
            } else {
                $view->recordList = $this->orderAvailability($view->recordList);
            }
        }
        $sortList = [
            'available' => [
                'desc' =>  'hold_available',
                'url' => '?sort=available',
                'selected' => $sort === 'available'
            ],
            'expire_asc' => [
                'desc' =>  'hold_sort_expire_asc',
                'url' => '?sort=expire_asc',
                'selected' => $sort === 'expire_asc'
            ],
            'expire_desc' => [
                'desc' =>  'hold_sort_expire_desc',
                'url' => '?sort=expire_desc',
                'selected' => $sort === 'expire_desc'
            ]
        ];
        $view->sortList = $sortList;
        $view->blocks = $this->getAccountBlocks($patron);
        return $view;
    }

    /**
     * Sort holds list
     *
     * @param array  $recordList array of holds
     * @param string $sort       sort order
     *
     * @return array
     */
    protected function sortHolds($recordList, $sort)
    {
        [$field, $order] = explode('_', $sort);
        $date = $this->serviceLocator->get(\VuFind\Date\Converter::class);
        $sortFunc = function ($a, $b) use ($field, $order, $date) {
            $aDetail = $a->getExtraDetail('ils_details')[$field] ?? '';
            $bDetail = $b->getExtraDetail('ils_details')[$field] ?? '';
            $aDate = $aDetail
                ? $date->convertFromDisplayDate('U', $aDetail)
                : 0;
            $bDate = $bDetail
                ? $date->convertFromDisplayDate('U', $bDetail)
                : 0;
            return $order === 'asc' ? $aDate - $bDate : $bDate - $aDate;
        };
        usort($recordList, $sortFunc);
        return $recordList;
    }
}
