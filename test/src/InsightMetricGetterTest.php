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

use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Metric\ArpuInterface;
use ActiveCollab\Insight\Metric\ChurnInterface;
use ActiveCollab\Insight\Metric\ConversionRatesInterface;
use ActiveCollab\Insight\Metric\DailyAccountsHistoryInterface;
use ActiveCollab\Insight\Metric\EventsInterface;
use ActiveCollab\Insight\Metric\MrrInterface;
use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test
 */
class InsightMetricGetterTest extends InsightTestCase
{
    /**
     * Test if we can get a supported metrics as properties.
     */
    public function testGetSupportedMetrics()
    {
        $this->assertInstanceOf(AccountsInterface::class, $this->insight->accounts);
        $this->assertInstanceOf(ChurnInterface::class, $this->insight->churn);
        $this->assertInstanceOf(ConversionRatesInterface::class, $this->insight->conversion_rates);
        $this->assertInstanceOf(DailyAccountsHistoryInterface::class, $this->insight->daily_accounts_history);
        $this->assertInstanceOf(EventsInterface::class, $this->insight->events);
        $this->assertInstanceOf(MrrInterface::class, $this->insight->mrr);
        $this->assertInstanceOf(ArpuInterface::class, $this->insight->arpu);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Metric 'metric_that_does_not_exist' is not currently supported
     */
    public function testExceptionOnUnsupportedMetric()
    {
        $this->insight->metric_that_does_not_exist;
    }
}
