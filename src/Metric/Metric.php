<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Metric;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\Insight\Insight;
use ActiveCollab\Insight\InsightInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
abstract class Metric implements MetricInterface
{
    /**
     * @var Insight|InsightInterface
     */
    protected $insight;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @param InsightInterface    $insight
     * @param ConnectionInterface $connection
     * @param LoggerInterface     $log
     */
    public function __construct(InsightInterface &$insight, ConnectionInterface &$connection, LoggerInterface &$log)
    {
        $this->insight = $insight;
        $this->connection = $connection;
        $this->log = $log;

        $this->configure();
    }

    /**
     * Do post-construction confirguration.
     */
    protected function configure()
    {
    }
}
