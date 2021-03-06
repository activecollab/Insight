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
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\None;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\FreePlan;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountMrrSpansTest extends InsightTestCase
{
    /**
     * Test if account MMR spans table creates referenced tables.
     */
    public function testAccountUpdatesCreatesReferencedTables()
    {
        $this->assertEquals([], $this->connection->getTableNames());

        $this->insight->getTableName('account_mrr_spans');

        $table_names = $this->connection->getTableNames();

        $this->assertCount(4, $table_names);

        $this->assertContains('insight_accounts', $table_names);
        $this->assertContains('insight_account_status_spans', $table_names);
        $this->assertContains('insight_account_mrr_spans', $table_names);
        $this->assertContains('insight_account_updates', $table_names);
    }

    /**
     * Confirm that new trials don't create MRR spans.
     */
    public function testTrialDoesNotCreateMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addTrial(1);

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));
    }

    /**
     * Confirm that new free accounts don't create MRR spans.
     */
    public function testFreeDoesNotCreateMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addFree(1, new FreePlan());

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));
    }

    /**
     * Confirm that conversion from trial to free account does not create MRR span.
     */
    public function testTrialToFreeDoesNotCreateMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addTrial(1);
        $this->insight->accounts->changePlan(1, new FreePlan(), new None());

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));
    }

    /**
     * Test if new paid account opens a good MRR span.
     */
    public function testPaidCreatesMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());

        $this->assertEquals(1, $this->connection->count($account_mrr_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanM())->getMrrValue(new Monthly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertNull($row['ended_at']);
        $this->assertNull($row['ended_on']);
    }

    /**
     * Test if trial to paid conversion creates a new MRR span.
     */
    public function testTrialToPaidCreatesMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addTrial(1);
        $this->insight->accounts->changePlan(1, new PlanL(), new Monthly());

        $this->assertEquals(1, $this->connection->count($account_mrr_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanL())->getMrrValue(new Monthly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertNull($row['ended_at']);
        $this->assertNull($row['ended_on']);
    }

    /**
     * Test if free to paid creates a new MRR span.
     */
    public function testFreeToPaidCreatesMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addFree(1, new FreePlan());
        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $this->assertEquals(1, $this->connection->count($account_mrr_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanM())->getMrrValue(new Yearly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertNull($row['ended_at']);
        $this->assertNull($row['ended_on']);
    }

    /**
     * Test if change from paid account to another closes old MRR span, and opens a new one.
     */
    public function testPaidPlanChangeClosesPreviousAndCreatesNewMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addPaid(1, new PlanL(), new Monthly());
        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $this->assertEquals(2, $this->connection->count($account_mrr_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table` WHERE `id` = ?", 1);

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanL())->getMrrValue(new Monthly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertInstanceOf(DateTimeValue::class, $row['ended_at']);
        $this->assertInstanceOf(DateValue::class, $row['ended_on']);

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table` WHERE `id` = ?", 2);

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanM())->getMrrValue(new Yearly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertNull($row['ended_at']);
        $this->assertNull($row['ended_on']);
    }

    /**
     * Test if retiering of a paid account closes existing MRR span.
     */
    public function testRetirePaidAccountClosesMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());
        $this->insight->accounts->retire(1);

        $this->assertEquals(1, $this->connection->count($account_mrr_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanM())->getMrrValue(new Monthly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertInstanceOf(DateTimeValue::class, $row['ended_at']);
        $this->assertInstanceOf(DateValue::class, $row['ended_on']);
    }

    /**
     * Test if cancelation of a paid account closes existing MRR span.
     */
    public function testCancelPaidAccountClosesMrrSpan()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());
        $this->insight->accounts->cancel(1);

        $this->assertEquals(1, $this->connection->count($account_mrr_spans_table));

        $row = $this->connection->executeFirstRow("SELECT * FROM `$account_mrr_spans_table`");

        $this->assertInternalType('array', $row);

        $this->assertEquals(1, $row['account_id']);
        $this->assertEquals((new PlanM())->getMrrValue(new Monthly()), $row['mrr_value']);
        $this->assertInstanceOf(DateTimeValue::class, $row['started_at']);
        $this->assertInstanceOf(DateValue::class, $row['started_on']);
        $this->assertInstanceOf(DateTimeValue::class, $row['ended_at']);
        $this->assertInstanceOf(DateValue::class, $row['ended_on']);
    }
}
