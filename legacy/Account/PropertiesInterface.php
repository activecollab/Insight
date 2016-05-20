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
interface PropertiesInterface
{
    /**
     * Return account ID.
     *
     * @return int
     */
    public function getInsightAccountId();

    /**
     * @param  string   $property_name
     * @param  DateTime $on_date
     * @return mixed
     */
    public function getProperty($property_name, DateTime $on_date = null);

    /**
     * @param string   $property_name
     * @param mixed    $value
     * @param DateTime $on_date
     * @param          mixed
     */
    public function setProperty($property_name, $value, DateTime $on_date = null);

    /**
     * Return property history (key is date).
     *
     * @param  string $property_name
     * @return array
     */
    public function getPropertyHistory($property_name);

    /**
     * @param  string      $property_name
     * @return string|null
     */
    public function getOldestPropertyValueTimestamp($property_name);

    /**
     * @param  string      $property_name
     * @return string|null
     */
    public function getLatestPropertyValueTimestamp($property_name);
}