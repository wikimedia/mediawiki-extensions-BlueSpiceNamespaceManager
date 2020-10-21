<?php

namespace BlueSpice\NamespaceManager\Hook\NamespaceManagerWriteNamespaceConfiguration;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerWriteNamespaceConfiguration;

class WriteSubPagesFlag extends NamespaceManagerWriteNamespaceConfiguration {

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		if ( isset( $this->definition[ 'subpages' ] ) ) {
			$stringVal = $this->definition['subpages'] ? "true" : "false";
			$this->saveContent
				.= "\$GLOBALS['wgNamespacesWithSubpages'][{$this->constName}] = $stringVal;\n";
		}
		return true;
	}

}
