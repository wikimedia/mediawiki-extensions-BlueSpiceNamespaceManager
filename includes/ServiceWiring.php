<?php

use BlueSpice\NamespaceManager\NamespaceManager;
use MediaWiki\MediaWikiServices;

return [

	'BSNamespaceManager' => static function ( MediaWikiServices $services ) {
		return new NamespaceManager(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$services->getHookContainer(),
			$services->getService( 'MWStakeDynamicConfigManager' )
		);
	},

];
