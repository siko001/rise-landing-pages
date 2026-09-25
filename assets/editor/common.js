import { LandingRichText as RichText } from './outline-format';
import {
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	RangeControl,
	Notice,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import EditableButton from './EditableButton';
import BlockSettingsToolbar from './BlockSettingsToolbar';

export const INLINE_FORMATS = [
	'core/bold',
	'core/italic',
	'rise-landing/outline',
];
export const BODY_FORMATS = [
	...INLINE_FORMATS,
	'core/link',
	'rise-landing/text-color',
];
export const HEADING_FORMATS = [
	'rise-landing/outline',
	'rise-landing/size',
	'rise-landing/text-color',
	'rise-landing/line-height',
];
export const EYEBROW_FORMATS = [ 'rise-landing/text-color' ];

/** Parse only the plugin's custom properties into a React style object. */
const brandStyle = () => {
	const declarations = window.riseLandingEditor?.cssVariables || '';
	return declarations.split( ';' ).reduce( ( result, declaration ) => {
		const separator = declaration.indexOf( ':' );
		const key = declaration.slice( 0, separator ).trim();
		if ( separator > 0 && /^--rise-[a-z-]+$/.test( key ) ) {
			result[ key ] = declaration.slice( separator + 1 ).trim();
		}
		return result;
	}, {} );
};

export function useDefaults() {
	const meta = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
		[]
	);
	const settings = window.riseLandingEditor?.settings || {};
	return {
		ctaLabel:
			meta._rise_landing_cta_label ||
			settings.cta_label ||
			__( 'Book an appointment', 'rise-landing-pages' ),
		ctaUrl: meta._rise_landing_booking_url || settings.booking_url || '',
	};
}

export function SectionFrame( { attributes, setAttributes, title, children } ) {
	const sectionBackground =
		attributes.sectionBackground === 'accent'
			? 'none'
			: attributes.sectionBackground || 'none';
	const blockProps = useBlockProps( {
		className: `${
			window.riseLandingEditor?.rootClass || 'rise-lp'
		} rise-lp-editor rise-lp-editor--bg-${ sectionBackground }${
			attributes.hidden ? ' rise-lp-editor--hidden' : ''
		}`,
		style: {
			...brandStyle(),
			...( attributes.headingLeading !== undefined
				? {
						'--rise-section-heading-leading':
							attributes.headingLeading,
				  }
				: {} ),
			...( attributes.cardHeadingLeading !== undefined
				? {
						'--rise-card-heading-leading':
							attributes.cardHeadingLeading,
				  }
				: {} ),
			...( attributes.stepTitleSize !== undefined
				? {
						'--rise-process-step-title-size': `${ attributes.stepTitleSize }px`,
				  }
				: {} ),
			...( /^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test(
				attributes.markerImageBackground || ''
			)
				? {
						'--rise-process-image-background':
							attributes.markerImageBackground,
				  }
				: {} ),
			...( attributes.gapEyebrowHeading !== undefined
				? {
						'--rise-services-eyebrow-gap': `${ attributes.gapEyebrowHeading }px`,
				  }
				: {} ),
			...( attributes.gapHeadingIntro !== undefined
				? {
						'--rise-services-intro-gap': `${ attributes.gapHeadingIntro }px`,
				  }
				: {} ),
			...( attributes.gapIntroCards !== undefined
				? {
						'--rise-services-cards-gap': `${ attributes.gapIntroCards }px`,
				  }
				: {} ),
			...( attributes.cardTextGap !== undefined
				? {
						'--rise-services-card-text-gap': `${ attributes.cardTextGap }px`,
				  }
				: {} ),
			...( attributes.gridGap !== undefined
				? { '--rise-services-grid-gap': `${ attributes.gridGap }px` }
				: {} ),
			...( attributes.gridColumns !== undefined
				? { '--rise-services-columns': attributes.gridColumns }
				: {} ),
			...( attributes.mobileGridColumns !== undefined
				? {
						'--rise-services-mobile-columns':
							attributes.mobileGridColumns,
				  }
				: {} ),
		},
	} );
	return (
		<>
			<BlockSettingsToolbar
				backgroundControl={
					<SelectControl
						label={
							title === __( 'Hero', 'rise-landing-pages' )
								? __(
										'Background / overlay tint',
										'rise-landing-pages'
								  )
								: __( 'Background', 'rise-landing-pages' )
						}
						value={ sectionBackground }
						options={ [
							{
								label:
									title === __( 'Hero', 'rise-landing-pages' )
										? __(
												'Page background / custom overlay colour',
												'rise-landing-pages'
										  )
										: __(
												'None — page background',
												'rise-landing-pages'
										  ),
								value: 'none',
							},
							...( [
								__(
									'Frequently asked questions',
									'rise-landing-pages'
								),
								__( 'Call to action', 'rise-landing-pages' ),
								__( 'Benefits', 'rise-landing-pages' ),
								__( 'How it works', 'rise-landing-pages' ),
							].includes( title )
								? [
										{
											label: __(
												'White',
												'rise-landing-pages'
											),
											value: 'white',
										},
								  ]
								: [] ),
							{
								label: __( 'Off white', 'rise-landing-pages' ),
								value: 'offwhite',
							},
							{
								label: __(
									'Card colour',
									'rise-landing-pages'
								),
								value: 'surface',
							},
						] }
						onChange={ ( selectedBackground ) =>
							setAttributes( {
								sectionBackground: selectedBackground,
								...( title ===
									__( 'Hero', 'rise-landing-pages' ) &&
								selectedBackground === 'offwhite' &&
								( ! attributes.overlayTextColor ||
									attributes.overlayTextColor.toLowerCase() ===
										'#ffffff' )
									? {
											overlayTextColor: '#111111',
									  }
									: {} ),
							} )
						}
					/>
				}
			/>
			<div { ...blockProps }>
				<div className="rise-lp-editor__section-label">{ title }</div>
				{ attributes.hidden && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'This section is hidden on your page.',
							'rise-landing-pages'
						) }
					</Notice>
				) }
				{ children }
			</div>
		</>
	);
}

export function SectionHeading( { attributes, setAttributes } ) {
	return (
		<div className="rise-lp__section-header">
			<RichText
				tagName="p"
				className="rise-lp__eyebrow"
				value={ attributes.eyebrow }
				onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) }
				placeholder={ __(
					'Supporting text (optional)',
					'rise-landing-pages'
				) }
				aria-label={ __( 'Supporting text', 'rise-landing-pages' ) }
				allowedFormats={ EYEBROW_FORMATS }
			/>
			<RichText
				tagName="h2"
				className="rise-lp__heading"
				value={ attributes.heading }
				onChange={ ( heading ) => setAttributes( { heading } ) }
				placeholder={ __( 'Section heading', 'rise-landing-pages' ) }
				aria-label={ __( 'Section heading', 'rise-landing-pages' ) }
				allowedFormats={ HEADING_FORMATS }
			/>
			<RichText
				tagName="p"
				className="rise-lp__intro"
				value={ attributes.intro }
				onChange={ ( intro ) => setAttributes( { intro } ) }
				placeholder={ __(
					'Introduction (optional)',
					'rise-landing-pages'
				) }
				aria-label={ __( 'Introduction', 'rise-landing-pages' ) }
				allowedFormats={ BODY_FORMATS }
			/>
		</div>
	);
}

export function ImageControl( {
	label,
	imageId,
	imageUrl,
	imageAlt,
	mediaType = 'image',
	focalX = 50,
	focalY = 50,
	mediaFit = 'cover',
	videoMuted = true,
	videoAutoplay = false,
	videoLoop = false,
	videoControls = true,
	captionsUrl = '',
	imagesOnly = false,
	onChange,
} ) {
	return (
		<div className="rise-lp-editor__media-control">
			<p className="rise-lp-editor__control-label">{ label }</p>
			{ imageUrl && mediaType !== 'video' && (
				<img
					className="rise-lp-editor__media-preview"
					src={ imageUrl }
					alt=""
				/>
			) }
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={
						imagesOnly ? [ 'image' ] : [ 'image', 'video' ]
					}
					value={ imageId }
					onSelect={ ( media ) =>
						onChange( {
							mediaType:
								media.type === 'video' ? 'video' : 'image',
							imageId: media.id || 0,
							imageUrl: media.url || '',
							imageAlt: media.alt || '',
						} )
					}
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ imageId
								? __( 'Replace media', 'rise-landing-pages' )
								: __( 'Choose media', 'rise-landing-pages' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			{ ( imageId > 0 || imageUrl ) && (
				<>
					<Button
						variant="tertiary"
						isDestructive
						onClick={ () =>
							onChange( {
								mediaType: 'image',
								imageId: 0,
								imageUrl: '',
								imageAlt: '',
							} )
						}
					>
						{ __( 'Remove media', 'rise-landing-pages' ) }
					</Button>
					<RangeControl
						label={ __(
							'Horizontal position',
							'rise-landing-pages'
						) }
						value={ focalX }
						min={ 0 }
						max={ 100 }
						onChange={ ( value ) => onChange( { focalX: value } ) }
					/>
					<RangeControl
						label={ __(
							'Vertical position',
							'rise-landing-pages'
						) }
						value={ focalY }
						min={ 0 }
						max={ 100 }
						onChange={ ( value ) => onChange( { focalY: value } ) }
					/>
					<SelectControl
						label={ __( 'Fit', 'rise-landing-pages' ) }
						value={ mediaFit }
						options={ [
							{
								label: __(
									'Fill and crop',
									'rise-landing-pages'
								),
								value: 'cover',
							},
							{
								label: __(
									'Show entire media',
									'rise-landing-pages'
								),
								value: 'contain',
							},
						] }
						onChange={ ( value ) =>
							onChange( { mediaFit: value } )
						}
					/>
					{ mediaType === 'video' && (
						<>
							<ToggleControl
								label={ __(
									'Autoplay (muted)',
									'rise-landing-pages'
								) }
								checked={ videoAutoplay }
								onChange={ ( value ) =>
									onChange( {
										videoAutoplay: value,
										...( value
											? { videoMuted: true }
											: {} ),
									} )
								}
								help={ __(
									'Autoplay is muted and respects reduced-motion preferences.',
									'rise-landing-pages'
								) }
							/>
							<ToggleControl
								label={ __(
									'Mute audio',
									'rise-landing-pages'
								) }
								checked={ videoMuted || videoAutoplay }
								disabled={ videoAutoplay }
								onChange={ ( value ) =>
									onChange( { videoMuted: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Loop', 'rise-landing-pages' ) }
								checked={ videoLoop }
								onChange={ ( value ) =>
									onChange( { videoLoop: value } )
								}
							/>
							<ToggleControl
								label={ __(
									'Show player controls',
									'rise-landing-pages'
								) }
								checked={ videoControls }
								onChange={ ( value ) =>
									onChange( { videoControls: value } )
								}
							/>
							<TextControl
								label={ __(
									'Captions file URL (.vtt)',
									'rise-landing-pages'
								) }
								value={ captionsUrl }
								onChange={ ( value ) =>
									onChange( { captionsUrl: value } )
								}
								help={ __(
									'Add captions for speech or meaningful audio.',
									'rise-landing-pages'
								) }
							/>
						</>
					) }
					<TextControl
						label={ __(
							'Media description',
							'rise-landing-pages'
						) }
						value={ imageAlt || '' }
						onChange={ ( value ) =>
							onChange( { imageAlt: value } )
						}
						help={ __(
							'Describe useful information in the image. Leave blank for a decorative image.',
							'rise-landing-pages'
						) }
					/>
				</>
			) }
		</div>
	);
}

export function ActionPreview( {
	attributes,
	setAttributes,
	secondary = false,
	blockProps = {},
} ) {
	const defaults = useDefaults();
	const showPrimary = attributes.showCta !== false;
	const showSecondary =
		attributes.showSecondary ??
		!! ( attributes.secondaryLabel && attributes.secondaryUrl );
	return (
		<>
			<div
				{ ...blockProps }
				className={ `rise-lp__actions ${ blockProps.className || '' }` }
			>
				{ showPrimary ? (
					<EditableButton
						className="rise-lp__button"
						label={ attributes.ctaLabel }
						fallbackLabel={ defaults.ctaLabel }
						url={ attributes.ctaUrl }
						fallbackUrl={
							attributes.requireExplicitCta ? '' : defaults.ctaUrl
						}
						newTab={ attributes.ctaNewTab }
						onRemove={ () => setAttributes( { showCta: false } ) }
						onAdd={
							secondary && ! showSecondary
								? () => setAttributes( { showSecondary: true } )
								: undefined
						}
						addLabel={ __(
							'Add second button',
							'rise-landing-pages'
						) }
						onChange={ ( { label, url, newTab } ) =>
							setAttributes( {
								...( label !== undefined
									? { ctaLabel: label }
									: {} ),
								...( url !== undefined ? { ctaUrl: url } : {} ),
								...( newTab !== undefined
									? { ctaNewTab: newTab }
									: {} ),
							} )
						}
					/>
				) : (
					<Button
						variant="secondary"
						icon="plus"
						className="rise-lp-editor__add-button"
						onClick={ () => setAttributes( { showCta: true } ) }
					>
						{ __( 'Add button', 'rise-landing-pages' ) }
					</Button>
				) }
				{ secondary && showSecondary && (
					<EditableButton
						className={ `rise-lp__button rise-lp__button--secondary rise-lp__button--${
							attributes.secondaryStyle || 'outline'
						}` }
						label={ attributes.secondaryLabel }
						fallbackLabel={ __(
							'Second button',
							'rise-landing-pages'
						) }
						url={ attributes.secondaryUrl }
						newTab={ attributes.secondaryNewTab }
						onRemove={ () =>
							setAttributes( { showSecondary: false } )
						}
						onChange={ ( { label, url, newTab } ) =>
							setAttributes( {
								...( label !== undefined
									? { secondaryLabel: label }
									: {} ),
								...( url !== undefined
									? { secondaryUrl: url }
									: {} ),
								...( newTab !== undefined
									? { secondaryNewTab: newTab }
									: {} ),
							} )
						}
						style={
							attributes.secondaryStyle === 'custom'
								? {
										background:
											attributes.secondaryBackground,
										borderColor:
											attributes.secondaryBackground,
										color: attributes.secondaryText,
								  }
								: undefined
						}
					>
						<SelectControl
							label={ __( 'Button style', 'rise-landing-pages' ) }
							value={ attributes.secondaryStyle || 'outline' }
							options={ [
								{
									label: __(
										'Outline',
										'rise-landing-pages'
									),
									value: 'outline',
								},
								{
									label: __( 'White', 'rise-landing-pages' ),
									value: 'light',
								},
								{
									label: __( 'Black', 'rise-landing-pages' ),
									value: 'dark',
								},
								{
									label: __(
										'Brand colour',
										'rise-landing-pages'
									),
									value: 'accent',
								},
								{
									label: __(
										'Custom colours',
										'rise-landing-pages'
									),
									value: 'custom',
								},
							] }
							onChange={ ( secondaryStyle ) =>
								setAttributes( { secondaryStyle } )
							}
						/>
						{ attributes.secondaryStyle === 'custom' && (
							<div className="rise-lp-editor__button-colours">
								<TextControl
									type="color"
									label={ __(
										'Background',
										'rise-landing-pages'
									) }
									value={
										attributes.secondaryBackground ||
										'#ffffff'
									}
									onChange={ ( secondaryBackground ) =>
										setAttributes( { secondaryBackground } )
									}
								/>
								<TextControl
									type="color"
									label={ __(
										'Text colour',
										'rise-landing-pages'
									) }
									value={
										attributes.secondaryText || '#000000'
									}
									onChange={ ( secondaryText ) =>
										setAttributes( { secondaryText } )
									}
								/>
							</div>
						) }
					</EditableButton>
				) }
			</div>
			{ showPrimary &&
				! attributes.ctaUrl &&
				( attributes.requireExplicitCta || ! defaults.ctaUrl ) && (
					<p className="rise-lp-editor__hint">
						{ attributes.requireExplicitCta
							? __(
									'Add a link in the button toolbar to show it on your page.',
									'rise-landing-pages'
							  )
							: __(
									'Add a link in the button toolbar or a booking URL in page settings to show this button on your page.',
									'rise-landing-pages'
							  ) }
					</p>
				) }
		</>
	);
}

export function MediaPreview( { attributes, className } ) {
	const x = attributes.focalX ?? 50;
	const y = attributes.focalY ?? 50;
	const style = {
		'--rise-media-position': `${ x }% ${ y }%`,
		'--rise-media-mobile-position': attributes.mobileImageUrl
			? `${ attributes.mobileFocalX ?? 50 }% ${
					attributes.mobileFocalY ?? 50
			  }%`
			: `${ x }% ${ y }%`,
		objectFit: attributes.mediaFit || 'cover',
	};
	if ( attributes.mediaType === 'video' ) {
		return (
			<video
				className={ className }
				src={ attributes.imageUrl }
				style={ style }
				controls
				muted
				playsInline
				preload="metadata"
				aria-label={
					attributes.imageAlt ||
					__( 'Video preview', 'rise-landing-pages' )
				}
			>
				<track
					kind="captions"
					src={ attributes.captionsUrl || undefined }
				/>
			</video>
		);
	}
	const image = (
		<img
			className={ className }
			src={ attributes.imageUrl }
			alt={ attributes.imageAlt || '' }
			style={ style }
		/>
	);
	return attributes.mobileImageUrl ? (
		<picture>
			<source
				media="(max-width: 767px)"
				srcSet={ attributes.mobileImageUrl }
			/>
			{ image }
		</picture>
	) : (
		image
	);
}
