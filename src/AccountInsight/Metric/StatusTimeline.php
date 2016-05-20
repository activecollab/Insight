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

/**
 * @package ActiveCollab\Insight\AccountInsight\Metric
 */
class StatusTimeline extends Metric implements StatusTimelineInterface
{
    /**
     * @var string
     */
    private $status_spans_table;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->status_spans_table = $this->insight->getTableName('account_status_spans');
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $result = $this->connection->execute("SELECT `status`, `started_at`, `ended_at` FROM {$this->status_spans_table} WHERE `account_id` = ? ORDER BY `started_at` DESC", $this->account->getAccountId());

        if (empty($result)) {
            $result = [];
        } else {
            $result = $result->toArray();
        }

        return $result;
    }
}
