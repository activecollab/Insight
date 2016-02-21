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
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountRetiringTest extends InsightTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Account #12345 does not exist
     */
    public function testRetireErrorsWhenAccountIsNotFound()
    {
        $this->assertFalse($this->insight->accounts->exists(12345));
        $this->insight->accounts->retire(12345);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account #12345 is already retired
     */
    public function testRetireErrorsWhenAccountIsAlreadyRetired()
    {
        $this->insight->accounts->addTrial(12345);
        $this->insight->accounts->retire(12345);
        $this->insight->accounts->retire(12345);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account #12345 is already canceled
     */
    public function testRetireErrorsWhenAccountIsCanceled()
    {
        $this->insight->accounts->addTrial(12345);
        $this->insight->accounts->cancel(12345);
        $this->insight->accounts->retire(12345);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Account retireing timestamp can't be before creation timestamp
     */
    public function testRetireErrorsWhenRetieringTimestampIsBeforeCreationTimestamp()
    {
        $this->insight->accounts->addTrial(12345, new DateTimeValue('2016-01-01 12:00:00'));
        $this->insight->accounts->retire(12345, new DateTimeValue('2015-01-01 12:00:00'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Account #12345 does not exist
     */
    public function testIsRetiredErrorsWhenAccountDoesNotExist()
    {
        $this->insight->accounts->isRetired(12345);
    }

    /**
     * Test account retire call.
     */
    public function testRetire()
    {
        $this->insight->accounts->addTrial(12345);
        $this->assertFalse($this->insight->accounts->isRetired(12345));
        $this->insight->accounts->retire(12345);
        $this->assertTrue($this->insight->accounts->isRetired(12345));
    }

    /**
     * Test if account retiering sets its MRR value to zero.
     */
    public function testRetireSetsMrrValueToZero()
    {
        $this->insight->accounts->addPaid(12345, new PlanL(), new Monthly());
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(99, $row['mrr_value']);

        $this->insight->accounts->retire(12345);

        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM {$this->insight->getTableName('accounts')}"));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('accounts')} WHERE `id` = ?", 12345);

        $this->assertInternalType('array', $row);
        $this->assertEquals(0, $row['mrr_value']);
    }
}
