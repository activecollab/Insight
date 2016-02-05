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
class NewTrialAccountTest extends InsightTestCase
{
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
