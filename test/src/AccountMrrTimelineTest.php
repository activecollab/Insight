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
class AccountMrrTimelineTest extends InsightTestCase
{
    /**
     * Test account MRR timeline.
     */
    public function testAccountMrrTimeline()
    {
        $account_mrr_spans_table = $this->insight->getTableName('account_mrr_spans');

        $this->assertEquals(0, $this->connection->count($account_mrr_spans_table));

        $this->current_timestamp = new DateTimeValue('2016-01-12 11:11:11');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addTrial(1);

        $this->current_timestamp = new DateTimeValue('2016-01-14 12:12:12');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new FreePlan(), new None());

        $this->current_timestamp = new DateTimeValue('2016-01-18 13:13:13');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new PlanM(), new Yearly());

        $this->current_timestamp = new DateTimeValue('2016-01-24 14:14:14');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new PlanL(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-27 15:15:15');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->retire(1);

        $this->current_timestamp = new DateTimeValue('2016-02-02 16:16:16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new PlanM(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-07-22 17:17:17');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->cancel(1);

        $this->assertEquals(3, $this->connection->count($account_mrr_spans_table));

        $timeline = $this->insight->account(1)->mrr_timeline->get();

        $this->assertInternalType('array', $timeline);
        $this->assertCount(3, $timeline);

        $this->assertSame(49.00, $timeline[0]['mrr_value']);
        $this->assertEquals('2016-02-02 16:16:16', $timeline[0]['started_at']->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-07-22 17:17:17', $timeline[0]['ended_at']->format('Y-m-d H:i:s'));

        $this->assertSame(99.00, $timeline[1]['mrr_value']);
        $this->assertEquals('2016-01-24 14:14:14', $timeline[1]['started_at']->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-01-27 15:15:15', $timeline[1]['ended_at']->format('Y-m-d H:i:s'));

        $this->assertSame(41.583, $timeline[2]['mrr_value']);
        $this->assertEquals('2016-01-18 13:13:13', $timeline[2]['started_at']->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-01-24 14:14:14', $timeline[2]['ended_at']->format('Y-m-d H:i:s'));
    }
}
