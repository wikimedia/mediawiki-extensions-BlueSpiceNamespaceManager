<?php

$IP = dirname( dirname( dirname( __DIR__ ) ) );
require_once "$IP/maintenance/Maintenance.php";

class BSNamespaceManagerRemoveLegacyBackupTables extends LoggedUpdateMaintenance {

	private $tableNames = [
		'bs_namespacemanager_backup_page',
		'bs_namespacemanager_backup_revision',
		'bs_namespacemanager_backup_text',
	];

	/**
	 *
	 * @return true
	 */
	protected function doDBUpdates() {
		$db = $this->getDB( DB_PRIMARY );
		foreach ( $this->tableNames as $table ) {
			$this->output( "\n * delete $table... " );
			if ( !$db->tableExists( $table ) ) {
				$this->output( "does not exist => OK" );
				continue;
			}
			$this->output( "exist, deleting ... " );
			$res = $db->dropTable( $table, __METHOD__ );
			if ( !$res ) {
				$this->output( "FAILED" );
				continue;
			}
			$this->output( "OK" );
		}
		$this->output( "\n\n" );
		return true;
	}

	/**
	 *
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_namespacemanager-removelegacybackuptables';
	}

}
