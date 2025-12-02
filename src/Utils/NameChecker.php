<?php

namespace BlueSpice\NamespaceManager\Utils;

use BlueSpice\Api\Response\Standard;
use MessageLocalizer;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\IDatabase;

class NameChecker {

	/** @var array */
	private $namespaceNames = [];

	/** @var array */
	private $namespaceAliases = [];

	/** @var Database */
	private $db;

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/**
	 * @param array $namespaceNames
	 * @param array $namespaceAliases
	 * @param IDatabase|null $db
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct(
		$namespaceNames,
		$namespaceAliases,
		?IDatabase $db,
		MessageLocalizer $messageLocalizer
	) {
		$this->namespaceNames = $namespaceNames;
		$this->namespaceAliases = $namespaceAliases;
		$this->db = $db;
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * Checks if a namespace or alias name passes the naming conventions
	 * @param string $namespaceName
	 * @param string $namespaceAlias
	 * @param int $namespaceId
	 * @return Standard
	 */
	public function checkNamingConvention( $namespaceName, $namespaceAlias, $namespaceId ) {
		$oResult = new Standard();
		if ( strlen( $namespaceName ) < 2 ) {
			$oResult->message = $this->messageLocalizer->msg( 'bs-namespacemanager-ns-length' )->text();
			return $oResult;
		}

		if ( !empty( $namespaceAlias ) && strlen( $namespaceAlias ) < 2 ) {
			$oResult->message = $this->messageLocalizer->msg( 'bs-namespacemanager-ns-length' )->text();
			return $oResult;
		}

		if ( $namespaceId !== NS_MAIN && $namespaceId !== NS_PROJECT && $namespaceId !== NS_PROJECT_TALK &&
			!preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%i', $namespaceName )
			) {
			$oResult->message = $this->messageLocalizer->msg( 'bs-namespacemanager-wrong-name' )->text();
			return $oResult;
		}

		if ( !empty( $namespaceAlias )
			&& !preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%i', $namespaceAlias ) ) {
			$oResult->message = $this->messageLocalizer->msg( 'bs-namespacemanager-wrong-alias' )->text();
			return $oResult;
		}
		$oResult->success = true;
		return $oResult;
	}

	/**
	 * Checks it a namespace name or alias is already in use
	 * @param string $namespaceName
	 * @param string $namespaceAlias
	 * @param int $namespaceId
	 * @return Standard
	 */
	public function checkExists( $namespaceName, $namespaceAlias, $namespaceId ) {
		$oResult = new Standard();
		foreach ( $this->namespaceNames as $sKey => $sNamespaceFromArray ) {
			if ( strtolower( $sNamespaceFromArray ) == strtolower( $namespaceName ) ) {
				if ( $sKey === $namespaceId ) {
					continue;
				}
				$oResult->message = $this->messageLocalizer->msg( 'bs-namespacemanager-ns-exists' )->text();
				return $oResult;
			}
		}
		if ( !empty( $namespaceAlias ) ) {
			foreach ( $this->namespaceNames as $sKey => $sNamespaceFromArray ) {
				if ( strtolower( $sNamespaceFromArray ) == strtolower( $namespaceAlias ) ) {
					// It's ok if name and alias point to the same ID
					if ( isset( $this->namespaceAliases[$namespaceAlias] ) &&
						$sKey === $this->namespaceAliases[$namespaceAlias] ) {
						continue;
					}
					$oResult->message = $this->messageLocalizer->msg(
						'bs-namespacemanager-alias-exists-as-ns'
					)->text();
					return $oResult;
				}
			}

			if ( $this->isAliasInUse( $namespaceId, $namespaceAlias ) ) {
				$nsName = $this->namespaceNames[$this->namespaceAliases[$namespaceAlias]];
				$oResult->message = $this->messageLocalizer->msg(
					'bs-namespacemanager-alias-exists', $nsName
				)->text();
				return $oResult;
			}

		}
		$oResult->success = true;
		return $oResult;
	}

	/**
	 * @param int $namespaceId
	 * @param string $namespaceAlias
	 * @return bool
	 */
	protected function isAliasInUse( $namespaceId, $namespaceAlias ) {
		if ( empty( $namespaceAlias ) || !isset( $this->namespaceAliases[$namespaceAlias] ) ) {
			return false;
		}
		if ( $this->namespaceAliases[$namespaceAlias] === $namespaceId ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if there is a title in Main namespace which begins with
	 * the proposed namespace name
	 * @param string $namespaceName
	 * @param string $namespaceAlias
	 * @return bool
	 */
	public function checkPseudoNamespace( $namespaceName, $namespaceAlias ) {
		$oResult = new Standard();
		$res = $this->db->select(
			[ 'page' ],
			'page_title',
			[
				'page_namespace' => NS_MAIN,
				'page_title LIKE "%:%"'
			],
			__METHOD__
		);

		$titlesInMainBeginWithNamespaceNameOrAlias = [];

		foreach ( $res as $row ) {
			if ( strpos( strtolower( $row->page_title ), strtolower( $namespaceName . ":" ) ) === 0 ) {
				$titlesInMainBeginWithNamespaceNameOrAlias[] = $row->page_title;
			}
			if ( !empty( $namespaceAlias )
				&& strpos( strtolower( $row->page_title ), strtolower( $namespaceAlias . ":" ) ) === 0
			) {
				$titlesInMainBeginWithNamespaceNameOrAlias[] = $row->page_title;
			}
		}

		if ( count( $titlesInMainBeginWithNamespaceNameOrAlias ) > 0 ) {
			$oResult->message = $this->messageLocalizer->msg( 'bs-namespacemanager-pseudo-ns' )->text();
			return $oResult;
		}

		$oResult->success = true;
		return $oResult;
	}
}
