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
use ActiveCollab\Insight\Metric\Accounts;
use ActiveCollab\Insight\Metric\MetricInterface;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @property \ActiveCollab\Insight\Metric\AccountsInterface             $accounts
 * @property \ActiveCollab\Insight\Metric\ConversionRatesInterface      $conversion_rates
 * @property \ActiveCollab\Insight\Metric\DailyAccountsHistoryInterface $daily_accounts_history
 * @property \ActiveCollab\Insight\Metric\EventsInterface               $events
 * @property \ActiveCollab\Insight\Metric\MrrInterface                  $mrr
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
                case 'daily_conversions':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `day` date NOT NULL,
                        `visits` int unsigned NOT NULL DEFAULT '0',
                        `trials` int unsigned NOT NULL DEFAULT '0',
                        `conversions` int unsigned NOT NULL DEFAULT '0',
                        `to_trial_rate` DECIMAL(6,3) NOT NULL DEFAULT '0',
                        `to_paid_rate` DECIMAL(6,3) NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        UNIQUE (`day`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", Accounts::CANCELATION_REASONS);

                    $this->createConversionRateTriggers($table_name, $prefixed_table_name);

                    break;
                case 'monthly_conversions':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `day` date NOT NULL COMMENT 'First day of the month!',
                        `visits` int unsigned NOT NULL DEFAULT '0',
                        `trials` int unsigned NOT NULL DEFAULT '0',
                        `conversions` int unsigned NOT NULL DEFAULT '0',
                        `to_trial_rate` DECIMAL(6,3),
                        `to_paid_rate` DECIMAL(6,3),
                        PRIMARY KEY (`id`),
                        UNIQUE (`day`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", Accounts::CANCELATION_REASONS);

                    $this->createConversionRateTriggers($table_name, $prefixed_table_name);

                    break;
                case 'accounts':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL,
                        `status` ENUM('trial', 'free', 'paid') NOT NULL,
                        `created_at` DATETIME NOT NULL,
                        `cohort_month` TINYINT unsigned NOT NULL,
                        `cohort_year` SMALLINT unsigned NOT NULL,
                        `canceled_at` DATETIME NULL,
                        `cancelation_reason` ENUM ?,
                        `mrr_value` DECIMAL(13,3) NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY (`status`),
                        KEY (`created_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", Accounts::CANCELATION_REASONS);

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_cohort`');
                    $this->connection->execute("CREATE TRIGGER `account_cohort` BEFORE INSERT ON `$prefixed_table_name` FOR EACH ROW
                        BEGIN
                            SET NEW.cohort_month = MONTH(NEW.created_at);
                            SET NEW.cohort_year = YEAR(NEW.created_at);
                        END");

                    break;
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
                        `period_changes` int unsigned NOT NULL DEFAULT '0',
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
                        `mrr_value` DECIMAL(13,3) NOT NULL DEFAULT '0',
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

    /**
     * @param string $table_name
     * @param string $prefixed_table_name
     */
    private function createConversionRateTriggers(string $table_name, string $prefixed_table_name)
    {
        if ($table_name == 'daily_conversions') {
            $insert_trigger = 'daily_trial_conversion_rate_on_insert';
            $update_trigger = 'daily_trial_conversion_rate_on_update';
        } else {
            $insert_trigger = 'monthly_trial_conversion_rate_on_insert';
            $update_trigger = 'monthly_trial_conversion_rate_on_update';
        }

        $this->connection->execute("DROP TRIGGER IF EXISTS `$insert_trigger`");
        $this->connection->execute("CREATE TRIGGER `$insert_trigger` BEFORE INSERT ON `$prefixed_table_name` FOR EACH ROW
            BEGIN
                IF NEW.visits > '0' AND NEW.trials > '0' THEN
                    SET NEW.to_trial_rate = NEW.trials / NEW.visits * 100;
                ELSE
                    SET NEW.to_trial_rate = '0';
                END IF;

                IF NEW.visits > '0' AND NEW.conversions > '0' THEN
                    SET NEW.to_paid_rate = NEW.conversions / NEW.visits * 100;
                ELSE
                    SET NEW.to_paid_rate = '0';
                END IF;
            END");

        $this->connection->execute("DROP TRIGGER IF EXISTS `$update_trigger`");
        $this->connection->execute("CREATE TRIGGER `$update_trigger` BEFORE UPDATE ON `$prefixed_table_name` FOR EACH ROW
            BEGIN
                IF NEW.visits != OLD.visits OR NEW.trials != OLD.trials OR NEW.conversions != OLD.conversions THEN
                    IF NEW.visits > '0' AND NEW.trials > '0' THEN
                        SET NEW.to_trial_rate = NEW.trials / NEW.visits * 100;
                    ELSE
                        SET NEW.to_trial_rate = '0';
                    END IF;

                    IF NEW.visits > '0' AND NEW.conversions > '0' THEN
                        SET NEW.to_paid_rate = NEW.conversions / NEW.visits * 100;
                    ELSE
                        SET NEW.to_paid_rate = '0';
                    END IF;
                END IF;
            END");
    }
}
