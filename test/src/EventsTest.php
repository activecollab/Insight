<?php

/*
 * This file is part of the Active Collab Promises.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test;

use ActiveCollab\Insight\Utilities\Timestamp;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class EventsTest extends TestCase
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
     * Test if events are properly counted.
     */
    public function testCount()
    {
        $this->account->logEvent('Project Created');
        $this->account->logEvent('Task Created');
        $this->account->logEvent('User Created');

        $this->assertEquals(3, $this->account->countEvents());
    }

    /**
     * Test if events iterator is working propertly.
     */
    public function testIteractor()
    {
        $current_timestamp = Timestamp::getCurrentTimestamp();

        for ($i = 1; $i <= 11; ++$i) {
            $current_timestamp = Timestamp::lock($current_timestamp + 1);

            $this->account->logEvent("Event {$i}");
        }

        $this->assertEquals(11, $this->account->countEvents());

        $last_5 = [];

        $this->account->forEachEvent(function ($event, $iteration) use (&$current_timestamp, &$last_5) {
        $this->assertEquals($current_timestamp--, $event['timestamp']);

        $last_5[] = $event['event'];

        return $iteration === 5 ? false : null;
      });

        $this->assertCount(5, $last_5);
        $this->assertEquals('Event 11', $last_5[0]);
        $this->assertEquals('Event 7', $last_5[4]);
    }

    /**
     * Test events pagination.
     */
    public function testPagination()
    {
        $current_timestamp = Timestamp::getCurrentTimestamp();

        for ($i = 1; $i <= 11; ++$i) {
            $current_timestamp = Timestamp::lock($current_timestamp + 1);

            $this->account->logEvent("Event {$i}");
        }

        $page_1 = $this->account->getEvents(1, 5);

        $this->assertCount(5, $page_1);

        $this->assertEquals('Event 11', $page_1[0]['event']);
        $this->assertEquals('Event 10', $page_1[1]['event']);
        $this->assertEquals('Event 9', $page_1[2]['event']);
        $this->assertEquals('Event 8', $page_1[3]['event']);
        $this->assertEquals('Event 7', $page_1[4]['event']);

        $page_2 = $this->account->getEvents(2, 5);

        $this->assertCount(5, $page_2);

        $this->assertEquals('Event 6', $page_2[0]['event']);
        $this->assertEquals('Event 5', $page_2[1]['event']);
        $this->assertEquals('Event 4', $page_2[2]['event']);
        $this->assertEquals('Event 3', $page_2[3]['event']);
        $this->assertEquals('Event 2', $page_2[4]['event']);

        $page_3 = $this->account->getEvents(3, 5);

        $this->assertCount(1, $page_3);

        $this->assertEquals('Event 1', $page_3[0]['event']);
    }
  }
