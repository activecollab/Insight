<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight;

use ActiveCollab\Insight\Metric\MetricInterface;
use Doctrine\Common\Inflector\Inflector;
use LogicException;

/**
 * @property \ActiveCollab\Insight\Metric\MrrInterface $mrr
 * @property \ActiveCollab\Insight\Metric\EventsInterface $events
 * @package ActiveCollab\Insight
 */
class Insight implements InsightInterface
{
    /**
     * @var MetricInterface[]
     */
    private $metrics = [];

    /**
     * Get a supported metric as a property.
     *
     * @param  string          $metric
     * @return MetricInterface
     */
    public function __get($metric)
    {
        if (empty($this->metrics[$metric])) {
            $class_name = '\\ActiveCollab\\Insight\\Metric\\' . Inflector::classify($metric);

            if (class_exists($class_name)) {
                $this->metrics[$metric] = new $class_name();
            } else {
                throw new LogicException("Metric '$metric' is not currently supported");
            }
        }

        return $this->metrics[$metric];
    }
}
