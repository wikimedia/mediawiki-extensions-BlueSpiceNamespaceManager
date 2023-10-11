<?php

namespace BlueSpice\NamespaceManager\HookHandler;

use BlueSpice\NamespaceManager\GlobalActionsAdministration;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsAdministration',
			[
				'special-bluespice-namespacemanager' => [
					'factory' => static function () {
						return new GlobalActionsAdministration();
					}
				]
			]
		);
	}
}
