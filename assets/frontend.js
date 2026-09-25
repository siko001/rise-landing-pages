/** Accessible, progressively enhanced Rise FAQ accordions. */
( function () {
	'use strict';

	const reducedMotion = window.matchMedia
		? window.matchMedia( '(prefers-reduced-motion: reduce)' )
		: { matches: false };
	const tileStarts = [
		'polygon(0% 0%, 0% 0%, 0% 0%, 0% 0%)',
		'polygon(33% 0%, 33% 0%, 33% 0%, 33% 0%)',
		'polygon(66% 0%, 66% 0%, 66% 0%, 66% 0%)',
		'polygon(0% 33%, 0% 33%, 0% 33%, 0% 33%)',
		'polygon(33% 33%, 33% 33%, 33% 33%, 33% 33%)',
		'polygon(66% 33%, 66% 33%, 66% 33%, 66% 33%)',
		'polygon(0% 66%, 0% 66%, 0% 66%, 0% 66%)',
		'polygon(33% 66%, 33% 66%, 33% 66%, 33% 66%)',
		'polygon(66% 66%, 66% 66%, 66% 66%, 66% 66%)',
	];
	const tileEnds = [
		'polygon(0% 0%, 33.5% 0%, 33.5% 33%, 0% 33.5%)',
		'polygon(33% 0%, 66.5% 0%, 66.5% 33%, 33% 33.5%)',
		'polygon(66% 0%, 100% 0%, 100% 33%, 66% 33.5%)',
		'polygon(0% 33%, 33.5% 33%, 33.5% 66%, 0% 66.5%)',
		'polygon(33% 33%, 66.5% 33%, 66.5% 66%, 33% 66.5%)',
		'polygon(66% 33%, 100% 33%, 100% 66%, 66% 66.5%)',
		'polygon(0% 66%, 33.5% 66%, 33.5% 100%, 0% 100%)',
		'polygon(33% 66%, 66.5% 66%, 66.5% 100%, 33% 100%)',
		'polygon(66% 66%, 100% 66%, 100% 100%, 66% 100%)',
	];
	// The five diagonal groups follow the Rise Fitness Classes image reveal.
	const tileDelays = [ 0, 125, 250, 225, 350, 375, 450, 475, 500 ];
	const imageMotionStates = new WeakMap();
	const imageMedia = new Set();
	let imageMotionObserver = null;

	function stopImageMotion( media ) {
		const state = imageMotionStates.get( media );
		if ( ! state ) {
			return;
		}
		state.token += 1;
		state.animations.forEach( ( animation ) => animation.cancel() );
		state.animations = [];
		state.tiles.forEach( ( tile ) => tile.remove() );
		state.tiles = [];
		if ( state.frame ) {
			state.frame.classList.remove( 'rise-lp__image-motion-playing' );
			state.frame.classList.add( 'rise-lp__image-motion-complete' );
		}
		state.frame = null;
	}

	function playImageMotion( media, frame ) {
		if ( ! media || ! frame ) {
			return;
		}
		if ( imageMotionObserver ) {
			imageMotionObserver.unobserve( media );
		}
		media.dataset.riseImageActivated = 'true';
		stopImageMotion( media );
		const state = imageMotionStates.get( media ) || {
			token: 0,
			frame: null,
			animations: [],
			tiles: [],
		};
		imageMotionStates.set( media, state );
		state.token += 1;
		const token = state.token;
		state.frame = frame;
		const image = frame.querySelector( '.rise-lp__faq-image' );
		if ( reducedMotion.matches || ! image || ! frame.animate ) {
			frame.classList.add( 'rise-lp__image-motion-complete' );
			return;
		}
		frame.classList.remove( 'rise-lp__image-motion-complete' );
		frame.classList.add( 'rise-lp__image-motion-playing' );
		const finish = function () {
			if ( state.token !== token ) {
				return;
			}
			frame.classList.add( 'rise-lp__image-motion-complete' );
			frame.classList.remove( 'rise-lp__image-motion-playing' );
			state.animations.forEach( ( animation ) => animation.cancel() );
			state.animations = [];
			state.tiles.forEach( ( tile ) => tile.remove() );
			state.tiles = [];
		};
		const start = function () {
			if ( state.token !== token ) {
				return;
			}
			try {
				if ( media.dataset.riseImageAnimation !== 'tiles' ) {
					state.animations = [
						frame.animate( [ { opacity: 0 }, { opacity: 1 } ], {
							duration: 500,
							easing: 'ease-out',
							fill: 'both',
						} ),
					];
				} else {
					const imageStyle = window.getComputedStyle( image );
					state.animations = tileStarts.map( ( clipPath, index ) => {
						const tile = image.cloneNode( false );
						tile.className = 'rise-lp__faq-image-tile';
						tile.removeAttribute( 'id' );
						tile.alt = '';
						tile.setAttribute( 'aria-hidden', 'true' );
						tile.loading = 'eager';
						tile.style.objectFit = imageStyle.objectFit;
						tile.style.objectPosition = imageStyle.objectPosition;
						frame.appendChild( tile );
						state.tiles.push( tile );
						return tile.animate(
							[ { clipPath }, { clipPath: tileEnds[ index ] } ],
							{
								duration: 500,
								delay: tileDelays[ index ],
								easing: 'cubic-bezier(0, 0, 0.2, 1)',
								fill: 'both',
							}
						);
					} );
				}
				Promise.all(
					state.animations.map( ( animation ) => animation.finished )
				).then( finish, finish );
			} catch ( error ) {
				finish();
			}
		};
		if ( image.complete ) {
			if ( image.naturalWidth > 0 ) {
				start();
			} else {
				finish();
			}
			return;
		}
		// Hidden accordion images may still be lazy when selected. A rejected
		// decode does not mean the image failed; wait for its load event.
		let imageReady = false;
		const cleanupImageEvents = function () {
			image.removeEventListener( 'load', onImageLoad );
			image.removeEventListener( 'error', onImageError );
		};
		const onImageLoad = function () {
			if ( imageReady ) {
				return;
			}
			imageReady = true;
			cleanupImageEvents();
			start();
		};
		const onImageError = function () {
			if ( imageReady ) {
				return;
			}
			imageReady = true;
			cleanupImageEvents();
			finish();
		};
		image.addEventListener( 'load', onImageLoad );
		image.addEventListener( 'error', onImageError );
		image.loading = 'eager';
		if ( image.decode ) {
			image.decode().then( onImageLoad, function () {
				if ( image.complete ) {
					if ( image.naturalWidth > 0 ) {
						onImageLoad();
					} else {
						onImageError();
					}
				}
			} );
		}
	}

	function initializeImageMotion() {
		const mediaElements = document.querySelectorAll(
			'.rise-lp .rise-lp__faq-media[data-rise-image-animation]'
		);
		if (
			! imageMotionObserver &&
			! reducedMotion.matches &&
			'IntersectionObserver' in window
		) {
			imageMotionObserver = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( ! entry.isIntersecting ) {
							return;
						}
						const media = entry.target;
						imageMotionObserver.unobserve( media );
						if ( media.dataset.riseImageActivated === 'true' ) {
							return;
						}
						const frame = Array.from(
							media.querySelectorAll(
								'.rise-lp__faq-media-frame'
							)
						).find( ( candidate ) => ! candidate.hidden );
						playImageMotion( media, frame );
					} );
				},
				{ rootMargin: '0px 0px -8% 0px', threshold: 0.1 }
			);
		}
		mediaElements.forEach( function ( media ) {
			if ( media.dataset.riseImageMotionReady === 'true' ) {
				return;
			}
			media.dataset.riseImageMotionReady = 'true';
			imageMedia.add( media );
			if ( reducedMotion.matches || ! imageMotionObserver ) {
				media
					.querySelectorAll( '.rise-lp__faq-media-frame' )
					.forEach( ( frame ) =>
						frame.classList.add( 'rise-lp__image-motion-complete' )
					);
			} else {
				imageMotionObserver.observe( media );
			}
		} );
	}

	function onImageMotionPreferenceChange() {
		if ( ! reducedMotion.matches ) {
			return;
		}
		if ( imageMotionObserver ) {
			imageMotionObserver.disconnect();
		}
		imageMedia.forEach( function ( media ) {
			stopImageMotion( media );
			media
				.querySelectorAll( '.rise-lp__faq-media-frame' )
				.forEach( ( frame ) =>
					frame.classList.add( 'rise-lp__image-motion-complete' )
				);
		} );
	}
	if ( reducedMotion.addEventListener ) {
		reducedMotion.addEventListener(
			'change',
			onImageMotionPreferenceChange
		);
	} else if ( reducedMotion.addListener ) {
		reducedMotion.addListener( onImageMotionPreferenceChange );
	}

	function initialize() {
		document
			.querySelectorAll( '.rise-lp [data-rise-faq]' )
			.forEach( function ( list ) {
				if ( list.dataset.riseEnhanced === 'true' ) {
					return;
				}
				const singleOpen = list.dataset.singleOpen === 'true';
				const firstOpen = list.dataset.firstOpen === 'true';
				const items = [];
				const layout = list.closest( '.rise-lp__faq-layout' );
				const media = layout
					? layout.querySelector( '.rise-lp__faq-media' )
					: null;
				const mediaFrames = layout
					? Array.from(
							layout.querySelectorAll(
								'[data-rise-faq-media-index]'
							)
					  )
					: [];

				list.querySelectorAll( '.rise-lp__faq-question' ).forEach(
					function ( button ) {
						const answer = document.getElementById(
							button.getAttribute( 'aria-controls' )
						);
						if ( answer && list.contains( answer ) ) {
							items.push( {
								button,
								answer,
								animation: null,
								index: items.length,
							} );
						}
					}
				);

				function showMedia( index, animate ) {
					const next =
						mediaFrames.find(
							( frame ) =>
								frame.dataset.riseFaqMediaIndex ===
								String( index )
						) ||
						mediaFrames.find(
							( frame ) =>
								frame.dataset.riseFaqMediaIndex === '-1'
						);
					if ( ! next ) {
						return;
					}
					if ( next.hidden ) {
						mediaFrames.forEach( ( frame ) => {
							frame.hidden = frame !== next;
						} );
					}
					if ( animate ) {
						playImageMotion( media, next );
					}
				}

				function settle( item, open ) {
					item.answer.hidden = ! open;
					item.answer.style.height = '';
					item.answer.style.overflow = '';
				}

				function setOpen( item, open, animate ) {
					const answer = item.answer;
					const fromHeight = answer.hidden
						? 0
						: answer.getBoundingClientRect().height;
					if ( item.animation ) {
						item.animation.onfinish = null;
						item.animation.cancel();
						item.animation = null;
					}
					item.button.setAttribute(
						'aria-expanded',
						open ? 'true' : 'false'
					);
					if ( open ) {
						answer.removeAttribute( 'inert' );
						answer.removeAttribute( 'aria-hidden' );
						showMedia( item.index, animate );
					} else {
						// Closing content leaves both the tab order and accessibility tree immediately.
						if (
							answer.contains(
								answer.ownerDocument.activeElement
							)
						) {
							item.button.focus();
						}
						answer.setAttribute( 'inert', '' );
						answer.setAttribute( 'aria-hidden', 'true' );
					}
					// Older browsers without inert close immediately so links cannot retain focus.
					if (
						! animate ||
						reducedMotion.matches ||
						! answer.animate ||
						( ! open && ! ( 'inert' in answer ) )
					) {
						settle( item, open );
						return;
					}

					answer.hidden = false;
					answer.style.height = 'auto';
					const toHeight = open
						? answer.getBoundingClientRect().height
						: 0;
					if ( fromHeight === toHeight ) {
						settle( item, open );
						return;
					}
					answer.style.height = fromHeight + 'px';
					answer.style.overflow = 'hidden';
					try {
						const animation = answer.animate(
							[
								{ height: fromHeight + 'px' },
								{ height: toHeight + 'px' },
							],
							{
								duration: 240,
								easing: 'cubic-bezier(0.2, 0, 0, 1)',
								fill: 'both',
							}
						);
						item.animation = animation;
						animation.onfinish = function () {
							if ( item.animation !== animation ) {
								return;
							}
							item.animation = null;
							animation.cancel();
							settle( item, open );
						};
					} catch ( error ) {
						settle( item, open );
					}
				}

				items.forEach( function ( item, index ) {
					// Server-rendered answers remain readable when JavaScript is unavailable.
					setOpen( item, firstOpen && index === 0, false );
					item.button.addEventListener( 'click', function () {
						const open =
							item.button.getAttribute( 'aria-expanded' ) !==
							'true';
						if ( open && singleOpen ) {
							items.forEach( function ( sibling ) {
								if (
									sibling !== item &&
									sibling.button.getAttribute(
										'aria-expanded'
									) === 'true'
								) {
									setOpen( sibling, false, true );
								}
							} );
						}
						setOpen( item, open, true );
					} );
					item.button.addEventListener(
						'keydown',
						function ( event ) {
							let target;
							if ( event.key === 'ArrowDown' ) {
								target = ( index + 1 ) % items.length;
							} else if ( event.key === 'ArrowUp' ) {
								target =
									( index - 1 + items.length ) % items.length;
							} else if ( event.key === 'Home' ) {
								target = 0;
							} else if ( event.key === 'End' ) {
								target = items.length - 1;
							} else {
								return;
							}
							event.preventDefault();
							items[ target ].button.focus();
						}
					);
				} );
				list.dataset.riseEnhanced = 'true';
			} );
	}

	/** Reveal each section's copy and cards as they enter the viewport. */
	function initializeReveals() {
		if ( reducedMotion.matches ) {
			document.documentElement.classList.remove( 'rise-lp-motion-ready' );
			window.clearTimeout( window.riseLandingMotionFallback );
			return;
		}
		if (
			! document.documentElement.classList.contains(
				'rise-lp-motion-ready'
			)
		) {
			return;
		}
		if (
			! ( 'IntersectionObserver' in window ) ||
			! document.documentElement.animate
		) {
			document.documentElement.classList.remove( 'rise-lp-motion-ready' );
			window.clearTimeout( window.riseLandingMotionFallback );
			return;
		}

		const targets = new Map();
		const activeAnimations = new Map();
		const observer = new IntersectionObserver(
			function ( entries ) {
				entries
					.filter( function ( entry ) {
						return entry.isIntersecting;
					} )
					.sort( function ( a, b ) {
						return (
							a.boundingClientRect.top -
								b.boundingClientRect.top ||
							a.boundingClientRect.left -
								b.boundingClientRect.left
						);
					} )
					.forEach( function ( entry, index ) {
						observer.unobserve( entry.target );
						const options = targets.get( entry.target );
						if ( ! options || reducedMotion.matches ) {
							return;
						}
						entry.target.classList.add( 'rise-lp__revealed' );
						const distance = options.kind === 'heading' ? 34 : 24;
						const startTransform =
							options.kind === 'card'
								? 'translate3d(0, 30px, 0) scale(0.97)'
								: 'translate3d(0, ' + distance + 'px, 0)';
						try {
							const animation = entry.target.animate(
								[
									{ opacity: 0, transform: startTransform },
									{ opacity: 1, transform: 'none' },
								],
								{
									duration:
										options.kind === 'media' ? 850 : 650,
									delay: index * 80,
									easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
									fill: 'both',
								}
							);
							activeAnimations.set( entry.target, animation );
							animation.onfinish = function () {
								activeAnimations.delete( entry.target );
								animation.cancel();
							};
						} catch ( error ) {
							// The server-rendered content remains visible if animation fails.
						}
					} );
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.1 }
		);

		function observe( element, kind ) {
			if ( targets.has( element ) ) {
				return;
			}
			targets.set( element, { kind } );
			observer.observe( element );
		}

		// Keyboard focus must never land on content waiting to reveal.
		document.addEventListener(
			'focusin',
			function ( event ) {
				let element = event.target;
				while ( element && element !== document.documentElement ) {
					if ( targets.has( element ) ) {
						observer.unobserve( element );
						element.classList.add( 'rise-lp__revealed' );
						const animation = activeAnimations.get( element );
						if ( animation ) {
							animation.cancel();
							activeAnimations.delete( element );
						}
					}
					element = element.parentElement;
				}
			},
			true
		);

		document
			.querySelectorAll( '.rise-lp .rise-lp__section' )
			.forEach( function ( section ) {
				section
					.querySelectorAll(
						'.rise-lp__section-header > .rise-lp__eyebrow, .rise-lp__section-header > .rise-lp__heading, .rise-lp__section-header > .rise-lp__intro, .rise-lp__hero-content > .rise-lp__eyebrow, .rise-lp__hero-content > .rise-lp__heading, .rise-lp__hero-content > .rise-lp__intro, .rise-lp__hero-content > .rise-lp__reassurance, .rise-lp__hero-content > .rise-lp__actions, .rise-lp__cta-copy > .rise-lp__eyebrow, .rise-lp__cta-copy > .rise-lp__heading, .rise-lp__cta-copy > .rise-lp__intro, .rise-lp__cta-actions'
					)
					.forEach( function ( element ) {
						observe(
							element,
							element.classList.contains( 'rise-lp__heading' )
								? 'heading'
								: 'text'
						);
					} );

				section
					.querySelectorAll(
						'.rise-lp__service-card, .rise-lp__process-step, .rise-lp__benefit, .rise-lp__faq-item'
					)
					.forEach( function ( element ) {
						if (
							element.closest( '.rise-lp__process-grid--circle' )
						) {
							return;
						}
						observe( element, 'card' );
					} );

				if ( section.classList.contains( 'rise-lp__hero' ) ) {
					const media = section.querySelector(
						'.rise-lp__hero-media'
					);
					if ( media ) {
						observe( media, 'media' );
					}
				}
			} );

		window.clearTimeout( window.riseLandingMotionFallback );
		if ( ! targets.size ) {
			document.documentElement.classList.remove( 'rise-lp-motion-ready' );
		}

		function onMotionChange() {
			if ( reducedMotion.matches ) {
				document.documentElement.classList.remove(
					'rise-lp-motion-ready'
				);
				observer.disconnect();
				activeAnimations.forEach( function ( animation ) {
					animation.cancel();
				} );
				activeAnimations.clear();
			}
		}
		if ( reducedMotion.addEventListener ) {
			reducedMotion.addEventListener( 'change', onMotionChange );
		} else if ( reducedMotion.addListener ) {
			reducedMotion.addListener( onMotionChange );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initialize();
			initializeImageMotion();
			initializeReveals();
		} );
	} else {
		initialize();
		initializeImageMotion();
		initializeReveals();
	}
} )();
