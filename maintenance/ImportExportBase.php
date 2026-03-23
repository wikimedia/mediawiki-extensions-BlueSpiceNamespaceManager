<?php

use BlueSpice\NamespaceManager\NamespaceManager;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( !$IP ) {
	$IP = dirname( __DIR__, 3 );
}
require_once "$IP/maintenance/Maintenance.php";

abstract class ImportExportBase extends Maintenance {

	/**
	 * @return array
	 */
	protected function getNamespaceData(): array {
		/** @var NamespaceManager $nsManager */
		$nsManager = MediaWikiServices::getInstance()->getService( 'BSNamespaceManager' );
		$namespaces = $nsManager->getUserNamespaces( true );

		foreach ( $namespaces as $id => &$config ) {
			$extensionProps = [];
			$this->getServiceContainer()->getHookContainer()->run(
				'NamespaceManagerCollectNamespaceProperties',
				[ $id, $GLOBALS, &$extensionProps ]
			);

			if ( ExtensionRegistry::getInstance()->isLoaded( 'VisualEditor' ) ) {
				$extensionProps['visualeditor'] = !empty( $globals['wgVisualEditorAvailableNamespaces'][$id] );
			}
			if ( ExtensionRegistry::getInstance()->isLoaded( 'SemanticMediaWiki' ) ) {
				$extensionProps['smw'] = ( $globals['smwgNamespacesWithSemanticLinks'][$id] ?? false ) === true;
			}

			$config = $config + $extensionProps;
		}

		return $namespaces;
	}

}
