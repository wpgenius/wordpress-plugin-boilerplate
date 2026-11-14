/**
 * Front end: the "Load more" button of the [myplugin_items] shortcode.
 * Reads the next page from the REST route GET /wp-json/myplugin/v1/items
 * (src/Rest.php) and appends its rendered HTML.
 *
 * mypluginData (itemsUrl, i18n) comes from src/Frontend/Frontend.php.
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

		const url = new URL( mypluginData.itemsUrl );
		url.searchParams.set( 'page', page );
		url.searchParams.set( 'per_page', button.dataset.perPage );

		button.disabled = true;
		button.textContent = mypluginData.i18n.loading;

		// A public route: no nonce, so it keeps working on cached pages.
		fetch( url, { credentials: 'omit' } )
			.then( ( response ) =>
				response.json().then( ( data ) => {
					if ( ! response.ok ) {
						throw new Error( data && data.message );
					}
					return data;
				} )
			)
			.then( ( data ) => {
				list.insertAdjacentHTML( 'beforeend', data.html );
				button.dataset.page = page;
				if ( ! data.has_more ) {
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
