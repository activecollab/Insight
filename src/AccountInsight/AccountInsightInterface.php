<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\AccountInsight;

/**
 * @package ActiveCollab\Insight\AccountInsight
 */
interface AccountInsightInterface
{
    /**
     * Return ID of the account.
     *
     * @return int
     */
    public function getAccountId(): int;
}
