<?php

namespace BlueSpice\NamespaceManager\Maintenance;

use BlueSpice\NamespaceManager\HookHandler\NamespaceTableUpdater;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;

class PopulateNamespaces extends LoggedUpdateMaintenance {

	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		$contentNamespaces = MediaWikiServices::getInstance()->getNamespaceInfo()
			->getContentNamespaces();

		$serialized = [
			'globals' => [
				'wgExtraNamespaces' => [],
				'wgContentNamespaces' => $contentNamespaces,
			]
		];

		$updater = new NamespaceTableUpdater();
		$updater->onNamespaceManagerBeforeSerializeSettings( $serialized );
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'namespace-manager-populate-namespaces';
	}

}
