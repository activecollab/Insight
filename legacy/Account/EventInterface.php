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

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DateValue\DateTimeValue;

/**
 * @package ActiveCollab\Insight\Account
 */
interface EventInterface extends AccountElementInterface, LoadFromRow
{
    /**
     * Get event name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set event name.
     *
     * @param  string $name
     * @return $this
     */
    public function &setName($name);

    /**
     * Get event timestamp.
     *
     * @return DateTimeValue
     */
    public function getTimestamp();

    /**
     * Set event timestamp.
     *
     * @param  DateTimeValue $timestamp
     * @return $this
     */
    public function &setTimestamp(DateTimeValue $timestamp);

    /**
     * Get event context.
     *
     * @return array
     */
    public function getContext();

    /**
     * Set event context.
     *
     * @param  array|null $context
     * @return $this
     */
    public function &setContext(array $context = null);
}
