<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;

abstract class BSApiNamespaceStoreMakeData extends Hook {
	/**
	 *
	 * @var array
	 */
	protected $results;

	/**
	 * Fired in BSApiNamespaceStore::makeData
	 *
	 * @param array $results
	 * @return boolean
	 */
	public static function callback( &$results ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$results
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param array $results
	 */
	public function __construct( $context, $config, &$results ) {
		parent::__construct( $context, $config );

		$this->results = &$results;
	}
}
