/* Resize only the settings sidebar while editing a Rise landing page. */
const STORAGE_KEY = 'rise_landing_sidebar_width';
const SIDEBAR_SELECTOR = '.interface-interface-skeleton__sidebar';
const CLASS_NAME = 'rise-lp-resizable-sidebar';
const HANDLE_ATTRIBUTE = 'data-rise-lp-sidebar-handle';
const MIN_WIDTH = 280;
const DEFAULT_WIDTH = 400;
const maxWidth = () =>
	Math.max( MIN_WIDTH, Math.min( 800, window.innerWidth / 2 ) );
const clamp = ( width ) =>
	Math.max( MIN_WIDTH, Math.min( maxWidth(), Math.round( width ) ) );

function savedWidth() {
	try {
		const value = Number( window.localStorage.getItem( STORAGE_KEY ) );
		return Number.isFinite( value ) && value >= MIN_WIDTH
			? clamp( value )
			: clamp( DEFAULT_WIDTH );
	} catch {
		return clamp( DEFAULT_WIDTH );
	}
}

function rememberWidth( width ) {
	try {
		window.localStorage.setItem( STORAGE_KEY, String( width ) );
	} catch {
		// A blocked storage API should never prevent the editor from working.
	}
}

function injectStyles() {
	if ( document.getElementById( 'rise-lp-resizable-sidebar-css' ) ) {
		return;
	}
	const style = document.createElement( 'style' );
	style.id = 'rise-lp-resizable-sidebar-css';
	style.textContent = `
		.${ CLASS_NAME } { position: relative; width: var(--rise-lp-sidebar-width, ${ DEFAULT_WIDTH }px) !important; }
		.${ CLASS_NAME } .interface-complementary-area,
		.${ CLASS_NAME } .interface-complementary-area__fill { width: 100% !important; }
		.${ CLASS_NAME } [${ HANDLE_ATTRIBUTE }] { position: absolute; inset: 0 auto 0 -4px; width: 9px; z-index: 100; cursor: col-resize; touch-action: none; }
		.${ CLASS_NAME } [${ HANDLE_ATTRIBUTE }]::after { content: ""; position: absolute; inset: 0 auto 0 4px; width: 2px; background: var(--wp-admin-theme-color, #3858e9); opacity: 0; }
		.${ CLASS_NAME } [${ HANDLE_ATTRIBUTE }]:is(:hover, :focus-visible, .is-dragging)::after { opacity: 1; }
		@media (max-width: 781px) { .${ CLASS_NAME } { width: auto !important; } .${ CLASS_NAME } [${ HANDLE_ATTRIBUTE }] { display: none; } }
	`;
	document.head.append( style );
}

function attachHandle( sidebar ) {
	if ( sidebar.querySelector( `[${ HANDLE_ATTRIBUTE }]` ) ) {
		return;
	}
	const handle = document.createElement( 'div' );
	handle.setAttribute( HANDLE_ATTRIBUTE, '' );
	handle.setAttribute( 'role', 'separator' );
	handle.setAttribute( 'aria-orientation', 'vertical' );
	handle.setAttribute( 'aria-label', 'Resize landing page settings sidebar' );
	handle.setAttribute( 'aria-valuemin', String( MIN_WIDTH ) );
	handle.setAttribute( 'aria-valuemax', String( Math.round( maxWidth() ) ) );
	handle.tabIndex = 0;

	const setWidth = ( width ) => {
		const next = clamp( width );
		sidebar.style.setProperty( '--rise-lp-sidebar-width', `${ next }px` );
		handle.setAttribute( 'aria-valuenow', String( next ) );
		return next;
	};
	setWidth( savedWidth() );

	handle.addEventListener( 'pointerdown', ( event ) => {
		if ( event.button !== 0 || window.innerWidth <= 781 ) {
			return;
		}
		event.preventDefault();
		handle.setPointerCapture( event.pointerId );
		handle.classList.add( 'is-dragging' );
		const startX = event.clientX;
		const startWidth = sidebar.getBoundingClientRect().width;
		const move = ( nextEvent ) => {
			setWidth( startWidth + startX - nextEvent.clientX );
		};
		const stop = () => {
			handle.classList.remove( 'is-dragging' );
			handle.removeEventListener( 'pointermove', move );
			handle.removeEventListener( 'pointerup', stop );
			handle.removeEventListener( 'pointercancel', stop );
			rememberWidth( Number( handle.getAttribute( 'aria-valuenow' ) ) );
		};
		handle.addEventListener( 'pointermove', move );
		handle.addEventListener( 'pointerup', stop );
		handle.addEventListener( 'pointercancel', stop );
	} );

	handle.addEventListener( 'keydown', ( event ) => {
		const current = Number( handle.getAttribute( 'aria-valuenow' ) );
		let next = current;
		if ( event.key === 'ArrowLeft' ) {
			next += 20;
		} else if ( event.key === 'ArrowRight' ) {
			next -= 20;
		} else if ( event.key === 'Home' ) {
			next = MIN_WIDTH;
		} else if ( event.key === 'End' ) {
			next = maxWidth();
		} else {
			return;
		}
		event.preventDefault();
		rememberWidth( setWidth( next ) );
	} );
	sidebar.append( handle );
}

function ensureSidebar() {
	const sidebar = document.querySelector( SIDEBAR_SELECTOR );
	if ( ! sidebar ) {
		return;
	}
	const handle = sidebar.querySelector( `[${ HANDLE_ATTRIBUTE }]` );
	// The Rise Medical starter already provides a resizer. Let it own the sidebar.
	if (
		sidebar.classList.contains( 'atx-resizable-sidebar' ) ||
		sidebar.querySelector( '[data-atx-sidebar-handle]' )
	) {
		handle?.remove();
		sidebar.classList.remove( CLASS_NAME );
		return;
	}
	if ( ! sidebar.querySelector( '.interface-complementary-area' ) ) {
		handle?.remove();
		sidebar.classList.remove( CLASS_NAME );
		return;
	}
	injectStyles();
	sidebar.classList.add( CLASS_NAME );
	attachHandle( sidebar );
}

if ( window.riseLandingEditor?.isLanding ) {
	const initialize = () => {
		let scheduled = false;
		const schedule = () => {
			if ( scheduled ) {
				return;
			}
			scheduled = true;
			window.requestAnimationFrame( () => {
				scheduled = false;
				ensureSidebar();
			} );
		};
		ensureSidebar();
		new MutationObserver( schedule ).observe( document.body, {
			childList: true,
			subtree: true,
		} );
		window.addEventListener( 'resize', schedule );
	};
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initialize, {
			once: true,
		} );
	} else {
		initialize();
	}
}
