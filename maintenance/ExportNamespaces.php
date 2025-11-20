<?php

use MediaWiki\Maintenance\Maintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( !$IP ) {
	$IP = dirname( __DIR__, 3 );
}
require_once "$IP/maintenance/Maintenance.php";

class ExportNamespaces extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'output', 'File path to write JSON settings', false, true );
	}

	public function execute() {
		$outputPath = $this->getOption( 'output' );

		/** @var NamespaceManager $nsManager */
		$nsManager = $this->getServiceContainer()->getService( 'BSNamespaceManager' );
		$userNamespaces = $nsManager->getUserNamespaces( true );
		$export = [];

		foreach ( $userNamespaces as $nsId => $config ) {
			if ( $nsId < 3000 ) {
				// skip default namespaces
				continue;
			}
			$export[$nsId] = $config;
		}

		$json = json_encode(
			$export,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( $json === false ) {
			$this->output( "Failed encoding JSON\n" );
			return;
		}

		if ( !$outputPath ) {
			$this->output( $json );
			return;
		}

		if ( file_put_contents( $outputPath, $json ) === false ) {
			$this->output( "Failed writing to: $outputPath\n" );
			return;
		}

		$this->output( "Exported namespace settings to: $outputPath\n" );
	}
}

$maintClass = ExportNamespaces::class;
require_once RUN_MAINTENANCE_IF_MAIN;
