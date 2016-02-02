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
use ActiveCollab\Insight\AccountInsight\Metric\MetricInterface;
use Doctrine\Common\Inflector\Inflector;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @property \ActiveCollab\Insight\AccountInsight\Metric\EventsInterface $events
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

    /**
     * @var MetricInterface
     */
    private $metrics = [];

    /**
     * Get a supported metric as a property.
     *
     * @param  string          $metric
     * @return MetricInterface
     */
    public function __get($metric)
    {
        if (empty($this->metrics[$metric])) {
            $class_name = '\\ActiveCollab\\Insight\\AccountInsight\\Metric\\' . Inflector::classify($metric);

            if (class_exists($class_name)) {
                $this->metrics[$metric] = new $class_name();
            } else {
                throw new LogicException("Metric '$metric' is not currently supported");
            }
        }

        return $this->metrics[$metric];
    }
}
