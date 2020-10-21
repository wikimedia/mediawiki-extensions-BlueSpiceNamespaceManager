<?php

namespace BlueSpice\NamespaceManager\Hook\NamespaceManagerEditNamespace;

use BlueSpice\NamespaceManager\Hook\NamespaceManagerEditNamespace;

class SetContentFlag extends NamespaceManagerEditNamespace {

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		if ( empty( $this->namespaceDefinition[$this->nsId] ) ) {
			$this->namespaceDefinition[$this->nsId] = [];
		}
		if ( $this->useInternalDefaults ) {
			$this->namespaceDefinition[$this->nsId]['content'] = false;
			return true;
		}
		$this->namespaceDefinition[$this->nsId]['content']
			= $this->additionalSettings['content'];
		return true;
	}

}
