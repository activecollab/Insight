<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Metric;

use ActiveCollab\DateValue\DateValueInterface;

/**
 * @package ActiveCollab\Insight\Metric
 */
interface MrrInterface extends MetricInterface
{
    /**
     * Return MRR value on a given day.
     *
     * @param  DateValueInterface|null $day
     * @return int
     */
    public function getOnDay(DateValueInterface $day = null): int;
}
