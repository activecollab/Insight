<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare (strict_types = 1);

namespace ActiveCollab\Insight\Storage;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\Insight\Account\Event;
use ActiveCollab\Insight\ElementInterface;
use ActiveCollab\Insight\StorageInterface;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use ReflectionClass;

/**
 * @package ActiveCollab\Insight
 */
class RelationalDatabaseStorage implements StorageInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $table_prefix;

    /**
     * @param ConnectionInterface $connection
     * @param string              $table_prefix
     */
    public function __construct(ConnectionInterface &$connection, $table_prefix = 'metrics')
    {
        $this->connection = $connection;
        $this->table_prefix = $table_prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function store(ElementInterface $element)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTablePrefix(): string
    {
        return $this->table_prefix;
    }

    /**
     * @var array
     */
    private $store_names = [];

    /**
     * {@inheritdoc}
     */
    public function getStoreName($for_element): string
    {
        if (empty($for_element)) {
            throw new InvalidArgumentException('Element name is required');
        }

        if (substr($for_element, 0, 1) === '\\') {
            $for_element = ltrim($for_element, '\\');
        }

        if (empty($this->store_names[$for_element])) {
            if (class_exists($for_element)) {
                if (!(new ReflectionClass($for_element))->implementsInterface(ElementInterface::class)) {
                    throw new InvalidArgumentException('Element class expected (class that implements ' . ElementInterface::class . ')');
                }
            } else {
                throw new InvalidArgumentException("Class name '$for_element' not found");
            }

            $namespace_bits = explode('\\', substr($for_element, strlen('ActiveCollab\\Insight\\')));
            $last_namespace_bit = array_pop($namespace_bits);

            $this->store_names[$for_element] = empty($this->getTablePrefix()) ? '' : $this->getTablePrefix() . '_';

            foreach ($namespace_bits as $namespace_bit) {
                $this->store_names[$for_element] .= Inflector::tableize($namespace_bit) . '_';
            }

            $this->store_names[$for_element] .= Inflector::pluralize(Inflector::tableize($last_namespace_bit));
        }

        return $this->store_names[$for_element];
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreNames(): array
    {
        return [$this->getStoreName(Event::class)];
    }

    /**
     * {@inheritdoc}
     */
    public function prepareStores()
    {
        foreach ($this->getStoreNames() as $table_name) {
            if (!$this->connection->tableExists($table_name)) {
                switch ($table_name) {
                    case $this->getStoreName(Event::class):
                        $this->connection->execute("CREATE TABLE `$table_name` (
                            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `account_id` int(10) unsigned NOT NULL DEFAULT '0',
                            `event` varchar(191) NOT NULL DEFAULT '',
                            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            KEY (`account_id`, `event`),
                            KEY (`event`),
                            KEY (`created_at`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        foreach ($this->getStoreNames() as $table_name) {
            $this->connection->dropTable($table_name);
        }
    }
}
