<?php

namespace BlueSpice\NamespaceManager\Tests;

use BlueSpice\NamespaceManager\SettingsComposer;
use MediaWikiIntegrationTestCase;

/**
 * @covers \BlueSpice\NamespaceManager\SettingsComposer
 */
class SettingsComposerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \BlueSpice\NamespaceManager\SettingsComposer::compose()
	 */
	public function testSuccess() {
		$constantNames = [
			0 => 'NS_MAIN',
			10 => 'NS_TEMPLATE',
			6 => 'NS_IMAGE',
		];

		$aliasesMap = [
			0 => [
				'(Pages)'
			],

			10 => [
				'Template',
				'Template'
			],

			6 => [
				'Image',
				'File'
			]
		];

		$this->setMwGlobals( 'wgHooks', [
			"NamespaceManager::writeNamespaceConfiguration" => [
				// phpcs:ignore
				"\\BlueSpice\\NamespaceManager\\Hook\\NamespaceManagerWriteNamespaceConfiguration\\WriteContentFlag::callback",
				// phpcs:ignore
				"\\BlueSpice\\NamespaceManager\\Hook\\NamespaceManagerWriteNamespaceConfiguration\\WriteSubPagesFlag::callback"
			]
		] );

		$settingsComposer = new SettingsComposer( $constantNames, $aliasesMap );

		$userNamespacesDefinitions = [
			0 => [
				'content' => true,
				'subpages' => true
			],

			10 => [
				'alias' => 'Template',
				'content' => '',
				'subpages' => true,
			],

			6 => [
				'content' => '',
				'subpages' => ''
			]
		];

		$actualContent = $settingsComposer->compose( $userNamespacesDefinitions );

		$expectedContent = file_get_contents( __DIR__ . '/data/SettingsComposer/nm-settings.result' );

		$this->assertEquals( $expectedContent, $actualContent );
	}

}
