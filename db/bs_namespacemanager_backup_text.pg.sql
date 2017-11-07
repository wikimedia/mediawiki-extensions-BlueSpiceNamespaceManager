-- PostgreSQL database definition for table bs_namespacemanager_backup_text in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Thomas Lorenz <lorenz@hallowelt.com>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_namespacemanager_backup_text (
  old_id    int     NOT NULL,
  old_text  text,
  old_flags text,
  textvector tsvector
);
