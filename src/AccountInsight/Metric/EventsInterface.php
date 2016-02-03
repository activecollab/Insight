<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare (strict_types = 1);

namespace ActiveCollab\Insight\AccountInsight\Metric;

use ActiveCollab\DateValue\DateTimeValue;

/**
 * @package ActiveCollab\Insight\AccountInsight\Metric
 */
interface EventsInterface extends MetricInterface
{
    /**
     * Log an event.
     *
     * @param string             $what
     * @param DateTimeValue|null $when
     * @param array              $context
     */
    public function add(string $what, DateTimeValue $when = null, array $context = []);

    /**
     * Paginate events.
     *
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public function get(int $page = 1, int $per_page = 100);

    /**
     * Return all events.
     *
     * @return array
     */
    public function all();

    /**
     * Return number of events that are logged.
     *
     * @return int
     */
    public function count();
}
