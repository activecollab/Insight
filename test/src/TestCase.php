<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Utilities\Timestamp;
  use Predis\Client;

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
     * @var Client
     */
    protected $redis_client;

    /**
     * Switch to test database
     */
    public function setUp()
    {
      $this->current_timestamp = Timestamp::lock();

      $this->redis_client = new Client();
      $this->redis_client->select(15);
      $this->redis_client->flushdb();
    }

    /**
     * Tear down test database
     */
    public function tearDown()
    {
      Timestamp::unlock();
      $this->current_timestamp = null;

      $this->redis_client->flushdb();
    }
  }