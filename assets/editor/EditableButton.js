import {
	Button,
	Popover,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * The same click-to-edit controls for section and service-card links.
 *
 * @param {Object} props Component properties.
 * @return {Element} Editable button and its toolbar.
 */
export default function EditableButton( props ) {
	const {
		label,
		fallbackLabel,
		url,
		fallbackUrl,
		newTab,
		onChange,
		onAdd,
		addLabel,
		onRemove,
		children,
		className = '',
		style,
	} = props;
	const [ isOpen, setIsOpen ] = useState( false );
	const buttonRef = useRef( null );
	const displayLabel = label || fallbackLabel;

	return (
		<>
			<button
				ref={ buttonRef }
				type="button"
				className={ `rise-lp-editor__editable-button ${ className }` }
				style={ style }
				aria-haspopup="dialog"
				aria-label={
					__( 'Edit button', 'rise-landing-pages' ) +
					': ' +
					displayLabel
				}
				aria-expanded={ isOpen }
				onClick={ ( event ) => {
					event.stopPropagation();
					setIsOpen( true );
				} }
			>
				<span className="rise-lp__button-label">{ displayLabel }</span>
				<span className="rise-lp__button-copy" aria-hidden="true">
					{ displayLabel }
				</span>
			</button>
			{ isOpen && (
				<Popover
					anchor={ buttonRef.current }
					placement="top-start"
					onClose={ () => setIsOpen( false ) }
					focusOnMount="firstElement"
				>
					<div className="rise-lp-editor__button-toolbar">
						<TextControl
							label={ __( 'Button text', 'rise-landing-pages' ) }
							value={ label || '' }
							placeholder={ fallbackLabel }
							onChange={ ( nextLabel ) =>
								onChange( { label: nextLabel } )
							}
						/>
						<TextControl
							label={ __( 'Link', 'rise-landing-pages' ) }
							type="url"
							value={ url || '' }
							placeholder={ fallbackUrl || 'https://' }
							onChange={ ( nextUrl ) =>
								onChange( { url: nextUrl } )
							}
						/>
						<ToggleControl
							label={ __(
								'Open in a new tab',
								'rise-landing-pages'
							) }
							checked={ !! newTab }
							onChange={ ( nextNewTab ) =>
								onChange( { newTab: nextNewTab } )
							}
						/>
						{ children }
						<div className="rise-lp-editor__button-toolbar-actions">
							{ onAdd && (
								<Button
									variant="secondary"
									icon="plus"
									onClick={ () => {
										onAdd();
										setIsOpen( false );
									} }
								>
									{ addLabel }
								</Button>
							) }
							<Button
								variant="tertiary"
								icon="trash"
								isDestructive
								onClick={ onRemove }
							>
								{ __( 'Remove button', 'rise-landing-pages' ) }
							</Button>
						</div>
					</div>
				</Popover>
			) }
		</>
	);
}
