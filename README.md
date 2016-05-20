# BBC\iPlayerRadio\Cache

A simple cache wrapper around Doctrine\Cache that allows us to do standard, fuzzy and stale-while-revalidate caching.

[![Build Status](https://travis-ci.org/bbc/ipr-php-cache.svg?branch=master)](https://travis-ci.org/bbc/ipr-php-cache)
[![Latest Stable Version](https://poser.pugx.org/bbc/ipr-cache/v/stable.svg)](https://packagist.org/packages/bbc/ipr-cache)
[![Total Downloads](https://poser.pugx.org/bbc/ipr-cache/downloads.svg)](https://packagist.org/packages/bbc/ipr-cache)
[![License](https://poser.pugx.org/bbc/ipr-cache/license.svg)](https://packagist.org/packages/bbc/ipr-cache)

- [Requirements](#requirements)
- [Usage](#usage)
    - [Getting Started](#getting-started)
    - [Reading Items](#reading-items)
    - [Fuzzy Caching](#fuzzy-caching)
    - [Pure Caching](#pure-caching)
    - [Stale While Revalidate Caching](#stale-while-revalidate-caching)
- [Interfaces](#interfaces)
- [Mocking the Cache](#mocking-the-cache)

## Requirements

- PHP >= 5.5
- A cache backend that Doctrine/Cache understands

## Usage

### Getting Started

Install via Composer:

```sh
$ composer require bbc/ipr-cache
```

Now you need to construct an instance of `BBC\iPlayerRadio\Cache`, with an instance of `Doctrine\Common\Cache\Cache`
passed to it. Here's a simple example:

```php
$cacheAdapter = new Doctrine\Common\Cache\ArrayCache();
$cache = new BBC\iPlayerRadio\Cache\Cache($cacheAdapter);
```

The `$cacheAdapter` is the thing that actually does the reading and writing to the cache, our library just wraps it.
Therefore we accept any of the `Doctrine\Common\Cache\*` classes, so if you're using Redis, or Memcached, or even plain
old filesystem caches, this library will work with it. (More specifically, any class implementing the 
`Doctrine\Common\Cache\Cache` interface is accepted).

### Reading Items

This library handles reading cache items slightly differently to what you might be used to. The "traditional" way, as
used by Doctrine, works something like this:

```php
if (($item = $cache->fetch($cacheKey) === false) {
    $data = '{somekey: somevalue}';
    $cache->save($cacheKey, $data, $lifetime);
}
```

This works great for a simple use-case, however if you want to be able to do things like fuzzing and
stale-while-revalidate cleanly, this API style gets clunky quickly.

Instead, this library uses a repository pattern for reading and writing items into cache. This allows us to encapsulate
the logic of the different cache modes more cleanly, even if it does look a bit strange at first!

Here's how we read an item from the cache:

```php
<?php

use BBC\iPlayerRadio\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

$cacheAdapter = new ArrayCache();
$cache = new Cache($cacheAdapter);

$cacheItem = $cache->get('cache_key');

// $cacheItem is an instance of BBC\iPlayerRadio\Cache\CacheItem and will be an object whether the item
// is present in cache or not. You can now call functions on this object to ascertain its state:

// Check if the item is expired: true if it isn't in the cache, false if it is
var_dump($cacheItem->isExpired());

// Retrieve the data you stored in the cache from the item:
$cacheData = $cacheItem->getData();
```

You can if you really want, construct a `BBC\iPlayerRadio\Cache\CacheItem` instance yourself, however the easiest
way is to simply call `$cache->get('myCacheKey');` as it'll *always* give you back a CacheItem instance, regardless of
whether it's present in the cache or not.

### "Fuzzy" Caching

Let's say you have a page section that requires five operations to build itself. If we cache all of them for the same
length of time, they'll all expire at the same moment, potentially overloading the service as it tries to rebuild
everything at once. One way of mitigating against this is to "fuzz" your cache lifetimes; adding or subtracting a random
number from any lifetime to ensure that things drop out in a more spread out way.

This is the default mode of operation for this library, all your cache times will be "fuzzed" by +/- 5% to prevent
engineering a stampede. Here's how to use it:

```php
use BBC\iPlayerRadio\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

$cache = new Cache(new ArrayCache());

// Attempt to read from the cache:
$item = $cache->get('hello_world');
if ($item->isExpired()) {
    // We don't have an item in the cache, let's rebuild!
    $data = 'This could be the result of an expensive call...';

    // Now we update the item we fetched from the cache with the data
    // and give it a new expiry time (in seconds).
    $item->setData($data);
    $item->setLifetime(60);

    // And re-store in the cache:
    $cache->save($item);
}

// Now we can make use of that data:
echo $item->getData();

```

As you can see, the cache handles the fuzzing automatically.

The default fuzz is 5%, but you can change that using setFuzz():

```php
$item = $cache->get('hello_world');
$item->setData('I am data!');
$item->setFuzz(0.1); // 10% fuzz
$cache->save($item);
```

### "Pure" Caching

What if you don't want to fuzz your lifetimes? Easy, you can use "pure" caching which essentially is just turning the
fuzzing off:

```php
use BBC\iPlayerRadio\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

$cache = new Cache(new ArrayCache());

// Attempt to read from the cache:
$item = $cache->get('hello_world');
if ($item->isExpired()) {
    $data = 'This could be the result of an expensive call...';

    $item->setData($data);
    $item->setLifetime(60);

    // This is the only difference from Fuzzy caching, we set the fuzz to 0:
    $item->setFuzz(0);

    $cache->save($item);
}
```

### Stale While Revalidate Caching

Stale while revalidate (sometimes known as "soft") caching introduces two different lifetimes for an object; it's Best
Before and it's Expires time.

Once an item has exceeded it's Best Before (and become "stale"), clients should attempt to rebuild the data. Should
that rebuild fail however, they can continue to use the stale data.

However an expires date works the same as in the other two modes, at that point it will be flushed from the caching
backend and you **must** rebuild your data or gracefully degrade.

Here's how to use stale while revalidate caching:

```php
use BBC\iPlayerRadio\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

$cache = new Cache(new ArrayCache());

$item = $cache->get('hello_world');
if ($item->isStale() || $item->isExpired()) {
    $data = someExpensiveOperation();

    if ($data) {
        // We got a good response, let's cache that:
        $item->setData($data);
        $item->setBestBefore(60); // start re-fetching after 1 minute
        $item->setLifetime(300); // flush from cache at 5 minutes

        $cache->save($item);
    }
}

// At this point, we have to re-examine the cache item to see if the data has been updated. The $item could actually
// be in any state at this point:
//
// - The item was in cache and valid, no refetch happened, you're good to go
// - The item was stale, we re-fetched successfully, you're good to go
// - The item was stale, re-fetch failed, go with the stale data
// - The item was expired, re-fetch failed, you need to do something
//
// Luckily, these four complex states can be handled simply by asking if the item is expired or not:

if ($item->isExpired()) {
    // We have no data to work with:
    gracefullyDegrade();
} else {
    // We have data from somewhere; use it!
    echo $item->getData();
}
```

#### Stale while revalidate caching and fuzzing

If you elect to use soft caching by calling setBestBefore() then the fuzzing
will be applied to the Best Before time and NOT the Expires time. This is
again to prevent a stampede as your app begins re-requesting data.

## Interfaces

When passing caches as parameters, please type hint against the CacheInterface rather than Cache explicitly:

```php
function doSomethingWithCache(BBC\iPlayerRadio\Cache\CacheInterface $cache) {

};
```

There is naturally also a `BBC\iPlayerRadio\Cache\CacheItemInterface` as well should you wish to write your own
implementation of the cache item.

## Mocking the Cache

You can easily have a mock cache instance within your unit tests by using the ArrayCache
adapter from Doctrine. This is exactly how we test the Cache class itself;

```php
$mockedCache = new Cache(new ArrayCache());
```
