<?php

class BSApiNamespaceTasks extends BSApiTasksBase {

	protected $aTasks = [
		'add' => [
			'examples' => [
				[
					'name' => 'My namespace',
					'settings' => [
						'content' => true
					]
				]
			],
			'params' => [
				'name' => [
					'desc' => 'Name for namespace',
					'type' => 'string',
					'required' => true
				],
				'settings' => [
					'desc' => 'Array of settings in key/value pairs',
					'type' => 'array',
					'required' => true
				]
			]
		],
		'edit' => [
			'examples' => [
				[
					'id' => 123,
					'name' => 'My namespace',
					'settings' => [
						'content' => true
					]
				]
			],
			'params' => [
				'id' => [
					'desc' => 'ID of namespace to edit',
					'type' => 'integer',
					'required' => true
				],
				'name' => [
					'desc' => 'Name for namespace',
					'type' => 'string',
					'required' => true
				],
				'settings' => [
					'desc' => 'Array of settings in key/value pairs',
					'type' => 'array',
					'required' => true
				]
			]
		],
		'remove' => [
			'examples' => [
				[
					'id' => 123,
					'doArticle' => 1
				],
				[
					'id' => 123
				]
			],
			'params' => [
				'id' => [
					'desc' => 'ID of namespace to remove',
					'type' => 'integer',
					'required' => true
				],
				'doArticle' => [
					'desc' => 'Determines what happens to articles in this NS, can be 0,1,2',
					'type' => 'integer',
					'required' => false,
					'default' => 0
				]
			]
		]
	];

	protected function getRequiredTaskPermissions() {
		return [
			'add' => [ 'wikiadmin' ],
			'edit' => [ 'wikiadmin' ],
			'remove' => [ 'wikiadmin' ]
		];
	}

	/**
	 * Build the configuration for a new namespace and give it to the save method.
	 *
	 * @global string $wgReadOnly
	 * @global Language $wgContLang
	 * @param stdClass $oData
	 * @param stdClass $aParams
	 * @return BSStandardAPIResponse
	 */
	protected function task_add( $oData, $aParams ) {
		$sNamespace = $oData->name;

		$aAdditionalSettings = (array)$oData->settings;
		$sAlias = isset( $aAdditionalSettings['alias'] ) ? $aAdditionalSettings['alias'] : '';
		$sAlias = str_replace( ' ', '_', $sAlias );

		$oResult = $this->makeStandardReturn();

		global $wgContLang, $wgNamespaceAliases;
		$aNamespaces = $wgContLang->getNamespaces();
		$aUserNamespaces = NamespaceManager::getUserNamespaces( true );
		end( $aNamespaces );
		$iNS = key( $aNamespaces ) + 1;
		reset( $aNamespaces );

		$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );

		if ( $iNS < $config->get( 'NamespaceManagerNsOffset' ) ) {
			$iNS = $config->get( 'NamespaceManagerNsOffset' ) + 1;
		}

		$sResult = true;
		foreach ( $aNamespaces as $sKey => $sNamespaceFromArray ) {
			if ( strtolower( $sNamespaceFromArray ) == strtolower( $sNamespace ) ) {
				$oResult->message = wfMessage( 'bs-namespacemanager-ns-exists')->plain();
				return $oResult;
			}
		}

		if ( strlen( $sNamespace ) < 2 ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-ns-length' )->plain();
			return $oResult;
		// TODO MRG (06.11.13 11:17): Unicodefähigkeit?
		} else if ( !preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%i', $sNamespace ) ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-wrong-name' )->plain();
			return $oResult;
		} else if ( !empty( $sAlias ) && !preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%i', $sAlias ) ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-wrong-alias' )->plain();
			return $oResult;
		} else if ( $this->isAliasInUse( $iNS, $sAlias ) ) {
			$nsName = $aNamespaces[$wgNamespaceAliases[$sAlias]];
			$oResult->message = wfMessage( 'bs-namespacemanager-alias-exists', $nsName )->plain();
			return $oResult;
		} else {
			$aUserNamespaces[$iNS] = [ 'name' => $sNamespace, 'alias' => $sAlias ];

			Hooks::run( 'NamespaceManager::editNamespace', [ &$aUserNamespaces, &$iNS, $aAdditionalSettings, false ] );

			++$iNS;
			$aUserNamespaces[ ( $iNS ) ] = [
				'name' => $sNamespace . '_' . $wgContLang->getNsText( NS_TALK ),
				'alias' => $sAlias . '_talk'
			];

			Hooks::run( 'NamespaceManager::editNamespace', [ &$aUserNamespaces, &$iNS, $aAdditionalSettings, true ] );

			$aResult = NamespaceManager::setUserNamespaces( $aUserNamespaces );

			if($aResult[ 'success' ] === true) {
				// Create a log entry for the creation of the namespace
				$this->logTaskAction(
					'create',
					[ '4::namespace' => $sNamespace ]
				);
				$aResult['message'] = wfMessage( 'bs-namespacemanager-nsadded' )->plain();
			}

			$oResult->success = $aResult['success'];
			$oResult->message = $aResult['message'];
		}
		return $oResult;
	}

	/**
	 * Change the configuration of a given namespace and give it to the save method.
	 *
	 * @global string $wgReadOnly
	 * @global array $bsSystemNamespaces
	 * @global Language $wgContLang
	 * @param stdClass $oData
	 * @param stdClass $aParams
	 * @return BSStandardAPIResponse
	 */
	protected function task_edit( $oData, $aParams ) {
		$iNS = (int)$oData->id;
		$sNamespace = $oData->name;
		$aAdditionalSettings = (array)$oData->settings;
		$sAlias = isset( $aAdditionalSettings['alias'] ) ? $aAdditionalSettings['alias'] : '';
		$sAlias = str_replace( ' ', '_', $sAlias );

		$oResult = $this->makeStandardReturn();

		global $bsSystemNamespaces, $wgContLang, $wgNamespaceAliases;

		$oNamespaceManager =
			\MediaWiki\MediaWikiServices::getInstance()
			->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceNamespaceManager' );
		Hooks::run( 'BSNamespaceManagerBeforeSetUsernamespaces', [ $oNamespaceManager, &$bsSystemNamespaces ] );
		$aUserNamespaces = NamespaceManager::getUserNamespaces( true );

		if ( $iNS !== NS_MAIN && !$iNS ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-invalid-id' )->plain();
			return $oResult;
		}
		if ( strlen( $sNamespace ) < 2 ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-ns-length' )->plain();
			return $oResult;
		}
		if ( $iNS !== NS_MAIN && $iNS !== NS_PROJECT && $iNS !== NS_PROJECT_TALK
				&& !preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%', $sNamespace ) ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-wrong-name' )->plain();
			return $oResult;
		}
		if ( !empty( $sAlias ) && !preg_match( '%^[a-zA-Z_\\x80-\\xFF][a-zA-Z0-9_\\x80-\\xFF]{1,99}$%', $sAlias ) ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-wrong-alias' )->plain();
			return $oResult;
		}

		if ( $this->isAliasInUse( $iNS, $sAlias ) ) {
			$nsName = $wgContLang->getNamespaces()[$wgNamespaceAliases[$sAlias]];
			$oResult->message = wfMessage( 'bs-namespacemanager-alias-exists', $nsName )->plain();
			return $oResult;
		}

		if( isset( $bsSystemNamespaces[$iNS] ) ) {
			$sOriginalNamespaceName = $bsSystemNamespaces[ $iNS ];
		} else {
			$sOriginalNamespaceName = $aUserNamespaces[ $iNS ][ 'name' ];
		}

		if ( !isset( $bsSystemNamespaces[($iNS)] ) && strstr( $sAlias, '_' . $wgContLang->getNsText( NS_TALK ) ) ) {
				$aUserNamespaces[ $iNS ] = [
					'name' => $aUserNamespaces[ $iNS ][ 'name' ],
					'alias' => str_replace( '_' . $wgContLang->getNsText( NS_TALK ), '_talk', $sAlias ),
				];
			Hooks::run( 'NamespaceManager::editNamespace', [ &$aUserNamespaces, &$iNS, $aAdditionalSettings, false ] );
		} else {
			$aUserNamespaces[$iNS] = [
				'name' => $sNamespace,
				'alias' => $sAlias
			];

			if ( !isset( $bsSystemNamespaces[($iNS)] ) ) {
				$aUserNamespaces[($iNS + 1)]['name'] = $sNamespace . '_' . $wgContLang->getNsText( NS_TALK );
				$aUserNamespaces[($iNS + 1)]['alias'] = $sAlias . '_talk';
			}
			Hooks::run( 'NamespaceManager::editNamespace', [ &$aUserNamespaces, &$iNS, $aAdditionalSettings, false ] );
		}

		$aResult = NamespaceManager::setUserNamespaces( $aUserNamespaces );
		if( $aResult[ 'success' ] === true ) {
			// Create a log entry for the modification of the namespace
			if( $sOriginalNamespaceName == $sNamespace ) {
				$this->logTaskAction( 'modify', [
					'4::namespaceName' => $sOriginalNamespaceName
				] );
			} else {
				$this->logTaskAction( 'rename', [
					'4::namespaceName' => $sOriginalNamespaceName,
					'5::newNamespaceName' => $sNamespace
				] );
			}

			$aResult['message'] = wfMessage( 'bs-namespacemanager-nsedited' )->plain();
		}

		$oResult->success = $aResult['success'];
		$oResult->message = $aResult['message'];

		return $oResult;
	}

	/**
	 * Delete a given namespace.
	 *
	 * @global string $wgReadOnly
	 * @global Language $wgContLang
	 * @param stdClass $oData
	 * @param stdClass $aParams
	 * @return BSStandardAPIResponse
	 */
	protected function task_remove( $oData, $aParams ) {
		$oResult = $this->makeStandardReturn();
		$iNS = (int)$oData->id;

		if ( $iNS < 0 ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-invalid-id' )->plain();
			return $oResult;
		}

		global $wgContLang;
		$aUserNamespaces = NamespaceManager::getUserNamespaces( true );
		if ( !isset( $aUserNamespaces[$iNS] ) ) {
			$oResult->message = wfMessage( 'bs-namespacemanager-msgnoteditabledelete' )->plain();
			return $oResult;
		}

		$aNamespacesToRemove = [ [ $iNS, 0 ] ];
		$aNamespacesToRemoveNames = [];
		$sOriginalNamespace = $sNamespace = $aUserNamespaces[ $iNS ][ 'name' ];
		$aNamespacesToRemoveNames[] = $sNamespace;
		if ( strstr( $sNamespace, '_'.$wgContLang->getNsText( NS_TALK ) ) ) {
			if (isset($aUserNamespaces[ ($iNS -1 ) ])) {
				$oResult->message = wfMessage( 'bs-namespacemanager-nodeletetalk' )->plain();
				return $oResult;
			}
		}

		if ( isset( $aUserNamespaces[ ($iNS + 1) ] ) && strstr( $aUserNamespaces[ ($iNS + 1) ][ 'name' ], '_'.$wgContLang->getNsText( NS_TALK ) ) ) {
			$aNamespacesToRemove[] = [ ($iNS + 1), 1 ];
			$sNamespace = $aUserNamespaces[ ($iNS + 1) ][ 'name' ];
			$aNamespacesToRemoveNames[] = $sNamespace;
		}

		$bErrors = false;
		$iDoArticle = 0;
		if ( !empty( $oData->doArticle ) ){
			$iDoArticle = (int) $oData->doArticle;
		}

		switch ( $iDoArticle ) {
			case 0:
				foreach ( $aNamespacesToRemove as $aNamespace ) {
					$iNs = $aNamespace[0];
					if ( !NamespaceNuker::removeAllNamespacePages( $iNs, $aUserNamespaces[$iNs]['name'] ) ) {
						$bErrors = true;
					} else {
						$aUserNamespaces[ $aNamespace[ 0 ] ] = false;
					}
				}
				break;
			case 1:
				foreach ( $aNamespacesToRemove as $aNamespace ) {
					$iNs = $aNamespace[0];
					if ( !NamespaceNuker::moveAllPagesIntoMain( $iNs, $aUserNamespaces[$iNs]['name'] ) ) {
						$bErrors = true;
					} else {
						$aUserNamespaces[ $aNamespace[ 0 ] ] = false;
					}
				}
				break;
			case 2:
			default:
				foreach ( $aNamespacesToRemove as $aNamespace ) {
					$iNs = $aNamespace[0];
					if ( !NamespaceNuker::moveAllPagesIntoMain( $iNs, $aUserNamespaces[$iNs]['name'], true ) ) {
						$bErrors = true;
					} else {
						$aUserNamespaces[ $aNamespace[ 0 ] ] = false;
					}
				}
				break;
		}

		if ( !$bErrors ) {
			$aResult = NamespaceManager::setUserNamespaces( $aUserNamespaces );
			if( $aResult[ 'success' ] === true ) {
				// Create a log entry for the removal of the namespace
				foreach ($aNamespacesToRemoveNames as $nameSpace) {
					$this->logTaskAction(
						'remove',
						[ '4::namespace' => $nameSpace ]
					);
				}

				$oResult->success = $aResult[ 'success' ];
				$oResult->message = wfMessage( 'bs-namespacemanager-nsremoved' )->plain();
			}
		} else {
			$oResult->message = wfMessage( 'bs-namespacemanager-error_on_remove_namespace' )->plain();
			return $oResult;
		}

		return $oResult;
	}
	/**
	 * Logs NamespaceManager actions
	 *
	 * @param string $sAction
	 * @param array $aParams
	 * @param array $aOptions not used
	 * @param bool $bDoPublish
	 */
	public function logTaskAction( $sAction, $aParams, $aOptions = [], $bDoPublish = false) {
		$oTitle = SpecialPage::getTitleFor( 'WikiAdmin' );
		$oUser = RequestContext::getMain()->getUser();
		$oLogger = new ManualLogEntry( 'bs-namespace-manager', $sAction );
		$oLogger->setPerformer( $oUser );
		$oLogger->setTarget( $oTitle );
		$oLogger->setParameters( $aParams );
		$oLogger->insert();
	}

	protected function isAliasInUse( $ns, $alias ) {
		global $wgNamespaceAliases;

		if( empty( $alias ) || !isset( $wgNamespaceAliases[$alias] ) ) {
			return false;
		}
		if( $wgNamespaceAliases[$alias] === $ns ) {
			return false;
		}
		return true;
	}

}