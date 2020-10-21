<?php

namespace BlueSpice\NamespaceManager\Hook\NamespaceManagerWriteNamespaceConfiguration;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerWriteNamespaceConfiguration;

class WriteContentFlag extends NamespaceManagerWriteNamespaceConfiguration {

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		if ( isset( $this->definition[ 'content' ] ) && $this->definition['content'] === true ) {
			$this->saveContent .= "\$GLOBALS['wgContentNamespaces'][] = {$this->constName};\n";
		}
		return true;
	}

}
