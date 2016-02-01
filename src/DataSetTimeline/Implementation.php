<?php

/*
 * This file is part of the Active Collab Promises.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\DataSetTimeline;

use DateInterval;
use DatePeriod;
use DateTime;
use LogicException;
use Redis;
use RedisCluster;

/**
 * @package ActiveCollab\Insight\DataSetTimeline
 */
trait Implementation
{
    /**
     * Build timeline for the given time range (dates are inclusive).
     *
     * @param  DateTime $from
     * @param  DateTime $to
     * @return array
     */
    public function getTimeline(DateTime $from, DateTime $to)
    {
        $from_timestamp = $from->format('Y-m-d');

        if ($from_timestamp == $to->format('Y-m-d')) {
            return [$from_timestamp => $this->getTimelineDataForDate($from)];
        } else {
            if ($from->getTimestamp() > $to->getTimestamp()) {
                throw new LogicException('From date should not be larger than to date');
            }

            $result = [];

            /** @var DateTime $date */
            foreach (new DatePeriod($from, DateInterval::createFromDateString('1 day'), $to->modify('+1 day')) as $date) {
                $result[$date->format('Y-m-d')] = $this->getTimelineDataForDate($date);
            }

            return $result;
        }
    }

    /**
     * Set timeline values for the given date.
     *
     * @param DateTime $date
     * @param int      $additions
     * @param int      $unarchives
     * @param int      $archives
     * @param int      $deletions
     */
    public function setTimelineDataForDate(DateTime $date, $additions, $unarchives, $archives, $deletions)
    {
        $this->getInsightRedisClient()->set($this->getTimelineKeyForDate($date), implode(',', [$this->prepareValueBeforeAddingToTimeline($additions), $this->prepareValueBeforeAddingToTimeline($unarchives), $this->prepareValueBeforeAddingToTimeline($archives), $this->prepareValueBeforeAddingToTimeline($deletions)]));
    }

    /**
     * Prepare input value before adding it to the timeline.
     *
     * @param  mixed $value
     * @return int
     */
    private function prepareValueBeforeAddingToTimeline($value)
    {
        $value = (integer) $value;

        return $value > 0 ? $value : 0;
    }

    /**
     * Increment number of additions for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogAddition(DateTime $date)
    {
        list($additions, $unarchives, $archives, $deletions) = $this->getTimelineDataForDate($date);
        ++$additions;
        $this->setTimelineDataForDate($date, $additions, $unarchives, $archives, $deletions);
    }

    /**
     * Increment number of unarchives for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogUnarchive(DateTime $date)
    {
        list($additions, $unarchives, $archives, $deletions) = $this->getTimelineDataForDate($date);
        ++$unarchives;
        $this->setTimelineDataForDate($date, $additions, $unarchives, $archives, $deletions);
    }

    /**
     * Increment number of archives for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogArchive(DateTime $date)
    {
        list($additions, $unarchives, $archives, $deletions) = $this->getTimelineDataForDate($date);
        ++$archives;
        $this->setTimelineDataForDate($date, $additions, $unarchives, $archives, $deletions);
    }

    /**
     * Increment number of deletions for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogDeletion(DateTime $date)
    {
        list($additions, $unarchives, $archives, $deletions) = $this->getTimelineDataForDate($date);
        ++$deletions;
        $this->setTimelineDataForDate($date, $additions, $unarchives, $archives, $deletions);
    }

    private function getTimelineDataForDate(DateTime $date)
    {
        $key = $this->getTimelineKeyForDate($date);

        if ($this->getInsightRedisClient()->exists($key)) {
            return array_map('intval', explode(',', $this->getInsightRedisClient()->get($key)));
        } else {
            return [0, 0, 0, 0];
        }
    }

    /**
     * Return timeline data key for the given date.
     *
     * @param  DateTime $date
     * @return string
     */
    private function getTimelineKeyForDate(DateTime $date)
    {
        return $this->getRedisKey('timeline:' . $date->format('Y-m-d'));
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return Redis|RedisCluster
     */
    abstract protected function &getInsightRedisClient();

    /**
     * Return Redis key for the given account and subkey.
     *
     * @param  string|array|null $sub
     * @return string
     */
    abstract public function getRedisKey($sub = null);
}
