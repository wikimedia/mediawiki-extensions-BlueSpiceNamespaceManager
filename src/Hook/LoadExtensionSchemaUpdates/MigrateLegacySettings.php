<?php

namespace BlueSpice\NamespaceManager\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;
use BlueSpice\NamespaceManager\Maintenance\MigrateNmSettings;

class MigrateLegacySettings extends LoadExtensionSchemaUpdates {

	/**
	 * @return bool
	 */
	protected function doProcess() {
		$this->updater->addPostDatabaseUpdateMaintenance( MigrateNmSettings::class );
		return true;
	}

}
