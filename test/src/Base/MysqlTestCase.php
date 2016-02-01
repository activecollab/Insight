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

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DateValue\DateTimeValue;

/**
 * @package ActiveCollab\Insight\Test
 */
abstract class MysqlTestCase extends TestCase
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
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->connection->disconnect();

        parent::tearDown();
    }
}
