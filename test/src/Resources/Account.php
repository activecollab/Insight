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

use ActiveCollab\Insight\DataSetTimeline;
  use ActiveCollab\Insight\DataSetTimeline\Implementation as DataSetTimelineImplementation;
  use ActiveCollab\Insight\Events;
  use ActiveCollab\Insight\Events\Implementation as EventsImplementation;
  use ActiveCollab\Insight\Goals;
  use ActiveCollab\Insight\Goals\Implementation as GoalsImplementation;
  use ActiveCollab\Insight\Properties;
  use ActiveCollab\Insight\Properties\Implementation as PropertiesImplementation;
  use ActiveCollab\Insight\SystemLogs;
  use ActiveCollab\Insight\SystemLogs\Implementation as SystemLogsImplementation;
  use ActiveCollab\Insight\Utilities\Keyspace;
  use Redis;
  use RedisCluster;

  /**
   * @package ActiveCollab\Insight\Test
   */
  class Account implements Properties, Events, SystemLogs, DataSetTimeline, Goals
  {
      use Keyspace, PropertiesImplementation, EventsImplementation, SystemLogsImplementation, DataSetTimelineImplementation, GoalsImplementation;

    /**
     * @var Redis|RedisCluster
     */
    private $redis_client;

    /**
     * @var int
     */
    private $id = 1;

    /**
     * @param Redis|RedisCluster $redis_client
     * @param int|null           $id
     */
    public function __construct(&$redis_client, $id = null)
    {
        $this->redis_client = $redis_client;

        if ($id) {
            $this->id = $id;
        }

        $this->onBeforeSetProperty('clean_version_number', function (&$value) {
        if (strpos($value, '-')) {
            $value = explode('-', $value)[0];
        }
      });
    }

    /**
     * @return int
     */
    public function getInsightAccountId()
    {
        return $this->id;
    }

    /**
     * @return Redis|RedisCluster
     */
    protected function &getInsightRedisClient()
    {
        return $this->redis_client;
    }

    /**
     * @param callable $callback
     */
    protected function transaction(callable $callback)
    {
        call_user_func_array($callback, [&$this->redis_client]);
    }
  }
