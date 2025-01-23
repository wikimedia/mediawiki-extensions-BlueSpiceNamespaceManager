<?php

namespace BlueSpice\NamespaceManager\Hook;

use BlueSpice\Hook;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;

abstract class NamespaceManagerAfterAction extends Hook {
	/** @var array */
	protected $namespace;
	/** @var array */
	protected $talkNamespace;

	/**
	 * @param array $namespace
	 * @param array $talkNamespace
	 * @return bool
	 */
	public static function callback( $namespace, $talkNamespace ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$namespace,
			$talkNamespace
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param IContextSource $context
	 * @param Config $config
	 * @param array $namespace
	 * @param array $talkNamespace
	 */
	public function __construct( $context, $config, $namespace, $talkNamespace ) {
		parent::__construct( $context, $config );

		$this->namespace = $namespace;
		$this->talkNamespace = $talkNamespace;
	}

	/**
	 * @return int|null
	 */
	protected function getNamespaceId() {
		return isset( $this->namespace['id'] ) ? (int)$this->namespace['id'] : null;
	}

	/**
	 * @return int|null
	 */
	protected function getTalkNamespaceId() {
		return isset( $this->talkNamespace['id'] ) ? (int)$this->talkNamespace['id'] : null;
	}
}
