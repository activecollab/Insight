<?php
  namespace ActiveCollab\Insight\Test;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class AccountsTest extends TestCase
  {
    public function testGetRedisKey()
    {
      $account = new Account($this->redis_namespace, $this->redis_client);

      $this->assertEquals('i', $this->redis_namespace);

      $this->assertEquals('i:acc:1', $account->getRedisKey());
      $this->assertEquals('i:acc:1:sub:key', $account->getRedisKey('sub:key'));
      $this->assertEquals('i:acc:1:sub:key', $account->getRedisKey('sub:key'));
      $this->assertEquals('i:acc:1:sub:key', $account->getRedisKey(':sub:key:'));
      $this->assertEquals('i:acc:1:sub:key', $account->getRedisKey([ 'sub', 'key' ]));
    }
  }