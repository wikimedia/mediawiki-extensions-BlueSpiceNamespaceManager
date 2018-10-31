<?php

namespace BlueSpice\NamespaceManager\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddBackupRevisionTable extends LoadExtensionSchemaUpdates {

	protected function doProcess() {
		$dir = $this->getExtensionPath();

		$this->updater->addExtensionTable(
			'bs_namespacemanager_backup_revision',
			"$dir/maintenance/db/bs_namespacemanager_backup_revision.sql"
		);

		$this->updater->addExtensionField(
			'bs_namespacemanager_backup_revision',
			'rev_sha1',
			"$dir/maintenance/db/bs_namespacemanager_backup_revision.patch.rev_sha1.sql"
		);
		$this->updater->addExtensionField(
			'bs_namespacemanager_backup_revision',
			'rev_content_model',
			"$dir/maintenance/db/bs_namespacemanager_backup_revision.patch2.sql"
		);
	}

	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
