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
interface ChurnInterface extends MetricInterface
{
    /**
     * Create a reference snapshot,
     *
     * @param  DateValueInterface $reference_day
     * @param  int                $number_of_paid_accounts
     * @param  int                $current_mrr
     * @return ChurnInterface
     */
    public function &snapshot(DateValueInterface $reference_day, int $number_of_paid_accounts, int $current_mrr): ChurnInterface;
}
