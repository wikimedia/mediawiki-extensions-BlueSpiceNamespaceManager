-- PostgreSQL database definition for table bs_namespacemanager_backup_revision in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Thomas Lorenz <lorenz@hallowelt.com>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_namespacemanager_backup_revision (
	rev_id			integer			NOT NULL,
	rev_page		integer			NOT NULL,
	rev_text_id		integer			NOT NULL,
	rev_comment		TEXT			NOT NULL,
	rev_user		integer			NOT NULL 	DEFAULT '0',
	rev_user_text	TEXT        	NOT NULL 	DEFAULT '',
	rev_timestamp	TIMESTAMPTZ		NOT NULL,
	rev_minor_edit	smallint		NOT NULL 	DEFAULT '0',
	rev_deleted		smallint		NOT NULL 	DEFAULT '0',
	rev_len			integer						DEFAULT NULL,
	rev_parent_id	integer						DEFAULT NULL,
	rev_sha1		TEXT			NOT NULL 	DEFAULT '0'
) /*$wgDBTableOptions*/;
