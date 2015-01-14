<?php

namespace Openclerk;

use Db\Connection;

/**
 * Naive caching <em>of strings</em>. Get the most recent cache value or recompile it using a callback.
 * Uses key/hash storage.
 */
class Cache {

  /**
   * Get the most recent database cache value for the given $key and $hash,
   * or recompile it from the $callback and $arguments if the database cache value
   * is more than $age seconds old.
   *
   * @param $db the database connection to use
   * @param $key the cache key, must be < 255 chars string
   * @param $hash must be < 32 chars string
   * @param $age the maximum age for the cache in seconds
   * @param $callback the function which will generate the content if the cache is invalidated or missing,
   *           must return less than 16 MB of content
   * @param $args the arguments to pass to the callback, if any
   */
  static function get(Connection $db, $key, $hash, $age, $callback, $args = array()) {
    if (strlen($hash) > 255) {
      throw new CacheException("Cannot cache with a key longer than 255 characters");
    }
    if (strlen($hash) > 32) {
      throw new CacheException("Cannot cache with a hash longer than 32 characters");
    }

    $q = $db->prepare("SELECT * FROM cached_strings WHERE cache_key=? AND cache_hash=? AND created_at >= DATE_SUB(NOW(), INTERVAL $age SECOND)");
    $q->execute(array($key, $hash));
    if ($cache = $q->fetch()) {
      $result = $cache['content'];
    } else {
      $result = call_user_func_array($callback, $args);
      $q = $db->prepare("DELETE FROM cached_strings WHERE cache_key=? AND cache_hash=?");
      $q->execute(array($key, $hash));

      if (strlen($result) >= pow(2, 24)) {
        throw new CacheException("Cache value is too large (> 16 MB)");
      }

      $q = $db->prepare("INSERT INTO cached_strings SET cache_key=?, cache_hash=?, content=?");
      $q->execute(array($key, $hash, $result));
    }

    return $result;
  }

}
