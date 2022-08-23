<?php

namespace BlueSpice\NamespaceManager;

use FatalError;
use MediaWiki\MediaWikiServices;
use MWException;

class SettingsComposer {

	/**
	 * Constants names map array, where key is namespace ID and value is name of namespace constant.
	 * For example, 0 => "NS_MAIN"
	 *
	 * @var array
	 */
	private $constantNames;

	/**
	 * List of namespaces with their aliases.
	 * Has such structure:
	 * <code>nsId => [ 'alias1', 'alias2' ]</code>
	 *
	 * @var array
	 */
	private $namespaceAliases;

	/**
	 * @param array $constantsNames Constants names map array, where key is namespace ID and
	 * value is name of namespace constant. For example, 0 => "NS_MAIN"
	 * @param array $namespaceAliases
	 */
	public function __construct( $constantsNames, $namespaceAliases ) {
		$this->constantNames = $constantsNames;
		$this->namespaceAliases = $namespaceAliases;
	}

	/**
	 * Composes namespace settings file content, depending on namespaces user definitions
	 *
	 * @param array $userNamespaceDefinition Array with user namespaces definitions.
	 * 		Result of {@link \NamespaceManager::getUserNamespaces()} should be used for that
	 * @return string Namespace settings file content
	 * @throws FatalError
	 * @throws MWException
	 */
	public function compose( $userNamespaceDefinition ) {
		$saveContent = "<?php\n\n";

		foreach ( $userNamespaceDefinition as $nsId => $definition ) {
			if ( empty( $definition ) ) {
				continue;
			}

			$constName = $this->constantNames[$nsId];

			$saveContent .= "// START Namespace {$constName}\n";
			$saveContent .= "if( !defined( \"{$constName}\" ) ) define(\"{$constName}\", {$nsId});\n";
			if ( $nsId >= 100 && isset( $definition['name'] ) && $definition['name'] !== '' ) {
				$saveContent .= "\$GLOBALS['wgExtraNamespaces'][{$constName}] = '" . $definition['name'] . "';\n";
			} elseif ( $nsId >= 100 && isset( $GLOBALS['wgExtraNamespaces'][$nsId] ) ) {
				$saveContent .= "\$GLOBALS['wgExtraNamespaces'][{$constName}] = '"
					. $GLOBALS['wgExtraNamespaces'][$nsId] . "';\n";
			}

			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->run( 'NamespaceManager::writeNamespaceConfiguration', [
				&$saveContent,
				$constName,
				$nsId,
				$definition
			] );
			if ( isset( $definition['alias'] ) ) {
				if ( !empty( $definition['alias'] ) ) {
					$saveContent .= "\$GLOBALS['wgNamespaceAliases']['{$definition['alias']}'] = {$constName};\n";
				}
			} else {
				$aliases = $this->namespaceAliases[$nsId];

				// Thing which will be always presented in aliases array - namespace title.
				// So if there is only 1 item in array, then it is namespace title.
				// We should not use namespace title as alias, so just skip such cases
				$isOnlyTitlePresented = count( $aliases ) === 1;
				if ( !empty( $aliases ) && !$isOnlyTitlePresented ) {
					$saveContent .= "\$GLOBALS['wgNamespaceAliases']['{$aliases[0]}'] = {$constName};\n";
				}
			}
			$saveContent .= "// END Namespace {$constName}\n\n";
		}

		return $saveContent;
	}

}
