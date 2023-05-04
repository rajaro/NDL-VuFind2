<?php

/**
 * Class Database
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
 * Copyright (C) The National Library of Finland 2015-2022.
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
 * @package  Form
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

declare(strict_types=1);

namespace Finna\Form\Handler;

use Finna\Db\Table\FinnaFeedback;
use Laminas\Log\LoggerAwareInterface;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Form\Handler\HandlerInterface;
use VuFind\Log\LoggerAwareTrait;

/**
 * Class Database
 *
 * @category VuFind
 * @package  Form
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Database implements HandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $viewRenderer;

    /**
     * Feedback table
     *
     * @var FinnaFeedback
     */
    protected $table;

    /**
     * Site base url
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Constructor
     *
     * @param \VuFind\Db\Table\Feedback $feedbackTable Feedback db table
     * @param string                    $baseUrl       Site base url
     * @param RendererInterface         $viewRenderer  View renderer
     */
    public function __construct(
        FinnaFeedback $feedbackTable,
        string $baseUrl,
        RendererInterface $viewRenderer
    ) {
        $this->table = $feedbackTable;
        $this->baseUrl = $baseUrl;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Get data from submitted form and process them.
     *
     * @param \VuFind\Form\Form                     $form   Submitted form
     * @param \Laminas\Mvc\Controller\Plugin\Params $params Request params
     * @param ?\VuFind\Db\Row\User                  $user   Authenticated user
     *
     * @return bool
     */
    public function handle(
        \VuFind\Form\Form $form,
        \Laminas\Mvc\Controller\Plugin\Params $params,
        ?\VuFind\Db\Row\User $user = null
    ): bool {
        $fields = $form->mapRequestParamsToFieldValues($params->fromPost());
        $save = array_column($fields, 'value', 'name');

        $userId = $user ? $user->id : null;

        $subject = $form->getEmailSubject($params->fromPost());
        $save['emailSubject'] = $subject;
        $messageJson = json_encode($save);

        $emailMessage = $this->viewRenderer->partial(
            'Email/form.phtml',
            compact('fields')
        );

        $message = $subject . PHP_EOL . '-----' . PHP_EOL . PHP_EOL . $emailMessage;

        try {
            $this->table->saveFeedback(
                $this->baseUrl,
                $form->getFormId(),
                $userId,
                $message,
                $messageJson
            );
        } catch (\Exception $e) {
            $this->logError('Could not save feedback data: ' . $e->getMessage());
            return false;
        }

        return true;
    }
}
