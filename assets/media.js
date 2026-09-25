/** Native media and scroll-snap sliders; no frontend framework or global selectors. */
( () => {
	const initialize = () => {
		const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );
		document
			.querySelectorAll( '.rise-lp [data-rise-video]' )
			.forEach( ( video ) => {
				const toggle = () => {
					if ( video.paused ) {
						video.play().catch( () => {} );
					} else {
						video.pause();
					}
				};
				if ( video.hasAttribute( 'data-rise-click-play' ) ) {
					video.addEventListener( 'click', toggle );
					video.addEventListener( 'keydown', ( event ) => {
						if ( event.key === ' ' || event.key === 'Enter' ) {
							event.preventDefault();
							toggle();
						}
					} );
				}
				if ( video.dataset.autoplay === 'true' && ! reduced.matches ) {
					video.muted = true;
					video.play().catch( () => {} );
				}
				reduced.addEventListener( 'change', () => {
					if ( reduced.matches ) {
						video.pause();
					}
				} );
			} );
		document
			.querySelectorAll( '.rise-lp [data-rise-slider]' )
			.forEach( ( track ) => {
				const controls = track.nextElementSibling;
				if ( ! controls?.matches( '[data-slider-controls]' ) ) {
					return;
				}
				const previous = controls.querySelector(
					'[data-slide-previous]'
				);
				const next = controls.querySelector( '[data-slide-next]' );
				const status = controls.querySelector(
					'.rise-lp__slider-status'
				);
				const cards = Array.from( track.children );
				const rtl = getComputedStyle( track ).direction === 'rtl';
				const loop = track.dataset.loop === 'true';
				const autoplay = track.dataset.autoplay === 'true';
				const interval = Math.max(
					2,
					Math.min(
						15,
						Number( track.dataset.autoplayInterval ) || 5
					)
				);
				const update = () => {
					const position = Math.abs( track.scrollLeft );
					const max = track.scrollWidth - track.clientWidth;
					controls.hidden = max < 2;
					previous.disabled = ! loop && position < 2;
					next.disabled = ! loop && position >= max - 2;
					const rect = track.getBoundingClientRect();
					const visible = cards
						.map( ( card, index ) => ( {
							rect: card.getBoundingClientRect(),
							index,
						} ) )
						.filter(
							( card ) =>
								card.rect.left < rect.right - 8 &&
								card.rect.right > rect.left + 8
						);
					if ( status && visible.length ) {
						status.textContent = `${ visible[ 0 ].index + 1 }–${
							visible[ visible.length - 1 ].index + 1
						} / ${ cards.length }`;
					}
				};
				const move = ( direction ) => {
					const max = track.scrollWidth - track.clientWidth;
					if ( max < 2 ) {
						return;
					}
					const position = Math.abs( track.scrollLeft );
					if ( loop && direction > 0 && position >= max - 2 ) {
						track.scrollTo( {
							left: 0,
							behavior: reduced.matches ? 'instant' : 'smooth',
						} );
						return;
					}
					if ( loop && direction < 0 && position < 2 ) {
						track.scrollTo( {
							left: max * ( rtl ? -1 : 1 ),
							behavior: reduced.matches ? 'instant' : 'smooth',
						} );
						return;
					}
					const step =
						( cards[ 0 ]?.getBoundingClientRect().width ||
							track.clientWidth ) +
						( parseFloat( getComputedStyle( track ).gap ) || 24 );
					track.scrollBy( {
						left: step * direction * ( rtl ? -1 : 1 ),
						behavior: reduced.matches ? 'instant' : 'smooth',
					} );
				};
				previous.addEventListener( 'click', () => move( -1 ) );
				next.addEventListener( 'click', () => move( 1 ) );
				track.addEventListener( 'scroll', update, { passive: true } );
				track.addEventListener( 'keydown', ( event ) => {
					if ( event.target !== track ) {
						return;
					}
					if (
						event.key === 'ArrowRight' ||
						event.key === 'ArrowLeft'
					) {
						event.preventDefault();
						move(
							( event.key === 'ArrowRight' ? 1 : -1 ) *
								( rtl ? -1 : 1 )
						);
					}
				} );
				new ResizeObserver( update ).observe( track );
				update();
				if ( autoplay ) {
					window.setInterval( () => {
						if (
							reduced.matches ||
							document.hidden ||
							track.matches( ':hover' ) ||
							controls.matches( ':hover' ) ||
							track.contains(
								track.ownerDocument.activeElement
							) ||
							controls.contains(
								controls.ownerDocument.activeElement
							)
						) {
							return;
						}
						move( 1 );
					}, interval * 1000 );
				}
			} );
	};
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initialize );
	} else {
		initialize();
	}
} )();
