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
class FreeToTrialAccountTest extends InsightTestCase
{
    /**
     * Test if conversion from free to trial is recorded.
     */
    public function testFreeToTrialIncreasesDailyCounter()
    {
        $this->insight->daily_accounts_history->newAccount(12345, true);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $this->insight->daily_accounts_history->newFreeToTrial(12345);

        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow("SELECT * FROM {$this->insight->getTableName('daily_accounts_history')} WHERE id = ?", 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(1, $row['new_accounts']);
        $this->assertEquals(1, $row['conversions_to_trial']);
    }
}
