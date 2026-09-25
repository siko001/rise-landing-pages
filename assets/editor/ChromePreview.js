import { useSelect } from '@wordpress/data';
import { createPortal, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

function previewUrl( part, choice ) {
	const base = window.riseLandingEditor?.chromePreviewUrl;
	if ( ! base ) {
		return '';
	}
	const canonical = new URL( base, window.location.href );
	// Local installs can advertise a canonical host or port different from the
	// one used for the editor. Keep the preview on the editor's origin.
	const url = new URL(
		canonical.pathname + canonical.search + canonical.hash,
		window.location.origin
	);
	url.searchParams.set( 'rise_lp_editor_preview', '1' );
	url.searchParams.set(
		'rise_lp_editor_header',
		part === 'header' ? choice : 'hidden'
	);
	url.searchParams.set(
		'rise_lp_editor_footer',
		part === 'footer' ? choice : 'hidden'
	);
	return url.toString();
}

function ChromeFrame( { part, choice } ) {
	const frame = useRef( null );
	const [ state, setState ] = useState( 'loading' );
	const [ height, setHeight ] = useState( 160 );
	const src = previewUrl( part, choice );

	useEffect( () => {
		setState( 'loading' );
		if ( ! src || choice === 'hidden' ) {
			return undefined;
		}
		const timeout = window.setTimeout( () => setState( 'error' ), 8000 );
		const receive = ( event ) => {
			if (
				event.origin !== window.location.origin ||
				event.source !== frame.current?.contentWindow ||
				event.data?.type !== 'rise-lp-chrome-preview' ||
				event.data.part !== part ||
				event.data.choice !== choice
			) {
				return;
			}
			if ( event.data.status === 'ready' ) {
				setHeight(
					Math.min(
						part === 'footer' ? 2400 : 1200,
						Math.max( 48, event.data.height || 160 )
					)
				);
				setState( 'ready' );
				window.clearTimeout( timeout );
			} else if ( event.data.status === 'error' ) {
				setState( 'error' );
			}
		};
		window.addEventListener( 'message', receive );
		return () => {
			window.clearTimeout( timeout );
			window.removeEventListener( 'message', receive );
		};
	}, [ src, part, choice ] );

	if ( choice === 'hidden' ) {
		return null;
	}

	const label =
		part === 'header'
			? __( 'Header preview', 'rise-landing-pages' )
			: __( 'Footer preview', 'rise-landing-pages' );
	return (
		<div className="rise-lp-chrome-preview" aria-label={ label }>
			<div className="rise-lp-chrome-preview__label">{ label }</div>
			{ state === 'error' || ! src ? (
				<p className="rise-lp-chrome-preview__message">
					{ __(
						'Preview unavailable here. Use the page Preview button to check the theme.',
						'rise-landing-pages'
					) }
				</p>
			) : null }
			{ src ? (
				<iframe
					ref={ frame }
					src={ src }
					style={ {
						height: state === 'ready' ? `${ height }px` : '0px',
						visibility: state === 'ready' ? 'visible' : 'hidden',
					} }
					title={ label }
					tabIndex={ -1 }
					aria-hidden="true"
					onLoad={ () =>
						frame.current?.contentWindow?.postMessage(
							{ type: 'rise-lp-chrome-preview-request' },
							window.location.origin
						)
					}
					onError={ () => setState( 'error' ) }
				/>
			) : null }
		</div>
	);
}

/** Place previews next to the block list without adding saved page blocks. */
function useCanvasMounts() {
	const [ mounts, setMounts ] = useState( null );
	useEffect( () => {
		let current;
		let watchedDoc;
		let canvasObserver;
		const ensure = () => {
			const canvas = document.querySelector(
				'iframe[name="editor-canvas"]'
			);
			const doc = canvas ? canvas.contentDocument : document;
			if ( ! doc ) {
				return;
			}
			if ( doc !== watchedDoc ) {
				canvasObserver?.disconnect();
				watchedDoc = doc;
				if ( doc !== document && doc.documentElement ) {
					canvasObserver = new MutationObserver( ensure );
					canvasObserver.observe( doc.documentElement, {
						childList: true,
						subtree: true,
					} );
				}
			}
			const root = doc.querySelector(
				'.editor-styles-wrapper .is-root-container'
			);
			if ( ! root?.parentElement ) {
				return;
			}
			if (
				current?.root === root &&
				current.header.isConnected &&
				current.footer.isConnected
			) {
				return;
			}
			current?.header.remove();
			current?.footer.remove();
			const header = doc.createElement( 'div' );
			const footer = doc.createElement( 'div' );
			header.className = 'rise-lp-chrome-preview-mount';
			footer.className = 'rise-lp-chrome-preview-mount';
			root.before( header );
			root.after( footer );
			current = { root, header, footer };
			setMounts( { header, footer } );
		};
		const observer = new MutationObserver( ensure );
		observer.observe( document.body, { childList: true, subtree: true } );
		ensure();
		return () => {
			observer.disconnect();
			canvasObserver?.disconnect();
			current?.header.remove();
			current?.footer.remove();
		};
	}, [] );
	return mounts;
}

export default function ChromePreview() {
	const meta = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
		[]
	);
	const mounts = useCanvasMounts();
	if ( ! mounts ) {
		return null;
	}
	const legacySiteLayout = meta._rise_landing_layout === 'site';
	const header = legacySiteLayout
		? 'site'
		: meta._rise_landing_header || 'minimal';
	const footer = legacySiteLayout
		? 'site'
		: meta._rise_landing_footer || 'landing';
	return (
		<>
			{ createPortal(
				<ChromeFrame part="header" choice={ header } />,
				mounts.header
			) }
			{ createPortal(
				<ChromeFrame part="footer" choice={ footer } />,
				mounts.footer
			) }
		</>
	);
}
