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
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Monthly;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\None;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\FreePlan;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountUpdatesTest extends InsightTestCase
{
    /**
     * Test if account changes table creates referenced tables.
     */
    public function testAccountUpdatesCreatesReferencedTables()
    {
        $this->assertEquals([], $this->connection->getTableNames());

        $this->insight->getTableName('account_updates');

        $table_names = $this->connection->getTableNames();

        $this->assertCount(2, $table_names);

        $this->assertContains('insight_accounts', $table_names);
        $this->assertContains('insight_account_updates', $table_names);
    }

    /**
     * Test if account status change records an update records.
     */
    public function testAccountStatusChangeIsRecorded()
    {
        $account_updates_table = $this->insight->getTableName('account_updates');

        $this->insight->accounts->addTrial(1);

        $this->assertEquals(0, $this->connection->count($account_updates_table));

        $this->insight->accounts->cancel(1);

        $this->assertEquals(1, $this->connection->count($account_updates_table));

        $row = $this->connection->executeFirstRow("SELECT `old_status`, `new_status` FROM `$account_updates_table` WHERE `account_id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEquals(AccountsInterface::TRIAL, $row['old_status']);
        $this->assertEquals(AccountsInterface::CANCELED, $row['new_status']);
    }

    /**
     * Test account plan change is recorded.
     */
    public function testAccountPlanChangeIsRecorded()
    {
        $account_updates_table = $this->insight->getTableName('account_updates');

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());

        $this->assertEquals(0, $this->connection->count($account_updates_table));

        $this->insight->accounts->changePlan(1, new PlanL(), new Monthly());

        $this->assertEquals(1, $this->connection->count($account_updates_table));

        $row = $this->connection->executeFirstRow("SELECT `old_plan`, `new_plan` FROM `$account_updates_table` WHERE `account_id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEquals(PlanM::class, $row['old_plan']);
        $this->assertEquals(PlanL::class, $row['new_plan']);
    }

    /**
     * Test account billing period change is recorded.
     */
    public function testAccountBillingPeriodChangeIsRecorded()
    {
        $account_updates_table = $this->insight->getTableName('account_updates');

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());

        $this->assertEquals(0, $this->connection->count($account_updates_table));

        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $this->assertEquals(1, $this->connection->count($account_updates_table));

        $row = $this->connection->executeFirstRow("SELECT `old_billing_period`, `new_billing_period` FROM `$account_updates_table` WHERE `account_id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEquals(Monthly::class, $row['old_billing_period']);
        $this->assertEquals(Yearly::class, $row['new_billing_period']);
    }

    /**
     * Test if MRR changes are recorded.
     */
    public function testMrrChangesAreRecorded()
    {
        $account_updates_table = $this->insight->getTableName('account_updates');

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());
        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $row = $this->connection->executeFirstRow("SELECT `old_mrr_value`, `new_mrr_value` FROM `$account_updates_table` WHERE `account_id` = ?", 1);

        $this->assertInternalType('array', $row);
        $this->assertEquals((new PlanM())->getMrrValue(new Monthly()), $row['old_mrr_value']);
        $this->assertEquals((new PlanM())->getMrrValue(new Yearly()), $row['new_mrr_value']);
    }

    /**
     * Test if created at timestamp is properly recorded on different events.
     */
    public function testCreationTimestampIsRecorded()
    {
        $account_updates_table = $this->insight->getTableName('account_updates');

        $this->current_timestamp = new DateTimeValue('2016-01-12 11:11:11');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addTrial(1);

        $this->current_timestamp = new DateTimeValue('2016-01-28 12:12:12');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $this->current_timestamp = new DateTimeValue('2016-02-22 13:13:13');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new FreePlan(), new None());

        $this->current_timestamp = new DateTimeValue('2016-03-12 14:14:14');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->retire(1);

        /** @var DateTimeValue $first_update_timestamp */
        $first_update_timestamp = $this->connection->executeFirstCell("SELECT `created_at` FROM `$account_updates_table` WHERE `id` = ?", 1);

        $this->assertInstanceOf(DateTimeValue::class, $first_update_timestamp);
        $this->assertEquals('2016-01-28 12:12:12', $first_update_timestamp->format('Y-m-d H:i:s'));

        /** @var DateTimeValue $second_update_timestamp */
        $second_update_timestamp = $this->connection->executeFirstCell("SELECT `created_at` FROM `$account_updates_table` WHERE `id` = ?", 2);

        $this->assertInstanceOf(DateTimeValue::class, $second_update_timestamp);
        $this->assertEquals('2016-02-22 13:13:13', $second_update_timestamp->format('Y-m-d H:i:s'));

        /* @var DateTimeValue $first_update_timestamp */
        $third_update_timestamp = $this->connection->executeFirstCell("SELECT `created_at` FROM `$account_updates_table` WHERE `id` = ?", 3);

        $this->assertInstanceOf(DateTimeValue::class, $third_update_timestamp);
        $this->assertEquals('2016-03-12 14:14:14', $third_update_timestamp->format('Y-m-d H:i:s'));
    }
}
