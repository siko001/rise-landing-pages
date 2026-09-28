import { BlockControls } from '@wordpress/block-editor';
import { Dropdown, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export default function BlockSettingsToolbar( {
	backgroundControl,
	headingFontControl,
} ) {
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );
	const settingsButton = ( onClick, isPressed = false ) => (
		<ToolbarButton
			icon="admin-generic"
			title={ __( 'Block settings', 'rise-landing-pages' ) }
			aria-label={ __( 'Block settings', 'rise-landing-pages' ) }
			isPressed={ isPressed }
			onClick={ onClick }
		/>
	);
	return (
		<BlockControls>
			<ToolbarGroup>
				{ headingFontControl && (
					<Dropdown
						popoverProps={ { placement: 'bottom-start' } }
						renderToggle={ ( { isOpen, onToggle } ) => (
							<ToolbarButton
								icon="editor-paragraph"
								title={ __(
									'Heading font',
									'rise-landing-pages'
								) }
								aria-label={ __(
									'Heading font',
									'rise-landing-pages'
								) }
								isPressed={ isOpen }
								onClick={ onToggle }
							/>
						) }
						renderContent={ () => (
							<div className="rise-lp-editor__background-popover">
								{ headingFontControl }
							</div>
						) }
					/>
				) }
				{ backgroundControl ? (
					<Dropdown
						popoverProps={ { placement: 'bottom-start' } }
						renderToggle={ ( { isOpen, onToggle } ) =>
							settingsButton( () => {
								openGeneralSidebar( 'edit-post/block' );
								onToggle();
							}, isOpen )
						}
						renderContent={ () => (
							<div className="rise-lp-editor__background-popover">
								{ backgroundControl }
							</div>
						) }
					/>
				) : (
					settingsButton( () =>
						openGeneralSidebar( 'edit-post/block' )
					)
				) }
			</ToolbarGroup>
		</BlockControls>
	);
}
