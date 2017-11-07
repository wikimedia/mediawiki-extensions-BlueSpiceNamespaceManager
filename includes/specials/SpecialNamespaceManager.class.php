<?php

class SpecialNamespaceManager extends BsSpecialPage {

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
		$this->getOutput()->addModules( 'ext.bluespice.namespaceManager' );
		$aMetaFields = array(
			array(
				'name' => 'id',
				'type' => 'int',
				'sortable' => true,
				'filter' => array(
					'type' => 'numeric'
				),
				'label' => wfMessage( 'bs-namespacemanager-label-id' )->plain()
			),
			array(
				'name' => 'name',
				'type' => 'string',
				'sortable' => true,
				'filter' => array(
					'type' => 'string'
				),
				'label' => wfMessage( 'bs-namespacemanager-label-namespaces' )->plain()
			),
			array(
				'name' => 'pageCount',
				'type' => 'int',
				'sortable' => true,
				'filter' => array(
					'type' => 'numeric'
				),
				'label' => wfMessage( 'bs-namespacemanager-label-pagecount' )->plain()
			),
			array(
				'name' => 'isSystemNS',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-editable' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => array(
					'type' => 'bool'
				),
			),
			array(
				'name' => 'isTalkNS',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-istalk' )->plain(),
				'hidden' => true,
				'sortable' => true,
				'filter' => array(
					'type' => 'bool'
				),
			),
			array(
				'name' => 'subpages',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-subpages' )->plain(),
				'sortable' => true,
				'filter' => array(
					'type' => 'bool'
				),
			),
			array(
				'name' => 'searched',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-searchable' )->plain(),
				'sortable' => true,
				'filter' => array(
					'type' => 'bool'
				),
			),
			array(
				'name' => 'content',
				'type' => 'boolean',
				'label' => wfMessage( 'bs-namespacemanager-label-content' )->plain(),
				'sortable' => true,
				'filter' => array(
					'type' => 'bool'
				),
			)
		);

		Hooks::run( 'NamespaceManager::getMetaFields', array( &$aMetaFields ) );
		$this->getOutput()->addJsConfigVars( 'bsNamespaceManagerMetaFields', $aMetaFields );

		$this->getOutput()->addHTML( '<div id="bs-namespacemanager-grid" class="bs-manager-container"></div>' );
	}

}
