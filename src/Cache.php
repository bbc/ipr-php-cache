<?php

namespace BBC\iPlayerRadio\Cache;

use Doctrine\Common\Cache\Cache as DoctrineCacheInterface;

/**
 * Class Cache
 *
 * Simple cache wrapper that provides fuzzy and stale-while-revalidate caching
 *
 * @package     BBC\iPlayerRadio\Cache
 * @author      Alex Gisby <alex.gisby@bbc.co.uk>, Ben Scott <ben.scott@bbc.co.uk>
 */
class Cache implements CacheInterface
{
    /**
     * @var     DoctrineCacheInterface
     */
    protected $adapter;

    /**
     * @var     string
     */
    protected $prefix = '';

    /**
     * Pass in the adapter that does the read/write.
     *
     * @param   DoctrineCacheInterface  $adapter
     * @param   string                  $prefix
     */
    public function __construct(DoctrineCacheInterface $adapter, $prefix = '')
    {
        $this->adapter = $adapter;
        $this->prefix = $prefix;
    }

    /**
     * Sets the prefix to use when reading, writing and deleting cache objects.
     *
     * @param   string  $prefix
     * @return  $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Returns the cache prefix we're using for reading, writing and deleting.
     *
     * @return  string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Retrieves an item from the cache. If that item doesn't exist, the
     * cache will return an empty CacheItemInterface object for you to then
     * populate and pass to save().
     *
     * Subclasses should override this function to provide custom CacheItemInterface
     * instances.
     *
     * @param   string      $key
     * @return  CacheItemInterface
     */
    public function get($key)
    {
        return new CacheItem(
            $key,
            $this->adapter->fetch($this->prefix.$key)
        );
    }

    /**
     * Stores an item in the cache.
     *
     * @param   CacheItemInterface   $item
     * @return  $this
     */
    public function save(CacheItemInterface $item)
    {
        $item->setStoredTime(time());
        $this->adapter->save(
            $this->prefix.$item->getKey(),
            $item->getDataEnvelope(),
            $item->getLifetime()
        );
        return $this;
    }

    /**
     * Sets the cache adapter to use.
     *
     * @param   DoctrineCacheInterface $adapter Cache adapter to use.
     * @return  $this
     */
    public function setAdapter(DoctrineCacheInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Returns the cache adapter we're using on this instance.
     *
     * @return DoctrineCacheInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Returns whether the cache has a given key or not.
     *
     * @param   string $key
     * @return  bool
     */
    public function hasKey($key)
    {
        return $this->adapter->contains($this->prefix.$key);
    }

    /**
     * Deletes an item from the cache.
     *
     * @param   string $key Keyname to delete
     * @return  $this
     */
    public function delete($key)
    {
        $this->adapter->delete($this->prefix.$key);
        return $this;
    }
}
