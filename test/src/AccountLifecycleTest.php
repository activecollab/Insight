<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test;

use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountLifecycleTest extends InsightTestCase
{
    public function testCountPayingOnDay()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-01-22')); // Monhtly, churns soon
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-01-22')); // Monthly, loyal
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-01-22')); // Yearly, churns, but remains active
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-01-22')); // Never converts

        $this->insight->accounts->changePlan(1, new PlanM(), new Monthly(), new DateTimeValue('2016-02-15'));
    }
}
