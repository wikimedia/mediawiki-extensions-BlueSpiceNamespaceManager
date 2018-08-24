<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;

abstract class NamespaceManagerWriteNamespaceConfiguration extends Hook {
	/**
	 *
	 * @var string
	 */
	protected $saveContent;

	/**
	 *
	 * @var string
	 */
	protected $constName;

	/**
	 *
	 * @var integer
	 */
	protected $ns;

	/**
	 *
	 * @var array
	 */
	protected $definition;

	/**
	 *
	 * @param string $saveContent
	 * @param string $constName
	 * @param integer $ns
	 * @param array $definition
	 * @return boolean
	 */
	public static function callback( &$saveContent, $constName, $ns, $definition ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$saveContent,
			$constName,
			$ns,
			$definition
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param string $saveContent
	 * @param string $constName
	 * @param integer $ns
	 * @param array $definition
	 */
	public function __construct( $context, $config, &$saveContent, $constName, $ns, $definition ) {
		parent::__construct( $context, $config );

		$this->saveContent = &$saveContent;
		$this->constName = $constName;
		$this->ns = $ns;
		$this->definition = $definition;
	}

	/**
	 * Convenience function - most of the extension will do the same thing here
	 *
	 * @param string $configVar - name of the global (bsg) variable
	 * @param string $nsManagerOptionName - name of the option as registered with NSManager
	 */
	protected function writeConfiguration( $configVar, $nsManagerOptionName ) {
		$enabledNamespaces = $this->getConfig()->get( $configVar );

		$currentlyActivated = in_array( $this->ns, $enabledNamespaces );

		$explicitlyDeactivated = false;
		if ( isset( $this->definition[$nsManagerOptionName] ) && $this->definition[$nsManagerOptionName] === false ) {
			$explicitlyDeactivated = true;
		}

		$explicitlyActivated = false;
		if ( isset( $this->definition[$nsManagerOptionName] ) && $this->definition[$nsManagerOptionName] === true ) {
			$explicitlyActivated = true;
		}

		if( ( $currentlyActivated && !$explicitlyDeactivated ) || $explicitlyActivated ) {
			$this->saveContent .= "\$GLOBALS['bsg$configVar'][] = {$this->constName};\n";
		}
	}
}
