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

use ActiveCollab\Insight\Test\Base\TestCase;
use ActiveCollab\Insight\Test\Fixtures\Account;
use DateTime;
use DateTimeZone;

/**
 * @package ActiveCollab\Resistance\Test
 */
class DatasetTimelineTest extends TestCase
{
    /**
     * @var Account
     */
    private $account;

    /**
     * Set up teast environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->account = new Account($this->redis_client);
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        $this->account = null;

        parent::tearDown();
    }

    /**
     * Test get timeline.
     */
    public function testGetTimeline()
    {
        $gmt = new DateTimeZone('GMT');

        $this->account->setTimelineDataForDate(new DateTime('2015-05-09', $gmt), 5, 4, 3, 2);
        $this->account->setTimelineDataForDate(new DateTime('2015-05-11', $gmt), 8, 0, 2, 0);
        $this->account->setTimelineDataForDate(new DateTime('2015-05-15', $gmt), 24, 2, 15, 3);

        $timeline = $this->account->getTimeline(new DateTime('2015-05-09', $gmt), new DateTime('2015-05-15', $gmt));

        $this->assertCount(7, $timeline);

        $this->assertEquals([5, 4, 3, 2], $timeline['2015-05-09']);
        $this->assertEquals([0, 0, 0, 0], $timeline['2015-05-10']);
        $this->assertEquals([8, 0, 2, 0], $timeline['2015-05-11']);
        $this->assertEquals([0, 0, 0, 0], $timeline['2015-05-12']);
        $this->assertEquals([0, 0, 0, 0], $timeline['2015-05-13']);
        $this->assertEquals([0, 0, 0, 0], $timeline['2015-05-14']);
        $this->assertEquals([24, 2, 15, 3], $timeline['2015-05-15']);
    }

    /**
     * Test if increment methods are working properly.
     */
    public function testIncrements()
    {
        $date = new DateTime('2015-05-09', new DateTimeZone('GMT'));

        $this->account->setTimelineDataForDate($date, 5, 4, 3, 2);

        $this->account->timelineLogAddition($date);
        $this->account->timelineLogUnarchive($date);
        $this->account->timelineLogArchive($date);
        $this->account->timelineLogDeletion($date);

        $timeline = $this->account->getTimeline($date, $date);

        $this->assertCount(1, $timeline);

        $this->assertEquals([6, 5, 4, 3], $timeline['2015-05-09']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testFromLargerThanToException()
    {
        $this->account->getTimeline(new DateTime('2015-05-09'), new DateTime('2013-10-02'));
    }
}
