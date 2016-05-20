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

/**
 * Interface AccountInterface.
 *
 * @package ActiveCollab\Insight
 */
interface AccountInterface
{
    /**
     * Return account ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Return metrics storage instance.
     *
     * @return StorageInterface
     */
    public function &getMetricsStorage(): StorageInterface;
}
