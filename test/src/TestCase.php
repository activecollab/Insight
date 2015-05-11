<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Timestamp;

  /**
   * @package ActiveCollab\Insight\Test
   */
  abstract class TestCase extends \PHPUnit_Framework_TestCase
  {
    /**
     * @var int
     */
    protected $current_timestamp;

    /**
     * Switch to test database
     */
    public function setUp()
    {
      $this->current_timestamp = Timestamp::lock();
    }

    /**
     * Tear down test database
     */
    public function tearDown()
    {
      Timestamp::unlock();
      $this->current_timestamp = null;
    }
  }