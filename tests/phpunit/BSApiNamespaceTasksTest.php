<?php

namespace BlueSpice\NamespaceManager\Tests;

use BlueSpice\Tests\BSApiTasksTestBase;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;

/**
 * @group Broken
 * @group medium
 * @group API
 * @group Database
 * @group BlueSpice
 * @group BlueSpiceNamespaceManager
 */
class BSApiNamespaceTasksTest extends BSApiTasksTestBase {

	/**
	 *
	 * @var array
	 */
	protected $aSettings = [
		'subpages' => true,
		'content' => false
	];

	/**
	 *
	 * @return string
	 */
	protected function getModuleName() {
		return 'bs-namespace-tasks';
	}

	/**
	 *
	 * @return array
	 */
	public function getTokens() {
		return $this->getTokenList( self::$users[ 'sysop' ] );
	}

	/**
	 * @covers \BSApiNamespaceTasks::task_add
	 */
	public function testAdd() {
		$oData = $this->executeTask(
			'add',
			[
				'name' => 'DummyNS',
				'settings' => $this->aSettings
			]
		);

		$iInsertedID = $this->getLastNS();

		$this->assertTrue(
			$oData->success,
			"Namespace could not be added via API"
		);
		// Is saved to nm-settings.php
		// main NS
		$this->assertTrue(
			$this->isNSSaved( $iInsertedID ),
			"Namespace cannot be found in config."
		);
		// talk page
		$this->assertTrue(
			$this->isNSSaved( $iInsertedID + 1 ),
			"Talk namespace cannot be found in config."
		);
	}

	/**
	 * @covers \BSApiNamespaceTasks::task_edit
	 */
	public function testEdit() {
		$iNS = $this->getLastNS();

		$aSettings = $this->aSettings;
		$aSettings['subpages'] = true;

		$oData = $this->executeTask(
			'edit',
			[
				'id' => $iNS,
				'name' => 'FakeNS',
				'settings' => $aSettings
			]
		);

		$this->assertTrue(
			$oData->success,
			"Namespace could not be edited via API"
		);
	}

	/**
	 * @covers \BSApiNamespaceTasks::task_remove
	 */
	public function testRemove() {
		$iNS = $this->getLastNS();

		$oData = $this->executeTask(
			'remove',
			[
				'id' => $iNS,
				'doArticle' => 0
			]
		);

		$this->assertTrue(
			$oData->success,
			"Namespace could not be deleted via API"
		);

		// Is removed from nm-settings.php
		$this->assertFalse(
			$this->isNSSaved( $iNS ),
			"Namespace is still present in settings file."
		);
	}

	/**
	 *
	 * @return int
	 */
	protected function getLastNS() {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();

		$aNamespaces = $contLang->getNamespaces();
		end( $aNamespaces );
		$iNS = key( $aNamespaces ) + 1;
		reset( $aNamespaces );

		// if there is no custom namespace yet, ID range will begin at 3000
		if ( $iNS < 3000 ) {
			$iNS = 3000;
		}
		return $iNS;
	}

	/**
	 *
	 * @param int $iID
	 * @return bool
	 */
	protected function isNSSaved( $iID ) {
		/** @var DynamicConfigManager $manager */
		$manager = $this->getServiceContainer()->get( 'MWStakeDynamicConfigManager' );
		$raw = $manager->retrieveRaw( $manager->getConfigObject( 'bs-namespacemanager-namespaces' ) );
		$data = unserialize( $raw );
		return isset( $data['constants'][$iID] );
	}
}
