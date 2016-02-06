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

use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;
use ActiveCollab\Insight\Plan\PlanInterface;
use DateTimeInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
interface AccountsInterface
{
    const TRIAL = 'trial';
    const FREE = 'free';
    const PAID = 'paid';

    const STATUSES = [self::TRIAL, self::FREE, self::PAID];

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
     * @param  int                     $account_id
     * @param  PlanInterface           $plan
     * @param  BillingPeriodInterface  $billing_period
     * @param  DateTimeInterface|null  $timestamp
     * @return AccountInsightInterface
     */
    public function addPaid(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period, DateTimeInterface $timestamp = null): AccountInsightInterface;

    /**
     * @param  int                     $account_id
     * @param  DateTimeInterface|null  $timestamp
     * @return AccountInsightInterface
     */
    public function addTrial(int $account_id, DateTimeInterface $timestamp = null): AccountInsightInterface;

    /**
     * @param  int                     $account_id
     * @param  DateTimeInterface|null  $timestamp
     * @return AccountInsightInterface
     */
    public function addFree(int $account_id, DateTimeInterface $timestamp = null): AccountInsightInterface;

//    /**
//     * @param int                    $account_id
//     * @param PlanInterface          $plan
//     * @param BillingPeriodInterface $billing_period
//     */
//    public function upgradeToPlan(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period);
//
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
     * Return true if account exists and it is canceled.
     *
     * @param  int  $account_id
     * @return bool
     */
    public function isCanceled(int $account_id): bool;

    /**
     * Mark an account as canceled.
     *
     * @param  int                     $account_id
     * @param  string                  $reason
     * @param  DateTimeInterface|null  $timestamp
     * @return AccountInsightInterface
     */
    public function cancel(int $account_id, string $reason = self::USER_CANCELED, DateTimeInterface $timestamp = null): AccountInsightInterface;
}
