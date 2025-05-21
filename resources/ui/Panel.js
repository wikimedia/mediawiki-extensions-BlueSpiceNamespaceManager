bs.util.registerNamespace( 'bs.namespaceManager.ui' );

bs.namespaceManager.ui.NamespaceManagerPanel = function ( cfg ) {
	cfg = cfg || {};
	this.fields = cfg.fields || [];
	this.hidingNonContent = true;
	this.hidingTalk = true;
	this.selectedItem = null;

	const columns = {
		id: {
			type: 'number',
			headerText: mw.msg( 'bs-namespacemanager-label-id' ),
			filter: { type: 'number' },
			sortable: true,
			width: 70
		},
		name: {
			sticky: true,
			type: 'text',
			headerText: mw.msg( 'bs-namespacemanager-label-namespaces' ),
			filter: { type: 'string' },
			sortable: true,
			minWidth: 170,
			width: 170
		},
		pageCount: {
			type: 'ns-manager-setting',
			headerText: mw.msg( 'bs-namespacemanager-label-pagecount' ),
			filter: { type: 'number' },
			sortable: true,
			valueParser: ( value, row ) => new OO.ui.HtmlSnippet( row.allPagesLink ),
			width: 60,
			maxWidth: 140
		}
	};
	for ( let i = 0; i < this.fields.length; i++ ) {
		const fieldDef = this.fields[ i ];
		if ( fieldDef.type === 'boolean' ) {
			columns[ fieldDef.name ] = {
				type: 'ns-manager-setting',
				headerText: fieldDef.label || fieldDef.name,
				filter: { type: 'boolean' },
				sortable: true,
				width: 60
			};
		} else {
			columns[ fieldDef.name ] = {
				type: fieldDef.type || 'text',
				headerText: fieldDef.headerText || fieldDef.label || fieldDef.name,
				filter: fieldDef.filter || { type: 'string' },
				sortable: fieldDef.sortable || false
			};
		}
	}

	columns.actionEdit = {
		type: 'action',
		title: mw.message( 'bs-namespacemanager-tipedit' ).text(),
		actionId: 'edit',
		icon: 'edit',
		headerText: mw.message( 'bs-namespacemanager-tipedit' ).text(),
		invisibleHeader: true,
		width: 30,
		visibleOnHover: true
	};
	columns.actionDelete = {
		type: 'action',
		title: mw.message( 'bs-namespacemanager-tipremove' ).text(),
		actionId: 'delete',
		icon: 'trash',
		headerText: mw.message( 'bs-namespacemanager-tipremove' ).text(),
		invisibleHeader: true,
		width: 30,
		visibleOnHover: true,
		shouldShow: ( row ) => !row.isSystemNS && !row.isTalkNS
	};

	this.store = new OOJSPlus.ui.data.store.RemoteStore( {
		action: 'bs-namespace-store',
		filter: {
			content_raw: { // eslint-disable-line camelcase
				type: 'boolean',
				value: true
			},
			isTalkNS: {
				type: 'boolean',
				value: false
			}
		}
	} );
	this.store.connect( this, {
		reload: () => {
			this.setAbilitiesOnSelection( null );
			this.selectedItem = null;
		}
	} );
	cfg.grid = {
		store: this.store,
		columns: columns,
		multiSelect: false,
		exportable: true,
		provideExportData: () => {
			const dfd = $.Deferred(),
				store = new OOJSPlus.ui.data.store.RemoteStore( {
					action: 'bs-namespace-store',
					limit: 9999,
					pageSize: 9999
				} );
			store.load().done( ( response ) => {
				const $table = $( '<table>' );
				let $row = $( '<tr>' );

				for ( const key in columns ) {
					if ( columns.hasOwnProperty( key ) ) {
						const column = columns[ key ];
						const $cell = $( '<th>' );
						$cell.append( column.headerText );
						$row.append( $cell );
					}
				}

				$table.append( $row );
				for ( const id in response ) {
					if ( !response.hasOwnProperty( id ) ) {
						continue;
					}
					$row = $( '<tr>' );
					const record = response[ id ];
					for ( const key in columns ) {
						if ( !columns.hasOwnProperty( key ) ) {
							continue;
						}
						const column = columns[ key ];
						const $cell = $( '<td>' );
						if ( key === 'pageCount' ) {
							$cell.append( record.pageCount );
						} else if ( column.type === 'ns-manager-setting' ) {
							if ( typeof record[ key ] === 'object' ) {
								$cell.append( record[ key ].value ? 'x' : '' );
							} else {
								$cell.append( record[ key ] ? 'x' : '' );
							}
						} else {
							$cell.append( record[ key ] );
						}
						$row.append( $cell );
					}
					$table.append( $row );
				}

				dfd.resolve( '<table>' + $table.html() + '</table>' );
			} ).fail( () => {
				dfd.reject( 'Failed to load data' );
			} );

			return dfd.promise();
		}
	};

	bs.namespaceManager.ui.NamespaceManagerPanel.parent.call( this, cfg );

	const hideTalkTool = this.toolbar.getTool( 'hideTalk' );
	hideTalkTool.setDisabled( true );
};

OO.inheritClass( bs.namespaceManager.ui.NamespaceManagerPanel, OOJSPlus.ui.panel.ManagerGrid );

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.getToolbarActions = function () {
	const actions = [];
	actions.push( this.getAddAction( { icon: 'add', flags: [ 'progressive' ], displayBothIconAndLabel: true } ) );
	actions.push( this.getEditAction( { displayBothIconAndLabel: true } ) );
	actions.push( this.getDeleteAction( { displayBothIconAndLabel: true } ) );

	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'hideNonContent',
		position: 'none',
		displayBothIconAndLabel: true,
		displayToggleCheckbox: true,
		active: this.hidingNonContent,
		title: mw.msg( 'bs-namespacemanager-hide-non-content-ns-label' ),
		callback: ( toolInstance ) => {
			if ( this.hidingNonContent ) {
				this.store.filter( null, 'content_raw' );
				toolInstance.setActive( false );
				this.hidingNonContent = false;
			} else {
				const filterFactory = new OOJSPlus.ui.data.FilterFactory();
				this.store.filter( filterFactory.makeFilter( {
					type: 'boolean',
					value: true
				} ), 'content_raw' );
				toolInstance.setActive( true );
				this.hidingNonContent = true;
			}
		}
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'hideTalk',
		position: 'none',
		displayBothIconAndLabel: true,
		displayToggleCheckbox: true,
		title: mw.msg( 'bs-namespacemanager-hide-talk-label' ),
		active: this.hidingTalk,
		callback: ( toolInstance ) => {
			if ( this.hidingTalk ) {
				this.store.filter( null, 'isTalkNS' );
				toolInstance.setActive( false );
				this.hidingTalk = false;
			} else {
				const filterFactory = new OOJSPlus.ui.data.FilterFactory();
				this.store.filter( filterFactory.makeFilter( {
					type: 'boolean',
					value: false
				} ), 'isTalkNS' );
				toolInstance.setActive( true );
				this.hidingTalk = true;
			}
		}
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.List( {
		type: 'list',
		position: 'right',
		icon: 'menu',
		title: 'More',
		include: [ 'hideTalk', 'hideNonContent' ]
	} ) );
	return actions;
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.onAction = function ( action, row ) {
	if ( action === 'hideNonContent' ) {
		// Disable hideTalkTool if hideNonContentTool is active
		const hideTalkTool = this.toolbar.getTool( 'hideTalk' );
		if ( this.hidingNonContent ) {
			// force implicit active
			hideTalkTool.setActive( true );
		} else {
			// restore original selection
			hideTalkTool.setActive( this.hidingTalk );
		}

		hideTalkTool.setDisabled( this.hidingNonContent );
	}

	if ( action === 'add' ) {
		this.addNamespace();
	}
	row = row || this.selectedItem;
	if ( !row ) {
		return;
	}
	if ( action === 'edit' ) {
		this.editNamespace( row );
	}
	if ( action === 'delete' && !row.isSystemNS && !row.isTalkNS ) {
		this.deleteNamespace( row );
	}
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.getInitialAbilities = function () {
	return {
		add: true,
		edit: false,
		delete: false
	};
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.onItemSelected = function ( item, selectedItems ) { // eslint-disable-line no-unused-vars
	this.setAbilitiesOnSelection( item.item );
	this.selectedItem = item.item;
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.setAbilitiesOnSelection = function ( selectedItem ) {
	this.setAbilities( { edit: false, delete: false } );
	if ( !selectedItem ) {
		return;
	}
	if ( selectedItem.isSystemNS || selectedItem.isTalkNS ) {
		this.setAbilities( { edit: true, delete: false } );
	} else {
		this.setAbilities( { edit: true, delete: true } );
	}
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.addNamespace = function () {
	const dialog = new bs.namespaceManager.ui.dialog.EditNamespaceDialog( {
		fields: this.fields,
		isCreation: true
	} );
	this.openWindow( dialog );
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.editNamespace = function ( row ) {
	const dialog = new bs.namespaceManager.ui.dialog.EditNamespaceDialog( {
		fields: this.fields,
		isCreation: false,
		item: row
	} );
	this.openWindow( dialog );
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.deleteNamespace = function ( row ) {
	const dialog = new bs.namespaceManager.ui.dialog.DeleteNamespaceDialog( {
		id: row.id,
		nsName: row.name
	} );
	this.openWindow( dialog );
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.openWindow = function ( dialog ) {
	if ( !this.windowManager ) {
		this.windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( this.windowManager.$element );
	}
	this.windowManager.addWindows( [ dialog ] );
	this.windowManager.openWindow( dialog ).closed.then( ( data ) => {
		if ( data && data.reload ) {
			this.store.reload();
		}
		this.windowManager.clearWindows();
	} );
};
