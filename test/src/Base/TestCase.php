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

use ActiveCollab\DateValue\DateTimeValue;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\Insight\Test
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var DateTimeValue
     */
    protected $current_timestamp;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->log = $log = new Logger('Insight test');
        $this->log->pushHandler(new NullHandler());

        $this->current_timestamp = new DateTimeValue();
        DateTimeValue::setTestNow($this->current_timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        DateTimeValue::setTestNow(null);
        $this->current_timestamp = null;

        parent::tearDown();
    }
}
