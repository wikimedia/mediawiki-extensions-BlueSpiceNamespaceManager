<?php

use BlueSpice\NamespaceManager\NamespaceManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( !$IP ) {
	$IP = dirname( __DIR__, 3 );
}
require_once "$IP/maintenance/Maintenance.php";

class ImportNamespaces extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'mediawiki-xml', 'Path to MediaWiki XML dump', false, true );
		$this->addOption(
			'json',
			'Path to JSON file with additional namespace settings
			Expected format: {
				"3000": { "name": "myName", "subpages": true, ... },
				"3001": { ... }
			}',
			false,
			true
		);
	}

	public function execute() {
		$xmlPath = $this->getOption( 'mediawiki-xml' );
		$jsonPath = $this->getOption( 'json' );

		if ( !$xmlPath && !$jsonPath ) {
			$this->output( 'No file provided' );
			return;
		}

		// XML
		if ( $xmlPath && !file_exists( $xmlPath ) ) {
			$this->output( "XML file not found: $xmlPath" );
			return;
		}

		$namespacesFromXml = [];
		if ( $xmlPath ) {
			$namespacesFromXml = $this->parseXml( $xmlPath );
		}

		if ( $jsonPath && !file_exists( $jsonPath ) ) {
			$this->output( "JSON file not found: $jsonPath" );
			return;
		}

		// JSON
		$jsonDecoded = [];
		if ( $jsonPath ) {
			$json = file_get_contents( $jsonPath );

			if ( !$json ) {
				$this->output( 'JSON file read failure' );
				return;
			}

			$jsonDecoded = json_decode( $json, true );
			if ( !$jsonDecoded ) {
				$this->output( 'JSON cannot be decoded' );
				return;
			}
		}

		$namespaces = [];
		foreach ( $namespacesFromXml as $ns ) {
			$namespaces[$ns['key']] = $ns['name'];
		}
		foreach ( $jsonDecoded as $nsId => $settings ) {
			$namespaces[$nsId] = $settings['name'] ?? $namespaces[$nsId] ?? '';
		}

		$namespaceList = [];
		foreach ( $namespaces as $key => $name ) {
			$namespaceList[] = [
				'key' => $key,
				'name' => $name
			];
		}

		$this->appendNamespaces( $namespaceList, $jsonDecoded ?? [] );
	}

	/**
	 * Parse MediaWiki XML dump for custom namespaces ≥ 3000
	 */
	private function parseXml( string $xmlPath ): array {
		$xml = simplexml_load_file( $xmlPath );
		if ( !$xml ) {
			$this->output( 'Failed interpreting XML file' );
		}
		$xml->registerXPathNamespace( 'mw', 'http://www.mediawiki.org/xml/export-0.11/' );

		$namespaces = [];
		foreach ( $xml->xpath( '//mw:siteinfo/mw:namespaces/mw:namespace' ) as $ns ) {
			$key = (int)$ns['key'];
			if ( $ns['key'] < 3000 ) {
				// skip default namespaces
				continue;
			}
			$namespaces[] = [ 'key' => $key, 'name' => (string)$ns ];
		}

		$this->output( 'Found ' . count( $namespaces ) . ' custom namespaces ≥ 3000: ' . json_encode( $namespaces ) . "\n" );
		return $namespaces;
	}

	/**
	 * Append new namespaces to existing userNamespaces and update via BSNamespaceManager.
	 *
	 * @param array $namespaces Array of ['key' => int, 'name' => string]
	 * @param array $settingsMap Settings indexed by namespace key, e.g.:
	 *     [
	 *         3000 => ["name" => "myName"],
	 *         3001 => ["content" => true]
	 *     ]
	 */
	private function appendNamespaces( array $namespaces, array $settingsMap = [] ): void {
		/** @var NamespaceManager $nsManager */
		$nsManager = MediaWikiServices::getInstance()->getService( 'BSNamespaceManager' );
		$userNamespaces = $nsManager->getUserNamespaces( true );

		foreach ( $namespaces as $ns ) {
			$key = $ns['key'];
			$name = $ns['name'];

			if ( isset( $userNamespaces[$key] ) ) {
				$this->output( "Namespace $name ($key) already exists, skipping\n" );
				continue;
			}

			$metaFields = $nsManager->getMetaFields( RequestContext::getMain() );

			$defaults = [
				'name' => $name,
				'alias' => '',
			];

			foreach ( $metaFields as $field ) {
				$fieldName = $field['name'];

				switch ( $fieldName ) {
					case 'subpages':
					case 'content':
						$defaults[$fieldName] = true;
						break;

					default:
						$defaults[$fieldName] = false;
						break;
				}
			}

			$customSettings = $settingsMap[$key] ?? [];
			$userNamespaces[$key] = array_merge( $defaults, $customSettings );

			$this->output( "Prepared namespace: $name ($key) for import\n" );
		}

		$status = $nsManager->updateUserNamespaces( $userNamespaces );
		if ( $status->isGood() ) {
			$this->output( "Namespaces imported successfully\n" );
		} else {
			$this->output( "Error importing namespaces: {$status->getMessage()}\n" );
		}
	}

}

$maintClass = ImportNamespaces::class;
require_once RUN_MAINTENANCE_IF_MAIN;
