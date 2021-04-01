<?php
/**
 * SavePaljoVolumeCode AJAX handler
 *
 * PHP version 7
 *
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
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Finna\Db\Table\PaljoVolumeCode;
use Finna\Service\PaljoService;
use Finna\Db\Row\User;

/**
 * SavePaljoVolumeCode AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SavePaljoVolumeCode extends \VuFind\AjaxHandler\AbstractBase
{
  /**
   * PaljoVolumeCode database table
   *
   * @var PaljoVolumeCode
   */
    protected $paljoVolumeCode;

    /**
     * Paljo service
     *
     * @var PaljoService
     */
    protected $paljo;

    /**
     * Logged in user (or false)
     *
     * @var User|bool
     */
    protected $user;

    /**
     * Constructor
     *
     * @param PaljoVolumeCode $volumeCode paljo volume code database table
     * @param PaljoService    $paljo      paljo service
     * @param User|bool       $user       Logged in user (or false)
     */
    public function __construct(
        PaljoVolumeCode $paljoVolumeCode,
        PaljoService $paljo,
        User $user
    ) {
        $this->paljoVolumeCode = $paljoVolumeCode;
        $this->paljo = $paljo;
        $this->user = $user;
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
        if ($this->user === false) {
            return $this->formatResponse(
                $this->translate('You must be logged in first'),
                self::STATUS_HTTP_NEED_AUTH
            );
        }
        if ($this->user->paljo_id === null) {
          return $this->formatResponse(
                $this->translate('paljo_id_required')
          );
        }
        $paljoId = $this->user->paljo_id;
        $volumeCode = $params->fromPost('volumeCode', '');

        $response = $this->paljo->getDiscountForUser($paljoId, $volumeCode);
        if (!empty($response)) {
            $this->paljoVolumeCode->saveVolumeCode(
              $paljoId, $volumeCode, $response['organisation'], $response['discount']);
        }
        return $this->formatResponse($response);
    }
}
