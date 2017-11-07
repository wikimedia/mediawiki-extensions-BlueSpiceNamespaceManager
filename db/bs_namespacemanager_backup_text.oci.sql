-- Oracle database definition for table bs_ns_bak_text in NamespaceManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>

-- @package    BlueSpice_Extensions
-- @subpackage NamespaceManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_ns_bak_text (
  old_id     NUMBER  NOT NULL,
  old_text   CLOB,
  old_flags  VARCHAR2(255)
);
