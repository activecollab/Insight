<?php
  namespace ActiveCollab\Insight\Account;

  use ActiveCollab\Insight\Timestamp;
  use DateTime, DateTimeZone;
  use Predis\Client;
  use InvalidArgumentException;

  trait Implementation
  {
    /**
     * @param  string   $property_name
     * @param  DateTime $on_date
     * @return mixed
     */
    public function getProperty($property_name, DateTime $on_date = null)
    {
      $on_date_timestamp = $on_date instanceof DateTime ? $on_date->getTimestamp() : Timestamp::getCurrentTimestamp();

      if ($oldest_property_timestamp = $this->getOldestPropertyValueTimestamp($property_name)) {
        if ($oldest_property_timestamp->getTimestamp() > $on_date->getTimestamp()) {
          return null;
        }
      }

      $timestamps = $this->getPropertyTimestamps($property_name);

      for ($i = count($timestamps) - 1; $i >= 0; $i--) {
        if ($on_date_timestamp > $timestamps[$i]) {
          return $this->getRedisClient()->get($this->getPropertyValueKey($property_name, $timestamps[$i]));
        }
      }

      return null;
    }

    /**
     * Set property value on the given date
     *
     * If $on_date is not provided, current date is used
     *
     * @param string   $property_name
     * @param mixed    $value
     * @param DateTime $on_date
     * @param mixed
     */
    public function setProperty($property_name, $value, DateTime $on_date = null)
    {
      $on_date_timestamp = $on_date instanceof DateTime ? $on_date->getTimestamp() : Timestamp::getCurrentTimestamp();

      $existing_property_timestamps = $this->getPropertyTimestamps($property_name);

      $this->getRedisClient()->transaction(function($t) use ($property_name, $value, $on_date_timestamp, $existing_property_timestamps) {
        $property_value_key = $this->getPropertyValueKey($property_name, $on_date_timestamp);

        /** @var $t Client */
        $t->set($this->getPropertyValueKey($property_name, $on_date_timestamp), $value);

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
     * Return a list of property timestamps
     *
     * @param  string $property_name
     * @return int[]
     */
    public function getPropertyTimestamps($property_name)
    {
      $timestamps_key = $this->getPropertyTimestampsKey($property_name);

      if ($this->getRedisClient()->exists($timestamps_key)) {
        return array_map('intval', explode(',', $this->getRedisClient()->get($timestamps_key)));
      } else {
        return [];
      }
    }

    /**
     * @param  string        $property_name
     * @return DateTime|null
     */
    public function getOldestPropertyValueTimestamp($property_name)
    {
      $oldest_property_value_timestamp_key = $this->getOldestPropertyValueTimestampKey($property_name);

      if ($this->getRedisClient()->exists($oldest_property_value_timestamp_key)) {
        $result = new DateTime('now', new DateTimeZone('GMT'));
        $result->setTimestamp((integer) $this->getRedisClient()->get($oldest_property_value_timestamp_key));
        return $result;
      } else {
        return null;
      }
    }

    /**
     * @param  string        $property_name
     * @return DateTime|null
     */
    public function getLatestPropertyValueTimestamp($property_name)
    {
      $latest_property_value_timestamp_key = $this->getLatestPropertyValueTimestampKey($property_name);

      if ($this->getRedisClient()->exists($latest_property_value_timestamp_key)) {
        $result = new DateTime('now', new DateTimeZone('GMT'));
        $result->setTimestamp((integer) $this->getRedisClient()->get($latest_property_value_timestamp_key));
        return $result;
      } else {
        return null;
      }
    }

    /**
     * Return property value key for the given property and timestamp
     *
     * @param  string  $property_name
     * @param  integer $timestamp
     * @return string
     */
    private function getPropertyValueKey($property_name, $timestamp)
    {
      if (is_int($timestamp) && $timestamp > 0) {
        return $this->getRedisKey("prop:$property_name:$timestamp");
      } else {
        throw new \InvalidArgumentException('Invalid timestamp');
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

    /**
     * Return Redis key for the given account and subkey
     *
     * @param  string|array|null $sub
     * @return string
     */
    public function getRedisKey($sub = null)
    {
      $result = $this->getRedisNamespace() . ':acc:' . $this->getInsightAccountId();

      if (is_string($sub) && $sub) {
        $result .= substr($sub, 0, 1) == ':' ? $sub : ':' . $sub;
      } else if (is_array($sub) && count($sub)) {
        $result .= ':' . implode(':', $sub);
      }

      return trim($result, ':');
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return int
     */
    abstract public function getInsightAccountId();

    /**
     * Return namespace that is used to prefix Insight database entries
     *
     * @return string
     */
    abstract protected function getRedisNamespace();

    /**
     * @return Client
     */
    abstract protected function &getRedisClient();
  }