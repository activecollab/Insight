<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Utilities\Timestamp;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class SystemLogsTest extends TestCase
  {
    /**
     * @var Account
     */
    private $account;

    /**
     * Set up teast environment
     */
    public function setUp()
    {
      parent::setUp();

      $this->account = new Account($this->redis_client);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
      $this->account = null;

      parent::tearDown();
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnDebug()
    {
      $this->account->debug('Detailed email sent log entry');
    }

    /**
     * Test if log size is properly tracked
     */
    public function testGetSystemLogSize()
    {
      $this->account->error('Failed to send email');
      $this->account->warning('Something odd is happening here');
      $this->account->info('Email sent');

      $this->assertEquals(3, $this->account->getLogSize());
    }

    /**
     * Test log message placeholders
     */
    public function testLogMessagePlaceholders()
    {
      $this->account->error('Failed to send email from {from} to {to} with subject "{subject}"', [
        'from' => 'ilija.studen@activecollab.com',
        'to' => 'goran.radulovic@activecollab.com',
        'subject' => 'Re: Feather updates',
        'not_in_message' => true,
      ]);

      $log_entries = $this->account->getLog();

      $this->assertCount(1, $log_entries);

      $this->assertEquals('Failed to send email from <span data-prop="from">ilija.studen@activecollab.com</span> to <span data-prop="to">goran.radulovic@activecollab.com</span> with subject "<span data-prop="subject">Re: Feather updates</span>"', $this->account->getLog()[0]['message']);
      $this->assertEquals([
        'not_in_message' => true,
      ], $this->account->getLog()[0]['context']);
    }

    /**
     * Test log pagination
     */
    public function testLogPagination()
    {
      $current_timestamp = Timestamp::getCurrentTimestamp();

      for ($i = 1; $i <= 11; $i++) {
        $current_timestamp = Timestamp::lock($current_timestamp + 1);

        $this->account->info("Event {$i}");
      }

      $page_1 = $this->account->getLog(1, 5);

      $this->assertCount(5, $page_1);

      $this->assertEquals('Event 11', $page_1[0]['message']);
      $this->assertEquals('Event 10', $page_1[1]['message']);
      $this->assertEquals('Event 9', $page_1[2]['message']);
      $this->assertEquals('Event 8', $page_1[3]['message']);
      $this->assertEquals('Event 7', $page_1[4]['message']);

      $page_2 = $this->account->getLog(2, 5);

      $this->assertCount(5, $page_2);

      $this->assertEquals('Event 6', $page_2[0]['message']);
      $this->assertEquals('Event 5', $page_2[1]['message']);
      $this->assertEquals('Event 4', $page_2[2]['message']);
      $this->assertEquals('Event 3', $page_2[3]['message']);
      $this->assertEquals('Event 2', $page_2[4]['message']);

      $page_3 = $this->account->getLog(3, 5);

      $this->assertCount(1, $page_3);

      $this->assertEquals('Event 1', $page_3[0]['message']);
    }

    /**
     * Confirm that log records are properly set to expire
     */
    public function testLogTtl()
    {
      $this->account->info('Log entry');

      $hash = $this->account->getLog()[0]['hash'];

      $ttl = $this->redis_client->ttl($this->account->getLogRecordKey($hash));

      $this->assertNotEquals(-1, $ttl);
      $this->assertNotEquals(-2, $ttl);
      $this->assertEquals(604800, $ttl);
    }

    /**
     * Make sure that references to the old records are cleaned up on new record
     */
    public function testLogRecordsCleanupOnNewRecord()
    {
      $old_timestamp = Timestamp::lock(strtotime('-14 days')); // old

      $this->account->info('Log entry #1');

      $this->assertEquals(1, $this->redis_client->zcount($this->account->getLogRecordsKey(), $old_timestamp, $old_timestamp));

      $new_timestamp = Timestamp::lock();

      $this->account->info('Log entry #2');

      $this->assertEquals(0, $this->redis_client->zcount($this->account->getLogRecordsKey(), $old_timestamp, $old_timestamp));
      $this->assertEquals(1, $this->redis_client->zcount($this->account->getLogRecordsKey(), $new_timestamp, $new_timestamp));
    }
  }