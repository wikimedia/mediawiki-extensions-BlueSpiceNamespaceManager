<?php

namespace BlueSpice\NamespaceManager\Hook\NamespaceManagerBeforePersistSettings;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerBeforePersistSettingsHook;

class PersistNamespaceFlags implements NamespaceManagerBeforePersistSettingsHook {

	/**
	 * @inheritDoc
	 */
	public function onNamespaceManagerBeforePersistSettings(
		array &$configuration, int $id, array $definition, array $mwGlobals
	): void {
		if ( isset( $definition['content'] ) && $definition['content'] === true ) {
			$configuration['wgContentNamespaces'][] = $id;
		}
		if ( isset( $definition['subpages'] ) ) {
			$configuration['wgNamespacesWithSubpages'][$id] = (bool)$definition['subpages'];
		}
	}
}
