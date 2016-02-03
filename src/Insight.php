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

namespace ActiveCollab\Insight;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\Insight\AccountInsight\AccountInsight;
use ActiveCollab\Insight\Metric\MetricInterface;
use Doctrine\Common\Inflector\Inflector;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @property \ActiveCollab\Insight\Metric\MrrInterface $mrr
 * @property \ActiveCollab\Insight\Metric\EventsInterface $events
 * @package ActiveCollab\Insight
 */
class Insight implements InsightInterface
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
     * @param ConnectionInterface $connection
     * @param LoggerInterface     $log
     */
    public function __construct(ConnectionInterface &$connection, LoggerInterface &$log)
    {
        $this->connection = $connection;
        $this->log = $log;
    }

    /**
     * @var AccountInsight[]
     */
    private $account_insights = [];

    /**
     * {@inheritdoc}
     */
    public function account(int $account_id)
    {
        if (empty($this->account_insights[$account_id])) {
            $this->account_insights[$account_id] = new AccountInsight($this->connection, $this->log, $account_id);
        }

        return $this->account_insights[$account_id];
    }

    /**
     * @var MetricInterface[]
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
            $class_name = '\\ActiveCollab\\Insight\\Metric\\' . Inflector::classify($metric);

            if (class_exists($class_name)) {
                $this->metrics[$metric] = new $class_name($this->connection, $this->log);
            } else {
                throw new LogicException("Metric '$metric' is not currently supported");
            }
        }

        return $this->metrics[$metric];
    }

    /**
     * @var string
     */
    private $table_prefix = 'insight_';

    /**
     * Return table prefix.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->table_prefix;
    }

    /**
     * Set table prefix.
     *
     * @param  string $value
     * @return $this
     */
    public function &setTablePrefix(string $value)
    {
        $this->table_prefix = trim($value);

        return $this;
    }
}
