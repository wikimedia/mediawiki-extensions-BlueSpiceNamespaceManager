<?php

namespace BlueSpice\NamespaceManager\Maintenance;

use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;

require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/maintenance/Maintenance.php';

class MigrateNmSettings extends LoggedUpdateMaintenance {
	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		/** @var DynamicConfigManager $configManager */
		$configManager = MediaWikiServices::getInstance()->getService( 'MWStakeDynamicConfigManager' );
		if ( $this->isMigrated( $configManager ) ) {
			$this->output( 'New settings already migrated. Nothing to do.' );
			return true;
		}
		if ( !defined( 'BS_LEGACY_CONFIGDIR' ) ) {
			$this->output( 'BS_LEGACY_CONFIGDIR not defined. Nothing to do.' );
			return true;
		}
		$parsed = $this->parseOldSettings();
		if ( !$parsed ) {
			return true;
		}
		return $this->storeSettings( $parsed, $configManager );
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'namespace-manager-migrate-nm-settings';
	}

	/**
	 * @param DynamicConfigManager $configManager
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function isMigrated( DynamicConfigManager $configManager ) {
		$config = $configManager->getConfigObject( 'bs-namespacemanager-namespaces' );
		if ( !$config ) {
			throw new \Exception( 'Dynamic config for NamespaceManager not found' );
		}
		$data = $configManager->retrieveRaw( $config );
		return $data !== null;
	}

	/**
	 * @return array|null
	 */
	private function parseOldSettings(): ?array {
		$path = BS_LEGACY_CONFIGDIR . '/nm-settings.php';
		if ( !file_exists( $path ) ) {
			$this->output( 'Old settings file not found. Nothing to do.' );
			return null;
		}
		return $this->doParse( file_get_contents( $path ) );
	}

	/**
	 * @param string|false $source
	 *
	 * @return array|null
	 */
	private function doParse( $source ): ?array {
		if ( $source === false ) {
			$this->output( 'Could not read old settings file' );
			return null;
		}
		$consts = $this->parseConsts( $source );
		$globals = $this->parseGlobals( $source, $consts );
		return [ 'constants' => $consts, 'globals' => $globals ];
	}

	/**
	 * @param string $source
	 *
	 * @return array
	 */
	private function parseConsts( string $source ): array {
		$consts = [];
		$matches = [];
		if ( !preg_match_all( '/define\(\"(.*?)\",\s(\d{1,5})\)/', $source, $matches ) ) {
			return $consts;
		}
		foreach ( $matches[1] as $i => $name ) {
			$consts[$name] = (int)$matches[2][$i];
		}
		return $consts;
	}

	/**
	 * @param array $parsed
	 * @param DynamicConfigManager $configManager
	 *
	 * @return false
	 */
	private function storeSettings( array $parsed, DynamicConfigManager $configManager ): bool {
		$config = $configManager->getConfigObject( 'bs-namespacemanager-namespaces' );
		return $configManager->storeConfig( $config, [], serialize( $parsed ) );
	}

	/**
	 * @param string $source
	 * @param array $consts
	 *
	 * @return array
	 */
	private function parseGlobals( string $source, array $consts ) {
		$globals = [];
		$matches = [];
		if ( !preg_match_all( '/\$GLOBALS\[\'(.*?)\'\]\[(.*?)\]\s=\s(.*?);/', $source, $matches ) ) {
			return $globals;
		}
		foreach ( $matches[1] as $i => $name ) {
			if ( !isset( $globals[$name] ) ) {
				$globals[$name] = [];
			}
			$key = trim( $matches[2][$i], '"\'' );
			if ( empty( $key ) ) {
				// Indexed array
				$globals[$name][] = $this->parseValue( $matches[3][$i], $consts );
				continue;
			}
			if ( isset( $consts[$key] ) ) {
				$key = $consts[$key];
			}
			if ( $name === 'wgNamespaceAliases' ) {
				// Special case ERM36233: make sure alias is valid (issue coming from BS3)
				if ( preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%i', $key ) ) {
					$globals[$name][$key] = $this->parseValue( $matches[3][$i], $consts );
				}
			} else {
				$globals[$name][$key] = $this->parseValue( $matches[3][$i], $consts );
			}
		}
		return $globals;
	}

	/**
	 * @param string $value
	 * @param array $consts
	 *
	 * @return bool|int|mixed|string
	 */
	private function parseValue( string $value, array $consts ) {
		if ( $value === 'true' ) {
			return true;
		}
		if ( $value === 'false' ) {
			return false;
		}
		if ( is_numeric( $value ) ) {
			return (int)$value;
		}
		if ( isset( $consts[$value] ) ) {
			return $consts[$value];
		}
		return trim( $value, '"\'' );
	}
}

$maintClass = MigrateNmSettings::class;
require_once RUN_MAINTENANCE_IF_MAIN;
