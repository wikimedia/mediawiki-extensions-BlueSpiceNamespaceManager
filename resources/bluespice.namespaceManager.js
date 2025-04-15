$( () => {
	const $cnt = $( '#bs-namespacemanager-grid' );
	if ( $cnt.length ) {
		const panel = new bs.namespaceManager.ui.NamespaceManagerPanel( {
			fields: mw.config.get( 'bsNamespaceManagerMetaFields' )
		} );
		$cnt.html( panel.$element );
	}
} );
