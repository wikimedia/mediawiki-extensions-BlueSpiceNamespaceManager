<?php

namespace BlueSpice\NamespaceManager\DynamicConfig;

use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DynamicConfig\IDynamicConfig;

class NamespaceSettings implements IDynamicConfig {

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'bs-namespacemanager-namespaces';
	}

	/**
	 * @param string $serialized
	 *
	 * @return bool
	 */
	public function apply( string $serialized ): bool {
		$unserialized = unserialize( $serialized );
		if ( $unserialized === null ) {
			return false;
		}
		foreach ( $unserialized['constants'] ?? [] as $constName => $nsId ) {
			if ( !defined( $constName ) ) {
				define( $constName, $nsId );
			}
		}
		foreach ( $unserialized['globals'] ?? [] as $global => $value ) {
			$baseValue = $GLOBALS[$global] ?? [];
			if ( $this->isAssoc( $baseValue, $value ) ) {
				// Use new value as the first array, so that it overrides defaults
				$value = $value + $baseValue;
				ksort( $value );
			} elseif ( is_array( $value ) && is_array( $baseValue ) ) {
				$value = array_values( array_unique( array_merge( $baseValue, $value ) ) );
			}
			$GLOBALS[$global] = $value;
		}

		$GLOBALS['wgExtraSignatureNamespaces'] = $GLOBALS['wgContentNamespaces'] ?? [];
		return true;
	}

	/**
	 * @param array|null $additionalData
	 *
	 * @return string
	 */
	public function serialize( ?array $additionalData = [] ): string {
		$constantNames = $additionalData['constantsNames'] ?? [];
		$aliases = $additionalData['aliasesMap'] ?? [];
		$namespaceDefinition = $additionalData['userNSDefinition'] ?? [];

		$globals = [
			'wgExtraNamespaces' => [],
			'wgNamespaceAliases' => $GLOBALS['wgNamespaceAliases'] ?? [],
		];
		$serialized = [ 'constants' => [] ];

		foreach ( $namespaceDefinition as $nsId => $definition ) {
			if ( empty( $definition ) || !is_int( $nsId ) ) {
				continue;
			}

			$constName = $constantNames[$nsId];
			$serialized['constants'][$constName] = $nsId;

			if ( $nsId >= 100 && isset( $definition['name'] ) && $definition['name'] !== '' ) {
				$globals['wgExtraNamespaces'][$nsId] = $definition['name'];
			} elseif ( $nsId >= 100 && isset( $GLOBALS['wgExtraNamespaces'][$nsId] ) ) {
				$globals['wgExtraNamespaces'][$nsId] = $GLOBALS['wgExtraNamespaces'][$nsId];
			}

			$this->hookContainer->run( 'NamespaceManagerBeforePersistSettings', [
				&$globals, $nsId, $definition, $GLOBALS
			] );
			if ( isset( $definition['alias'] ) ) {
				if ( !empty( $definition['alias'] ) ) {
					$globals['wgNamespaceAliases'][$definition['alias']] = $nsId;
				}
			} else {
				$aliases = $aliases[$nsId] ?? [];
				if ( is_string( $aliases ) ) {
					$aliases = [ $aliases ];
				}
				// Thing which will always be presented in aliases array - namespace title.
				// So if there is only 1 item in array, then it is namespace title.
				// We should not use namespace title as alias, so just skip such cases
				$isOnlyTitlePresented = count( $aliases ) === 1;
				if ( !empty( $aliases ) && !$isOnlyTitlePresented ) {
					$globals['wgNamespaceAliases'][$aliases[0]] = $nsId;
				}
			}
		}
		$serialized['globals'] = $globals;

		return serialize( $serialized );
	}

	/**
	 * @return bool
	 */
	public function shouldAutoApply(): bool {
		return true;
	}

	/**
	 * @param mixed $a
	 * @param mixed $b
	 *
	 * @return bool
	 */
	private function isAssoc( $a, $b ) {
		return $this->isNormalAssoc( $a ) || $this->isNormalAssoc( $b );
	}

	/**
	 * @param mixed $array
	 *
	 * @return bool
	 */
	private function isNormalAssoc( $array ) {
		return is_array( $array ) && array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}
}
