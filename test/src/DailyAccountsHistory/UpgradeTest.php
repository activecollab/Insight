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
class UpgradeTest extends InsightTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testUpgradeRaisesAnErrorWhenMrrGainedIsZero()
    {
        $this->insight->daily_accounts_history->newAccount(1245);
        $this->insight->daily_accounts_history->newUpgrade(12345, 0);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testUpgradeRaisesAnErrorWhenMrrGainedIsNegative()
    {
        $this->insight->daily_accounts_history->newAccount(1245);
        $this->insight->daily_accounts_history->newUpgrade(12345, -25);
    }

    /**
     * Test if paid account upgrade increases daily upgrades counter.
     */
    public function testPaidAccountUpgradeIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newUpgrade(12345, 50);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['upgrades']);
    }

    /**
     * Test if paid account upgrade records a MMR change.
     */
    public function testPaidAccountUpgradeUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(25, $row['mrr_value']);

        $this->insight->daily_accounts_history->newUpgrade(12345, 155);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(155, $row['mrr_value']);
    }

    /**
     * Test if free account upgrade increases daily upgrades counter.
     */
    public function testFreeAccountUpgradeIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newUpgrade(12345, 50);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['upgrades']);
    }

    /**
     * Test if free account upgrade records a MMR change.
     */
    public function testFreeAccountUpgradeUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345);
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $this->insight->daily_accounts_history->newUpgrade(12345, 155);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(155, $row['mrr_value']);
    }
}
