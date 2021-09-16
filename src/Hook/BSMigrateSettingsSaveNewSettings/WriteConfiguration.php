<?php

namespace BlueSpice\NamespaceManager\Hook\BSMigrateSettingsSaveNewSettings;

use BlueSpice\Hook\BSMigrateSettingsSaveNewSettings;

class WriteConfiguration extends BSMigrateSettingsSaveNewSettings {

	/**
	 * At this point, all extensions should have updated their config variables.
	 * This will read the nm-settings, and rewrite it, with updated configs.
	 *
	 */
	protected function doProcess() {
		$nsManager = $this->getServices()->getService( 'BSNamespaceManager' );
		$userNamespaces = $nsManager->getUserNamespaces( true );
		$nsManager->setUserNamespaces( $userNamespaces );
	}
}
