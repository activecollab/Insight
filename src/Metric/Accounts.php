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

use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateValueInterface;
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;
use ActiveCollab\Insight\Plan\PlanInterface;
use Carbon\Carbon;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * @package ActiveCollab\Insight\Metric
 */
class Accounts extends Metric implements AccountsInterface
{
    /**
     * @var string
     */
    private $accounts_table;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->accounts_table = $this->insight->getTableName('accounts');
    }

    /**
     * Return true if given account exists.
     *
     * @param  int  $account_id
     * @return bool
     */
    public function exists(int $account_id): bool
    {
        return (boolean) $this->connection->count($this->accounts_table, ['`id` = ?', $account_id]);
    }

    /**
     * {@inheritdoc}
     */
    public function addPaid(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period, DateTimeValueInterface $timestamp = null, DateTimeValueInterface $conversion_timestamp = null): AccountInsightInterface
    {
        if ($plan->isFree()) {
            throw new LogicException('Paid accounts can use only paid plans');
        }

        $mrr = $plan->getMrrValue($billing_period);

        if ($mrr <= 0) {
            throw new RuntimeException('Paid accounts should have MRR value');
        }

        $created_at = $timestamp ?? new DateTimeValue();
        $converted_at = $conversion_timestamp ?? $created_at;

        if ($created_at->getTimestamp() > $converted_at->getTimestamp()) {
            throw new LogicException("Account can't convert before it is created");
        }

        $this->connection->insert($this->accounts_table, [
            'id' => $account_id,
            'status' => self::PAID,
            'plan' => get_class($plan),
            'billing_period' => get_class($billing_period),
            'created_at' => $created_at,
            'converted_to_paid_at' => $converted_at,
            'mrr_value' => $mrr,
            'updated_at' => $converted_at,
        ]);

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function addTrial(int $account_id, DateTimeValueInterface $timestamp = null): AccountInsightInterface
    {
        if (empty($timestamp)) {
            $timestamp = new DateTimeValue();
        }

        $this->connection->insert($this->accounts_table, [
            'id' => $account_id,
            'status' => self::TRIAL,
            'created_at' => $timestamp,
            'mrr_value' => 0,
            'had_trial' => true,
            'updated_at' => $timestamp,
        ]);

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function addFree(int $account_id, PlanInterface $plan, DateTimeValueInterface $timestamp = null, DateTimeValueInterface $conversion_timestamp = null): AccountInsightInterface
    {
        if (!$plan->isFree()) {
            throw new LogicException('Free accounts can use only free plans');
        }

        $created_at = $timestamp ?? new DateTimeValue();
        $converted_at = $conversion_timestamp ?? $created_at;

        if ($created_at->getTimestamp() > $converted_at->getTimestamp()) {
            throw new LogicException("Account can't convert before it is created");
        }

        $this->connection->insert($this->accounts_table, [
            'id' => $account_id,
            'status' => self::FREE,
            'plan' => get_class($plan),
            'created_at' => $created_at,
            'converted_to_free_at' => $converted_at,
            'mrr_value' => 0,
            'updated_at' => $converted_at,
        ]);

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function changePlan(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period, DateTimeValueInterface $timestamp = null): AccountInsightInterface
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `plan`, `billing_period`, `created_at`, `converted_to_free_at`, `converted_to_paid_at`, `canceled_at` FROM `{$this->insight->getTableName('accounts')}` WHERE `id` = ?", $account_id)) {
            if ($row['canceled_at']) {
                throw new LogicException("Canceled accounts can't change plans");
            }

            $converted_at = $timestamp ?? new DateTimeValue();

            if ($row['created_at'] instanceof DateTimeValue && $row['created_at']->getTimestamp() > $converted_at->getTimestamp()) {
                throw new LogicException("Account can't convert before it is created");
            }

            if ($row['plan'] == get_class($plan) && $row['billing_period'] == get_class($billing_period)) {
                throw new LogicException("Can't change to the current plan");
            }

            $mrr = $plan->getMrrValue($billing_period);

            if ($mrr < 0) {
                throw new RuntimeException("MRR can't be negative value");
            }

            $field_values = [
                'plan' => get_class($plan),
                'billing_period' => get_class($billing_period),
                'mrr_value' => $mrr,
                'updated_at' => $converted_at,
            ];

            if (empty($mrr)) {
                $field_values['status'] = AccountsInterface::FREE;

                if (empty($row['converted_to_free_at'])) {
                    $field_values['converted_to_free_at'] = $timestamp ?? new DateTimeValue();
                }
            } else {
                $field_values['status'] = AccountsInterface::PAID;

                if (empty($row['converted_to_paid_at'])) {
                    $field_values['converted_to_paid_at'] = $timestamp ?? new DateTimeValue();
                }
            }

            $this->connection->update($this->insight->getTableName('accounts'), $field_values, ['`id` = ?', $account_id]);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function isRetired(int $account_id): bool
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `status`, `retired_at` FROM `$this->accounts_table` WHERE `id` = ?", $account_id)) {
            return $row['status'] === AccountsInterface::RETIRED && !empty($row['retired_at']);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function retire(int $account_id, DateTimeValueInterface $timestamp = null): AccountInsightInterface
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `created_at`, `retired_at`, `canceled_at` FROM `$this->accounts_table` WHERE `id` = ?", $account_id)) {
            if (!empty($row['retired_at'])) {
                throw new LogicException("Account #{$account_id} is already retired");
            }

            if (!empty($row['canceled_at'])) {
                throw new LogicException("Account #{$account_id} is already canceled");
            }

            $timestamp = $timestamp ?? new DateTimeValue();

            /** @var DateTimeValue $created_at */
            $created_at = $row['created_at'];

            if ($timestamp->getTimestamp() < $created_at->getTimestamp()) {
                throw new LogicException("Account retireing timestamp can't be before creation timestamp");
            }

            $this->connection->execute("UPDATE `$this->accounts_table` SET `status` = ?, `retired_at` = ?, `mrr_value` = ?, `updated_at` = ? WHERE `id` = ?", AccountsInterface::RETIRED, $timestamp, 0, $timestamp, $account_id);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function isCanceled(int $account_id): bool
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `status`, `canceled_at` FROM `$this->accounts_table` WHERE `id` = ?", $account_id)) {
            return $row['status'] === AccountsInterface::CANCELED && !empty($row['canceled_at']);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(int $account_id, string $reason = self::USER_CANCELED, DateTimeValueInterface $timestamp = null): AccountInsightInterface
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `created_at`, `canceled_at` FROM `$this->accounts_table` WHERE `id` = ?", $account_id)) {
            if (!empty($row['canceled_at'])) {
                throw new LogicException("Account #{$account_id} is already canceled");
            }

            if (!in_array($reason, self::CANCELATION_REASONS)) {
                throw new InvalidArgumentException("Value '$reason' is not a supported cancelation reason");
            }

            $timestamp = $timestamp ?? new DateTimeValue();

            /** @var DateTimeValue $created_at */
            $created_at = $row['created_at'];

            if ($timestamp->getTimestamp() < $created_at->getTimestamp()) {
                throw new LogicException("Account cancelation timestamp can't be before creation timestamp");
            }

            $this->connection->execute("UPDATE `$this->accounts_table` SET `status` = ?, `canceled_at` = ?, `cancelation_reason` = ?, `mrr_value` = ?, `updated_at` = ? WHERE `id` = ?", AccountsInterface::CANCELED, $timestamp, $reason, 0, $timestamp, $account_id);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }

        return $this->insight->account($account_id);
    }

    /**
     * @param  DateValueInterface|Carbon|null $day
     * @return int
     */
    public function countActive(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return $this->countByStatusOnDay(AccountsInterface::ACTIVE, $day);
    }

    /**
     * @param  DateValueInterface|Carbon|null $day
     * @return int
     */
    public function countTrials(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return $this->countByStatusOnDay(AccountsInterface::TRIAL, $day);
    }

    /**
     * @param  DateValueInterface|Carbon|null $day
     * @return int
     *
     * @todo Support stretches of time, with gaps, changes and switches.
     */
    public function countFree(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return $this->countByStatusOnDay(AccountsInterface::FREE, $day);
    }

    /**
     * @param  DateValueInterface|Carbon|null $day
     * @return int
     *
     * @todo Support stretches of time, with gaps, changes and switches.
     */
    public function countPaid(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return $this->countByStatusOnDay(AccountsInterface::PAID, $day);
    }

    /**
     * @param  DateValueInterface|Carbon|null $day
     * @return int
     */
    public function countRetired(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return $this->countByStatusOnDay(AccountsInterface::RETIRED, $day);
    }

    /**
     * @param  DateValueInterface|Carbon|null $day
     * @return int
     */
    public function countCanceled(DateValueInterface $day = null): int
    {
        if (empty($day)) {
            $day = new DateValue();
        }

        return $this->countByStatusOnDay(AccountsInterface::CANCELED, $day);
    }

    /**
     * Return number of accounts that were had a given status on a given day.
     *
     * @param  string|string[]    $statuses
     * @param  DateValueInterface $day
     * @return int
     */
    private function countByStatusOnDay($statuses, DateValueInterface $day): int
    {
        return $this->connection->executeFirstCell("SELECT COUNT(DISTINCT `account_id`) AS 'row_count' FROM {$this->insight->getTableName('account_status_spans')} WHERE `status` IN ? AND `started_on` <= ? AND (`ended_on` IS NULL OR `ended_on` >= ?)", (array) $statuses, $day, $day);
    }
}
