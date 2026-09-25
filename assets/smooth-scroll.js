import Lenis from 'lenis';

/** Give Rise pages one smooth scroller without duplicating a theme's Lenis. */
( function () {
	const root = document.documentElement;
	const reducedMotion = window.matchMedia
		? window.matchMedia( '(prefers-reduced-motion: reduce)' )
		: null;
	let instance = null;

	function themeHasScroller() {
		return (
			root.classList.contains( 'lenis' ) ||
			( window.lenis && typeof window.lenis.scrollTo === 'function' )
		);
	}

	function sync() {
		if ( reducedMotion?.matches ) {
			if ( instance ) {
				instance.destroy();
				instance = null;
				delete window.riseLandingLenis;
			}
			return;
		}
		if ( instance || themeHasScroller() ) {
			return;
		}
		instance = new Lenis( {
			autoRaf: true,
			autoToggle: true,
			anchors: true,
			allowNestedScroll: true,
			stopInertiaOnNavigate: true,
		} );
		window.riseLandingLenis = instance;
	}

	function initialize() {
		window.requestAnimationFrame( sync );
	}

	if ( document.readyState === 'complete' ) {
		initialize();
	} else {
		window.addEventListener( 'load', initialize, { once: true } );
	}
	if ( reducedMotion?.addEventListener ) {
		reducedMotion.addEventListener( 'change', sync );
	}
} )();
