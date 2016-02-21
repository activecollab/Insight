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
use ActiveCollab\DateValue\DateValue;
use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\FreePlan;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountCountersTest extends InsightTestCase
{
    /**
     * Test count active accounts on a given day.
     */
    public function testCountActiveOnDay()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addFree(2, new FreePlan(), new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addPaid(3, new PlanL(), new Yearly(), new DateTimeValue('2016-02-14')); // Cancel!
        $this->insight->accounts->addPaid(4, new PlanM(), new Monthly(), new DateTimeValue('2016-02-15'));

        $this->insight->accounts->cancel(3, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-14'));

        $this->assertEquals(1, $this->insight->accounts->countActive(new DateValue('2016-02-12')));
        $this->assertEquals(2, $this->insight->accounts->countActive(new DateValue('2016-02-13')));
        $this->assertEquals(2, $this->insight->accounts->countActive(new DateValue('2016-02-14')));
        $this->assertEquals(3, $this->insight->accounts->countActive(new DateValue('2016-02-15')));

        $this->current_timestamp = DateTimeValue::setTestNow(new DateTimeValue('2016-02-16'));

        $this->assertEquals(3, $this->insight->accounts->countActive());
    }
}
