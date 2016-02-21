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
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\None;
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
     * Test active accounts for the current day (don't try to reconstruct a value in the history).
     */
    public function testCountActiveToday()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addFree(2, new FreePlan(), new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addPaid(3, new PlanL(), new Yearly(), new DateTimeValue('2016-02-14')); // Cancel!
        $this->insight->accounts->addPaid(4, new PlanM(), new Monthly(), new DateTimeValue('2016-02-15'));

        $this->insight->accounts->cancel(3, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-14'));

        $this->current_timestamp = new DateTimeValue('2016-02-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->assertEquals(3, $this->insight->accounts->countActive());
    }

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

        $this->assertEquals(0, $this->insight->accounts->countActive(new DateValue('2016-02-11')));
        $this->assertEquals(1, $this->insight->accounts->countActive(new DateValue('2016-02-12')));
        $this->assertEquals(2, $this->insight->accounts->countActive(new DateValue('2016-02-13')));
        $this->assertEquals(2, $this->insight->accounts->countActive(new DateValue('2016-02-14')));
        $this->assertEquals(3, $this->insight->accounts->countActive(new DateValue('2016-02-15')));
        $this->assertEquals(3, $this->insight->accounts->countActive(new DateValue('2016-02-16')));
    }

    /**
     * Test trials counter for the current state (today), without going back through history.
     */
    public function testCountTrialsToday()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13')); // Convert to paid on 2016-02-14
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14')); // Convert to free on 2016-02-15
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-02-15')); // Cancel

        $this->insight->accounts->changePlan(2, new PlanM(), new Yearly(), new DateTimeValue('2016-02-14'));
        $this->insight->accounts->changePlan(3, new FreePlan(), new None(), new DateTimeValue('2016-02-15'));
        $this->insight->accounts->cancel(4, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->current_timestamp = new DateTimeValue('2016-02-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->assertEquals(1, $this->insight->accounts->countTrials());
    }

    /**
     * Test trials counter on a given day.
     */
    public function testCountTrialsOnDay()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13')); // Convert to paid on 2016-02-14
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14')); // Convert to free on 2016-02-15
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-02-15')); // Cancel

        $this->insight->accounts->changePlan(2, new PlanM(), new Yearly(), new DateTimeValue('2016-02-14'));
        $this->insight->accounts->changePlan(3, new FreePlan(), new None(), new DateTimeValue('2016-02-15'));
        $this->insight->accounts->cancel(4, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->assertEquals(0, $this->insight->accounts->countTrials(new DateValue('2016-02-11')));
        $this->assertEquals(1, $this->insight->accounts->countTrials(new DateValue('2016-02-12')));
        $this->assertEquals(2, $this->insight->accounts->countTrials(new DateValue('2016-02-13')));
        $this->assertEquals(3, $this->insight->accounts->countTrials(new DateValue('2016-02-14'))); // One new trial, total of 3 trials, one will convert today
        $this->assertEquals(3, $this->insight->accounts->countTrials(new DateValue('2016-02-15'))); // One new trial, total of 3 trials, one will convert today
        $this->assertEquals(2, $this->insight->accounts->countTrials(new DateValue('2016-02-16'))); // No new trials, one will be canceled
        $this->assertEquals(1, $this->insight->accounts->countTrials(new DateValue('2016-02-17')));
    }

    /**
     * Test free account counter for the current state (today), without going back through history.
     */
    public function testCountFreeToday()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13')); // Convert to paid on 2016-02-14
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14')); // Convert to free on 2016-02-15
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-02-15')); // Cancel
        $this->insight->accounts->addFree(5, new FreePlan(), new DateTimeValue('2016-02-16')); // Converts to paid plan on 2016-02-16
        $this->insight->accounts->changePlan(5, new PlanL(), new Yearly(), new DateTimeValue('2016-02-16'));

        $this->insight->accounts->changePlan(2, new PlanM(), new Yearly(), new DateTimeValue('2016-02-14'));
        $this->insight->accounts->changePlan(3, new FreePlan(), new None(), new DateTimeValue('2016-02-15'));
        $this->insight->accounts->cancel(4, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->current_timestamp = new DateTimeValue('2016-02-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->assertEquals(2, $this->insight->accounts->countFree());
    }

    /**
     * Test count free accounts on a given date.
     */
    public function testCountFree()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14'));
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-02-15'));

        $this->insight->accounts->changePlan(2, new FreePlan(), new None(), new DateTimeValue('2016-02-13'));
        $this->insight->accounts->changePlan(3, new FreePlan(), new None(), new DateTimeValue('2016-02-14'));
        $this->insight->accounts->changePlan(4, new FreePlan(), new None(), new DateTimeValue('2016-02-15'));

        $this->insight->accounts->changePlan(3, new PlanL(), new Yearly(), new DateTimeValue('2016-02-15'));
        $this->insight->accounts->cancel(4, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->assertEquals(0, $this->insight->accounts->countFree(new DateValue('2016-02-11')));
        $this->assertEquals(0, $this->insight->accounts->countFree(new DateValue('2016-02-12')));
        $this->assertEquals(1, $this->insight->accounts->countFree(new DateValue('2016-02-13')));
        $this->assertEquals(2, $this->insight->accounts->countFree(new DateValue('2016-02-14')));
        $this->assertEquals(3, $this->insight->accounts->countFree(new DateValue('2016-02-15')));
        $this->assertEquals(2, $this->insight->accounts->countFree(new DateValue('2016-02-16')));
        $this->assertEquals(1, $this->insight->accounts->countFree(new DateValue('2016-02-17')));
    }

    /**
     * Test paid account counter for the current state (today), without going back through history.
     */
    public function testCountPaidToday()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13')); // Convert to free on 2016-02-14
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14')); // Convert to paid on 2016-02-15
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-02-15')); // Cancel
        $this->insight->accounts->addPaid(5, new PlanL(), new Yearly(), new DateTimeValue('2016-02-16')); // Converts to free plan on 2016-02-16
        $this->insight->accounts->changePlan(5, new FreePlan(), new None(), new DateTimeValue('2016-02-16'));

        $this->insight->accounts->changePlan(2, new PlanM(), new Yearly(), new DateTimeValue('2016-02-14'));
        $this->insight->accounts->changePlan(3, new FreePlan(), new None(), new DateTimeValue('2016-02-15'));
        $this->insight->accounts->cancel(4, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->current_timestamp = new DateTimeValue('2016-02-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->assertEquals(2, $this->insight->accounts->countPaid());
    }

    /**
     * Test count paid accounts on a given date.
     */
    public function testCountPaid()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14'));
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-02-15'));

        $this->insight->accounts->changePlan(2, new PlanM(), new Yearly(), new DateTimeValue('2016-02-13'));
        $this->insight->accounts->changePlan(3, new PlanM(), new Monthly(), new DateTimeValue('2016-02-14'));
        $this->insight->accounts->changePlan(4, new PlanM(), new Yearly(), new DateTimeValue('2016-02-15'));

        $this->insight->accounts->changePlan(3, new FreePlan(), new None(), new DateTimeValue('2016-02-15'));
        $this->insight->accounts->cancel(4, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->assertEquals(0, $this->insight->accounts->countPaid(new DateValue('2016-02-11')));
        $this->assertEquals(0, $this->insight->accounts->countPaid(new DateValue('2016-02-12')));
        $this->assertEquals(1, $this->insight->accounts->countPaid(new DateValue('2016-02-13')));
        $this->assertEquals(2, $this->insight->accounts->countPaid(new DateValue('2016-02-14')));
        $this->assertEquals(3, $this->insight->accounts->countPaid(new DateValue('2016-02-15')));
        $this->assertEquals(2, $this->insight->accounts->countPaid(new DateValue('2016-02-16')));
        $this->assertEquals(1, $this->insight->accounts->countPaid(new DateValue('2016-02-17')));
    }

    /**
     * Test accounts retired today, without going back through history.
     */
    public function testCountRetiredToday()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14'));

        $this->insight->accounts->retire(1, new DateTimeValue('2016-02-16'));
        $this->insight->accounts->retire(3, new DateTimeValue('2016-02-16'));

        $this->current_timestamp = new DateTimeValue('2016-02-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->assertEquals(2, $this->insight->accounts->countRetired());
    }

    /**
     * Test count retired accounts on a given day.
     */
    public function testCountRetiredOnDay()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14'));

        $this->insight->accounts->retire(1, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->retire(3, new DateTimeValue('2016-02-14'));

        $this->assertEquals(0, $this->insight->accounts->countRetired(new DateValue('2016-02-12')));
        $this->assertEquals(1, $this->insight->accounts->countRetired(new DateValue('2016-02-13')));
        $this->assertEquals(2, $this->insight->accounts->countRetired(new DateValue('2016-02-14')));
        $this->assertEquals(2, $this->insight->accounts->countRetired(new DateValue('2016-02-15')));
    }

    /**
     * Test accounts canceled today, without going back through history.
     */
    public function testCountCanceledToday()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14'));

        $this->insight->accounts->cancel(1, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));
        $this->insight->accounts->cancel(3, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-16'));

        $this->current_timestamp = new DateTimeValue('2016-02-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->assertEquals(2, $this->insight->accounts->countCanceled());
    }

    /**
     * Test count canceled accounts on a given day.
     */
    public function testCountCanceledOnDay()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-12'));
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-02-14'));

        $this->insight->accounts->cancel(1, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-13'));
        $this->insight->accounts->cancel(3, AccountsInterface::USER_CANCELED, new DateTimeValue('2016-02-14'));

        $this->assertEquals(0, $this->insight->accounts->countCanceled(new DateValue('2016-02-12')));
        $this->assertEquals(1, $this->insight->accounts->countCanceled(new DateValue('2016-02-13')));
        $this->assertEquals(2, $this->insight->accounts->countCanceled(new DateValue('2016-02-14')));
        $this->assertEquals(2, $this->insight->accounts->countCanceled(new DateValue('2016-02-15')));
    }
}
