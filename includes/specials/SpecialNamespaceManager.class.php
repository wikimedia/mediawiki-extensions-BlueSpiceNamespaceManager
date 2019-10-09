<?php

use BlueSpice\Special\ManagerBase;

class SpecialNamespaceManager extends ManagerBase {

	public function __construct() {
		parent::__construct( 'NamespaceManager', 'namespacemanager-viewspecialpage' );
	}

	/**
	 * @return string ID of the HTML element being added
	 */
	protected function getId() {
		return "bs-namespacemanager-grid";
	}

	/**
	 * @return array
	 */
	protected function getModules() {
		return [
			'ext.bluespice.namespaceManager.styles',
			'ext.bluespice.namespaceManager'
		];
	}

	protected function getJSVars() {
		$aMetaFields = NamespaceManager::getMetaFields();
		return [
			'bsNamespaceManagerMetaFields' => $aMetaFields
		];
	}
}
