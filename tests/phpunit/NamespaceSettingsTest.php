<?php

namespace BlueSpice\Namespacemanager\Tests;

use BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings;
use BlueSpice\NamespaceManager\Hook\NamespaceManagerBeforePersistSettings\PersistNamespaceFlags;
use BlueSpice\NamespaceManager\NamespaceManager;
use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use PHPUnit\Framework\TestCase;

class NamespaceSettingsTest extends TestCase {
	/**
	 * @covers \BlueSpice\NamespaceManager\NamespaceManager::getUserNamespaces
	 * @covers \BlueSpice\NamespaceManager\NamespaceManager::getSystemNamespaces
	 * @covers \BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings::serialize
	 * @dataProvider provideSystemNamespaceFlags
	 */
	public function testPreserveSystemNamespaceOverrides( bool $enabled ): void {
		$hooks = $this->createMock( HookContainer::class );
		$hooks->method( 'run' )->willReturnCallback( static function ( $name, $args ) {
			if ( $name === 'NamespaceManagerBeforePersistSettings' ) {
				( new PersistNamespaceFlags() )->onNamespaceManagerBeforePersistSettings( ...$args );
			} elseif ( $name === 'NamespaceManagerCollectNamespaceProperties' ) {
				$args[2]['customFlag'] = !empty( $args[1]['testNamespaceFlags'][$args[0]] );
			}
			return true;
		} );
		$config = new NamespaceSettings( $hooks, [] );
		$raw = serialize( [
			'constants' => [ 'NS_MAIN' => 0, 'NS_PROJECT' => 4, 'NS_CUSTOM' => 3000 ],
			'globals' => [
				'wgExtraNamespaces' => [ 3000 => 'Custom' ],
				'wgNamespacesWithSubpages' => [ 0 => $enabled, 4 => !$enabled, 3000 => false ],
				'wgContentNamespaces' => $enabled ? [ 0 ] : [ 4 ],
				'testNamespaceFlags' => [ 0 => $enabled ],
			],
		] );
		$dynamicConfig = $this->createMock( DynamicConfigManager::class );
		$dynamicConfig->method( 'getConfigObject' )->willReturn( $config );
		$dynamicConfig->method( 'retrieveRaw' )->willReturnCallback( static function () use ( &$raw ) {
			return $raw;
		} );
		$manager = new NamespaceManager( $this->createMock( Config::class ), $hooks, $dynamicConfig );

		$this->assertSame( [ 3000 ], $manager->getUserNamespaces() );
		$this->assertSame( [ 0, 4 ], $manager->getSystemNamespaces() );
		$this->assertSame( [ 3000 ], array_keys( $manager->getUserNamespaces( true ) ) );
		$this->assertSame( [ 0, 4 ], array_keys( $manager->getSystemNamespaces( true ) ) );
		$this->assertSame( $enabled, $manager->getSystemNamespaces( true )[0]['customFlag'] );

		// Editing another namespace must preserve both enabled and disabled system overrides.
		$definitions = $manager->getUserNamespaces( true ) + $manager->getSystemNamespaces( true );
		$definitions[3000]['subpages'] = true;
		$raw = $config->serialize( [
			'constantsNames' => [ 0 => 'NS_MAIN', 4 => 'NS_PROJECT', 3000 => 'NS_CUSTOM' ],
			'userNSDefinition' => $definitions,
		] );
		$this->assertSame( $enabled, $manager->getSystemNamespaces( true )[0]['subpages'] );
		$this->assertSame( $enabled, $manager->getSystemNamespaces( true )[0]['content'] );
		$this->assertSame( !$enabled, $manager->getSystemNamespaces( true )[4]['subpages'] );
		$this->assertTrue( $manager->getUserNamespaces( true )[3000]['subpages'] );

		// Removing a user namespace must not remove system overrides.
		$definitions = $manager->getUserNamespaces( true ) + $manager->getSystemNamespaces( true );
		unset( $definitions[3000] );
		$raw = $config->serialize( [
			'constantsNames' => [ 0 => 'NS_MAIN', 4 => 'NS_PROJECT' ],
			'userNSDefinition' => $definitions,
		] );
		$this->assertSame( [], $manager->getUserNamespaces( true ) );
		$this->assertSame( $enabled, $manager->getSystemNamespaces( true )[0]['subpages'] );
		$this->assertSame( !$enabled, $manager->getSystemNamespaces( true )[4]['subpages'] );
	}

	public static function provideSystemNamespaceFlags(): array {
		return [
			'enabled' => [ true ],
			'disabled' => [ false ],
		];
	}

	/**
	 * @covers \BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings::apply
	 * @return void
	 */
	public function testApply() {
		$serialized = serialize( [ 'globals' => [
			'G1' => [
				'foo' => NS_FILE,
				'bar' => NS_TEMPLATE
			],
			'G2' => [ 'foo', 'bar' ],
			'G3' => [
				NS_FILE => 'foo'
			]
		] ] );

		$GLOBALS['X'] = [
			'foo' => NS_MAIN,
			'bar' => NS_TEMPLATE
		];
		$GLOBALS['G1'] = [
			'foo' => NS_MAIN,
			'dummy' => NS_MEDIAWIKI
		];
		$GLOBALS['G2'] = [ 'foo', 'test' ];
		$GLOBALS['G3'] = [
			NS_FILE => 'bar',
			NS_MAIN => true
		];

		$expected = [
			'X' => [
				'foo' => NS_MAIN,
				'bar' => NS_TEMPLATE
			],
			'G1' => [
				'bar' => NS_TEMPLATE,
				'dummy' => NS_MEDIAWIKI,
				'foo' => NS_FILE,
			],
			'G2' => [ 'foo', 'bar' ],
			'G3' => [
				NS_MAIN => true,
				NS_FILE => 'foo',
			],
			'wgExtraSignatureNamespaces' => [],
		];

		$config = new NamespaceSettings( $this->createMock( HookContainer::class ), [] );
		$config->apply( $serialized );
		$subsetGlobals = [
			'X' => $GLOBALS['X'],
			'G1' => $GLOBALS['G1'],
			'G2' => $GLOBALS['G2'],
			'G3' => $GLOBALS['G3'],
			'wgExtraSignatureNamespaces' => []
		];
		$this->assertSame( $expected, $subsetGlobals );
	}

	/**
	 * @covers \BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings::apply
	 * @return void
	 */
	public function testApplyWithFixedContentNamespaces() {
		$fixedContentNamespaces = [ 1, 5 ];
		$serialized = serialize( [ 'globals' => [
			'wgContentNamespaces' => [ 0, 2 ],
		] ] );

		$GLOBALS['wgContentNamespaces'] = [ 1, 2, 3, 4 ];

		$expected = [
			'wgContentNamespaces' => [ 0, 2, 1, 5 ],
			'wgExtraSignatureNamespaces' => [],
		];

		$config = new NamespaceSettings( $this->createMock( HookContainer::class ), $fixedContentNamespaces );
		$config->apply( $serialized );
		$subsetGlobals = [
			'wgContentNamespaces' => $GLOBALS['wgContentNamespaces'],
			'wgExtraSignatureNamespaces' => []
		];
		$this->assertSame( $expected, $subsetGlobals );
	}
}
