/**
 * Editor view: sidebar controls plus a live preview rendered by render.php.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

export default function Edit( { attributes, setAttributes } ) {
	const { count, layout } = attributes;

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
						value={ count }
						onChange={ ( value ) =>
							setAttributes( { count: value } )
						}
						min={ 1 }
						max={ 12 }
					/>
					<SelectControl
						label={ __( 'Layout', 'wordpress-plugin-boilerplate' ) }
						value={ layout }
						options={ [
							{
								label: __(
									'Grid',
									'wordpress-plugin-boilerplate'
								),
								value: 'grid',
							},
							{
								label: __(
									'List',
									'wordpress-plugin-boilerplate'
								),
								value: 'list',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
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
