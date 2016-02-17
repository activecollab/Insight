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

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\Insight\Test\Base\InsightTestCase;

/**
 * @package ActiveCollab\Insight\Test
 */
class ConversionRatesTest extends InsightTestCase
{
    /**
     * Test if set daily stats properly inserts values.
     */
    public function testSetDailyStatsInsertsValuesForNewDay()
    {
        $day = new DateValue('2016-02-17');

        $this->insight->conversion_rates->setDailyStats($day, 2000, 200, 20);

        $this->assertEquals(2000, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(200, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(20, $this->insight->conversion_rates->getDailyConversions($day));
    }

    /**
     * Test if set daily stats properly updates values.
     */
    public function testSetDailyStatsUpdatesValueForExistingDay()
    {
        $day = new DateValue('2016-02-17');

        $this->insight->conversion_rates->setDailyStats($day, 2000, 200, 20);

        $this->assertEquals(2000, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(200, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(20, $this->insight->conversion_rates->getDailyConversions($day));

        $this->insight->conversion_rates->setDailyStats($day, 4000, 300, 20);

        $this->assertEquals(4000, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(300, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(20, $this->insight->conversion_rates->getDailyConversions($day));
    }

    /**
     * Test if set daily stat inserts a new day record.
     */
    public function testSetDailyStatInsertsValueForNewDay()
    {
        $day = new DateValue('2016-02-17');

        $this->insight->conversion_rates->setVisits($day, 2000);

        $this->assertEquals(2000, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(0, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(0, $this->insight->conversion_rates->getDailyConversions($day));
    }

    /**
     * Test if set daily stat updates an existing day record.
     */
    public function testSetDailyStatUpdatesValuesForExistingDay()
    {
        $day = new DateValue('2016-02-17');

        $this->insight->conversion_rates->setDailyStats($day, 2000, 200, 20);

        $this->assertEquals(2000, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(200, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(20, $this->insight->conversion_rates->getDailyConversions($day));

        $this->insight->conversion_rates->setVisits($day, 4000);

        $this->assertEquals(4000, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(200, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(20, $this->insight->conversion_rates->getDailyConversions($day));
    }

    /**
     * Test if daily stats also refresh monthly stats.
     */
    public function testSetDailyAlsoSetsMonthlyStats()
    {
        $this->assertEquals(0, $this->connection->count($this->insight->getTableName('monthly_conversions')));

        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-01-31'), 2000, 200, 20);
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-02-14'), 3000, 300, 40);
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-02-28'), 4000, 400, 30);

        $this->assertEquals(2, $this->connection->count($this->insight->getTableName('monthly_conversions')));

        $this->assertEquals(2000, $this->insight->conversion_rates->getMonthlyVisits(new DateValue('2016-01-12')));
        $this->assertEquals(200, $this->insight->conversion_rates->getMonthlyTrials(new DateValue('2016-01-12')));
        $this->assertEquals(20, $this->insight->conversion_rates->getMonthlyConversions(new DateValue('2016-01-12')));

        $this->assertEquals(7000, $this->insight->conversion_rates->getMonthlyVisits(new DateValue('2016-02-12')));
        $this->assertEquals(700, $this->insight->conversion_rates->getMonthlyTrials(new DateValue('2016-02-12')));
        $this->assertEquals(70, $this->insight->conversion_rates->getMonthlyConversions(new DateValue('2016-02-12')));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stat 'visits' can't be a negative number
     */
    public function testNumberOfVisitsCantBeNegative()
    {
        $this->insight->conversion_rates->setVisits(new DateValue('2016-01-31'), -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stat 'trials' can't be a negative number
     */
    public function testNumberOfTrialsCantBeNegative()
    {
        $this->insight->conversion_rates->setTrials(new DateValue('2016-01-31'), -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stat 'conversions' can't be a negative number
     */
    public function testNumberOfConversionsCantBeNegative()
    {
        $this->insight->conversion_rates->setConversions(new DateValue('2016-01-31'), -1);
    }

    /**
     * Test if add daily stat inserts a day record if missing.
     */
    public function testAddDailyStatInsertsValueForNewDay()
    {
        $day = new DateValue('2016-01-31');

        $this->insight->conversion_rates->addVisits($day);

        $this->assertEquals(1, $this->insight->conversion_rates->getDailyVisits($day));
    }

    /**
     * Test if add daily stat updates a day record if present.
     */
    public function testAddDailyStatUpdatesValueForExistingDay()
    {
        $day = new DateValue('2016-01-31');
        $this->insight->conversion_rates->setVisits($day, 12);
        $this->insight->conversion_rates->addVisits($day);

        $this->assertEquals(13, $this->insight->conversion_rates->getDailyVisits($day));
    }

    /**
     * Test if add daily stat can use an increment different than 1.
     */
    public function testAddDailyStatCanBeValueDifferentThanOne()
    {
        $day = new DateValue('2016-01-31');
        $this->insight->conversion_rates->setVisits($day, 12);
        $this->insight->conversion_rates->addVisits($day, 18);

        $this->assertEquals(30, $this->insight->conversion_rates->getDailyVisits($day));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stat 'visits' can't be incremented with a negative number
     */
    public function testAddDailyVisitsStatCanUseNegativeIncrement()
    {
        $this->insight->conversion_rates->addVisits(new DateValue('2016-01-31'), -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stat 'trials' can't be incremented with a negative number
     */
    public function testAddDailyTrialsStatCanUseNegativeIncrement()
    {
        $this->insight->conversion_rates->addTrials(new DateValue('2016-01-31'), -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stat 'conversions' can't be incremented with a negative number
     */
    public function testAddDailyConversionsStatCanUseNegativeIncrement()
    {
        $this->insight->conversion_rates->addConversions(new DateValue('2016-01-31'), -1);
    }

    /**
     * Test daily conversion rates are calculated when values are added to the database.
     */
    public function testDailyConversionRates()
    {
        $day = new DateValue('2016-01-31');

        $this->insight->conversion_rates->setDailyStats($day, 2500, 48, 14);

        $this->assertSame(1.92, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.56, $this->insight->conversion_rates->getDailyPaidConversionRate($day));
    }

    /**
     * Test daily conversion rates are recalculated when values are update.
     */
    public function testDailyStatChangesRefreshConversionRates()
    {
        $day = new DateValue('2016-01-31');

        $this->insight->conversion_rates->setDailyStats($day, 2500, 48, 14);

        $this->assertSame(1.92, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.56, $this->insight->conversion_rates->getDailyPaidConversionRate($day));

        $this->insight->conversion_rates->addVisits($day, 869);
        $this->insight->conversion_rates->addTrials($day, 123);
        $this->insight->conversion_rates->addConversions($day, 21);

        $this->assertEquals(3369, $this->insight->conversion_rates->getDailyVisits($day));
        $this->assertEquals(171, $this->insight->conversion_rates->getDailyTrials($day));
        $this->assertEquals(35, $this->insight->conversion_rates->getDailyConversions($day));

        $this->assertSame(5.076, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(1.039, $this->insight->conversion_rates->getDailyPaidConversionRate($day));
    }

    /**
     * Test daily conversio rate properly returns 0 if there was no conversion.
     */
    public function testDailyConversionRatesDivisionByZero()
    {
        $day = new DateValue('2016-01-31');

        $this->insight->conversion_rates->setDailyStats($day, 0, 0, 0);

        $this->assertSame(0.0, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.0, $this->insight->conversion_rates->getDailyPaidConversionRate($day));

        $this->insight->conversion_rates->setDailyStats($day, 14, 0, 0);

        $this->assertSame(0.0, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.0, $this->insight->conversion_rates->getDailyPaidConversionRate($day));

        $this->insight->conversion_rates->setDailyStats($day, 0, 12, 14);

        $this->assertSame(0.0, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.0, $this->insight->conversion_rates->getDailyPaidConversionRate($day));
    }

    /**
     * Test if we can store 100% conversion rate.
     */
    public function testHundredPercentConversion()
    {
        $day = new DateValue('2016-01-31');

        $this->insight->conversion_rates->setDailyStats($day, 1000, 1000, 1000);

        $this->assertSame(100.0, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(100.0, $this->insight->conversion_rates->getDailyPaidConversionRate($day));
    }

    /**
     * Test daily conversion rates are calculated when values are added to the database.
     */
    public function testMonthlyConversionRatesAreRefreshedWhenDailyStatsAreAdded()
    {
        $january = new DateValue('2016-01-15');

        $day1 = new DateValue('2016-01-05');

        $this->insight->conversion_rates->setDailyStats($day1, 2500, 48, 14);

        $this->assertSame(1.92, $this->insight->conversion_rates->getDailyTrialConversionRate($day1));
        $this->assertSame(0.56, $this->insight->conversion_rates->getDailyPaidConversionRate($day1));

        $this->assertSame(1.92, $this->insight->conversion_rates->getMonthlyTrialConversionRate($january));
        $this->assertSame(0.56, $this->insight->conversion_rates->getMonthlyPaidConversionRate($january));

        $day2 = new DateValue('2016-01-13');

        $this->insight->conversion_rates->setDailyStats($day2, 350, 24, 12);

        $this->assertSame(6.857, $this->insight->conversion_rates->getDailyTrialConversionRate($day2));
        $this->assertSame(3.429, $this->insight->conversion_rates->getDailyPaidConversionRate($day2));

        $this->assertSame(2.526, $this->insight->conversion_rates->getMonthlyTrialConversionRate($january));
        $this->assertSame(0.912, $this->insight->conversion_rates->getMonthlyPaidConversionRate($january));
    }

    /**
     * Test daily conversion rates are calculated when daily stats are updated.
     */
    public function testMonthlyConversionRatesAreRefreshedWhenDailyStatsAreChanged()
    {
        $january = new DateValue('2016-01-15');

        $day = new DateValue('2016-01-05');

        $this->insight->conversion_rates->setDailyStats($day, 2500, 48, 14);

        $this->assertSame(1.92, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.56, $this->insight->conversion_rates->getDailyPaidConversionRate($day));

        $this->assertSame(1.92, $this->insight->conversion_rates->getMonthlyTrialConversionRate($january));
        $this->assertSame(0.56, $this->insight->conversion_rates->getMonthlyPaidConversionRate($january));

        $this->insight->conversion_rates->addVisits($day, 12);
        $this->insight->conversion_rates->addTrials($day, 6);
        $this->insight->conversion_rates->addConversions($day, 2);

        $this->assertSame(2.150, $this->insight->conversion_rates->getDailyTrialConversionRate($day));
        $this->assertSame(0.637, $this->insight->conversion_rates->getDailyPaidConversionRate($day));

        $this->assertSame(2.150, $this->insight->conversion_rates->getMonthlyTrialConversionRate($january));
        $this->assertSame(0.637, $this->insight->conversion_rates->getMonthlyPaidConversionRate($january));
    }

    /**
     * Test if daily conversion timeline is properly set.
     */
    public function testDailyTimeline()
    {
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-01-15'), 2500, 50, 5);
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-01-17'), 3500, 40, 10);
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-01-22'), 4500, 90, 15);

        $timeline = $this->insight->conversion_rates->getDailyTimeline(new DateValue('2016-01-01'), new DateValue('2016-01-31'));

        $this->assertInternalType('array', $timeline);
        $this->assertCount(31, $timeline);

        $this->assertArrayNotHasKey('2015-12-31', $timeline);
        $this->assertArrayHasKey('2016-01-01', $timeline);
        $this->assertArrayHasKey('2016-01-31', $timeline);
        $this->assertArrayNotHasKey('2016-02-01', $timeline);

        foreach ($timeline as $k => $v) {
            if (in_array($k, ['2016-01-15', '2016-01-17', '2016-01-22'])) {
                continue;
            }

            $this->assertArrayHasKey('visits', $v);
            $this->assertArrayHasKey('trials', $v);
            $this->assertArrayHasKey('conversions', $v);
            $this->assertArrayHasKey('trial_conversion_rate', $v);
            $this->assertArrayHasKey('paid_conversion_rate', $v);

            $this->assertEmpty($v['visits']);
            $this->assertEmpty($v['trials']);
            $this->assertEmpty($v['conversions']);
            $this->assertEmpty($v['trial_conversion_rate']);
            $this->assertEmpty($v['paid_conversion_rate']);
        }

        $this->assertEquals(2500, $timeline['2016-01-15']['visits']);
        $this->assertEquals(50, $timeline['2016-01-15']['trials']);
        $this->assertEquals(5, $timeline['2016-01-15']['conversions']);
        $this->assertEquals(2.0, $timeline['2016-01-15']['trial_conversion_rate']);
        $this->assertEquals(0.2, $timeline['2016-01-15']['paid_conversion_rate']);

        $this->assertEquals(3500, $timeline['2016-01-17']['visits']);
        $this->assertEquals(40, $timeline['2016-01-17']['trials']);
        $this->assertEquals(10, $timeline['2016-01-17']['conversions']);
        $this->assertEquals(1.143, $timeline['2016-01-17']['trial_conversion_rate']);
        $this->assertEquals(0.286, $timeline['2016-01-17']['paid_conversion_rate']);

        $this->assertEquals(4500, $timeline['2016-01-22']['visits']);
        $this->assertEquals(90, $timeline['2016-01-22']['trials']);
        $this->assertEquals(15, $timeline['2016-01-22']['conversions']);
        $this->assertEquals(2.0, $timeline['2016-01-22']['trial_conversion_rate']);
        $this->assertEquals(0.333, $timeline['2016-01-22']['paid_conversion_rate']);
    }

    /**
     * Test if monthly conversion timeline is properly set.
     */
    public function testMonthlyTimeline()
    {
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-03-15'), 2500, 50, 5);
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-06-17'), 3500, 40, 10);
        $this->insight->conversion_rates->setDailyStats(new DateValue('2016-08-22'), 4500, 90, 15);

        $timeline = $this->insight->conversion_rates->getMonthlyTimeline(new DateValue('2016-01-15'), new DateValue('2016-12-15'));

        $this->assertInternalType('array', $timeline);
        $this->assertCount(12, $timeline);

        $this->assertArrayNotHasKey('2015-12', $timeline);
        $this->assertArrayHasKey('2016-01', $timeline);
        $this->assertArrayHasKey('2016-12', $timeline);
        $this->assertArrayNotHasKey('2017-01', $timeline);

        foreach ($timeline as $k => $v) {
            if (in_array($k, ['2016-03', '2016-06', '2016-08'])) {
                continue;
            }

            $this->assertArrayHasKey('visits', $v);
            $this->assertArrayHasKey('trials', $v);
            $this->assertArrayHasKey('conversions', $v);
            $this->assertArrayHasKey('trial_conversion_rate', $v);
            $this->assertArrayHasKey('paid_conversion_rate', $v);

            $this->assertEmpty($v['visits']);
            $this->assertEmpty($v['trials']);
            $this->assertEmpty($v['conversions']);
            $this->assertEmpty($v['trial_conversion_rate']);
            $this->assertEmpty($v['paid_conversion_rate']);
        }

        $this->assertEquals(2500, $timeline['2016-03']['visits']);
        $this->assertEquals(50, $timeline['2016-03']['trials']);
        $this->assertEquals(5, $timeline['2016-03']['conversions']);
        $this->assertEquals(2.0, $timeline['2016-03']['trial_conversion_rate']);
        $this->assertEquals(0.2, $timeline['2016-03']['paid_conversion_rate']);

        $this->assertEquals(3500, $timeline['2016-06']['visits']);
        $this->assertEquals(40, $timeline['2016-06']['trials']);
        $this->assertEquals(10, $timeline['2016-06']['conversions']);
        $this->assertEquals(1.143, $timeline['2016-06']['trial_conversion_rate']);
        $this->assertEquals(0.286, $timeline['2016-06']['paid_conversion_rate']);

        $this->assertEquals(4500, $timeline['2016-08']['visits']);
        $this->assertEquals(90, $timeline['2016-08']['trials']);
        $this->assertEquals(15, $timeline['2016-08']['conversions']);
        $this->assertEquals(2.0, $timeline['2016-08']['trial_conversion_rate']);
        $this->assertEquals(0.333, $timeline['2016-08']['paid_conversion_rate']);
    }
}
