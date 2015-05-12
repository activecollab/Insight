<?php
  namespace ActiveCollab\Insight;

  use DateTime;

  interface Account
  {
    /**
     * Return account ID
     *
     * @return int
     */
    public function getInsightAccountId();

    /**
     * @param  string      $property_name
     * @return string|null
     */
    public function getOldestPropertyValueTimestamp($property_name);

    /**
     * @param  string      $property_name
     * @return string|null
     */
    public function getLatestPropertyValueTimestamp($property_name);

    /**
     * @param  string   $property_name
     * @param  DateTime $on_date
     * @return mixed
     */
    public function getProperty($property_name, DateTime $on_date = null);

    /**
     * @param string   $property_name
     * @param mixed    $value
     * @param DateTime $on_date
     * @param mixed
     */
    public function setProperty($property_name, $value, DateTime $on_date = null);
  }