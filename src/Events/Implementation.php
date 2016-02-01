<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Events;

use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\Account\Event;
use ActiveCollab\Insight\AccountInterface;
use ActiveCollab\Insight\StorageInterface;

/**
 * @package ActiveCollab\Insight\Events
 */
trait Implementation
{
    /**
     * Paginate events.
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
     * Return number of events that are logged.
     *
     * @return int
     */
    public function countEvents()
    {
        return $this->getInsightRedisClient()->zcard($this->getEventsKey());
    }

    /**
     * Iterate over events, for newest to oldest.
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
//        $iteration = 0;
//        foreach ($this->getInsightRedisClient()->zrevrange($this->getEventsKey(), 0, $this->countEvents() - 1, true) as $hash => $timestamp) {
//            if ($record = $this->getEventByHash($hash, $timestamp)) {
//                $callback_result = call_user_func($callback, $record, ++$iteration);
//
//                if ($callback_result === false) {
//                    break;
//                }
//            } else {
//                break;
//            }
//        }
    }

    /**
     * Log an event.
     *
     * @param string $event
     * @param array  $context
     */
    public function logEvent($event, array $context = [])
    {
        if (empty($context['timestamp'])) {
            $timestamp = DateTimeValue::now();
        } elseif (is_int($context['timestamp'])) {
            $timestamp = DateTimeValue::createFromTimestamp($context['timestamp']);
        } else {
            $timestamp = new DateTimeValue($context['timestamp']);
        }

        /** @var $this AccountInterface */
        $this->getMetricsStorage()->store(new Event($this, $event, $timestamp, $context));
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return account ID.
     *
     * @return int
     */
    abstract public function getId();

    /**
     * Return metrics storage instance.
     *
     * @return StorageInterface
     */
    abstract public function &getMetricsStorage(): StorageInterface;
}
