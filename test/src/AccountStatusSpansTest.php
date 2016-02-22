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

use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountStatusSpansTest extends InsightTestCase
{
    /**
     * Test if account status spans table creates referenced tables.
     */
    public function testAccountUpdatesCreatesReferencedTables()
    {
        $this->assertEquals([], $this->connection->getTableNames());

        $this->insight->getTableName('account_status_spans');

        $table_names = $this->connection->getTableNames();

        $this->assertCount(2, $table_names);

        $this->assertContains('insight_accounts', $table_names);
        $this->assertContains('insight_account_status_spans', $table_names);
    }

    /**
     * Test if status span is started when new account is inserted.
     */
    public function testSpanIsStartedOnNewAccount()
    {
        $account_status_spans_table = $this->insight->getTableName('account_status_spans');

        $this->assertEquals(0, $this->connection->count($account_status_spans_table));

        $this->insight->accounts->addTrial(1);

        $this->assertEquals(1, $this->connection->count($account_status_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_status_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals(AccountsInterface::TRIAL, $row['status']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertNull($row['ended_at']);
    }

    /**
     * Test span is ended on status change.
     */
    public function testSpanIsEndedOnStatusChange()
    {
        $account_status_spans_table = $this->insight->getTableName('account_status_spans');

        $this->assertEquals(0, $this->connection->count($account_status_spans_table));

        $this->insight->accounts->addTrial(1);

        $this->assertEquals(1, $this->connection->count($account_status_spans_table));

        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $this->assertEquals(2, $this->connection->count($account_status_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_status_spans_table` WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals(AccountsInterface::TRIAL, $row['status']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateTimeValue::class, $row['ended_at']);

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_status_spans_table` WHERE `id` = ?", 2);

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals(AccountsInterface::PAID, $row['status']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertEmpty($row['ended_at']);
    }
}
