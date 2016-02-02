<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Account;

use DateTime;

/**
 * @package ActiveCollab\Insight
 */
interface DataSetTimelineInterface
{
    /**
     * Build timeline for the given time range (dates are inclusive).
     *
     * @param  DateTime $from
     * @param  DateTime $to
     * @return array
     */
    public function getTimeline(DateTime $from, DateTime $to);

    /**
     * Set timeline values for the given date.
     *
     * @param DateTime $date
     * @param int      $additions
     * @param int      $unarchives
     * @param int      $archives
     * @param int      $deletions
     */
    public function setTimelineDataForDate(DateTime $date, $additions, $unarchives, $archives, $deletions);

    /**
     * Increment number of additions for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogAddition(DateTime $date);

    /**
     * Increment number of unarchives for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogUnarchive(DateTime $date);

    /**
     * Increment number of archives for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogArchive(DateTime $date);

    /**
     * Increment number of deletions for the given date.
     *
     * @param DateTime $date
     */
    public function timelineLogDeletion(DateTime $date);
}
