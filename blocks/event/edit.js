import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Placeholder } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { id } = attributes;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Event settings', 'luma-viewer' ) }>
					<TextControl
						label={ __( 'Event ID', 'luma-viewer' ) }
						value={ id }
						onChange={ ( value ) => setAttributes( { id: value } ) }
						help={ __( 'The Luma event api_id (e.g. evt-…).', 'luma-viewer' ) }
					/>
				</PanelBody>
			</InspectorControls>
			{ id ? (
				<ServerSideRender block="luma-viewer/event" attributes={ attributes } />
			) : (
				<Placeholder
					icon="tickets-alt"
					label={ __( 'Luma Event', 'luma-viewer' ) }
					instructions={ __(
						'Enter an event ID in the block settings.',
						'luma-viewer'
					) }
				/>
			) }
		</div>
	);
}
