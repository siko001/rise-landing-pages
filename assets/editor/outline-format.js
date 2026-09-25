import { BlockControls, RichText } from '@wordpress/block-editor';
import {
	Button,
	ColorPalette,
	Popover,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	applyFormat,
	registerFormatType,
	toggleFormat,
} from '@wordpress/rich-text';
import { expandOutlineTokens, safeOutlineColor } from './outline-utils';
import { colorPresets } from './color-presets';

const FORMAT = 'rise-landing/outline';

function OutlineEdit( { isActive, value, onChange, activeAttributes } ) {
	const [ showColors, setShowColors ] = useState( false );
	const setColor = ( input ) => {
		const color = safeOutlineColor( input );
		onChange(
			applyFormat( value, {
				type: FORMAT,
				attributes: color
					? { color, style: `--rise-outline-color:${ color }` }
					: {},
			} )
		);
	};
	return (
		<>
			<BlockControls group="inline">
				<ToolbarGroup>
					<ToolbarButton
						icon="editor-textcolor"
						title={ __( 'Outline text', 'rise-landing-pages' ) }
						isPressed={ isActive }
						onClick={ () =>
							onChange( toggleFormat( value, { type: FORMAT } ) )
						}
					/>
					<ToolbarButton
						icon="art"
						title={ __( 'Outline colour', 'rise-landing-pages' ) }
						isPressed={ showColors }
						onClick={ () => setShowColors( ! showColors ) }
					/>
				</ToolbarGroup>
			</BlockControls>
			{ showColors && (
				<Popover
					placement="bottom-start"
					onClose={ () => setShowColors( false ) }
					focusOnMount="firstElement"
				>
					<div className="rise-lp-editor__outline-colors">
						<p>{ __( 'Outline colour', 'rise-landing-pages' ) }</p>
						<ColorPalette
							colors={ colorPresets() }
							value={ safeOutlineColor(
								activeAttributes?.color
							) }
							onChange={ setColor }
							enableAlpha={ false }
							clearable={ false }
						/>
						<Button
							variant="secondary"
							onClick={ () => setColor( '' ) }
						>
							{ __( 'Use site default', 'rise-landing-pages' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => setShowColors( false ) }
						>
							{ __( 'Done', 'rise-landing-pages' ) }
						</Button>
					</div>
				</Popover>
			) }
		</>
	);
}

registerFormatType( FORMAT, {
	title: __( 'Outline text', 'rise-landing-pages' ),
	tagName: 'span',
	className: 'rise-lp__outline',
	attributes: { color: 'data-rise-outline-color', style: 'style' },
	edit: OutlineEdit,
} );

export function LandingRichText( { onChange, ...props } ) {
	return (
		<RichText
			{ ...props }
			onChange={ ( value ) => onChange( expandOutlineTokens( value ) ) }
		/>
	);
}
