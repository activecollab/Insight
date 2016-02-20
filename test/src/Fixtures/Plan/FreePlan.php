<?php

namespace ActiveCollab\Insight\Test\Fixtures\Plan;


use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;
use ActiveCollab\Insight\Plan\PlanInterface;
use RuntimeException;

/**
 * @package ActiveCollab\Insight\Test\Fixtures\Plan
 */
class FreePlan implements PlanInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMrrValue(BillingPeriodInterface $billing_period): float
    {
        if ($billing_period->getBillingPeriod() == BillingPeriodInterface::NONE) {
            return 0;
        } else {

        }

        switch ($billing_period->getBillingPeriod()) {
            case BillingPeriodInterface::MONTHLY:
                return 99;
            case BillingPeriodInterface::YEARLY:
                return 999;
            default:
                throw new RuntimeException("Value '{$billing_period->getBillingPeriod()} is not a valid billing period for free plans");
        }
    }
}