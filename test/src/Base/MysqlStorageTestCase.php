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

use ActiveCollab\Insight\Storage\RelationalDatabaseStorage;
use ActiveCollab\Insight\StorageInterface;

/**
 * @package ActiveCollab\Insight\Test\Base
 */
abstract class MysqlStorageTestCase extends TestCase
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->storage = new RelationalDatabaseStorage($this->connection);
        $this->storage->prepareStores();

        $this->assertCount(count($this->storage->getStoreNames()), $this->connection->getTableNames());
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->storage->clear();

        parent::tearDown();
    }
}
