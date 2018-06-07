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
	 * @param \Congif $config
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
}
