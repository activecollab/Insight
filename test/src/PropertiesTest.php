<?php
  namespace ActiveCollab\Insight\Test;

  use DateTime;

  /**
   * @package ActiveCollab\Resistance\Test
   */
  class PropertiesTest extends TestCase
  {
    /**
     * @var Account
     */
    private $account;

    /**
     * Set up teast environment
     */
    public function setUp()
    {
      parent::setUp();

      $this->account = new Account($this->redis_client);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
      $this->account = null;

      parent::tearDown();
    }

    /**
     * Test validate property name
     */
    public function testValidatePropertyName()
    {
      $this->assertTrue($this->account->isValidPropertyName('valid_property_name'));
      $this->assertTrue($this->account->isValidPropertyName('ValidPropertyName123'));
      $this->assertTrue($this->account->isValidPropertyName('Valid Property Name'));
      $this->assertFalse($this->account->isValidPropertyName('something:redis:something'));
      $this->assertFalse($this->account->isValidPropertyName('InvalidChar*'));
      $this->assertFalse($this->account->isValidPropertyName('  '));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnInvalidPropetyName()
    {
      $this->account->setProperty('Invalid:', 'M');
    }

    /**
     * Test get / set property
     */
    public function testGetSet()
    {
      $this->account->setProperty('plan', 'M');
      $this->assertEquals('M', $this->account->getProperty('plan'));
    }

    /**
     * Don't store duplicates of the current value
     */
    public function testDontStoreDusplicatesOfTheCurrentValue()
    {
      $this->account->setProperty('plan', 'M', new DateTime('2015-05-01'));
      $this->account->setProperty('plan', 'M', new DateTime('2015-05-02'));

      $this->assertCount(1, $this->account->getPropertyTimestamps('plan'));
    }

    /**
     * Don't store duplicates of the current value historically
     */
    public function testDontStoreDusplicatesOfTheCurrentValueHistorically()
    {
      $this->account->setProperty('plan', 'M', new DateTime('2015-05-01'));
      $this->account->setProperty('plan', 'L', new DateTime('2015-05-07'));

      $this->assertCount(2, $this->account->getPropertyTimestamps('plan'));

      $this->account->setProperty('plan', 'M', new DateTime('2015-05-02'));

      $this->assertCount(2, $this->account->getPropertyTimestamps('plan'));
    }

    /**
     * Test date specific property
     */
    public function testDateSpecificProperty()
    {
      $this->account->setProperty('plan', 'M', new DateTime('2015-05-01'));
      $this->account->setProperty('plan', 'L', new DateTime('2015-05-07'));
      $this->account->setProperty('plan', 'XL', new DateTime('2015-05-11'));

      $this->assertEquals([ '2015-05-01', '2015-05-07', '2015-05-11' ], $this->account->getPropertyTimestamps('plan'));
      $this->assertEquals([
        '2015-05-01' => 'M',
        '2015-05-07' => 'L',
        '2015-05-11' => 'XL'
      ], $this->account->getPropertyHistory('plan'));

      $this->assertEquals('2015-05-01', $this->account->getOldestPropertyValueTimestamp('plan'));
      $this->assertEquals('2015-05-11', $this->account->getLatestPropertyValueTimestamp('plan'));

      $this->assertNull($this->account->getProperty('plan', new DateTime('2014-08-09')));

      $this->assertEquals('M', $this->account->getProperty('plan', new DateTime('2015-05-02')));
      $this->assertEquals('L', $this->account->getProperty('plan', new DateTime('2015-05-10')));
      $this->assertEquals('XL', $this->account->getProperty('plan', new DateTime('2015-05-12')));
    }
  }