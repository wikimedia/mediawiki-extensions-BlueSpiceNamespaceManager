<?php

class SpecialNamespaceManager extends \BlueSpice\SpecialPage {

	public function __construct() {
		parent::__construct( 'NamespaceManager', 'namespacemanager-viewspecialpage' );
	}

	/**
	 *
	 * @global OutputPage $this->getOutput()
	 * @param type $sParameter
	 * @return type
	 */
	public function execute( $sParameter ) {
		parent::execute( $sParameter );
		$this->getOutput()->addModuleStyles( 'ext.bluespice.namespaceManager.styles' );
		$this->getOutput()->addModules( 'ext.bluespice.namespaceManager' );

		$aMetaFields = NamespaceManager::getMetaFields();
		$this->getOutput()->addJsConfigVars( 'bsNamespaceManagerMetaFields', $aMetaFields );

		$this->getOutput()->addHTML( '<div id="bs-namespacemanager-grid" class="bs-manager-container"></div>' );
	}

}
