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
use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountCancelTest extends InsightTestCase
{
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
}
