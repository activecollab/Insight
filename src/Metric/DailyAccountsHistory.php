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

namespace ActiveCollab\Insight\Metric;

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateValueInterface;
use Carbon\Carbon;
use LogicException;

/**
 * @package ActiveCollab\Insight\Metric
 */
class DailyAccountsHistory extends Metric implements DailyAccountsHistoryInterface
{
    /**
     * @var string
     */
    private $daily_accounts_history_table;

    /**
     * @var string
     */
    private $daily_account_mrr_table;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->daily_accounts_history_table = $this->insight->getTableName('daily_accounts_history');
        $this->daily_account_mrr_table = $this->insight->getTableName('daily_account_mrr');
    }

    /**
     * {@inheritdoc}
     */
    public function getSnapshotId(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        if ($day_id = $this->connection->executeFirstCell("SELECT `id` FROM `$this->daily_accounts_history_table` WHERE `day` = ?", $day)) {
            return $day_id;
        } else {
            return $this->dailySnapshot($day);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newAccount(int $account_id, bool $is_trial = false, float $mrr_value = 0, DateValueInterface $day = null)
    {
        if ($is_trial && $mrr_value != 0) {
            throw new LogicException('Trial accounts should not have MRR value');
        }

        if ($mrr_value < 0) {
            throw new LogicException("MRR value can't be negative for new accounts");
        }

        if (!is_bool($is_trial)) {
            throw new LogicException('You have an error in your logic');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `new_accounts` = `new_accounts` + 1 WHERE `id` = ?", $this->getSnapshotId($day));

        if (!$is_trial && $mrr_value > 0) {
            $this->recordMrrOnDay($account_id, $mrr_value, $day);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newTrial(int $account_id, DateValueInterface $day = null)
    {
        $this->newAccount($account_id, false, 0, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newFreeToTrial(int $account_id, DateValueInterface $day = null)
    {
        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_trial` = `conversions_to_trial` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
    }

    /**
     * {@inheritdoc}
     */
    public function newFreeToPaid(int $account_id, float $mrr_value, DateValueInterface $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_paid` = `conversions_to_paid` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newTrialToFree(int $account_id, DateValueInterface $day = null)
    {
        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_free` = `conversions_to_free` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
    }

    /**
     * {@inheritdoc}
     */
    public function newTrialToPaid(int $account_id, float $mrr_value, DateValueInterface $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_paid` = `conversions_to_paid` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newCancelation(int $account_id, float $mrr_lost = 0, DateValueInterface $day = null)
    {
        if ($mrr_lost < 0) {
            throw new LogicException('MRR lost value should be 0 or more');
        }

        if ($mrr_lost > 0) {
            $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `paid_cancelations` = `paid_cancelations` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
            $this->recordMrrOnDay($account_id, 0);
        } else {
            $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `free_cancelations` = `free_cancelations` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newUpgrade(int $account_id, float $mrr_value, DateValueInterface $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `upgrades` = `upgrades` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newDowngrade(int $account_id, float $mrr_value, DateValueInterface $day = null)
    {
        if ($mrr_value < 0) {
            throw new LogicException('MRR lost value should be 0 or more');
        }

        $conditions = $this->connection->prepareConditions(['account_id = ? AND day = ?', $account_id, $day ?? new DateValue()]);

        if ($this->connection->count($this->daily_account_mrr_table, $conditions)) {
            $current_mrr_value = (float) $this->connection->executeFirstCell("SELECT `mrr_value` FROM `$this->daily_account_mrr_table` WHERE $conditions");

            if ($current_mrr_value <= $mrr_value) {
                throw new LogicException('MRR value when dowgrading needs to be lower than current account MRR value');
            }
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `downgrades` = `downgrades` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newPeriodChange(int $account_id, float $mrr_value, DateValueInterface $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `period_changes` = `period_changes` + 1 WHERE `id` = ?", $this->getSnapshotId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * Record MRR value for a given account on a given day.
     *
     * @param int                     $account_id
     * @param float                   $mrr_value
     * @param DateValueInterface|null $day
     */
    private function recordMrrOnDay(int $account_id, float $mrr_value = 0, DateValueInterface $day = null)
    {
        $day = $day ?? new DateValue();

        if ($this->connection->count($this->daily_account_mrr_table, ['account_id = ? AND day = ?', $account_id, $day])) {
            $this->connection->update($this->daily_account_mrr_table, [
                'mrr_value' => $mrr_value,
            ], ['account_id = ? AND day = ?', $account_id, $day]);
        } else {
            $this->connection->insert($this->daily_account_mrr_table, ['account_id' => $account_id, 'day' => $day, 'mrr_value' => $mrr_value]);
        }
    }

    /**
     * @param  DateValueInterface|Carbon $day
     * @return int
     */
    public function dailySnapshot(DateValueInterface $day): int
    {
        $this->connection->insert($this->daily_accounts_history_table, [
            'day' => $day,
            'started_with_active' => $this->insight->accounts->countActive($day),
            'started_with_trials' => $this->insight->accounts->countTrials($day),
            'started_with_free' => $this->insight->accounts->countFree($day),
            'started_with_paid' => $this->insight->accounts->countPaid($day),
            'started_with_retired' => $this->insight->accounts->countRetired($day),
            'started_with_canceled' => $this->insight->accounts->countCanceled($day),
        ]);

        return $this->connection->lastInsertId();
    }
}
