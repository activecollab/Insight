<?php
  namespace ActiveCollab\Insight;

  use DateTime, DateTimeZone, InvalidArgumentException;

  /**
   * @package ActiveCollab\Insight
   */
  final class Timestamp
  {
    /**
     * Returns today object
     *
     * @return DateTime
     */
    public static function now()
    {
      $datetime = new DateTime('now', new DateTimeZone('GMT'));
      $datetime->setTimestamp(self::getCurrentTimestamp());

      return $datetime;
    }

    /**
     * Locked current timestamp value
     *
     * @var integer|null
     */
    private static $current_timestamp = null;

    /**
     * Return current timestamp
     *
     * @return int
     */
    public static function getCurrentTimestamp()
    {
      return self::$current_timestamp ? self::$current_timestamp : time();
    }

    /**
     * Return true if current timestamp is locked
     *
     * @return bool
     */
    public static function isLocked()
    {
      return self::$current_timestamp !== null;
    }

    /**
     * Lock current timestamp to a given value
     *
     * If $timestamp is set, that value will be used. In case $timestamp is null, current timestamp (time() call) will be used
     *
     * @param  integer|null             $timestamp
     * @return integer
     * @throws InvalidArgumentException
     */
    public static function lock($timestamp = null)
    {
      if (is_int($timestamp)) {
        self::$current_timestamp = $timestamp;
      } elseif ($timestamp === null) {
        self::$current_timestamp = time();
      } else {
        throw new InvalidArgumentException('Timestamp can be integer or NULL');
      }

      return self::$current_timestamp;
    }

    /**
     * Unlock current timestamp
     */
    public static function unlock()
    {
      self::$current_timestamp = null;
    }
  }