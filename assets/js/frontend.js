/**
 * Front end: the "Load more" button of the [myplugin_items] shortcode.
 * Calls the myplugin_load_items AJAX action (src/Ajax.php) and appends the HTML.
 *
 * mypluginData (ajaxUrl, nonce, i18n) comes from src/Frontend/Frontend.php.
 */
/* global mypluginData */
( function () {
	document.addEventListener( 'click', function ( event ) {
		const button = event.target.closest( '.myplugin-items__more' );
		if ( ! button ) {
			return;
		}

		const list = button
			.closest( '.myplugin-items' )
			.querySelector( '.myplugin-items__list' );
		const page = parseInt( button.dataset.page, 10 ) + 1;
		const label = button.textContent;

		const body = new URLSearchParams( {
			action: 'myplugin_load_items',
			nonce: mypluginData.nonce,
			page,
			per_page: button.dataset.perPage,
		} );

		button.disabled = true;
		button.textContent = mypluginData.i18n.loading;

		fetch( mypluginData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		} )
			.then( ( response ) => response.json() )
			.then( ( result ) => {
				if ( ! result.success ) {
					throw new Error( result.data && result.data.message );
				}
				list.insertAdjacentHTML( 'beforeend', result.data.html );
				button.dataset.page = page;
				if ( ! result.data.has_more ) {
					button.remove();
				}
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-alert
				window.alert( error.message || mypluginData.i18n.error );
			} )
			.finally( () => {
				button.disabled = false;
				button.textContent = label;
			} );
	} );
} )();
