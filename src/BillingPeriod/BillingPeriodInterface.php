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

namespace ActiveCollab\Insight\BillingPeriod;

/**
 * @package ActiveCollab\Insight\BillingPeriod
 */
interface BillingPeriodInterface
{
    const NONE = 'none';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';

    /**
     * Return billing period.
     *
     * @return string
     */
    public function getBillingPeriod(): string;
}
