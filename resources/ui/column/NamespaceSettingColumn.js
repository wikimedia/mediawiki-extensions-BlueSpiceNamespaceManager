bs.util.registerNamespace( 'bs.namespaceManager.ui.column' );

bs.namespaceManager.ui.column.NamespaceSettingColumn = function ( cfg ) {
	bs.namespaceManager.ui.column.NamespaceSettingColumn.parent.call( this, cfg );
};

OO.inheritClass( bs.namespaceManager.ui.column.NamespaceSettingColumn, OOJSPlus.ui.data.column.Boolean );

bs.namespaceManager.ui.column.NamespaceSettingColumn.prototype.getHeader = function () {
	const $cell = $( '<th>' ).addClass(
		'oojsplus-data-gridWidget-cell oojsplus-data-gridWidget-column-header bs-namespace-manager-column-setting'
	);
	this.setWidth( $cell );

	this.headerButton = new OO.ui.ButtonWidget( {
		framed: false,
		label: this.headerText,
		invisibleLabel: this.invisibleLabel,
		classes: [ 'header-button' ]
	} );

	if ( this.sorter ) {
		this.headerButton.connect( this, {
			click: function () {
				this.toggleSort();
				const direction = this.sorter.getValue().direction ? this.sorter.getValue().direction : 'other';
				this.setSortValue( $cell, direction );
				this.emit( 'sort-update', $cell, direction );
			}
		} );
	}
	$cell.append();

	const $textCnt = $( '<div>' ).addClass( 'header-text' );
	$textCnt.append( this.headerButton.$element );
	$cell.append( $textCnt );
	return $cell;
};

bs.namespaceManager.ui.column.NamespaceSettingColumn.prototype.toggleSort = function ( clearOnly ) {
	clearOnly = clearOnly || false;
	const sortOptions = this.getSortOptions();
	const directions = sortOptions.directions;
	const indicators = sortOptions.indicators;
	const index = directions.indexOf( this.sortingDirection );
	let newIndex = index + 1 === directions.length ? 0 : index + 1;
	let indicator = indicators[ newIndex ];

	if ( clearOnly ) {
		newIndex = 0;
		indicator = indicators[ 0 ];
	}
	this.sortingDirection = directions[ newIndex ];
	this.sortIndicator.setIndicator( indicator );
	this.sorter.setDirection( this.sortingDirection );

	if ( !clearOnly ) {
		this.emit( 'sort', this.sortingDirection === null ? null : this.sorter, this.id );
	}
};

bs.namespaceManager.ui.column.NamespaceSettingColumn.prototype.getViewControls = function ( value ) {
	let disabled = false;
	if ( typeof value === 'object' ) {
		disabled = value.disabled;
		value = value.value;
	}
	const widget = new OO.ui.IconWidget( {
		icon: disabled ? 'subtract' : value ? 'color-check' : 'color-cross'
	} );
	widget.$element.attr( 'aria-label', disabled ? 'disabled' : value ? 'checked' : 'unchecked' );
	return widget;
};

bs.namespaceManager.ui.column.NamespaceSettingColumn.prototype.renderCell = function ( value, row ) {
	const $cell = bs.namespaceManager.ui.column.NamespaceSettingColumn.parent.prototype.renderCell.call( this, value, row );
	$cell.addClass( 'bs-namespace-manager-column-setting-cell' );
	return $cell;
};

OOJSPlus.ui.data.registry.columnRegistry.register( 'ns-manager-setting', bs.namespaceManager.ui.column.NamespaceSettingColumn );
