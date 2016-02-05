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
class DowngradeTest extends InsightTestCase
{
    /**
     * Test if downgrade can accept 0 as MRR value (downgrade to free plan).
     */
    public function testDowngradeCanAcceptZeroMrrValue()
    {
        $this->insight->daily_accounts_history->newAccount(1245);
        $this->insight->daily_accounts_history->newDowngrade(12345, 0);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage MRR lost value should be 0 or more
     */
    public function testDowngradeRaisesAnErrorWhenMrrValueIsNegative()
    {
        $this->insight->daily_accounts_history->newAccount(1245);
        $this->insight->daily_accounts_history->newDowngrade(12345, -25);
    }

    /**
     * Test if paid account downgrade increases daily upgrades counter.
     */
    public function testPaidAccountDowngradeIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newDowngrade(12345, 5);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['downgrades']);
    }

    /**
     * Test if paid account upgrade records a MMR change.
     */
    public function testPaidAccountDowngradeUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(25, $row['mrr_value']);

        $this->insight->daily_accounts_history->newDowngrade(12345, 15);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(15, $row['mrr_value']);
    }

    /**
     * Test if MRR value of the downgrade needs to be lower than current MRR value.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage MRR value when dowgrading needs to be lower than current account MRR value
     */
    public function testDowngradeMrrValueNeedsToBeLowerThanCurrentMrrValue()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newAccount(12345, false, 25);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $this->insight->daily_accounts_history->newDowngrade(12345, 255);
    }
}
