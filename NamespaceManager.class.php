<?php

/**
 * NamespaceManager extension for BlueSpice
 *
 * Administration interface for adding, editing and deleting namespaces
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
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
 * @package    Bluespice_Extensions
 * @subpackage NamespaceManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

/**
 * Class for namespace management assistent
 * @package BlueSpice_Extensions
 * @subpackage WikiAdmin
 */
class NamespaceManager extends BsExtensionMW {

	private static $_aDefaultNamespaceSettings = [
		'content' => false,
		'subpages' => true
	];

	public static $aSortConditions = [
		'sort' => '',
		'dir' => ''
	];

	/**
	 * Initialization of NamespaceManager extension
	 */
	public function initExt() {
		//CR, RBV: This is suposed to return all constants! Not just system NS.
		//At the moment the implementation relies on an hardcoded mapping,
		//which is bad. We need to change this and make it more generic!
		$GLOBALS['bsSystemNamespaces'] = BsNamespaceHelper::getMwNamespaceConstants();
	}

	/**
	 * extension.json callback
	 * @global array $bsgConfigFiles
	 */
	public static function onRegistration() {
		global $bsgConfigFiles;
		$bsgConfigFiles['NamespaceManager']
			= BSCONFIGDIR . '/nm-settings.php';
	}

	/**
	 * Hook-Handler for NamespaceManager::editNamespace
	 * @return boolean Always true to keep hook alive
	 */
	public static function onEditNamespace( &$aNamespaceDefinition, &$iNs, $aAdditionalSettings, $bUseInternalDefaults ) {
		if ( !$bUseInternalDefaults ) {
			if ( empty( $aNamespaceDefinition[$iNs] ) ) {
				$aNamespaceDefinition[$iNs] = [];
			}
			$aNamespaceDefinition[$iNs] += [
				'content'  => $aAdditionalSettings['content'],
				'subpages' => $aAdditionalSettings['subpages']
			];
		} else {
			$aNamespaceDefinition[$iNs] += static::$_aDefaultNamespaceSettings;
		}
		return true;
	}

	/**
	 * Hook-Handler for NamespaceManager::writeNamespaceConfiguration
	 * @return boolean Always true to keep hook alive
	 */
	public static function onWriteNamespaceConfiguration( &$sSaveContent, $sConstName, $iNs, $aDefinition ) {
		if ( isset( $aDefinition[ 'content' ] ) && $aDefinition['content'] === true ) {
			$sSaveContent .= "\$GLOBALS['wgContentNamespaces'][] = {$sConstName};\n";
		}
		if ( isset( $aDefinition[ 'subpages' ] ) ) {
			$stringVal = $aDefinition['subpages'] ? "true" : "false";
			$sSaveContent .= "\$GLOBALS['wgNamespacesWithSubpages'][{$sConstName}] = $stringVal;\n";
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
			$wgContentNamespaces, $bsgConfigFiles;

		if ( !file_exists( $bsgConfigFiles['NamespaceManager'] ) ) {
			return [];
		}
		$sConfigContent = file_get_contents( $bsgConfigFiles['NamespaceManager'] );
		$aUserNamespaces = [];
		$aMatches = [];
		if ( preg_match_all( '%define\("NS_([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)", ([0-9]*)\)%s', $sConfigContent, $aMatches, PREG_PATTERN_ORDER ) ) {
			$aUserNamespaces = $aMatches[ 2 ];
		}
		if ( $bFullDetails ) {
			$aTmp = [];
			foreach ( $aUserNamespaces as $iNS ) {
				$aTmp[$iNS] = [
					'content' => in_array( $iNS, $wgContentNamespaces ),
					'subpages' => ( isset( $wgNamespacesWithSubpages[$iNS] ) && $wgNamespacesWithSubpages[$iNS] )
				];
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

		$oNamespaceManager =
			\MediaWiki\MediaWikiServices::getInstance()
			->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceNamespaceManager' );
		Hooks::run( 'BSNamespaceManagerBeforeSetUsernamespaces', [ $oNamespaceManager, &$bsSystemNamespaces ] );

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

				Hooks::run( 'NamespaceManager::writeNamespaceConfiguration', [ &$sSaveContent, $sConstName, $iNS, $aDefinition ] );
				if ( isset( $aDefinition['alias'] ) && !empty( $aDefinition['alias'] ) ) {
					$sSaveContent .= "\$GLOBALS['wgNamespaceAliases']['{$aDefinition['alias']}'] = {$sConstName};\n";
				}
				$sSaveContent .= "// END Namespace {$sConstName}\n\n";
			}
		}

		$res = file_put_contents( $bsgConfigFiles['NamespaceManager'], $sSaveContent );

		if ( $res ) {
			return [
				'success' => true,
				'message' => wfMessage( 'bs-namespacemanager-ns-config-saved' )->plain()
			];
		}
		return [
			'success' => false,
			'message' => wfMessage( 'bs-namespacemanager-error-ns-config-not-saved' , $bsgConfigFiles['NamespaceManager'] )->plain()
		];
	}

	public static function getNamespaceConstName( $iNS, $aDefinition ) {

		$sConstName = '';

		// find existing NS_ definitions
		$aNSConstants = [];
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

	public static function getMetaFields() {
		$aMetaFields = [
			[
				'name' => 'id',
				'type' => 'int',
				'sortable' => true,
				'filter' => [ 'type' => 'numeric' ],
				'label' => wfMessage( 'bs-namespacemanager-label-id' )->plain()
			],
			[
				'name' => 'name',
				'type' => 'string',
				'sortable' => true,
				'filter' => [ 'type' => 'string' ],
				'label' => wfMessage( 'bs-namespacemanager-label-namespaces' )->plain()
			],
			[
				'name' => 'pageCount',
				'type' => 'int',
				'sortable' => true,
				'filter' => [ 'type' => 'numeric' ],
				'label' => wfMessage( 'bs-namespacemanager-label-pagecount' )->plain()
			],
			[
				'name' => 'isSystemNS',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-editable' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'isTalkNS',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-istalk' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'subpages',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-subpages' )->plain(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'content',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-content' )->plain(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			]
		];

		Hooks::run( 'NamespaceManager::getMetaFields', [ &$aMetaFields ] );

		return $aMetaFields;
	}

}
