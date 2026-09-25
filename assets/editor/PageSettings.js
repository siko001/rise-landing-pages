import {
	Notice,
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

export default function PageSettings() {
	const {
		meta,
		postType,
		blocks,
		title,
		savedTitle,
		isSaving,
		isAutosaving,
		isPreviewing,
		saveSucceeded,
	} = useSelect(
		( getStore ) => ( {
			meta:
				getStore( 'core/editor' ).getEditedPostAttribute( 'meta' ) ||
				{},
			postType: getStore( 'core/editor' ).getCurrentPostType(),
			blocks: getStore( 'core/block-editor' ).getBlocks(),
			title: getStore( 'core/editor' ).getEditedPostAttribute( 'title' ),
			savedTitle:
				getStore( 'core/editor' ).getCurrentPostAttribute( 'title' ),
			isSaving: getStore( 'core/editor' ).isSavingPost(),
			isAutosaving: getStore( 'core/editor' ).isAutosavingPost(),
			isPreviewing: getStore( 'core/editor' ).isPreviewingPost(),
			saveSucceeded:
				getStore( 'core/editor' ).didPostSaveRequestSucceed(),
		} ),
		[]
	);
	const { editPost, removeEditorPanel } = useDispatch( 'core/editor' );
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const unlocked = useRef( false );
	const lastTitle = useRef( null );
	const saveStartedWithTitle = useRef( null );
	const [ duplicateTitle, setDuplicateTitle ] = useState( '' );
	useEffect( () => {
		if ( postType !== 'page' || ! window.riseLandingEditor?.isLanding ) {
			return;
		}
		removeEditorPanel( 'featured-image' );
		removeEditorPanel( 'post-excerpt' );
	}, [ postType, removeEditorPanel ] );
	useEffect( () => {
		const config = window.riseLandingEditor;
		if (
			postType !== 'page' ||
			! config?.isLanding ||
			isSaving ||
			typeof title !== 'string'
		) {
			return;
		}
		if ( lastTitle.current === null ) {
			lastTitle.current = title;
			return;
		}
		if ( title === lastTitle.current ) {
			return;
		}
		lastTitle.current = title;
		setDuplicateTitle( '' );
		removeNotice( 'rise-landing-duplicate-title' );
		if ( ! title.trim() ) {
			return;
		}
		const controller = new AbortController();
		const timer = window.setTimeout( async () => {
			try {
				const response = await window.fetch( config.titleCheckUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.restNonce,
					},
					body: JSON.stringify( { title } ),
					signal: controller.signal,
				} );
				if ( ! response.ok ) {
					return;
				}
				const result = await response.json();
				if ( controller.signal.aborted ) {
					return;
				}
				if ( result.slug ) {
					editPost( { slug: result.slug } );
				}
			} catch ( error ) {
				// Saving still resolves duplicate titles and slugs on the server.
			}
		}, 300 );
		return () => {
			window.clearTimeout( timer );
			controller.abort();
		};
	}, [ title, postType, isSaving, editPost, removeNotice ] );
	useEffect( () => {
		if ( postType !== 'page' || ! window.riseLandingEditor?.isLanding ) {
			return;
		}
		if ( isSaving && ! isAutosaving && ! isPreviewing ) {
			if ( saveStartedWithTitle.current === null ) {
				saveStartedWithTitle.current = title;
			}
			return;
		}
		if ( isSaving || saveStartedWithTitle.current === null ) {
			return;
		}
		const requestedTitle = saveStartedWithTitle.current;
		saveStartedWithTitle.current = null;
		if ( saveSucceeded && savedTitle && savedTitle !== requestedTitle ) {
			setDuplicateTitle( savedTitle );
			createNotice(
				'warning',
				sprintf(
					/* translators: %s: the unique page title saved by WordPress. */
					__(
						'Another page already has that name. This page was saved as “%s”.',
						'rise-landing-pages'
					),
					savedTitle
				),
				{
					id: 'rise-landing-duplicate-title',
					isDismissible: true,
				}
			);
		}
	}, [
		isSaving,
		isAutosaving,
		isPreviewing,
		saveSucceeded,
		savedTitle,
		title,
		postType,
		createNotice,
	] );
	useEffect( () => {
		if (
			unlocked.current ||
			! blocks.length ||
			! window.riseLandingEditor?.isLanding
		) {
			return;
		}
		unlocked.current = true;
		const lockedIds = blocks
			.filter(
				( block ) =>
					block.name.startsWith( 'rise-landing/' ) &&
					( block.attributes.lock?.move ||
						block.attributes.lock?.remove )
			)
			.map( ( block ) => block.clientId );
		if ( lockedIds.length ) {
			updateBlockAttributes( lockedIds, {
				lock: { move: false, remove: false },
			} );
		}
	}, [ blocks, updateBlockAttributes ] );
	if ( postType !== 'page' || ! window.riseLandingEditor?.isLanding ) {
		return null;
	}
	const update = ( key, value ) =>
		editPost( { meta: { ...meta, [ key ]: value } } );
	const settings = window.riseLandingEditor?.settings || {};
	const legacySiteLayout = meta._rise_landing_layout === 'site';
	const header = legacySiteLayout
		? 'site'
		: meta._rise_landing_header || 'minimal';
	const footer = legacySiteLayout
		? 'site'
		: meta._rise_landing_footer || 'landing';
	const updateChrome = ( key, value ) => {
		const currentMeta =
			select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		const currentHeader =
			currentMeta._rise_landing_layout === 'site'
				? 'site'
				: currentMeta._rise_landing_header || 'minimal';
		const currentFooter =
			currentMeta._rise_landing_layout === 'site'
				? 'site'
				: currentMeta._rise_landing_footer || 'landing';
		editPost( {
			meta: {
				...currentMeta,
				_rise_landing_layout: 'standalone',
				_rise_landing_header:
					key === '_rise_landing_header' ? value : currentHeader,
				_rise_landing_footer:
					key === '_rise_landing_footer' ? value : currentFooter,
			},
		} );
	};
	return (
		<PluginDocumentSettingPanel
			name="rise-landing-page"
			className="rise-lp-page-settings"
			title={ __( 'Rise Landing Page', 'rise-landing-pages' ) }
			icon="layout"
		>
			{ duplicateTitle && (
				<Notice status="warning" isDismissible={ false }>
					{ sprintf(
						/* translators: %s: the unique page title WordPress will save. */
						__(
							'Another page already has that name. This page was saved as “%s”.',
							'rise-landing-pages'
						),
						duplicateTitle
					) }
				</Notice>
			) }
			<SelectControl
				label={ __( 'Header', 'rise-landing-pages' ) }
				value={ header }
				options={ [
					{
						label: __(
							'Minimal landing header',
							'rise-landing-pages'
						),
						value: 'minimal',
					},
					{
						label: __( 'Site header', 'rise-landing-pages' ),
						value: 'site',
					},
					{
						label: __( 'Hidden', 'rise-landing-pages' ),
						value: 'hidden',
					},
				] }
				onChange={ ( value ) =>
					updateChrome( '_rise_landing_header', value )
				}
			/>
			<SelectControl
				label={ __( 'Footer', 'rise-landing-pages' ) }
				value={ footer }
				options={ [
					{
						label: __( 'Landing footer', 'rise-landing-pages' ),
						value: 'landing',
					},
					{
						label: __( 'Site footer', 'rise-landing-pages' ),
						value: 'site',
					},
					{
						label: __( 'Hidden', 'rise-landing-pages' ),
						value: 'hidden',
					},
				] }
				onChange={ ( value ) =>
					updateChrome( '_rise_landing_footer', value )
				}
			/>
			<TextControl
				label={ __(
					'Booking URL for this page',
					'rise-landing-pages'
				) }
				type="url"
				value={ meta._rise_landing_booking_url || '' }
				placeholder={ settings.booking_url || 'https://' }
				onChange={ ( value ) =>
					update( '_rise_landing_booking_url', value )
				}
				help={ __(
					'Leave blank to use your site default. Individual sections can use their own destination.',
					'rise-landing-pages'
				) }
			/>
			<TextControl
				label={ __(
					'Button label for this page',
					'rise-landing-pages'
				) }
				value={ meta._rise_landing_cta_label || '' }
				placeholder={
					settings.cta_label ||
					__( 'Book an appointment', 'rise-landing-pages' )
				}
				onChange={ ( value ) =>
					update( '_rise_landing_cta_label', value )
				}
				help={ __(
					'Leave blank to use your site default.',
					'rise-landing-pages'
				) }
			/>
			<PanelBody
				title={ __( 'Advanced', 'rise-landing-pages' ) }
				initialOpen={ false }
			>
				<TextControl
					label={ __(
						'Additional page class',
						'rise-landing-pages'
					) }
					value={ meta._rise_landing_body_class || '' }
					onChange={ ( value ) =>
						update( '_rise_landing_body_class', value )
					}
					help={ __(
						'Optional. Use only if your website developer supplies a value.',
						'rise-landing-pages'
					) }
				/>
			</PanelBody>
		</PluginDocumentSettingPanel>
	);
}
