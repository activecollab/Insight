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

use ActiveCollab\Insight\AccountInterface;

/**
 * @package ActiveCollab\Insight\Account
 */
class AccountElement implements AccountElementInterface
{
    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * @return AccountInterface
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param  AccountInterface $account
     * @return $this
     */
    public function &setAccount(AccountInterface $account)
    {
        $this->account = $account;

        return $this;
    }
}
