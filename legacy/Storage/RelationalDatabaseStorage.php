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
use ActiveCollab\Insight\AccountInterface;
use ActiveCollab\Insight\ElementInterface;
use ActiveCollab\Insight\StorageInterface;
use ActiveCollab\Insight\Test\Fixtures\Account;
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
     * Return account by account ID.
     *
     * @param  int              $account_id
     * @return AccountInterface
     */
    public function getAccount($account_id)
    {
        return new Account($this, $account_id);
    }

    /**
     * {@inheritdoc}
     */
    public function store(ElementInterface $element)
    {
        if ($element instanceof Event) {
            $this->connection->insert($this->getStoreName(Event::class), [
                'account_id' => $element->getAccount()->getId(),
                'name' => $element->getName(),
                'created_at' => $element->getTimestamp(),
                'context' => serialize($element->getContext()),
            ]);
        }

        return $this;
    }

    /**
     * Return total number of elements of a given type that we have stored.
     *
     * @param  string $element_type
     * @return int
     */
    public function count(string $element_type): int
    {
        return $this->connection->count($this->getStoreName($element_type));
    }

    /**
     * Return total number of elements of a given type that we have stored for the given account.
     *
     * @param  string           $element_type
     * @param  AccountInterface $account
     * @return int
     */
    public function countByAccount(string $element_type, AccountInterface $account): int
    {
        return $this->connection->count($this->getStoreName($element_type), ['`account_id` = ?', $account->getId()]);
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
    public function getStoreName(string $element_type): string
    {
        if (empty($element_type)) {
            throw new InvalidArgumentException('Element name is required');
        }

        if (substr($element_type, 0, 1) === '\\') {
            $element_type = ltrim($element_type, '\\');
        }

        if (empty($this->store_names[$element_type])) {
            if (class_exists($element_type)) {
                if (!(new ReflectionClass($element_type))->implementsInterface(ElementInterface::class)) {
                    throw new InvalidArgumentException('Element class expected (class that implements ' . ElementInterface::class . ')');
                }
            } else {
                throw new InvalidArgumentException("Class name '$element_type' not found");
            }

            $namespace_bits = explode('\\', substr($element_type, strlen('ActiveCollab\\Insight\\')));
            $last_namespace_bit = array_pop($namespace_bits);

            $this->store_names[$element_type] = empty($this->getTablePrefix()) ? '' : $this->getTablePrefix() . '_';

            foreach ($namespace_bits as $namespace_bit) {
                $this->store_names[$element_type] .= Inflector::tableize($namespace_bit) . '_';
            }

            $this->store_names[$element_type] .= Inflector::pluralize(Inflector::tableize($last_namespace_bit));
        }

        return $this->store_names[$element_type];
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
                            `name` varchar(191) NOT NULL DEFAULT '',
                            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                            `context` LONGTEXT,
                            PRIMARY KEY (`id`),
                            KEY (`account_id`, `name`),
                            KEY (`name`),
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
