-- Database definition for table bs_namespacemanager_backup_page in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
-- @filesource

CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_namespacemanager_backup_page (
  page_id            int(10)    unsigned NOT NULL,
  page_namespace     int(11)             NOT NULL,
  page_title         varbinary(255)      NOT NULL,
  page_restrictions  tinyblob            NOT NULL,
  page_counter       bigint(20) unsigned NOT NULL DEFAULT '0',
  page_is_redirect   tinyint(3) unsigned NOT NULL DEFAULT '0',
  page_is_new        tinyint(3) unsigned NOT NULL DEFAULT '0',
  page_random        double     unsigned NOT NULL,
  page_touched       binary(14)          NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  page_latest        int(10)    unsigned NOT NULL,
  page_len           int(10)    unsigned NOT NULL
) /*$wgDBTableOptions*/;
