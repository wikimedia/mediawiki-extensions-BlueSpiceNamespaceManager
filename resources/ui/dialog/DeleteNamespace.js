bs.util.registerNamespace( 'bs.namespaceManager.ui.dialog' );

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog = function ( cfg ) {
	bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.parent.call( this, cfg );
	this.id = cfg.id;
	this.nsName = cfg.nsName;
};

OO.inheritClass( bs.namespaceManager.ui.dialog.DeleteNamespaceDialog, OO.ui.ProcessDialog );

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.static.name = 'deleteNamespaceDialog';
bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.static.title = mw.msg( 'bs-namespacemanager-tipremove' );
bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.static.actions = [
	{ action: 'delete', label: mw.msg( 'bs-namespacemanager-delete' ), flags: [ 'primary', 'destructive' ] },
	{ action: 'cancel', label: mw.msg( 'bs-namespacemanager-cancel' ), flags: [ 'safe', 'close' ] }
];

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.prototype.initialize = function () {
	bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.parent.prototype.initialize.call( this );
	this.content = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );
	const moveFromMsg = mw.message( 'bs-from-something', this.nsName ).text();
	this.nukeOptions = new OO.ui.RadioSelectInputWidget( {
		options: [
			{ label: mw.message( 'bs-namespacemanager-willdelete' ).text(), data: 'delete' },
			{ label: mw.message( 'bs-namespacemanager-willmove' ).text(), data: 'move' },
			{ label: mw.message( 'bs-namespacemanager-willmovesuffix', moveFromMsg ).text(), data: 'movesuffix' }
		],
		value: 'movesuffix'
	} );

	this.content.$element.append(
		new OO.ui.MessageWidget( {
			type: 'warning',
			label: mw.message( 'bs-namespacemanager-deletewarning' ).text()
		} ).$element,
		new OO.ui.FieldLayout( this.nukeOptions, {
			label: mw.message( 'bs-namespacemanager-pagepresent' ).text(),
			align: 'top'
		} ).$element
	);
	this.$body.append( this.content.$element );
};

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.prototype.getActionProcess = function ( action ) {
	return bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.parent.prototype.getActionProcess.call( this, action ).next(
		function () {
			if ( action === 'delete' ) {
				const dfd = $.Deferred();
				this.pushPending();
				const nukeAction = this.nukeOptions.getValue();

				const handlers = {
					success: () => {
						this.close( { reload: true } );
					},
					failure: ( e ) => {
						this.popPending();
						dfd.reject( new OO.ui.Error( e.message ) );
					}
				};

				bs.api.tasks.exec(
					'namespace',
					'remove',
					{
						id: this.id,
						pageAction: nukeAction
					},
					handlers
				);
				return dfd.promise();
			} else {
				this.close( { reload: false } );
			}
		}, this
	);
};

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}
	return this.$body[ 0 ].scrollHeight;
};

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.prototype.showErrors = function () {
	bs.namespaceManager.ui.dialog.DeleteNamespaceDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};
