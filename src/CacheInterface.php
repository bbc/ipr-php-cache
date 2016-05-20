<?php

namespace BBC\iPlayerRadio\Cache;

use Doctrine\Common\Cache\Cache as DoctrineCacheInterface;

/**
 * Interface CacheInterface
 *
 * @package     BBC\iPlayerRadio\Cache
 * @author      Alex Gisby <alex.gisby@bbc.co.uk>, Ben Scott <ben.scott@bbc.co.uk>
 */
interface CacheInterface
{
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
    public function get($key);

    /**
     * Stores an item in the cache.
     *
     * @param   CacheItemInterface   $item
     * @return  bool
     */
    public function save(CacheItemInterface $item);

    /**
     * Sets the cache adapter to use.
     *
     * @param   DoctrineCacheInterface $adapter Cache adapter to use.
     * @return  $this
     */
    public function setAdapter(DoctrineCacheInterface $adapter);

    /**
     * Returns the cache adapter we're using on this instance.
     *
     * @return DoctrineCacheInterface
     */
    public function getAdapter();

    /**
     * Returns whether the cache has a given key or not.
     *
     * @param   string $key
     * @return  bool
     */
    public function hasKey($key);

    /**
     * Deletes an item from the cache.
     *
     * @param   string $key Keyname to delete
     * @return  $this
     */
    public function delete($key);
}
