-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: sql/abstractSchemaChanges/patch-oldimage-oi_size_to_bigint.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
ALTER TABLE /*_*/oldimage
  CHANGE oi_size oi_size BIGINT UNSIGNED DEFAULT 0 NOT NULL;
