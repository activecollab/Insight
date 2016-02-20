<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test\Fixtures\Plan;

use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;
use RuntimeException;

/**
 * @package ActiveCollab\Insight\Test\Fixtures\Plan
 */
class PlanS extends PaidPlan
{
    /**
     * {@inheritdoc}
     */
    public function getMrrValue(BillingPeriodInterface $billing_period): float
    {
        switch ($billing_period->getBillingPeriod()) {
            case BillingPeriodInterface::MONTHLY:
                return 25;
            case BillingPeriodInterface::YEARLY:
                return 250;
            default:
                throw new RuntimeException("Value '{$billing_period->getBillingPeriod()} is not a valid billing period for paid plans");
        }
    }
}
