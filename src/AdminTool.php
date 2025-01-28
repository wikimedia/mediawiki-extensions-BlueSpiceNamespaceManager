<?php

namespace BlueSpice\NamespaceManager;

use BlueSpice\IAdminTool;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;

class AdminTool implements IAdminTool {

	/**
	 *
	 * @return string
	 */
	public function getURL() {
		$tool = SpecialPage::getTitleFor( 'NamespaceManager' );
		return $tool->getLocalURL();
	}

	/**
	 *
	 * @return Message
	 */
	public function getDescription() {
		return wfMessage( 'bs-namespacemanager-desc' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getName() {
		return wfMessage( 'bs-namespacemanager-label' );
	}

	/**
	 *
	 * @return string[]
	 */
	public function getClasses() {
		$classes = [ 'bs-icon-register-box' ];
		return $classes;
	}

	/**
	 *
	 * @return array
	 */
	public function getDataAttributes() {
		return [];
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPermissions() {
		$permissions = [ 'namespacemanager-viewspecialpage' ];
		return $permissions;
	}

}
