import { LandingRichText as RichText } from './outline-format';
import {
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	Button,
	ColorPalette,
	PanelBody,
	RangeControl,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { Fragment, useEffect, useRef, useState } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { colorPresets } from './color-presets';
import EditableButton from './EditableButton';
import SpacerInserter from './SpacerInserter';
import {
	BODY_FORMATS,
	ImageControl,
	MediaPreview,
	HEADING_FORMATS,
	SectionFrame,
	SectionHeading,
	useDefaults,
} from './common';

const SECTIONS = {
	services: {
		title: __( 'Services', 'rise-landing-pages' ),
		item: __( 'Service', 'rise-landing-pages' ),
		add: __( 'Add service', 'rise-landing-pages' ),
		min: 1,
		max: 6,
		grid: 'services-grid',
		card: 'service-card',
	},
	process: {
		title: __( 'How it works', 'rise-landing-pages' ),
		item: __( 'Step', 'rise-landing-pages' ),
		add: __( 'Add step', 'rise-landing-pages' ),
		min: 2,
		max: 6,
		grid: 'process-grid',
		card: 'process-step',
	},
	benefits: {
		title: __( 'Benefits', 'rise-landing-pages' ),
		item: __( 'Benefit', 'rise-landing-pages' ),
		add: __( 'Add benefit', 'rise-landing-pages' ),
		min: 1,
		max: 12,
		grid: 'benefits-grid',
		card: 'benefit',
	},
	faq: {
		title: __( 'Frequently asked questions', 'rise-landing-pages' ),
		item: __( 'Question', 'rise-landing-pages' ),
		add: __( 'Add question', 'rise-landing-pages' ),
		min: 1,
		max: 20,
		grid: 'faq-list',
		card: 'faq-item',
	},
};

const emptyItem = ( type, index ) => {
	if ( type === 'faq' ) {
		return {
			question: '',
			answer: '',
			pills: '',
			imageId: 0,
			imageUrl: '',
			imageAlt: '',
		};
	}
	const item = { title: '', description: '' };
	if ( type === 'services' || type === 'process' ) {
		Object.assign( item, { imageId: 0, imageUrl: '', imageAlt: '' } );
	}
	if ( type === 'services' ) {
		Object.assign( item, { ctaLabel: '', ctaUrl: '', ctaNewTab: false } );
	}
	if ( type === 'process' ) {
		item.number = String( index + 1 ).padStart( 2, '0' );
	}
	return item;
};

const processCirclePosition = ( index, count, direction = 'clockwise' ) => {
	// Keep step one anchored while reversing the path around the same positions.
	const position =
		direction === 'anticlockwise' && index > 0 ? count - index : index;
	const leftCount = Math.ceil( count / 2 );
	const rightCount = count - leftCount;
	const left = position === 0 || position > rightCount;
	let sideIndex = position - 1;
	if ( left ) {
		sideIndex = position === 0 ? 0 : count - position;
	}
	const sideCount = left ? leftCount : count - leftCount;
	let angle = left ? -90 : -270;
	if ( sideCount > 1 ) {
		angle =
			( left ? -45 : -315 ) +
			( ( left ? -90 : 90 ) * sideIndex ) / ( sideCount - 1 );
	}
	const row = left
		? sideIndex + 1
		: 1 +
		  Math.round(
				( sideIndex * ( leftCount - 1 ) ) / Math.max( 1, sideCount - 1 )
		  );
	const horizontalRatio =
		0.5 - 0.39 * Math.abs( Math.sin( ( angle * Math.PI ) / 180 ) );
	return {
		className: `rise-lp__process-step--${ left ? 'left' : 'right' }`,
		style: {
			'--rise-process-row': row,
			'--rise-process-angle': `${ angle }deg`,
			'--rise-process-horizontal-ratio': horizontalRatio.toFixed( 5 ),
		},
	};
};

const processNeedsTimeline = ( items ) =>
	items.some( ( item ) => {
		const title = String( item?.title || '' );
		const description = String( item?.description || '' );
		return (
			title.replace( /<[^>]*>/g, '' ).length > 40 ||
			( title.match( /<br\s*\/?>/gi ) || [] ).length > 2 ||
			description.replace( /<[^>]*>/g, '' ).length > 180
		);
	} );

function ProcessMarker( { item, index, mode, globalUrl, circle } ) {
	let image = '';
	if ( mode === 'global' ) {
		image = globalUrl;
	} else if ( mode === 'items' ) {
		image = item.imageUrl;
	}
	let marker;
	if ( image && mode === 'global' ) {
		marker = <img className="rise-lp__step-image" src={ image } alt="" />;
	} else if ( image ) {
		marker = (
			<MediaPreview
				className="rise-lp__step-image"
				attributes={ { ...item, mediaFit: 'contain' } }
			/>
		);
	} else {
		marker = (
			<span className="rise-lp__step-number" aria-hidden="true">
				{ item.number || String( index + 1 ).padStart( 2, '0' ) }
			</span>
		);
	}
	return circle ? (
		<span className="rise-lp__step-marker">{ marker }</span>
	) : (
		marker
	);
}

const faqDashPath = ( index ) => {
	const x = ( index * ( index + 21 ) ) / 2;
	const width = index + 2;
	return `M${ x + 9 } 1h${ width }l-9 30h-${ width }z`;
};

const itemPanelTitle = ( config, type, item, index ) => {
	const number = sprintf(
		/* translators: 1: item type, 2: item position. */
		__( '%1$s %2$d', 'rise-landing-pages' ),
		config.item,
		index + 1
	);
	let value = '';
	if ( type === 'faq' ) {
		value = item.question;
	} else if (
		type === 'benefits' ||
		type === 'process' ||
		type === 'services'
	) {
		value = item.title;
	}
	if ( ! value ) {
		return number;
	}
	const title = value
		.replace( /<[^>]*>/g, '' )
		.replace( /\[\/?(?:outline|size)[^\]]*\]/g, '' )
		.trim();
	return title ? `${ number } · ${ title.slice( 0, 48 ) }` : number;
};

const markerImageUrl = ( url, imageId = 0 ) => {
	if ( typeof url !== 'string' || ! /^(https?:\/\/|\/)/i.test( url ) ) {
		return '';
	}
	return imageId > 0 ||
		/\.(?:svg|png|jpe?g|gif|webp|avif)(?:[?#].*)?$/i.test( url )
		? url
		: '';
};

const serviceFeatures = ( item ) => {
	if ( Array.isArray( item.features ) ) {
		return item.features;
	}
	return typeof item.features === 'string' && item.features.trim()
		? item.features.split( /\r?\n/ )
		: [];
};

function BenefitMarkerControl( {
	label,
	imageId = 0,
	imageUrl = '',
	help,
	onChange,
} ) {
	const previewUrl = markerImageUrl( imageUrl, imageId );
	return (
		<div className="rise-lp-editor__marker-control">
			<p className="rise-lp-editor__control-label">{ label }</p>
			{ previewUrl && (
				<img
					className="rise-lp-editor__marker-preview"
					src={ previewUrl }
					alt=""
				/>
			) }
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ [ 'image' ] }
					value={ imageId }
					onSelect={ ( media ) =>
						onChange( {
							markerImageId: media.id || 0,
							markerSvgUrl: media.url || '',
						} )
					}
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ imageId
								? __(
										'Replace marker image',
										'rise-landing-pages'
								  )
								: __(
										'Choose from Media Library',
										'rise-landing-pages'
								  ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			<TextControl
				label={ __( 'Image URL', 'rise-landing-pages' ) }
				type="url"
				value={ imageUrl }
				onChange={ ( markerSvgUrl ) =>
					onChange( { markerImageId: 0, markerSvgUrl } )
				}
				help={ help }
			/>
			{ ( imageId > 0 || imageUrl ) && (
				<Button
					variant="tertiary"
					isDestructive
					onClick={ () =>
						onChange( { markerImageId: 0, markerSvgUrl: '' } )
					}
				>
					{ __( 'Remove marker image', 'rise-landing-pages' ) }
				</Button>
			) }
		</div>
	);
}

function ProcessMarkerControls( {
	attributes,
	setAttributes,
	processMarkerMode,
} ) {
	return (
		<>
			<SelectControl
				label={ __( 'Marker style', 'rise-landing-pages' ) }
				value={ processMarkerMode }
				options={ [
					{
						label: __(
							'Number or text for each step',
							'rise-landing-pages'
						),
						value: 'number',
					},
					{
						label: __(
							'One image for all steps',
							'rise-landing-pages'
						),
						value: 'global',
					},
					{
						label: __(
							'Image for each step',
							'rise-landing-pages'
						),
						value: 'items',
					},
				] }
				onChange={ ( markerMode ) => setAttributes( { markerMode } ) }
			/>
			{ processMarkerMode === 'global' && (
				<BenefitMarkerControl
					label={ __(
						'Shared step image or icon',
						'rise-landing-pages'
					) }
					imageId={ attributes.markerImageId || 0 }
					imageUrl={ attributes.markerSvgUrl || '' }
					onChange={ setAttributes }
					help={ __(
						'Choose an image or paste a direct image URL. Steps use their number until an image is set.',
						'rise-landing-pages'
					) }
				/>
			) }
			{ attributes.layout === 'circle' &&
				processMarkerMode !== 'number' && (
					<>
						<p className="rise-lp-editor__control-label">
							{ __(
								'Image circle background',
								'rise-landing-pages'
							) }
						</p>
						<ColorPalette
							colors={ colorPresets() }
							value={
								attributes.markerImageBackground || '#000000'
							}
							onChange={ ( markerImageBackground ) =>
								setAttributes( {
									markerImageBackground:
										markerImageBackground || '',
								} )
							}
							clearable
						/>
					</>
				) }
		</>
	);
}

function CenteredBenefitFields( {
	item,
	index,
	markerStyle,
	markerUrl,
	number,
	onChange,
	onOpen,
} ) {
	return (
		<>
			<div className="rise-lp__benefit-heading">
				{ markerStyle === 'check' && (
					<span className="rise-lp__check" aria-hidden="true">
						✓
					</span>
				) }
				{ markerStyle === 'number' && (
					<span
						className="rise-lp__check rise-lp__benefit-number"
						aria-hidden="true"
					>
						{ number }
					</span>
				) }
				{ markerStyle === 'svg' && markerUrl && (
					<span className="rise-lp__benefit-icon" aria-hidden="true">
						<img src={ markerUrl } alt="" />
					</span>
				) }
				<RichText
					tagName="h3"
					className={ `rise-lp__card-title${
						item.title ? '' : ' rise-lp-editor__empty-benefit-title'
					}` }
					data-placeholder={ __(
						'Item title',
						'rise-landing-pages'
					) }
					value={ item.title || '' }
					onChange={ ( title ) => onChange( { title } ) }
					onClick={ onOpen }
					onFocus={ onOpen }
					aria-label={ sprintf(
						/* translators: %d: benefit position. */
						__( 'Benefit %d title', 'rise-landing-pages' ),
						index + 1
					) }
					allowedFormats={ HEADING_FORMATS }
				/>
			</div>
			<RichText
				tagName="p"
				className={ `rise-lp__card-copy${
					item.description
						? ''
						: ' rise-lp-editor__empty-benefit-copy'
				}` }
				data-placeholder={ __(
					'Describe this item',
					'rise-landing-pages'
				) }
				value={ item.description || '' }
				onChange={ ( description ) => onChange( { description } ) }
				onClick={ onOpen }
				onFocus={ onOpen }
				aria-label={ sprintf(
					/* translators: %d: benefit position. */
					__( 'Benefit %d description', 'rise-landing-pages' ),
					index + 1
				) }
				allowedFormats={ BODY_FORMATS }
			/>
		</>
	);
}

function FaqPillControls( { value, onChange } ) {
	const entries = value ? value.split( /\r?\n/ ).slice( 0, 8 ) : [];
	const save = ( next ) => onChange( next.join( '\n' ) );
	return (
		<div className="rise-lp-editor__faq-labels">
			{ entries.length > 0 && (
				<ul className="rise-lp__faq-pills rise-lp-editor__faq-pills">
					{ entries.map( ( entry, index ) => (
						<li
							key={ index }
							style={ {
								'--rise-pill-input-width': `${ Math.min(
									120,
									Math.max(
										28,
										( entry.trim().length + 1 ) * 6
									)
								) }px`,
							} }
						>
							<TextControl
								aria-label={ sprintf(
									/* translators: %d: label position. */
									__( 'Label %d', 'rise-landing-pages' ),
									index + 1
								) }
								value={ entry.trim() ? entry : '' }
								placeholder={ __(
									'Label',
									'rise-landing-pages'
								) }
								onChange={ ( text ) => {
									const next = [ ...entries ];
									next[ index ] = text || ' ';
									save( next );
								} }
							/>
							<Button
								icon="no-alt"
								label={ __(
									'Remove label',
									'rise-landing-pages'
								) }
								onClick={ () =>
									save(
										entries.filter(
											( _, pillIndex ) =>
												pillIndex !== index
										)
									)
								}
							/>
						</li>
					) ) }
				</ul>
			) }
			<Button
				variant="tertiary"
				size="small"
				onClick={ () => save( [ ...entries, ' ' ] ) }
				disabled={ entries.length >= 8 }
			>
				{ __( '+ Add label', 'rise-landing-pages' ) }
			</Button>
		</div>
	);
}

export default function Collection( {
	type,
	attributes,
	setAttributes,
	clientId,
} ) {
	const config = SECTIONS[ type ];
	const sectionSpacerCount = useSelect(
		( select ) =>
			[ 'services', 'process' ].includes( type )
				? select( 'core/block-editor' )
						.getBlocks( clientId )
						.filter(
							( block ) => block.name === 'rise-landing/spacer'
						).length
				: 0,
		[ type, clientId ]
	);
	const { insertBlock } = useDispatch( 'core/block-editor' );
	const defaults = useDefaults();
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const serviceLayout = [ 'pricing', 'image' ].includes(
		attributes.cardLayout
	)
		? attributes.cardLayout
		: 'standard';
	const imageDesktopColumns = attributes.gridColumns === 4 ? 4 : 3;
	const [ faqActiveIndex, setFaqActiveIndex ] = useState( 0 );
	const [ openItemPanelIndex, setOpenItemPanelIndex ] = useState( null );
	const sliderTrackRef = useRef( null );
	const [ sliderState, setSliderState ] = useState( {
		first: 1,
		last: 1,
		overflow: false,
		atStart: true,
		atEnd: true,
	} );
	useEffect( () => {
		if ( type !== 'services' || attributes.displayMode !== 'slider' ) {
			return;
		}
		const track = sliderTrackRef.current;
		if ( ! track ) {
			return;
		}
		const update = () => {
			const rect = track.getBoundingClientRect();
			const visible = Array.from( track.children )
				.map( ( card, index ) => ( {
					rect: card.getBoundingClientRect(),
					index,
				} ) )
				.filter(
					( card ) =>
						card.rect.left < rect.right - 8 &&
						card.rect.right > rect.left + 8
				);
			const position = Math.abs( track.scrollLeft );
			const max = track.scrollWidth - track.clientWidth;
			setSliderState( {
				first: visible.length ? visible[ 0 ].index + 1 : 1,
				last: visible.length
					? visible[ visible.length - 1 ].index + 1
					: 1,
				overflow: max > 2,
				atStart: position < 2,
				atEnd: position >= max - 2,
			} );
		};
		const observer = new ResizeObserver( update );
		observer.observe( track );
		track.addEventListener( 'scroll', update, { passive: true } );
		update();
		return () => {
			observer.disconnect();
			track.removeEventListener( 'scroll', update );
		};
	}, [ type, attributes.displayMode, items.length, serviceLayout ] );
	const moveSlider = ( direction ) => {
		const track = sliderTrackRef.current;
		if ( ! track ) {
			return;
		}
		const rtl = getComputedStyle( track ).direction === 'rtl';
		const max = track.scrollWidth - track.clientWidth;
		const position = Math.abs( track.scrollLeft );
		if ( attributes.sliderLoop && direction > 0 && position >= max - 2 ) {
			track.scrollTo( { left: 0, behavior: 'smooth' } );
			return;
		}
		if ( attributes.sliderLoop && direction < 0 && position < 2 ) {
			track.scrollTo( {
				left: max * ( rtl ? -1 : 1 ),
				behavior: 'smooth',
			} );
			return;
		}
		const card = track.firstElementChild;
		const step =
			( card?.getBoundingClientRect().width || track.clientWidth ) +
			( parseFloat( getComputedStyle( track ).gap ) || 24 );
		track.scrollBy( {
			left: step * direction * ( rtl ? -1 : 1 ),
			behavior: 'smooth',
		} );
	};
	const hasPanelShortcuts =
		type === 'benefits' ||
		type === 'faq' ||
		type === 'process' ||
		type === 'services';
	const ItemPanelWrapper = hasPanelShortcuts ? 'div' : Fragment;
	const processMarkerMode =
		attributes.markerMode ||
		( items.some( ( item ) => item.imageUrl ) ? 'items' : 'number' );
	const processCircle = type === 'process' && attributes.layout === 'circle';
	const processCrowded = processCircle && processNeedsTimeline( items );
	const processGlobalMarkerUrl = markerImageUrl(
		attributes.markerSvgUrl,
		attributes.markerImageId
	);
	const faqMode = type === 'faq' ? attributes.faqMediaMode || 'text' : 'text';
	const defaultMarkerUrl = markerImageUrl(
		attributes.markerSvgUrl,
		attributes.markerImageId
	);
	const itemMarkerUrls = items.map(
		( item ) =>
			markerImageUrl( item.markerSvgUrl, item.markerImageId ) ||
			defaultMarkerUrl
	);
	const siteImageAnimation =
		window.riseLandingEditor?.settings?.image_animation === 'tiles'
			? __( 'Rise Fitness tiles', 'rise-landing-pages' )
			: __( 'Fade', 'rise-landing-pages' );
	let faqPreviewImage = attributes;
	if ( faqMode === 'items' ) {
		if ( items[ faqActiveIndex ]?.imageUrl ) {
			faqPreviewImage = items[ faqActiveIndex ];
		} else if ( ! attributes.imageUrl ) {
			faqPreviewImage = items.find( ( item ) => item.imageUrl );
		}
	}
	const faqHasMedia = faqMode !== 'text' && !! faqPreviewImage?.imageUrl;
	const faqLayout = `rise-lp__faq-layout rise-lp__faq-layout--${
		faqHasMedia
			? `media rise-lp__faq-layout--media-${
					attributes.faqImageSide === 'right' ? 'right' : 'left'
			  }`
			: 'text'
	}`;
	let layoutClass;
	if ( type === 'faq' ) {
		layoutClass = faqLayout;
	} else if ( type === 'benefits' ) {
		layoutClass = `rise-lp__benefits-layout rise-lp__benefits-layout--${
			[ 'right', 'center' ].includes( attributes.layout )
				? attributes.layout
				: 'left'
		}${
			attributes.layout === 'center' && attributes.centerItems
				? ' rise-lp__benefits-layout--items-centered'
				: ''
		}`;
	}
	const updateItem = ( index, values ) =>
		setAttributes( {
			items: items.map( ( item, i ) =>
				i === index ? { ...item, ...values } : item
			),
		} );
	const updateServiceButton = ( index, change ) => {
		const values = {};
		if ( 'label' in change ) {
			values.ctaLabel = change.label;
		}
		if ( 'url' in change ) {
			values.ctaUrl = change.url;
		}
		if ( 'newTab' in change ) {
			values.ctaNewTab = change.newTab;
		}
		updateItem( index, values );
	};
	const openPreviewItem = ( event, index ) => {
		if (
			type === 'services' ||
			event.target.closest?.(
				'.rise-lp__card-title, .rise-lp__card-copy, .rise-lp-editor__faq-question, .rise-lp__faq-answer'
			)
		) {
			setOpenItemPanelIndex( index );
			if ( type === 'faq' ) {
				setFaqActiveIndex( index );
			}
		}
	};
	const addItem = () => {
		if ( items.length < config.max ) {
			setAttributes( {
				items: [ ...items, emptyItem( type, items.length ) ],
			} );
		}
	};
	const moveItem = ( index, direction ) => {
		const destination = index + direction;
		if ( destination < 0 || destination >= items.length ) {
			return;
		}
		const next = [ ...items ];
		[ next[ index ], next[ destination ] ] = [
			next[ destination ],
			next[ index ],
		];
		if ( openItemPanelIndex === index ) {
			setOpenItemPanelIndex( destination );
		} else if ( openItemPanelIndex === destination ) {
			setOpenItemPanelIndex( index );
		}
		setAttributes( { items: next } );
	};
	const removeItem = ( index ) => {
		if ( items.length > config.min ) {
			if ( openItemPanelIndex === index ) {
				setOpenItemPanelIndex( null );
			} else if ( openItemPanelIndex > index ) {
				setOpenItemPanelIndex( openItemPanelIndex - 1 );
			}
			if ( type === 'faq' && faqActiveIndex >= index ) {
				setFaqActiveIndex( Math.max( 0, faqActiveIndex - 1 ) );
			}
			setAttributes( { items: items.filter( ( _, i ) => i !== index ) } );
		}
	};
	return (
		<SectionFrame
			{ ...{ attributes, setAttributes } }
			title={ config.title }
		>
			<InspectorControls>
				{ type === 'process' && (
					<PanelBody
						title={ __( 'Process layout', 'rise-landing-pages' ) }
					>
						<SelectControl
							label={ __( 'Layout', 'rise-landing-pages' ) }
							value={ attributes.layout || 'classic' }
							help={
								processCrowded
									? __(
											'Long step text is shown as a vertical timeline to keep it readable.',
											'rise-landing-pages'
									  )
									: undefined
							}
							options={ [
								{
									label: __(
										'Layout 1 — Columns',
										'rise-landing-pages'
									),
									value: 'classic',
								},
								{
									label: __(
										'Layout 2 — Circle',
										'rise-landing-pages'
									),
									value: 'circle',
								},
							] }
							onChange={ ( layout ) =>
								setAttributes( { layout } )
							}
						/>
						{ processCircle && (
							<SelectControl
								label={ __(
									'Circle direction',
									'rise-landing-pages'
								) }
								value={
									attributes.circleDirection || 'clockwise'
								}
								options={ [
									{
										label: __(
											'Clockwise',
											'rise-landing-pages'
										),
										value: 'clockwise',
									},
									{
										label: __(
											'Anticlockwise',
											'rise-landing-pages'
										),
										value: 'anticlockwise',
									},
								] }
								onChange={ ( circleDirection ) =>
									setAttributes( { circleDirection } )
								}
							/>
						) }
						<RangeControl
							label={ __(
								'Step title size',
								'rise-landing-pages'
							) }
							value={ attributes.stepTitleSize ?? 32 }
							min={ 16 }
							max={ 72 }
							step={ 1 }
							onChange={ ( stepTitleSize ) =>
								setAttributes( { stepTitleSize } )
							}
						/>
						<ProcessMarkerControls
							{ ...{
								attributes,
								setAttributes,
								processMarkerMode,
							} }
						/>
					</PanelBody>
				) }
				{ type === 'benefits' && (
					<PanelBody
						title={ __( 'Benefits layout', 'rise-landing-pages' ) }
					>
						<SelectControl
							label={ __(
								'Title position',
								'rise-landing-pages'
							) }
							value={ attributes.layout || 'left' }
							options={ [
								{
									label: __( 'Left', 'rise-landing-pages' ),
									value: 'left',
								},
								{
									label: __( 'Right', 'rise-landing-pages' ),
									value: 'right',
								},
								{
									label: __(
										'Centered above list',
										'rise-landing-pages'
									),
									value: 'center',
								},
							] }
							onChange={ ( layout ) =>
								setAttributes( { layout } )
							}
						/>
						{ attributes.layout === 'center' && (
							<ToggleControl
								label={ __(
									'Center benefit items',
									'rise-landing-pages'
								) }
								checked={ !! attributes.centerItems }
								onChange={ ( centerItems ) =>
									setAttributes( { centerItems } )
								}
							/>
						) }
						<SelectControl
							label={ __( 'List marker', 'rise-landing-pages' ) }
							value={ attributes.markerStyle || 'check' }
							options={ [
								{
									label: __(
										'Checkmark',
										'rise-landing-pages'
									),
									value: 'check',
								},
								{
									label: __(
										'No marker',
										'rise-landing-pages'
									),
									value: 'none',
								},
								{
									label: __(
										'Numbered',
										'rise-landing-pages'
									),
									value: 'number',
								},
								{
									label: __(
										'Custom image / SVG',
										'rise-landing-pages'
									),
									value: 'svg',
								},
							] }
							onChange={ ( markerStyle ) =>
								setAttributes( { markerStyle } )
							}
						/>
						{ attributes.markerStyle === 'svg' && (
							<BenefitMarkerControl
								label={ __(
									'Default marker image',
									'rise-landing-pages'
								) }
								imageId={ attributes.markerImageId || 0 }
								imageUrl={ attributes.markerSvgUrl || '' }
								onChange={ setAttributes }
								help={ __(
									'Used for benefits without their own image. Choose an uploaded image or paste a direct SVG or image URL.',
									'rise-landing-pages'
								) }
							/>
						) }
					</PanelBody>
				) }
				{ type === 'services' && (
					<PanelBody
						title={ __( 'Services layout', 'rise-landing-pages' ) }
					>
						<SelectControl
							label={ __( 'Card layout', 'rise-landing-pages' ) }
							value={ serviceLayout }
							options={ [
								{
									label: __(
										'Layout 1 — classic cards',
										'rise-landing-pages'
									),
									value: 'standard',
								},
								{
									label: __(
										'Layout 2 — fitness pricing',
										'rise-landing-pages'
									),
									value: 'pricing',
								},
								{
									label: __(
										'Layout 3 — physio image cards',
										'rise-landing-pages'
									),
									value: 'image',
								},
							] }
							onChange={ ( cardLayout ) =>
								setAttributes( {
									cardLayout,
									...( cardLayout === 'image'
										? {
												gridColumns:
													imageDesktopColumns,
										  }
										: {} ),
									...( cardLayout === 'pricing' &&
									attributes.gridColumns === 3
										? { gridColumns: 2 }
										: {} ),
								} )
							}
						/>
						<SelectControl
							label={ __( 'Display', 'rise-landing-pages' ) }
							value={ attributes.displayMode || 'slider' }
							options={ [
								{
									label: __( 'Grid', 'rise-landing-pages' ),
									value: 'grid',
								},
								{
									label: __(
										'Swipeable slider',
										'rise-landing-pages'
									),
									value: 'slider',
								},
							] }
							onChange={ ( displayMode ) =>
								setAttributes( { displayMode } )
							}
						/>
						{ serviceLayout === 'image' &&
							attributes.displayMode !== 'slider' && (
								<SelectControl
									label={ __(
										'Card alignment',
										'rise-landing-pages'
									) }
									value={
										attributes.imageGridAlignment || 'start'
									}
									options={ [
										{
											label: __(
												'Start left',
												'rise-landing-pages'
											),
											value: 'start',
										},
										{
											label: __(
												'Center',
												'rise-landing-pages'
											),
											value: 'center',
										},
									] }
									onChange={ ( imageGridAlignment ) =>
										setAttributes( { imageGridAlignment } )
									}
								/>
							) }
						{ attributes.displayMode === 'slider' && (
							<>
								<ToggleControl
									label={ __(
										'Show slide counter',
										'rise-landing-pages'
									) }
									checked={
										attributes.sliderShowCounter !== false
									}
									onChange={ ( sliderShowCounter ) =>
										setAttributes( { sliderShowCounter } )
									}
								/>
								<ToggleControl
									label={ __(
										'Loop items',
										'rise-landing-pages'
									) }
									checked={ !! attributes.sliderLoop }
									onChange={ ( sliderLoop ) =>
										setAttributes( { sliderLoop } )
									}
								/>
								<ToggleControl
									label={ __(
										'Autoplay',
										'rise-landing-pages'
									) }
									checked={ !! attributes.sliderAutoplay }
									onChange={ ( sliderAutoplay ) =>
										setAttributes( { sliderAutoplay } )
									}
									help={ __(
										'Pauses while a visitor hovers or focuses the slider, and when reduced motion is requested.',
										'rise-landing-pages'
									) }
								/>
								{ attributes.sliderAutoplay && (
									<RangeControl
										label={ __(
											'Seconds between slides',
											'rise-landing-pages'
										) }
										value={
											attributes.sliderAutoplayInterval ??
											5
										}
										min={ 2 }
										max={ 15 }
										onChange={ ( sliderAutoplayInterval ) =>
											setAttributes( {
												sliderAutoplayInterval,
											} )
										}
									/>
								) }
							</>
						) }
						{ attributes.displayMode !== 'slider' && (
							<>
								<RangeControl
									label={ __(
										'Desktop columns',
										'rise-landing-pages'
									) }
									value={
										serviceLayout === 'image'
											? imageDesktopColumns
											: attributes.gridColumns ?? 3
									}
									min={ serviceLayout === 'image' ? 3 : 1 }
									max={ serviceLayout === 'image' ? 4 : 6 }
									onChange={ ( gridColumns ) =>
										setAttributes( { gridColumns } )
									}
								/>
								{ serviceLayout !== 'image' && (
									<RangeControl
										label={ __(
											'Mobile columns',
											'rise-landing-pages'
										) }
										value={
											attributes.mobileGridColumns ?? 1
										}
										min={ 1 }
										max={ 3 }
										onChange={ ( mobileGridColumns ) =>
											setAttributes( {
												mobileGridColumns,
											} )
										}
										help={ __(
											'Cards wrap when the screen is too narrow.',
											'rise-landing-pages'
										) }
									/>
								) }
								<RangeControl
									label={ __(
										'Space between cards',
										'rise-landing-pages'
									) }
									value={
										serviceLayout === 'image'
											? Math.max(
													attributes.gridGap ?? 24,
													24
											  )
											: attributes.gridGap ?? 24
									}
									min={ serviceLayout === 'image' ? 24 : 0 }
									max={ 80 }
									onChange={ ( gridGap ) =>
										setAttributes( { gridGap } )
									}
								/>
							</>
						) }
					</PanelBody>
				) }
				{ type === 'faq' && (
					<PanelBody
						title={ __( 'FAQ presentation', 'rise-landing-pages' ) }
					>
						<SelectControl
							label={ __(
								'Image display',
								'rise-landing-pages'
							) }
							value={ faqMode }
							options={ [
								{
									label: __(
										'Text and questions',
										'rise-landing-pages'
									),
									value: 'text',
								},
								{
									label: __(
										'One section image',
										'rise-landing-pages'
									),
									value: 'section',
								},
								{
									label: __(
										'Image for each question',
										'rise-landing-pages'
									),
									value: 'items',
								},
							] }
							onChange={ ( faqMediaMode ) =>
								setAttributes( { faqMediaMode } )
							}
						/>
						{ faqMode !== 'text' && (
							<>
								<SelectControl
									label={ __(
										'Image position',
										'rise-landing-pages'
									) }
									value={ attributes.faqImageSide || 'left' }
									options={ [
										{
											label: __(
												'Left image, right questions',
												'rise-landing-pages'
											),
											value: 'left',
										},
										{
											label: __(
												'Right image, left questions',
												'rise-landing-pages'
											),
											value: 'right',
										},
									] }
									onChange={ ( faqImageSide ) =>
										setAttributes( { faqImageSide } )
									}
								/>
								<SelectControl
									label={ __(
										'Image animation',
										'rise-landing-pages'
									) }
									value={
										attributes.faqImageAnimation ||
										'inherit'
									}
									options={ [
										{
											label: sprintf(
												/* translators: %s: sitewide image animation. */
												__(
													'Site default (%s)',
													'rise-landing-pages'
												),
												siteImageAnimation
											),
											value: 'inherit',
										},
										{
											label: __(
												'Fade',
												'rise-landing-pages'
											),
											value: 'fade',
										},
										{
											label: __(
												'Rise Fitness tiles',
												'rise-landing-pages'
											),
											value: 'tiles',
										},
									] }
									onChange={ ( faqImageAnimation ) =>
										setAttributes( { faqImageAnimation } )
									}
									help={ __(
										'Reveals the first image on scroll. Question images animate again when selected.',
										'rise-landing-pages'
									) }
								/>
								<ImageControl
									label={
										faqMode === 'section'
											? __(
													'Section image',
													'rise-landing-pages'
											  )
											: __(
													'Fallback image (optional)',
													'rise-landing-pages'
											  )
									}
									imagesOnly
									{ ...attributes }
									onChange={ ( values ) =>
										setAttributes( values )
									}
								/>
							</>
						) }
						<ToggleControl
							label={ __(
								'Show colour changing dashes',
								'rise-landing-pages'
							) }
							checked={ !! attributes.showDashes }
							onChange={ ( showDashes ) =>
								setAttributes( { showDashes } )
							}
						/>
					</PanelBody>
				) }
				{ type === 'faq' && (
					<PanelBody
						title={ __(
							'Accordion behaviour',
							'rise-landing-pages'
						) }
					>
						<ToggleControl
							label={ __(
								'Open only one answer at a time',
								'rise-landing-pages'
							) }
							checked={ attributes.singleOpen !== false }
							onChange={ ( singleOpen ) =>
								setAttributes( { singleOpen } )
							}
						/>
						<ToggleControl
							label={ __(
								'Open the first answer on page load',
								'rise-landing-pages'
							) }
							checked={ !! attributes.firstOpen }
							onChange={ ( firstOpen ) =>
								setAttributes( { firstOpen } )
							}
						/>
					</PanelBody>
				) }
				{ items.map( ( item, index ) => (
					<ItemPanelWrapper
						key={ index }
						{ ...( hasPanelShortcuts
							? {
									className:
										'rise-lp-editor__item-panel--quick-actions',
							  }
							: {} ) }
					>
						<PanelBody
							title={ itemPanelTitle(
								config,
								type,
								item,
								index
							) }
							initialOpen={ false }
							opened={ openItemPanelIndex === index }
							onToggle={ ( opened ) =>
								setOpenItemPanelIndex( opened ? index : null )
							}
						>
							{ type === 'benefits' && (
								<ToggleControl
									label={ __(
										'Show this benefit',
										'rise-landing-pages'
									) }
									checked={ ! item.hidden }
									onChange={ ( visible ) =>
										updateItem( index, {
											hidden: ! visible,
										} )
									}
								/>
							) }
							{ type === 'benefits' &&
								attributes.markerStyle === 'svg' && (
									<BenefitMarkerControl
										label={ __(
											'Marker image for this benefit',
											'rise-landing-pages'
										) }
										imageId={ item.markerImageId || 0 }
										imageUrl={ item.markerSvgUrl || '' }
										onChange={ ( values ) =>
											updateItem( index, values )
										}
										help={ __(
											'Overrides the default marker for this benefit. Leave empty to use the default.',
											'rise-landing-pages'
										) }
									/>
								) }
							{ type === 'process' &&
								processMarkerMode === 'number' && (
									<TextControl
										label={ __(
											'Step number or label',
											'rise-landing-pages'
										) }
										value={ item.number || '' }
										onChange={ ( number ) =>
											updateItem( index, { number } )
										}
									/>
								) }
							{ ( ( type === 'services' &&
								serviceLayout !== 'pricing' ) ||
								( type === 'process' &&
									processMarkerMode === 'items' ) ) && (
								<ImageControl
									imagesOnly={
										type === 'process' ||
										serviceLayout === 'image'
									}
									label={
										type === 'process'
											? __(
													'Step image or icon (optional)',
													'rise-landing-pages'
											  )
											: __(
													'Service image (optional)',
													'rise-landing-pages'
											  )
									}
									{ ...item }
									onChange={ ( values ) =>
										updateItem( index, values )
									}
								/>
							) }
							{ type === 'faq' && (
								<>
									{ faqMode === 'items' && (
										<ImageControl
											label={ __(
												'Image for this question',
												'rise-landing-pages'
											) }
											imagesOnly
											{ ...item }
											onChange={ ( values ) =>
												updateItem( index, values )
											}
										/>
									) }
									<TextareaControl
										label={ __(
											'Small labels below answer',
											'rise-landing-pages'
										) }
										help={ __(
											'One label per line, up to eight.',
											'rise-landing-pages'
										) }
										value={ item.pills || '' }
										onChange={ ( pills ) =>
											updateItem( index, { pills } )
										}
									/>
								</>
							) }
							{ type === 'services' &&
								serviceLayout !== 'pricing' &&
								( item.imageId > 0 || item.imageUrl ) && (
									<>
										<p className="rise-lp-editor__control-label">
											{ __(
												'Media overlay colour',
												'rise-landing-pages'
											) }
										</p>
										<ColorPalette
											value={ item.overlayColor || '' }
											onChange={ ( overlayColor ) =>
												updateItem( index, {
													overlayColor:
														overlayColor || '',
												} )
											}
											clearable
										/>
										<RangeControl
											label={ __(
												'Overlay opacity',
												'rise-landing-pages'
											) }
											value={ item.overlayOpacity ?? 0 }
											min={ 0 }
											max={ 95 }
											onChange={ ( overlayOpacity ) =>
												updateItem( index, {
													overlayOpacity,
												} )
											}
										/>
									</>
								) }
							{ type === 'services' && (
								<>
									{ serviceLayout === 'pricing' && (
										<>
											{ item.badgeEnabled ||
											item.badge ? (
												<>
													<TextControl
														label={ __(
															'Badge',
															'rise-landing-pages'
														) }
														value={
															item.badge || ''
														}
														onChange={ ( badge ) =>
															updateItem( index, {
																badge,
															} )
														}
													/>
													<Button
														variant="tertiary"
														isDestructive
														onClick={ () =>
															updateItem( index, {
																badge: '',
																badgeEnabled: false,
															} )
														}
													>
														{ __(
															'Remove badge',
															'rise-landing-pages'
														) }
													</Button>
												</>
											) : (
												<Button
													variant="secondary"
													icon="plus"
													onClick={ () =>
														updateItem( index, {
															badgeEnabled: true,
														} )
													}
												>
													{ __(
														'Add badge',
														'rise-landing-pages'
													) }
												</Button>
											) }
											<TextareaControl
												label={ __(
													'Features (one per line)',
													'rise-landing-pages'
												) }
												value={ serviceFeatures(
													item
												).join( '\n' ) }
												onChange={ ( features ) =>
													updateItem( index, {
														features: features
															? features.split(
																	/\r?\n/
															  )
															: [],
													} )
												}
											/>
											<TextControl
												label={ __(
													'Price',
													'rise-landing-pages'
												) }
												value={ item.price || '' }
												placeholder="EUR 75"
												onChange={ ( price ) =>
													updateItem( index, {
														price,
													} )
												}
											/>
											<TextControl
												label={ __(
													'Price period',
													'rise-landing-pages'
												) }
												value={
													item.priceQualifier || ''
												}
												placeholder={ __(
													'Per month',
													'rise-landing-pages'
												) }
												onChange={ ( priceQualifier ) =>
													updateItem( index, {
														priceQualifier,
													} )
												}
											/>
											<TextControl
												label={ __(
													'Price note',
													'rise-landing-pages'
												) }
												value={ item.priceNote || '' }
												placeholder={ __(
													'No commitment',
													'rise-landing-pages'
												) }
												onChange={ ( priceNote ) =>
													updateItem( index, {
														priceNote,
													} )
												}
											/>
										</>
									) }
								</>
							) }
						</PanelBody>
						{ hasPanelShortcuts && (
							<div className="rise-lp-editor__panel-shortcuts">
								<Button
									icon="arrow-up-alt2"
									label={ sprintf(
										/* translators: 1: item type, 2: item position. */
										__(
											'Move %1$s %2$d up',
											'rise-landing-pages'
										),
										config.item,
										index + 1
									) }
									onClick={ () => moveItem( index, -1 ) }
									disabled={ index === 0 }
								/>
								<Button
									icon="arrow-down-alt2"
									label={ sprintf(
										/* translators: 1: item type, 2: item position. */
										__(
											'Move %1$s %2$d down',
											'rise-landing-pages'
										),
										config.item,
										index + 1
									) }
									onClick={ () => moveItem( index, 1 ) }
									disabled={ index === items.length - 1 }
								/>
								<Button
									icon="trash"
									label={ sprintf(
										/* translators: 1: item type, 2: item position. */
										__(
											'Remove %1$s %2$d',
											'rise-landing-pages'
										),
										config.item,
										index + 1
									) }
									isDestructive
									onClick={ () => removeItem( index ) }
									disabled={ items.length <= config.min }
								/>
							</div>
						) }
					</ItemPanelWrapper>
				) ) }
				{ ( type === 'services' ||
					type === 'benefits' ||
					type === 'faq' ||
					type === 'process' ) && (
					<div className="rise-lp-editor__sidebar-add-item">
						<Button
							variant="secondary"
							onClick={ addItem }
							disabled={ items.length >= config.max }
						>
							{ config.add }
						</Button>
					</div>
				) }
			</InspectorControls>
			<section className={ `rise-lp__section rise-lp__${ type }` }>
				<div className="rise-lp__container">
					<div className={ layoutClass }>
						{ type === 'faq' && faqHasMedia && (
							<div className="rise-lp__faq-media">
								<figure className="rise-lp__faq-media-frame">
									<MediaPreview
										attributes={ faqPreviewImage }
										className="rise-lp__faq-image"
									/>
								</figure>
							</div>
						) }
						<div
							className={
								type === 'faq'
									? 'rise-lp__faq-content'
									: 'rise-lp-editor__collection-content'
							}
						>
							<SectionHeading
								{ ...{ attributes, setAttributes } }
							/>
							{ [ 'services', 'process' ].includes( type ) && (
								<div
									className={ `rise-lp-editor__${ type }-spacers` }
								>
									{ ! sectionSpacerCount && (
										<SpacerInserter
											className={ `rise-lp-editor__${ type }-spacer-slot` }
											label={
												type === 'services'
													? __(
															'Add a spacer before cards',
															'rise-landing-pages'
													  )
													: __(
															'Add a spacer before steps',
															'rise-landing-pages'
													  )
											}
											onClick={ () =>
												insertBlock(
													createBlock(
														'rise-landing/spacer'
													),
													0,
													clientId
												)
											}
										/>
									) }
									<InnerBlocks
										allowedBlocks={
											sectionSpacerCount
												? []
												: [ 'rise-landing/spacer' ]
										}
										renderAppender={ false }
									/>
								</div>
							) }
							<div
								className={ `rise-lp__${ config.grid }${
									processCircle
										? ` rise-lp__process-grid--circle${
												processCrowded
													? ' rise-lp__process-grid--crowded'
													: ''
										  }`
										: ''
								}${
									type === 'services'
										? ` rise-lp__services-grid--${ serviceLayout }${
												serviceLayout === 'image' &&
												attributes.gridColumns === 4
													? ' rise-lp__services-grid--image-four'
													: ''
										  }${
												serviceLayout === 'image' &&
												attributes.imageGridAlignment ===
													'center'
													? ' rise-lp__services-grid--image-centered'
													: ''
										  }${
												attributes.displayMode ===
												'slider'
													? ' rise-lp__services-grid--slider'
													: ''
										  }`
										: ''
								}` }
								style={
									processCircle
										? {
												'--rise-process-rows':
													Math.ceil(
														items.length / 2
													),
										  }
										: undefined
								}
								ref={
									type === 'services'
										? sliderTrackRef
										: undefined
								}
							>
								{ items.map( ( item, index ) => (
									<div
										className={ `rise-lp__${ config.card }${
											processCircle
												? ` ${
														processCirclePosition(
															index,
															items.length,
															attributes.circleDirection
														).className
												  }`
												: ''
										}${
											type === 'faq' &&
											faqActiveIndex === index
												? ' rise-lp__faq-item--preview-active'
												: ''
										}${
											type === 'benefits' && item.hidden
												? ' rise-lp__benefit--editor-hidden'
												: ''
										}` }
										style={
											processCircle
												? processCirclePosition(
														index,
														items.length,
														attributes.circleDirection
												  ).style
												: undefined
										}
										key={ index }
										onClickCapture={ ( event ) =>
											openPreviewItem( event, index )
										}
										onFocusCapture={
											type === 'faq'
												? ( event ) => {
														setFaqActiveIndex(
															index
														);
														openPreviewItem(
															event,
															index
														);
												  }
												: ( event ) =>
														openPreviewItem(
															event,
															index
														)
										}
										onMouseEnter={
											type === 'faq'
												? () =>
														setFaqActiveIndex(
															index
														)
												: undefined
										}
									>
										{ type === 'services' &&
											serviceLayout === 'pricing' &&
											( item.badgeEnabled ||
											item.badge ? (
												<div className="rise-lp-editor__service-badge-control">
													<input
														className="rise-lp__service-badge"
														type="text"
														value={
															item.badge || ''
														}
														placeholder={ __(
															'Badge text',
															'rise-landing-pages'
														) }
														aria-label={ sprintf(
															/* translators: %d: service position. */
															__(
																'Service %d badge',
																'rise-landing-pages'
															),
															index + 1
														) }
														onChange={ ( event ) =>
															updateItem( index, {
																badge: event
																	.target
																	.value,
															} )
														}
													/>
													<Button
														icon="trash"
														label={ __(
															'Remove badge',
															'rise-landing-pages'
														) }
														isDestructive
														onClick={ () =>
															updateItem( index, {
																badge: '',
																badgeEnabled: false,
															} )
														}
													/>
												</div>
											) : (
												<Button
													className="rise-lp-editor__service-badge-add"
													icon="plus"
													label={ __(
														'Add badge',
														'rise-landing-pages'
													) }
													onClick={ () =>
														updateItem( index, {
															badgeEnabled: true,
														} )
													}
												/>
											) ) }
										{ type === 'benefits' &&
										attributes.layout === 'center' &&
										attributes.centerItems ? (
											<CenteredBenefitFields
												item={ item }
												index={ index }
												markerStyle={
													attributes.markerStyle ||
													'check'
												}
												markerUrl={
													itemMarkerUrls[ index ]
												}
												number={
													items
														.slice( 0, index + 1 )
														.filter(
															( benefit ) =>
																! benefit.hidden
														).length
												}
												onChange={ ( values ) =>
													updateItem( index, values )
												}
												onOpen={ () =>
													setOpenItemPanelIndex(
														index
													)
												}
											/>
										) : (
											<>
												{ type === 'process' && (
													<ProcessMarker
														item={ item }
														index={ index }
														mode={
															processMarkerMode
														}
														globalUrl={
															processGlobalMarkerUrl
														}
														circle={ processCircle }
													/>
												) }
												{ type === 'services' &&
													item.imageUrl &&
													serviceLayout !==
														'pricing' && (
														<div
															className="rise-lp__service-media"
															style={ {
																'--rise-service-overlay-color':
																	item.overlayColor ||
																	'#000000',
																'--rise-service-overlay-opacity':
																	( item.overlayOpacity ??
																		( serviceLayout ===
																		'image'
																			? 55
																			: 0 ) ) /
																	100,
															} }
														>
															<MediaPreview
																className="rise-lp__service-image"
																attributes={
																	item
																}
															/>
														</div>
													) }
												{ type === 'benefits' &&
													( attributes.markerStyle ||
														'check' ) ===
														'check' && (
														<span
															className="rise-lp__check"
															aria-hidden="true"
														>
															✓
														</span>
													) }
												{ type === 'benefits' &&
													attributes.markerStyle ===
														'number' && (
														<span
															className="rise-lp__check rise-lp__benefit-number"
															aria-hidden="true"
														>
															{
																items
																	.slice(
																		0,
																		index +
																			1
																	)
																	.filter(
																		(
																			benefit
																		) =>
																			! benefit.hidden
																	).length
															}
														</span>
													) }
												{ type === 'benefits' &&
													attributes.markerStyle ===
														'svg' &&
													itemMarkerUrls[ index ] && (
														<span
															className="rise-lp__benefit-icon"
															aria-hidden="true"
														>
															<img
																src={
																	itemMarkerUrls[
																		index
																	]
																}
																alt=""
															/>
														</span>
													) }
												<div
													className={
														type === 'services'
															? 'rise-lp__service-content'
															: undefined
													}
												>
													<div
														className={
															type === 'faq'
																? 'rise-lp-editor__faq-title-row'
																: 'rise-lp-editor__title-row'
														}
													>
														<RichText
															tagName="h3"
															onClick={
																type ===
																	'faq' ||
																type ===
																	'benefits'
																	? () =>
																			setOpenItemPanelIndex(
																				index
																			)
																	: undefined
															}
															onFocus={
																type ===
																	'faq' ||
																type ===
																	'benefits'
																	? () =>
																			setOpenItemPanelIndex(
																				index
																			)
																	: undefined
															}
															className={
																type === 'faq'
																	? 'rise-lp-editor__faq-question'
																	: 'rise-lp__card-title'
															}
															value={
																type === 'faq'
																	? item.question ||
																	  ''
																	: item.title ||
																	  ''
															}
															onChange={ (
																value
															) =>
																updateItem(
																	index,
																	{
																		[ type ===
																		'faq'
																			? 'question'
																			: 'title' ]:
																			value,
																	}
																)
															}
															placeholder={
																type === 'faq'
																	? __(
																			'Write a question',
																			'rise-landing-pages'
																	  )
																	: __(
																			'Item title',
																			'rise-landing-pages'
																	  )
															}
															aria-label={ sprintf(
																/* translators: 1: item type, 2: item position. */
																__(
																	'%1$s %2$d title',
																	'rise-landing-pages'
																),
																config.item,
																index + 1
															) }
															allowedFormats={
																HEADING_FORMATS
															}
														/>
														{ type === 'faq' &&
															!! attributes.showDashes && (
																<svg
																	className="rise-lp__faq-dashes"
																	viewBox="0 0 138 32"
																	aria-hidden="true"
																	focusable="false"
																>
																	{ Array.from(
																		{
																			length: 9,
																		},
																		(
																			_,
																			dash
																		) => (
																			<path
																				key={
																					dash
																				}
																				style={ {
																					'--rise-dash-index':
																						dash,
																				} }
																				d={ faqDashPath(
																					dash
																				) }
																			/>
																		)
																	) }
																</svg>
															) }
													</div>
													{ ( type !== 'services' ||
														serviceLayout !==
															'pricing' ) && (
														<RichText
															tagName="p"
															onClick={
																type ===
																'benefits'
																	? () =>
																			setOpenItemPanelIndex(
																				index
																			)
																	: undefined
															}
															onFocus={
																type ===
																'benefits'
																	? () =>
																			setOpenItemPanelIndex(
																				index
																			)
																	: undefined
															}
															className={
																type === 'faq'
																	? 'rise-lp__faq-answer'
																	: 'rise-lp__card-copy'
															}
															value={
																type === 'faq'
																	? item.answer ||
																	  ''
																	: item.description ||
																	  ''
															}
															onChange={ (
																value
															) =>
																updateItem(
																	index,
																	{
																		[ type ===
																		'faq'
																			? 'answer'
																			: 'description' ]:
																			value,
																	}
																)
															}
															placeholder={
																type === 'faq'
																	? __(
																			'Write an answer',
																			'rise-landing-pages'
																	  )
																	: __(
																			'Describe this item',
																			'rise-landing-pages'
																	  )
															}
															aria-label={ sprintf(
																/* translators: 1: item type, 2: item position. */
																__(
																	'%1$s %2$d description',
																	'rise-landing-pages'
																),
																config.item,
																index + 1
															) }
															allowedFormats={
																BODY_FORMATS
															}
														/>
													) }
													{ type === 'faq' && (
														<FaqPillControls
															value={
																item.pills || ''
															}
															onChange={ (
																pills
															) =>
																updateItem(
																	index,
																	{
																		pills,
																	}
																)
															}
														/>
													) }
													{ type === 'services' &&
														serviceLayout ===
															'pricing' && (
															<>
																{ serviceFeatures(
																	item
																).length >
																	0 && (
																	<ul className="rise-lp__service-features">
																		{ serviceFeatures(
																			item
																		)
																			.slice(
																				0,
																				12
																			)
																			.map(
																				(
																					feature,
																					featureIndex
																				) => (
																					<li
																						key={
																							featureIndex
																						}
																					>
																						<input
																							type="text"
																							className="rise-lp-editor__service-feature-input"
																							value={
																								feature
																							}
																							placeholder={ __(
																								'Feature',
																								'rise-landing-pages'
																							) }
																							aria-label={ sprintf(
																								/* translators: 1: service position, 2: feature position. */
																								__(
																									'Service %1$d feature %2$d',
																									'rise-landing-pages'
																								),
																								index +
																									1,
																								featureIndex +
																									1
																							) }
																							onChange={ (
																								event
																							) =>
																								updateItem(
																									index,
																									{
																										features:
																											serviceFeatures(
																												item
																											).map(
																												(
																													value,
																													i
																												) =>
																													i ===
																													featureIndex
																														? event
																																.target
																																.value
																														: value
																											),
																									}
																								)
																							}
																						/>
																						<Button
																							icon="trash"
																							label={ sprintf(
																								/* translators: %d: feature position. */
																								__(
																									'Remove feature %d',
																									'rise-landing-pages'
																								),
																								featureIndex +
																									1
																							) }
																							isDestructive
																							onClick={ () =>
																								updateItem(
																									index,
																									{
																										features:
																											serviceFeatures(
																												item
																											).filter(
																												(
																													_,
																													i
																												) =>
																													i !==
																													featureIndex
																											),
																									}
																								)
																							}
																						/>
																					</li>
																				)
																			) }
																	</ul>
																) }
																<Button
																	className="rise-lp-editor__service-add-feature"
																	variant="secondary"
																	icon="plus"
																	disabled={
																		serviceFeatures(
																			item
																		)
																			.length >=
																		12
																	}
																	onClick={ () =>
																		updateItem(
																			index,
																			{
																				features:
																					[
																						...serviceFeatures(
																							item
																						),
																						'',
																					],
																			}
																		)
																	}
																>
																	{ __(
																		'Add feature',
																		'rise-landing-pages'
																	) }
																</Button>
																{ ( item.price ||
																	item.priceQualifier ||
																	item.priceNote ) && (
																	<div className="rise-lp__service-price-block">
																		<div className="rise-lp__service-price-line">
																			{ item.price && (
																				<span className="rise-lp__service-price">
																					{
																						item.price
																					}
																				</span>
																			) }
																			{ item.priceQualifier && (
																				<span className="rise-lp__service-price-qualifier">
																					{
																						item.priceQualifier
																					}
																				</span>
																			) }
																		</div>
																		{ item.priceNote && (
																			<span className="rise-lp__service-price-note">
																				{
																					item.priceNote
																				}
																			</span>
																		) }
																	</div>
																) }
															</>
														) }
													{ type === 'services' &&
														( item.showCta !==
														false ? (
															<EditableButton
																className="rise-lp__button rise-lp__button--secondary rise-lp__button--outline"
																label={
																	item.ctaLabel
																}
																fallbackLabel={
																	defaults.ctaLabel
																}
																url={
																	item.ctaUrl
																}
																fallbackUrl={
																	item.requireExplicitCta
																		? ''
																		: defaults.ctaUrl
																}
																newTab={
																	item.ctaNewTab
																}
																onChange={ (
																	change
																) =>
																	updateServiceButton(
																		index,
																		change
																	)
																}
																onRemove={ () =>
																	updateItem(
																		index,
																		{
																			showCta: false,
																		}
																	)
																}
															/>
														) : (
															<Button
																variant="secondary"
																icon="plus"
																className="rise-lp-editor__add-button"
																onClick={ () =>
																	updateItem(
																		index,
																		{
																			showCta: true,
																		}
																	)
																}
															>
																{ __(
																	'Add button',
																	'rise-landing-pages'
																) }
															</Button>
														) ) }
												</div>
											</>
										) }
									</div>
								) ) }
							</div>
							{ type === 'services' &&
								attributes.displayMode === 'slider' &&
								sliderState.overflow && (
									<div className="rise-lp__slider-controls">
										<button
											type="button"
											className="rise-lp__button rise-lp__button--secondary"
											onClick={ () => moveSlider( -1 ) }
											disabled={
												! attributes.sliderLoop &&
												sliderState.atStart
											}
										>
											{ __(
												'Previous',
												'rise-landing-pages'
											) }
										</button>
										{ attributes.sliderShowCounter !==
											false && (
											<span
												className="rise-lp__slider-status"
												aria-live="polite"
											>{ `${ sliderState.first }–${ sliderState.last } / ${ items.length }` }</span>
										) }
										<button
											type="button"
											className="rise-lp__button rise-lp__button--secondary"
											onClick={ () => moveSlider( 1 ) }
											disabled={
												! attributes.sliderLoop &&
												sliderState.atEnd
											}
										>
											{ __(
												'Next',
												'rise-landing-pages'
											) }
										</button>
									</div>
								) }
							{ ( type === 'services' ||
								type === 'faq' ||
								type === 'benefits' ) && (
								<div className="rise-lp-editor__add-item">
									<Button
										variant="secondary"
										onClick={ addItem }
										disabled={ items.length >= config.max }
									>
										{ config.add }
									</Button>
								</div>
							) }
						</div>
					</div>
					{ type !== 'faq' &&
						type !== 'benefits' &&
						type !== 'services' && (
							<div className="rise-lp-editor__add-item">
								<Button
									variant="secondary"
									onClick={ addItem }
									disabled={ items.length >= config.max }
								>
									{ config.add }
								</Button>
							</div>
						) }
				</div>
			</section>
		</SectionFrame>
	);
}
