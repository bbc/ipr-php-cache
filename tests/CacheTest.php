<?php

namespace BBC\iPlayerRadio\Cache\Tests;

use PHPUnit_Framework_TestCase;
use BBC\iPlayerRadio\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\RedisCache;

class CacheTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $cache = new Cache(new ArrayCache());
        $this->assertInstanceOf('BBC\\iPlayerRadio\\Cache\\Cache', $cache);
    }

    public function testSetGetAdapter()
    {
        $cache = new Cache(new ArrayCache());
        $adapter = new RedisCache();

        $this->assertEquals($cache, $cache->setAdapter($adapter), '$this is not returned when setting.');
        $this->assertEquals($adapter, $cache->getAdapter(), 'adapter does not match');
    }

    public function testGetWhenCacheEmpty()
    {
        $cache = new Cache(new ArrayCache());
        $response = $cache->get('cacheKey');
        $this->assertFalse($response->isValid());
        $this->assertFalse($response->getData());
    }

    public function testSave()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter);

        $data = 'Hello World! I am data!';

        $item = $cache->get('cacheKey');
        $item->setData($data);

        $this->assertEquals($cache, $cache->save($item), '$this is not returned when saving');

        // We directly check that the adapter received the data:
        $this->assertTrue($adapter->contains('cacheKey'), 'data was not stored in cache');
        // Verify the data was stored:
        $itemInCache = $adapter->fetch('cacheKey');
        $this->assertTrue(is_array($itemInCache));
        $this->assertArrayHasKey('storedTime', $itemInCache);
        $this->assertArrayHasKey('bestBefore', $itemInCache);
        $this->assertNull($itemInCache['bestBefore']);
    }

    public function testSaveWithBestBefore()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter);

        $data = 'Hello World! I am data!';

        $item = $cache->get('cacheKey');
        $item->setData($data);
        $item->setBestBefore(30);

        $this->assertEquals($cache, $cache->save($item), '$this is not returned when saving');

        // We directly check that the adapter received the data:
        $this->assertTrue($adapter->contains('cacheKey'), 'data was not stored in cache');
        // Verify the data was stored:
        $itemInCache = $adapter->fetch('cacheKey');
        $this->assertTrue(is_array($itemInCache));
        $this->assertArrayHasKey('storedTime', $itemInCache);
        $this->assertArrayHasKey('bestBefore', $itemInCache);
        $this->assertEquals(30, $itemInCache['bestBefore']);
    }

    public function testGetWithData()
    {
        $cache = new Cache(new ArrayCache());

        $data = 'Hello world! I am data!';

        $item = $cache->get('cacheKey');
        $item->setData($data);
        $cache->save($item);

        $response = $cache->get('cacheKey');
        $this->assertTrue($response->isValid());
        $this->assertEquals($data, $response->getData());
    }

    public function testHasKey()
    {
        $cache = new Cache(new ArrayCache());

        $item = $cache->get('cacheKey');
        $item->setData('Hello, I am data');
        $cache->save($item);

        $this->assertTrue($cache->hasKey('cacheKey'), 'cacheKey is reported not present, when it is.');
        $this->assertFalse($cache->hasKey('unknownKey'), 'unknownKey reported as present, when it is not');
    }

    public function testDelete()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter);

        // Save and verify:
        $item = $cache->get('cacheKey');
        $item->setData('Hello, I am data');
        $cache->save($item);

        $this->assertTrue($cache->hasKey('cacheKey'));

        // Now delete that key and verify it's gone from both the store, and
        // the class:
        $this->assertEquals($cache, $cache->delete('cacheKey'), '$this is not returned from delete()');
        $this->assertFalse($cache->hasKey('cacheKey'), 'hasKey() reports that cacheKey remains.');
        $this->assertFalse($adapter->contains('cacheKey'), 'data is still in the adapter');
    }

    /* --------------- Cache Prefixing ------------------ */

    public function testSetGetPrefix()
    {
        $cache = new Cache(new ArrayCache());
        $this->assertEquals('', $cache->getPrefix());

        $cache = new Cache(new ArrayCache(), 'mycache_');
        $this->assertEquals('mycache_', $cache->getPrefix());

        $this->assertEquals($cache, $cache->setPrefix('johnnycache_'));
        $this->assertEquals('johnnycache_', $cache->getPrefix());
    }

    public function testCacheSaveWithPrefix()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter, 'mycache_');

        $item = $cache->get('cacheKey');
        $item->setData('Hello, I am data');
        $item->setLifetime(20);
        $cache->save($item);

        $this->assertTrue($adapter->contains('mycache_cacheKey'), 'prefix has not been applied.');
    }

    public function testCacheHasKeyWithPrefix()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter, 'mycache_');

        $item = $cache->get('cacheKey');
        $item->setData('Hello, I am data');
        $item->setLifetime(20);
        $cache->save($item);

        $this->assertTrue($adapter->contains('mycache_cacheKey'), 'prefix has not been applied.');
        $this->assertTrue($cache->hasKey('cacheKey'));
    }

    public function testCacheReadWithPrefix()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter, 'mycache_');

        $adapter->save('mycache_cacheKey', 'Hello, I am data', 20);

        $item = $cache->get('cacheKey');
        $this->assertTrue($item->isValid());
        $this->assertEquals('Hello, I am data', $item->getData());
    }

    public function testCacheDeleteWithPrefix()
    {
        $adapter = new ArrayCache();
        $cache = new Cache($adapter, 'mycache_');

        $adapter->save('mycache_cacheKey', 'Hello, I am data', 20);

        $cache->delete('cacheKey');
        $this->assertFalse($adapter->contains('mycache_cacheKey'));
    }
}
