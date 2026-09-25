import { BlockControls, useBlockEditContext } from '@wordpress/block-editor';
import {
	Popover,
	RangeControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerFormatType } from '@wordpress/rich-text';

function LineHeightEdit( { contentRef, isVisible } ) {
	const [ open, setOpen ] = useState( false );
	const { clientId } = useBlockEditContext();
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const { blockName, attributes, parentId, parentAttributes } = useSelect(
		( select ) => {
			const editor = select( 'core/block-editor' );
			const rootId = editor.getBlockRootClientId( clientId );
			return {
				blockName: editor.getBlockName( clientId ),
				attributes: editor.getBlockAttributes( clientId ) || {},
				parentId: rootId,
				parentAttributes: editor.getBlockAttributes( rootId ) || {},
			};
		},
		[ clientId ]
	);
	const isCard = contentRef.current?.tagName === 'H3';
	const attribute = isCard ? 'cardHeadingLeading' : 'headingLeading';
	const isHeroPart = blockName === 'rise-landing/hero-part';
	const targetId = isHeroPart ? parentId : clientId;
	const currentAttributes = isHeroPart ? parentAttributes : attributes;
	let defaultValue = 1.14;
	if ( isCard ) {
		defaultValue = blockName === 'rise-landing/faq' ? 1.5 : 1.25;
	} else if ( blockName === 'rise-landing/hero' || isHeroPart ) {
		defaultValue = 0.95;
	}
	const value = currentAttributes[ attribute ] ?? defaultValue;

	if ( isVisible === false || ! targetId ) {
		return null;
	}

	return (
		<>
			<BlockControls group="inline">
				<ToolbarGroup>
					<ToolbarButton
						icon="editor-alignleft"
						title={ __(
							'Heading line height',
							'rise-landing-pages'
						) }
						isPressed={ open }
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
						<RangeControl
							label={ __(
								'Heading line height',
								'rise-landing-pages'
							) }
							value={ value }
							min={ 0.7 }
							max={ isCard ? 1.8 : 1.5 }
							step={ 0.05 }
							onChange={ ( lineHeight ) =>
								updateBlockAttributes( targetId, {
									[ attribute ]: lineHeight,
								} )
							}
						/>
					</div>
				</Popover>
			) }
		</>
	);
}

registerFormatType( 'rise-landing/line-height', {
	title: __( 'Heading line height', 'rise-landing-pages' ),
	tagName: 'span',
	className: 'rise-lp__line-height-control',
	edit: LineHeightEdit,
} );
