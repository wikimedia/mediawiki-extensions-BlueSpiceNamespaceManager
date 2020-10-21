<?php

namespace BlueSpice\NamespaceManager\Hook\NamespaceManagerEditNamespace;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerEditNamespace;

class SetSubPagesFlag extends NamespaceManagerEditNamespace {

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		if ( empty( $this->namespaceDefinition[$this->nsId] ) ) {
			$this->namespaceDefinition[$this->nsId] = [];
		}
		if ( $this->useInternalDefaults ) {
			$this->namespaceDefinition[$this->nsId]['subpages'] = true;
			return true;
		}
		$this->namespaceDefinition[$this->nsId]['subpages']
			= $this->additionalSettings['subpages'];
		return true;
	}

}
