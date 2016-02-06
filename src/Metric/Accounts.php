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
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;
use ActiveCollab\Insight\Plan\PlanInterface;
use DateTimeInterface;
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
    public function addPaid(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period, DateTimeInterface $timestamp = null): AccountInsightInterface
    {
        $mrr = $plan->getMrrValue($billing_period);

        if ($mrr <= 0) {
            throw new RuntimeException('Paid accounts should have MRR value');
        }

        $this->connection->insert($this->accounts_table, [
            'id' => $account_id,
            'status' => self::PAID,
            'created_at' => $timestamp ?? new DateTimeValue(),
            'mrr_value' => $mrr,
        ]);

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function addTrial(int $account_id, DateTimeInterface $timestamp = null): AccountInsightInterface
    {
        $this->connection->insert($this->accounts_table, [
            'id' => $account_id,
            'status' => self::TRIAL,
            'created_at' => $timestamp ?? new DateTimeValue(),
            'mrr_value' => 0,
        ]);

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function addFree(int $account_id, DateTimeInterface $timestamp = null): AccountInsightInterface
    {
        $this->connection->insert($this->accounts_table, [
            'id' => $account_id,
            'status' => self::FREE,
            'created_at' => $timestamp ?? new DateTimeValue(),
            'mrr_value' => 0,
        ]);

        return $this->insight->account($account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function isCanceled(int $account_id): bool
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `canceled_at` FROM `$this->accounts_table` WHERE `id` = ?", $account_id)) {
            return !empty($row['canceled_at']);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(int $account_id, DateTimeInterface $timestamp = null): AccountInsightInterface
    {
        if ($row = $this->connection->executeFirstRow("SELECT `id`, `created_at`, `canceled_at` FROM `$this->accounts_table` WHERE `id` = ?", $account_id)) {
            if (!empty($row['canceled_at'])) {
                throw new LogicException("Account #{$account_id} is already canceled");
            }

            $timestamp = $timestamp ?? new DateTimeValue();

            /** @var DateTimeValue $created_at */
            $created_at = $row['created_at'];

            if ($timestamp->getTimestamp() < $created_at->getTimestamp()) {
                throw new LogicException("Account cancelation timestamp can't be before creation timestamp");
            }

            $this->connection->execute("UPDATE `$this->accounts_table` SET `canceled_at` = ? WHERE `id` = ?", $timestamp, $account_id);
        } else {
            throw new InvalidArgumentException("Account #{$account_id} does not exist");
        }

        return $this->insight->account($account_id);
    }
}
