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
use InvalidArgumentException;
use LogicException;

/**
 * @package ActiveCollab\Insight\Metric
 */
class Churn extends Metric implements ChurnInterface
{
    /**
     * {@inheritdoc}
     */
    public function &snapshot(DateValueInterface $reference_day, int $number_of_paid_accounts = null, float $mrr = null): ChurnInterface
    {
        /** @var DateValue $month_start */
        $month_start = clone $reference_day;

        if ($month_start->day != 1) {
            $month_start->startOfMonth();
        }

        $monthly_churn_table = $this->insight->getTableName('monthly_churn');

        if ($this->connection->count($monthly_churn_table, ['`day` = ?', $month_start])) {
            throw new InvalidArgumentException('Snapshot for ' . $month_start->format('Y-m') . ' already exists');
        } else {
            $this->connection->insert($monthly_churn_table, [
                'day' => $month_start,
                'accounts' => $number_of_paid_accounts,
                'mrr' => $mrr,
            ]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function &churn(int $account_id, DateValueInterface $churned_on, $reason = AccountsInterface::USER_CANCELED): ChurnInterface
    {
        if ($snapshot_id = $this->getSnapshotIdFor($churned_on)) {
            if ($account = $this->connection->executeFirstRow("SELECT * FROM `{$this->insight->getTableName('accounts')}` WHERE `id` = ?", $account_id)) {
                if ($account['status'] != AccountsInterface::PAID) {
                    throw new LogicException('Only paid accounts can churn');
                }
            } else {
                throw new InvalidArgumentException("Account #{$account_id} does not exist");
            }
        } else {
            throw new InvalidArgumentException("Churn snapshot for {$churned_on->format('Y-m')} was not found");
        }

        return $this;
    }

    /**
     * Return snapshot ID for the given reference day. If snapshot is not found for the given day, 0 is returned.
     *
     * @param  DateValueInterface $reference_day
     * @return int
     */
    private function getSnapshotIdFor(DateValueInterface $reference_day): int
    {
        /** @var DateValue $month_start */
        $month_start = clone $reference_day;

        if ($month_start->day != 1) {
            $month_start->startOfMonth();
        }

        if ($snapshot_id = $this->connection->executeFirstCell("SELECT `id` FROM `{$this->insight->getTableName('monthly_churn')}` WHERE `day` = ?", $month_start)) {
            return $snapshot_id;
        }

        return 0;
    }
}
