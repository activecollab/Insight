<?php
  namespace ActiveCollab\Insight\Account;

  use ActiveCollab\Insight\Timestamp;
  use DateTime;

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

      if (isset($this->properties[$property_name])) {
        foreach ($this->properties[$property_name] as $timestamp => $value) {
          if ($timestamp > $on_date_timestamp) {
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
      if (empty($on_date)) {
        $on_date = Timestamp::now();
      }

      if (empty($this->properties[$property_name])) {
        $this->properties[$property_name] = [];
      }

      $this->properties[$property_name][$on_date->getTimestamp()] = $value;

      if (count($this->properties) > 1) {
        krsort($this->properties);
      }
    }

    /**
     * @var array
     */
    private $oldest_property_timestamps = [];

    /**
     * @param  string        $property_name
     * @return DateTime|null
     */
    public function getOldestPropertyValueTimestamp($property_name)
    {
      if (!array_key_exists($property_name, $this->oldest_property_timestamps)) {
        
      }

      return $this->oldest_property_timestamps[$property_name];
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
      if (!array_key_exists($property_name, $this->latest_property_timestamps)) {

      }

      return $this->latest_property_timestamps[$property_name];
    }
  }