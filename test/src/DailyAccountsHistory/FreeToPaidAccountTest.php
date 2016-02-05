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

use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test\DailyAccountsHistory
 */
class FreeToPaidAccountTest extends InsightTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testFreeToPaidRaisesAnErrorWhenMrrIsMissing()
    {
        $this->insight->daily_accounts_history->newFreeToPaid(12345, -25);
    }

    /**
     * Test if free to paid conversion is recorded as conversion (increases a conversions_to_paid counter).
     */
    public function testFreeToPaidIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newFreeToPaid(12345, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['conversions_to_paid']);
    }

    /**
     * Test if free to paid records as a MMR change.
     */
    public function testFreeToPaidUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newFreeToPaid(12345, 199);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(199, $row['mrr_value']);
    }
}
