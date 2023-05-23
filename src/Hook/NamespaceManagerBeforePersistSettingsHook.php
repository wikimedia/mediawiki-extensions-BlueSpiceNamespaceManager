<?php

namespace BlueSpice\NamespaceManager\Hook;

interface NamespaceManagerBeforePersistSettingsHook {
	/**
	 * @param array &$configuration
	 * @param int $id
	 * @param array $definition
	 * @param array $mwGlobals
	 */
	public function onNamespaceManagerBeforePersistSettings(
		array &$configuration, int $id, array $definition, array $mwGlobals
	): void;
}
