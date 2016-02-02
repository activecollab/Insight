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
        $this->assertInstanceOf(MrrInterface::class, $this->insight->mrr);
        $this->assertInstanceOf(EventsInterface::class, $this->insight->events);
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
