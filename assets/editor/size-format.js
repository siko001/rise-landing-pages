import { BlockControls } from '@wordpress/block-editor';
import {
	Button,
	Popover,
	TextControl,
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

const FORMAT = 'rise-landing/size';
const MIN_SIZE = 1;
const MAX_SIZE = 200;

function SizeEdit( { isActive, value, onChange, activeAttributes } ) {
	const [ open, setOpen ] = useState( false );
	const [ draftSize, setDraftSize ] = useState( '' );
	const changeSize = ( nextSize ) => {
		setDraftSize( nextSize );
		const size = Number( nextSize );
		if (
			! /^\d+$/.test( nextSize ) ||
			! Number.isInteger( size ) ||
			size < MIN_SIZE ||
			size > MAX_SIZE
		) {
			return;
		}
		onChange(
			applyFormat( value, {
				type: FORMAT,
				attributes: {
					size: String( size ),
					style: `--rise-inline-size:${ size }px`,
				},
			} )
		);
	};
	const clearSize = () => {
		onChange( removeFormat( value, FORMAT ) );
		setOpen( false );
	};
	return (
		<>
			<BlockControls group="inline">
				<ToolbarGroup>
					<ToolbarButton
						icon="heading"
						title={ __( 'Text size', 'rise-landing-pages' ) }
						isPressed={ isActive || open }
						onClick={ () => {
							setDraftSize( activeAttributes?.size || '' );
							setOpen( ! open );
						} }
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
						<TextControl
							type="number"
							label={ __(
								'Selected text size',
								'rise-landing-pages'
							) }
							value={ draftSize }
							min={ MIN_SIZE }
							max={ MAX_SIZE }
							step="1"
							onChange={ changeSize }
							help={ __( '1–200 px', 'rise-landing-pages' ) }
						/>
						<Button variant="tertiary" onClick={ clearSize }>
							{ __( 'Heading default', 'rise-landing-pages' ) }
						</Button>
					</div>
				</Popover>
			) }
		</>
	);
}

registerFormatType( FORMAT, {
	title: __( 'Text size', 'rise-landing-pages' ),
	tagName: 'span',
	className: 'rise-lp__text-size',
	attributes: { size: 'data-rise-size', style: 'style' },
	edit: SizeEdit,
} );
