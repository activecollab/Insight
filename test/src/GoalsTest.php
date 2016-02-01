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

use DateTime;
  use DateTimeZone;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class GoalsTest extends TestCase
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
     * Test validate property name.
     */
    public function testValidateGoalName()
    {
        $this->assertTrue($this->account->isValidGoalName('valid_property_name'));
        $this->assertTrue($this->account->isValidGoalName('ValidPropertyName123'));
        $this->assertTrue($this->account->isValidGoalName('Valid Property Name'));
        $this->assertFalse($this->account->isValidGoalName('something:redis:something'));
        $this->assertFalse($this->account->isValidGoalName('InvalidChar*'));
        $this->assertFalse($this->account->isValidGoalName('  '));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnInvalidPropetyName()
    {
        $this->account->setGoalAsReached('Invalid:');
    }

    /**
     * Test empty array when there are no goals reached.
     */
    public function testNoGoalsReached()
    {
        $this->assertEquals([], $this->account->getGoalsReached());
    }

    /**
     * Test set goals as reached.
     */
    public function testSetGoalAsReached()
    {
        $this->account->setGoalAsReached('C');
        $this->account->setGoalAsReached('B');
        $this->account->setGoalAsReached('D');
        $this->account->setGoalAsReached('A');

        $this->assertEquals(['A', 'B', 'C', 'D'], $this->account->getGoalsReached());
    }

    /**
     * Test if setting goal as reached for the second time does not change the original timestamp.
     */
    public function testSetGoalAsReachedDoesNotChangeOldTimestamp()
    {
        $this->account->setGoalAsReached('C', new DateTime('2015-05-01', new DateTimeZone('GMT')));
        $this->account->setGoalAsReached('C', new DateTime('2015-05-03', new DateTimeZone('GMT')));

        $this->assertEquals('2015-05-01', $this->account->getGoalsHistory()['C']->format('Y-m-d'));
    }

    /**
     * Test goals history.
     */
    public function testGoalsHistory()
    {
        $gmt = new DateTimeZone('GMT');

        $this->account->setGoalAsReached('C', new DateTime('2015-05-01', $gmt));
        $this->account->setGoalAsReached('B', new DateTime('2015-05-03', $gmt));
        $this->account->setGoalAsReached('D', new DateTime('2015-05-05', $gmt));
        $this->account->setGoalAsReached('A', new DateTime('2015-05-09', $gmt));

      /** @var DateTime[] $history */
      $history = $this->account->getGoalsHistory();

        $this->assertEquals(['C', 'B', 'D', 'A'], array_keys($history));

        $this->assertEquals('2015-05-01', $history['C']->format('Y-m-d'));
        $this->assertEquals('2015-05-03', $history['B']->format('Y-m-d'));
        $this->assertEquals('2015-05-05', $history['D']->format('Y-m-d'));
        $this->assertEquals('2015-05-09', $history['A']->format('Y-m-d'));
    }
  }
