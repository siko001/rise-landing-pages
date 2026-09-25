/* global riseLandingChromePreview */
( function () {
	'use strict';

	if ( typeof riseLandingChromePreview === 'undefined' ) {
		return;
	}

	const part =
		riseLandingChromePreview.header !== 'hidden' ? 'header' : 'footer';
	const choice = riseLandingChromePreview[ part ];
	const headerSelectors =
		'.et-l--header, #top-header, #main-header, .et_slide_in_menu_container, #masthead, .site-header, body.wp-theme-risefitness > .rf-custom-block:not(.rise-lp), #nav-banner, header, [role="banner"]';
	const footerSelectors =
		'.et-l--footer, #main-footer, #colophon, .site-footer, footer, [role="contentinfo"]';
	let nodes;
	let observer;

	function send( status ) {
		const bottom = nodes?.length
			? Math.max(
					...nodes.map(
						( node ) => node.getBoundingClientRect().bottom
					)
			  )
			: 0;
		window.top.postMessage(
			{
				type: 'rise-lp-chrome-preview',
				part,
				choice,
				status,
				height: Math.ceil( bottom ),
			},
			window.location.origin
		);
	}

	function findNodes() {
		if ( choice !== 'site' ) {
			return [
				document.querySelector(
					part === 'header' ? '.rise-lp__header' : '.rise-lp__footer'
				),
			].filter( Boolean );
		}
		const selector = part === 'header' ? headerSelectors : footerSelectors;
		const candidates = Array.from(
			document.querySelectorAll( selector )
		).filter( ( node ) => ! node.closest( '.rise-lp' ) );
		return candidates.filter(
			( node ) =>
				! candidates.some(
					( other ) => other !== node && other.contains( node )
				)
		);
	}

	function isolate() {
		const path = new Set();
		nodes.forEach( ( node ) => {
			for (
				let parent = node.parentElement;
				parent;
				parent = parent.parentElement
			) {
				path.add( parent );
			}
		} );
		const prune = ( parent ) => {
			Array.from( parent.children ).forEach( ( child ) => {
				// Divi prints its Theme Builder rules after the footer markup.
				// Keep those rules when removing the rest of the page.
				if (
					child.matches(
						'style, link[rel="stylesheet"], link[rel="preload"][as="style"]'
					)
				) {
					document.head.appendChild( child );
					return;
				}
				if ( nodes.includes( child ) ) {
					return;
				}
				if ( path.has( child ) ) {
					prune( child );
				} else {
					child.remove();
				}
			} );
		};
		prune( document.body );
		path.forEach( ( node ) => {
			node.style.minHeight = '0';
			if ( node.id === 'page-container' ) {
				node.style.paddingTop = '0';
			}
		} );
		document.body.classList.remove( 'admin-bar' );
		document.documentElement.style.setProperty(
			'margin-top',
			'0',
			'important'
		);
		document.documentElement.style.minHeight = '0';
		document.body.style.margin = '0';
		document.body.style.overflow = 'hidden';
		window.scrollTo( 0, 0 );
	}

	function render() {
		if ( ! nodes ) {
			nodes = findNodes();
			if ( ! nodes.length ) {
				send( 'error' );
				return;
			}
			isolate();
			if ( window.ResizeObserver ) {
				observer = new ResizeObserver( () => send( 'ready' ) );
				nodes.forEach( ( node ) => observer.observe( node ) );
			}
			document.fonts?.ready.then( () => send( 'ready' ) );
		}
		send( 'ready' );
	}

	window.addEventListener( 'message', ( event ) => {
		if (
			event.origin === window.location.origin &&
			event.source === window.top &&
			event.data?.type === 'rise-lp-chrome-preview-request'
		) {
			render();
		}
	} );
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', render, { once: true } );
	} else {
		render();
	}
} )();
