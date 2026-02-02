<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DynamicConfig\Hook\MWStakeDynamicConfigRegisterConfigsHook;

class RegisterDynamicConfig implements MWStakeDynamicConfigRegisterConfigsHook {

	/**
	 * @param HookContainer $hookContainer
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		private readonly HookContainer $hookContainer,
		private readonly ConfigFactory $configFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeDynamicConfigRegisterConfigs( array &$configs ): void {
		$config = $this->configFactory->makeConfig( 'bsg' );
		$fixedContentNamespaces = [];
		if ( $config->has( 'NamespaceManagerFixedContentNamespaces' ) ) {
			$fixedContentNamespaces = $config->get( 'NamespaceManagerFixedContentNamespaces' );
		}

		$configs[] = new NamespaceSettings( $this->hookContainer, $fixedContentNamespaces );
	}
}
