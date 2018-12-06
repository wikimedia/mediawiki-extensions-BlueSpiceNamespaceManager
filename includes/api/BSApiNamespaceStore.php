<?php

class BSApiNamespaceStore extends BSApiExtJSStoreBase {

	protected function getRequiredPermissions() {
		return 'wikiadmin';
	}
	/**
	 * Calculate the data for the NamespaceManager store and put them to the ajax output.
	 */
	protected function makeData($sQuery = '') {
		global $wgContLang, $bsgSystemNamespaces, $wgContentNamespaces, $wgNamespaceAliases,
			$wgNamespacesWithSubpages;

		$aResult = [];
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
				[ 'page_namespace' => $iNs ]
			);

			$nsAlias = '';
			foreach( $wgNamespaceAliases as $alias => $nsId ) {
				if ( $nsId === $iNs ) {
					$nsAlias = $alias;
				}
			}

			$aResult[] = [
				'id' => $iNs,
				'name' => $sNamespace,
				'alias' => $nsAlias,
				'isSystemNS' => isset( $bsgSystemNamespaces[$iNs] ) || $iNs < 3000, //formerly 'editable'
				'isTalkNS' => MWNamespace::isTalk( $iNs ),
				'pageCount' => $res->numRows(),
				'content' => [
					'value' => ( $wgContentNamespaces && in_array( $iNs, $wgContentNamespaces ) ),
					'read_only' => ( $iNs === NS_MAIN )
				],
				'subpages' => ( isset( $wgNamespacesWithSubpages[ $iNs ] ) && $wgNamespacesWithSubpages[ $iNs ] === true )
			];
		}

		Hooks::run( 'NamespaceManager::getNamespaceData', [ &$aResult ], '1.23.2' );
		Hooks::run( 'BSApiNamespaceStoreMakeData', [ &$aResult ] );

		/**
		 * To be downwards compatible we need to have the dataset be arrays.
		 * BSApiExtJSStoreBase expects an array of objects to be returned from
		 * this method. Therefore we need to convert them.
		 */
		$aResultObjects = [];
		foreach( $aResult as $aDataSet ) {
			$aResultObjects[] = (object) $aDataSet;
		}

		return $aResultObjects;
	}

}