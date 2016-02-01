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

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\Storage;
use ActiveCollab\Insight\StorageInterface;
use ActiveCollab\Insight\Utilities\Timestamp;
use Redis;
use RedisCluster;

/**
 * @package ActiveCollab\Insight\Test
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \mysqli
     */
    protected $link;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var StorageInterface
     */
    protected $storage;

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

        $this->link = new \MySQLi('localhost', 'root', '', 'activecollab_insight_test');

        if ($this->link->connect_error) {
            throw new \RuntimeException('Failed to connect to database. MySQL said: ' . $this->link->connect_error);
        }

        $this->connection = new MysqliConnection($this->link);
        $this->storage = new Storage($this->connection);

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
