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
class TrialToPaidAccountTest extends InsightTestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Paid accounts should have MRR value
     */
    public function testTrialToPaidRaisesAnErrorWhenMrrIsMissing()
    {
        $this->insight->daily_accounts_history->newTrialToPaid(12345, -25);
    }

    /**
     * Test if trial to paid conversion is recorded as conversion (increases a conversions_to_paid counter).
     */
    public function testTrialToPaidIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newTrial(12345);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newTrialToPaid(12345, 25);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['conversions_to_paid']);
    }

    /**
     * Test if trial to paid records as a MMR change.
     */
    public function testTrialToPaidUpdatesDailyMrr()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_account_mrr')));
        $this->insight->daily_accounts_history->newTrialToPaid(12345, 155);
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_account_mrr')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_account_mrr')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);

        $this->assertEquals(12345, $row['account_id']);
        $this->assertEquals(date('Y-m-d'), $row['day']);
        $this->assertEquals(155, $row['mrr_value']);
    }
}
