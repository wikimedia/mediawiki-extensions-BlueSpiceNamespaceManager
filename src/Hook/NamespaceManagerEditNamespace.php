<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;

abstract class NamespaceManagerEditNamespace extends Hook {
	/**
	 *
	 * @var array
	 */
	protected $namespaceDefinition;

	/**
	 *
	 * @var int
	 */
	protected $nsId;

	/**
	 *
	 * @var array
	 */
	protected $additionalSettings;

	/**
	 *
	 * @var bool
	 */
	protected $useInternalDefaults;

	/**
	 *
	 * @param array &$namespaceDefinition
	 * @param int &$nsId
	 * @param array $additionalSettings
	 * @param bool $useInternalDefaults
	 * @return bool
	 */
	public static function callback( &$namespaceDefinition, &$nsId, $additionalSettings,
		$useInternalDefaults ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$namespaceDefinition,
			$nsId,
			$additionalSettings,
			$useInternalDefaults
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param IContextSource $context
	 * @param Config $config
	 * @param array &$namespaceDefinition
	 * @param int &$nsId
	 * @param array $additionalSettings
	 * @param bool $useInternalDefaults
	 */
	public function __construct( $context, $config, &$namespaceDefinition, &$nsId,
		$additionalSettings, $useInternalDefaults ) {
		parent::__construct( $context, $config );

		$this->namespaceDefinition = &$namespaceDefinition;
		$this->nsId = &$nsId;
		$this->additionalSettings = $additionalSettings;
		$this->useInternalDefaults = $useInternalDefaults;
	}
}
