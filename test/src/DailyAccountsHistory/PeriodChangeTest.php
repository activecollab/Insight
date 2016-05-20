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
class PeriodChangeTest extends InsightTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testPeriodChangeRaisesAnErrorWhenMrrGainedIsZero()
    {
        $this->insight->daily_accounts_history->newAccount(1245);
        $this->insight->daily_accounts_history->newPeriodChange(12345, 0);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testPeriodChangeRaisesAnErrorWhenMrrGainedIsNegative()
    {
        $this->insight->daily_accounts_history->newAccount(1245);
        $this->insight->daily_accounts_history->newPeriodChange(12345, -25);
    }

    /**
     * Test if paid account period change increases daily counter.
     */
    public function testPeriodChangeIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newPeriodChange(12345, 250 / 12);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['period_changes']);
    }

    /**
     * Test if paid account period change records a MMR change.
     */
    public function testPeriodChangeUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(25, $row['mrr_value']);

        $this->insight->daily_accounts_history->newPeriodChange(12345, 250 / 12);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(round(250 / 12, 3), $row['mrr_value']);
    }
}
