<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test\Base;

use ActiveCollab\Insight\Insight;

/**
 * @package ActiveCollab\Insight\Test
 */
abstract class InsightTestCase extends MysqliTestCase
{
    /**
     * @var Insight
     */
    protected $insight;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->insight = new Insight($this->connection, $this->log);
    }
}
