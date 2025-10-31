bs.util.registerNamespace( 'bs.namespaceManager.ui.dialog' );

bs.namespaceManager.ui.dialog.EditNamespaceDialog = function ( cfg ) {
	bs.namespaceManager.ui.dialog.EditNamespaceDialog.parent.call( this, cfg );
	this.isCreation = cfg.isCreation || false;
	this.fields = cfg.fields || [];
	this.item = cfg.item || {};
	this.checkboxValues = {};
};

OO.inheritClass( bs.namespaceManager.ui.dialog.EditNamespaceDialog, OO.ui.ProcessDialog );

bs.namespaceManager.ui.dialog.EditNamespaceDialog.static.name = 'editNamespaceDialog';
bs.namespaceManager.ui.dialog.EditNamespaceDialog.static.title = mw.msg( 'bs-namespacemanager-tipedit' );
bs.namespaceManager.ui.dialog.EditNamespaceDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-namespacemanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'cancel', label: mw.msg( 'bs-namespacemanager-cancel' ), flags: [ 'safe', 'close' ] }
];

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.getSetupProcess = function () {
	return bs.namespaceManager.ui.dialog.EditNamespaceDialog.parent.prototype.getSetupProcess.call( this ).next(
		function () {
			if ( this.isCreation ) {
				this.title
					.setLabel( mw.msg( 'bs-namespacemanager-tipadd' ) )
					.setTitle( mw.msg( 'bs-namespacemanager-tipadd' ) );
			}
		}, this
	);
};

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.initialize = function () {
	bs.namespaceManager.ui.dialog.EditNamespaceDialog.parent.prototype.initialize.call( this );

	this.content = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );
	this.nameInput = new OO.ui.TextInputWidget( {
		required: true,
		value: this.item.name,
		disabled: this.item.isSystemNS || this.item.isTalkNS
	} );
	this.nameInput.connect( this, {
		change: function () {
			if ( this.typingTimeout ) {
				clearTimeout( this.typingTimeout );
			}
			this.typingTimeout = setTimeout( () => {
				this.nameInput.getValidity().done( () => {
					this.onValidityCheck( true );
					this.nameInput.setValidityFlag( true );
				} ).fail( () => {
					this.onValidityCheck( false );
					this.nameInput.setValidityFlag( false );
				} );
			}, 500 );
		}
	} );

	this.aliasInput = new OO.ui.TextInputWidget( {
		value: this.item.alias,
		disabled: this.item.isTalkNS
	} );

	const options = [];
	for ( let i = 0; i < this.fields.length; i++ ) {
		const field = this.fields[ i ];
		if ( field.type !== 'boolean' ) {
			continue;
		}
		let value = this.item[ field.name ] || false;
		let disabled = false;
		if ( typeof value === 'object' ) {
			disabled = value.disabled || value.read_only;
			value = value.value;
		}
		options.push( new OO.ui.CheckboxMultioptionWidget( {
			data: field.name,
			label: field.label || field.name,
			selected: !!value,
			disabled: disabled
		} ) );
		this.checkboxValues[ field.name ] = false;
	}

	this.checkboxes = new OO.ui.CheckboxMultiselectWidget( {
		items: options,
		classes: [ 'bs-namespace-manager-checkboxes' ]
	} );

	this.content.$element.append(
		new OO.ui.FieldLayout( this.nameInput, {
			label: mw.message( 'bs-namespacemanager-labelnsname' ).text(),
			align: 'left'
		} ).$element,
		new OO.ui.FieldLayout( this.aliasInput, {
			label: mw.message( 'bs-namespacemanager-labelnsalias' ).text(),
			align: 'left'
		} ).$element,
		$( '<hr>' ).css( 'margin', '10px 0' ),
		this.checkboxes.$element
	);

	this.actions.setAbilities( { save: !this.isCreation } );
	this.$body.append( this.content.$element );
};

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.onValidityCheck = function ( valid ) {
	this.actions.setAbilities( { save: valid } );
};

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.getActionProcess = function ( action ) {
	return bs.namespaceManager.ui.dialog.EditNamespaceDialog.parent.prototype.getActionProcess.call( this, action ).next(
		function () {
			if ( action === 'save' ) {
				const selected = this.checkboxes.findSelectedItems().map( ( item ) => item.getData() );
				const settings = { alias: this.aliasInput.getValue() };
				for ( const checkboxkey in this.checkboxValues ) {
					if ( !this.checkboxValues.hasOwnProperty( checkboxkey ) ) {
						continue;
					}
					settings[ checkboxkey ] = selected.indexOf( checkboxkey ) !== -1;
				}
				const dfd = $.Deferred();
				this.pushPending();

				const handlers = {
					success: () => {
						this.close( { reload: true } );
					},
					failure: ( e ) => {
						this.popPending();
						dfd.reject( new OO.ui.Error( e.message ) );
					}
				};

				if ( this.isCreation ) {
					bs.api.tasks.exec(
						'namespace',
						'add',
						{
							name: this.nameInput.getValue(),
							settings: settings
						},
						handlers
					);
				} else {
					bs.api.tasks.exec(
						'namespace',
						'edit',
						{
							id: this.item.id,
							name: this.nameInput.getValue(),
							settings: settings
						},
						handlers
					);
				}
				return dfd.promise();
			} else {
				this.close( { reload: false } );
			}
		}, this
	);
};

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}
	return this.$body[ 0 ].scrollHeight;
};

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.namespaceManager.ui.dialog.EditNamespaceDialog.prototype.showErrors = function () {
	bs.namespaceManager.ui.dialog.EditNamespaceDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};
