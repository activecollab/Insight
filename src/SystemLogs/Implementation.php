<?php
  namespace ActiveCollab\Insight\SystemLogs;

  use ActiveCollab\Insight\Utilities\Timestamp;
  use Psr\Log\LoggerTrait;
  use LogicException;
  use Predis\Client;

  /**
   * @package ActiveCollab\Insight\SystemLogs
   */
  trait Implementation
  {
    use LoggerTrait;

    /**
     * Paginate log entries
     *
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public function getLog($page = 1, $per_page = 100)
    {
      $this->cleanUpRecordsFromExpiredHashes();

      $result = [];

      foreach ($this->getInsightRedisClient()->zrevrange($this->getLogRecordsKey(), ($page - 1) * $per_page, $page * $per_page - 1) as $hash) {
        $record = $this->getInsightRedisClient()->hmget($this->getLogRecordKey($hash), [ 'level', 'message', 'context' ]);

        $result[] = [
          'hash' => $hash,
          'level' => $record[0],
          'message' => $record[1],
          'context' => unserialize($record[2]),
        ];
      }

      return $result;
    }

    /**
     * Return number of log records that are in the log
     *
     * @return integer
     */
    public function getLogSize()
    {
      return $this->getInsightRedisClient()->zcard($this->getLogRecordsKey());
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
      do {
        $log_record_hash = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), rand(0, 23), 12);
        $log_record_key = $this->getLogRecordKey($log_record_hash);
      } while ($this->getInsightRedisClient()->exists($log_record_key));

      foreach ($context as $k => $v) {
        $search = $replace = [];

        if (strpos($message, '{' . $k . '}') !== false) {
          $search[] = '{' . $k . '}';
          $replace[] = '<span data-prop="' . $k . '">' . $v . '</span>';

          unset($context[$k]);
        }

        if (count($search) && count($replace)) {
          $message = str_replace($search, $replace, $message);
        }
      }

      if (isset($context['timestamp']) && $context['timestamp']) {
        $timestamp = $context['timestamp'];
        unset($context['timestamp']);
      } else {
        $timestamp = Timestamp::getCurrentTimestamp();
      }

      $this->getInsightRedisClient()->transaction(function($t) use ($level, $message, $context, $timestamp, $log_record_hash, $log_record_key) {

        /** @var $t Client */
        $t->hmset($log_record_key, [
          'level' => $level,
          'message' => $message,
          'context' => serialize($context),
        ]);

        if ($ttl = $this->getLogTtl()) {
          $t->expire($log_record_key, $ttl);
        }

        $t->zadd($this->getLogRecordsKey(), [ $log_record_hash => $timestamp ]);
      });

      $this->cleanUpRecordsFromExpiredHashes();
    }

    /**
     * Expire records that are older than TTL from the records list
     */
    private function cleanUpRecordsFromExpiredHashes()
    {
      $this->getInsightRedisClient()->zremrangebyscore($this->getLogRecordsKey(), '-inf', Timestamp::getCurrentTimestamp() - $this->getLogTtl());
    }

    /**
     * Return time to live for log records
     *
     * @return integer
     */
    protected function getLogTtl()
    {
      return 604800; // 7 days
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, array $context = array())
    {
      throw new LogicException('Debug messages should not be stored in the Insight database');
    }

    /**
     * @return string
     */
    public function getLogRecordsKey()
    {
      return $this->getRedisKey('log:records');
    }

    /**
     * Return key where individual log record is stored
     *
     * @param  string $record_hash
     * @return string
     */
    public function getLogRecordKey($record_hash)
    {
      return $this->getRedisKey("log:$record_hash");
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return Client
     */
    abstract protected function &getInsightRedisClient();

    /**
     * Return Redis key for the given account and subkey
     *
     * @param  string|array|null $sub
     * @return string
     */
    abstract public function getRedisKey($sub = null);
  }