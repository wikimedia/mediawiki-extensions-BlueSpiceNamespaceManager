<?php

namespace BlueSpice\NamespaceManager;

use BsNamespaceHelper;
use Config;
use Exception;
use IContextSource;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use RequestContext;
use Status;

class NamespaceManager {
	/**
	 * @var Config
	 */
	protected $config = null;

	/**
	 * @var HookContainer
	 */
	protected $hookContainer = null;

	/**
	 * @var DynamicConfigManager
	 */
	protected $configManager = null;

	/**
	 * @param Config $config
	 * @param HookContainer $hookContainer
	 * @param DynamicConfigManager $configManager
	 */
	public function __construct( Config $config, HookContainer $hookContainer, DynamicConfigManager $configManager ) {
		$this->config = $config;
		$this->hookContainer = $hookContainer;
		$this->configManager = $configManager;
	}

	/**
	 * Get all namespaces, which are created with the NamespaceManager.
	 * @param bool $fullDetails should the complete configuration of the namespaces be loaded
	 * @return array the namespace data
	 */
	public function getUserNamespaces( $fullDetails = false ) {
		$configObject = $this->configManager->getConfigObject( 'bs-namespacemanager-namespaces' );
		if ( !$configObject ) {
			return [];
		}
		$raw = $this->configManager->retrieveRaw( $configObject );
		if ( !$raw ) {
			return [];
		}
		$data = unserialize( $raw );
		$userNamespaces = array_values( $data['constants'] );

		if ( $fullDetails ) {
			$tmp = [];
			foreach ( $userNamespaces as $ns ) {
				$tmp[$ns] = [
					'content' => in_array( $ns, $this->config->get( 'ContentNamespaces' ) ),
					'subpages' => isset( $this->config->get( 'NamespacesWithSubpages' )[$ns] )
						&& $this->config->get( 'NamespacesWithSubpages' )[$ns]
				];
				if ( $ns >= 100 && isset( $this->config->get( 'ExtraNamespaces' )[$ns] ) ) {
					$tmp[$ns]['name'] = $this->config->get( 'ExtraNamespaces' )[$ns];
				}
			}

			$userNamespaces = $tmp;
		}

		return $userNamespaces;
	}

	/**
	 * DEPRECATED!
	 * Saves a given namespace configuration to bluespice-core/config/nm-settings.php
	 * @deprecated since version 4.0.2 - use ->getService( 'BSNamespaceManager' )
	 * ->updateUserNamespaces instead
	 * @param array $userNSDefinition the namespace configuration
	 * @param IContextSource $context
	 * @return array
	 */
	public function setUserNamespaces( $userNSDefinition, IContextSource $context = null ) {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		if ( !$context ) {
			$context = RequestContext::getMain();
		}
		$status = $this->updateUserNamespaces( $userNSDefinition );
		if ( $status->isGood() ) {
			return [
				'success' => true,
				'message' => $context->msg( 'bs-namespacemanager-ns-config-saved' )->plain()
			];
		}
		return [
			'success' => false,
			'message' => $context->msg( 'bs-namespacemanager-error-save-fail' )->plain()
		];
	}

	/**
	 * Persists namespace settings
	 *
	 * @param array $userNSDefinition the namespace configuration
	 * @return Status
	 */
	public function updateUserNamespaces( $userNSDefinition ) {
		$systemNamespaces = BsNamespaceHelper::getMwNamespaceConstants();
		$extension = MediaWikiServices::getInstance()->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceNamespaceManager' );
		$this->hookContainer->run( 'BSNamespaceManagerBeforeSetUsernamespaces', [
			$extension,
			&$systemNamespaces
		] );

		$constantsNames = [];
		$aliasesMap = [];
		foreach ( $userNSDefinition as $nsId => $definition ) {
			$aliasesMap[$nsId] = BsNamespaceHelper::getNamespaceAliases( $nsId );

			$name = isset( $definition['name'] ) ? $definition['name'] : null;
			$constantsNames[$nsId] = BsNamespaceHelper::getNamespaceConstName( $nsId, $name );
		}

		$data = [
			'constantsNames' => $constantsNames,
			'aliasesMap' => $aliasesMap,
			'userNSDefinition' => $userNSDefinition
		];
		try {
			$config = $this->configManager->getConfigObject( 'bs-namespacemanager-namespaces' );
			$this->configManager->storeConfig( $config, $data );
		} catch ( Exception $e ) {
			return Status::newFatal( $e->getMessage() );
		}
		return Status::newGood();
	}

	/**
	 * @param IContextSource|null $context
	 * @return array
	 */
	public function getMetaFields( IContextSource $context = null ) {
		if ( !$context ) {
			$context = RequestContext::getMain();
		}
		$metaFields = [
			[
				'name' => 'id',
				'type' => 'int',
				'sortable' => true,
				'filter' => [ 'type' => 'numeric' ],
				'label' => $context->msg( 'bs-namespacemanager-label-id' )->plain()
			],
			[
				'name' => 'name',
				'type' => 'string',
				'sortable' => true,
				'filter' => [ 'type' => 'string' ],
				'label' => $context->msg( 'bs-namespacemanager-label-namespaces' )->plain()
			],
			[
				'name' => 'pageCount',
				'type' => 'int',
				'sortable' => true,
				'filter' => [ 'type' => 'numeric' ],
				'label' => $context->msg( 'bs-namespacemanager-label-pagecount' )->plain()
			],
			[
				'name' => 'isSystemNS',
				'type' => 'boolean',
				'label' => $context->msg( 'bs-namespacemanager-label-editable' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'isTalkNS',
				'type' => 'boolean',
				'label' => $context->msg( 'bs-namespacemanager-label-istalk' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'subpages',
				'type' => 'boolean',
				'label' => $context->msg( 'bs-namespacemanager-label-subpages' )->plain(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'content',
				'type' => 'boolean',
				'label' => $context->msg( 'bs-namespacemanager-label-content' )->plain(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			]
		];

		$this->hookContainer->run( 'NamespaceManager::getMetaFields', [
			&$metaFields
		] );

		return $metaFields;
	}
}
