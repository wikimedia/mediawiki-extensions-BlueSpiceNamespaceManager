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
 * For further information visit https://bluespice.com
 *
 * @author     Sebastian Ulbricht
 * @author     Stefan Widmann <widmann@hallowelt.com>
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage NamespaceManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

use BlueSpice\DynamicSettingsManager;
use MediaWiki\MediaWikiServices;

/**
 * Class for namespace management assistent
 * @package BlueSpice_Extensions
 * @subpackage WikiAdmin
 */
class NamespaceManager extends BsExtensionMW {

	/**
	 *
	 * @var array
	 */
	private static $defaultNamespaceSettings = [
		'content' => false,
		'subpages' => true
	];

	/**
	 *
	 * @var array
	 */
	public static $aSortConditions = [
		'sort' => '',
		'dir' => ''
	];

	/**
	 * Hook-Handler for NamespaceManager::editNamespace
	 * @param array &$aNamespaceDefinition
	 * @param int &$iNs
	 * @param array $aAdditionalSettings
	 * @param bool $bUseInternalDefaults
	 * @return bool Always true to keep hook alive
	 */
	public static function onEditNamespace( &$aNamespaceDefinition, &$iNs, $aAdditionalSettings,
		$bUseInternalDefaults ) {
		if ( !$bUseInternalDefaults ) {
			if ( empty( $aNamespaceDefinition[$iNs] ) ) {
				$aNamespaceDefinition[$iNs] = [];
			}
			$aNamespaceDefinition[$iNs] += [
				'content'  => $aAdditionalSettings['content'],
				'subpages' => $aAdditionalSettings['subpages']
			];
		} else {
			$aNamespaceDefinition[$iNs] += static::$defaultNamespaceSettings;
		}
		return true;
	}

	/**
	 * Hook-Handler for NamespaceManager::writeNamespaceConfiguration
	 * @param string &$sSaveContent
	 * @param string $sConstName
	 * @param int $iNs
	 * @param array $aDefinition
	 * @return bool Always true to keep hook alive
	 */
	public static function onWriteNamespaceConfiguration( &$sSaveContent, $sConstName,
		$iNs, $aDefinition ) {
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
	 * @param bool $bFullDetails should the complete configuration of the namespaces be loaded
	 * @return array the namespace data
	 */
	public static function getUserNamespaces( $bFullDetails = false ) {
		global $wgExtraNamespaces, $wgNamespacesWithSubpages,
			$wgContentNamespaces;

		$dynamicSettingsManager = DynamicSettingsManager::factory();
		$sConfigContent = $dynamicSettingsManager->fetch( 'NamespaceManager' );
		$aUserNamespaces = [];
		$aMatches = [];
		$match = preg_match_all(
			'%define\("NS_([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)", ([0-9]*)\)%s',
			$sConfigContent,
			$aMatches,
			PREG_PATTERN_ORDER
		);
		if ( $match ) {
			$aUserNamespaces = $aMatches[ 2 ];
		}
		if ( $bFullDetails ) {
			$aTmp = [];
			foreach ( $aUserNamespaces as $iNS ) {
				$aTmp[$iNS] = [
					'content' => in_array( $iNS, $wgContentNamespaces ),
					'subpages' => ( isset( $wgNamespacesWithSubpages[$iNS] ) && $wgNamespacesWithSubpages[$iNS] )
				];
				if ( $iNS >= 100 && isset( $wgExtraNamespaces[$iNS] ) ) {
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
	 * @return array
	 */
	public static function setUserNamespaces( $aUserNamespaceDefinition ) {
		$systemNamespaces = BsNamespaceHelper::getMwNamespaceConstants();
		$oNamespaceManager = MediaWikiServices::getInstance()->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceNamespaceManager' );
		Hooks::run(
			'BSNamespaceManagerBeforeSetUsernamespaces', [ $oNamespaceManager, &$systemNamespaces ]
		);

		$sSaveContent = "<?php\n\n";
		foreach ( $aUserNamespaceDefinition as $iNS => $aDefinition ) {
			if ( empty( $aDefinition ) ) {
				continue;
			}

			$name = isset( $aDefinition['name'] ) ? $aDefinition['name'] : null;
			$sConstName = BsNamespaceHelper::getNamespaceConstName( $iNS, $name );

			$sSaveContent .= "// START Namespace {$sConstName}\n";
			$sSaveContent .= "if( !defined( \"{$sConstName}\" ) ) define(\"{$sConstName}\", {$iNS});\n";

			if ( $iNS >= 100 && isset( $aDefinition['name'] ) && $aDefinition['name'] !== '' ) {
				$sSaveContent
					.= "\$GLOBALS['wgExtraNamespaces'][{$sConstName}] = '{$aDefinition['name']}';\n";
			}

			Hooks::run( 'NamespaceManager::writeNamespaceConfiguration', [
				&$sSaveContent,
				$sConstName,
				$iNS,
				$aDefinition
			] );
			if ( isset( $aDefinition['alias'] ) ) {
				if ( !empty( $aDefinition['alias'] ) ) {
					$sSaveContent .= "\$GLOBALS['wgNamespaceAliases']['{$aDefinition['alias']}'] = {$sConstName};\n";
				}
			} else {
				$aliases = BsNamespaceHelper::getNamespaceAliases( $iNS );
				if ( !empty( $aliases ) ) {
					$sSaveContent .= "\$GLOBALS['wgNamespaceAliases']['{$aliases[0]}'] = {$sConstName};\n";
				}
			}
			$sSaveContent .= "// END Namespace {$sConstName}\n\n";
		}

		$dynamicSettingsManager = DynamicSettingsManager::factory();
		$status = $dynamicSettingsManager->persist( 'NamespaceManager', $sSaveContent );
		$res = $status->isGood();
		if ( $res ) {
			return [
				'success' => true,
				'message' => wfMessage( 'bs-namespacemanager-ns-config-saved' )->plain()
			];
		}
		return [
			'success' => false,
			'message' => wfMessage(
				'bs-namespacemanager-error-ns-config-not-saved', 'nm-settings.php'
			)->plain()
		];
	}

	/**
	 * @return array
	 * @throws FatalError
	 * @throws MWException
	 */
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
