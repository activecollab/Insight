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

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test
 */
class DailyAccountsHistoryTest extends InsightTestCase
{
    /**
     * Test if table for daily account history is created when requested.
     */
    public function testTableIsCreated()
    {
        $this->assertFalse($this->connection->tableExists('insight_daily_accounts_history'));
        $this->insight->getTableName('daily_accounts_history');
        $this->assertTrue($this->connection->tableExists('insight_daily_accounts_history'));
    }

    /**
     * Test if row with empty values is create when we request day ID.
     */
    public function testGetDayIdCretesRow()
    {
        $daily_accounts_history_table = $this->insight->getTableName('daily_accounts_history');

        $this->assertEquals(0, $this->connection->count($daily_accounts_history_table));
        $this->assertSame(1, $this->insight->daily_accounts_history->getDayId());
        $this->assertEquals(1, $this->connection->count($daily_accounts_history_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM $daily_accounts_history_table WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        foreach ($row as $k => $v) {
            if ($k == 'id') {
                $this->assertEquals(1, $v);
            } elseif ($k == 'day') {
                $this->assertEquals(date('Y-m-d'), $v);
            } else {
                $this->assertEquals(0, $v);
            }
        }
    }

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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trial accounts should not have MRR value
     */
    public function testNewTrialWithMrrValueRaisesLogicException()
    {
        $this->insight->daily_accounts_history->newAccount(12345, true, 15);
    }

    /**
     * Test if new trial properly logs a new account.
     */
    public function testNewTrialAccountIncreasesDailyCounters()
    {
        $this->insight->daily_accounts_history->newTrial(12345);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));
        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['new_accounts']);

        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
    }
}
