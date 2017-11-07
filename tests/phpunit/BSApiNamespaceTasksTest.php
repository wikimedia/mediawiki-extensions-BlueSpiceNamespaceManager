<?php

/**
 * @group medium
 * @group API
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceNamespaceManager
 */
class BSApiNamespaceTasksTest extends BSApiTasksTestBase {

	protected $aSettings = array(
		'subpages' => true,
		'searched' => true,
		'content' => false
	);

	protected function getModuleName () {
		return 'bs-namespace-tasks';
	}

	function getTokens() {
		return $this->getTokenList( self::$users[ 'sysop' ] );
	}

	public function testAdd() {
		global $wgContentNamespaces, $wgNamespacesWithSubpages,
				$wgNamespacesToBeSearchedDefault;

		$oData = $this->executeTask(
			'add',
			array(
				'name' => 'DummyNS',
				'settings' => $this->aSettings
			)
		);

		$iInsertedID = $this->getLastNS();

		$this->assertTrue( $oData->success );
		//Is saved to nm-settings.php
		$this->assertTrue( $this->isNSSaved( $iInsertedID ) ); // main NS
		$this->assertTrue( $this->isNSSaved( $iInsertedID + 1 ) ); // talk page
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
			array(
				'id' => $iNS,
				'name' => 'FakeNS',
				'settings' => $aSettings
			)
		);

		$this->assertTrue( $oData->success );
	}

	public function testRemove() {
		$iNS = $this->getLastNS();

		$aToRemove = array( $iNS, $iNS +1 );

		foreach( $aToRemove as $iID ) {
			$oData = $this->executeTask(
				'remove',
				array(
					'id' => $iID,
					'doArticle' => 0
				)
			);

			$this->assertTrue( $oData->success );

			//Is removed from nm-settings.php
			$this->assertFalse( $this->isNSSaved( $iID ) );
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
		$aUserNamespaces = array();
		if ( preg_match_all( '%define\("NS_([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)", ([0-9]*)\)%s', $sConfigContent, $aMatches, PREG_PATTERN_ORDER ) ) {
			$aUserNamespaces = $aMatches[ 2 ];
		}

		if( in_array ( $iID, $aUserNamespaces ) ) {
			return true;
		}

		return false;
	}
}

