<?php

/**
 * NamespaceManager extension for BlueSpice
 *
 * Administration interface for adding, editing and deleting namespaces
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://www.bluespice.com
 *
 * @author     Sebastian Ulbricht
 * @author     Stefan Widmann <widmann@hallowelt.com>
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @version    2.23.2
 * @package    Bluespice_Extensions
 * @subpackage NamespaceManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

/**
 * Class for namespace management assistent
 * @package BlueSpice_Extensions
 * @subpackage WikiAdmin
 */
class NamespaceManager extends BsExtensionMW {

	private $_aDefaultNamespaceSettings = array(
		'content' => false,
		'subpages' => true,
		'searched' => false
	);

	public static $aSortConditions = array(
		'sort' => '',
		'dir' => ''
	);

	public function __construct() {
		wfProfileIn( 'BS::NamespaceManager::__construct' );
		WikiAdmin::registerModule( 'NamespaceManager', [
			'image' => '/extensions/BlueSpiceExtensions/WikiAdmin/resources/images/bs-btn_namespaces_v1.png',
			'level' => 'wikiadmin',
			'message' => 'bs-namespacemanager-label',
			'iconCls' => 'bs-icon-register-box',
			'permissions' => [ 'namespacemanager-viewspecialpage' ],
		]);
		wfProfileOut( 'BS::NamespaceManager::__construct' );
	}

	/**
	 * Initialization of NamespaceManager extension
	 */
	public function initExt() {
		wfProfileIn( 'BS::'.__METHOD__ );

		BsConfig::registerVar( 'MW::NamespaceManager::NsOffset', 2999, BsConfig::TYPE_INT, BsConfig::LEVEL_PRIVATE );

		$this->mCore->registerPermission( 'namespacemanager-viewspecialpage', array( 'sysop' ), array( 'type' => 'global' ) );

		$this->setHook( 'NamespaceManager::editNamespace', 'onEditNamespace', true );
		$this->setHook( 'NamespaceManager::writeNamespaceConfiguration', 'onWriteNamespaceConfiguration', true );

		//CR, RBV: This is suposed to return all constants! Not just system NS.
		//At the moment the implementation relies on an hardcoded mapping,
		//which is bad. We need to change this and make it more generic!
		$GLOBALS['bsSystemNamespaces'] = BsNamespaceHelper::getMwNamespaceConstants();

		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * extension.json callback
	 * @global array $bsgConfigFiles
	 */
	public static function onRegistration() {
		global $bsgConfigFiles;
		$bsgConfigFiles['NamespaceManager']
			= BSCONFIGDIR . DS . 'nm-settings.php';
	}

	/**
	* Add the sql file to database by executing the update.php
	* @global type $wgDBtype
	* @global array $wgExtNewTables
	* @param DatabaseUpdater $du
	* @return boolean
	*/
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		global $wgExtPGNewFields, $wgDBtype;
		$dir = __DIR__ . '/' . 'db' . '/';

		if ( $wgDBtype == 'oracle' ) {
			$updater->addExtensionTable(
				'bs_ns_bak_page',
				$dir . 'bs_namespacemanager_backup_page.sql'
			);
			$updater->addExtensionTable(
				'bs_ns_bak_revision',
				$dir . 'bs_namespacemanager_backup_revision.sql'
			);
			$updater->addExtensionTable(
				'bs_ns_bak_text',
				$dir . 'bs_namespacemanager_backup_text.sql'
			);
		} else {
			$updater->addExtensionTable(
				'bs_namespacemanager_backup_page',
				$dir . 'bs_namespacemanager_backup_page.sql'
			);
			$updater->addExtensionTable(
				'bs_namespacemanager_backup_revision',
				$dir . 'bs_namespacemanager_backup_revision.sql'
			);
			$updater->addExtensionTable(
				'bs_namespacemanager_backup_text',
				$dir . 'bs_namespacemanager_backup_text.sql'
			);
		}

		if ( $wgDBtype == 'postgres' ) {
			$wgExtPGNewFields[] = array(
				'bs_namespacemanager_backup_page',
				'page_content_model',
				$dir . 'bs_namespacemanager_backup_page.patch.pg.sql'
			);
			$wgExtPGNewFields[] = array(
				'bs_namespacemanager_backup_revision',
				'rev_sha1',
				$dir . 'bs_namespacemanager_backup_revision.patch.rev_sha1.pg.sql'
			);
			$wgExtPGNewFields[] = array(
				'bs_namespacemanager_backup_revision',
				'rev_content_model',
				$dir . 'bs_namespacemanager_backup_revision.patch2.pg.sql'
			);
		} elseif ( $wgDBtype != 'sqlite' ) { /* Do not apply patches to sqlite */
			$updater->addExtensionField(
				'bs_namespacemanager_backup_page',
				'page_content_model',
				$dir . 'bs_namespacemanager_backup_page.patch.sql'
			);
			$updater->addExtensionField(
				'bs_namespacemanager_backup_revision',
				'rev_sha1',
				$dir . 'bs_namespacemanager_backup_revision.patch.rev_sha1.sql'
			);
			$updater->addExtensionField(
				'bs_namespacemanager_backup_revision',
				'rev_content_model',
				$dir . 'bs_namespacemanager_backup_revision.patch2.sql'
			);
		}

		return true;
	}

	/**
	 * Hook-Handler for NamespaceManager::editNamespace
	 * @return boolean Always true to kepp hook alive
	 */
	public function onEditNamespace( &$aNamespaceDefinition, &$iNs, $aAdditionalSettings, $bUseInternalDefaults ) {
		if ( !$bUseInternalDefaults ) {
			if ( empty( $aNamespaceDefinition[$iNs] ) ) {
				$aNamespaceDefinition[$iNs] = array();
			}
			$aNamespaceDefinition[$iNs] += array(
				'content'  => $aAdditionalSettings['content'],
				'subpages' => $aAdditionalSettings['subpages'],
				'searched' => $aAdditionalSettings['searched'] );
		} else {
			$aNamespaceDefinition[$iNs] += $this->_aDefaultNamespaceSettings;
		}
		return true;
	}

	public function onWriteNamespaceConfiguration( &$sSaveContent, $sConstName, $iNs, $aDefinition ) {
		if ( isset( $aDefinition[ 'content' ] ) && $aDefinition['content'] === true ) {
			$sSaveContent .= "\$GLOBALS['wgContentNamespaces'][] = {$sConstName};\n";
		}
		if ( isset( $aDefinition[ 'subpages' ] ) && $aDefinition['subpages'] === true ) {
			$sSaveContent .= "\$GLOBALS['wgNamespacesWithSubpages'][{$sConstName}] = true;\n";
		}
		if ( isset( $aDefinition[ 'searched' ] ) && $aDefinition['searched'] === true ) {
			$sSaveContent .= "\$GLOBALS['wgNamespacesToBeSearchedDefault'][{$sConstName}] = true;\n";
		}
		return true;
	}

	/**
	 * Get all namespaces, which are created with the NamespaceManager.
	 * @param boolean $bFullDetails should the complete configuration of the namespaces be loaded
	 * @return array the namespace data
	 */
	public static function getUserNamespaces( $bFullDetails = false ) {
		global $wgExtraNamespaces, $wgNamespacesWithSubpages,
			$wgContentNamespaces, $wgNamespacesToBeSearchedDefault,
			$bsgConfigFiles;

		if ( !file_exists( $bsgConfigFiles['NamespaceManager'] ) ) {
			return array();
		}
		$sConfigContent = file_get_contents( $bsgConfigFiles['NamespaceManager'] );
		$aUserNamespaces = array();
		if ( preg_match_all( '%define\("NS_([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)", ([0-9]*)\)%s', $sConfigContent, $aMatches, PREG_PATTERN_ORDER ) ) {
			$aUserNamespaces = $aMatches[ 2 ];
		}
		if ( $bFullDetails ) {
			$aTmp = array();
			foreach ( $aUserNamespaces as $iNS ) {
				$aTmp[$iNS] = array(
					'content' => in_array( $iNS, $wgContentNamespaces ),
					'subpages' => ( isset( $wgNamespacesWithSubpages[$iNS] ) && $wgNamespacesWithSubpages[$iNS] ),
					'searched' => ( isset( $wgNamespacesToBeSearchedDefault[$iNS] ) && $wgNamespacesToBeSearchedDefault[$iNS] )
				);
				if ( $iNS >= 100 ) {
					$aTmp[$iNS]['name'] = $wgExtraNamespaces[$iNS];
				}
			}

			$aUserNamespaces = $aTmp;
		}

		return $aUserNamespaces;
	}

	/**
	 * Saves a given namespace configuration to bluespice-core/config/nm-settings.php
	 * @param array $aUserNamespaceDefinition the namespace configuration
	 */
	public static function setUserNamespaces( $aUserNamespaceDefinition ) {
		global $bsSystemNamespaces, $bsgConfigFiles;

		$oNamespaceManager = BsExtensionManager::getExtension( 'NamespaceManager' );
		Hooks::run( 'BSNamespaceManagerBeforeSetUsernamespaces', array( $oNamespaceManager, &$bsSystemNamespaces ) );

		$sSaveContent = "<?php\n\n";
		foreach ( $aUserNamespaceDefinition as $iNS => $aDefinition ) {
			$bIsSystemNs = false;
			if ( isset( $bsSystemNamespaces[$iNS] ) ) {
				$bIsSystemNs = true;
			}

			if ( $aDefinition ) {
				$sConstName = NamespaceManager::getNamespaceConstName( $iNS, $aDefinition );

				$sSaveContent .= "// START Namespace {$sConstName}\n";
				$sSaveContent .= "if( !defined( \"{$sConstName}\" ) ) define(\"{$sConstName}\", {$iNS});\n";
				if ( $iNS >= 100 ) {
					$sSaveContent .= "\$GLOBALS['wgExtraNamespaces'][{$sConstName}] = '" . $aDefinition['name'] . "';\n";
				}

				Hooks::run( 'NamespaceManager::writeNamespaceConfiguration', array( &$sSaveContent, $sConstName, $iNS, $aDefinition ) );
				if ( !$bIsSystemNs && isset( $aDefinition['alias'] ) && $aDefinition['alias'] ) {
					$sSaveContent .= "\$GLOBALS['wgNamespaceAliases']['{$aDefinition['alias']}'] = {$sConstName};\n";
				}
				$sSaveContent .= "// END Namespace {$sConstName}\n\n";
			}
		}

		$res = file_put_contents( $bsgConfigFiles['NamespaceManager'], $sSaveContent );

		if ( $res ) {
			return array(
				'success' => true,
				'message' => wfMessage( 'bs-namespacemanager-ns-config-saved' )->plain()
			);
		}
		return array(
			'success' => false,
			'message' => wfMessage( 'bs-namespacemanager-error-ns-config-not-saved' , $bsgConfigFiles['NamespaceManager'] )->plain()
		);
	}

	public static function getNamespaceConstName( $iNS, $aDefinition ) {
		global $bsSystemNamespaces;

		$sConstName = '';

		// find existing NS_ definitions
		$aNSConstants = array();
		foreach ( get_defined_constants() as $key => $value ) {
			if ( strpos( $key, "NS_" ) === 0
				// ugly solution to identify smw namespaces as they don't adhere to the convention
				|| strpos( $key, "SMW_NS_" ) === 0
				|| strpos( $key, "SF_NS_" ) === 0
				) {
				$aNSConstants[$key] = $value;
			}
		}

		$aNSConstants = array_flip( $aNSConstants );

		// Use existing constant name if possible
		if ( isset( $aNSConstants[$iNS] ) ) {
			$sConstName = $aNSConstants[$iNS];
		} else {
			// If compatible, use namespace name as const name
			if ( preg_match(  "/^[a-zA-Z0-9_]{3,}$/", $aDefinition['name'] ) ) {
				$sConstName = 'NS_' . strtoupper( $aDefinition['name'] );
			} else {
				// Otherwise use namespace number
				$sConstName = 'NS_' . $iNS;
			}
		}

		return $sConstName;
	}

	/**
	 * UnitTestsList allows registration of additional test suites to execute
	 * under PHPUnit. Extensions can append paths to files to the $paths array,
	 * and since MediaWiki 1.24, can specify paths to directories, which will
	 * be scanned recursively for any test case files with the suffix "Test.php".
	 * @param array $paths
	 */
	public static function onUnitTestsList ( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';
		return true;
	}
}
