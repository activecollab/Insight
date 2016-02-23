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

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\Insight\Test\Base\InsightTestCase;
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\FreePlan;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

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

        $this->assertCount(5, $table_names);

        $this->assertContains('insight_churned_accounts', $table_names);
        $this->assertContains('insight_monthly_churn', $table_names);
        $this->assertContains('insight_accounts', $table_names);
        $this->assertContains('insight_account_status_spans', $table_names);
        $this->assertContains('insight_account_updates', $table_names);
    }

    public function testCreateSnapshot()
    {
        $this->insight->churn->snapshot(new DateValue('2016-02-22'), 100, 10000);

        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('monthly_churn'), ['`day` = ?', new DateValue('2016-02-22')]));
        $this->assertEquals(1, $this->connection->count($this->insight->getTableName('monthly_churn'), ['`day` = ?', new DateValue('2016-02-01')]));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Snapshot for 2016-02 already exists
     */
    public function testErrorWhenSnapshotIsCreatedForExistingMonth()
    {
        $this->insight->churn->snapshot(new DateValue('2016-02-02'), 100, 10000);
        $this->insight->churn->snapshot(new DateValue('2016-02-22'), 100, 10000);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Account #1234 does not exist
     */
    public function testErrorWhenChurningAccountThatDoesNotExist()
    {
        $this->insight->churn->snapshot(new DateValue('2016-02-02'), 100, 10000);

        $this->insight->churn->churn(1234, new DateValue('2016-02-02'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Only paid accounts can churn
     */
    public function testTrialsCantChurn()
    {
        $this->insight->churn->snapshot(new DateValue('2016-02-02'), 100, 10000);

        $this->insight->accounts->addTrial(1);
        $this->insight->churn->churn(1, new DateValue('2016-02-02'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Only paid accounts can churn
     */
    public function testFreeCantChurn()
    {
        $this->insight->churn->snapshot(new DateValue('2016-02-02'), 100, 10000);

        $this->insight->accounts->addFree(1, new FreePlan());
        $this->insight->churn->churn(1, new DateValue('2016-02-02'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Churn snapshot for 2016-02 was not found
     */
    public function testChurnSnapshotNotFound()
    {
        $this->insight->accounts->addPaid(1, new PlanM(), new Yearly());
        $this->insight->churn->churn(1, new DateValue('2016-02-12'));
    }

//    /**
//     * Test successful churn.
//     */
//    public function testChurn()
//    {
//        $this->insight->accounts->addPaid(1, new PlanM(), new Yearly(), new DateValue('2014-12-31'));
//
//        $this->insight->churn->snapshot(new DateValue('2016-02-01'));
//        $this->insight->churn->churn(1, new DateValue('2016-02-12'));
//    }
}
