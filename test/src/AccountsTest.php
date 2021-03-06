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
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Invalid;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\None;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\FreePlan;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanZ;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountsTest extends InsightTestCase
{
    /**
     * Test if table for accounts is created when requested.
     */
    public function testTableIsCreated()
    {
        $this->assertFalse($this->connection->tableExists('insight_accounts'));
        $this->insight->getTableName('accounts');
        $this->assertTrue($this->connection->tableExists('insight_accounts'));
    }

    /**
     * Test if new trial returns account insight instance.
     */
    public function testNewTrialReturnsAccountInsightInstance()
    {
        $this->assertInstanceOf(AccountInsightInterface::class, $this->insight->accounts->addTrial(12345));
    }

    /**
     * Test if new trial adds a valid record.
     */
    public function testNewTrialAccountAddValidRecord()
    {
        $this->assertEquals(0, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));
        $this->insight->accounts->addTrial(12345);
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals(AccountsInterface::TRIAL, $row['status']);
        $this->assertNull($row['plan']);
        $this->assertNull($row['billing_period']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']);
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(0, $row['mrr_value']);
        $this->assertTrue($row['had_trial']);
    }

    /**
     * Test if new free returns account insight instance.
     */
    public function testNewFreeReturnsAccountInsightInstance()
    {
        $this->assertInstanceOf(AccountInsightInterface::class, $this->insight->accounts->addFree(12345, new FreePlan()));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Free accounts can use only free plans
     */
    public function testAddFreeRequiresFreeAccount()
    {
        $this->insight->accounts->addFree(12345, new PlanL());
    }

    /**
     * Test new free accounts adds valid record.
     */
    public function testNewFreeAccountAddValidRecord()
    {
        $this->assertEquals(0, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));
        $this->insight->accounts->addFree(12345, new FreePlan());
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals(AccountsInterface::FREE, $row['status']);
        $this->assertEquals(FreePlan::class, $row['plan']);
        $this->assertNull($row['billing_period']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']);
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['converted_to_free_at']->format('Y-m-d H:i:s'));
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(0, $row['mrr_value']);
        $this->assertFalse($row['had_trial']);
    }

    /**
     * Test if creation timestamp can be specified when free account is added.
     */
    public function testNewFreeCreationTimestampCanBeSpecified()
    {
        $in_two_weeks = new DateTimeValue('+14 days');

        $this->insight->accounts->addFree(12345, new FreePlan(), $in_two_weeks);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals($in_two_weeks->format('Y-m-d H:i:s'), $row['converted_to_free_at']->format('Y-m-d H:i:s'));
    }

    /**
     * Test if conversion to free timestamp can be specified.
     */
    public function testNewFreeConversionTimestampCanBeSpecified()
    {
        $in_two_weeks = new DateTimeValue('+14 days');

        $this->insight->accounts->addFree(12345, new FreePlan(), null, $in_two_weeks);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($in_two_weeks->format('Y-m-d H:i:s'), $row['converted_to_free_at']->format('Y-m-d H:i:s'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account can't convert before it is created
     */
    public function testNewFreeConversionCantBeBeforeCreation()
    {
        $this->insight->accounts->addFree(12345, new FreePlan(), new DateTimeValue('2016-02-20'), new DateTimeValue('2014-03-18'));
    }

    /**
     * Test if new paid returns account insight instance.
     */
    public function testNewPaidReturnsAccountInsightInstance()
    {
        $this->assertInstanceOf(AccountInsightInterface::class, $this->insight->accounts->addPaid(12345, new PlanM(), new Yearly()));
    }

    /**
     * Test if we can specify a custom creation timestamp for a paid plan.
     */
    public function testNewPaidAccountCanHaveCustomTimestamp()
    {
        $this->insight->accounts->addPaid(12345, new PlanM(), new Yearly(), new DateTimeValue('2015-12-05'));
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals(AccountsInterface::PAID, $row['status']);
        $this->assertEquals('2015-12-05 00:00:00', $row['created_at']->format('Y-m-d H:i:s'));
        $this->assertEquals(2015, $row['cohort_year']);
        $this->assertEquals(12, $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(41.583, $row['mrr_value']);
    }

    /**
     * Test if adding a yearly paid plan records correct values.
     */
    public function testNewPaidYearlyAccountAddValidRecord()
    {
        $this->insight->accounts->addPaid(12345, new PlanM(), new Yearly());

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals(AccountsInterface::PAID, $row['status']);
        $this->assertEquals(PlanM::class, $row['plan']);
        $this->assertEquals(Yearly::class, $row['billing_period']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']->format('Y-m-d H:i:s'));
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(41.583, $row['mrr_value']);
        $this->assertFalse($row['had_trial']);
    }

    /**
     * Test if adding a monthly paid plan records correct values.
     */
    public function testNewPaidMonthlyAccountAddValidRecord()
    {
        $this->assertEquals(0, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));
        $this->insight->accounts->addPaid(12345, new PlanL(), new Monthly());
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals(PlanL::class, $row['plan']);
        $this->assertEquals(Monthly::class, $row['billing_period']);
        $this->assertEquals(AccountsInterface::PAID, $row['status']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']);
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(99, $row['mrr_value']);
        $this->assertFalse($row['had_trial']);
    }

    /**
     * Test if new paid account with no conversion timestamp specified users created at timestamp.
     */
    public function testNewPaidSetsConversionToCreationTimestampWhenOmitted()
    {
        $this->insight->accounts->addPaid(12345, new PlanM(), new Yearly());
        $this->insight->accounts->addPaid(12346, new PlanM(), new Yearly(), new DateTimeValue('2015-12-05'));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['converted_to_paid_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($row['created_at'], $row['converted_to_paid_at']);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12346);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12346, $row['id']);
        $this->assertEquals('2015-12-05 00:00:00', $row['converted_to_paid_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($row['created_at'], $row['converted_to_paid_at']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account can't convert before it is created
     */
    public function testErrorWhenConversionTimestampIsBeforeCreationTimestamp()
    {
        $this->insight->accounts->addPaid(12346, new PlanM(), new Yearly(), new DateTimeValue('2015-12-05'), new DateTimeValue('2014-12-05'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts can use only paid plans
     */
    public function testAddPaidRequiresPaidPlan()
    {
        $this->insight->accounts->addPaid(12345, new FreePlan(), new None());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testInvalidPlanMrrRaisesAnException()
    {
        $this->insight->accounts->addPaid(12345, new PlanZ(), new Monthly());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Value 'this is not valid billing period is not a valid billing period for paid plans
     */
    public function testInvalidBillingPeriodRaisesAnException()
    {
        $this->insight->accounts->addPaid(12345, new PlanL(), new Invalid());
    }

    /**
     * Test if account already exists.
     */
    public function testExists()
    {
        $this->assertFalse($this->insight->accounts->exists(12345));
        $this->insight->accounts->addTrial(12345);
        $this->assertTrue($this->insight->accounts->exists(12345));
    }
}
