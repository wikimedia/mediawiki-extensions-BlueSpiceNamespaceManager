<?php

namespace BlueSpice\NamespaceManager\Special;

use BlueSpice\NamespaceManager\NamespaceManager;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialNamespaceManager extends SpecialPage {

	/** @var NamespaceManager */
	private $namespaceManager;

	/**
	 * @param NamespaceManager $namespaceManager
	 */
	public function __construct( NamespaceManager $namespaceManager ) {
		parent::__construct( 'NamespaceManager', 'namespacemanager-viewspecialpage' );
		$this->namespaceManager = $namespaceManager;
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->getOutput()->addModules( [
			'ext.bluespice.namespaceManager.styles',
			'ext.bluespice.namespaceManager'
		] );
		$this->getOutput()->addHTML(
			Html::element( 'div', [ 'id' => 'bs-namespacemanager-grid' ] )
		);
		$this->getOutput()->addJsConfigVars( [
			'bsNamespaceManagerMetaFields' => $this->namespaceManager->getMetaFields( $this->getContext() ),
		] );
	}
}
