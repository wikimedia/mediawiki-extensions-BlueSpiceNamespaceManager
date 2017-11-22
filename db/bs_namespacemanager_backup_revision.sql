-- Database definition for table bs_namespacemanager_backup_revision in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
-- @filesource

CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_namespacemanager_backup_revision (
  rev_id         int(10)    unsigned NOT NULL,
  rev_page       int(10)    unsigned NOT NULL,
  rev_text_id    int(10)    unsigned NOT NULL,
  rev_comment    tinyblob            NOT NULL,
  rev_user       int(10)    unsigned NOT NULL DEFAULT '0',
  rev_user_text  varbinary(255)      NOT NULL DEFAULT '',
  rev_timestamp  binary(14)          NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  rev_minor_edit tinyint(3) unsigned NOT NULL DEFAULT '0',
  rev_deleted    tinyint(3) unsigned NOT NULL DEFAULT '0',
  rev_len        int(10)    unsigned DEFAULT NULL,
  rev_parent_id  int(10)    unsigned DEFAULT NULL
) /*$wgDBTableOptions*/;