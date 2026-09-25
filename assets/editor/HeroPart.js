import BlockSettingsToolbar from './BlockSettingsToolbar';
import { LandingRichText as RichText } from './outline-format';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	ActionPreview,
	BODY_FORMATS,
	EYEBROW_FORMATS,
	HEADING_FORMATS,
	INLINE_FORMATS,
} from './common';

const PARTS = {
	eyebrow: {
		tagName: 'p',
		className: 'rise-lp__eyebrow',
		label: __( 'Supporting text', 'rise-landing-pages' ),
		formats: EYEBROW_FORMATS,
	},
	heading: {
		tagName: 'h1',
		className: 'rise-lp__heading',
		label: __( 'Main headline', 'rise-landing-pages' ),
		formats: HEADING_FORMATS,
	},
	description: {
		tagName: 'p',
		className: 'rise-lp__intro',
		label: __( 'Hero description', 'rise-landing-pages' ),
		formats: BODY_FORMATS,
	},
	reassurance: {
		tagName: 'p',
		className: 'rise-lp__reassurance',
		label: __( 'Reassurance', 'rise-landing-pages' ),
		formats: INLINE_FORMATS,
	},
};

export default function HeroPart( { attributes, setAttributes, clientId } ) {
	const role = attributes.role;
	const { parentId, parentAttributes, hasEyebrow, hasReassurance } =
		useSelect(
			( select ) => {
				const editor = select( 'core/block-editor' );
				const heroId = editor.getBlockRootClientId( clientId );
				const siblings = editor.getBlocks( heroId );
				return {
					parentId: heroId,
					parentAttributes: editor.getBlockAttributes( heroId ) || {},
					hasEyebrow: siblings.some(
						( block ) =>
							block.attributes.role === 'eyebrow' &&
							!! block.attributes.content
								?.replace( /<[^>]*>/g, '' )
								.trim()
					),
					hasReassurance: siblings.some(
						( block ) =>
							block.attributes.role === 'reassurance' &&
							!! block.attributes.content
								?.replace( /<[^>]*>/g, '' )
								.trim()
					),
				};
			},
			[ clientId ]
		);
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const isActions = role === 'actions';
	const part = PARTS[ role ];
	const gap =
		isActions && ! hasReassurance
			? attributes.gapWithoutReassurance
			: attributes.gap;
	const style = {
		'--rise-hero-part-gap': `${
			role === 'eyebrow' || ( role === 'heading' && ! hasEyebrow )
				? 0
				: gap
		}px`,
	};
	const blockProps = useBlockProps( {
		className: isActions ? 'rise-lp__actions' : 'rise-lp-editor__hero-part',
		style,
	} );
	const label = {
		heading: __( 'Space below supporting text (px)', 'rise-landing-pages' ),
		description: __( 'Space above description (px)', 'rise-landing-pages' ),
		reassurance: __( 'Space above reassurance (px)', 'rise-landing-pages' ),
		actions: __( 'Space above buttons (px)', 'rise-landing-pages' ),
	}[ role ];
	return (
		<>
			<BlockSettingsToolbar />
			{ role !== 'eyebrow' && (
				<InspectorControls>
					<PanelBody title={ __( 'Spacing', 'rise-landing-pages' ) }>
						<RangeControl
							label={ label }
							value={ attributes.gap }
							min={ 0 }
							max={ 240 }
							onChange={ ( value ) =>
								setAttributes( { gap: value } )
							}
						/>
						{ isActions && (
							<RangeControl
								label={ __(
									'Space when reassurance is empty (px)',
									'rise-landing-pages'
								) }
								value={ attributes.gapWithoutReassurance }
								min={ 0 }
								max={ 240 }
								onChange={ ( value ) =>
									setAttributes( {
										gapWithoutReassurance: value,
									} )
								}
							/>
						) }
					</PanelBody>
				</InspectorControls>
			) }
			{ isActions ? (
				<ActionPreview
					attributes={ parentAttributes }
					setAttributes={ ( values ) =>
						updateBlockAttributes( parentId, values )
					}
					blockProps={ blockProps }
					secondary
				/>
			) : (
				<div { ...blockProps }>
					<RichText
						tagName={ part.tagName }
						className={ part.className }
						value={ attributes.content }
						onChange={ ( content ) => setAttributes( { content } ) }
						placeholder={ part.label }
						aria-label={ part.label }
						allowedFormats={ part.formats }
					/>
				</div>
			) }
		</>
	);
}
