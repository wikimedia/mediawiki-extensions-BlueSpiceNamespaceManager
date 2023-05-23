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

		// "base" represents default values of globals, before applying
		$baseGlobals = [
			'X' => [
				'foo' => NS_MAIN,
				'bar' => NS_TEMPLATE
			],
			'G1' => [
				'foo' => NS_MAIN,
				'dummy' => NS_MEDIAWIKI
			],
			'G2' => [ 'foo', 'test' ],
			'G3' => [
				NS_FILE => 'bar',
				NS_MAIN => true
			],
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
		$config->setMwGlobals( $baseGlobals );
		$config->apply( $serialized );
		$this->assertSame( $expected, $baseGlobals );
	}
}
