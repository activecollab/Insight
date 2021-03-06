<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test\Base;

use ActiveCollab\Insight\AccountInsight\AccountInsight;
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;

/**
 * @package ActiveCollab\Insight\Test
 */
abstract class AccountInsightTestCase extends InsightTestCase
{
    /**
     * @var AccountInsight|AccountInsightInterface
     */
    protected $account;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->account = $this->insight->account(1);
    }
}
