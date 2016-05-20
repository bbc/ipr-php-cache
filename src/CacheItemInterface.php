<?php

namespace BBC\iPlayerRadio\Cache;

/**
 * Interface CacheItemInterface
 *
 * Represents an item either extracted from, or about to be inserted into the Cache.
 *
 * @package     BBC\iPlayerRadio\Cache
 * @author      Alex Gisby <alex.gisby@bbc.co.uk>
 */
interface CacheItemInterface
{
    /**
     * Constructor. Pass the cache key and the data to store
     *
     * @param   string  $key
     * @param   mixed   $data
     */
    public function __construct($key, $data);

    /**
     * Returns whether this item has expired or not. Inverse of isValid()
     *
     * @return  bool
     */
    public function isExpired();

    /**
     * Returns whether the cache contains data for this key
     *
     * @return  bool
     */
    public function isValid();

    /**
     * Returns the data. This will put it in a wrapper which contains the expiry
     * timestamp too
     *
     * @return  array|false
     */
    public function getData();

    /**
     * Returns the data envelope for the data we currently have
     * contained within this instance. Used for saving.
     *
     * @return  array
     */
    public function getDataEnvelope();

    /**
     * Sets data into this cache item. Used for saving an item into the cache.
     *
     * @param   mixed   $data
     * @return  $this
     */
    public function setData($data);

    /**
     * Returns this items cache key
     *
     * @return  string
     */
    public function getKey();

    /**
     * Sets this items cache key
     *
     * @param   string  $key
     * @return  mixed
     */
    public function setKey($key);

    /**
     * Sets the lifetime in seconds of this cache item
     *
     * @param   int     $lifetime
     * @return  $this
     */
    public function setLifetime($lifetime);

    /**
     * Returns the lifetime for this cache item in seconds
     *
     * @return  int
     */
    public function getLifetime();

    /**
     * Sets the best before lifetime in seconds
     *
     * @param   int     $bestBefore
     * @return  $this
     */
    public function setBestBefore($bestBefore);

    /**
     * Returns the best before lifetime
     *
     * @return  int
     */
    public function getBestBefore();

    /**
     * Sets the current fuzz level
     *
     * @param   float   $fuzz
     * @return  $this
     */
    public function setFuzz($fuzz);

    /**
     * Returns the amount of fuzz we're using.
     *
     * @return  float
     */
    public function getFuzz();

    /**
     * This is static so other classes can use it if they wish
     */
    public static function fuzzLifetime($lifetime, $fuzz);

    /**
     * Returns whether this item is "stale" or not. A stale item is good to use, but
     * efforts should be made to re-validate
     *
     * @return  bool
     */
    public function isStale();

    /**
     * Sets the timestamp of when this item was stored.
     *
     * @param   int     $timestamp
     * @return  $this
     */
    public function setStoredTime($timestamp);

    /**
     * Returns the timestamp when this item was stored.
     *
     * @return  int
     */
    public function getStoredTime();
}
