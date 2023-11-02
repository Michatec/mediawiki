( function () {

	mw.htmlform = {};

	/**
	 * Allows custom data specific to HTMLFormField to be set for OOUI forms. This picks up the
	 * extra config from a matching PHP widget (defined in HTMLFormElement.php) when constructed using
	 * OO.ui.infuse().
	 *
	 * Currently only supports passing 'cond-state' data.
	 *
	 * @ignore
	 * @param {Object} [config] Configuration options
	 */
	mw.htmlform.Element = function ( config ) {
		// Configuration initialization
		config = config || {};

		// Properties
		this.condState = config.condState;
	};

	mw.htmlform.FieldLayout = function ( config ) {
		// Parent constructor
		mw.htmlform.FieldLayout.super.call( this, config );
		// Mixin constructors
		mw.htmlform.Element.call( this, config );
	};
	OO.inheritClass( mw.htmlform.FieldLayout, OO.ui.FieldLayout );
	OO.mixinClass( mw.htmlform.FieldLayout, mw.htmlform.Element );

	mw.htmlform.ActionFieldLayout = function ( config ) {
		// Parent constructor
		mw.htmlform.ActionFieldLayout.super.call( this, config );
		// Mixin constructors
		mw.htmlform.Element.call( this, config );
	};
	OO.inheritClass( mw.htmlform.ActionFieldLayout, OO.ui.ActionFieldLayout );
	OO.mixinClass( mw.htmlform.ActionFieldLayout, mw.htmlform.Element );

}() );
