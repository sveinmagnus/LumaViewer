import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	RangeControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { view, tag, count, date } = attributes;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Calendar settings', 'luma-viewer' ) }>
					<SelectControl
						label={ __( 'View', 'luma-viewer' ) }
						value={ view }
						options={ [
							{ label: __( 'Site default', 'luma-viewer' ), value: '' },
							{ label: __( 'List', 'luma-viewer' ), value: 'list' },
							{ label: __( 'Month', 'luma-viewer' ), value: 'month' },
							{ label: __( 'Day', 'luma-viewer' ), value: 'day' },
							{ label: __( 'Photo', 'luma-viewer' ), value: 'photo' },
							{ label: __( 'Summary', 'luma-viewer' ), value: 'summary' },
						] }
						onChange={ ( value ) => setAttributes( { view: value } ) }
					/>
					<TextControl
						label={ __( 'Category (tag)', 'luma-viewer' ) }
						value={ tag }
						onChange={ ( value ) => setAttributes( { tag: value } ) }
						help={ __( 'Show only events with this Luma tag.', 'luma-viewer' ) }
					/>
					<RangeControl
						label={ __( 'Number of events', 'luma-viewer' ) }
						value={ count }
						min={ 0 }
						max={ 50 }
						help={ __( '0 uses the site default.', 'luma-viewer' ) }
						onChange={ ( value ) => setAttributes( { count: value } ) }
					/>
					<TextControl
						label={ __( 'Month (YYYY-MM)', 'luma-viewer' ) }
						value={ date }
						onChange={ ( value ) => setAttributes( { date: value } ) }
						help={ __( 'Anchor month for the Month view.', 'luma-viewer' ) }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="luma-viewer/calendar"
				attributes={ attributes }
			/>
		</div>
	);
}
