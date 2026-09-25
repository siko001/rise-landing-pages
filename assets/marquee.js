/** Seamless separator ticker: measure the browser's actual flex layout, then wrap. */
( function () {
	'use strict';

	const reducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	);
	const instances = new WeakMap();

	function init( element ) {
		if ( instances.has( element ) ) {
			return;
		}
		const track = element.querySelector( '.rise-lp__separator-track' );
		if ( ! track || ! track.children.length ) {
			return;
		}
		const motion = [ 'scroll', 'none' ].includes( element.dataset.motion )
			? element.dataset.motion
			: 'auto';
		const originals = Array.from( track.children );
		let distance = 0;
		let offset = 0;
		let frame = 0;
		let scrollFrame = 0;
		let lastTime = 0;
		let visible = true;
		let hovering = false;

		function stop() {
			window.cancelAnimationFrame( frame );
			window.cancelAnimationFrame( scrollFrame );
			frame = 0;
			scrollFrame = 0;
			lastTime = 0;
		}

		function syncScroll() {
			scrollFrame = 0;
			if (
				! visible ||
				hovering ||
				reducedMotion.matches ||
				distance <= 0
			) {
				return;
			}
			const speed = Math.max(
				10,
				Math.min( 300, Number( element.dataset.scrollSpeed ) || 100 )
			);
			const progress =
				( ( Math.max( 0, window.scrollY ) * speed ) / 100 ) % distance;
			offset =
				element.dataset.direction === 'right'
					? -distance + progress
					: -progress;
			track.style.transform = `translate3d(${ offset }px, 0, 0)`;
		}

		function queueScroll() {
			if (
				! scrollFrame &&
				visible &&
				! hovering &&
				! reducedMotion.matches &&
				distance > 0
			) {
				scrollFrame = window.requestAnimationFrame( syncScroll );
			}
		}

		function tick( time ) {
			if ( ! visible || hovering || reducedMotion.matches ) {
				stop();
				return;
			}
			const elapsed = lastTime
				? Math.min( time - lastTime, 100 ) / 1000
				: 0;
			lastTime = time;
			const speed = Math.max(
				10,
				Math.min( 300, Number( element.dataset.speed ) || 80 )
			);
			const right = element.dataset.direction === 'right';
			offset += speed * elapsed * ( right ? 1 : -1 );
			while ( right && offset >= 0 ) {
				offset -= distance;
			}
			while ( ! right && offset <= -distance ) {
				offset += distance;
			}
			track.style.transform = `translate3d(${ offset }px, 0, 0)`;
			frame = window.requestAnimationFrame( tick );
		}

		function start() {
			if ( motion === 'scroll' ) {
				queueScroll();
				return;
			}
			if (
				motion === 'auto' &&
				! frame &&
				visible &&
				! hovering &&
				! reducedMotion.matches &&
				distance > 0
			) {
				frame = window.requestAnimationFrame( tick );
			}
		}

		function appendSet() {
			originals.forEach( ( item ) => {
				const clone = item.cloneNode( true );
				clone.setAttribute( 'aria-hidden', 'true' );
				clone.classList.add( 'rise-lp__separator-clone' );
				track.appendChild( clone );
			} );
		}

		function build() {
			stop();
			track.style.transform = '';
			track
				.querySelectorAll( '.rise-lp__separator-clone' )
				.forEach( ( item ) => item.remove() );
			appendSet();
			const firstClone = track.querySelector(
				'.rise-lp__separator-clone'
			);
			distance = firstClone
				? firstClone.getBoundingClientRect().left -
				  originals[ 0 ].getBoundingClientRect().left
				: 0;
			if ( distance <= 0 ) {
				return;
			}
			const needed = Math.max(
				3,
				Math.ceil( element.clientWidth / distance ) + 2
			);
			for ( let index = 2; index < needed; index++ ) {
				appendSet();
			}
			if ( motion === 'auto' && ! reducedMotion.matches ) {
				offset = element.dataset.direction === 'right' ? -distance : 0;
				track.style.transform = `translate3d(${ offset }px, 0, 0)`;
			}
			start();
		}

		const resize = new ResizeObserver( () =>
			window.requestAnimationFrame( build )
		);
		resize.observe( element );
		if ( document.fonts?.ready ) {
			document.fonts.ready.then( build );
		}
		const intersection = new IntersectionObserver( ( entries ) => {
			visible = entries[ 0 ].isIntersecting;
			if ( visible ) {
				start();
			} else {
				stop();
			}
		} );
		intersection.observe( element );
		if ( motion === 'scroll' ) {
			window.addEventListener( 'scroll', queueScroll, { passive: true } );
		}
		if ( element.dataset.pauseOnHover === 'true' ) {
			element.addEventListener( 'mouseenter', () => {
				hovering = true;
				stop();
			} );
			element.addEventListener( 'mouseleave', () => {
				hovering = false;
				start();
			} );
			element.addEventListener( 'focusin', () => {
				hovering = true;
				stop();
			} );
			element.addEventListener( 'focusout', () => {
				hovering = false;
				start();
			} );
		}
		reducedMotion.addEventListener( 'change', build );
		track.querySelectorAll( 'img' ).forEach( ( image ) => {
			if ( ! image.complete ) {
				image.addEventListener( 'load', build, { once: true } );
			}
		} );
		instances.set( element, { resize, intersection } );
		window.requestAnimationFrame( build );
	}

	document.querySelectorAll( '[data-rise-marquee]' ).forEach( init );
} )();
