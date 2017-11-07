<?php

class BSApiNamespaceStore extends BSApiExtJSStoreBase {

	protected function getRequiredPermissions() {
		return 'wikiadmin';
	}
	/**
	 * Calculate the data for the NamespaceManager store and put them to the ajax output.
	 */
	protected function makeData($sQuery = '') {
		global $wgContLang, $bsgSystemNamespaces, $wgContentNamespaces,
			$wgNamespacesWithSubpages, $wgNamespacesToBeSearchedDefault;

		$aResult = array();
		$aNamespaces = $wgContLang->getNamespaces();
		foreach ( $aNamespaces as $iNs => $sNamespace ) {
			if ( $sNamespace === '' ) {
				$sNamespace = BsNamespaceHelper::getNamespaceName( $iNs );
			}
			if ( $iNs === NS_SPECIAL || $iNs === NS_MEDIA ) {
				continue;
			}
			$res = $this->getDB()->select(
				'page',
				'page_id',
				array(
					'page_namespace' => $iNs
				)
			);

			$aResult[] = array(
				'id' => $iNs,
				'name' => $sNamespace,
				'isSystemNS' => isset( $bsgSystemNamespaces[$iNs] ) || $iNs < 3000, //formerly 'editable'
				'isTalkNS' => MWNamespace::isTalk( $iNs ),
				'pageCount' => $res->numRows(),
				'content' => in_array( $iNs, $wgContentNamespaces ),
				'subpages' => ( isset( $wgNamespacesWithSubpages[$iNs] ) && $wgNamespacesWithSubpages[$iNs] ),
				'searched' => ( isset( $wgNamespacesToBeSearchedDefault[$iNs] ) && $wgNamespacesToBeSearchedDefault[$iNs] )
			);
		}

		Hooks::run( 'NamespaceManager::getNamespaceData', array( &$aResult ), '1.23.2' );
		Hooks::run( 'BSApiNamespaceStoreMakeData', array( &$aResult ) );

		/**
		 * To be downwards compatible we need to have the dataset be arrays.
		 * BSApiExtJSStoreBase expects an array of objects to be returned from
		 * this method. Therefore we need to convert them.
		 */
		$aResultObjects = array();
		foreach( $aResult as $aDataSet ) {
			$aResultObjects[] = (object) $aDataSet;
		}

		return $aResultObjects;
	}

	public function sortCallback($oA, $oB) {
		//TODO: Check if this hook from the old implementation was used somewhere...
		//wfRunHooks( 'NamespaceManager::namespaceManagerRemoteSort', array( $value1, $value2, self::$aSortConditions ) );
		parent::sortCallback($oA, $oB);
	}
}