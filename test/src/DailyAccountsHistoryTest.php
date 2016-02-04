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
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('daily_accounts_history')));
        $this->assertSame(1, $this->insight->daily_accounts_history->getDayId());
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('daily_accounts_history')));

        $row = $this->connection->executeFirstRow('SELECT * FROM insight_daily_accounts_history WHERE id = ?', 1);
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
}
