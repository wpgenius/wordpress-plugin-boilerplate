/**
 * Editor view: a setting for the number of items, plus a preview from render.php.
 * The filter box works on the front end (view.js does not run in the editor preview).
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'wordpress-plugin-boilerplate' ) }
				>
					<RangeControl
						label={ __(
							'Number of items',
							'wordpress-plugin-boilerplate'
						) }
						value={ attributes.count }
						onChange={ ( value ) =>
							setAttributes( { count: value } )
						}
						min={ 1 }
						max={ 50 }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
			/>
		</div>
	);
}
