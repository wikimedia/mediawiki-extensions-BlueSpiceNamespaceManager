<?php

namespace BlueSpice\NamespaceManager;

use MediaWiki\Message\Message;
use MediaWiki\Page\DeletePageFactory;
use MediaWiki\Page\MovePageFactory;
use MediaWiki\Status\Status;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * NamespacerNuker
 * @author Stephan Muggli <muggli@hallowelt.com>
 * @author Sebastian Ulbricht
 */
class NamespaceNuker {

	/** @var MovePageFactory */
	protected $movePageFactory;

	/** @var DeletePageFactory */
	protected $deletePageFactory;

	/** @var TitleFactory */
	protected $titleFactory;

	/** @var ILoadBalancer */
	protected $loadBalancer;

	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @param MovePageFactory $movePageFactory
	 * @param DeletePageFactory $deletePageFactory
	 * @param TitleFactory $titleFactory
	 * @param ILoadBalancer $loadBalancer
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		MovePageFactory $movePageFactory, DeletePageFactory $deletePageFactory, TitleFactory $titleFactory,
		ILoadBalancer $loadBalancer, LoggerInterface $logger
	) {
		$this->movePageFactory = $movePageFactory;
		$this->deletePageFactory = $deletePageFactory;
		$this->titleFactory = $titleFactory;
		$this->loadBalancer = $loadBalancer;
		$this->logger = $logger;
	}

	/**
	 * @param int $namespaceId
	 * @param string $namespaceName
	 * @param bool $withSuffix
	 * @return Status
	 */
	public function moveAllPagesIntoMain( int $namespaceId, string $namespaceName, bool $withSuffix = false ): Status {
		if ( $namespaceId === NS_MAIN ) {
			// Short-circuit
			return Status::newFatal( 'Cannot nuke NS_MAIN' );
		}
		$res = $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
			->from( 'page' )
			->select( [ 'page_id', 'page_title', 'page_namespace', 'page_latest' ] )
			->where( [ 'page_namespace' => $namespaceId ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$mainStatus = Status::newGood();
		foreach ( $res as $row ) {
			$newTitleText = $row->page_title;
			if ( $withSuffix ) {
				$newTitleText .= ' ' . Message::newFromKey( 'bs-from-something', $namespaceName )->text();
			}
			$title = $this->titleFactory->newFromRow( $row );
			if ( !$title->exists() ) {
				$this->logger->error( 'Tried to move non-existing page', [
					'title' => $title->getPrefixedText()
				] );
				continue;
			}
			$newTitle = $this->titleFactory->newFromText( $newTitleText );
			if ( !$newTitle ) {
				$this->logger->error( 'Failed to create new title', [
					'title' => $newTitleText,
					'oldTitle' => $title->getPrefixedText()
				] );
				continue;
			}
			if ( $newTitle->exists() ) {
				$this->logger->error( 'New title already exists', [
					'title' => $newTitle->getPrefixedText()
				] );
				continue;
			}

			$movePage = $this->movePageFactory->newMovePage( $title, $newTitle );
			$actor = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
			$mainStatus->merge(
				$movePage->move( $actor, null, false )
			);
		}

		if ( !$mainStatus->isGood() ) {
			$this->logger->error( 'Failed to delete pages', [
				'errors' => $mainStatus->getMessages()
			] );
		}
		return $mainStatus;
	}

	/**
	 *
	 * @param int $namespaceId
	 * @param string $namespaceName
	 * @return Status
	 */
	public function removeAllNamespacePages( int $namespaceId, string $namespaceName ): Status {
		$res = $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
			->from( 'page' )
			->select( [ 'page_id', 'page_title', 'page_namespace', 'page_latest' ] )
			->where( [ 'page_namespace' => $namespaceId ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$mainStatus = Status::newGood();

		foreach ( $res as $row ) {
			$title = $this->titleFactory->newFromRow( $row );
			if ( !$title->exists() ) {
				$this->logger->error( 'Tried to delete non-existing page', [
					'title' => $title->getPrefixedText()
				] );
				continue;
			}
			$actor = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
			$deletePage = $this->deletePageFactory->newDeletePage( $title->toPageIdentity(), $actor );
			$mainStatus->merge(
				$deletePage->deleteUnsafe(
					Message::newFromKey( 'bs-namespacemanager-deletens-deletepages', $namespaceName )->text()
				)
			);
		}

		if ( !$mainStatus->isGood() ) {
			$this->logger->error( 'Failed to delete pages', [
				'errors' => $mainStatus->getMessages()
			] );
		}
		return $mainStatus;
	}

}
