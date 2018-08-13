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
		$classes = [ 'bs-icon-register-box' ];
		return $classes;
	}

	public function getDataAttributes() {
		return [];
	}

	public function getPermissions() {
		$permissions = [ 'namespacemanager-viewspecialpage' ];
		return $permissions;
	}

}