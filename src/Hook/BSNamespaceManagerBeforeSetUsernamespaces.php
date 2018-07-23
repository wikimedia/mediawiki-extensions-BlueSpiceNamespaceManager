<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;

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
	 * @param array $bsSystemNamespaces
	 * @return boolean
	 */
	public static function callback( $namespaceManager, &$bsSystemNamespaces ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$namespaceManager,
			$bsSystemNamespaces
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param \BlueSpice\Extension $namespaceManager
	 * @param array $bsSystemNamespaces
	 */
	public function __construct( $context, $config, $namespaceManager, &$bsSystemNamespaces ) {
		parent::__construct( $context, $config );

		$this->namespaceManager = $namespaceManager;
		$this->bsSystemNamespaces = &$bsSystemNamespaces;
	}
}
