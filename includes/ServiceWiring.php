<?php

use BlueSpice\NamespaceManager\NamespaceManager;
use BlueSpice\NamespaceManager\NamespaceNuker;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [

	'BSNamespaceManager' => static function ( MediaWikiServices $services ) {
		// Deprecated since 5.0, use BlueSpiceNamespaceManager.Manager instead
		return $services->getService( 'BlueSpiceNamespaceManager.Manager' );
	},
	'BlueSpiceNamespaceManager.Manager' => static function ( MediaWikiServices $services ) {
		return new NamespaceManager(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$services->getHookContainer(),
			$services->getService( 'MWStakeDynamicConfigManager' )
		);
	},
	'BlueSpiceNamespaceManager.Nuker' => static function ( MediaWikiServices $services ) {
		return new NamespaceNuker(
			$services->getMovePageFactory(),
			$services->getDeletePageFactory(),
			$services->getTitleFactory(),
			$services->getDBLoadBalancer(),
			LoggerFactory::getInstance( 'BlueSpiceNamespaceManager.Nuker' )
		);
	},
];
