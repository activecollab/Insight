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
use ActiveCollab\Insight\Test\Fixtures\BillingPeriod\Yearly;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanL;
use ActiveCollab\Insight\Test\Fixtures\Plan\PlanM;

/**
 * @package ActiveCollab\Insight\Test
 */
class ArpuTest extends InsightTestCase
{
    /**
     * Test ARPU on day call.
     */
    public function testArpuOnDay()
    {
        $m_monthly_mrr = (new PlanM())->getMrrValue(new Monthly());
        $l_monthly_mrr = (new PlanL())->getMrrValue(new Monthly());
        $l_yearly_mrr = (new PlanL())->getMrrValue(new Yearly());

        $this->current_timestamp = new DateTimeValue('2016-01-12');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addPaid(1, new PlanM(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-13');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addPaid(2, new PlanM(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-14');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addPaid(3, new PlanL(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-15');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->addPaid(4, new PlanL(), new Monthly());

        $this->current_timestamp = new DateTimeValue('2016-01-16');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->cancel(1);
        $this->insight->accounts->cancel(2);

        $this->current_timestamp = new DateTimeValue('2016-01-18');
        DateTimeValue::setTestNow($this->current_timestamp);

        $this->insight->accounts->changePlan(4, new PlanL(), new Yearly());

        $this->assertEquals(0, $this->insight->accounts->countPaid(new DateValue('2016-01-11')));
        $this->assertEquals(0, $this->insight->arpu->getOnDay(new DateValue('2016-01-11')));

        $this->assertEquals(1, $this->insight->accounts->countPaid(new DateValue('2016-01-12')));
        $this->assertEquals($m_monthly_mrr, $this->insight->arpu->getOnDay(new DateValue('2016-01-12')));

        $this->assertEquals(2, $this->insight->accounts->countPaid(new DateValue('2016-01-13')));
        $this->assertEquals($m_monthly_mrr, $this->insight->arpu->getOnDay(new DateValue('2016-01-13')));

        $this->assertEquals(3, $this->insight->accounts->countPaid(new DateValue('2016-01-14')));
        $this->assertEquals(ceil(($m_monthly_mrr * 2 + $l_monthly_mrr) / 3), $this->insight->arpu->getOnDay(new DateValue('2016-01-14')));

        $this->assertEquals(4, $this->insight->accounts->countPaid(new DateValue('2016-01-15')));
        $this->assertEquals(ceil(($m_monthly_mrr * 2 + $l_monthly_mrr * 2) / 4), $this->insight->arpu->getOnDay(new DateValue('2016-01-15')));

        $this->assertEquals(4, $this->insight->accounts->countPaid(new DateValue('2016-01-16')));
        $this->assertEquals(ceil(($m_monthly_mrr * 2 + $l_monthly_mrr * 2) / 4), $this->insight->arpu->getOnDay(new DateValue('2016-01-16')));

        $this->assertEquals(2, $this->insight->accounts->countPaid(new DateValue('2016-01-17')));
        $this->assertEquals($l_monthly_mrr, $this->insight->arpu->getOnDay(new DateValue('2016-01-17')));

        $this->assertEquals(2, $this->insight->accounts->countPaid(new DateValue('2016-01-18')));
        $this->assertEquals(ceil(($l_monthly_mrr + $l_yearly_mrr) / 2), $this->insight->arpu->getOnDay(new DateValue('2016-01-18')));

        // A year in the future...
        $this->assertEquals(2, $this->insight->accounts->countPaid(new DateValue('2017-01-18')));
        $this->assertEquals(ceil(($l_monthly_mrr + $l_yearly_mrr) / 2), $this->insight->arpu->getOnDay(new DateValue('2017-01-18')));
    }
}
