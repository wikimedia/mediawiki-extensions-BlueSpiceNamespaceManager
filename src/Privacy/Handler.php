<?php

namespace BlueSpice\NamespaceManager\Privacy;

use BlueSpice\Privacy\IPrivacyHandler;

class Handler implements IPrivacyHandler {
	protected $user;
	protected $db;

	public function __construct( \User $user, \Database $db ) {
		$this->user = $user;
		$this->db = $db;
	}

	public function anonymize( $newUsername ) {
		$this->db->update(
			'bs_namespacemanager_backup_revision',
			[ 'rev_user_text' => $newUsername ],
			[ 'rev_user_text' => $this->user->getName() ]
		);

		return \Status::newGood();

	}
}