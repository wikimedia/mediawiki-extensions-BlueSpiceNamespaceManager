-- Oracle database definition for table bs_ns_bak_page in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_ns_bak_page (
  page_id            NUMBER        NOT NULL,
  page_namespace     NUMBER       DEFAULT 0 NOT NULL,
  page_title         VARCHAR2(255)           NOT NULL,
  page_restrictions  VARCHAR2(255),
  page_counter       NUMBER         DEFAULT 0 NOT NULL,
  page_is_redirect   CHAR(1)           DEFAULT '0' NOT NULL,
  page_is_new        CHAR(1)           DEFAULT '0' NOT NULL,
  page_random        NUMBER(15,14) NOT NULL,
  page_touched       TIMESTAMP(6) WITH TIME ZONE,
  page_latest        NUMBER        DEFAULT 0 NOT NULL, -- FK?
  page_len           NUMBER        DEFAULT 0 NOT NULL,
  page_content_model VARCHAR2(32)
);

