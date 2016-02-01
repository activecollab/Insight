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

use ActiveCollab\Insight\Account\Event;
use ActiveCollab\Insight\Storage\RelationalDatabaseStorage;
use ActiveCollab\Insight\Test\Base\MysqlTestCase;

/**
 * @package ActiveCollab\Insight\Test
 */
class RelationalDatabaseStorageTest extends MysqlTestCase
{
    /**
     * Test if default table prefix is metrics.
     */
    public function testTablePrefix()
    {
        $this->assertEquals('metrics', (new RelationalDatabaseStorage($this->connection))->getTablePrefix());
    }

    /**
     * Test if RDB storage properly prepares table names based on element classes.
     */
    public function testStoreNames()
    {
        $this->assertEquals('metrics_account_events', (new RelationalDatabaseStorage($this->connection))->getStoreName(Event::class));
    }

    /**
     * Check if storage returns correct list of table names.
     */
    public function testTables()
    {
        $this->assertEquals(['metrics_account_events'], (new RelationalDatabaseStorage($this->connection))->getStoreNames());
    }

    /**
     * Test if storage can create all required tables.
     */
    public function testCreateTables()
    {
        $storage = new RelationalDatabaseStorage($this->connection);

        foreach ($storage->getStoreNames() as $table_name) {
            $this->assertFalse($this->connection->tableExists($table_name));
        }

        $storage->prepareStores();

        foreach ($storage->getStoreNames() as $table_name) {
            $this->assertTrue($this->connection->tableExists($table_name));
        }
    }

    /**
     * Test if storage can clear all tables storage related tables.
     */
    public function testClear()
    {
        $this->connection->execute("CREATE TABLE IF NOT EXISTS `example_table` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(191) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->assertTrue($this->connection->tableExists('example_table'));

        $storage = new RelationalDatabaseStorage($this->connection);

        $storage->prepareStores();

        foreach ($storage->getStoreNames() as $table_name) {
            $this->assertTrue($this->connection->tableExists($table_name));
        }

        $storage->clear();

        foreach ($storage->getStoreNames() as $table_name) {
            $this->assertFalse($this->connection->tableExists($table_name));
        }

        $this->assertTrue($this->connection->tableExists('example_table'));
        $this->connection->dropTable('example_table');
        $this->assertFalse($this->connection->tableExists('example_table'));
    }
}
