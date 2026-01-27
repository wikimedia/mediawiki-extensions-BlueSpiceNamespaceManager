<?php

namespace BlueSpice\NamespaceManager\HookHandler;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerBeforeSerializeSettingsHook;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\IDatabase;

class NamespaceTableUpdater implements NamespaceManagerBeforeSerializeSettingsHook {

	public function onNamespaceManagerBeforeSerializeSettings( array &$serialized ): void {
		$services = MediaWikiServices::getInstance();
		/** @var DBConnRef */
		$dbw = $services->getConnectionProvider()->getPrimaryDatabase();

		if ( !$dbw->tableExists( 'bs_namespacemanager_namespaces', __METHOD__ ) ) {
			return;
		}

		$dbw->startAtomic( __METHOD__ );

		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'bs_namespacemanager_namespaces' )
			->where( '1=1' )
			->caller( __METHOD__ )
			->execute();

		$allNamespaces = [];

		// Core namespaces
		$canonicalNamespaces = $services->getNamespaceInfo()->getCanonicalNamespaces();
		$messageCache = $services->getMessageCache();
		$contentLanguage = $services->getContentLanguage();
		foreach ( $canonicalNamespaces as $nsId => $_ ) {
			if ( $nsId === NS_MAIN ) {
				$allNamespaces[$nsId] = $messageCache->get( 'blanknamespace', false, $contentLanguage ) ?? '';
				continue;
			}

			$allNamespaces[$nsId] = $contentLanguage->getNsText( $nsId );
		}

		// Custom namespaces
		$extraNamespaces = $serialized['globals']['wgExtraNamespaces'] ?? [];
		foreach ( $extraNamespaces as $nsId => $name ) {
			$allNamespaces[$nsId] = $name;
		}

		$contentNamespaces = array_values( $serialized['globals']['wgContentNamespaces'] ?? [] );
		foreach ( $allNamespaces as $nsId => $name ) {
			$this->insertRow( $dbw, $nsId, $name, $contentNamespaces );
		}

		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * @param IDatabase $dbw
	 * @param int $nsId
	 * @param string $name
	 * @param array $contentNamespaces
	 */
	private function insertRow(
		$dbw,
		int $nsId,
		string $name,
		array $contentNamespaces
	): void {
		$dbw->newInsertQueryBuilder()
			->insertInto( 'bs_namespacemanager_namespaces' )
			->row( [
				'ns_id' => $nsId,
				'ns_name' => $name,
				'ns_is_talk' => (int)( $nsId % 2 === 1 ),
				'ns_is_content' => (int)in_array( $nsId, $contentNamespaces ),
			] )
			->caller( __METHOD__ )
			->execute();
	}

}
