<?php
  namespace ActiveCollab\Insight\Utilities;

  use Redis, RedisCluster;

  /**
   * @package ActiveCollab\Insight\Utilities
   */
  trait Transaction
  {
    /**
     * @param callable $callback
     */
    public function transaction(callable $callback)
    {
      call_user_func_array($callback, [ $this->getInsightRedisClient() ]);
    }

    /**
     * @return Redis|RedisCluster
     */
    abstract protected function &getInsightRedisClient();
  }