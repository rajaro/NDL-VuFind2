<?php

/**
 * Additional functionality for Finna and Primo records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library 2015-2019.
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
 * @package  RecordDrivers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */

namespace Finna\RecordDriver\Feature;

use Finna\Db\Row\User;

/**
 * Additional functionality for Finna and Primo records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
trait FinnaRecordTrait
{
    /**
     * Preferred language for display strings
     *
     * @var string
     */
    protected $preferredLanguage = null;

    /**
     * Search settings
     *
     * @var array
     */
    protected $datasourceSettings = null;

    /**
     * Get inappropriate comments for this record reported by the given user.
     *
     * @param object $userId Reporter ID
     *
     * @return array
     */
    public function getInappropriateComments($userId)
    {
        $table = $this->getDbTable('CommentsInappropriate');
        return $table->getForRecord(
            $userId,
            $this->getUniqueID()
        );
    }

    /**
     * Get OpenURL parameters for a book.
     *
     * @return array
     */
    protected function getBookOpenUrlParams()
    {
        $params = parent::getBookOpenUrlParams();
        if ($mmsId = $this->tryMethod('getAlmaMmsId')) {
            $params['rft.mms_id'] = $mmsId;
        }
        $params['rft.au'] = $this->getOpenUrlAuthor();

        return $params;
    }

    /**
     * Get OpenURL parameters for a book section.
     *
     * @return array
     */
    protected function getBookSectionOpenUrlParams()
    {
        $params = $this->getBookOpenUrlParams();
        $params['rft.volume'] = $this->getContainerVolume();
        $params['rft.issue'] = $this->getContainerIssue();
        $params['rft.spage'] = $this->getContainerStartPage();
        unset($params['rft.title']);
        $params['rft.btitle'] = $this->getContainerTitle();
        $params['rft.atitle'] = $this->getTitle();
        if ($mmsId = $this->tryMethod('getAlmaMmsId')) {
            $params['rft.mms_id'] = $mmsId;
        }
        $params['rft.au'] = $this->getOpenUrlAuthor();

        return $params;
    }

    /**
     * Get OpenURL parameters for a journal.
     *
     * @return array
     */
    protected function getJournalOpenUrlParams()
    {
        $params = parent::getJournalOpenUrlParams();
        if ($objectId = $this->tryMethod('getSfxObjectId')) {
            $params['rft.object_id'] = $objectId;
        }
        if ($mmsId = $this->tryMethod('getAlmaMmsId')) {
            $params['rft.mms_id'] = $mmsId;
        }
        $params['rft.au'] = $this->getOpenUrlAuthor();

        return $params;
    }

    /**
     * Get OpenURL parameters for an article.
     *
     * @return array
     */
    protected function getArticleOpenUrlParams()
    {
        $params = parent::getArticleOpenUrlParams();
        if ($doiFull = $this->tryMethod('getCleanDOI')) {
            $doi = preg_replace('/^doi:|^info:doi\//', '', $doiFull);
            $doi = 'info:doi/' . $doi;

            $params['rft_id'] = (array)($params['rft_id'] ?? []);
            $params['rft_id'][] = $doi;
        }
        if ($mmsId = $this->tryMethod('getAlmaMmsId')) {
            $params['rft.mms_id'] = $mmsId;
        }
        $params['rft.au'] = $this->getOpenUrlAuthor();

        return $params;
    }

    /**
     * Get OpenURL parameters for an unknown format.
     *
     * @param string $format Name of format
     *
     * @return array
     */
    protected function getUnknownFormatOpenUrlParams($format = 'UnknownFormat')
    {
        $params = $this->getDefaultOpenUrlParams();
        $resolver = strtolower($this->mainConfig->OpenURL->resolver ?? '');
        // Alma does not support rft_val_fmt 'info:ofi/fmt:kev:mtx:dc', so
        // use the 'info:ofi/fmt:kev:mtx:book' format instead
        if ('alma' === $resolver) {
            $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
            // Don't set genre. It seems to cause Alma to ignore date and author.
            // $params['rft.genre'] = 'unknown';
            $params['rft.au'] = $this->getOpenUrlAuthor();
        } else {
            $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';
            $params['rft.creator'] = $this->getOpenUrlAuthor();
            $params['rft.format'] = $format;
            $langs = $this->getLanguages();
            if (count($langs) > 0) {
                $params['rft.language'] = $langs[0];
            }
        }
        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }
        if ($mmsId = $this->tryMethod('getAlmaMmsId')) {
            $params['rft.mms_id'] = $mmsId;
        }

        return $params;
    }

    /**
     * Get an author for OpenURL
     *
     * @return string
     */
    protected function getOpenUrlAuthor()
    {
        $authors = $this->tryMethod('getNonPresenterAuthors');
        if (!empty($authors[0]['name'])) {
            return $authors[0]['name'];
        }
        $authors = $this->tryMethod('getPresenters');
        if (!empty($authors['presenters'][0]['name'])) {
            return $authors['presenters'][0]['name'];
        }
        if ($author = $this->getPrimaryAuthor()) {
            return trim($author, ' .');
        }
        if ($authors = $this->getSecondaryAuthors()) {
            return trim($authors[0], ' .');
        }

        return '';
    }

    /**
     * Get saved time associated with this record in a user list.
     *
     * @param int $list_id List id
     * @param int $user_id List owner id
     *
     * @return timestamp
     */
    public function getListSavedDate($list_id, $user_id)
    {
        $db = $this->getDbTable('UserResource');
        $data = $db->getSavedData(
            $this->getUniqueId(),
            $this->getSourceIdentifier(),
            $list_id,
            $user_id
        );
        foreach ($data as $current) {
            return $current->saved;
        }
        return null;
    }

    /**
     * Set preferred language for display strings.
     *
     * @param string $language Language
     *
     * @return void
     */
    public function setPreferredLanguage($language)
    {
        $this->preferredLanguage = $language;
    }

    /**
     * Get user id from db
     *
     * @param int $user_id user user_id
     *
     * @return User|boolean
     */
    public function getUserById($user_id)
    {
        return $this->getDbTable('User')->getById($user_id);
    }

    /**
     * Allow record image to be downloaded?
     *
     * @param array $image Image to check
     *
     * @return bool
     */
    public function allowRecordImageDownload(array $image = []): bool
    {
        // Check rights from index if they would not allow the download
        $indexRights = $this->tryMethod('getUsageRights', [], []);
        if (empty($indexRights) || in_array('usage_F', $indexRights)) {
            return false;
        }
        if (empty($image)) {
            return true;
        }
        if (!empty($this->mainConfig->FileDownload->excludeRights)) {
            $restrictions
                = $this->mainConfig->FileDownload->excludeRights->toArray();
            $copyright = mb_strtoupper($image['rights']['copyright'] ?? '', 'UTF-8');
            if (in_array($copyright, $restrictions)) {
                return false;
            }
        }
        if (
            !empty($image['pdf'])
            && !empty($this->mainConfig->Content->pdfCoverImageDownload)
        ) {
            return !empty(
                array_intersect(
                    explode(',', $this->mainConfig->Content->pdfCoverImageDownload),
                    $this->getFormats()
                )
            );
        }
        return true;
    }

    /**
     * Is authority functionality enabled?
     *
     * @param string $type Authority type
     *
     * @return bool
     */
    public function isAuthorityEnabled($type = '*')
    {
        return !empty($this->getAuthoritySource($type));
    }

    /**
     * Whether the record has related records declared in metadata.
     * (used by RecordDriverRelated related module).
     *
     * @return bool
     */
    public function hasRelatedRecords()
    {
        return false;
    }

    /**
     * Format authority id by prefixing the given id with authority record source.
     *
     * @param string $id   Authority id
     * @param string $type Authority type (e.g. author)
     *
     * @return null|string
     */
    public function getAuthorityId($id, $type = '*')
    {
        if (!$id) {
            return $id;
        }
        if (preg_match('/^https?:/', $id)) {
            // Never prefix http(s) url's
            return $id;
        }

        if (!$this->datasourceSettings || !is_callable([$this, 'getDatasource'])) {
            return $id;
        }

        $recordSource = $this->getDataSource();
        if (!($authSrc = $this->getAuthoritySource($type))) {
            return null;
        }

        $idRegex
            = $this->datasourceSettings[$recordSource]['authority_id_regex'][$type]
            ?? $this->datasourceSettings[$recordSource]['authority_id_regex']['*']
            ?? null;

        if ($idRegex && !preg_match($idRegex, $id)) {
            return null;
        }
        return "$authSrc.$id";
    }

    /**
     * Attach datasource settings to the driver.
     *
     * @param array $settings Settings
     *
     * @return void
     */
    public function attachDatasourceSettings($settings)
    {
        $this->datasourceSettings = $settings;
    }

    /**
     * Get authority record source.
     *
     * @param string $type Authority type
     *
     * @return string|null
     */
    protected function getAuthoritySource($type = '*')
    {
        if (!is_callable([$this, 'getDatasource'])) {
            return null;
        }
        $recordSource = $this->getDataSource();
        return $this->datasourceSettings[$recordSource]['authority'][$type]
            ?? $this->datasourceSettings[$recordSource]['authority']['*']
            ?? null;
    }

    /**
     * Whether to show record labels for this record.
     *
     * @return boolean
     */
    public function getRecordLabelsEnabled()
    {
        $labelsConfig = $this->mainConfig->RecordLabels->showLabels ?? null;
        if (!$labelsConfig) {
            return false;
        }
        $backend = $this->getSourceIdentifier();
        return $labelsConfig[$backend]
            ?? $labelsConfig['*']
            ?? false;
    }
}
