<?php

namespace BlueSpice\NamespaceManager\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddBackupPageTable extends LoadExtensionSchemaUpdates {

	protected function doProcess() {
		$dir = $this->getExtensionPath();

		$this->updater->addExtensionTable(
			'bs_namespacemanager_backup_page',
			"$dir/maintenance/db/bs_namespacemanager_backup_page.sql"
		);

		$this->updater->addExtensionField(
			'bs_namespacemanager_backup_page',
			'page_content_model',
			"$dir/maintenance/db/bs_namespacemanager_backup_page.patch.sql"
		);
	}

	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
