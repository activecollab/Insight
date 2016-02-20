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
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Invalid;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
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
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']);
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(0, $row['mrr_value']);
    }

    /**
     * Test if new free returns account insight instance.
     */
    public function testNewFreeReturnsAccountInsightInstance()
    {
        $this->assertInstanceOf(AccountInsightInterface::class, $this->insight->accounts->addFree(12345));
    }

    /**
     * Test new free accounts adds valid record.
     */
    public function testNewFreeAccountAddValidRecord()
    {
        $this->assertEquals(0, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));
        $this->insight->accounts->addFree(12345);
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals(AccountsInterface::FREE, $row['status']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']);
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(0, $row['mrr_value']);
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
        $this->assertEquals(499, $row['mrr_value']);
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
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']->format('Y-m-d H:i:s'));
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(499, $row['mrr_value']);
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
        $this->assertEquals(AccountsInterface::PAID, $row['status']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['created_at']);
        $this->assertEquals(date('Y'), $row['cohort_year']);
        $this->assertEquals(date('m'), $row['cohort_month']);
        $this->assertNull($row['canceled_at']);
        $this->assertEquals(99, $row['mrr_value']);
    }

    /**
     * Test if new paid account with no conversion timestamp specified users created at timestamp
     */
    public function testNewPaidSetsConversionToCreationTimestampWhenOmitted()
    {
        $this->insight->accounts->addPaid(12345, new PlanM(), new Yearly());
        $this->insight->accounts->addPaid(12346, new PlanM(), new Yearly(), new DateTimeValue('2015-12-05'));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12345, $row['id']);
        $this->assertEquals($this->current_timestamp->format('Y-m-d H:i:s'), $row['converted_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($row['created_at'], $row['converted_at']);

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12346);

        $this->assertInternalType('array', $row);
        $this->assertEquals(12346, $row['id']);
        $this->assertEquals('2015-12-05 00:00:00', $row['converted_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($row['created_at'], $row['converted_at']);
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testInvalidPlanMrrRaisesAnException()
    {
        $this->insight->accounts->addPaid(12345, new PlanZ(), new Monthly());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Value 'this is not valid billing period is not a valid billing period
     */
    public function testInvalidBillingPeriodRaisesAnException()
    {
        $this->insight->accounts->addPaid(12345, new PlanL(), new Invalid());
    }

    public function testExists()
    {
        $this->assertFalse($this->insight->accounts->exists(12345));
        $this->insight->accounts->addTrial(12345);
        $this->assertTrue($this->insight->accounts->exists(12345));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Account #12345 does not exist
     */
    public function testCancelErrorsWhenAccountIsNotFound()
    {
        $this->assertFalse($this->insight->accounts->exists(12345));
        $this->insight->accounts->cancel(12345);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account #12345 is already canceled
     */
    public function testCancelErrorsWhenAccountIsAlreadyCanceled()
    {
        $this->insight->accounts->addTrial(12345);
        $this->insight->accounts->cancel(12345);
        $this->insight->accounts->cancel(12345);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value 'something not supported' is not a supported cancelation reason
     */
    public function testCancelErrorsWhenReasonIsNotSupported()
    {
        $this->insight->accounts->addTrial(12345);
        $this->insight->accounts->cancel(12345, 'something not supported');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account cancelation timestamp can't be before creation timestamp
     */
    public function testCancelErrorsWhenCancelationTimestampIsBeforeCreationTimestamp()
    {
        $this->insight->accounts->addTrial(12345, new DateTimeValue('2016-01-01 12:00:00'));
        $this->insight->accounts->cancel(12345, AccountsInterface::USER_CANCELED, new DateTimeValue('2015-01-01 12:00:00'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Account #12345 does not exist
     */
    public function testIsCanceledErrorsWhenAccountDoesNotExist()
    {
        $this->insight->accounts->isCanceled(12345);
    }

    /**
     * Test account cancel call.
     */
    public function testCancel()
    {
        $this->insight->accounts->addTrial(12345);
        $this->assertFalse($this->insight->accounts->isCanceled(12345));
        $this->insight->accounts->cancel(12345);
        $this->assertTrue($this->insight->accounts->isCanceled(12345));
    }

    /**
     * Confirm that default cancelation reason is "user canceled".
     */
    public function testDefaultCancelationReasonIsUserCanceled()
    {
        $this->insight->accounts->addTrial(12345);
        $this->insight->accounts->cancel(12345);

        $this->assertEquals(AccountsInterface::USER_CANCELED, $this->connection->executeFirstCell("SELECT `cancelation_reason` FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345));
    }

    /**
     * Test that cancelation reason can be changed when cancelation is recorded.
     */
    public function testCancelationReasonCanBeSpecified()
    {
        $this->insight->accounts->addTrial(12345);
        $this->insight->accounts->cancel(12345, AccountsInterface::TERMINATED);

        $this->assertEquals(AccountsInterface::TERMINATED, $this->connection->executeFirstCell("SELECT `cancelation_reason` FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345));
    }

    /**
     * Test if account cancelation sets its MRR value to zero.
     */
    public function testCancelSetsMrrValueToZero()
    {
        $this->insight->accounts->addPaid(12345, new PlanL(), new Monthly());
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(99, $row['mrr_value']);

        $this->insight->accounts->cancel(12345);

        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(0, $row['mrr_value']);
    }

    public function testCountPayingOnDay()
    {
        $this->insight->accounts->addTrial(1, new DateTimeValue('2016-01-22')); // Monhtly, churns soon
        $this->insight->accounts->addTrial(2, new DateTimeValue('2016-01-22')); // Monthly, loyal
        $this->insight->accounts->addTrial(3, new DateTimeValue('2016-01-22')); // Yearly, churns, but remains active
        $this->insight->accounts->addTrial(4, new DateTimeValue('2016-01-22')); // Never converts

        $this->insight->accounts->upgradeToPlan(1, new PlanM(), new Monthly(), new DateTimeValue('2016-02-15'));
    }
}
