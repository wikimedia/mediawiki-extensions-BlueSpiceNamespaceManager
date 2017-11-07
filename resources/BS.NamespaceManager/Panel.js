/**
 * NamespaceManager Panel
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

Ext.define( 'BS.NamespaceManager.Panel', {
	extend: 'BS.CRUDGridPanel',
	requires: [ 'BS.store.BSApi' ],
	pageSize: 20,

	initComponent: function() {
		var fieldDefs = mw.config.get('bsNamespaceManagerMetaFields');
		var fields = [];
		var columns = [];

		//TODO: the "fieldDefs" should contain a "config" property that allows
		//for more settings than just the few ones we process here
		for( var i = 0; i < fieldDefs.length; i++ ) {
			var fieldDef = fieldDefs[i];
			fields.push( fieldDef.name );
			var column = {
				id: 'ns-'+ fieldDef.name,
				dataIndex: fieldDef.name,
				header: fieldDef.label,
				sortable: fieldDef.sortable || true,
				filter: fieldDef.filter || true,
				hidden: fieldDef.hidden || false
			};
			if( fieldDef.type === 'boolean' ) {
				if ( fieldDef.name === 'isSystemNS' ) {
					column.renderer = this.renderInvertedIcon;
				} else {
					column.renderer = this.renderIcon;
				}
				column.flex = 0.5;
			}
			if( i === 0 ){ //Typically the ID column
				column.flex = 0;
				column.width = 50;
			}
			columns.push( column );
		}

		$(document).trigger('BSNamespaceManagerInitCompontent', [this, fields, columns]);

		this.strMain = new BS.store.BSApi({
			apiAction: 'bs-namespace-store',
			fields: fields,
			pageSize: this.pageSize
		});

		this.colMainConf.columns = columns;
		this.callParent( arguments );
	},
	makeRowActions: function() {
		if( this.opPermitted( 'delete' ) ) {
			this.colMainConf.actions.unshift({
				glyph: true, //Needed to have the "BS.override.grid.column.Action" render an <span> instead of an <img>,
				tooltip: mw.message( 'bs-extjs-delete' ).plain(),
				handler: this.onActionRemoveClick,
				getClass: function( value, meta, record ) {
					if( record.get( 'isTalkNS' ) || record.get( 'isSystemNS' ) ) {
						return "x-hide-display";
					}
					return "bs-extjs-actioncolumn-icon bs-icon-cross destructive";
				},
				scope: this
			});
		}

		if( this.opPermitted( 'update' ) ) {
			this.colMainConf.actions.unshift({
				iconCls: 'bs-extjs-actioncolumn-icon bs-icon-wrench progressive',
				glyph: true,
				tooltip: mw.message( 'bs-extjs-edit' ).plain(),
				handler: this.onActionEditClick,
				scope: this
			});
		}

		return this.colMainConf.actions;
	},
	renderIcon: function( value ) {
		//TODO: make CSS class icon
		var icon = '<img src="' + mw.config.get( "wgScriptPath" ) + '/extensions/BlueSpiceFoundation/resources/bluespice/images/{0}"/>';
		if ( value === false ) {
			return icon.format( 'bs-cross.png');
		}
		return icon.format( 'bs-tick.png');
	},
	renderInvertedIcon: function( value ) {
		//TODO: make CSS class icon
		var icon = '<img src="' + mw.config.get( "wgScriptPath" ) + '/extensions/BlueSpiceFoundation/resources/bluespice/images/{0}"/>';
		if ( value === true ) {
			return icon.format( 'bs-cross.png');
		}
		return icon.format( 'bs-tick.png');
	},
	onGrdMainRowClick: function( oSender, iRowIndex, oEvent ) {
		this.callParent( oSender, iRowIndex, oEvent );

		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		var isSystemNS = selectedRow[0].get( 'isSystemNS' );
		var isTalkNS = selectedRow[0].get( 'isTalkNS' );

		if ( isSystemNS !== false || isTalkNS !== false ) {
			this.btnRemove.disable();
		}
	},
	onBtnAddClick: function( oButton, oEvent ) {
		if ( !this.dlgNamespaceAdd ) {
			this.dlgNamespaceAdd = Ext.create( 'BS.NamespaceManager.NamespaceDialog', {id:"bs-namespacemanager-add-dlg"} );
			this.dlgNamespaceAdd.on( 'ok', this.onDlgNamespaceAddOk, this );
		}

		//TODO: not nice. Decision on wether is "add" or "edit" shold be made
		//by the dialog depending on the provided ID. I.e. -1 for "add"
		this.active = 'add';
		this.dlgNamespaceAdd.setTitle( mw.message( 'bs-namespacemanager-tipadd' ).plain() );
		this.dlgNamespaceAdd.show();
		this.callParent( arguments );
	},
	onBtnEditClick: function( oButton, oEvent ) {
		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		if ( !this.dlgNamespaceEdit ) {
			this.dlgNamespaceEdit = Ext.create( 'BS.NamespaceManager.NamespaceDialog', {id:"bs-namespacemanager-edit-dlg"} );
			this.dlgNamespaceEdit.on( 'ok', this.onDlgNamespaceEditOk, this );
		}

		this.active = 'edit';
		this.dlgNamespaceEdit.setTitle( mw.message( 'bs-namespacemanager-tipedit' ).plain() );
		this.dlgNamespaceEdit.setData( selectedRow[0].getData() );
		this.dlgNamespaceEdit.show();
		this.callParent( arguments );
	},
	onBtnRemoveClick: function( oButton, oEvent ) {
		this.active = 'remove';
		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		var isSystemNS = selectedRow[0].get( 'isSystemNS' );
		var isTalkNS = selectedRow[0].get( 'isTalkNS' );
		if ( isSystemNS !== false ) {
			bs.util.alert(
				'NMfail',
				{
					textMsg: 'bs-namespacemanager-msgnoteditabledelete',
					titleMsg: 'bs-extjs-title-warning'
				}
			);
			return;
		}
		if ( isTalkNS !== false ) {
			bs.util.alert(
				'NMfail',
				{
					textMsg: 'bs-namespacemanager-nodeletetalk',
					titleMsg: 'bs-extjs-title-warning'
				}
			);
			return;
		}
		if ( !this.dlgNamespaceRemove ) {
			this.dlgNamespaceRemove = Ext.create(
				'BS.NamespaceManager.NamespaceRemoveDialog',
				{
					id: "bs-namespacemanager-remove-dlg",
					nsName: selectedRow[0].get( 'name' )
				}
			);
			this.dlgNamespaceRemove.on( 'ok', this.onDlgNamespaceRemoveOk, this );
		}

		this.dlgNamespaceRemove.setTitle( mw.message( 'bs-namespacemanager-tipremove' ).plain() );
		this.dlgNamespaceRemove.setData( selectedRow[0].getData() );
		this.dlgNamespaceRemove.show();
		this.callParent( arguments );
	},
	onDlgNamespaceAddOk: function( sender, namespace ) {
		var additionalSettings = this.getAdditionalSettings( namespace );
		var me = this;
		bs.api.tasks.exec(
			'namespace',
			'add',
			{
				name: namespace.name,
				settings: additionalSettings
			}
		).done( function( response ) {
			me.dlgNamespaceAdd.resetData();
			me.reloadStore();
		});
	},
	onDlgNamespaceEditOk: function( sender, namespace ) {
		var additionalSettings = this.getAdditionalSettings( namespace );
		var me = this;
		bs.api.tasks.exec(
			'namespace',
			'edit',
			{
				id: namespace.id,
				name: namespace.name,
				settings: additionalSettings
			}
		).done( function( response ) {
			me.dlgNamespaceEdit.resetData();
			me.reloadStore();
		});
	},
	getAdditionalSettings: function( data ) {
		var filteredData = {};
		for( var prop in data ) {
			if( $.inArray(prop, ['id', 'name', 'isSystemNS']) !== -1 ) {
				continue;
			}
			filteredData[prop] = data[prop];
		}
		return filteredData;
	},
	onDlgNamespaceRemoveOk: function( data, namespace ) {
		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		var id = selectedRow[0].get( 'id' );
		var doArticle = namespace.doarticle.rb;
		var me = this;
		bs.api.tasks.exec(
			'namespace',
			'remove',
			{
				id: id,
				doArticle: doArticle
			}
		).done( function( response ) {
			me.reloadStore();
		});
	},
	reloadStore: function() {
		this.strMain.reload();
	},
	showDlgAgain: function() {
		if ( this.active === 'add' ) {
			this.dlgNamespaceAdd.show();
		} else if( this.active === 'edit' ) {
			this.dlgNamespaceEdit.show();
		} else if( this.active === 'remove' ) {
			this.dlgNamespaceRemove.show();
		}
	}
} );