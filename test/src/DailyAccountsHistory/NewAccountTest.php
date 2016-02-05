<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test\DailyAccountsHistory;

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test\DailyAccountsHistory
 */
class NewAccountTest extends InsightTestCase
{
    /**
     * Confirm that new acount increases number of new daily accounts.
     */
    public function testNewAccountIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345, false, 15);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);

        $this->insight->daily_accounts_history->newAccount(12345, false, 25);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));
        $this->assertEquals(2, $this->connection->executeFirstCell("SELECT `new_accounts` FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1));

        $this->insight->daily_accounts_history->newAccount(12345, false, 25, new DateValue('2016-02-01'));
        $this->assertEquals(2, $this->connection->count($this->insight->getTableName('daily_accounts_history')));
        $this->assertEquals(1, $this->connection->executeFirstCell("SELECT `new_accounts` FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 2));
    }

    /**
     * Test new paid account adds an MRR record to daily accounts MRR log.
     */
    public function testNewAccountUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345, false, 15);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(15, $row['mrr_value']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage MRR value can't be negative for new accounts
     */
    public function testNewAccountCantHaveNegativeMrr()
    {
        $this->insight->daily_accounts_history->newAccount(12345, false, -15);
    }
}
