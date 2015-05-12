<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Utilities\Timestamp;

  /**
   * @package ActiveCollab\Insight\Test
   */
  class TimestampLockingTest extends TestCase
  {
    /**
     * Test if current timestamp is locked by default
     */
    public function testTimestampLockedInTests()
    {
      $this->assertTrue(Timestamp::isLocked());
    }

    /**
     * Test if timestamp can be locked
     */
    public function testTimestampLocking()
    {
      Timestamp::lock(1380712680);

      $this->assertEquals(1380712680, Timestamp::now()->getTimestamp());
    }

    /**
     * Test if timestamp can be unlocked
     */
    public function testTimestampUnlocking()
    {
      Timestamp::lock(1380712680);

      $this->assertEquals(1380712680, Timestamp::now()->getTimestamp());

      Timestamp::unlock();
      $this->assertFalse(Timestamp::isLocked());

      $this->assertEquals(time(), Timestamp::now()->getTimestamp());
    }
  }