<?php
  namespace ActiveCollab\Insight\Properties;

  use ActiveCollab\Insight\Utilities\Timestamp;
  use DateTime, DateTimeZone;
  use Redis, RedisCluster;
  use InvalidArgumentException;

  /**
   * @package ActiveCollab\Insight\Account
   */
  trait Implementation
  {
    /**
     * @param  string   $property_name
     * @param  DateTime $on_date
     * @return mixed
     */
    public function getProperty($property_name, DateTime $on_date = null)
    {
      if (empty($on_date)) {
        $on_date = Timestamp::getCurrentDateTime();
      }

      $on_date_timestamp = $on_date->format('Y-m-d');

      if ($oldest_property_timestamp = $this->getOldestPropertyValueTimestamp($property_name)) {
        if ((new DateTime($oldest_property_timestamp, new DateTimeZone('GMT')))->getTimestamp() > $on_date->getTimestamp()) {
          return null;
        }
      }

      $timestamps = $this->getPropertyTimestamps($property_name);

      for ($i = count($timestamps) - 1; $i >= 0; $i--) {
        if (strcmp($on_date_timestamp, $timestamps[$i]) >= 0) {
          if ($raw_value = $this->getInsightRedisClient()->get($this->getPropertyValueKey($property_name, $timestamps[$i]))) {
            return $this->unserializeRawPropertyValue($raw_value);
          }
        }
      }

      return null;
    }

    /**
     * Return property history (key is date)
     *
     * @param  string $property_name
     * @return array
     */
    public function getPropertyHistory($property_name)
    {
      $result = [];

      foreach ($this->getPropertyTimestamps($property_name) as $timestamp) {
        if ($raw_value = $this->getInsightRedisClient()->get($this->getPropertyValueKey($property_name, $timestamp))) {
          $result[$timestamp] = $this->unserializeRawPropertyValue($raw_value);
        } else {
          $result[$timestamp] = null;
        }
      }

      return $result;
    }

    /**
     * Serialize to raw property value
     *
     * @param  mixed      $value
     * @return int|string
     */
    private function serializeToRawPropertyValue($value)
    {
      if (is_int($value)) {
        return $value;
      } else if (is_string($value) && (ctype_digit($value) || substr($value, 0, 1) == '-' && ctype_digit(substr($value, 1)))) {
        return (integer) $value;
      } else {
        return serialize($value);
      }
    }

    /**
     * Unserialize raw property value
     *
     * @param  string         $raw_value
     * @return bool|int|mixed
     */
    private function unserializeRawPropertyValue($raw_value)
    {
      if ($raw_value === 'b:0;') {
        return false;
      } else {
        $value = @unserialize($raw_value);

        if ($value === false) {
          return (int) $raw_value;
        } else {
          return $value;
        }
      }
    }

    /**
     * @var callable[]
     */
    private $before_set_property = [];

    /**
     * Set property value on the given date
     *
     * If $on_date is not provided, current date is used
     *
     * @param  string                   $property_name
     * @param  mixed                    $value
     * @param  DateTime                 $on_date
     * @throws InvalidArgumentException
     */
    public function setProperty($property_name, $value, DateTime $on_date = null)
    {
      if (!$this->isValidPropertyName($property_name)) {
        throw new InvalidArgumentException("Property name '$property_name' is not valid (letters, numbers, space and underscore are allowed)");
      }

      if (isset($this->before_set_property[$property_name])) {
        call_user_func_array($this->before_set_property[$property_name], [ &$value ]);
      }

      if ($this->getProperty($property_name, $on_date) === $value) {
        return;
      }

      if (empty($on_date)) {
        $on_date = Timestamp::getCurrentDateTime();
      }

      $on_date_timestamp = $on_date->format('Y-m-d');

      $existing_property_timestamps = $this->getPropertyTimestamps($property_name);

      $this->transaction(function($t) use ($property_name, $value, $on_date_timestamp, $existing_property_timestamps) {
        $property_value_key = $this->getPropertyValueKey($property_name, $on_date_timestamp);

        /** @var $t Redis */
        $t->set($this->getPropertyValueKey($property_name, $on_date_timestamp), $this->serializeToRawPropertyValue($value));

        if (!in_array($property_value_key, $existing_property_timestamps)) {
          $existing_property_timestamps[] = $on_date_timestamp;

          sort($existing_property_timestamps);

          $t->set($this->getPropertyTimestampsKey($property_name), implode(',', $existing_property_timestamps));
          $t->set($this->getOldestPropertyValueTimestampKey($property_name), $existing_property_timestamps[0]);
          $t->set($this->getLatestPropertyValueTimestampKey($property_name), $existing_property_timestamps[count($existing_property_timestamps) - 1]);
        }
      });
    }

    /**
     * Set callback that is called before field value is set
     *
     * Value is passed by reference, so it can be modified:
     *
     * public function __construct(Client &$redis_client, $id = null)
     * {
     *   $this->onBeforeSetProperty('clean_version_number', function(&$value) {
     *     if (strpos($value, '-')) {
     *       $value = explode('-', $value)[0];
     *     }
     *   });
     * }
     *
     * @param  string                   $property_name
     * @param  callable                 $callback
     * @throws InvalidArgumentException
     */
    protected function onBeforeSetProperty($property_name, callable $callback)
    {
      if (is_callable($callback)) {
        $this->before_set_property[$property_name] = $callback;
      } else {
        throw new InvalidArgumentException('$callback needs to be callable');
      }
    }

    /**
     * Return a list of property timestamps
     *
     * @param  string $property_name
     * @return int[]
     */
    public function getPropertyTimestamps($property_name)
    {
      $timestamps_key = $this->getPropertyTimestampsKey($property_name);

      if ($this->getInsightRedisClient()->exists($timestamps_key)) {
        return explode(',', $this->getInsightRedisClient()->get($timestamps_key));
      } else {
        return [];
      }
    }

    /**
     * @param  string      $property_name
     * @return string|null
     */
    public function getOldestPropertyValueTimestamp($property_name)
    {
      $oldest_property_value_timestamp_key = $this->getOldestPropertyValueTimestampKey($property_name);

      if ($this->getInsightRedisClient()->exists($oldest_property_value_timestamp_key)) {
        return $this->getInsightRedisClient()->get($oldest_property_value_timestamp_key);
      } else {
        return null;
      }
    }

    /**
     * @param  string      $property_name
     * @return string|null
     */
    public function getLatestPropertyValueTimestamp($property_name)
    {
      $latest_property_value_timestamp_key = $this->getLatestPropertyValueTimestampKey($property_name);

      if ($this->getInsightRedisClient()->exists($latest_property_value_timestamp_key)) {
        return $this->getInsightRedisClient()->get($latest_property_value_timestamp_key);
      } else {
        return null;
      }
    }

    /**
     * Return true if $property_name is valid
     *
     * @param  string $property_name
     * @return bool
     */
    public function isValidPropertyName($property_name)
    {
      if (is_string($property_name)) {
        if ($property_name = trim($property_name)) {
          $len = mb_strlen($property_name);

          if ($len > 0 && $len <= 50) {
            return (boolean)preg_match("/^([a-zA-Z0-9_\s]*)$/", $property_name);
          }
        }
      }

      return false;
    }

    // ---------------------------------------------------
    //  Property keys
    // ---------------------------------------------------

    /**
     * Return property value key for the given property and timestamp
     *
     * @param  string  $property_name
     * @param  integer $timestamp
     * @return string
     */
    private function getPropertyValueKey($property_name, $timestamp)
    {
      if (is_string($timestamp)) {
        return $this->getRedisKey("prop:$property_name:$timestamp");
      } else {
        throw new InvalidArgumentException('Invalid timestamp');
      }
    }

    /**
     * Return key where we'll store all property value timestamps
     *
     * @param  string $property_name
     * @return string
     */
    private function getPropertyTimestampsKey($property_name)
    {
      return $this->getRedisKey("prop:$property_name:timestamps");
    }

    /**
     * Return string where we'll store oldest property value timestamp
     *
     * @param  string $property_name
     * @return string
     */
    private function getOldestPropertyValueTimestampKey($property_name)
    {
      return $this->getRedisKey("prop:$property_name:timestamps:min");
    }

    /**
     * Return string where we'll store newest property value timestamp
     *
     * @param  string $property_name
     * @return string
     */
    private function getLatestPropertyValueTimestampKey($property_name)
    {
      return $this->getRedisKey("prop:$property_name:timestamps:max");
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