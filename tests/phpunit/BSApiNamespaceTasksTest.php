<?php

namespace BlueSpice\NamespaceManager\Tests;

use BlueSpice\Tests\BSApiTasksTestBase;

/**
 * @group medium
 * @group API
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceNamespaceManager
 */
class BSApiNamespaceTasksTest extends BSApiTasksTestBase {

	protected $aSettings = [
		'subpages' => true,
		'content' => false
	];

	protected function setUp() {
		if( !defined( BSCONFIGDIR ) ) {
			define( BSCONFIGDIR, wfTempDir() );
		}
		$time = time();
		$this->setMwGlobals( [
			'bsgConfigFiles' => [
				'NamespaceManager' => wfTempDir() . "/nm-settings.$time.php"
			]
		] );

		return parent::setUp();
	}

	protected function getModuleName () {
		return 'bs-namespace-tasks';
	}

	function getTokens() {
		return $this->getTokenList( self::$users[ 'sysop' ] );
	}

	public function testAdd() {
		global $wgContentNamespaces, $wgNamespacesWithSubpages;

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
		//Is saved to nm-settings.php
		$this->assertTrue(
			$this->isNSSaved( $iInsertedID ),
			"Namespace cannot be found in settings file."
		); // main NS
		$this->assertTrue(
			$this->isNSSaved( $iInsertedID + 1 ),
			"Talk namespace cannot be found in settings file."
		); // talk page
	}

	public function testEdit() {
		global $wgExtraNamespaces, $wgContLang;

		$iNS = $this->getLastNS();

		$wgExtraNamespaces[$iNS] = 'DummyNS';
		$wgExtraNamespaces[$iNS + 1] = 'DummyNS_talk';

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

	public function testRemove() {
		$iNS = $this->getLastNS();

		$aToRemove = [ $iNS, $iNS +1 ];

		foreach( $aToRemove as $iID ) {
			$oData = $this->executeTask(
				'remove',
				[
					'id' => $iID,
					'doArticle' => 0
				]
			);

			$this->assertTrue(
				$oData->success,
				"Namespace could not be deleted via API"
			);

			//Is removed from nm-settings.php
			$this->assertFalse(
				$this->isNSSaved( $iID ),
				"Namespace is still present settings file."
			);
		}
	}

	protected function getLastNS() {
		global $wgContLang;

		$aNamespaces = $wgContLang->getNamespaces();
		end( $aNamespaces );
		$iNS = key( $aNamespaces ) + 1;
		reset( $aNamespaces );

		// if there is no custom namespace yet, ID range will begin at 3000
		if ( $iNS < 3000 ) {
			$iNS = 3000;
		}
		return $iNS;
	}

	protected function isNSSaved( $iID ) {
		global $bsgConfigFiles;
		$sConfigContent = file_get_contents( $bsgConfigFiles['NamespaceManager'] );
		$aUserNamespaces = [];
		$aMatches = [];
		if ( preg_match_all( '%define\("NS_([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)", ([0-9]*)\)%s', $sConfigContent, $aMatches, PREG_PATTERN_ORDER ) ) {
			$aUserNamespaces = $aMatches[ 2 ];
		}

		if( in_array ( $iID, $aUserNamespaces ) ) {
			return true;
		}

		return false;
	}
}

