<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test\Fixtures\BillingPeriod;

use ActiveCollab\Insight\BillingPeriod\BillingPeriodInterface;

/**
 * @package ActiveCollab\Insight\Test\Fixtures\BillingPeriod
 */
class Invalid implements BillingPeriodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBillingPeriod(): string
    {
        return 'this is not valid billing period';
    }
}
