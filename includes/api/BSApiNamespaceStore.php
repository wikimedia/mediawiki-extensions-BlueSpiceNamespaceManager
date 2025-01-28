<?php

use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;

class BSApiNamespaceStore extends BSApiExtJSStoreBase {

	/**
	 *
	 * @return string
	 */
	protected function getRequiredPermissions() {
		return 'wikiadmin';
	}

	/**
	 *
	 * @param int $nsId
	 * @param int $linkcontent
	 * @return string
	 */
	protected function renderNsLink( $nsId, $linkcontent ) {
		$href = SpecialPage::getTitleFor( 'Allpages' )->getLinkURL( [ 'namespace' => $nsId ] );
		return Html::element( 'a', [ 'title' => $linkcontent, 'href' => $href ], $linkcontent );
	}

	/**
	 * Calculate the data for the NamespaceManager store and put them to the ajax output.
	 * @param string $sQuery
	 * @return array
	 */
	protected function makeData( $sQuery = '' ) {
		global $wgContentNamespaces, $wgNamespaceAliases, $wgNamespacesWithSubpages;

		$contLang = $this->services->getContentLanguage();
		$aResult = [];
		$aNamespaces = $contLang->getNamespaces();
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
				[ 'page_namespace' => $iNs ],
				__METHOD__
			);

			$nsAlias = '';
			foreach ( $wgNamespaceAliases as $alias => $nsId ) {
				if ( $nsId === $iNs ) {
					$nsAlias = $alias;
				}
			}

			$aResult[] = [
				'id' => $iNs,
				'name' => $sNamespace,
				'alias' => $nsAlias,
				// formerly 'editable'
				'isSystemNS' => isset( $GLOBALS['bsSystemNamespaces'][$iNs] ) || $iNs < 3000,
				'isTalkNS' => $this->services->getNamespaceInfo()->isTalk( $iNs ),
				'pageCount' => $res->numRows(),
				'allPagesLink' => $this->renderNsLink( $iNs, $res->numRows() ),
				'content_raw' => ( $wgContentNamespaces && in_array( $iNs, $wgContentNamespaces ) ),
				'content' => [
					'value' => ( $wgContentNamespaces && in_array( $iNs, $wgContentNamespaces ) ),
					'read_only' => ( $iNs === NS_MAIN )
				],
				'subpages' => isset( $wgNamespacesWithSubpages[ $iNs ] )
					&& $wgNamespacesWithSubpages[ $iNs ] === true
			];
		}

		$this->services->getHookContainer()->run( 'BSApiNamespaceStoreMakeData', [
			&$aResult
		] );

		/**
		 * To be downwards compatible we need to have the dataset be arrays.
		 * BSApiExtJSStoreBase expects an array of objects to be returned from
		 * this method. Therefore we need to convert them.
		 */
		$aResultObjects = [];
		foreach ( $aResult as $aDataSet ) {
			$aResultObjects[] = (object)$aDataSet;
		}

		return $aResultObjects;
	}

	/**
	 * @param stdClass $oFilter
	 * @param stdClass $aDataSet
	 * @return bool
	 */
	public function filterBoolean( $oFilter, $aDataSet ) {
		if ( isset( $aDataSet->{$oFilter->field}['value'] ) ) {
			return $oFilter->value == $aDataSet->{$oFilter->field}['value'];
		}

		return parent::filterBoolean( $oFilter, $aDataSet );
	}

}
