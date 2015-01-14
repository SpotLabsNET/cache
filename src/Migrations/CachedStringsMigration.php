<?php

namespace Openclerk\Migrations;

class CachedStringsMigration extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE cached_strings (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,

      cache_key varchar(255) not null,
      cache_hash varchar(32) not null,

      content mediumblob not null, /* up to 16 MB */

      UNIQUE(cache_key, cache_hash)
    );");
    return $q->execute();
  }

}
