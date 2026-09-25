import BlockSettingsToolbar from './BlockSettingsToolbar';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const DESKTOP_MAX_HEIGHT = 400;
const MOBILE_MAX_HEIGHT = 240;
const clampHeight = ( height, maxHeight ) =>
	Math.max( 0, Math.min( maxHeight, Math.round( height ) ) );

export default function Spacer( { attributes, setAttributes } ) {
	const isMobile = useSelect(
		( select ) => select( 'core/editor' )?.getDeviceType?.() === 'Mobile',
		[]
	);
	const heightKey = isMobile ? 'mobileHeight' : 'height';
	const maxHeight = isMobile ? MOBILE_MAX_HEIGHT : DESKTOP_MAX_HEIGHT;
	const spacerRef = useRef( null );
	const dragStart = useRef( null );
	const [ dragHeight, setDragHeight ] = useState( null );
	const previewHeight = dragHeight ?? attributes[ heightKey ];

	const heightAtPointer = ( clientY ) => {
		const drag = dragStart.current;
		return clampHeight(
			drag.height + ( clientY - drag.clientY ) / drag.scale,
			drag.maxHeight
		);
	};

	const startResize = ( event ) => {
		if ( event.button !== 0 ) {
			return;
		}
		event.preventDefault();
		event.stopPropagation();
		const spacer = spacerRef.current;
		const scale = spacer?.offsetHeight
			? spacer.getBoundingClientRect().height / spacer.offsetHeight
			: 1;
		dragStart.current = {
			pointerId: event.pointerId,
			clientY: event.clientY,
			height: attributes[ heightKey ],
			heightKey,
			maxHeight,
			scale: scale || 1,
		};
		event.currentTarget.setPointerCapture( event.pointerId );
	};

	const moveResize = ( event ) => {
		if ( dragStart.current?.pointerId !== event.pointerId ) {
			return;
		}
		event.stopPropagation();
		setDragHeight( heightAtPointer( event.clientY ) );
	};

	const stopResize = ( event ) => {
		if ( dragStart.current?.pointerId !== event.pointerId ) {
			return;
		}
		event.stopPropagation();
		const nextHeight = heightAtPointer( event.clientY );
		const resizedKey = dragStart.current.heightKey;
		dragStart.current = null;
		setDragHeight( null );
		if ( nextHeight !== attributes[ resizedKey ] ) {
			setAttributes( { [ resizedKey ]: nextHeight } );
		}
	};

	const cancelResize = ( event ) => {
		if ( dragStart.current?.pointerId === event.pointerId ) {
			dragStart.current = null;
			setDragHeight( null );
		}
	};

	const resizeWithKeyboard = ( event ) => {
		let nextHeight;
		const step = event.shiftKey ? 10 : 1;
		switch ( event.key ) {
			case 'ArrowUp':
			case 'ArrowRight':
				nextHeight = attributes[ heightKey ] + step;
				break;
			case 'ArrowDown':
			case 'ArrowLeft':
				nextHeight = attributes[ heightKey ] - step;
				break;
			case 'Home':
				nextHeight = 0;
				break;
			case 'End':
				nextHeight = maxHeight;
				break;
			default:
				return;
		}
		event.preventDefault();
		event.stopPropagation();
		setAttributes( {
			[ heightKey ]: clampHeight( nextHeight, maxHeight ),
		} );
	};

	return (
		<>
			<BlockSettingsToolbar />
			<InspectorControls>
				<PanelBody title={ __( 'Spacing', 'rise-landing-pages' ) }>
					<RangeControl
						label={ __( 'Desktop height', 'rise-landing-pages' ) }
						value={ attributes.height }
						min={ 0 }
						max={ DESKTOP_MAX_HEIGHT }
						onChange={ ( height ) => setAttributes( { height } ) }
					/>
					<RangeControl
						label={ __( 'Mobile height', 'rise-landing-pages' ) }
						value={ attributes.mobileHeight }
						min={ 0 }
						max={ MOBILE_MAX_HEIGHT }
						onChange={ ( mobileHeight ) =>
							setAttributes( { mobileHeight } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: `rise-lp-editor rise-lp-editor--spacer${
						attributes.heroSlot
							? ` rise-lp-editor--spacer-${ attributes.heroSlot }`
							: ''
					}`,
					style: { minHeight: 0 },
				} ) }
			>
				<div
					ref={ spacerRef }
					className="rise-lp-editor__spacer"
					style={ { height: previewHeight } }
				>
					<div
						className="rise-lp-editor__spacer-resize"
						role="slider"
						tabIndex={ 0 }
						aria-label={
							isMobile
								? __(
										'Mobile spacer height',
										'rise-landing-pages'
								  )
								: __(
										'Desktop spacer height',
										'rise-landing-pages'
								  )
						}
						aria-orientation="vertical"
						aria-valuemin={ 0 }
						aria-valuemax={ maxHeight }
						aria-valuenow={ previewHeight }
						title={ __(
							'Drag to resize spacer',
							'rise-landing-pages'
						) }
						onPointerDown={ startResize }
						onPointerMove={ moveResize }
						onPointerUp={ stopResize }
						onPointerCancel={ cancelResize }
						onLostPointerCapture={ cancelResize }
						onKeyDown={ resizeWithKeyboard }
					>
						{ __( 'Space', 'rise-landing-pages' ) } ·{ ' ' }
						{ previewHeight } px
					</div>
				</div>
			</div>
		</>
	);
}
