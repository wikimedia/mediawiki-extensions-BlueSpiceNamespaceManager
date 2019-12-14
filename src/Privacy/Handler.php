<?php

namespace BlueSpice\NamespaceManager\Privacy;

use BlueSpice\Privacy\IPrivacyHandler;

class Handler implements IPrivacyHandler {
	/**
	 *
	 * @var \Database
	 */
	protected $db;

	/**
	 *
	 * @param \Database $db
	 */
	public function __construct( \Database $db ) {
		$this->db = $db;
	}

	/**
	 *
	 * @param string $oldUsername
	 * @param string $newUsername
	 * @return \Status
	 */
	public function anonymize( $oldUsername, $newUsername ) {
		$this->db->update(
			'bs_namespacemanager_backup_revision',
			[ 'rev_user_text' => $newUsername ],
			[ 'rev_user_text' => $oldUsername ]
		);

		return \Status::newGood();
	}

	/**
	 *
	 * @param \User $userToDelete
	 * @param \User $deletedUser
	 * @return \Status
	 */
	public function delete( \User $userToDelete, \User $deletedUser ) {
		$this->anonymize( $userToDelete->getName(), $deletedUser->getName() );

		$this->db->update(
			'bs_namespacemanager_backup_revision',
			[ 'rev_user' => $deletedUser->getId() ],
			[ 'rev_user' => $userToDelete->getId() ]
		);

		return \Status::newGood();
	}

	/**
	 *
	 * @param array $types
	 * @param string $format
	 * @param \User $user
	 * @return \Status
	 */
	public function exportData( array $types, $format, \User $user ) {
		return \Status::newGood( [] );
	}
}
