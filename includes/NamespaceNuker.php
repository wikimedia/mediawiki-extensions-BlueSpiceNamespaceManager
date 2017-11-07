<?php

/**
 * NamespacerNuker
 * @author Stephan Muggli <muggli@hallowelt.com>
 * @author Sebastian Ulbricht
 */
class NamespaceNuker {

	public static function moveAllPagesIntoMain( $idNS, $nameNS, $bWithSuffix = false ) {
		if ( !$idNS ) {
			return false;
		}

		$dbw = wfgetDB( DB_MASTER );
		$res = $dbw->select(
			'page',
			array(
				'page_id',
				'page_title',
				'page_len',
				'page_latest'
			),
			array(
				'page_namespace' => $idNS
			)
		);

		$sToken = RequestContext::getMain()->getUser()->getEditToken();
		foreach ( $res as $row ) {
			$sTitle = ( $bWithSuffix )
				? $row->page_title . ' ' . wfMessage( 'bs-from-something', $nameNS )->text()
				: $row->page_title;

			$oParams = new DerivativeRequest(
				RequestContext::getMain()->getRequest(),
				array(
					'action' => 'move',
					'fromid' => $row->page_id,
					'to' => $sTitle,
					'reason' => wfMessage( 'bs-namespacemanager-deletens-movepages', $nameNS )->text(),
					'movetalk' => 1,
					'movesubpages' => 1,
					'noredirect' => 1,
					'token' => $sToken
				),
				true
			);

			$api = new ApiMain( $oParams, true );
			$api->execute();
		}

		return true;
	}

	public static function removeAllNamespacePages( $idNS, $nameNS ) {
		$dbw = wfgetDB( DB_MASTER );
		$res = $dbw->select(
			'page',
			array(
				'page_id',
				'page_title',
				'page_len',
				'page_latest'
			),
			array(
				'page_namespace' => $idNS
			)
		);

		$sToken = RequestContext::getMain()->getUser()->getEditToken();
		foreach ( $res as $row ) {
			$oParams = new DerivativeRequest(
				RequestContext::getMain()->getRequest(),
				array(
					'action' => 'delete',
					'pageid' => $row->page_id,
					'reason' => wfMessage( 'bs-namespacemanager-deletens-deletepages', $nameNS )->text(),
					'token' => $sToken
				),
				true
			);

			$api = new ApiMain( $oParams, true );
			$api->execute();
		}

		return true;
	}

}