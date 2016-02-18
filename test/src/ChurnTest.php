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
class ChurnTest extends InsightTestCase
{
    /**
     * Test if churned_accounts table creates all required tables.
     */
    public function testChurnAccountsTableCreatesReferencedTables()
    {
        $this->assertEquals([], $this->connection->getTableNames());

        $this->insight->getTableName('churned_accounts');

        $table_names = $this->connection->getTableNames();

        $this->assertCount(3, $table_names);

        $this->assertContains('insight_churned_accounts', $table_names);
        $this->assertContains('insight_monthly_churn', $table_names);
        $this->assertContains('insight_accounts', $table_names);
    }
}
