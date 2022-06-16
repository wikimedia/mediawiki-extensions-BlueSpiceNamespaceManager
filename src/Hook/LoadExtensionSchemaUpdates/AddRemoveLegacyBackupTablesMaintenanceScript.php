<?php

namespace BlueSpice\NamespaceManager\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddRemoveLegacyBackupTablesMaintenanceScript extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$this->updater->addPostDatabaseUpdateMaintenance( \BSNamespaceManagerRemoveLegacyBackupTables::class );
		return true;
	}

}
