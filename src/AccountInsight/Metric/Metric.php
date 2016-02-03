<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\AccountInsight\Metric;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\Insight\AccountInsight\Metric
 */
abstract class Metric implements MetricInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface     $log
     */
    public function __construct(ConnectionInterface &$connection, LoggerInterface &$log)
    {
        $this->connection = $connection;
        $this->log = $log;
    }
}
