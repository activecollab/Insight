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
use ActiveCollab\DateValue\DateValue;
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
    public function testAccountStatusSpansCreatesReferencedTables()
    {
        $this->assertEquals([], $this->connection->getTableNames());

        $this->insight->getTableName('account_status_spans');

        $table_names = $this->connection->getTableNames();

        $this->assertCount(4, $table_names);

        $this->assertContains('insight_accounts', $table_names);
        $this->assertContains('insight_account_status_spans', $table_names);
        $this->assertContains('insight_account_mrr_spans', $table_names);
        $this->assertContains('insight_account_updates', $table_names);
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
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertNull($row['ended_at']);
        $this->assertNull($row['ended_on']);
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
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertInstanceOf(DateTimeValue::class, $row['ended_at']);
        $this->assertInstanceOf(DateValue::class, $row['ended_on']);

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_status_spans_table` WHERE `id` = ?", 2);

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals(AccountsInterface::PAID, $row['status']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertEmpty($row['ended_at']);
        $this->assertEmpty($row['ended_on']);
    }

    /**
     * Test if trigger properly sets date value based on start date and time.
     */
    public function testInsertUpdatesDateValue()
    {
        $account_status_spans_table = $this->insight->getTableName('account_status_spans');
        $this->insight->accounts->addTrial(1);

        $row = $this->connection->executeFirstRow("SELECT `started_at`, `started_on` FROM `$account_status_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertEquals($row['started_at']->format('Y-m-d'), $row['started_on']->format('Y-m-d'));
    }

    /**
     * Test if trigger properly sets date value based on end date and time.
     */
    public function testUpdateUpdatesDateValue()
    {
        $account_status_spans_table = $this->insight->getTableName('account_status_spans');

        $this->insight->accounts->addTrial(1);
        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_status_spans_table` WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);

        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertEquals($row['started_at']->format('Y-m-d'), $row['started_on']->format('Y-m-d'));

        $this->assertInstanceOf(DateTimeValue::class, $row['ended_at']);
        $this->assertInstanceOf(DateValue::class, $row['ended_on']);
        $this->assertEquals($row['ended_at']->format('Y-m-d'), $row['ended_on']->format('Y-m-d'));
    }
}
