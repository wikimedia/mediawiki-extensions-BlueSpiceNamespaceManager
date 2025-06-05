<?php

use BlueSpice\Api\Response\Standard as StandardResponse;
use BlueSpice\NamespaceManager\Utils\NameChecker;
use MediaWiki\Context\RequestContext;
use MediaWiki\SpecialPage\SpecialPage;

class BSApiNamespaceTasks extends BSApiTasksBase {

	/**
	 *
	 * @var array
	 */
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

	/**
	 *
	 * @return array
	 */
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
	 * @param stdClass $oData
	 * @param array $aParams
	 * @return StandardResponse
	 */
	protected function task_add( $oData, $aParams ) { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName, Generic.Files.LineLength.TooLong
		$sNamespace = $oData->name;

		$aAdditionalSettings = (array)$oData->settings;
		$sAlias = isset( $aAdditionalSettings['alias'] ) ? $aAdditionalSettings['alias'] : '';
		$sAlias = str_replace( ' ', '_', $sAlias );

		$oResult = $this->makeStandardReturn();

		global $wgNamespaceAliases;
		$contLang = $this->services->getContentLanguage();
		$aNamespaces = $contLang->getNamespaces();
		$aUserNamespaces = $this->services->getService( 'BSNamespaceManager' )
			->getUserNamespaces( true );
		end( $aNamespaces );
		$iNS = key( $aNamespaces ) + 1;
		reset( $aNamespaces );

		$config = $this->getConfig();

		if ( $iNS < $config->get( 'NamespaceManagerNsOffset' ) ) {
			$iNS = $config->get( 'NamespaceManagerNsOffset' ) + 1;
		}

		$nameChecker = new NameChecker( $aNamespaces, $wgNamespaceAliases, $this->getDB(), $this );

		$oResult = $nameChecker->checkNamingConvention( $sNamespace, $sAlias, $iNS );
		if ( !$oResult->success ) {
			return $oResult;
		}

		$oResult = $nameChecker->checkExists( $sNamespace, $sAlias, $iNS );
		if ( !$oResult->success ) {
			return $oResult;
		}

		$oResult = $nameChecker->checkPseudoNamespace( $sNamespace, $sAlias );
		if ( !$oResult->success ) {
			return $oResult;
		}

		$aUserNamespaces[$iNS] = [ 'name' => $sNamespace, 'alias' => $sAlias ];

		$this->services->getHookContainer()->run( 'NamespaceManager::editNamespace', [
			&$aUserNamespaces,
			&$iNS,
			$aAdditionalSettings,
			false
		] );

		$talkNamespaceId = $iNS + 1;
		$aUserNamespaces[$talkNamespaceId] = [
			'name' => $sNamespace . '_' . $contLang->getNsText( NS_TALK ),
			'alias' => $sAlias . '_talk'
		];

		$this->services->getHookContainer()->run( 'NamespaceManager::editNamespace', [
			&$aUserNamespaces,
			&$talkNamespaceId,
			$aAdditionalSettings, true
		] );

		$aResult = $this->services->getService( 'BSNamespaceManager' )
			->setUserNamespaces( $aUserNamespaces );

		if ( $aResult[ 'success' ] === true ) {
			// Create a log entry for the creation of the namespace
			$this->logTaskAction(
				'create',
				[ '4::namespace' => $sNamespace ]
			);
			$aResult['message'] = wfMessage( 'bs-namespacemanager-nsadded' )->text();
			$this->services->getHookContainer()->run(
				'NamespaceManagerAfterAddNamespace',
				[
					$this->getNamespaceConfigWithId( $iNS, $aUserNamespaces ),
					$this->getNamespaceConfigWithId( $talkNamespaceId, $aUserNamespaces ),
				]
			);
		}

		$oResult->success = $aResult['success'];
		$oResult->message = $aResult['message'];

		return $oResult;
	}

	/**
	 * Change the configuration of a given namespace and give it to the save method.
	 *
	 * @param stdClass $oData
	 * @param array $aParams
	 * @return StandardResponse
	 */
	protected function task_edit( $oData, $aParams ) { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName, Generic.Files.LineLength.TooLong
		$bluespiceNamespaces = $this->getConfig()->get( 'SystemNamespaces' );

		$sNamespace = $oData->name;
		$aAdditionalSettings = (array)$oData->settings;
		$sAlias = isset( $aAdditionalSettings['alias'] ) ? $aAdditionalSettings['alias'] : '';
		$sAlias = str_replace( ' ', '_', $sAlias );

		$oResult = $this->makeStandardReturn();

		global $wgNamespaceAliases;

		$contLang = $this->services->getContentLanguage();
		$aNamespaces = $contLang->getNamespaces();

		$systemNamespaces = BsNamespaceHelper::getMwNamespaceConstants();
		$oNamespaceManager = $this->services->getService( 'BSExtensionFactory' )->getExtension(
			'BlueSpiceNamespaceManager'
		);
		$this->services->getHookContainer()->run(
			'BSNamespaceManagerBeforeSetUsernamespaces',
			[
				$oNamespaceManager,
				&$systemNamespaces
			]
		);
		$aUserNamespaces = $this->services->getService( 'BSNamespaceManager' )
			->getUserNamespaces( true );

		if ( !is_numeric( $oData->id ) ) {
			$oResult->message = $this->msg( 'bs-namespacemanager-invalid-id' )->text();
			return $oResult;
		}
		$iNS = (int)$oData->id;

		if ( !isset( $systemNamespaces[$iNS ] ) && !isset( $aUserNamespaces[$iNS] ) ) {
			$oResult->message = $this->msg( 'bs-namespacemanager-invalid-namespace' )->text();
			return $oResult;
		}

		$nameChecker = new NameChecker( $aNamespaces, $wgNamespaceAliases, $this->getDB(), $this );

		$oResult = $nameChecker->checkNamingConvention( $sNamespace, $sAlias, $iNS );
		if ( !$oResult->success ) {
			return $oResult;
		}

		$oResult = $nameChecker->checkExists( $sNamespace, $sAlias, $iNS );
		if ( !$oResult->success ) {
			return $oResult;
		}

		$oResult = $nameChecker->checkPseudoNamespace( $sNamespace, $sAlias );
		if ( !$oResult->success ) {
			return $oResult;
		}

		if ( isset( $systemNamespaces[$iNS] ) ) {
			$sOriginalNamespaceName = $systemNamespaces[ $iNS ];
		} else {
			$sOriginalNamespaceName = $aUserNamespaces[ $iNS ][ 'name' ];
		}

		if ( !in_array( $iNS, $bluespiceNamespaces ) && $iNS >= 3000 ) {
			if ( strstr( $sAlias, '_' . $contLang->getNsText( NS_TALK ) ) ) {
				$sAlias = str_replace( '_' . $contLang->getNsText( NS_TALK ), '_talk', $sAlias );
			}
			$aUserNamespaces[ $iNS ] = [
				'name' => $sNamespace,
				'alias' => $sAlias,
			];

			$talkId = $iNS + 1;
			// Make sure its an odd number
			if ( $talkId % 2 === 1 ) {
				$aUserNamespaces[$talkId]['name'] = $sNamespace . '_' . $contLang->getNsText( NS_TALK );
				if ( $sAlias ) {
					$aUserNamespaces[$talkId]['alias'] = $sAlias . '_talk';
				} else {
					$aUserNamespaces[$talkId]['alias'] = "";
				}
			}
		} else {
			$namespaceConfig = [
				'alias' => $sAlias
			];
			if ( isset( $aUserNamespaces[$iNS ]['name'] ) ) {
				$namespaceConfig['name'] = $aUserNamespaces[ $iNS ][ 'name' ];
			}
			$aUserNamespaces[$iNS] = $namespaceConfig;
		}
		$this->services->getHookContainer()->run(
			'NamespaceManager::editNamespace',
			[
				&$aUserNamespaces,
				&$iNS,
				$aAdditionalSettings,
				false
			]
		);

		$aResult = $this->services->getService( 'BSNamespaceManager' )
			->setUserNamespaces( $aUserNamespaces );
		if ( $aResult[ 'success' ] === true ) {
			// Create a log entry for the modification of the namespace
			if ( $sOriginalNamespaceName == $sNamespace ) {
				$this->logTaskAction( 'modify', [
					'4::namespaceName' => $sOriginalNamespaceName
				] );
			} else {
				$this->logTaskAction( 'rename', [
					'4::namespaceName' => $sOriginalNamespaceName,
					'5::newNamespaceName' => $sNamespace
				] );
			}

			$aResult['message'] = wfMessage( 'bs-namespacemanager-nsedited' )->text();
		}

		$oResult->success = $aResult['success'];
		$oResult->message = $aResult['message'];

		return $oResult;
	}

	/**
	 * Delete a given namespace.
	 *
	 * @param stdClass $oData
	 * @param array $aParams
	 * @return StandardResponse
	 */
	protected function task_remove( $oData, $aParams ) { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName, Generic.Files.LineLength.TooLong
		$oResult = $this->makeStandardReturn();
		$iNS = (int)$oData->id;

		if ( $iNS < 0 ) {
			$oResult->message = $this->msg( 'bs-namespacemanager-invalid-id' )->text();
			return $oResult;
		}

		$aUserNamespaces = $this->services->getService( 'BSNamespaceManager' )
			->getUserNamespaces( true );
		if ( !isset( $aUserNamespaces[$iNS] ) ) {
			$oResult->message = $this->msg( 'bs-namespacemanager-msgnoteditabledelete' )->text();
			return $oResult;
		}

		$namespceInfo = $this->services->getNamespaceInfo();
		$isTalkNS = $namespceInfo->isTalk( $iNS );
		try {
			$talkNS = $namespceInfo->getTalk( $iNS );
		} catch ( Throwable $e ) {
			// the given namespace doesn't have an associated talk namespace
		}

		$aNamespacesToRemove = [ [ $iNS, 0 ] ];
		$aNamespacesToRemoveNames = [];
		$sNamespace = $aUserNamespaces[ $iNS ][ 'name' ];
		$aNamespacesToRemoveNames[] = $sNamespace;
		if ( $isTalkNS ) {
			if ( $talkNS && isset( $aUserNamespaces[ $talkNS ] ) ) {
				$oResult->message = $this->msg( 'bs-namespacemanager-nodeletetalk' )->text();
				return $oResult;
			}
		}

		if ( $talkNS && isset( $aUserNamespaces[ $talkNS ] ) ) {
			$aNamespacesToRemove[] = [ $talkNS, 1 ];
			$sNamespace = $aUserNamespaces[ $talkNS ][ 'name' ];
			$aNamespacesToRemoveNames[] = $sNamespace;
		}

		$originalNamespaceConfig = [
			$iNS => $aUserNamespaces[$iNS]
		];
		if ( $talkNS ) {
			$originalNamespaceConfig[$talkNS] = $aUserNamespaces[$talkNS];
		}

		$bErrors = false;
		$iDoArticle = 0;
		if ( !empty( $oData->doArticle ) ) {
			$iDoArticle = (int)$oData->doArticle;
		}

		switch ( $iDoArticle ) {
			case 0:
				foreach ( $aNamespacesToRemove as $aNamespace ) {
					$iNs = $aNamespace[0];
					$success = NamespaceNuker::removeAllNamespacePages(
						$iNs,
						$aUserNamespaces[$iNs]['name']
					);
					if ( !$success ) {
						$bErrors = true;
					} else {
						$aUserNamespaces[ $aNamespace[ 0 ] ] = false;
					}
				}
				break;
			case 1:
				foreach ( $aNamespacesToRemove as $aNamespace ) {
					$iNs = $aNamespace[0];
					$success = NamespaceNuker::moveAllPagesIntoMain(
						$iNs,
						$aUserNamespaces[$iNs]['name']
					);
					if ( !$success ) {
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
					$success = NamespaceNuker::moveAllPagesIntoMain(
						$iNs,
						$aUserNamespaces[$iNs]['name'],
						true
					);
					if ( !$success ) {
						$bErrors = true;
					} else {
						$aUserNamespaces[ $aNamespace[ 0 ] ] = false;
					}
				}
				break;
		}

		if ( !$bErrors ) {
			$aResult = $this->services->getService( 'BSNamespaceManager' )
				->setUserNamespaces( $aUserNamespaces );
			if ( $aResult[ 'success' ] === true ) {
				// Create a log entry for the removal of the namespace
				foreach ( $aNamespacesToRemoveNames as $nameSpace ) {
					$this->logTaskAction(
						'remove',
						[ '4::namespace' => $nameSpace ]
					);
				}
				$namespacesToRemove = [ $this->getNamespaceConfigWithId( $iNS, $originalNamespaceConfig ) ];
				if ( $talkNS ) {
					$namespacesToRemove[] = $this->getNamespaceConfigWithId( $talkNS, $originalNamespaceConfig );
				}
				$this->services->getHookContainer()->run(
					'NamespaceManagerAfterRemoveNamespace',
					$namespacesToRemove
				);
				$oResult->success = $aResult[ 'success' ];
				$oResult->message = wfMessage( 'bs-namespacemanager-nsremoved' )->text();
			}
		} else {
			$oResult->message = $this->msg( 'bs-namespacemanager-error_on_remove_namespace' )->text();
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
	public function logTaskAction( $sAction, $aParams, $aOptions = [], $bDoPublish = false ) {
		$oTitle = SpecialPage::getTitleFor( 'WikiAdmin' );
		$oUser = RequestContext::getMain()->getUser();
		$oLogger = new ManualLogEntry( 'bs-namespace-manager', $sAction );
		$oLogger->setPerformer( $oUser );
		$oLogger->setTarget( $oTitle );
		$oLogger->setParameters( $aParams );
		$oLogger->insert();
	}

	/**
	 * @param int $id
	 * @param array $userNamespaces
	 * @return array
	 */
	private function getNamespaceConfigWithId( $id, array $userNamespaces ) {
		return array_merge( [
			'id' => $id,
		], $userNamespaces[$id] );
	}

}
