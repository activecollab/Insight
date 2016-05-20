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

use ActiveCollab\DateValue\DateValueInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
interface ConversionRatesInterface extends MetricInterface
{
    /**
     * Return visits to trial conversion rate for the given day.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getDailyTrialConversionRate(DateValueInterface $day): float;

    /**
     * Return visits to paid conversion rate for the given day.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getDailyPaidConversionRate(DateValueInterface $day): float;

    /**
     * Return visits to trial conversion rate for the given month.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getMonthlyTrialConversionRate(DateValueInterface $day): float;

    /**
     * Return visits to paid conversion rate for the given month.
     *
     * @param  DateValueInterface $day
     * @return float
     */
    public function getMonthlyPaidConversionRate(DateValueInterface $day): float;

    /**
     * Set daily stats.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $visits
     * @param  int                      $trials
     * @param  int                      $conversions
     * @return ConversionRatesInterface
     */
    public function setDailyStats(DateValueInterface $day, int $visits, int $trials, int $conversions): ConversionRatesInterface;

    /**
     * Return number of recorded visits for the given day.
     *
     * @param  DateValueInterface $day
     * @return int
     */
    public function getDailyVisits(DateValueInterface $day): int;

    /**
     * Set number of visits for the given day.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $value
     * @return ConversionRatesInterface
     */
    public function &setVisits(DateValueInterface $day, int $value = 1): ConversionRatesInterface;

    /**
     * Return number of trials visits for the given day.
     *
     * @param  DateValueInterface $day
     * @return int
     */
    public function getDailyTrials(DateValueInterface $day): int;

    /**
     * Set number of trials for the given day.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $value
     * @return ConversionRatesInterface
     */
    public function &setTrials(DateValueInterface $day, int $value = 1): ConversionRatesInterface;

    /**
     * Return number of recorded conversions for the given day.
     *
     * @param  DateValueInterface $day
     * @return int
     */
    public function getDailyConversions(DateValueInterface $day): int;

    /**
     * Set number of conversions for the given day.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $value
     * @return ConversionRatesInterface
     */
    public function &setConversions(DateValueInterface $day, int $value = 1): ConversionRatesInterface;

    /**
     * Add $num of visits to the given day.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $num
     * @return ConversionRatesInterface
     */
    public function &addVisits(DateValueInterface $day, int $num = 1): ConversionRatesInterface;

    /**
     * Add $num of trials to the given day.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $num
     * @return ConversionRatesInterface
     */
    public function &addTrials(DateValueInterface $day, int $num = 1): ConversionRatesInterface;

    /**
     * Add $num of conversions to the given day.
     *
     * @param  DateValueInterface       $day
     * @param  int                      $num
     * @return ConversionRatesInterface
     */
    public function &addConversions(DateValueInterface $day, int $num = 1): ConversionRatesInterface;

    /**
     * Return daily conversion data.
     *
     * @param  DateValueInterface $from
     * @param  DateValueInterface $to
     * @return array
     */
    public function getDailyTimeline(DateValueInterface $from, DateValueInterface $to): array;

    /**
     * Return number of visits for the given month.
     *
     * @param  DateValueInterface $reference_day
     * @return int
     */
    public function getMonthlyVisits(DateValueInterface $reference_day): int;

    /**
     * Return number of trials for the given month.
     *
     * @param  DateValueInterface $reference_day
     * @return int
     */
    public function getMonthlyTrials(DateValueInterface $reference_day): int;

    /**
     * Return number of conversion for the given month.
     *
     * @param  DateValueInterface $reference_day
     * @return int
     */
    public function getMonthlyConversions(DateValueInterface $reference_day): int;

    /**
     * Return monthly conversion data.
     *
     * @param  DateValueInterface $from
     * @param  DateValueInterface $to
     * @return array
     */
    public function getMonthlyTimeline(DateValueInterface $from, DateValueInterface $to): array;
}
