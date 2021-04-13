<?php
/**
 * GetPaljoDiscount AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;

/**
 * GetPaljoDiscount AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetPaljoDiscount extends \VuFind\AjaxHandler\AbstractBase
{
    protected $paljoService;
    /**
     * Constructor
     *
     * @param PaljoService $paljoService PALJO service
     */
    public function __construct(
        \Finna\Service\PaljoService $paljoService
    ) {
        $this->paljoService = $paljoService;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $email = $params->fromQuery('email', '');
        $code = $params->fromQuery('code', '');
        $orgId = $params->fromQuery('orgId', '');
        $imageId = $params->fromQuery('imageId', '');
        $priceType = $params->fromQuery('priceType', '');
        $imageInfo = $this->paljoService->getImagePrice($imageId, $orgId);
        $discount = $this->paljoService->getDiscountForUser($email, $code);
        $response = [];
        if ($discount) {
            if ($discount['organisation'] === $orgId) {
                $discountAmount = $discount['discount'];
                $currentPrice = $imageInfo['price'][$priceType];
                $newPrice = (1 - $discountAmount / 100) * $currentPrice;
                $response['price'] = $newPrice;
            }
        }
        return $this->formatResponse($response);
    }
}
