<?php

namespace BlueSpice\NamespaceManager\Tests;

use BlueSpice\DynamicSettingsManager;
use BlueSpice\Tests\BSApiTasksTestBase;
use MediaWiki\MediaWikiServices;

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
	 * @inheritDoc
	 */
	public function addDBDataOnce() {
		$dynamicSettingsManager = DynamicSettingsManager::factory();
		$dynamicSettingsManager->persist( 'NamespaceManager', "<?php\n" );

		return parent::addDBDataOnce();
	}

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
			"Namespace cannot be found in settings file."
		);
		// talk page
		$this->assertTrue(
			$this->isNSSaved( $iInsertedID + 1 ),
			"Talk namespace cannot be found in settings file."
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
		$dynamicSettingsManager = DynamicSettingsManager::factory();
		$sConfigContent = $dynamicSettingsManager->fetch( 'NamespaceManager' );
		$aUserNamespaces = [];
		$aMatches = [];
		$match = preg_match_all(
			'%define\("NS_([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)", ([0-9]*)\)%s',
			$sConfigContent,
			$aMatches,
			PREG_PATTERN_ORDER
		);
		if ( $match ) {
			$aUserNamespaces = $aMatches[ 2 ];
		}

		if ( in_array( $iID, $aUserNamespaces ) ) {
			return true;
		}

		return false;
	}
}
