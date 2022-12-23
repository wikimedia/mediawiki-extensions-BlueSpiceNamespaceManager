<?php

use BlueSpice\DynamicSettingsManager;
use BlueSpice\NamespaceManager\NamespaceManager;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [

	'BSNamespaceManager' => static function ( MediaWikiServices $services ) {
		return new NamespaceManager(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$services->getHookContainer(),
			DynamicSettingsManager::factory()
		);
	},

];

// @codeCoverageIgnoreEnd
