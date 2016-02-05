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

namespace ActiveCollab\Insight\Plan;

use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;

/**
 * @package ActiveCollab\Insight\Plan
 */
interface PlanInterface
{
    /**
     * Return MRR for the given billing period.
     *
     * @param  BillingPeriodInterface $billing_period
     * @return float
     */
    public function getMrrValue(BillingPeriodInterface $billing_period): float;
}
