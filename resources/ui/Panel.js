bs.util.registerNamespace( 'bs.namespaceManager.ui' );

bs.namespaceManager.ui.NamespaceManagerPanel = function( cfg ) {
	cfg = cfg || {};
	this.fields = cfg.fields || [];
	this.showingNonContent = false;
	this.showingTalk = true;
	this.selectedItem = null;

	let columns = {
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
			headerText: mw.msg('bs-namespacemanager-label-pagecount'),
			filter: { type: 'number' },
			sortable: true,
			valueParser: function( value, row ) {
				return new OO.ui.HtmlSnippet( row.allPagesLink );
			},
			width: 60,
			maxWidth: 140
		}
	};
	for( let i = 0; i < this.fields.length; i++ ) {
		const fieldDef = this.fields[i];
		if ( fieldDef.type === 'boolean' ) {
			columns[fieldDef.name] = {
				type: 'ns-manager-setting',
				headerText: fieldDef.label || fieldDef.name,
				filter: { type: 'boolean' },
				sortable: true,
				width: 60
			};
		} else {
			columns[fieldDef.name] = {
				type: fieldDef.type || 'text',
				headerText: fieldDef.headerText || fieldDef.label || fieldDef.name,
				filter: fieldDef.filter || {type: 'string'},
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
		shouldShow: function( row ) {
			return !row.isSystemNS;
		}
	};

	this.store = new OOJSPlus.ui.data.store.RemoteStore( {
		action: 'bs-namespace-store',
		filter: {
			content_raw: {
				type: 'boolean',
				value: true
			}
		}
	} );
	this.store.connect( this, {
		reload: function() {
			this.setAbilitiesOnSelection( null );
			this.selectedItem = null;
		}
	} );
	cfg.grid = {
		store: this.store,
		columns: columns,
		multiSelect: false,
		exportable: true,
		provideExportData: function () {
			const dfd = $.Deferred(),
				store = new OOJSPlus.ui.data.store.RemoteStore( {
					action: 'bs-namespace-store',
					limit: 9999,
					pageSize: 9999
				} );
			store.load().done( ( response ) => {
				const $table = $( '<table>' );
				let $row = $( '<tr>' );

				for ( let key in columns ) {
					if ( columns.hasOwnProperty( key ) ) {
						const column = columns[key];
						let $cell = $( '<th>' );
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
					for ( let key in columns ) {
						if ( !columns.hasOwnProperty( key ) ) {
							continue;
						}
						const column = columns[key];
						let $cell = $( '<td>' );
						if ( key === 'pageCount' ) {
							$cell.append( record.pageCount );
						} else if ( column.type === 'ns-manager-setting' ) {
							 if ( typeof record[key] === 'object' ) {
								$cell.append( record[key].value ? 'x' : '' );
							} else {
								$cell.append( record[key] ? 'x' : '' );
							}
						} else {
							$cell.append( record[key] );
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
};

OO.inheritClass( bs.namespaceManager.ui.NamespaceManagerPanel, OOJSPlus.ui.panel.ManagerGrid );

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.getToolbarActions = function() {
	var actions = [];
	actions.push( this.getAddAction( { icon: 'add', flags: [ 'progressive' ],  displayBothIconAndLabel: true } ) );
	actions.push( this.getEditAction( { displayBothIconAndLabel: true } ) );
	actions.push( this.getDeleteAction( { displayBothIconAndLabel: true } ) );

	const manager = this;
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'showNonContent',
		position: 'none',
		displayBothIconAndLabel: true,
		displayToggleCheckbox: true,
		active: this.showingNonContent,
		title: mw.msg( 'bs-namespacemanager-show-non-content-ns-label' ),
		callback: function() {
			if ( manager.showingNonContent ) {
				const filterFactory = new OOJSPlus.ui.data.FilterFactory();
				manager.store.filter( filterFactory.makeFilter( {
					type: 'boolean',
					value: true
				} ), 'content_raw' );
				this.setActive( false );
				manager.showingNonContent = false;
			} else {
				manager.store.filter( null, 'content_raw' );
				this.setActive( true );
				manager.showingNonContent = true;
			}
		}
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'showTalk',
		position: 'none',
		displayBothIconAndLabel: true,
		displayToggleCheckbox: true,
		title: mw.msg( 'bs-namespacemanager-show-talk-label' ),
		active: this.showingTalk,
		callback: function() {
			if ( manager.showingTalk ) {
				const filterFactory = new OOJSPlus.ui.data.FilterFactory();
				manager.store.filter( filterFactory.makeFilter( {
					type: 'boolean',
					value: false
				} ), 'isTalkNS' );
				this.setActive( false );
				manager.showingTalk = false;
			} else {
				manager.store.filter( null, 'isTalkNS' );
				this.setActive( true );
				manager.showingTalk = true;
			}
		}
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.List( {
		type: 'list',
		position: 'right',
		icon: 'menu',
		title: 'More',
		include: [ 'showTalk', 'showNonContent' ]
	} ));
	return actions;
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.onAction = function( action, row ) {
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
	if ( action === 'delete' && !row.isSystemNS ) {
		this.deleteNamespace( row );
	}
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.getInitialAbilities = function() {
	return {
		add: true,
		edit: false,
		delete: false
	};
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.onItemSelected = function ( item, selectedItems ) {
	this.setAbilitiesOnSelection( item.item );
	this.selectedItem = item.item;
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.setAbilitiesOnSelection = function( selectedItem ) {
	this.setAbilities( { edit: false, delete: false } );
	if ( !selectedItem ) {
		return;
	}
	if ( selectedItem.isSystemNS ) {
		this.setAbilities( { edit: true, delete: false } );
	} else {
		this.setAbilities( { edit: true, delete: true } );
	}
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.addNamespace = function() {
	var dialog = new bs.namespaceManager.ui.dialog.EditNamespaceDialog({
		fields: this.fields,
		isCreation: true
	} );
	this.openWindow( dialog );
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.editNamespace = function( row ) {
	var dialog = new bs.namespaceManager.ui.dialog.EditNamespaceDialog({
		fields: this.fields,
		isCreation: false,
		item: row
	} );
	this.openWindow( dialog );
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.deleteNamespace = function( row ) {
	var dialog = new bs.namespaceManager.ui.dialog.DeleteNamespaceDialog({
		id: row.id,
		nsName: row.name
	} );
	this.openWindow( dialog );
};

bs.namespaceManager.ui.NamespaceManagerPanel.prototype.openWindow = function( dialog ) {
	if ( !this.windowManager ) {
		this.windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( this.windowManager.$element );
	}
	this.windowManager.addWindows( [ dialog ] );
	this.windowManager.openWindow( dialog ).closed.then( function( data ) {
		if ( data && data.reload ) {
			this.store.reload();
		}
		this.windowManager.clearWindows();
	}.bind( this ) );
};
