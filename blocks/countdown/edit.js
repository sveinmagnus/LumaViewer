import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { id } = attributes;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Countdown', 'luma-viewer' ) }>
					<TextControl
						label={ __( 'Event ID', 'luma-viewer' ) }
						value={ id }
						onChange={ ( value ) => setAttributes( { id: value } ) }
						help={ __( 'Leave blank to count down to the next upcoming event.', 'luma-viewer' ) }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block="luma-viewer/countdown" attributes={ attributes } />
		</div>
	);
}
