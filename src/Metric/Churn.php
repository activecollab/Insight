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
class Churn extends Metric implements ChurnInterface
{
    /**
     * {@inheritdoc}
     */
    public function &snapshot(DateValueInterface $reference_day, int $number_of_paid_accounts, int $current_mrr): ChurnInterface
    {
        return $this;
    }
}
