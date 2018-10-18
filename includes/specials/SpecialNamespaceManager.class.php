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
		$aMetaFields = [
			[
				'name' => 'id',
				'type' => 'int',
				'sortable' => true,
				'filter' => [ 'type' => 'numeric' ],
				'label' => wfMessage( 'bs-namespacemanager-label-id' )->plain()
			],
			[
				'name' => 'name',
				'type' => 'string',
				'sortable' => true,
				'filter' => [ 'type' => 'string' ],
				'label' => wfMessage( 'bs-namespacemanager-label-namespaces' )->plain()
			],
			[
				'name' => 'pageCount',
				'type' => 'int',
				'sortable' => true,
				'filter' => [ 'type' => 'numeric' ],
				'label' => wfMessage( 'bs-namespacemanager-label-pagecount' )->plain()
			],
			[
				'name' => 'isSystemNS',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-editable' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'isTalkNS',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-istalk' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'subpages',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-subpages' )->plain(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			],
			[
				'name' => 'content',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-content' )->plain(),
				'sortable' => true,
				'filter' => [ 'type' => 'boolean' ],
			]
		];

		Hooks::run( 'NamespaceManager::getMetaFields', [ &$aMetaFields ] );
		$this->getOutput()->addJsConfigVars( 'bsNamespaceManagerMetaFields', $aMetaFields );

		$this->getOutput()->addHTML( '<div id="bs-namespacemanager-grid" class="bs-manager-container"></div>' );
	}

}
