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
  use Redis;
  use RedisCluster;

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
     * @var Redis|RedisCluster
     */
    protected $redis_client;

    /**
     * Switch to test database.
     */
    public function setUp()
    {
        $this->current_timestamp = Timestamp::lock();

        if (getenv('TEST_REDIS_CLUSTER')) {
            print "Resistance: Connecting to Redis Cluster...\n";
            $this->redis_client = new RedisCluster(null, ['127.0.0.1:30001', '127.0.0.1:30002', '127.0.0.1:30003']);
        } else {
            print "Resistance: Connecting to Standalone Redis...\n";
            $this->redis_client = new Redis();
            $this->redis_client->connect('127.0.0.1');
        }

        $this->flushData();
    }

    /**
     * Tear down test database.
     */
    public function tearDown()
    {
        Timestamp::unlock();
        $this->current_timestamp = null;

        $this->flushData();
    }

    /**
     * Flush data.
     */
    private function flushData()
    {
        if ($this->redis_client instanceof RedisCluster) {
            foreach ($this->redis_client->_masters() as $master) {
                $this->redis_client->flushAll($master);
            }
        } elseif ($this->redis_client instanceof Redis) {
            $this->redis_client->flushdb();
        }
    }
  }
