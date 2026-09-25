import { BlockControls } from '@wordpress/block-editor';
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
	removeFormat,
} from '@wordpress/rich-text';
import { colorPresets } from './color-presets';
import { safeOutlineColor } from './outline-utils';

const FORMAT = 'rise-landing/text-color';

function TextColorEdit( { isActive, value, onChange, activeAttributes } ) {
	const [ open, setOpen ] = useState( false );
	const setColor = ( input ) => {
		const color = safeOutlineColor( input );
		onChange(
			color
				? applyFormat( value, {
						type: FORMAT,
						attributes: {
							color,
							style: `--rise-inline-text-color:${ color }`,
						},
				  } )
				: removeFormat( value, FORMAT )
		);
	};
	return (
		<>
			<BlockControls group="inline">
				<ToolbarGroup>
					<ToolbarButton
						icon="art"
						title={ __( 'Text colour', 'rise-landing-pages' ) }
						isPressed={ isActive || open }
						onClick={ () => setOpen( ! open ) }
					/>
				</ToolbarGroup>
			</BlockControls>
			{ open && (
				<Popover
					placement="bottom-start"
					onClose={ () => setOpen( false ) }
					focusOnMount="firstElement"
				>
					<div className="rise-lp-editor__outline-colors">
						<p>{ __( 'Text colour', 'rise-landing-pages' ) }</p>
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
							{ __( 'Use default colour', 'rise-landing-pages' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => setOpen( false ) }
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
	title: __( 'Text colour', 'rise-landing-pages' ),
	tagName: 'span',
	className: 'rise-lp__text-color',
	attributes: { color: 'data-rise-text-color', style: 'style' },
	edit: TextColorEdit,
} );
