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

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateValueInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
class Mrr extends Metric implements MrrInterface
{
    /**
     * @var string
     */
    private $mrr_spans_table;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->mrr_spans_table = $this->insight->getTableName('account_mrr_spans');
    }

    /**
     * {@inheritdoc}
     */
    public function getOnDay(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return ceil($this->connection->executeFirstCell("SELECT SUM(tt.`mrr_value`)
            FROM $this->mrr_spans_table tt
            INNER JOIN (
                SELECT `account_id`, MAX(`started_at`) AS 'max_started_at'
                    FROM {$this->mrr_spans_table}
                    WHERE `started_on` <= ? AND (`ended_on` IS NULL OR `ended_on` >= ?)
                    GROUP BY `account_id`
            ) AS grouped_tt ON tt.`account_id` = grouped_tt.`account_id` AND tt.`started_at` = grouped_tt.max_started_at", $day, $day));
    }
}
