<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;

abstract class NamespaceManagerGetMetaFields extends Hook {
	/**
	 *
	 * @var array
	 */
	protected $metaFields;

	/**
	 * Fired in SpecialNamespaceManager::execute
	 *
	 * @param array &$metaFields
	 * @return bool
	 */
	public static function callback( &$metaFields ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$metaFields
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param IContextSource $context
	 * @param Config $config
	 * @param array &$metaFields
	 */
	public function __construct( $context, $config, &$metaFields ) {
		parent::__construct( $context, $config );

		$this->metaFields = &$metaFields;
	}
}
