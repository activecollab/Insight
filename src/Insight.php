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
use ActiveCollab\DateValue\DateValueInterface;
use ActiveCollab\Insight\AccountInsight\AccountInsight;
use ActiveCollab\Insight\Metric\AccountsInterface;
use ActiveCollab\Insight\Metric\MetricInterface;
use Carbon\Carbon;
use Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * @property \ActiveCollab\Insight\Metric\AccountsInterface             $accounts
 * @property \ActiveCollab\Insight\Metric\ChurnInterface                $churn
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
     * @param DateValueInterface|Carbon $day
     */
    public function dailySnapshot(DateValueInterface $day)
    {
        $this->daily_accounts_history->dailySnapshot($day);
    }

    /**
     * @param DateValueInterface|Carbon $day
     */
    public function weeklySnapshot(DateValueInterface $day)
    {
        if ($day->dayOfWeek > 1) {
            throw new InvalidArgumentException('Weekly snapshot can be done on Sundays and Mondays only');
        }
    }

    /**
     * @param DateValueInterface|Carbon $day
     */
    public function monthlySnapshot(DateValueInterface $day)
    {
    }

    /**
     * @param DateValueInterface|Carbon $day
     */
    public function yearlySnapshot(DateValueInterface $day)
    {
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
            $table_set_as_existing = false;

            switch ($table_name) {
                case 'accounts':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL,
                        `status` ENUM ? NOT NULL,
                        `plan` varchar(191) DEFAULT NULL,
                        `billing_period` varchar(191) DEFAULT NULL,
                        `created_at` DATETIME NOT NULL,
                        `cohort_month` TINYINT unsigned NOT NULL,
                        `cohort_year` SMALLINT unsigned NOT NULL,
                        `converted_to_free_at` DATETIME NULL,
                        `converted_to_paid_at` DATETIME NULL,
                        `retired_at` DATETIME NULL,
                        `canceled_at` DATETIME NULL,
                        `had_trial` TINYINT(1) NOT NULL DEFAULT '0',
                        `cancelation_reason` ENUM ?,
                        `mrr_value` DECIMAL(13,3) unsigned NOT NULL DEFAULT '0',
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY (`status`),
                        KEY (`created_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", AccountsInterface::STATUSES, AccountsInterface::CANCELATION_REASONS);

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_cohort`');
                    $this->connection->execute("CREATE TRIGGER `account_cohort` BEFORE INSERT ON `$prefixed_table_name` FOR EACH ROW
                        BEGIN
                            SET NEW.cohort_month = MONTH(NEW.created_at);
                            SET NEW.cohort_year = YEAR(NEW.created_at);
                        END");

                    $table_set_as_existing = $this->setTableAsExisting($prefixed_table_name);

                    $this->getTableName('account_updates');
                    $this->getTableName('account_status_spans');

                    break;
                case 'account_updates':
                    $account_table = $this->getTableName('accounts');

                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `account_id` int unsigned NOT NULL DEFAULT '0',
                        `old_status` ENUM ? DEFAULT NULL,
                        `new_status` ENUM ? DEFAULT NULL,
                        `old_plan` varchar(191) DEFAULT NULL,
                        `new_plan` varchar(191) DEFAULT NULL,
                        `old_billing_period` varchar(191) DEFAULT NULL,
                        `new_billing_period` varchar(191) DEFAULT NULL,
                        `old_mrr_value` DECIMAL(13,3) unsigned DEFAULT NULL,
                        `new_mrr_value` DECIMAL(13,3) unsigned DEFAULT NULL,
                        `created_at` DATETIME NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY (`account_id`),
                        KEY (`created_at`),
                        FOREIGN KEY (`account_id`)
                            REFERENCES `$account_table` (`id`)
                            ON UPDATE CASCADE ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", AccountsInterface::STATUSES, AccountsInterface::STATUSES);

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_updated`');
                    $this->connection->execute("CREATE TRIGGER `account_updated` AFTER UPDATE ON `$account_table` FOR EACH ROW
                        BEGIN
                            IF NEW.status != OLD.status OR NEW.plan != OLD.plan OR NEW.billing_period != OLD.billing_period OR NEW.mrr_value != OLD.mrr_value THEN
                                INSERT INTO `$prefixed_table_name` (`account_id`, `old_status`, `new_status`, `old_plan`, `new_plan`, `old_billing_period`, `new_billing_period`, `old_mrr_value`, `new_mrr_value`, `created_at`) VALUES (
                                    OLD.`id`, 
                                    OLD.`status`, NEW.`status`,  
                                    OLD.`plan`, NEW.`plan`,  
                                    OLD.`billing_period`, NEW.`billing_period`,  
                                    OLD.`mrr_value`, NEW.`mrr_value`,
                                    NEW.`updated_at`
                                );
                            END IF;
                        END");

                    $table_set_as_existing = $this->setTableAsExisting($prefixed_table_name);

                    break;
                case 'account_status_spans':
                    $account_table = $this->getTableName('accounts');

                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `account_id` int unsigned NOT NULL DEFAULT '0',
                        `status` ENUM ? DEFAULT NULL,
                        `started_at` DATETIME NOT NULL,
                        `started_on` DATE NOT NULL,
                        `ended_at` DATETIME DEFAULT NULL,
                        `ended_on` DATE DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY (`started_at`),
                        KEY (`ended_at`),
                        FOREIGN KEY (`account_id`)
                            REFERENCES `$account_table` (`id`)
                            ON UPDATE CASCADE ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", AccountsInterface::STATUSES);

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_span_on_insert`');
                    $this->connection->execute("CREATE TRIGGER `account_span_on_insert` BEFORE INSERT ON `$prefixed_table_name` FOR EACH ROW
                        BEGIN
                            IF NEW.started_at IS NOT NULL THEN
                                SET NEW.started_on = DATE(NEW.started_at);
                            END IF;
                            
                            IF NEW.ended_at IS NOT NULL THEN
                                SET NEW.ended_on = DATE(NEW.ended_at);
                            END IF;
                        END");

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_span_on_update`');
                    $this->connection->execute("CREATE TRIGGER `account_span_on_update` BEFORE UPDATE ON `$prefixed_table_name` FOR EACH ROW
                        BEGIN
                            IF NEW.started_at IS NOT NULL THEN
                                SET NEW.started_on = DATE(NEW.started_at);
                            END IF;
                            
                            IF NEW.ended_at IS NOT NULL THEN
                                SET NEW.ended_on = DATE(NEW.ended_at);
                            END IF;
                        END");

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_insert_create_span`');
                    $this->connection->execute("CREATE TRIGGER `account_insert_create_span` AFTER INSERT ON `$account_table` FOR EACH ROW INSERT INTO `$prefixed_table_name` (`account_id`, `status`, `started_at`) VALUES (NEW.`id`, NEW.`status`, NEW.`updated_at`);");

                    $this->connection->execute('DROP TRIGGER IF EXISTS `account_update_create_span`');
                    $this->connection->execute("CREATE TRIGGER `account_update_create_span` AFTER UPDATE ON `$account_table` FOR EACH ROW
                        BEGIN
                            IF NEW.status != OLD.status THEN
                                UPDATE `$prefixed_table_name` SET `ended_at` = NEW.`updated_at` WHERE `account_id` = NEW.`id` AND `ended_at` IS NULL;
                                INSERT INTO `$prefixed_table_name` (`account_id`, `status`, `started_at`) VALUES (NEW.`id`, NEW.`status`, NEW.`updated_at`);
                            END IF;
                        END");

                    $table_set_as_existing = $this->setTableAsExisting($prefixed_table_name);

                    break;
                case 'monthly_churn':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `day` date NOT NULL,
                        `accounts` int unsigned NOT NULL DEFAULT '0',
                        `mrr` DECIMAL(13,3) unsigned NOT NULL DEFAULT '0',
                        `accounts_lost` int unsigned NOT NULL DEFAULT '0',
                        `mrr_lost` DECIMAL(13,3) unsigned NOT NULL DEFAULT '0',
                        `accounts_churn_rate` DECIMAL(6,3) NOT NULL DEFAULT '0',
                        `mrr_churn_rate` DECIMAL(6,3) NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        UNIQUE (`day`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    break;
                case 'churned_accounts':
                    $account_table = $this->getTableName('accounts');
                    $monthly_churn_table = $this->getTableName('monthly_churn');

                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `snapshot_id` int unsigned NOT NULL DEFAULT '0',
                        `account_id` int unsigned NOT NULL DEFAULT '0',
                        `churned_on` date NOT NULL,
                        `mrr_lost` DECIMAL(13,3) unsigned NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        UNIQUE (`snapshot_id`, `account_id`),
                        KEY (`account_id`),
                        FOREIGN KEY (`snapshot_id`)
                            REFERENCES `$monthly_churn_table` (`id`)
                            ON UPDATE CASCADE ON DELETE RESTRICT,
                        FOREIGN KEY (`account_id`)
                            REFERENCES `$account_table` (`id`)
                            ON UPDATE CASCADE ON DELETE RESTRICT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    break;
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    $this->createConversionRateTriggers($table_name, $prefixed_table_name);

                    break;
                case 'daily_accounts_history':
                    $this->connection->execute("CREATE TABLE IF NOT EXISTS `$prefixed_table_name` (
                        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                        `day` DATE NOT NULL,
                        `started_with_active` int unsigned NOT NULL DEFAULT '0',
                        `started_with_trials` int unsigned NOT NULL DEFAULT '0',
                        `started_with_free` int unsigned NOT NULL DEFAULT '0',
                        `started_with_paid` int unsigned NOT NULL DEFAULT '0',
                        `started_with_retired` int unsigned NOT NULL DEFAULT '0',
                        `started_with_canceled` int unsigned NOT NULL DEFAULT '0',
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

            if (!$table_set_as_existing) {
                $this->setTableAsExisting($prefixed_table_name);
            }
        }

        return $prefixed_table_name;
    }

    /**
     * Set table as existing (after we create it).
     *
     * @param  string $prefixed_table_name
     * @return bool
     */
    private function setTableAsExisting(string $prefixed_table_name): bool
    {
        if (!in_array($prefixed_table_name, $this->existing_tables)) {
            $this->existing_tables[] = $prefixed_table_name;
        }

        return true;
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
