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

namespace ActiveCollab\Insight;

/**
 * @package ActiveCollab\Insight
 */
interface StorageInterface
{
    /**
     * @param  ElementInterface $element
     * @return $this
     */
    public function store(ElementInterface $element);

    /**
     * Return total number of elements of a given type that we have stored.
     *
     * @param  string $element_type
     * @return int
     */
    public function count(string $element_type): int;

    /**
     * Return total number of elements of a given type that we have stored for the given account.
     *
     * @param  string           $element_type
     * @param  AccountInterface $account
     * @return int
     */
    public function countByAccount(string $element_type, AccountInterface $account): int;

    /**
     * Return store name for the given element type.
     *
     * @param  string $element_type
     * @return string
     */
    public function getStoreName(string $element_type): string;

    /**
     * Return an array of store names that we use to store metrics.
     *
     * @return array
     */
    public function getStoreNames(): array;

    /**
     * Prepare data stores.
     *
     * @return $this
     */
    public function prepareStores();

    /**
     * Clear all storage data.
     *
     * @return $this
     */
    public function clear();
}
