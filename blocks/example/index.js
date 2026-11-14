/**
 * Registers the block in the editor. The front end is rendered by render.php.
 */
import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null, // Dynamic block: nothing is saved, render.php outputs the HTML.
} );
