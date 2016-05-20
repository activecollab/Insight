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

use ActiveCollab\DateValue\DateValueInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
interface ChurnInterface extends MetricInterface
{
    /**
     * Create a reference snapshot,.
     *
     * @param  DateValueInterface $reference_day
     * @param  int|null           $number_of_paid_accounts
     * @param  float|null         $mrr
     * @return ChurnInterface
     */
    public function &snapshot(DateValueInterface $reference_day, int $number_of_paid_accounts = null, float $mrr = null): ChurnInterface;

    /**
     * Churn an account.
     *
     * @param  int                $account_id
     * @param  DateValueInterface $churned_on
     * @param  string             $reason
     * @return ChurnInterface
     */
    public function &churn(int $account_id, DateValueInterface $churned_on, $reason = AccountsInterface::USER_CANCELED): ChurnInterface;
}
