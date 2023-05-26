<?php

/**
 * Console service for processing the statistics queue.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command\Util;

use Finna\Statistics\Driver\Database as DatabaseDriver;
use Finna\Statistics\Driver\Redis as RedisDriver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console service for processing the statistics queue.
 *
 * This will also do what ProcessRecordStatsLog does.
 *
 * @category VuFind
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class ProcessStatsQueue extends AbstractUtilCommand
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/process_stats_queue';

    /**
     * Statistics database driver
     *
     * @var DatabaseDriver;
     */
    protected $dbHandler;

    /**
     * Redis client
     *
     * @var \Credis_Client
     */
    protected $redisClient;

    /**
     * Redis key prefix
     *
     * @var string
     */
    protected $keyPrefix;

    /**
     * Constructor
     *
     * @param DatabaseDriver $dbHandler   Statistics database driver
     * @param \Credis_Client $redisClient Redis client
     * @param string         $keyPrefix   Redis key prefix
     */
    public function __construct(
        DatabaseDriver $dbHandler,
        \Credis_Client $redisClient,
        string $keyPrefix
    ) {
        $this->dbHandler = $dbHandler;
        $this->redisClient = $redisClient;
        $this->keyPrefix = $keyPrefix;

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription(
            'Process statistics queues from Redis into statistics tables'
        );
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->processSessions();
        $this->processPageViews();
        $this->processRecordViews();

        return 0;
    }

    /**
     * Process sessions
     *
     * @return void
     */
    protected function processSessions(): void
    {
        $this->processQueue(
            'session',
            RedisDriver::KEY_SESSION,
            [$this->dbHandler, 'addNewSessionEntry']
        );
    }

    /**
     * Process page views
     *
     * @return void
     */
    protected function processPageViews(): void
    {
        $this->processQueue(
            'page view',
            RedisDriver::KEY_PAGE_VIEW,
            [$this->dbHandler, 'addPageViewEntry']
        );
    }

    /**
     * Process a queue
     *
     * @param string   $queueName Queue display name
     * @param string   $queueKey  Redis key
     * @param callable $addFunc   Callback for adding an entry
     *
     * @return void
     */
    protected function processQueue(
        string $queueName,
        string $queueKey,
        callable $addFunc
    ): void {
        $this->msg("Processing $queueName queue");
        $count = 0;
        do {
            $logEntryStr = $this->redisClient->rPop(
                $this->keyPrefix . $queueKey
            );
            if ($logEntryStr) {
                try {
                    $logEntry = json_decode($logEntryStr, true);
                    unset($logEntry['v']);
                    call_user_func($addFunc, $logEntry);
                    ++$count;
                    $msg = "$count $queueName entries processed";
                    if ($count % 1000 == 0) {
                        $this->msg($msg);
                    } else {
                        $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                    }
                } catch (\Exception $e) {
                    // Try to push the result back into the queue:
                    $this->redisClient->rPush(
                        $this->keyPrefix . $queueKey,
                        $logEntryStr
                    );
                    throw $e;
                }
            }
        } while ($logEntryStr);

        $this->msg("Completed with $count $queueName entries processed");
    }

    /**
     * Process record views
     *
     * Writes normal stats entries as well as extended log entries that spread
     * multiple tables.
     *
     * @return void
     */
    protected function processRecordViews(): void
    {
        $callback = function (array $logEntry) {
            $this->dbHandler->addRecordViewEntry(
                [
                    'institution' => $logEntry['institution'],
                    'view' => $logEntry['view'],
                    'crawler' => $logEntry['crawler'],
                    'date' => $logEntry['date'],
                    'backend' => $logEntry['backend'],
                    'source' => $logEntry['source'],
                ]
            );

            // Add detailed log entry:
            $this->dbHandler->addDetailedRecordViewEntry($logEntry);
        };

        $this->processQueue('record view', RedisDriver::KEY_RECORD_VIEW, $callback);
    }
}
