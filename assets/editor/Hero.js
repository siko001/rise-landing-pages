import { LandingRichText as RichText } from './outline-format';
import './size-format';
import './text-color-format';
import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import SpacerInserter from './SpacerInserter';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	ActionPreview,
	BODY_FORMATS,
	EYEBROW_FORMATS,
	HEADING_FORMATS,
	ImageControl,
	INLINE_FORMATS,
	MediaPreview,
	SectionFrame,
} from './common';

const SPACER_SLOTS = [
	'after-eyebrow',
	'after-heading',
	'after-description',
	'after-reassurance',
];
const hasVisibleText = ( value ) =>
	typeof value === 'string' &&
	value
		.replace( /<[^>]*>/g, '' )
		.replace( /&(?:nbsp|#160|#xA0);/gi, ' ' )
		.trim().length > 0;

export default function Hero( { attributes, setAttributes, clientId } ) {
	const innerBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const { insertBlock, replaceInnerBlocks } =
		useDispatch( 'core/block-editor' );
	const slotHasText = {
		'after-eyebrow': hasVisibleText( attributes.eyebrow ),
		'after-heading': hasVisibleText( attributes.heading ),
		'after-description': hasVisibleText( attributes.description ),
		'after-reassurance': hasVisibleText( attributes.reassurance ),
	};
	const hasSpacer = ( slot ) =>
		innerBlocks.some(
			( block ) =>
				block.name === 'rise-landing/spacer' &&
				( block.attributes.heroSlot || 'after-description' ) === slot
		);
	const addSpacer = ( slot ) => {
		const position = innerBlocks.findIndex(
			( block ) =>
				block.name === 'rise-landing/spacer' &&
				SPACER_SLOTS.indexOf(
					block.attributes.heroSlot || 'after-description'
				) > SPACER_SLOTS.indexOf( slot )
		);
		insertBlock(
			createBlock( 'rise-landing/spacer', { heroSlot: slot } ),
			position < 0 ? innerBlocks.length : position,
			clientId
		);
	};
	const spacerSlot = ( slot, label ) =>
		slotHasText[ slot ] &&
		! hasSpacer( slot ) && (
			<SpacerInserter
				className={ `rise-lp-editor__hero-spacer-slot rise-lp-editor__hero-spacer-slot-${ slot }` }
				label={ label }
				onClick={ () => addSpacer( slot ) }
			/>
		);
	useEffect( () => {
		const parts = innerBlocks.filter(
			( block ) => block.name === 'rise-landing/hero-part'
		);
		if ( parts.length ) {
			const byRole = Object.fromEntries(
				parts.map( ( block ) => [
					block.attributes.role,
					block.attributes,
				] )
			);
			const migrated = {};
			for ( const role of [
				'eyebrow',
				'heading',
				'description',
				'reassurance',
			] ) {
				if (
					byRole[ role ] &&
					typeof byRole[ role ].content === 'string'
				) {
					migrated[ role ] = byRole[ role ].content;
				}
			}
			for ( const [ role, key ] of [
				[ 'heading', 'gapEyebrowHeading' ],
				[ 'description', 'gapHeadingDescription' ],
				[ 'reassurance', 'gapDescriptionReassurance' ],
				[ 'actions', 'gapReassuranceButtons' ],
			] ) {
				if ( Number.isFinite( byRole[ role ]?.gap ) ) {
					migrated[ key ] = byRole[ role ].gap;
				}
			}
			if ( Number.isFinite( byRole.actions?.gapWithoutReassurance ) ) {
				migrated.gapTextButtons = byRole.actions.gapWithoutReassurance;
			}
			setAttributes( migrated );
		}
		const seen = new Set();
		const kept = [];
		let precedingRole = '';
		let changed = false;
		for ( const block of innerBlocks ) {
			if ( block.name === 'rise-landing/hero-part' ) {
				precedingRole = block.attributes.role;
				changed = true;
				continue;
			}
			if ( block.name !== 'rise-landing/spacer' ) {
				kept.push( block );
				continue;
			}
			const inferredSlot = `after-${ precedingRole }`;
			const slot =
				block.attributes.heroSlot ||
				( parts.length ? inferredSlot : 'after-description' );
			if ( ! SPACER_SLOTS.includes( slot ) || seen.has( slot ) ) {
				changed = true;
				continue;
			}
			seen.add( slot );
			if ( block.attributes.heroSlot ) {
				kept.push( block );
			} else {
				kept.push( {
					...block,
					attributes: { ...block.attributes, heroSlot: slot },
				} );
				changed = true;
			}
		}
		if ( changed ) {
			replaceInnerBlocks( clientId, kept, false );
		}
	}, [ clientId, innerBlocks, replaceInnerBlocks, setAttributes ] );
	const resolvedLayout = attributes.layout === 'split' ? 'split' : 'overlay';
	let resolvedOverlayColor = attributes.overlayColor;
	if ( attributes.sectionBackground === 'surface' ) {
		resolvedOverlayColor = 'var(--rise-surface)';
	} else if ( attributes.sectionBackground === 'offwhite' ) {
		resolvedOverlayColor = '#f5f5f5';
	}
	return (
		<SectionFrame
			{ ...{ attributes, setAttributes } }
			title={ __( 'Hero', 'rise-landing-pages' ) }
		>
			<InspectorControls>
				<PanelBody title={ __( 'Hero media', 'rise-landing-pages' ) }>
					<ImageControl
						label={ __(
							'Main image or video',
							'rise-landing-pages'
						) }
						{ ...attributes }
						onChange={ setAttributes }
					/>
					<ImageControl
						label={ __(
							'Mobile image (optional)',
							'rise-landing-pages'
						) }
						imagesOnly
						imageId={ attributes.mobileImageId }
						focalX={ attributes.mobileFocalX ?? 50 }
						focalY={ attributes.mobileFocalY ?? 50 }
						imageUrl={ attributes.mobileImageUrl }
						imageAlt={ attributes.mobileImageAlt }
						onChange={ ( values ) =>
							setAttributes(
								Object.fromEntries(
									Object.entries( values ).map(
										( [ key, value ] ) => [
											`mobile${ key
												.charAt( 0 )
												.toUpperCase() }${ key.slice(
												1
											) }`,
											value,
										]
									)
								)
							)
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Layout', 'rise-landing-pages' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Hero layout', 'rise-landing-pages' ) }
						value={ resolvedLayout }
						options={ [
							{
								label: __(
									'Text beside media',
									'rise-landing-pages'
								),
								value: 'split',
							},
							{
								label: __(
									'Media with text overlay',
									'rise-landing-pages'
								),
								value: 'overlay',
							},
						] }
						onChange={ ( layout ) => setAttributes( { layout } ) }
					/>
					{ resolvedLayout === 'split' && (
						<SelectControl
							label={ __(
								'Media position',
								'rise-landing-pages'
							) }
							value={ attributes.mediaSide || 'right' }
							options={ [
								{
									label: __(
										'Right — text on left',
										'rise-landing-pages'
									),
									value: 'right',
								},
								{
									label: __(
										'Left — text on right',
										'rise-landing-pages'
									),
									value: 'left',
								},
							] }
							onChange={ ( mediaSide ) =>
								setAttributes( { mediaSide } )
							}
						/>
					) }
					{ resolvedLayout === 'overlay' && (
						<>
							<RangeControl
								label={ __(
									'Minimum height (px)',
									'rise-landing-pages'
								) }
								value={ attributes.minHeight }
								min={ 320 }
								max={ 1200 }
								onChange={ ( minHeight ) =>
									setAttributes( {
										minHeight,
										...( attributes.maxHeight > 0 &&
										minHeight > attributes.maxHeight
											? { maxHeight: minHeight }
											: {} ),
									} )
								}
								help={ __(
									'The hero can grow beyond this to fit its content and spacing.',
									'rise-landing-pages'
								) }
							/>
							<RangeControl
								label={ __(
									'Maximum height (px)',
									'rise-landing-pages'
								) }
								value={ attributes.maxHeight ?? 0 }
								min={ 0 }
								max={ 1600 }
								onChange={ ( maxHeight ) => {
									const limit =
										maxHeight > 0
											? Math.max( 320, maxHeight )
											: 0;
									setAttributes( {
										maxHeight: limit,
										...( limit > 0 &&
										attributes.minHeight > limit
											? { minHeight: limit }
											: {} ),
									} );
								} }
								help={ __(
									'0 fills the viewport. A limit creates a shorter banner; content and spacing can still make it taller.',
									'rise-landing-pages'
								) }
							/>
							<RangeControl
								label={ __(
									'Overlay darkness (%)',
									'rise-landing-pages'
								) }
								value={ attributes.overlayOpacity }
								min={ 0 }
								max={ 95 }
								onChange={ ( overlayOpacity ) =>
									setAttributes( { overlayOpacity } )
								}
							/>
							{ attributes.sectionBackground === 'none' && (
								<TextControl
									type="color"
									label={ __(
										'Overlay colour',
										'rise-landing-pages'
									) }
									value={ attributes.overlayColor }
									onChange={ ( overlayColor ) =>
										setAttributes( { overlayColor } )
									}
								/>
							) }
							<TextControl
								type="color"
								label={ __(
									'Overlay text colour',
									'rise-landing-pages'
								) }
								value={ attributes.overlayTextColor }
								onChange={ ( overlayTextColor ) =>
									setAttributes( { overlayTextColor } )
								}
							/>
						</>
					) }
					<SelectControl
						label={ __( 'Text alignment', 'rise-landing-pages' ) }
						value={ attributes.alignment }
						options={ [
							{
								label: __( 'Left', 'rise-landing-pages' ),
								value: 'left',
							},
							{
								label: __( 'Centred', 'rise-landing-pages' ),
								value: 'center',
							},
						] }
						onChange={ ( alignment ) =>
							setAttributes( { alignment } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<section
				className={ `rise-lp__hero rise-lp__hero--${
					attributes.alignment
				} rise-lp__hero--${ resolvedLayout } rise-lp__hero--media-${
					attributes.mediaSide || 'right'
				}${
					! attributes.imageUrl ? ' rise-lp__hero--text-only' : ''
				}` }
				style={ {
					'--rise-hero-min-height': `${ attributes.minHeight }px`,
					'--rise-hero-max-height':
						attributes.maxHeight > 0
							? `${ attributes.maxHeight }px`
							: undefined,
					'--rise-hero-preview-height': `${ Math.max(
						attributes.minHeight ?? 560,
						attributes.maxHeight ?? 0
					) }px`,
					'--rise-hero-leading': attributes.headingLeading ?? 0.95,
					'--rise-hero-eyebrow-gap': `${
						attributes.gapEyebrowHeading ?? 23
					}px`,
					'--rise-hero-description-gap': `${
						attributes.gapHeadingDescription ?? 32
					}px`,
					'--rise-hero-reassurance-gap': `${
						attributes.gapDescriptionReassurance ?? 30
					}px`,
					'--rise-hero-button-gap': `${
						attributes.gapTextButtons ?? 28
					}px`,
					'--rise-hero-reassurance-button-gap': `${
						attributes.gapReassuranceButtons ?? 35
					}px`,
					'--rise-overlay-opacity': attributes.overlayOpacity / 100,
					'--rise-overlay-color': resolvedOverlayColor,
					'--rise-overlay-text': attributes.overlayTextColor,
				} }
			>
				<div className="rise-lp__container rise-lp__hero-grid">
					<div className="rise-lp__hero-content">
						<RichText
							tagName="p"
							className="rise-lp__eyebrow"
							value={ attributes.eyebrow }
							onChange={ ( eyebrow ) =>
								setAttributes( { eyebrow } )
							}
							placeholder={ __(
								'Supporting text',
								'rise-landing-pages'
							) }
							aria-label={ __(
								'Supporting text',
								'rise-landing-pages'
							) }
							allowedFormats={ EYEBROW_FORMATS }
							style={
								hasSpacer( 'after-eyebrow' )
									? { marginBottom: 0 }
									: undefined
							}
						/>
						{ spacerSlot(
							'after-eyebrow',
							__(
								'Add a spacer after supporting text',
								'rise-landing-pages'
							)
						) }
						<RichText
							tagName="h1"
							className="rise-lp__heading"
							value={ attributes.heading }
							onChange={ ( heading ) =>
								setAttributes( { heading } )
							}
							placeholder={ __(
								'Your main headline',
								'rise-landing-pages'
							) }
							aria-label={ __(
								'Main headline',
								'rise-landing-pages'
							) }
							allowedFormats={ HEADING_FORMATS }
						/>
						{ spacerSlot(
							'after-heading',
							__(
								'Add a spacer after title',
								'rise-landing-pages'
							)
						) }
						<RichText
							tagName="p"
							className="rise-lp__intro"
							value={ attributes.description }
							onChange={ ( description ) =>
								setAttributes( { description } )
							}
							placeholder={ __(
								'One sentence about your service',
								'rise-landing-pages'
							) }
							aria-label={ __(
								'Hero description',
								'rise-landing-pages'
							) }
							allowedFormats={ BODY_FORMATS }
							style={
								hasSpacer( 'after-heading' )
									? { marginTop: 0 }
									: undefined
							}
						/>
						{ spacerSlot(
							'after-description',
							__(
								'Add a spacer after description',
								'rise-landing-pages'
							)
						) }
						<RichText
							tagName="p"
							className="rise-lp__reassurance"
							value={ attributes.reassurance }
							onChange={ ( reassurance ) =>
								setAttributes( { reassurance } )
							}
							placeholder={ __(
								'A reassuring detail (optional)',
								'rise-landing-pages'
							) }
							aria-label={ __(
								'Reassurance',
								'rise-landing-pages'
							) }
							allowedFormats={ INLINE_FORMATS }
							style={
								hasSpacer( 'after-description' )
									? { marginTop: 0 }
									: undefined
							}
						/>
						{ spacerSlot(
							'after-reassurance',
							__(
								'Add a spacer before buttons',
								'rise-landing-pages'
							)
						) }
						<InnerBlocks
							allowedBlocks={ [ 'rise-landing/spacer' ] }
							templateLock={ false }
							renderAppender={ false }
						/>
						<ActionPreview
							attributes={ attributes }
							setAttributes={ setAttributes }
							secondary
							blockProps={ {
								style:
									hasSpacer( 'after-reassurance' ) ||
									( ! attributes.reassurance &&
										hasSpacer( 'after-description' ) )
										? { marginTop: 0 }
										: undefined,
							} }
						/>
					</div>
					{ attributes.imageUrl && (
						<div className="rise-lp__hero-media">
							<MediaPreview
								attributes={ attributes }
								className="rise-lp__hero-image"
							/>
						</div>
					) }
				</div>
			</section>
		</SectionFrame>
	);
}
