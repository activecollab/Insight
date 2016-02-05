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

namespace ActiveCollab\Insight;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\Insight\AccountInsight\AccountInsight;
use ActiveCollab\Insight\Metric\MetricInterface;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @property \ActiveCollab\Insight\Metric\MrrInterface                  $mrr
 * @property \ActiveCollab\Insight\Metric\EventsInterface               $events
 * @property \ActiveCollab\Insight\Metric\DailyAccountsHistoryInterface $daily_accounts_history
 * @package ActiveCollab\Insight
 */
class Insight implements InsightInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface     $log
     */
    public function __construct(ConnectionInterface &$connection, LoggerInterface &$log)
    {
        $this->connection = $connection;
        $this->log = $log;

        $this->existing_tables = $this->connection->getTableNames();
    }

    /**
     * @var AccountInsight[]
     */
    private $account_insights = [];

    /**
     * {@inheritdoc}
     */
    public function account(int $account_id)
    {
        if (empty($this->account_insights[$account_id])) {
            $this->account_insights[$account_id] = new AccountInsight($this, $this->connection, $this->log, $account_id);
        }

        return $this->account_insights[$account_id];
    }

    /**
     * @var MetricInterface[]
     */
    private $metrics = [];

    /**
     * Get a supported metric as a property.
     *
     * @param  string          $metric
     * @return MetricInterface
     */
    public function __get($metric)
    {
        if (empty($this->metrics[$metric])) {
            $class_name = '\\ActiveCollab\\Insight\\Metric\\' . Inflector::classify($metric);

            if (class_exists($class_name)) {
                $this->metrics[$metric] = new $class_name($this, $this->connection, $this->log);
            } else {
                throw new LogicException("Metric '$metric' is not currently supported");
            }
        }

        return $this->metrics[$metric];
    }

    /**
     * @var string
     */
    private $table_prefix = 'insight_';

    /**
     * Return table prefix.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->table_prefix;
    }

    /**
     * Set table prefix.
     *
     * @param  string $value
     * @return $this
     */
    public function &setTablePrefix(string $value)
    {
        $this->table_prefix = trim($value);

        return $this;
    }

    /**
     * @var string[]
     */
    private $existing_tables = [];

    /**
     * Return prefixed table name and make sure that table exists.
     *
     * @param  string $table_name
     * @return string
     */
    public function getTableName($table_name): string
    {
        $prefixed_table_name = $this->getTablePrefix() . $table_name;

        if (!in_array($prefixed_table_name, $this->existing_tables)) {
            switch ($table_name) {
                case 'daily_accounts_history':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                        `day` DATE NOT NULL,
                        `new_accounts` int unsigned NOT NULL DEFAULT '0',
                        `conversions_to_trial` int unsigned NOT NULL DEFAULT '0',
                        `conversions_to_free` int unsigned NOT NULL DEFAULT '0',
                        `conversions_to_paid` int unsigned NOT NULL DEFAULT '0',
                        `upgrades` int unsigned NOT NULL DEFAULT '0',
                        `downgrades` int unsigned NOT NULL DEFAULT '0',
                        `free_cancelations` int unsigned NOT NULL DEFAULT '0',
                        `paid_cancelations` int unsigned NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        UNIQUE (`day`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    $this->connection->execute('DROP TRIGGER IF EXISTS `daily_accounts_history_default_day`');
                    $this->connection->execute("CREATE TRIGGER `daily_accounts_history_default_day` BEFORE INSERT ON `$prefixed_table_name` FOR EACH ROW
                        BEGIN
                            IF NEW.day IS NULL THEN
                                SET NEW.day = DATE(UTC_TIMESTAMP());
                            END IF;
                        END");

                    break;
                case 'daily_account_mrr':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                        `account_id` int(10) unsigned NOT NULL DEFAULT '0',
                        `day` DATE NOT NULL,
                        `mrr_value` DECIMAL(13,3) DEFAULT '0',
                        PRIMARY KEY (`id`),
                        UNIQUE (`account_id`, `day`),
                        KEY (`day`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    $this->connection->execute('DROP TRIGGER IF EXISTS `daily_account_mrr_default_day`');
                    $this->connection->execute("CREATE TRIGGER `daily_account_mrr_default_day` BEFORE INSERT ON `$prefixed_table_name` FOR EACH ROW
                        BEGIN
                            IF NEW.day IS NULL THEN
                                SET NEW.day = DATE(UTC_TIMESTAMP());
                            END IF;
                        END");

                    break;
                case 'events':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `account_id` int(10) unsigned NOT NULL DEFAULT '0',
                        `name` varchar(191) NOT NULL DEFAULT '',
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `context` JSON,
                        PRIMARY KEY (`id`),
                        KEY (`account_id`, `name`),
                        KEY (`name`),
                        KEY (`created_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    break;

                default:
                    throw new InvalidArgumentException("Table '$table_name' is not known");
            }

            $this->existing_tables[] = $prefixed_table_name;
        }

        return $prefixed_table_name;
    }
}
