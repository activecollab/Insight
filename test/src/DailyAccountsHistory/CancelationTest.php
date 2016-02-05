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

namespace ActiveCollab\Insight\Test\DailyAccountsHistory;

use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test\DailyAccountsHistory
 */
class CancelationTest extends InsightTestCase
{
    /**
     * Test if cancelation accepts 0 or above values for MRR lost.
     */
    public function testCancelationMrrLostCanBeZeroOrMore()
    {
        $this->insight->daily_accounts_history->newCancelation(12345, 0);
        $this->insight->daily_accounts_history->newCancelation(12345, 15);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage MRR lost value should be 0 or more
     */
    public function testCancelationRaisesAnErrorWhenMrrLostIsNegative()
    {
        $this->insight->daily_accounts_history->newCancelation(12345, -25);
    }

    /**
     * Test if cancelation with no MRR lost increases correct counter.
     */
    public function testCancelationWithNoMrrLostIncreasesCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newCancelation(12345, 0);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['free_cancelations']);
        $this->assertEquals(0, $row['paid_cancelations']);
    }

    /**
     * Test if cancelation with MRR lost increases correct counter.
     */
    public function testCancelationWithMrrLostIncreasesCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newCancelation(12345, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(0, $row['free_cancelations']);
        $this->assertEquals(1, $row['paid_cancelations']);
    }

    /**
     * Test if cancelation with MRR lost corrects account's daily MRR value.
     */
    public function testCancelationWithMrrLostCorrectsDailyMrrValue()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->assertEquals(25, $this->connection->executeFirstCell("SELECT mrr_value FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1));

        $this->insight->daily_accounts_history->newCancelation(12345, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->assertEquals(0, $this->connection->executeFirstCell("SELECT mrr_value FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1));
    }
}
