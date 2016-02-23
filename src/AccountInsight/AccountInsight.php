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
use ActiveCollab\Insight\InsightInterface;
use Doctrine\Common\Inflector\Inflector;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @property \ActiveCollab\Insight\AccountInsight\Metric\EventsInterface $events
 * @property \ActiveCollab\Insight\AccountInsight\Metric\StatusTimelineInterface $status_timeline
 * @package ActiveCollab\Insight\AccountInsight
 */
class AccountInsight implements AccountInsightInterface
{
    /**
     * @var InsightInterface
     */
    protected $insight;

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
     * @param InsightInterface    $insight
     * @param ConnectionInterface $connection
     * @param LoggerInterface     $log
     * @param int                 $account_id
     */
    public function __construct(InsightInterface &$insight, ConnectionInterface &$connection, LoggerInterface &$log, int $account_id)
    {
        $this->insight = $insight;
        $this->connection = $connection;
        $this->log = $log;
        $this->account_id = $account_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountId(): int
    {
        return $this->account_id;
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
                $this->metrics[$metric] = new $class_name($this, $this->insight, $this->connection, $this->log);
            } else {
                throw new LogicException("Metric '$metric' is not currently supported");
            }
        }

        return $this->metrics[$metric];
    }
}
