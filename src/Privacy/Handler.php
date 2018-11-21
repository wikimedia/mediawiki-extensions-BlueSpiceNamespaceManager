<?php

namespace BlueSpice\NamespaceManager\Privacy;

use BlueSpice\Privacy\IPrivacyHandler;

class Handler implements IPrivacyHandler {
	protected $db;

	public function __construct( \Database $db ) {
		$this->db = $db;
	}

	public function anonymize( $oldUsername, $newUsername ) {
		$this->db->update(
			'bs_namespacemanager_backup_revision',
			[ 'rev_user_text' => $newUsername ],
			[ 'rev_user_text' => $oldUsername ]
		);

		return \Status::newGood();
	}

	public function delete( \User $userToDelete, \User $deletedUser ) {
		$this->anonymize( $userToDelete->getName(), $deletedUser->getName() );

		$this->db->update(
			'bs_namespacemanager_backup_revision',
			[ 'rev_user' => $deletedUser->getId() ],
			[ 'rev_user' => $userToDelete->getId() ]
		);

		return \Status::newGood();
	}

	public function exportData( array $types, $format, \User $user ) {
		return \Status::newGood( [] );
	}
}
