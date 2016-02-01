<?php

/*
 * This file is part of the Active Collab Promises.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight;

/**
 * @package ActiveCollab\Insight
 */
interface EventsInterface
{
    /**
     * Log an event.
     *
     * @param string $event
     * @param array  $context
     */
    public function logEvent($event, array $context = []);

    /**
     * Paginate events.
     *
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public function getEvents($page = 1, $per_page = 100);

    /**
     * Return number of events that are logged.
     *
     * @return int
     */
    public function countEvents();

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
    public function forEachEvent(callable $callback);
}
