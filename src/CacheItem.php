<?php

namespace BBC\iPlayerRadio\Cache;

/**
 * Class CacheItem
 *
 * Represents an item either extracted from, or about to be inserted into the Cache.
 *
 * @package     BBC\iPlayerRadio\Cache
 * @author      Alex Gisby <alex.gisby@bbc.co.uk>
 */
class CacheItem implements CacheItemInterface
{
    /**
     * @var     string  Cache key
     */
    protected $key;

    /**
     * @var     mixed   Cache data
     */
    protected $data = false;

    /**
     * @var     int     Lifetime in seconds (default 1 minute)
     */
    protected $lifetime = 60;

    /**
     * @var     int|null    Best before lifetime
     */
    protected $bestBefore = null;

    /**
     * @var     int     Timestamp that this item was stored to cache
     */
    protected $storedTime = null;

    /**
     * @var     float   Fuzz amount as a percentage (default 5% or 0.05)
     */
    protected $fuzz = 0.05;

    /**
     * Constructor. Pass the cache key and the data read from the cache
     *
     * @param   string  $key
     * @param   mixed   $dataEnvelope
     */
    public function __construct($key, $dataEnvelope = false)
    {
        $this->key = $key;

        if (!is_bool($dataEnvelope) && is_array($dataEnvelope)) {
            if (array_key_exists('payload', $dataEnvelope)) {
                $this->setData($dataEnvelope['payload']);
            } else {
                $this->setData($dataEnvelope);
            }

            if (array_key_exists('bestBefore', $dataEnvelope)) {
                $this->setBestBefore($dataEnvelope['bestBefore']);
            }

            if (array_key_exists('storedTime', $dataEnvelope)) {
                $this->setStoredTime($dataEnvelope['storedTime']);
            }
        } else {
            $this->setData($dataEnvelope);
        }
    }

    /**
     * Returns whether this item has expired or not. Inverse of isValid()
     *
     * @return  bool
     */
    public function isExpired()
    {
        return $this->data === false;
    }

    /**
     * Returns whether the cache contains data for this key
     *
     * @return  bool
     */
    public function isValid()
    {
        return !$this->isExpired();
    }

    /**
     * Returns the data. This will put it in a wrapper which contains the expiry
     * timestamp too
     *
     * @return  array|false
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns the data envelope for the data we currently have
     * contained within this instance
     *
     * @return  array
     */
    public function getDataEnvelope()
    {
        return array(
            'bestBefore'    => $this->bestBefore,
            'storedTime'    => $this->storedTime,
            'payload'       => $this->data
        );
    }

    /**
     * Sets data into this cache item. Used for saving an item into the cache.
     *
     * @param   mixed   $data
     * @return  $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Returns this items cache key
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets this items cache key
     *
     * @param   string  $key
     * @return  mixed
     */
    public function setKey($key)
    {
        $this->key = (string)$key;
        return $this;
    }

    /**
     * Sets the lifetime in seconds of this cache item
     *
     * @param   int     $lifetime
     * @return  $this
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = (int)$lifetime;
        return $this;
    }

    /**
     * Returns the lifetime for this cache item in seconds
     *
     * @return  int
     */
    public function getLifetime()
    {
        // only fuzz if there's no best before date:
        return ($this->bestBefore === null)?
                    self::fuzzLifetime($this->lifetime, $this->fuzz)
                    : $this->lifetime;
    }

    /**
     * Sets the best before lifetime in seconds
     *
     * @param   int     $bestBefore
     * @return  $this
     */
    public function setBestBefore($bestBefore)
    {
        $this->bestBefore = $bestBefore;
        return $this;
    }

    /**
     * Returns the best before lifetime
     *
     * @return  int
     */
    public function getBestBefore()
    {
        return ($this->bestBefore !== null)?
                self::fuzzLifetime($this->bestBefore, $this->fuzz)
                : $this->bestBefore;
    }

    /**
     * Sets the current fuzz level
     *
     * @param   float   $fuzz
     * @return  $this
     */
    public function setFuzz($fuzz)
    {
        $this->fuzz = $fuzz;
        return $this;
    }

    /**
     * Returns the amount of fuzz we're using.
     *
     * @return  float
     */
    public function getFuzz()
    {
        return $this->fuzz;
    }

    /**
     * This is static so other classes can use it if they wish
     */
    public static function fuzzLifetime($lifetime, $fuzz = 0.05)
    {
        $change = mt_rand(0, ceil($lifetime * $fuzz));
        return (mt_rand(0, 1))? $lifetime - $change : $lifetime + $change;
    }

    /**
     * Returns whether this item is "stale" or not. A stale item is good to use, but
     * efforts should be made to re-validate
     *
     * @return  bool
     */
    public function isStale()
    {
        // If we have no bestBefore or storedTime:
        if ($this->bestBefore === null || $this->storedTime === null) {
            return ($this->data === false);
        }

        $staleAt = $this->storedTime + $this->bestBefore;
        if ($staleAt > time()) {
            return false;
        }
        return true;
    }

    /**
     * Sets the timestamp of when this item was stored.
     *
     * @param   int     $timestamp
     * @return  $this
     */
    public function setStoredTime($timestamp)
    {
        $this->storedTime = $timestamp;
        return $this;
    }

    /**
     * Returns the timestamp when this item was stored.
     *
     * @return  int
     */
    public function getStoredTime()
    {
        return $this->storedTime;
    }
}
