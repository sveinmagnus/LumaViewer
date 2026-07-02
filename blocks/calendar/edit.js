import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const {
		view,
		tag,
		count,
		date,
		layout,
		group_by: groupBy,
		calendar,
		filters,
		past,
		pagination,
		chrome,
		quickview,
		offset,
		from,
		to,
		order,
		online,
		free,
		tags,
		excerpt_words: excerptWords,
		show_cover: showCover,
		show_location: showLocation,
		show_host: showHost,
		show_price: showPrice,
		show_excerpt: showExcerpt,
		show_tags: showTags,
		show_relative: showRelative,
	} = attributes;

	const triState = [
		{ label: __( 'Default', 'luma-viewer' ), value: '' },
		{ label: __( 'Show', 'luma-viewer' ), value: '1' },
		{ label: __( 'Hide', 'luma-viewer' ), value: '0' },
	];

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
							{ label: __( 'Week', 'luma-viewer' ), value: 'week' },
							{ label: __( 'Month', 'luma-viewer' ), value: 'month' },
							{ label: __( 'Day', 'luma-viewer' ), value: 'day' },
							{ label: __( 'Photo', 'luma-viewer' ), value: 'photo' },
							{ label: __( 'Summary', 'luma-viewer' ), value: 'summary' },
							{ label: __( 'Map', 'luma-viewer' ), value: 'map' },
							{ label: __( 'Carousel', 'luma-viewer' ), value: 'carousel' },
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
						label={ __( 'Anchor date', 'luma-viewer' ) }
						value={ date }
						onChange={ ( value ) => setAttributes( { date: value } ) }
						help={ __( 'YYYY-MM for Month, YYYY-MM-DD for Week/Day.', 'luma-viewer' ) }
					/>
					<SelectControl
						label={ __( 'List layout', 'luma-viewer' ) }
						value={ layout }
						options={ [
							{ label: __( 'Cards', 'luma-viewer' ), value: '' },
							{ label: __( 'Compact', 'luma-viewer' ), value: 'compact' },
							{ label: __( 'Minimal', 'luma-viewer' ), value: 'minimal' },
						] }
						help={ __( 'Applies to List, Week and Day views.', 'luma-viewer' ) }
						onChange={ ( value ) => setAttributes( { layout: value } ) }
					/>
					<SelectControl
						label={ __( 'Group list by', 'luma-viewer' ) }
						value={ groupBy }
						options={ [
							{ label: __( 'Day', 'luma-viewer' ), value: '' },
							{ label: __( 'Month', 'luma-viewer' ), value: 'month' },
							{ label: __( 'No grouping', 'luma-viewer' ), value: 'none' },
						] }
						help={ __( 'Applies to the List view.', 'luma-viewer' ) }
						onChange={ ( value ) => setAttributes( { group_by: value } ) }
					/>
					<TextControl
						label={ __( 'Calendar ID', 'luma-viewer' ) }
						value={ calendar }
						onChange={ ( value ) => setAttributes( { calendar: value } ) }
						help={ __( 'Organization mode only: limit to one calendar (its api_id).', 'luma-viewer' ) }
					/>
					<ToggleControl
						label={ __( 'Show search & category filters', 'luma-viewer' ) }
						checked={ !! filters }
						onChange={ ( value ) => setAttributes( { filters: value } ) }
						help={ __( 'Adds a search box and category chips above list-style views.', 'luma-viewer' ) }
					/>
					<ToggleControl
						label={ __( 'Include past events', 'luma-viewer' ) }
						checked={ !! past }
						onChange={ ( value ) => setAttributes( { past: value } ) }
						help={ __( 'Show recent past events as well as upcoming ones.', 'luma-viewer' ) }
					/>
					<SelectControl
						label={ __( 'Pagination', 'luma-viewer' ) }
						value={ pagination }
						options={ [
							{ label: __( 'Site default', 'luma-viewer' ), value: '' },
							{ label: __( 'Load more', 'luma-viewer' ), value: 'more' },
							{ label: __( 'Numbered pages', 'luma-viewer' ), value: 'numbers' },
						] }
						onChange={ ( value ) => setAttributes( { pagination: value } ) }
					/>
					<ToggleControl
						label={ __( 'Quick view', 'luma-viewer' ) }
						checked={ !! quickview }
						onChange={ ( value ) => setAttributes( { quickview: value } ) }
						help={ __( 'Open an event summary in a popup instead of leaving the page.', 'luma-viewer' ) }
					/>
					<ToggleControl
						label={ __( 'Show view switcher', 'luma-viewer' ) }
						checked={ chrome !== false }
						onChange={ ( value ) => setAttributes( { chrome: value } ) }
						help={ __( 'The row of view tabs (List, Month, Map…) above the calendar.', 'luma-viewer' ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Filtering & order', 'luma-viewer' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Order', 'luma-viewer' ) }
						value={ order }
						options={ [
							{ label: __( 'Default', 'luma-viewer' ), value: '' },
							{ label: __( 'Soonest first', 'luma-viewer' ), value: 'asc' },
							{ label: __( 'Latest first', 'luma-viewer' ), value: 'desc' },
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
					/>
					<SelectControl
						label={ __( 'Location', 'luma-viewer' ) }
						value={ online }
						options={ [
							{ label: __( 'Any', 'luma-viewer' ), value: '' },
							{ label: __( 'Online only', 'luma-viewer' ), value: 'online' },
							{ label: __( 'In person only', 'luma-viewer' ), value: 'in_person' },
						] }
						onChange={ ( value ) => setAttributes( { online: value } ) }
					/>
					<SelectControl
						label={ __( 'Price', 'luma-viewer' ) }
						value={ free }
						options={ [
							{ label: __( 'Any', 'luma-viewer' ), value: '' },
							{ label: __( 'Free only', 'luma-viewer' ), value: 'free' },
							{ label: __( 'Paid only', 'luma-viewer' ), value: 'paid' },
						] }
						onChange={ ( value ) => setAttributes( { free: value } ) }
					/>
					<TextControl
						label={ __( 'Tags', 'luma-viewer' ) }
						value={ tags }
						onChange={ ( value ) => setAttributes( { tags: value } ) }
						help={ __( 'Comma-separated; matches events with any of these tags.', 'luma-viewer' ) }
					/>
					<RangeControl
						label={ __( 'Skip first (offset)', 'luma-viewer' ) }
						value={ offset }
						min={ 0 }
						max={ 100 }
						onChange={ ( value ) => setAttributes( { offset: value } ) }
					/>
					<TextControl
						label={ __( 'From date', 'luma-viewer' ) }
						value={ from }
						onChange={ ( value ) => setAttributes( { from: value } ) }
						help={ __( 'YYYY-MM-DD lower bound (list-style views).', 'luma-viewer' ) }
					/>
					<TextControl
						label={ __( 'To date', 'luma-viewer' ) }
						value={ to }
						onChange={ ( value ) => setAttributes( { to: value } ) }
						help={ __( 'YYYY-MM-DD upper bound (list-style views).', 'luma-viewer' ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Card elements', 'luma-viewer' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Cover image', 'luma-viewer' ) }
						value={ showCover }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_cover: value } ) }
					/>
					<SelectControl
						label={ __( 'Location', 'luma-viewer' ) }
						value={ showLocation }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_location: value } ) }
					/>
					<SelectControl
						label={ __( 'Hosts', 'luma-viewer' ) }
						value={ showHost }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_host: value } ) }
					/>
					<SelectControl
						label={ __( 'Price / free badge', 'luma-viewer' ) }
						value={ showPrice }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_price: value } ) }
					/>
					<SelectControl
						label={ __( 'Description excerpt', 'luma-viewer' ) }
						value={ showExcerpt }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_excerpt: value } ) }
					/>
					<SelectControl
						label={ __( 'Tags', 'luma-viewer' ) }
						value={ showTags }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_tags: value } ) }
					/>
					<SelectControl
						label={ __( 'Relative date', 'luma-viewer' ) }
						value={ showRelative }
						options={ triState }
						onChange={ ( value ) => setAttributes( { show_relative: value } ) }
					/>
					<RangeControl
						label={ __( 'Excerpt length (words)', 'luma-viewer' ) }
						value={ excerptWords }
						min={ 0 }
						max={ 100 }
						help={ __( '0 uses the site default.', 'luma-viewer' ) }
						onChange={ ( value ) => setAttributes( { excerpt_words: value } ) }
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
