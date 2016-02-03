<?php

namespace ActiveCollab\Insight\Test;

use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\Test\Base\AccountInsightTestCase;

/**
 * @package ActiveCollab\Insight\Test
 */
class AccountEventsTest extends AccountInsightTestCase
{
    /**
     * Test if events are properly counted.
     */
    public function testCount()
    {
        $this->account->events->add('Project Created');
        $this->account->events->add('Task Created');
        $this->account->events->add('User Created');

        $this->insight->account(12345)->events->add('Project Created');

        $this->assertEquals(4, $this->connection->count($this->insight->getTableName('events')));
        $this->assertEquals(3, $this->account->events->count());
    }

    /**
     * Test events pagination.
     */
    public function testPagination()
    {
        for ($i = 1; $i <= 11; ++$i) {
            $this->current_timestamp->addSecond();
            DateTimeValue::setTestNow($this->current_timestamp);

            $this->account->events->add("Event {$i}");
        }

        $page_1 = $this->account->events->get(1, 5);

        $this->assertCount(5, $page_1);

        $this->assertEquals('Event 11', $page_1[0]['name']);
        $this->assertEquals('Event 10', $page_1[1]['name']);
        $this->assertEquals('Event 9', $page_1[2]['name']);
        $this->assertEquals('Event 8', $page_1[3]['name']);
        $this->assertEquals('Event 7', $page_1[4]['name']);

        $page_2 = $this->account->events->get(2, 5);

        $this->assertCount(5, $page_2);

        $this->assertEquals('Event 6', $page_2[0]['name']);
        $this->assertEquals('Event 5', $page_2[1]['name']);
        $this->assertEquals('Event 4', $page_2[2]['name']);
        $this->assertEquals('Event 3', $page_2[3]['name']);
        $this->assertEquals('Event 2', $page_2[4]['name']);

        $page_3 = $this->account->events->get(3, 5);

        $this->assertCount(1, $page_3);

        $this->assertEquals('Event 1', $page_3[0]['name']);
    }

    /**
     * Test all events.
     */
    public function testAll()
    {
        for ($i = 1; $i <= 11; ++$i) {
            $this->current_timestamp->addSecond();
            DateTimeValue::setTestNow($this->current_timestamp);

            $this->account->events->add("Event {$i}");
        }

        $all_events = $this->account->events->all(1, 5);

        $this->assertCount(11, $all_events);

        $this->assertEquals('Event 11', $all_events[0]['name']);
        $this->assertEquals('Event 1', $all_events[10]['name']);
    }
}