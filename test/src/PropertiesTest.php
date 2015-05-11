<?php
  namespace ActiveCollab\Insight\Test;

  use DateTime;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class PropertiesTest extends TestCase
  {
    /**
     * Test date specific property
     */
    public function testDateSpecificProperty()
    {
      $account = new Account($this->redis_namespace, $this->redis_client);

      $account->setProperty('plan', 'M', new DateTime('2015-05-01'));
      $account->setProperty('plan', 'L', new DateTime('2015-05-07'));
      $account->setProperty('plan', 'XL', new DateTime('2015-05-11'));

      $this->assertEquals([
        (new DateTime('2015-05-01'))->getTimestamp(),
        (new DateTime('2015-05-07'))->getTimestamp(),
        (new DateTime('2015-05-11'))->getTimestamp()
      ], $account->getPropertyTimestamps('plan'));

      $this->assertEquals(new DateTime('2015-05-01'), $account->getOldestPropertyValueTimestamp('plan'));
      $this->assertEquals(new DateTime('2015-05-11'), $account->getLatestPropertyValueTimestamp('plan'));

      $this->assertNull($account->getProperty('plan', new DateTime('2014-08-09')));

      $this->assertEquals('M', $account->getProperty('plan', new DateTime('2015-05-02')));
      $this->assertEquals('L', $account->getProperty('plan', new DateTime('2015-05-10')));
      $this->assertEquals('XL', $account->getProperty('plan', new DateTime('2015-05-12')));
    }
  }