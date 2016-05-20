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

namespace ActiveCollab\Insight\Metric;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateValueInterface;
use Exception;
use InvalidArgumentException;
use DatePeriod;
use DateInterval;
use DateTime;

/**
 * @package ActiveCollab\Insight\Metric
 */
class ConversionRates extends Metric implements ConversionRatesInterface
{
    /**
     * Return visits to trial conversion rate for the given day.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getDailyTrialConversionRate(DateValueInterface $day): float
    {
        return $this->getDailyConversionRate($day, 'to_trial_rate');
    }

    /**
     * Return visits to paid conversion rate for the given day.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getDailyPaidConversionRate(DateValueInterface $day): float
    {
        return $this->getDailyConversionRate($day, 'to_paid_rate');
    }

    /**
     * Return visits to trial conversion rate for the given month.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getMonthlyTrialConversionRate(DateValueInterface $day): float
    {
        return $this->getMonthlyConversionRate($day, 'to_trial_rate');
    }

    /**
     * Return visits to paid conversion rate for the given month.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getMonthlyPaidConversionRate(DateValueInterface $day): float
    {
        return $this->getMonthlyConversionRate($day, 'to_paid_rate');
    }

    /**
     * Set daily stats.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $visits
     * @param  int                      $trials
     * @param  int                      $conversions
     * @return ConversionRatesInterface
     */
    public function setDailyStats(DateValueInterface $day, int $visits, int $trials, int $conversions): ConversionRatesInterface
    {
        return $this->doSetDailyStats($day, ['visits' => $visits, 'trials' => $trials, 'conversions' => $conversions]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDailyVisits(DateValueInterface $day, $num = 1): int
    {
        return $this->getDailyStat($day, 'visits');
    }

    /**
     * {@inheritdoc}
     */
    public function &setVisits(DateValueInterface $day, int $value = 1): ConversionRatesInterface
    {
        return $this->doSetDailyStats($day, ['visits' => $value]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDailyTrials(DateValueInterface $day, $num = 1): int
    {
        return $this->getDailyStat($day, 'trials');
    }

    /**
     * {@inheritdoc}
     */
    public function &setTrials(DateValueInterface $day, int $value = 1): ConversionRatesInterface
    {
        return $this->doSetDailyStats($day, ['trials' => $value]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDailyConversions(DateValueInterface $day, $num = 1): int
    {
        return $this->getDailyStat($day, 'conversions');
    }
    /**
     * {@inheritdoc}
     */
    public function &setConversions(DateValueInterface $day, int $value = 1): ConversionRatesInterface
    {
        return $this->doSetDailyStats($day, ['conversions' => $value]);
    }

    /**
     * {@inheritdoc}
     */
    public function &addVisits(DateValueInterface $day, int $num = 1): ConversionRatesInterface
    {
        return $this->doIncDailyStat($day, 'visits', $num);
    }

    /**
     * {@inheritdoc}
     */
    public function &addTrials(DateValueInterface $day, int $num = 1): ConversionRatesInterface
    {
        return $this->doIncDailyStat($day, 'trials', $num);
    }

    /**
     * {@inheritdoc}
     */
    public function &addConversions(DateValueInterface $day, int $num = 1): ConversionRatesInterface
    {
        return $this->doIncDailyStat($day, 'conversions', $num);
    }

    /**
     * {@inheritdoc}
     */
    public function getDailyTimeline(DateValueInterface $from, DateValueInterface $to): array
    {
        $existing_data = [];

        if ($rows = $this->connection->execute("SELECT * FROM `{$this->insight->getTableName('daily_conversions')}` WHERE `day` BETWEEN ? AND ?", $from, $to)) {
            $rows->setValueCaster(new ValueCaster([
                'visits' => ValueCasterInterface::CAST_INT,
                'trials' => ValueCasterInterface::CAST_INT,
                'conversions' => ValueCasterInterface::CAST_INT,
                'to_trial_rate' => ValueCasterInterface::CAST_FLOAT,
                'to_paid_rate' => ValueCasterInterface::CAST_FLOAT,
            ]));

            foreach ($rows as $row) {
                $existing_data[$row['day']] = [
                    'visits' => $row['visits'],
                    'trials' => $row['trials'],
                    'conversions' => $row['conversions'],
                    'trial_conversion_rate' => $row['to_trial_rate'],
                    'paid_conversion_rate' => $row['to_paid_rate'],
                ];
            }
        }

        $result = [];

        /** @var DateTime $day */
        /** @var DateValue $to */
        foreach (new DatePeriod($from, DateInterval::createFromDateString('1 day'), $to->addDay()) as $day) {
            $key = $day->format('Y-m-d');

            if (empty($existing_data[$key])) {
                $result[$key] = [
                    'visits' => 0,
                    'trials' => 0,
                    'conversions' => 0,
                    'trial_conversion_rate' => 0.0,
                    'paid_conversion_rate' => 0.0,
                ];
            } else {
                $result[$key] = $existing_data[$key];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyTimeline(DateValueInterface $from, DateValueInterface $to): array
    {
        $existing_data = [];

        if ($rows = $this->connection->execute("SELECT * FROM `{$this->insight->getTableName('monthly_conversions')}` WHERE `day` BETWEEN ? AND ?", $from, $to)) {
            $rows->setValueCaster(new ValueCaster([
                'day' => ValueCasterInterface::CAST_DATE,
                'visits' => ValueCasterInterface::CAST_INT,
                'trials' => ValueCasterInterface::CAST_INT,
                'conversions' => ValueCasterInterface::CAST_INT,
                'to_trial_rate' => ValueCasterInterface::CAST_FLOAT,
                'to_paid_rate' => ValueCasterInterface::CAST_FLOAT,
            ]));

            foreach ($rows as $row) {
                /** @var DateValue $day */
                $day = $row['day'];

                $existing_data[$day->format('Y-m')] = [
                    'visits' => $row['visits'],
                    'trials' => $row['trials'],
                    'conversions' => $row['conversions'],
                    'trial_conversion_rate' => $row['to_trial_rate'],
                    'paid_conversion_rate' => $row['to_paid_rate'],
                ];
            }
        }

        $result = [];

        /** @var DateValue $from_month_start */
        $from_month_start = clone $from;
        $from_month_start->startOfMonth();

        /** @var DateValue $to_month_end */
        $to_month_end = clone $to;
        $to_month_end->endOfMonth();

        /** @var DateTime $day */
        /** @var DateValue $to */
        foreach (new DatePeriod($from, DateInterval::createFromDateString('1 month'), $to->addDay()) as $day) {
            $key = $day->format('Y-m');

            if (empty($existing_data[$key])) {
                $result[$key] = [
                    'visits' => 0,
                    'trials' => 0,
                    'conversions' => 0,
                    'trial_conversion_rate' => 0.0,
                    'paid_conversion_rate' => 0.0,
                ];
            } else {
                $result[$key] = $existing_data[$key];
            }
        }

        return $result;
    }

    private function getDailyStat(DateValueInterface $day, string $stat, int $default = 0): int
    {
        if ($value = $this->connection->executeFirstCell("SELECT `$stat` FROM `{$this->insight->getTableName('daily_conversions')}` WHERE `day` = ?", $day)) {
            return (integer) $value;
        }

        return $default;
    }

    /**
     * Return an individual daily conversion rate.
     *
     * @param  DateValueInterface $day
     * @param  string             $conversion_rate
     * @param  float              $default
     * @return float
     */
    private function getDailyConversionRate(DateValueInterface $day, string $conversion_rate, float $default = 0.0): float
    {
        if ($value = $this->connection->executeFirstCell("SELECT `$conversion_rate` FROM `{$this->insight->getTableName('daily_conversions')}` WHERE `day` = ?", $day)) {
            return (float) number_format(round($value, 3), 3, '.', '');
        }

        return $default;
    }

    /**
     * Set daily statistics.
     *
     * @param  DateValueInterface       $day
     * @param  array                    $stats
     * @return ConversionRatesInterface
     * @throws Exception
     */
    private function &doSetDailyStats(DateValueInterface $day, array $stats): ConversionRatesInterface
    {
        if (empty($stats)) {
            throw new InvalidArgumentException('At least one stat needs to be set, empty array given');
        }

        foreach ($stats as $stat => $value) {
            if ($value < 0) {
                throw new InvalidArgumentException("Stat '$stat' can't be a negative number");
            }
        }

        $daily_conversions_table = $this->insight->getTableName('daily_conversions');

        $day_conditions = $this->connection->prepareConditions(['`day` = ?', $day]);

        try {
            $this->connection->beginWork();

            if ($this->connection->count($daily_conversions_table, $day_conditions)) {
                $this->connection->update($daily_conversions_table, $stats, $day_conditions);
            } else {
                $this->connection->insert($daily_conversions_table, array_merge(['day' => $day], $stats));
            }

            $this->refreshMonthlyStats($day);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * Increment a daily stat value.
     *
     * @param  DateValueInterface       $day
     * @param  string                   $stat
     * @param  int                      $inc_by
     * @return ConversionRatesInterface
     * @throws Exception
     */
    private function &doIncDailyStat(DateValueInterface $day, string $stat, int $inc_by = 1): ConversionRatesInterface
    {
        if ($inc_by < 0) {
            throw new InvalidArgumentException("Stat '$stat' can't be incremented with a negative number");
        }

        $daily_conversions_table = $this->insight->getTableName('daily_conversions');

        $day_conditions = $this->connection->prepareConditions(['`day` = ?', $day]);

        try {
            $this->connection->beginWork();

            if ($this->connection->count($daily_conversions_table, $day_conditions)) {
                $this->connection->execute("UPDATE `$daily_conversions_table` SET `$stat` = `$stat` + ? WHERE `day` = ?", $inc_by, $day);
            } else {
                $this->doSetDailyStats($day, [$stat => $inc_by]);
            }

            $this->refreshMonthlyStats($day);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyVisits(DateValueInterface $reference_day): int
    {
        return $this->getMonthlyStat($reference_day, 'visits');
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyTrials(DateValueInterface $reference_day): int
    {
        return $this->getMonthlyStat($reference_day, 'trials');
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyConversions(DateValueInterface $reference_day): int
    {
        return $this->getMonthlyStat($reference_day, 'conversions');
    }

    /**
     * Refresh monthly stats based on daily stats.
     *
     * @param DateValueInterface $day
     */
    private function refreshMonthlyStats(DateValueInterface $day)
    {
        /** @var DateValue $month_start */
        $month_start = clone $day;
        $month_start->startOfMonth();

        /** @var DateValue $month_end */
        $month_end = clone $day;
        $month_end->endOfMonth();

        $values = $this->connection->executeFirstRow("SELECT SUM(`visits`) AS 'visits', SUM(`trials`) AS 'trials', SUM(`conversions`) AS 'conversions' FROM {$this->insight->getTableName('daily_conversions')} WHERE `day` BETWEEN ? AND ?", $month_start, $month_end);

        $this->connection->insert($this->insight->getTableName('monthly_conversions'), [
            'day' => $month_start,
            'visits' => $values['visits'],
            'trials' => $values['trials'],
            'conversions' => $values['conversions'],
        ], ConnectionInterface::REPLACE);
    }

    /**
     * Return value of a monthly statistic.
     *
     * @param  DateValueInterface $day
     * @param  string             $stat
     * @param  int                $default
     * @return int
     */
    private function getMonthlyStat(DateValueInterface $day, string $stat, int $default = 0): int
    {
        /** @var DateValue $month_start */
        $month_start = clone $day;
        if ($month_start->day != 1) {
            $month_start->startOfMonth();
        }

        if ($value = $this->connection->executeFirstCell("SELECT `$stat` FROM `{$this->insight->getTableName('monthly_conversions')}` WHERE `day` = ?", $month_start)) {
            return (integer) $value;
        }

        return $default;
    }

    /**
     * Return an individual daily conversion rate.
     *
     * @param  DateValueInterface $day
     * @param  string             $conversion_rate
     * @param  float              $default
     * @return float
     */
    private function getMonthlyConversionRate(DateValueInterface $day, string $conversion_rate, float $default = 0.0): float
    {
        /** @var DateValue $month_start */
        $month_start = clone $day;
        if ($month_start->day != 1) {
            $month_start->startOfMonth();
        }

        if ($value = $this->connection->executeFirstCell("SELECT `$conversion_rate` FROM `{$this->insight->getTableName('monthly_conversions')}` WHERE `day` = ?", $month_start)) {
            return (float) number_format(round($value, 3), 3, '.', '');
        }

        return $default;
    }
}
