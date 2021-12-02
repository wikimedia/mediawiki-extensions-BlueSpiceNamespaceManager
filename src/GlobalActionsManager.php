<?php

namespace BlueSpice\NamespaceManager;

use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;
use SpecialPage;

class GlobalActionsManager extends RestrictedTextLink {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct( [] );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'ga-bs-namespacemanager';
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPermissions(): array {
		$permissions = [
			'namespacemanager-viewspecialpage'
		];
		return $permissions;
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$tool = SpecialPage::getTitleFor( 'NamespaceManager' );
		return $tool->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-namespacemanager-text' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-namespacemanager-desc' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-namespacemanager-text' );
	}
}
