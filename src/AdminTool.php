<?php

namespace BlueSpice\NamespaceManager;

use BlueSpice\IAdminTool;

class AdminTool implements IAdminTool {

	public function getURL() {
		$tool = \SpecialPage::getTitleFor( 'NamespaceManager' );
		return $tool->getLocalURL();
	}

	public function getDescription() {
		return wfMessage( 'bs-namespacemanager-desc' );
	}

	public function getName() {
		return wfMessage( 'bs-namespacemanager-label' );
	}

	public function getClasses() {
		$classes = array(
			'bs-icon-register-box'
		);

		return $classes;
	}

	public function getDataAttributes() {
	}

	public function getPermissions() {
		$permissions = array(
			'namespacemanager-viewspecialpage'
		);
		return $permissions;
	}

}