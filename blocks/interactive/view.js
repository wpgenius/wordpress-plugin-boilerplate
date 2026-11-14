/**
 * Front end: filters the list as the visitor types (Interactivity API).
 *
 * render.php puts the state in the HTML with data-wp-* directives:
 * - the block's context holds `search` and every item's lowercase title;
 * - each <li> adds its own `title` to the context;
 * - data-wp-bind--hidden shows or hides items using the getters below.
 */
import { store, getContext } from '@wordpress/interactivity';

const matches = ( title, search ) =>
	title.includes( search.trim().toLowerCase() );

store( 'wordpress-plugin-boilerplate/interactive', {
	state: {
		// Is the current item (an <li>) matching the search?
		get isVisible() {
			const context = getContext();
			return matches( context.title, context.search );
		},
		// Does nothing match? Shows the "No items match" message.
		get isEmpty() {
			const context = getContext();
			return ! context.titles.some( ( title ) =>
				matches( title, context.search )
			);
		},
	},
	actions: {
		updateSearch( event ) {
			getContext().search = event.target.value;
		},
	},
} );
