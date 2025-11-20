<?php

namespace BlueSpice\NamespaceManager;

use BsNamespaceHelper;
use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Status\Status;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;

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
			$userNamespaces = [];
			$globals = $data['globals'];

			foreach ( $globals['wgExtraNamespaces'] as $id => $name ) {
				$base = [
					'name' => $name,
					'alias' => $this->getNamespaceAlias( $id ),
					'subpages' => !empty( $globals['wgNamespacesWithSubpages'][$id] ),
					'content' => in_array( $id, $globals['wgContentNamespaces'] ?? [] ),
				];

				$extensionProps = [];
				$this->hookContainer->run(
					'NamespaceManagerCollectNamespaceProperties',
					[ $id, $globals, &$extensionProps ]
				);

				if ( ExtensionRegistry::getInstance()->isLoaded( 'VisualEditor' ) ) {
					$extensionProps['visualeditor'] = !empty( $globals['wgVisualEditorAvailableNamespaces'][$id] );
				}
				if ( ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
					$extensionProps['smw'] = ( $globals['smwgNamespacesWithSemanticLinks'][$id] ?? false ) === true;
				}

				$userNamespaces[$id] = $base + $extensionProps;
			}
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
	public function setUserNamespaces( $userNSDefinition, ?IContextSource $context = null ) {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		if ( !$context ) {
			$context = RequestContext::getMain();
		}
		$status = $this->updateUserNamespaces( $userNSDefinition );
		if ( $status->isGood() ) {
			return [
				'success' => true,
				'message' => $context->msg( 'bs-namespacemanager-ns-config-saved' )->text()
			];
		}
		return [
			'success' => false,
			'message' => $context->msg( 'bs-namespacemanager-error-save-fail' )->text()
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
			$alias = $definition['alias'] ?? '';
			if ( $alias ) {
				$aliasesMap[$nsId] = [ $alias ];
				$this->setNamespaceAlias( $alias, $nsId );
			} else {
				$aliasesMap[$nsId] = BsNamespaceHelper::getNamespaceAliases( $nsId );
			}

			$name = $definition['name'] ?? null;
			$constantsNames[$nsId] = BsNamespaceHelper::getNamespaceConstName( $nsId, $name, true );
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
	public function getMetaFields( ?IContextSource $context = null ) {
		if ( !$context ) {
			$context = RequestContext::getMain();
		}
		$metaFields = [
			[
				'name' => 'subpages',
				'type' => 'boolean',
				'label' => $context->msg( 'bs-namespacemanager-label-subpages' )->text(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'content',
				'type' => 'boolean',
				'label' => $context->msg( 'bs-namespacemanager-label-content' )->text(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			]
		];

		$this->hookContainer->run( 'NamespaceManager::getMetaFields', [
			&$metaFields
		] );

		return $metaFields;
	}

	/**
	 * @param string $alias
	 * @param int $nsId
	 */
	public function setNamespaceAlias( string $alias, int $nsId ): void {
		foreach ( $GLOBALS['wgNamespaceAliases'] as $existingAlias => $id ) {
			if ( $id === $nsId ) {
				unset( $GLOBALS['wgNamespaceAliases'][$existingAlias] );
			}
		}
		$GLOBALS['wgNamespaceAliases'][$alias] = $nsId;
	}

	/**
	 * @param int $nsId
	 * @return string
	 */
	protected function getNamespaceAlias( int $nsId ): string {
		foreach ( $GLOBALS['wgNamespaceAliases'] as $alias => $id ) {
			if ( $id === $nsId ) {
				return $alias;
			}
		}
		return '';
	}

}
