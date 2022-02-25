<?php

namespace BlueSpice\NamespaceManager\Tests;

use BlueSpice\NamespaceManager\Utils\NameChecker;
use Database;
use MediaWikiIntegrationTestCase;
use MessageLocalizer;

/**
 * @covers \BlueSpice\NamespaceManager\Utils\NameChecker
 */
class NameCheckerTest extends MediaWikiIntegrationTestCase {

	private $namespaceList = [
			-2 => 'Media',
			-1 => 'Special',
			0  => 'Main',
			1 => 'Talk',
			3000 => 'Exists',
			3001 => 'Exists_Talk',
			3002 => 'SomeNS',
			3003 => 'SomeNS_Talk',
			3004 => 'SameNameAndAlias',
			3005 => 'SameNameAndAlias_Talk'
		];

	private $namespaceAliasList = [
			'ExistsReally' => 3000,
			'ExistsReally_talk' => 3001,
			'ExistsElsewhere' => 3002,
			'ExistsElsewhere_talk' => 3003,
			'SameNameAndAlias' => 3004,
			'SameNameAndAlias_talk' => 3005
		];

	/**
	 * @covers \BlueSpice\NamespaceManager\Utils\NameChecker::checkNamingConvention()
	 * @dataProvider checkNameDataProvider()
	 */
	public function testCheckNamingConvention( $name, $alias, $id, $expected, $expectedMessage ) {
		$messageLocalizer = $this->createMock( MessageLocalizer::class );
		$messageLocalizer
			->method( 'msg' )
			->will( $this->returnCallback( static function ( $msgKey ) {
					return new \RawMessage( $msgKey );
			} ) );

		$nameChecker = new NameChecker( $this->namespaceList, $this->namespaceAliasList, null, $messageLocalizer );
		$actual = $nameChecker->checkNamingConvention( $name, $alias, $id );

		$this->assertEquals( $expected, $actual->success );
		$this->assertEquals( $expectedMessage, $actual->message );
	}

	/**
	 * @covers \BlueSpice\NamespaceManager\Utils\NameChecker::checkExists()
	 * @dataProvider checkExistsDataProvider()
	 */
	public function testCheckExists( $name, $alias, $id, $expected, $expectedMessage ) {
		$messageLocalizer = $this->createMock( MessageLocalizer::class );
		$messageLocalizer
			->method( 'msg' )
			->will( $this->returnCallback( static function ( $msgKey ) {
					return new \RawMessage( $msgKey );
			} ) );

		$nameChecker = new NameChecker( $this->namespaceList, $this->namespaceAliasList, null, $messageLocalizer );
		$actual = $nameChecker->checkExists( $name, $alias, $id );

		$this->assertEquals( $expected, $actual->success );
		$this->assertEquals( $expectedMessage, $actual->message );
	}

	/**
	 * @covers \BlueSpice\NamespaceManager\Utils\NameChecker::checkPseudoNamespace()
	 * @dataProvider checkPseudoNamespaceDataProvider()
	 */
	public function testCheckPseudoNamespace( $name, $alias, $expected, $expectedMessage ) {
		$mockDBData = [
			(object)[ 'page_title' => 'Normal:Page' ],
			(object)[ 'page_title' => 'Pseudo:Page' ],
		];
		$db = $this->createMock( Database::class );
		$db->method( 'select' )->willReturn( $mockDBData );

		$messageLocalizer = $this->createMock( MessageLocalizer::class );
		$messageLocalizer
			->method( 'msg' )
			->will( $this->returnCallback( static function ( $msgKey ) {
					return new \RawMessage( $msgKey );
			} ) );

		$nameChecker = new NameChecker( $this->namespaceList, $this->namespaceAliasList, $db, $messageLocalizer );
		$actual = $nameChecker->checkPseudoNamespace( $name, $alias );

		$this->assertEquals( $expected, $actual->success );
		$this->assertEquals( $expectedMessage, $actual->message );
	}

	public function checkNameDataProvider() {
		return [
			[ "Test", "Toast", 3200, true, null ],
			[ "Test", "", 3200, true, null ],
			[ "Täst", "", 3200, true, null ],
			[ "Te_st", "", 3200, true, null ],
			[ "Test123", "", 3200, true, null ],
			[ "T", "Test", 3200, false, 'bs-namespacemanager-ns-length' ],
			[ "Test", "T", 3200, false, 'bs-namespacemanager-ns-length' ],
			[ "Te-st", "", 3200, false, 'bs-namespacemanager-wrong-name' ],
			[ "Te st", "", 3200, false, 'bs-namespacemanager-wrong-name' ],
			[ "123Test", "", 3200, false, 'bs-namespacemanager-wrong-name' ],
			[ "Test", "Te-st", 3200, false, 'bs-namespacemanager-wrong-alias' ],
			[ "Test", "Te st", 3200, false, 'bs-namespacemanager-wrong-alias' ],
			[ "Test", "123Test", 3200, false, 'bs-namespacemanager-wrong-alias' ],
		];
	}

	public function checkExistsDataProvider() {
		return [
			// These are the cases for adding new namespaces
			[ "Test", "Toast", 3200, true, null ],
			[ "Test", "Test", 3200, true, null ],
			[ "Test", "", 3200, true, null ],
			[ "Exists", "Test", 3200, false, 'bs-namespacemanager-ns-exists' ],
			[ "Test", "Exists", 3200, false, 'bs-namespacemanager-alias-exists-as-ns' ],
			[ "Exists", "Exists", 3200, false, 'bs-namespacemanager-ns-exists' ],
			[ "Test", "ExistsReally", 3200, false, 'bs-namespacemanager-alias-exists' ],
			// These are the cases for editing namespaces
			// No change in name and alias
			[ "(Pages)", "", 0, true, null ],
			[ "(Project)", "", 4, true, null ],
			[ "(Project)_talk", "", 5, true, null ],
			[ "Exists", "ExistsReally", 3000, true, null ],
			[ "SameNameAndAlias", "SameNameAndAlias", 3004, true, null ],
			 // Name changes
			[ "Test", "ExistsReally", 3000, true, null ],
			 // Alias changes
			[ "Exists", "Test", 3000, true, null ],
			[ "SomeNS", "ExistsReally", 3000, false, 'bs-namespacemanager-ns-exists' ],
			[ "Exists", "SomeNS", 3000, false, 'bs-namespacemanager-alias-exists-as-ns' ],
			[ "Test", "ExistsElsewhere", 3000, false, 'bs-namespacemanager-alias-exists' ],
		];
	}

	public function checkPseudoNamespaceDataProvider() {
		return [
			// Namespace name
			// totaly unrelated
			[ "Fun", "Realfun", true, null ],
			 // using "page" component but not namespace
			[ "Page", "Realtest", true, null ],
			 // using part of the pseudo namespace
			[ "Pseu", "Realtest", true, null ],
			 // using more than the pseudo namespace
			[ "Pseudofun", "Realtest", true, null ],
			[ "Pseudo", "Realtest", false, 'bs-namespacemanager-pseudo-ns' ],
			// Alias name
			[ "Test", "Fun", true, null ],
			[ "Test", "Page", true, null ],
			[ "Test", "Pseu", true, null ],
			[ "Test", "Pseudofun", true, null ],
			[ "Test", "Pseudo", false, 'bs-namespacemanager-pseudo-ns' ]
		];
	}

}
