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

namespace ActiveCollab\Insight\Account;

use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\Insight\StorageInterface;

/**
 * @package ActiveCollab\Insight\Account
 */
class Event extends AccountElement implements EventInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $name;

    /**
     * @var DateTimeValue
     */
    private $timestamp;

    /**
     * @var array
     */
    private $context;

    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface &$storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param array $row
     */
    public function loadFromRow(array $row)
    {
        $this->setAccount($this->storage->getAccount($row['account_id']));

        $this->name = $row['name'];
        $this->timestamp = $row['created_at'];
        $this->context = $row['context'] ? unserialize($row['context']) : [];
    }

    /**
     * Get event name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set event name.
     *
     * @param  string $name
     * @return $this
     */
    public function &setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get event timestamp.
     *
     * @return DateTimeValue
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set event timestamp.
     *
     * @param  DateTimeValue $timestamp
     * @return $this
     */
    public function &setTimestamp(DateTimeValue $timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get event context.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set event context.
     *
     * @param  array|null $context
     * @return $this
     */
    public function &setContext(array $context = null)
    {
        $this->context = $context ?? [];

        return $this;
    }
}
