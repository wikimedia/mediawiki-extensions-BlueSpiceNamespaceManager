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

	/**
	 *
	 * @param array &$globals
	 */
	protected function doApply( &$globals ) {
		parent::doApply( $globals );
		$GLOBALS['wgExtraSignatureNamespaces'] = $GLOBALS['wgContentNamespaces'];
	}
}
