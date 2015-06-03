<?php
  namespace ActiveCollab\Insight\Test;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class AccountsTest extends TestCase
  {
    /**
     * Test get Redis key method behaviour
     */
    public function testGetRedisKey()
    {
      $account = new Account($this->redis_client);

      $this->assertEquals('{ins}:acc:1', $account->getRedisKey());
      $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey('sub:key'));
      $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey('sub:key'));
      $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey(':sub:key:'));
      $this->assertEquals('{ins}:acc:1:sub:key', $account->getRedisKey([ 'sub', 'key' ]));
    }
  }