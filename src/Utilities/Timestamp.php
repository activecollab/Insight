<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Utilities;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * @package ActiveCollab\Insight
 */
final class Timestamp
{
    /**
     * Returns today object.
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
     * Locked current timestamp value.
     *
     * @var int|null
     */
    private static $current_timestamp = null;

    /**
     * Return current timestamp.
     *
     * @return int
     */
    public static function getCurrentTimestamp()
    {
        return self::$current_timestamp ? self::$current_timestamp : time();
    }

    /**
     * Return DateTime instances based on the current timestamp.
     *
     * @return DateTime
     */
    public static function getCurrentDateTime()
    {
        $result = new DateTime('now', new DateTimeZone('GMT'));
        $result->setTimestamp(self::getCurrentTimestamp());

        return $result;
    }

    /**
     * Return formatted current timestamp.
     *
     * @param  string $format
     * @return string
     */
    public static function formatCurrentTimestamp($format)
    {
        return self::$current_timestamp ? date($format, self::$current_timestamp) : date($format);
    }

    /**
     * Return true if current timestamp is locked.
     *
     * @return bool
     */
    public static function isLocked()
    {
        return self::$current_timestamp !== null;
    }

    /**
     * Lock current timestamp to a given value.
     *
     * If $timestamp is set, that value will be used. In case $timestamp is null, current timestamp (time() call) will be used
     *
     * @param  int|null                 $timestamp
     * @return int
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
     * Unlock current timestamp.
     */
    public static function unlock()
    {
        self::$current_timestamp = null;
    }
}
