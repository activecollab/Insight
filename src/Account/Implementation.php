<?php
  namespace ActiveCollab\Insight\Account;

  use ActiveCollab\Insight\Timestamp;
  use DateTime, DateTimeZone;

  trait Implementation
  {
    /**
     * @var array
     */
    private $properties = [];

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

      if (isset($this->properties[$property_name])) {
        foreach ($this->properties[$property_name] as $timestamp => $value) {
          if ($on_date_timestamp > $timestamp) {
            return $value;
          }
        }
      }

      return null;
    }

    /**
     * @param string   $property_name
     * @param mixed    $value
     * @param DateTime $on_date
     * @param mixed
     */
    public function setProperty($property_name, $value, DateTime $on_date = null)
    {
      $on_date_timestamp = $on_date instanceof DateTime ? $on_date->getTimestamp() : Timestamp::getCurrentTimestamp();

      if (empty($this->properties[$property_name])) {
        $this->properties[$property_name] = [];
      }

      $this->properties[$property_name][$on_date_timestamp] = $value;

      if (count($this->properties[$property_name]) > 1) {
        krsort($this->properties[$property_name]);
      }

      if (empty($this->property_timestamps[$property_name])) {
        $this->property_timestamps[$property_name] = [ $on_date_timestamp ];
      } else {
        if (!in_array($on_date_timestamp, $this->property_timestamps)) {
          $this->property_timestamps[$property_name][] = $on_date_timestamp;

          if (count($this->property_timestamps[$property_name]) > 1) {
            sort($this->property_timestamps[$property_name]);
          }
        }
      }
    }

    /**
     * @var array
     */
    private $property_timestamps = [];

    /**
     * Return a list of property timestamps
     *
     * @param  string $property_name
     * @return int[]
     */
    public function getPropertyTimestamps($property_name)
    {
      return isset($this->property_timestamps[$property_name]) ? $this->property_timestamps[$property_name] : [];
    }

    /**
     * @param  string        $property_name
     * @return DateTime|null
     */
    public function getOldestPropertyValueTimestamp($property_name)
    {
      if (isset($this->property_timestamps[$property_name])) {
        $result = new DateTime('now', new DateTimeZone('GMT'));
        $result->setTimestamp($this->property_timestamps[$property_name][0]);
        return $result;
      } else {
        return null;
      }
    }

    /**
     * @var array
     */
    private $latest_property_timestamps = [];

    /**
     * @param  string        $property_name
     * @return DateTime|null
     */
    public function getLatestPropertyValueTimestamp($property_name)
    {
      if (isset($this->property_timestamps[$property_name])) {
        $result = new DateTime('now', new DateTimeZone('GMT'));
        $result->setTimestamp($this->property_timestamps[$property_name][count($this->property_timestamps[$property_name]) - 1]);
        return $result;
      } else {
        return null;
      }
    }
  }