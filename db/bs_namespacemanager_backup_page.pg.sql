-- PostgreSQL database definition for table bs_namespacemanager_backup_page in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Thomas Lorenz <lorenz@hallowelt.com>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_namespacemanager_backup_page (
  page_id           integer         NOT NULL,
  page_namespace    smallint        NOT NULL,
  page_title        text            NOT NULL,
  page_restrictions text            NOT NULL,
  page_counter      bigint          NOT NULL DEFAULT '0',
  page_is_redirect  smallint        NOT NULL DEFAULT '0',
  page_is_new       smallint        NOT NULL DEFAULT '0',
  page_random       numeric(15,14)  NOT NULL,
  page_touched      timestamp with time zone,
  page_latest       integer         NOT NULL,
  page_len          integer         NOT NULL,
  titlevector       tsvector
);

