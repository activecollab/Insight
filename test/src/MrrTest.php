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
class MrrTest extends InsightTestCase
{
    /**
     * Test MRR on day call.
     */
    public function testMrrOnDay()
    {
        $this->current_timestamp = new DateTimeValue('2016-01-12 11:11:11');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addTrial(1);
        $this->insight->accounts->addTrial(2);
        $this->insight->accounts->addTrial(3);

        $this->current_timestamp = new DateTimeValue('2016-01-15 12:12:12');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new FreePlan(), new None());
        $this->insight->accounts->changePlan(3, new PlanM(), new Yearly());

        $this->current_timestamp = new DateTimeValue('2016-01-16 13:13:13');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(1, new PlanL(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-17 14:14:14');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(3, new PlanL(), new Yearly());

        $this->current_timestamp = new DateTimeValue('2016-01-17 14:14:15');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(3, new PlanL(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-17 14:14:16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(3, new PlanM(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-18 15:15:15');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->cancel(1);

        $m_monthly_mrr = (int) ceil((new PlanM())->getMrrValue(new Monthly()));
        $m_yearly_mrr = (int) ceil((new PlanM())->getMrrValue(new Yearly()));
        $l_monthly_mrr = (int) ceil((new PlanL())->getMrrValue(new Monthly()));

        $this->assertEquals(0, $this->insight->mrr->getOnDay(new DateValue('2016-01-12')));
        $this->assertEquals(0, $this->insight->mrr->getOnDay(new DateValue('2016-01-13')));
        $this->assertEquals(0, $this->insight->mrr->getOnDay(new DateValue('2016-01-14')));

        // Account #3, M yearly
        $this->assertSame($m_yearly_mrr, $this->insight->mrr->getOnDay(new DateValue('2016-01-15')));

        // Account #3, M yearly + Account #1, L monthly
        $this->assertEquals($m_yearly_mrr + $l_monthly_mrr, $this->insight->mrr->getOnDay(new DateValue('2016-01-16')));

        // Account #3, M monthly + Account #1, L monthly
        $this->assertEquals($m_monthly_mrr + $l_monthly_mrr, $this->insight->mrr->getOnDay(new DateValue('2016-01-17')));

        // Account #3, M monthly + Account #1, L monthly (canceled, but day started with this value)
        $this->assertEquals($m_monthly_mrr + $l_monthly_mrr, $this->insight->mrr->getOnDay(new DateValue('2016-01-18')));

        // Account #3, M monthly
        $this->assertEquals($m_monthly_mrr, $this->insight->mrr->getOnDay(new DateValue('2016-01-19')));
    }
}
