<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings;
use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DynamicConfig\Hook\MWStakeDynamicConfigRegisterConfigsHook;

class RegisterDynamicConfig implements MWStakeDynamicConfigRegisterConfigsHook {

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeDynamicConfigRegisterConfigs( array &$configs ): void {
		$configs[] = new NamespaceSettings( $this->hookContainer );
	}
}
