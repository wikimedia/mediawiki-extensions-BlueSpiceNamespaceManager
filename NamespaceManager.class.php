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
use BlueSpice\NamespaceManager\SettingsComposer;
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
	public static $aSortConditions = [
		'sort' => '',
		'dir' => ''
	];

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
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSNamespaceManagerBeforeSetUsernamespaces',
			[
				$oNamespaceManager,
				&$systemNamespaces
			]
		);

		$constantsNames = [];
		$aliasesMap = [];
		foreach ( $aUserNamespaceDefinition as $nsId => $definition ) {
			$aliasesMap[$nsId] = BsNamespaceHelper::getNamespaceAliases( $nsId );

			$name = isset( $definition['name'] ) ? $definition['name'] : null;
			$constantsNames[$nsId] = BsNamespaceHelper::getNamespaceConstName( $nsId, $name );
		}

		$settingsComposer = new SettingsComposer( $constantsNames, $aliasesMap );
		$sSaveContent = $settingsComposer->compose( $aUserNamespaceDefinition );

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

		MediaWikiServices::getInstance()->getHookContainer()->run(
			'NamespaceManager::getMetaFields',
			[
				&$aMetaFields
			]
		);

		return $aMetaFields;
	}

}
