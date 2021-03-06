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

use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\DateValue\DateValueInterface;
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;
use ActiveCollab\Insight\Plan\PlanInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
interface AccountsInterface
{
    const TRIAL = 'trial';
    const FREE = 'free';
    const PAID = 'paid';
    const RETIRED = 'retired';
    const CANCELED = 'canceled';

    const STATUSES = [self::TRIAL, self::FREE, self::PAID, self::RETIRED, self::CANCELED];
    const ACTIVE = [self::TRIAL, self::FREE, self::PAID];
    const NOT_ACTIVE = [self::RETIRED, self::CANCELED];

    const TRIAL_EXPIRED = 'trial_expired';   // Trial expired and user never returned to convert.
    const USER_ABANDONED = 'user_abandoned'; // Payment failed, but user never returned to correct the billing info.
    const USER_CANCELED = 'user_canceled';   // User explicitely requested account cancelation.
    const TERMINATED = 'terminated';         // Staff terminated the account.

    const CANCELATION_REASONS = [self::TRIAL_EXPIRED, self::USER_ABANDONED, self::USER_CANCELED, self::TERMINATED];

    /**
     * Return true if given account exists.
     *
     * @param  int  $account_id
     * @return bool
     */
    public function exists(int $account_id): bool;

    /**
     * Add a new paid account to the.
     *
     * @param  int                         $account_id
     * @param  PlanInterface               $plan
     * @param  BillingPeriodInterface      $billing_period
     * @param  DateTimeValueInterface|null $timestamp
     * @param  DateTimeValueInterface|null $conversion_timestamp
     * @return AccountInsightInterface
     */
    public function addPaid(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period, DateTimeValueInterface $timestamp = null, DateTimeValueInterface $conversion_timestamp = null): AccountInsightInterface;

    /**
     * @param  int                         $account_id
     * @param  DateTimeValueInterface|null $timestamp
     * @return AccountInsightInterface
     */
    public function addTrial(int $account_id, DateTimeValueInterface $timestamp = null): AccountInsightInterface;

    /**
     * @param  int                         $account_id
     * @param  PlanInterface               $plan
     * @param  DateTimeValueInterface|null $timestamp
     * @param  DateTimeValueInterface|null $conversion_timestamp
     * @return AccountInsightInterface
     */
    public function addFree(int $account_id, PlanInterface $plan, DateTimeValueInterface $timestamp = null, DateTimeValueInterface $conversion_timestamp = null): AccountInsightInterface;

    /**
     * Change account's plan.
     *
     * @param  int                         $account_id
     * @param  PlanInterface               $plan
     * @param  BillingPeriodInterface      $billing_period
     * @param  DateTimeValueInterface|null $timestamp
     * @return AccountInsightInterface
     */
    public function changePlan(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period, DateTimeValueInterface $timestamp = null): AccountInsightInterface;

//    /**
//     * @param int                    $account_id
//     * @param PlanInterface          $plan
//     * @param BillingPeriodInterface $billing_period
//     */
//    public function downgradeToPlan(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period);
//
//    /**
//     * @param int $account_id
//     */
//    public function downgradeToFree(int $account_id);

    /**
     * Return true if account exists and it is retired.
     *
     * @param  int  $account_id
     * @return bool
     */
    public function isRetired(int $account_id): bool;

    /**
     * Mark an account as canceled.
     *
     * @param  int                         $account_id
     * @param  DateTimeValueInterface|null $timestamp
     * @return AccountInsightInterface
     */
    public function retire(int $account_id, DateTimeValueInterface $timestamp = null): AccountInsightInterface;

    /**
     * Return true if account exists and it is canceled.
     *
     * @param  int  $account_id
     * @return bool
     */
    public function isCanceled(int $account_id): bool;

    /**
     * Mark an account as canceled.
     *
     * @param  int                         $account_id
     * @param  string                      $reason
     * @param  DateTimeValueInterface|null $timestamp
     * @return AccountInsightInterface
     */
    public function cancel(int $account_id, string $reason = self::USER_CANCELED, DateTimeValueInterface $timestamp = null): AccountInsightInterface;

    /**
     * Return total number of accounts on a day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function countActive(DateValueInterface $day = null): int;

    /**
     * Return total number of trials on a day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function countTrials(DateValueInterface $day = null): int;

    /**
     * Return total number of free accounts on a day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function countFree(DateValueInterface $day = null): int;

    /**
     * Return total number of paid accounts on a day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function countPaid(DateValueInterface $day = null): int;

    /**
     * Return total number of retired accounts on a day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function countRetired(DateValueInterface $day = null): int;

    /**
     * Return total number of canceled accounts on a day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function countCanceled(DateValueInterface $day = null): int;
}
