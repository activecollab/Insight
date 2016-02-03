<?php

namespace ActiveCollab\Insight\Test;

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
}