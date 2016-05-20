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

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;

/**
 * @package ActiveCollab\Insight\AccountInsight\Metric
 */
class MrrTimeline extends Metric implements MrrTimelineInterface
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
    public function get()
    {
        $result = $this->connection->execute("SELECT `mrr_value`, `started_at`, `ended_at` FROM {$this->mrr_spans_table} WHERE `account_id` = ? ORDER BY `started_at` DESC", $this->account->getAccountId());

        if (empty($result)) {
            $result = [];
        } else {
            return $result->setValueCaster(new ValueCaster(['mrr_value' => ValueCasterInterface::CAST_FLOAT]))->toArray();
        }

        return $result;
    }
}
