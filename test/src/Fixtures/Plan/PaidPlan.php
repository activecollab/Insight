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

use ActiveCollab\Insight\Plan\PlanInterface;

/**
 * @package ActiveCollab\Insight\Test\Fixtures\Plan
 */
abstract class PaidPlan implements PlanInterface
{
    /**
     * {@inheritdoc}
     */
    public function isFree(): bool
    {
        return false;
    }
}
