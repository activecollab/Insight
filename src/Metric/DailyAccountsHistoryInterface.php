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

/**
 * @package ActiveCollab\Insight\Metric
 */
interface DailyAccountsHistoryInterface extends MetricInterface
{
    /**
     * Return internal record ID for the given day.
     *
     * @param  DateValue|null $day
     * @return int
     */
    public function getDayId(DateValue $day = null): int;

    /**
     * Record that new account is added.
     *
     * @param int            $account_id
     * @param bool           $is_trial
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    public function newAccount(int $account_id, bool $is_trial = false, float $mrr_value = 0, DateValue $day = null);

    /**
     * Record that new trial account is added.
     *
     * This method is just a shorter way to call newAccount() when trial account is created.
     *
     * @param int            $account_id
     * @param DateValue|null $day
     */
    public function newTrial(int $account_id, DateValue $day = null);

    /**
     * Record that new trial was created from a free account.
     *
     * @param int            $account_id
     * @param DateValue|null $day
     */
    public function newFreeToTrial(int $account_id, DateValue $day = null);

    /**
     * Record that trial converted to free account.
     *
     * @param int            $account_id
     * @param DateValue|null $day
     */
    public function newTrialToFree(int $account_id, DateValue $day = null);

    /**
     * Record that trial converted to paid account.
     *
     * @param int            $account_id
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    public function newTrialToPaid(int $account_id, float $mrr_value = 0, DateValue $day = null);

    /**
     * @param int            $account_id
     * @param DateValue|null $day
     */
    public function newCancelation(int $account_id, DateValue $day = null);

    /**
     * @param int            $account_id
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    public function newUpgrade(int $account_id, float $mrr_value = 0, DateValue $day = null);

    /**
     * @param int            $account_id
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    public function newDowngrade(int $account_id, float $mrr_value = 0, DateValue $day = null);

    /**
     * @param int            $account_id
     * @param float          $mrr_value
     * @param DateValue|null $day
     */
    public function newPeriodChange(int $account_id, float $mrr_value = 0, DateValue $day = null);
}
