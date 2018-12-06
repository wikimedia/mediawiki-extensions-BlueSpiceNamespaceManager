/**
 * NamespaceManager NamespaceDialog
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage NamespaceManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

Ext.define( 'BS.BlueSpiceNamespaceManager.NamespaceDialog', {
	extend: 'MWExt.Dialog',
	currentData: {},
	selectedData: {},
	makeItems: function() {
		this.tfNamespaceName = Ext.create( 'Ext.form.TextField', {
			fieldLabel: mw.message( 'bs-namespacemanager-labelnsname' ).plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'namespacename',
			allowBlank: false
		});

		this.tfNamespaceAlias = Ext.create( 'Ext.form.TextField', {
			fieldLabel: mw.message( 'bs-namespacemanager-labelnsalias' ).plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'namespacealias',
			allowBlank: true
		});


		var items = [
			this.tfNamespaceName,
			this.tfNamespaceAlias
		];
		this.checkboxControls = {};

		//TODO: this is not nice since it introduces an dependency
		var fieldDefs = mw.config.get('bsNamespaceManagerMetaFields');

		for( var i = 0; i < fieldDefs.length; i++ ) {
			var fieldDef = fieldDefs[i];
			if( fieldDef.type !== 'boolean' || fieldDef.name === 'isSystemNS' ) {
				continue;
			}
			if( fieldDef.name === 'isTalkNS' ) {
				continue;
			}

			var cbControl =  Ext.create( 'Ext.form.field.Checkbox', {
				boxLabel: fieldDef.label,
				name: 'cb-'+fieldDef.name
			});
			this.checkboxControls[fieldDef.name] = cbControl;
			items.push( cbControl );
		}

		return items;
	},
	resetData: function() {
		this.tfNamespaceName.reset();
		this.tfNamespaceAlias.reset();
		for( var name in this.checkboxControls ) {
			this.checkboxControls[name].reset();
		}

		this.callParent();
	},
	setData: function( obj ) {
		this.currentData = obj;
		if( this.currentData.isSystemNS || this.currentData.isTalkNS ) {
			this.tfNamespaceName.disable();
		}
		else {
			this.tfNamespaceName.enable();
		}

		if( this.currentData.isTalkNS ) {
			this.tfNamespaceAlias.disable();
		}

		this.tfNamespaceName.setValue( this.currentData.name );
		this.tfNamespaceAlias.setValue( this.currentData.alias );
		for( var name in this.checkboxControls ) {
			var value = this.currentData[name];
			if( typeof( value ) === 'object' ) {
				if( value.disabled && value.disabled === true ) {
					//If this field in not applicable to this NS - hide it
					this.checkboxControls[name].hide();
					continue;
				}

				if( value.read_only && value.read_only === true ) {
					//If field is applicable, but should not be changed,
					//show the value but disable it
					this.checkboxControls[name].disable();
				}
				value = value.value;
			}
			this.checkboxControls[name].setValue( value );
		}
	},
	getData: function() {
		this.selectedData.id = this.currentData.id;
		this.selectedData.name = this.tfNamespaceName.getValue();
		this.selectedData.alias = this.tfNamespaceAlias.getValue();
		for( var name in this.checkboxControls ) {
			this.selectedData[name] = this.checkboxControls[name].getValue();
		}

		return this.selectedData;
	}
} );
