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
    public function getDayId(DateValue $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        if ($day_id = $this->connection->executeFirstCell("SELECT `id` FROM `$this->daily_accounts_history_table` WHERE `day` = ?", $day)) {
            return $day_id;
        } else {
            $this->connection->insert($this->daily_accounts_history_table, ['day' => $day]);

            return $this->connection->lastInsertId();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newAccount(int $account_id, bool $is_trial = false, float $mrr_value = 0, DateValue $day = null)
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

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `new_accounts` = `new_accounts` + 1 WHERE `id` = ?", $this->getDayId($day));

        if (!$is_trial && $mrr_value > 0) {
            $this->recordMrrOnDay($account_id, $mrr_value, $day);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newTrial(int $account_id, DateValue $day = null)
    {
        $this->newAccount($account_id, false, 0, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newFreeToTrial(int $account_id, DateValue $day = null)
    {
        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_trial` = `conversions_to_trial` + 1 WHERE `id` = ?", $this->getDayId($day));
    }

    /**
     * Record that free account converted to paid account.
     *
     * @param int            $account_id
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    public function newFreeToPaid(int $account_id, float $mrr_value, DateValue $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_paid` = `conversions_to_paid` + 1 WHERE `id` = ?", $this->getDayId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newTrialToFree(int $account_id, DateValue $day = null)
    {
        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_free` = `conversions_to_free` + 1 WHERE `id` = ?", $this->getDayId($day));
    }

    /**
     * {@inheritdoc}
     */
    public function newTrialToPaid(int $account_id, float $mrr_value, DateValue $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `conversions_to_paid` = `conversions_to_paid` + 1 WHERE `id` = ?", $this->getDayId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newCancelation(int $account_id, float $mrr_lost = 0, DateValue $day = null)
    {
        if ($mrr_lost < 0) {
            throw new LogicException('MRR lost value should be 0 or more');
        }

        if ($mrr_lost > 0) {
            $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `paid_cancelations` = `paid_cancelations` + 1 WHERE `id` = ?", $this->getDayId($day));
            $this->recordMrrOnDay($account_id, 0);
        } else {
            $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `free_cancelations` = `free_cancelations` + 1 WHERE `id` = ?", $this->getDayId($day));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newUpgrade(int $account_id, float $mrr_value, DateValue $day = null)
    {
        if ($mrr_value <= 0) {
            throw new LogicException('Paid accounts should have MRR value');
        }

        $this->connection->execute("UPDATE `$this->daily_accounts_history_table` SET `upgrades` = `upgrades` + 1 WHERE `id` = ?", $this->getDayId($day));
        $this->recordMrrOnDay($account_id, $mrr_value, $day);
    }

    /**
     * {@inheritdoc}
     */
    public function newDowngrade(int $account_id, float $mrr_value, DateValue $day = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function newPeriodChange(int $account_id, float $mrr_value = 0, DateValue $day = null)
    {
    }

    /**
     * Record MRR value for a given account on a given day.
     *
     * @param int            $account_id
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    private function recordMrrOnDay(int $account_id, float $mrr_value = 0, DateValue $day = null)
    {
        $day = $day ?? new DateValue();

        if ($this->connection->count($this->daily_account_mrr_table, ['account_id = ? AND day = ?', $account_id, $day])) {
            $this->connection->update($this->daily_account_mrr_table, [
                'mrr_value' => $mrr_value
            ], ['account_id = ? AND day = ?', $account_id, $day]);
        } else {
            $this->connection->insert($this->daily_account_mrr_table, ['account_id' => $account_id, 'day' => $day, 'mrr_value' => $mrr_value]);
        }
    }
}
