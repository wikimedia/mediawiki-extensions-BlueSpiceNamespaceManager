<?php

namespace BlueSpice\NamespaceManager\Hook;

interface NamespaceManagerCollectNamespacePropertiesHook {

	/**
	 * @param int $namespaceId
	 * @param array $globals
	 * @param array &$properties
	 */
	public function onNamespaceManagerCollectNamespaceProperties(
		int $namespaceId,
		array $globals,
		array &$properties
	): void;

}
