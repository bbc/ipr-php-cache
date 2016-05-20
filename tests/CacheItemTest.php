<?php

namespace BBC\iPlayerRadio\Cache\Tests;

use PHPUnit_Framework_TestCase;
use BBC\iPlayerRadio\Cache\CacheItem;

class CacheItemTest extends PHPUnit_Framework_TestCase
{
    public function testConstructWithRawData()
    {
        $i = new CacheItem('cacheKey', 'I am data...');
        $i->setFuzz(0);
        $this->assertInstanceOf('BBC\\iPlayerRadio\\Cache\\CacheItem', $i);
        $this->assertEquals('I am data...', $i->getData());
        $this->assertEquals(null, $i->getBestBefore());
        $this->assertEquals(60, $i->getLifetime());
    }

    public function testConstructWithEnvelope()
    {
        $i = new CacheItem('cacheKey', array(
            'bestBefore' => 30,
            'payload' => 'I am a data object',
        ));
        $i->setFuzz(0);
        $this->assertEquals(30, $i->getBestBefore());
        $this->assertEquals('I am a data object', $i->getData());
        $this->assertEquals(60, $i->getLifetime());
    }

    public function testGetSetData()
    {
        $i = new CacheItem('cacheKey', 'Data');
        $this->assertEquals('Data', $i->getData());
        $this->assertEquals($i, $i->setData(27));
        $this->assertEquals(27, $i->getData());
    }

    public function testGetSetKey()
    {
        $i = new CacheItem('cacheKey', 'Data');
        $this->assertEquals('cacheKey', $i->getKey());
        $this->assertEquals($i, $i->setKey('newKey'));
        $this->assertEquals('newKey', $i->getKey());
    }

    public function testIsValid()
    {
        $i = new CacheItem('key', false);
        $this->assertFalse($i->isValid());

        $i = new CacheItem('key', null);
        $this->assertTrue($i->isValid());

        $i = new CacheItem('key', 'string');
        $this->assertTrue($i->isValid());

        $i = new CacheItem('key', 27);
        $this->assertTrue($i->isValid());

        $i = new CacheItem('key', array()); // this loosely evals to false, but is still a valid response.
        $this->assertTrue($i->isValid());
    }

    public function testFuzzLifetime()
    {
        $inRange = true;
        $borkedValue = null;
        $spread = array();
        for ($i = 0; $i < 1000; $i ++) {
            $ttl = CacheItem::fuzzLifetime(100, 0.05);

            // Add to the spread;
            $spread[$ttl] = (isset($spread[$ttl]))? $spread[$ttl] + 1 : 1;

            if ($ttl < 95 || $ttl > 105) {
                $inRange = false;
                $borkedValue = $ttl;
                break;
            }
        }
        $this->assertTrue($inRange, 'range was broken with value: '.$borkedValue);

        // And to ensure we have a spread, over 100 iterations, all of the range
        // should have values.
        $this->assertCount(11, array_keys($spread), 'not enough spread of values');
    }

    public function testSetGetExpiresNoFuzzing()
    {
        $i = new CacheItem('key');
        $i->setLifetime(120);
        $i->setFuzz(0);
        $this->assertEquals(120, $i->getLifetime());
    }

    public function testSetGetFuzz()
    {
        $i = new CacheItem('cacheKey');
        $this->assertEquals(0.05, $i->getFuzz());
        $this->assertEquals($i, $i->setFuzz(0.1), '$this is not returned from setFuzz()');
        $this->assertEquals(0.1, $i->getFuzz());
    }

    public function testSetGetBestBeforeNoFuzzing()
    {
        $i = new CacheItem('key');
        $i->setFuzz(0); // disable fuzzing.
        $this->assertNull($i->getBestBefore(), 'default best before is null');
        $this->assertEquals($i, $i->setBestBefore(30), '$this is not returned from setBestBefore()');
        $this->assertEquals(30, $i->getBestBefore());
    }

    public function testFuzzingBestBeforeAndNotExpires()
    {
        $i = new CacheItem('key');

        // If we only set the expires AND the bestBefore, expires should not be fuzzed and best before
        // should be:
        $i->setBestBefore(100)
            ->setLifetime(200)
            ->setFuzz(0.05);

        $bbValues = array();
        $expValues = array();
        for ($j = 0; $j < 100; $j ++) {
            $bb = $i->getBestBefore();
            $exp = $i->getLifetime();

            $bbValues[$bb] = (array_key_exists($bb, $bbValues))? $bbValues[$bb] + 1 : 1;
            $expValues[$exp] = (array_key_exists($exp, $expValues))? $expValues[$exp] + 1 : 1;
        }

        $this->assertCount(1, $expValues, 'Only a single expires value should return');
        $this->assertTrue(count($bbValues) > 1, 'multiple values for best before');
    }

    public function testSetGetBestBeforeFuzzing()
    {
        $i = new CacheItem('key');
        $this->assertNull($i->getBestBefore(), 'default best before is null');
        $this->assertEquals($i, $i->setBestBefore(100));

        // Run this 10 times to verify that the fuzzing occurred.
        $values = array();
        for ($j = 0; $j < 100; $j ++) {
            $bb = $i->getBestBefore();
            $values[$bb] = (array_key_exists($bb, $values))? $values[$bb] + 1 : 1;
        }

        $this->assertTrue(count($values) > 1, 'more than one best before returned, fuzzing ahoy');
    }

    public function testSetGetStoredTime()
    {
        $i = new CacheItem('key');
        $ts = time();

        $this->assertEquals(null, $i->getStoredTime());
        $this->assertEquals($i, $i->setStoredTime($ts));
        $this->assertEquals($ts, $i->getStoredTime());
    }

    public function testIsStaleWhenStaleWithBB()
    {
        $i = new CacheItem('key');
        $i->setBestBefore(30);
        $i->setStoredTime(strtotime('-4 minutes'));
        $this->assertTrue($i->isStale());
    }

    public function testIsStaleWhenFreshWithBB()
    {
        $i = new CacheItem('key');
        $i->setBestBefore(30);
        $i->setStoredTime(time());
        $this->assertFalse($i->isStale());
    }

    public function testIsStaleNoBB()
    {
        // If you don't explicitly set it, bestBefore is considered to be
        // the expires time. Therefore, if you have data, it should be considered
        // "Fresh"
        $i = new CacheItem('key');
        $i->setData('I am data');
        $i->setStoredTime(time());
        $this->assertFalse($i->isStale());

        // However, if you do not set data, or data is false, then the item
        // is considered expired, which is also stale.

        $i = new CacheItem('key');
        $i->setStoredTime(time());
        $this->assertTrue($i->isStale());

        $i = new CacheItem('key');
        $i->setStoredTime(time());
        $i->setData(false);
        $this->assertTrue($i->isStale());
    }

    public function testIsStaleNoStoredTime()
    {
        // Without a stored time, it's impossible to know whether
        // this item is fresh or not. Err on the side of caution and
        // declare it stale.

        $i = new CacheItem('key');
        $this->assertTrue($i->isStale());

        // Adding data with no stored time and no BB. This should be
        // considered "fresh" as we have likely only just fetched data.

        $i = new CacheItem('key');
        $i->setData('I am data');
        $this->assertFalse($i->isStale());

        // However, if the data set is FALSE, we should consider stale
        // as this equates to the cache returning an "empty" response:

        $i = new CacheItem('key');
        $i->setData(false);
        $this->assertTrue($i->isStale());

        // Adding a bestBefore doesn't change anything:
        $i = new CacheItem('key');
        $i->setBestBefore(30);
        $i->setData('I am data');
        $this->assertFalse($i->isStale());

        $i = new CacheItem('key');
        $i->setBestBefore(30);
        $i->setData(false);
        $this->assertTrue($i->isStale());
    }
}
