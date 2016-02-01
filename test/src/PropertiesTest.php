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
     * Set up teast environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->account = new Account($this->redis_client);
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        $this->account = null;

        parent::tearDown();
    }

    /**
     * Test validate property name.
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
     * Test get / set property.
     */
    public function testGetSet()
    {
        $this->account->setProperty('plan', 'M');
        $this->assertEquals('M', $this->account->getProperty('plan'));
    }

    /**
     * Don't store duplicates of the current value.
     */
    public function testDontStoreDusplicatesOfTheCurrentValue()
    {
        $this->account->setProperty('plan', 'M', new DateTime('2015-05-01'));
        $this->account->setProperty('plan', 'M', new DateTime('2015-05-02'));

        $this->assertCount(1, $this->account->getPropertyTimestamps('plan'));
    }

    /**
     * Don't store duplicates of the current value historically.
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
     * Test date specific property.
     */
    public function testDateSpecificProperty()
    {
        $this->account->setProperty('plan', 'M', new DateTime('2015-05-01'));
        $this->account->setProperty('plan', 'L', new DateTime('2015-05-07'));
        $this->account->setProperty('plan', 'XL', new DateTime('2015-05-11'));

        $this->assertEquals(['2015-05-01', '2015-05-07', '2015-05-11'], $this->account->getPropertyTimestamps('plan'));
        $this->assertEquals([
        '2015-05-01' => 'M',
        '2015-05-07' => 'L',
        '2015-05-11' => 'XL',
      ], $this->account->getPropertyHistory('plan'));

        $this->assertEquals('2015-05-01', $this->account->getOldestPropertyValueTimestamp('plan'));
        $this->assertEquals('2015-05-11', $this->account->getLatestPropertyValueTimestamp('plan'));

        $this->assertNull($this->account->getProperty('plan', new DateTime('2014-08-09')));

        $this->assertEquals('M', $this->account->getProperty('plan', new DateTime('2015-05-02')));
        $this->assertEquals('L', $this->account->getProperty('plan', new DateTime('2015-05-10')));
        $this->assertEquals('XL', $this->account->getProperty('plan', new DateTime('2015-05-12')));
    }

    /**
     * Test if before property set callback is properly called.
     */
    public function testBeforePropertySetCallback()
    {
        $this->account->setProperty('clean_version_number', '5.6.6-debian');
        $this->assertEquals('5.6.6', $this->account->getProperty('clean_version_number'));
    }

      public function testValueSerialization()
      {
          $this->account->setProperty('is_cool', true);
          $this->account->setProperty('is_not_cool', false);
          $this->account->setProperty('a_number', 12);
          $this->account->setProperty('a_string', 'abc123');
          $this->account->setProperty('a_numerical_string', '123');
          $this->account->setProperty('a_negative_numerical_string', '-123');
          $this->account->setProperty('an_array', [1, 2, 3]);

          $this->assertTrue($this->account->getProperty('is_cool'));
          $this->assertFalse($this->account->getProperty('is_not_cool'));
          $this->assertSame(12, $this->account->getProperty('a_number'));
          $this->assertSame('abc123', $this->account->getProperty('a_string'));
          $this->assertSame(123, $this->account->getProperty('a_numerical_string'));
          $this->assertSame(-123, $this->account->getProperty('a_negative_numerical_string'));
          $this->assertSame([1, 2, 3], $this->account->getProperty('an_array'));
      }
  }
