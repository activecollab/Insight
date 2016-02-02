<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare (strict_types = 1);

namespace ActiveCollab\Insight\AccountInsight;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\Insight\AccountInsight
 */
class AccountInsight implements AccountInsightInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var int
     */
    private $account_id;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface     $log
     * @param int                 $account_id
     */
    public function __construct(ConnectionInterface &$connection, LoggerInterface &$log, int $account_id)
    {
        $this->connection = $connection;
        $this->log = $log;
        $this->account_id = $account_id;
    }
}
