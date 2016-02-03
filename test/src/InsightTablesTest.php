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

use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test
 */
class InsightTablesTest extends InsightTestCase
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

    /**
     * Test if Insight::getTableName() adds prefix to the name.
     */
    public function testGetTableNameAddsPrefix()
    {
        $this->assertEquals('insight_events', $this->insight->getTableName('events'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Table 'unknown_table' is not known
     */
    public function testExceptionWhenTableIsNotKnown()
    {
        $this->insight->getTableName('unknown_table');
    }

    /**
     * Test if table is created when missing.
     */
    public function testGetTableNameCreatesTableIfMissing()
    {
        $this->assertcount(0, $this->connection->getTableNames());

        $this->insight->getTableName('events');

        $this->assertEquals(['insight_events'], $this->connection->getTableNames());
    }

    /**
     * Test if table is not created if it already exists.
     */
    public function testGetTableNameSkipsExistingTable()
    {
        $this->connection->execute("CREATE TABLE IF NOT EXISTS `insight_events` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(191) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->insight->getTableName('events');

        foreach ($this->connection->execute('SHOW COLUMNS FROM insight_events') as $row) {
            $this->assertContains($row['Field'], ['id', 'name']);
        }
    }
}
