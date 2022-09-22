<?php

/**
 * NamespacerNuker
 * @author Stephan Muggli <muggli@hallowelt.com>
 * @author Sebastian Ulbricht
 */
class NamespaceNuker {

	/**
	 *
	 * @param int $idNS
	 * @param string $nameNS
	 * @param bool $bWithSuffix
	 * @return bool
	 */
	public static function moveAllPagesIntoMain( $idNS, $nameNS, $bWithSuffix = false ) {
		if ( !$idNS ) {
			return false;
		}

		$dbw = wfgetDB( DB_PRIMARY );
		$res = $dbw->select(
			'page',
			[
				'page_id',
				'page_title',
				'page_len',
				'page_latest'
			],
			[
				'page_namespace' => $idNS
			]
		);

		$sToken = RequestContext::getMain()->getCsrfTokenSet()->getToken()->toString();
		foreach ( $res as $row ) {
			$sTitle = ( $bWithSuffix )
				? $row->page_title . ' ' . wfMessage( 'bs-from-something', $nameNS )->text()
				: $row->page_title;

			$oParams = new DerivativeRequest(
				RequestContext::getMain()->getRequest(),
				[
					'action' => 'move',
					'fromid' => $row->page_id,
					'to' => $sTitle,
					'reason' => wfMessage( 'bs-namespacemanager-deletens-movepages', $nameNS )->text(),
					'movetalk' => 1,
					'movesubpages' => 1,
					'noredirect' => 1,
					'token' => $sToken
				],
				true
			);

			$api = new ApiMain( $oParams, true );
			$api->execute();
		}

		return true;
	}

	/**
	 *
	 * @param int $idNS
	 * @param string $nameNS
	 * @return bool
	 */
	public static function removeAllNamespacePages( $idNS, $nameNS ) {
		$dbw = wfgetDB( DB_PRIMARY );
		$res = $dbw->select(
			'page',
			[
				'page_id',
				'page_title',
				'page_len',
				'page_latest'
			],
			[
				'page_namespace' => $idNS
			]
		);

		$sToken = RequestContext::getMain()->getCsrfTokenSet()->getToken()->toString();
		foreach ( $res as $row ) {
			$oParams = new DerivativeRequest(
				RequestContext::getMain()->getRequest(),
				[
					'action' => 'delete',
					'pageid' => $row->page_id,
					'reason' => wfMessage( 'bs-namespacemanager-deletens-deletepages', $nameNS )->text(),
					'token' => $sToken
				],
				true
			);

			$api = new ApiMain( $oParams, true );
			$api->execute();
		}

		return true;
	}

}
