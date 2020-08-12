<?php

namespace BlueSpice\NamespaceManager\DynamicSettings;

use BlueSpice\DynamicSettings\BSConfigDirSettingsFile;

class NmSettings extends BSConfigDirSettingsFile {

	/**
	 *
	 * @inheritDoc
	 */
	protected function getFilename() {
		return 'nm-settings.php';
	}
}
