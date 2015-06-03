<?php
  namespace ActiveCollab\Insight\Events;

  use ActiveCollab\Insight\Utilities\Timestamp;
  use Redis, RedisCluster;

  /**
   * @package ActiveCollab\Insight\Events
   */
  trait Implementation
  {
    /**
     * Paginate events
     *
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public function getEvents($page = 1, $per_page = 100)
    {
      $result = [];

      foreach ($this->getInsightRedisClient()->zrevrange($this->getEventsKey(), ($page - 1) * $per_page, $page * $per_page - 1, true) as $hash => $timestamp) {
        if ($record = $this->getEventByHash($hash, $timestamp)) {
          $result[] = $record;
        } else {
          break;
        }
      }

      return $result;
    }

    /**
     * Return number of events that are logged
     *
     * @return integer
     */
    public function countEvents()
    {
      return $this->getInsightRedisClient()->zcard($this->getEventsKey());
    }

    /**
     * Iterate over events, for newest to oldest
     *
     * Two arguments are sent to the callback:
     *
     * 1. $event - array with event details
     * 2. $iteration - current iteration #, starting from 1
     *
     * @param callable $callback
     */
    public function forEachEvent(callable $callback)
    {
      $iteration = 0;
      foreach ($this->getInsightRedisClient()->zrevrange($this->getEventsKey(), 0, $this->countEvents() - 1, true) as $hash => $timestamp) {
        if ($record = $this->getEventByHash($hash, $timestamp)) {
          $callback_result = call_user_func($callback, $record, ++$iteration);

          if ($callback_result === false) {
            break;
          }
        } else {
          break;
        }
      }
    }

    /**
     * Load event details by hash
     *
     * @param  string     $hash
     * @param  integer    $timestamp
     * @return array|null
     */
    private function getEventByHash($hash, $timestamp)
    {
      $record_key = $this->getEventKey($hash);

      if ($this->getInsightRedisClient()->exists($record_key)) {
        $record = $this->getInsightRedisClient()->hmget($record_key, [ 'event', 'context' ]);

        return [
          'timestamp' => $timestamp,
          'hash' => $hash,
          'event' => $record['event'],
          'context' => unserialize($record['context']),
        ];
      } else {
        return null;
      }
    }

    /**
     * Log an event
     *
     * @param string $event
     * @param array  $context
     */
    public function logEvent($event, array $context = [])
    {
      do {
        $event_hash = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), rand(0, 23), 12);
        $event_key = $this->getEventKey($event_hash);
      } while ($this->getInsightRedisClient()->exists($event_key));

      if (isset($context['timestamp']) && $context['timestamp']) {
        $timestamp = $context['timestamp'];
        unset($context['timestamp']);
      } else {
        $timestamp = Timestamp::getCurrentTimestamp();
      }

      $this->transaction(function($t) use ($event, $context, $timestamp, $event_hash, $event_key) {

        /** @var $t Redis|RedisCluster */
        $t->hmset($event_key, [
          'event' => $event,
          'context' => serialize($context),
        ]);

        $t->zadd($this->getEventsKey(), $timestamp, $event_hash);
      });
    }

    /**
     * @return string
     */
    public function getEventsKey()
    {
      return $this->getRedisKey('events:hashes');
    }

    /**
     * Return key where individual event is stored
     *
     * @param  string $hash
     * @return string
     */
    public function getEventKey($hash)
    {
      return $this->getRedisKey("events:$hash");
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return Redis|RedisCluster
     */
    abstract protected function &getInsightRedisClient();

    /**
     * @param callable $callback
     */
    abstract protected function transaction(callable $callback);

    /**
     * Return Redis key for the given account and subkey
     *
     * @param  string|array|null $sub
     * @return string
     */
    abstract public function getRedisKey($sub = null);
  }