import BlockSettingsToolbar from './BlockSettingsToolbar';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { LandingRichText as RichText } from './outline-format';

const backgrounds = [
	{ label: __( 'Rise Fitness red', 'rise-landing-pages' ), value: 'fitness' },
	{ label: __( 'Rise Physio mint', 'rise-landing-pages' ), value: 'physio' },
	{
		label: __( 'Rise Medical navy', 'rise-landing-pages' ),
		value: 'medical',
	},
	{ label: __( 'Black', 'rise-landing-pages' ), value: 'black' },
	{ label: __( 'White', 'rise-landing-pages' ), value: 'white' },
];

const dividerOptions = [
	{ label: __( 'Current brand icon', 'rise-landing-pages' ), value: 'brand' },
	{
		label: __( 'Rise Fitness icon', 'rise-landing-pages' ),
		value: 'fitnessMark',
	},
	{ label: __( 'Rise Physio icon', 'rise-landing-pages' ), value: 'physio' },
	{
		label: __( 'Rise Medical icon', 'rise-landing-pages' ),
		value: 'medical',
	},
	{ label: __( 'Custom image', 'rise-landing-pages' ), value: 'custom' },
	{ label: __( 'No divider icon', 'rise-landing-pages' ), value: 'none' },
];

function plainPhrase( html ) {
	const parsed = new DOMParser().parseFromString(
		html.replace( /<br\s*\/?\s*>|<\/p>/gi, ' ' ),
		'text/html'
	);
	return ( parsed.body.textContent || '' ).replace( /\s+/g, ' ' );
}

function singleLinePhrase( html ) {
	return String( html || '' )
		.replace( /<br\s*\/?\s*>|<\/?p(?:\s[^>]*)?>/gi, ' ' )
		.replace( /[\r\n]+/g, ' ' );
}

function DividerLogoControl( { choice, imageId, onChoice, onImage } ) {
	return (
		<>
			<SelectControl
				label={ __( 'Divider icon', 'rise-landing-pages' ) }
				value={ choice }
				options={ dividerOptions }
				onChange={ onChoice }
			/>
			{ choice === 'custom' && (
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ ( media ) => onImage( media.id || 0 ) }
						allowedTypes={ [ 'image' ] }
						value={ imageId }
						render={ ( { open } ) => (
							<Button variant="secondary" onClick={ open }>
								{ imageId
									? __( 'Change image', 'rise-landing-pages' )
									: __(
											'Choose image',
											'rise-landing-pages'
									  ) }
							</Button>
						) }
					/>
				</MediaUploadCheck>
			) }
		</>
	);
}

export default function Separator( { attributes, setAttributes } ) {
	const builtInLogos = window.riseLandingEditor?.separatorLogos || {};
	const previewRef = useRef( null );
	const firstSetRef = useRef( null );
	const [ repeatCount, setRepeatCount ] = useState( 3 );
	const texts = Array.isArray( attributes.texts ) ? attributes.texts : [];
	const showMainLogo =
		attributes.showMainLogo !== false && attributes.logoChoice !== 'none';
	const motion = [ 'auto', 'scroll', 'none' ].includes( attributes.motion )
		? attributes.motion
		: 'auto';
	const dividerChoice = dividerOptions.some(
		( item ) => item.value === attributes.dividerLogoChoice
	)
		? attributes.dividerLogoChoice
		: 'brand';
	const displayTexts = texts.length
		? texts
		: [ __( 'Move with Rise', 'rise-landing-pages' ) ];
	const customLogos = useSelect(
		( select ) => ( {
			divider: attributes.customDividerLogoId
				? select( 'core' ).getMedia( attributes.customDividerLogoId )
						?.source_url
				: '',
		} ),
		[ attributes.customDividerLogoId ]
	);
	useEffect( () => {
		const viewport = previewRef.current;
		const firstSet = firstSetRef.current;
		if ( ! viewport || ! firstSet ) {
			return;
		}
		const update = () => {
			const gap =
				parseFloat( window.getComputedStyle( firstSet ).gap ) || 0;
			const setWidth = firstSet.getBoundingClientRect().width;
			const width = viewport.getBoundingClientRect().width;
			if ( setWidth > 0 && width > 0 ) {
				setRepeatCount(
					Math.min(
						64,
						Math.max(
							2,
							Math.ceil( width / ( setWidth + gap ) ) + 2
						)
					)
				);
			}
		};
		const observer = new ResizeObserver( update );
		observer.observe( viewport );
		observer.observe( firstSet );
		update();
		return () => observer.disconnect();
	}, [ attributes.gap ] );
	const logoUrl = ( choice ) => {
		if ( choice === 'none' ) {
			return '';
		}
		if ( choice === 'custom' ) {
			return customLogos.divider || '';
		}
		return builtInLogos[ choice ] || builtInLogos.brand || '';
	};
	const primaryUrl = builtInLogos.fitness || '';
	const dividerUrl = logoUrl( dividerChoice );
	const logo = ( choice, url, type, key ) => {
		if ( choice === 'none' ) {
			return null;
		}
		return (
			<span
				key={ key }
				className={ `rise-lp__separator-item rise-lp__separator-logo--${ type }` }
			>
				{ url ? (
					<img
						src={ url }
						alt=""
						className={
							url.includes( 'rise-medical-icon.png' )
								? 'rise-lp__separator-medical-icon'
								: undefined
						}
					/>
				) : (
					<span className="rise-lp__separator-editor-fallback">
						{ choice === 'rise' || type === 'divider'
							? 'R'
							: 'RISE' }
					</span>
				) }
			</span>
		);
	};
	const renderItems = ( repeat ) =>
		displayTexts
			.flatMap( ( value, index ) => [
				logo(
					dividerChoice,
					dividerUrl,
					'divider',
					`${ repeat }-${ index }-divider-a`
				),
				showMainLogo
					? logo(
							'fitness',
							primaryUrl,
							'primary',
							`${ repeat }-${ index }-primary`
					  )
					: null,
				showMainLogo
					? logo(
							dividerChoice,
							dividerUrl,
							'divider',
							`${ repeat }-${ index }-divider-b`
					  )
					: null,
				<RichText
					key={ `${ repeat }-${ index }-text` }
					tagName="span"
					className="rise-lp__separator-item rise-lp__separator-text"
					value={ value }
					allowedFormats={ [ 'rise-landing/outline' ] }
					placeholder={ __( 'Add phrase', 'rise-landing-pages' ) }
					onChange={ ( next ) =>
						updateText( index, singleLinePhrase( next ) )
					}
					onKeyDownCapture={ ( event ) => {
						if ( event.key === 'Enter' ) {
							event.preventDefault();
						}
					} }
				/>,
			] )
			.filter( Boolean );
	const updateText = ( index, value ) =>
		setAttributes( {
			texts: texts.map( ( text, i ) => ( i === index ? value : text ) ),
		} );
	return (
		<>
			<BlockSettingsToolbar />
			<InspectorControls>
				<PanelBody
					title={ __( 'Separator design', 'rise-landing-pages' ) }
				>
					<SelectControl
						label={ __( 'Background', 'rise-landing-pages' ) }
						value={ attributes.background }
						options={ backgrounds }
						onChange={ ( background ) =>
							setAttributes( { background } )
						}
					/>
					<ToggleControl
						label={ __(
							'Show RISE wordmark',
							'rise-landing-pages'
						) }
						checked={ showMainLogo }
						onChange={ ( enabled ) =>
							setAttributes( {
								showMainLogo: enabled,
								logoChoice: enabled ? 'fitness' : 'none',
							} )
						}
					/>
					<DividerLogoControl
						choice={ dividerChoice }
						imageId={ attributes.customDividerLogoId }
						onChoice={ ( dividerLogoChoice ) =>
							setAttributes( { dividerLogoChoice } )
						}
						onImage={ ( customDividerLogoId ) =>
							setAttributes( { customDividerLogoId } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Marquee', 'rise-landing-pages' ) }>
					<SelectControl
						label={ __( 'Movement', 'rise-landing-pages' ) }
						value={ motion }
						options={ [
							{
								label: __( 'Automatic', 'rise-landing-pages' ),
								value: 'auto',
							},
							{
								label: __(
									'Follow page scroll',
									'rise-landing-pages'
								),
								value: 'scroll',
							},
							{
								label: __(
									'No movement',
									'rise-landing-pages'
								),
								value: 'none',
							},
						] }
						onChange={ ( next ) =>
							setAttributes( { motion: next } )
						}
					/>
					{ motion !== 'none' && (
						<SelectControl
							label={ __( 'Direction', 'rise-landing-pages' ) }
							value={ attributes.direction }
							options={ [
								{
									label: __(
										'Move left',
										'rise-landing-pages'
									),
									value: 'left',
								},
								{
									label: __(
										'Move right',
										'rise-landing-pages'
									),
									value: 'right',
								},
							] }
							onChange={ ( direction ) =>
								setAttributes( { direction } )
							}
						/>
					) }
					<RangeControl
						label={ __(
							'Gap between items (px)',
							'rise-landing-pages'
						) }
						min={ 0 }
						max={ 160 }
						value={ attributes.gap }
						onChange={ ( gap ) => setAttributes( { gap } ) }
					/>
					{ motion === 'auto' && (
						<RangeControl
							label={ __(
								'Speed (px per second)',
								'rise-landing-pages'
							) }
							min={ 10 }
							max={ 300 }
							value={ attributes.speed }
							onChange={ ( speed ) => setAttributes( { speed } ) }
						/>
					) }
					{ motion === 'scroll' && (
						<RangeControl
							label={ __(
								'Scroll speed (%)',
								'rise-landing-pages'
							) }
							min={ 10 }
							max={ 300 }
							value={ attributes.scrollSpeed ?? 100 }
							onChange={ ( scrollSpeed ) =>
								setAttributes( { scrollSpeed } )
							}
						/>
					) }
					{ motion !== 'none' && (
						<ToggleControl
							label={ __(
								'Pause on hover',
								'rise-landing-pages'
							) }
							checked={ !! attributes.pauseOnHover }
							onChange={ ( pauseOnHover ) =>
								setAttributes( { pauseOnHover } )
							}
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Text', 'rise-landing-pages' ) }>
					{ texts.map( ( value, index ) => (
						<div
							key={ index }
							className="rise-lp-editor__separator-text-control"
						>
							<TextControl
								label={ `${ __(
									'Phrase',
									'rise-landing-pages'
								) } ${ index + 1 }` }
								value={ plainPhrase( value ) }
								onChange={ ( next ) =>
									updateText( index, next )
								}
							/>
							<Button
								variant="tertiary"
								isDestructive
								disabled={ texts.length <= 1 }
								onClick={ () =>
									setAttributes( {
										texts: texts.filter(
											( _, i ) => i !== index
										),
									} )
								}
							>
								{ __( 'Remove', 'rise-landing-pages' ) }
							</Button>
						</div>
					) ) }
					<Button
						variant="secondary"
						disabled={ texts.length >= 6 }
						onClick={ () =>
							setAttributes( { texts: [ ...texts, '' ] } )
						}
					>
						{ __( 'Add phrase', 'rise-landing-pages' ) }
					</Button>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: `${
						window.riseLandingEditor?.rootClass || 'rise-lp'
					} rise-lp-editor rise-lp-editor--separator`,
				} ) }
			>
				<div
					ref={ previewRef }
					className={ `rise-lp__separator rise-lp__separator--${ attributes.background }` }
					style={ {
						'--rise-separator-gap': `${ attributes.gap }px`,
					} }
				>
					<div className="rise-lp__separator-track">
						{ Array.from(
							{ length: repeatCount },
							( _, repeat ) => (
								<div
									key={ repeat }
									ref={
										repeat === 0 ? firstSetRef : undefined
									}
									className="rise-lp__separator-editor-set"
								>
									{ renderItems( repeat ) }
								</div>
							)
						) }
					</div>
				</div>
			</div>
		</>
	);
}
