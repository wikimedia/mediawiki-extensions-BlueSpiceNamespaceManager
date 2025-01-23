<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;

abstract class BSNamespaceManagerBeforeSetUsernamespaces extends Hook {
	/**
	 *
	 * @var \BlueSpice\Extension
	 */
	protected $namespaceManager;

	/**
	 *
	 * @var array
	 */
	protected $bsSystemNamespaces;

	/**
	 *
	 * @param \BlueSpice\Extension $namespaceManager
	 * @param array &$systemNamespaces
	 * @return bool
	 */
	public static function callback( $namespaceManager, &$systemNamespaces ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$namespaceManager,
			$systemNamespaces
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param IContextSource $context
	 * @param Config $config
	 * @param \BlueSpice\Extension $namespaceManager
	 * @param array &$systemNamespaces
	 */
	public function __construct( $context, $config, $namespaceManager, &$systemNamespaces ) {
		parent::__construct( $context, $config );

		$this->namespaceManager = $namespaceManager;
		$this->bsSystemNamespaces = &$systemNamespaces;
	}
}
