<?php

namespace BlueSpice\NamespaceManager\HookHandler\LoadExtensionSchemaUpdatesHook;

use BlueSpice\NamespaceManager\Maintenance\PopulateNamespaces;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dbType = $updater->getDB()->getType();
		$dir = dirname( __DIR__, 3 );

		$updater->addExtensionTable(
			'bs_namespacemanager_namespaces',
			"$dir/db/$dbType/bs_namespacemanager_namespaces.sql"
		);

		$updater->addPostDatabaseUpdateMaintenance( PopulateNamespaces::class );
	}

}
