-- Oracle database definition for table bs_ns_bak_revision in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_ns_bak_revision (
  rev_id          NUMBER      NOT NULL,
  rev_page        NUMBER      NOT NULL,
  rev_text_id     NUMBER          NULL,
  rev_comment     VARCHAR2(255),
  rev_user        NUMBER      DEFAULT 0 NOT NULL,
  rev_user_text   VARCHAR2(255)         NOT NULL,
  rev_timestamp   TIMESTAMP(6) WITH TIME ZONE  NOT NULL,
  rev_minor_edit  CHAR(1)         DEFAULT '0' NOT NULL,
  rev_deleted     CHAR(1)         DEFAULT '0' NOT NULL,
  rev_len         NUMBER          NULL,
  rev_parent_id   NUMBER      	   DEFAULT NULL
);

