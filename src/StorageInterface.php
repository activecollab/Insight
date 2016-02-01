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
     * Prepare data stores.
     */
    public function prepareStores();

    /**
     * Return store name for the given element type.
     *
     * @param  string $for_element
     * @return string
     */
    public function getStoreName($for_element): string;

    /**
     * Return an array of store names that we use to store metrics.
     *
     * @return array
     */
    public function getStoreNames(): array;

    /**
     * Clear all storage data.
     */
    public function clear();
}
