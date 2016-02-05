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

    const STATUSES = [self::TRIAL, self::FREE, self::PAID];

    /**
     * Add a new paid account to the.
     *
     * @param int                    $account_id
     * @param PlanInterface          $plan
     * @param BillingPeriodInterface $billing_period
     */
    public function addPaid(int $account_id, PlanInterface $plan, BillingPeriodInterface $billing_period);

    /**
     * @param int $account_id
     */
    public function addTrial(int $account_id);

    /**
     * @param int $account_id
     */
    public function addFree(int $account_id);

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
}
