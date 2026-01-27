<?php

namespace BlueSpice\NamespaceManager\Hook;

interface NamespaceManagerBeforeSerializeSettingsHook {

	/**
	 * @param array &$serialized
	 */
	public function onNamespaceManagerBeforeSerializeSettings( array &$serialized ): void;

}
