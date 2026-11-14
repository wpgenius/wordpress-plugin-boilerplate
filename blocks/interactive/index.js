/**
 * Registers the block in the editor. The front end is rendered by render.php
 * and made interactive by view.js (Interactivity API).
 */
import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null, // Dynamic block: render.php outputs the HTML.
} );
