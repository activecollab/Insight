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

namespace ActiveCollab\Insight\AccountInsight\Metric;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\InsightInterface;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\Insight\AccountInsight\Metric
 */
class Events extends Metric implements EventsInterface
{
    /**
     * @var string
     */
    private $table_name;

    /**
     * {@inheritdoc}
     */
    public function __construct(AccountInsightInterface &$account, InsightInterface &$insight, ConnectionInterface &$connection, LoggerInterface &$log)
    {
        parent::__construct($account, $insight, $connection, $log);

        $this->table_name = $this->insight->getTableName('events');
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $what, DateTimeValue $when = null, array $context = [])
    {
        $this->connection->insert($this->table_name, [
            'account_id' => $this->account->getAccountId(),
            'name' => $what,
            'created_at' => $when ?? new DateTimeValue(),
            'context' => json_encode($context),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($page = 1, $per_page = 100)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->connection->count($this->table_name, ['account_id = ?', $this->account->getAccountId()]);
    }
}
