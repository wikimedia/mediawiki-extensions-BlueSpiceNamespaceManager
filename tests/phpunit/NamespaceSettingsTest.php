<?php

namespace BlueSpice\Namespacemanager\Tests;

use BlueSpice\NamespaceManager\DynamicConfig\NamespaceSettings;
use MediaWiki\HookContainer\HookContainer;
use PHPUnit\Framework\TestCase;

class NamespaceSettingsTest extends TestCase {
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
			'G2' => [ 'foo', 'test', 'bar' ],
			'G3' => [
				NS_MAIN => true,
				NS_FILE => 'foo',
			],
			'wgExtraSignatureNamespaces' => [],
		];

		$config = new NamespaceSettings( $this->createMock( HookContainer::class ) );
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
}
