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
class InsightTableNamesTest extends InsightTestCase
{
    /**
     * Test default table prefix value.
     */
    public function testDefaultTablePrefix()
    {
        $this->assertEquals('insight_', $this->insight->getTablePrefix());
    }

    /**
     * Test table prefix setter.
     */
    public function testTablePrefixCanBeChanged()
    {
        $this->assertEquals('awesome_', $this->insight->setTablePrefix('awesome_')->getTablePrefix());
    }
}
