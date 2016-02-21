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
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\None;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\FreePlan;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountPlanChangesTest extends InsightTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Account #1 does not exist
     */
    public function testErrorOnNonExistingAccount()
    {
        $this->insight->accounts->changePlan(1, new PlanM(), new Monthly());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Can't change to the current plan
     */
    public function testCantChangeToCurrentPlan()
    {
        $this->insight->accounts->addPaid(1, new PlanM(), new Yearly());
        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Canceled accounts can't change plans
     */
    public function testCantChangePlanForCanceledAccount()
    {
        $this->insight->accounts->addPaid(1, new PlanM(), new Yearly());
        $this->insight->accounts->cancel(1);

        $this->insight->accounts->changePlan(1, new PlanL(), new Monthly());
    }

    /**
     * Test if trial converts to paid.
     */
    public function testTrialConvertsToPaid()
    {
        $this->insight->accounts->addTrial(1);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEmpty($row['converted_to_paid_at']);
        $this->assertEmpty($row['converted_to_free_at']);

        $this->insight->accounts->changePlan(1, new PlanM(), new Monthly());

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertInstanceOf(DateTimeValue::class, $row['converted_to_paid_at']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:is'), $row['converted_to_paid_at']->format('Y-m-d H:i:is'));
        $this->assertEmpty($row['converted_to_free_at']);
    }

    /**
     * Test if conversion date can be specified.
     */
    public function testConversionDateCanBeSpecified()
    {
        $in_two_weeks = new DateTimeValue('+14 days');

        $this->insight->accounts->addTrial(1);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEmpty($row['converted_to_paid_at']);

        $this->insight->accounts->changePlan(1, new PlanM(), new Monthly(), $in_two_weeks);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertInstanceOf(DateTimeValue::class, $row['converted_to_paid_at']);
        $this->assertEquals($in_two_weeks->format('Y-m-d H:i:is'), $row['converted_to_paid_at']->format('Y-m-d H:i:is'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account can't convert before it is created
     */
    public function testConversionCantBeBeforeCreation()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-02-20'));
        $this->insight->accounts->changePlan(1, new PlanM(), new Monthly(), new DateTimeValue('2016-02-18'));
    }

    /**
     * Test if trial converts to free.
     */
    public function testTrialConvertsToFree()
    {
        $this->insight->accounts->addTrial(1);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEmpty($row['converted_to_paid_at']);
        $this->assertEmpty($row['converted_to_free_at']);

        $this->insight->accounts->changePlan(1, new FreePlan(), new None());

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEmpty($row['converted_to_paid_at']);
        $this->assertInstanceOf(DateTimeValue::class, $row['converted_to_free_at']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:is'), $row['converted_to_free_at']->format('Y-m-d H:i:is'));
    }
}
