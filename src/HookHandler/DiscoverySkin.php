<?php

namespace BlueSpice\NamespaceManager\HookHandler;

use BlueSpice\NamespaceManager\GlobalActionsManager;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class DiscoverySkin implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsManager',
			[
				'special-bluespice-namespacemanager' => [
					'factory' => static function () {
						return new GlobalActionsManager();
					}
				]
			]
		);
	}
}
