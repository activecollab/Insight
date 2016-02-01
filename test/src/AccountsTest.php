<?php

/*
 * This file is part of the Active Collab Promises.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Test;

/**
 * @package ActiveCollab\Resistance\Test
 */
class AccountsTest extends TestCase
{
    /**
     * Test get Redis key method behaviour.
     */
    public function testGetRedisKey()
    {
        $account = new Account($this->redis_client);

        $this->assertEquals('{ins}:acc:1', $account->getRedisKey());
        $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey('sub:key'));
        $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey('sub:key'));
        $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey(':sub:key:'));
        $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey(['sub', 'key']));
    }
}
