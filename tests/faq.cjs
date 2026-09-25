/** Real-browser regression checks for the progressively enhanced FAQ. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( 'playwright' );

const root = path.resolve( __dirname, '..' );
const script = fs.readFileSync( path.join( root, 'assets/frontend.js' ), 'utf8' );
const css = fs.readFileSync( path.join( root, 'assets/frontend.css' ), 'utf8' );
const chrome = process.env.RISE_TEST_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

function fixture() {
	const lists = [ [ 'single-first', true, true ], [ 'multi', false, false ], [ 'single', true, false ], [ 'multi-first', false, true ] ];
	return '<!doctype html><html><head><style>' + css + '</style></head><body><div class="rise-lp">' + lists.map( ( [ id, single, first ] ) =>
		`<div id="${ id }" class="rise-lp__faq-list" data-rise-faq data-single-open="${ single }" data-first-open="${ first }">` +
		[ 0, 1, 2 ].map( ( index ) => `<div class="rise-lp__faq-item"><h3 class="rise-lp__faq-heading"><button type="button" class="rise-lp__faq-question" id="${ id }-q${ index }" aria-expanded="true" aria-controls="${ id }-a${ index }">Question ${ index + 1 }<span class="rise-lp__faq-indicator"></span></button></h3><div id="${ id }-a${ index }" class="rise-lp__faq-answer" aria-labelledby="${ id }-q${ index }"><div class="rise-lp__faq-answer-inner"><p>An answer with enough content to give the animation a measurable height.</p><p><a href="#${ id }-q${ index }">Read more</a></p></div></div></div>` ).join( '' ) + '</div>'
	).join( '' ) + '</div></body></html>';
}

function mediaFixture() {
	const questions = [ 0, 1 ].map( ( index ) => `<div class="rise-lp__faq-item"><h3 class="rise-lp__faq-heading"><button type="button" class="rise-lp__faq-question" id="media-q${ index }" aria-expanded="true" aria-controls="media-a${ index }"><span class="rise-lp__faq-question-label">Question ${ index + 1 }<svg class="rise-lp__faq-dashes" viewBox="0 0 150 32" aria-hidden="true"><path d="M9 1h2l-9 30h-2z" style="--rise-dash-index:0"/><path d="M25 1h3l-9 30h-3z" style="--rise-dash-index:1"/></svg></span><span class="rise-lp__faq-indicator"></span></button></h3><div id="media-a${ index }" class="rise-lp__faq-answer" aria-labelledby="media-q${ index }"><div class="rise-lp__faq-answer-inner"><p>Answer ${ index + 1 }</p><ul class="rise-lp__faq-pills"><li>Label</li></ul></div></div></div>` ).join( '' );
	const image = 'data:image/svg+xml,' + encodeURIComponent( '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="50"><rect width="40" height="50" fill="red"/></svg>' );
	return '<!doctype html><html><head><style>' + css + '</style></head><body><div class="rise-lp" style="--rise-background:#000;--rise-text:#fff;--rise-muted:#bbb;--rise-primary:#ec1c2b">' +
		`<section id="dark-faq" class="rise-lp__section rise-lp__faq rise-lp__section--bg-none"><div class="rise-lp__container"><div class="rise-lp__faq-layout rise-lp__faq-layout--media rise-lp__faq-layout--media-left"><div class="rise-lp__faq-media" data-rise-image-animation="tiles"><figure class="rise-lp__faq-media-frame" data-rise-faq-media-index="0"><img class="rise-lp__faq-image" src="${ image }" alt="First"/></figure><figure class="rise-lp__faq-media-frame" data-rise-faq-media-index="1" hidden><img class="rise-lp__faq-image" src="${ image }" alt="Second"/></figure></div><div class="rise-lp__faq-content"><div class="rise-lp__section-header"><h2 class="rise-lp__heading">FAQ</h2></div><div class="rise-lp__faq-list" data-rise-faq data-single-open="true" data-first-open="true">` + questions + '</div></div></div></div></section>' +
		'<section id="white-faq" class="rise-lp__section rise-lp__faq rise-lp__section--bg-white"><div class="rise-lp__container"><div class="rise-lp__faq-layout rise-lp__faq-layout--text"><div class="rise-lp__faq-content"><div class="rise-lp__section-header"><h2 class="rise-lp__heading">FAQ</h2></div><div class="rise-lp__faq-list"><div class="rise-lp__faq-item">White</div></div></div></div></div></section>' +
		`<section id="fade-faq" class="rise-lp__section rise-lp__faq rise-lp__section--bg-none"><div class="rise-lp__container"><div class="rise-lp__faq-layout rise-lp__faq-layout--media rise-lp__faq-layout--media-left"><div class="rise-lp__faq-media" data-rise-image-animation="fade"><figure class="rise-lp__faq-media-frame"><img class="rise-lp__faq-image" src="${ image }" alt="Fade"/></figure></div><div class="rise-lp__faq-content"><div class="rise-lp__section-header"><h2 class="rise-lp__heading">Fade</h2></div></div></div></div></section></div></body></html>`;
}

async function state( page, id ) {
	return page.locator( `#${ id }` ).evaluate( ( list ) => Array.from( list.querySelectorAll( '.rise-lp__faq-question' ) ).map( ( button ) => {
		const answer = document.getElementById( button.getAttribute( 'aria-controls' ) );
		return { expanded: button.getAttribute( 'aria-expanded' ) === 'true', hidden: answer.hidden, inert: answer.hasAttribute( 'inert' ), height: answer.style.height, animating: answer.getAnimations().length > 0 };
	} ) );
}

async function settled( page ) {
	await page.waitForFunction( () => ! Array.from( document.querySelectorAll( '.rise-lp__faq-answer' ) ).some( ( element ) => element.getAnimations().length ) );
}

async function main() {
	const browser = await chromium.launch( { headless: true, ...( fs.existsSync( chrome ) ? { executablePath: chrome } : {} ) } );
	try {
		const page = await browser.newPage();
		await page.setContent( fixture() );
		assert.equal( await page.locator( '.rise-lp__faq-answer:visible' ).count(), 12, 'Every server answer is available before enhancement.' );
		await page.addScriptTag( { content: script } );
		assert.deepEqual( ( await state( page, 'single-first' ) ).map( ( item ) => item.expanded ), [ true, false, false ], 'First-open initializes a single accordion.' );
		assert.deepEqual( ( await state( page, 'multi-first' ) ).map( ( item ) => item.expanded ), [ true, false, false ], 'First-open initializes a multiple accordion.' );
		assert.ok( ( await state( page, 'multi' ) ).every( ( item ) => item.hidden && item.inert ), 'First-open false starts every answer closed.' );
		assert.ok( ( await state( page, 'single' ) ).every( ( item ) => item.hidden && item.inert ), 'Each accordion reads its own preferences.' );

		await page.locator( '#single-first-q1' ).click();
		let single = await state( page, 'single-first' );
		assert.deepEqual( single.map( ( item ) => item.expanded ), [ false, true, false ], 'Opening one question immediately updates sibling ARIA.' );
		assert.equal( single[ 0 ].inert, true, 'Closing content immediately leaves the tab order.' );
		await settled( page );
		single = await state( page, 'single-first' );
		assert.equal( single[ 0 ].hidden, true, 'The closing answer is hidden on completion.' );
		assert.equal( single[ 1 ].hidden, false, 'The open answer remains visible.' );
		assert.equal( single[ 1 ].height, '', 'Natural height is restored after opening.' );

		await page.locator( '#multi-q0' ).click();
		await page.locator( '#multi-q1' ).click();
		await settled( page );
		assert.deepEqual( ( await state( page, 'multi' ) ).map( ( item ) => item.expanded ), [ true, true, false ], 'Multiple mode preserves sibling answers.' );
		assert.deepEqual( ( await state( page, 'single-first' ) ).map( ( item ) => item.expanded ), [ false, true, false ], 'An accordion never changes another instance.' );

		await page.evaluate( async () => {
			const button = document.getElementById( 'multi-q0' );
			button.click();
			await new Promise( ( resolve ) => setTimeout( resolve, 40 ) );
			button.click();
			await new Promise( ( resolve ) => setTimeout( resolve, 40 ) );
			button.click();
		} );
		await settled( page );
		let rapid = ( await state( page, 'multi' ) )[ 0 ];
		assert.deepEqual( rapid, { expanded: false, hidden: true, inert: true, height: '', animating: false }, 'Rapid reversal finishes closed without stale callbacks.' );
		await page.evaluate( async () => {
			const button = document.getElementById( 'multi-q0' );
			button.click();
			await new Promise( ( resolve ) => setTimeout( resolve, 40 ) );
			button.click();
			await new Promise( ( resolve ) => setTimeout( resolve, 40 ) );
			button.click();
		} );
		await settled( page );
		rapid = ( await state( page, 'multi' ) )[ 0 ];
		assert.deepEqual( rapid, { expanded: true, hidden: false, inert: false, height: '', animating: false }, 'Rapid reversal finishes open with natural height.' );

		await page.locator( '#single-q0' ).focus();
		await page.keyboard.press( 'Space' );
		await settled( page );
		assert.equal( ( await state( page, 'single' ) )[ 0 ].expanded, true, 'Native Space activates a question.' );
		await page.keyboard.press( 'ArrowDown' );
		assert.equal( await page.evaluate( () => document.activeElement.id ), 'single-q1' );
		await page.keyboard.press( 'End' );
		assert.equal( await page.evaluate( () => document.activeElement.id ), 'single-q2' );
		await page.keyboard.press( 'ArrowDown' );
		assert.equal( await page.evaluate( () => document.activeElement.id ), 'single-q0', 'Arrow keys wrap in the current accordion.' );
		await page.keyboard.press( 'ArrowUp' );
		assert.equal( await page.evaluate( () => document.activeElement.id ), 'single-q2' );
		await page.keyboard.press( 'Home' );
		await page.keyboard.press( 'Enter' );
		await settled( page );
		assert.equal( ( await state( page, 'single' ) )[ 0 ].expanded, false, 'Native Enter closes a question.' );

		await page.locator( '#single-q0' ).click();
		await settled( page );
		await page.locator( '#single-a0 a' ).focus();
		await page.evaluate( () => document.getElementById( 'single-q0' ).click() );
		assert.equal( await page.evaluate( () => document.activeElement.id ), 'single-q0', 'Closing a focused answer returns focus to its question.' );
		await settled( page );

		await page.emulateMedia( { reducedMotion: 'reduce' } );
		await page.locator( '#single-q1' ).click();
		assert.deepEqual( ( await state( page, 'single' ) )[ 1 ], { expanded: true, hidden: false, inert: false, height: '', animating: false }, 'Reduced motion opens immediately.' );
		await page.locator( '#single-q1' ).click();
		assert.deepEqual( ( await state( page, 'single' ) )[ 1 ], { expanded: false, hidden: true, inert: true, height: '', animating: false }, 'Reduced motion closes immediately.' );

		// Re-running enhancement is harmless and must not attach duplicate click handlers.
		await page.addScriptTag( { content: script } );
		await page.locator( '#single-q2' ).click();
		assert.equal( ( await state( page, 'single' ) )[ 2 ].expanded, true );

		const fallback = await browser.newPage();
		await fallback.setContent( fixture() );
		await fallback.evaluate( () => { Element.prototype.animate = undefined; } );
		await fallback.addScriptTag( { content: script } );
		await fallback.locator( '#single-first-q0' ).click();
		assert.equal( ( await state( fallback, 'single-first' ) )[ 0 ].hidden, true, 'Browsers without Web Animations retain immediate toggles.' );
		await fallback.locator( '#single-first-q0' ).click();
		assert.equal( ( await state( fallback, 'single-first' ) )[ 0 ].hidden, false );

		const mediaPage = await browser.newPage();
		await mediaPage.setContent( mediaFixture() );
		await mediaPage.addScriptTag( { content: script } );
		assert.equal( await mediaPage.locator( '#dark-faq' ).evaluate( ( el ) => getComputedStyle( el ).borderTopWidth ), '0px', 'FAQ section has no extra top border.' );
		assert.equal( await mediaPage.locator( '#dark-faq .rise-lp__faq-list' ).evaluate( ( el ) => getComputedStyle( el ).borderTopColor ), 'rgb(255, 255, 255)', 'Dark FAQ uses white dividers.' );
		assert.equal( await mediaPage.locator( '#white-faq .rise-lp__faq-list' ).evaluate( ( el ) => getComputedStyle( el ).borderTopColor ), 'rgb(17, 17, 17)', 'White FAQ uses dark dividers.' );
		assert.equal( await mediaPage.locator( '[data-rise-faq-media-index="0"]' ).isVisible(), true );
		await mediaPage.locator( '#media-q1' ).click();
		assert.equal( await mediaPage.locator( '[data-rise-faq-media-index="0"]' ).isVisible(), false, 'Previous image hides when another question opens.' );
		assert.equal( await mediaPage.locator( '[data-rise-faq-media-index="1"]' ).isVisible(), true, 'Matching question image appears.' );
		await mediaPage.waitForFunction( () => document.querySelectorAll( '#dark-faq [data-rise-faq-media-index="1"] .rise-lp__faq-image-tile' ).length === 9 );
		assert.equal( await mediaPage.locator( '#dark-faq [data-rise-faq-media-index="1"] .rise-lp__faq-image-tile' ).count(), 9, 'Rise Fitness preset animates nine clipped image tiles.' );
		const firstSwitch = await browser.newPage();
		await firstSwitch.setContent( mediaFixture() );
		await firstSwitch.evaluate( () => {
			const image = document.querySelector( '[data-rise-faq-media-index="1"] .rise-lp__faq-image' );
			window.riseTestImageLoaded = false;
			Object.defineProperty( image, 'complete', { configurable: true, get: () => window.riseTestImageLoaded } );
			Object.defineProperty( image, 'naturalWidth', { configurable: true, get: () => window.riseTestImageLoaded ? 40 : 0 } );
			image.decode = () => Promise.reject( new DOMException( 'Image is still loading', 'EncodingError' ) );
		} );
		await firstSwitch.addScriptTag( { content: script } );
		await firstSwitch.locator( '#media-q1' ).click();
		await firstSwitch.waitForTimeout( 40 );
		assert.equal( await firstSwitch.locator( '[data-rise-faq-media-index="1"]' ).evaluate( ( frame ) => frame.classList.contains( 'rise-lp__image-motion-playing' ) ), true, 'An unloaded accordion image stays in reveal state after decode rejects.' );
		assert.equal( await firstSwitch.locator( '[data-rise-faq-media-index="1"] .rise-lp__faq-image' ).evaluate( ( image ) => getComputedStyle( image ).opacity ), '0', 'The unloaded image does not flash before its first reveal.' );
		await firstSwitch.evaluate( () => {
			window.riseTestImageLoaded = true;
			document.querySelector( '[data-rise-faq-media-index="1"] .rise-lp__faq-image' ).dispatchEvent( new Event( 'load' ) );
		} );
		await firstSwitch.waitForFunction( () => document.querySelectorAll( '[data-rise-faq-media-index="1"] .rise-lp__faq-image-tile' ).length === 9 );
		assert.equal( await firstSwitch.locator( '[data-rise-faq-media-index="1"] .rise-lp__faq-image-tile' ).count(), 9, 'The first selection animates once the image loads.' );
		if ( process.env.RISE_FAQ_QA_IMAGE ) {
			await mediaPage.waitForTimeout( 350 );
			await mediaPage.locator( '#dark-faq .rise-lp__faq-media' ).screenshot( { path: process.env.RISE_FAQ_QA_IMAGE } );
		}
		await mediaPage.waitForFunction( () => document.querySelectorAll( '#dark-faq .rise-lp__faq-image-tile' ).length === 0 && document.querySelector( '#dark-faq [data-rise-faq-media-index="1"]' ).classList.contains( 'rise-lp__image-motion-complete' ) );
		await mediaPage.locator( '#media-q0' ).click();
		await mediaPage.locator( '#media-q1' ).click();
		await mediaPage.waitForFunction( () => document.querySelectorAll( '#dark-faq [data-rise-faq-media-index="0"] .rise-lp__faq-image-tile' ).length === 0 );
		await mediaPage.waitForFunction( () => document.querySelectorAll( '#dark-faq .rise-lp__faq-image-tile' ).length === 0 );
		await mediaPage.locator( '#fade-faq' ).scrollIntoViewIfNeeded();
		await mediaPage.waitForFunction( () => document.querySelector( '#fade-faq .rise-lp__faq-media-frame' ).classList.contains( 'rise-lp__image-motion-complete' ) );
		assert.equal( await mediaPage.locator( '#fade-faq .rise-lp__faq-image-tile' ).count(), 0, 'Fade preset completes without mask tiles.' );
		await mediaPage.waitForTimeout( 450 );
		assert.equal( await mediaPage.locator( '#media-q1 .rise-lp__faq-dashes path' ).first().evaluate( ( el ) => getComputedStyle( el ).fill ), 'rgb(236, 28, 43)', 'Open SVG slashes fill with the Rise accent.' );
		await mediaPage.setViewportSize( { width: 390, height: 844 } );
		assert.equal( await mediaPage.locator( '#dark-faq .rise-lp__faq-layout' ).evaluate( ( el ) => getComputedStyle( el ).gridTemplateColumns.split( ' ' ).length ), 1, 'FAQ image and content stack on mobile.' );
		const firstPaint = await browser.newPage();
		await firstPaint.setContent( mediaFixture().replace( '<html>', '<html class="rise-lp-motion-ready">' ) );
		assert.equal( await firstPaint.locator( '#dark-faq .rise-lp__faq-image' ).first().evaluate( ( el ) => getComputedStyle( el ).opacity ), '0', 'Tile image is hidden before the deferred script runs.' );
		assert.equal( await firstPaint.locator( '#fade-faq .rise-lp__faq-media-frame' ).evaluate( ( el ) => getComputedStyle( el ).opacity ), '0', 'Fade image is hidden before the deferred script runs.' );
		await firstPaint.addScriptTag( { content: script } );
		await firstPaint.locator( '#fade-faq' ).scrollIntoViewIfNeeded();
		await firstPaint.waitForFunction( () => document.querySelector( '#fade-faq .rise-lp__faq-media-frame' ).classList.contains( 'rise-lp__image-motion-complete' ) );
		assert.equal( await firstPaint.locator( '#fade-faq .rise-lp__faq-media-frame' ).evaluate( ( el ) => getComputedStyle( el ).opacity ), '1', 'Fade image is visible after its reveal.' );
		const noMotionImages = await browser.newPage( { reducedMotion: 'reduce' } );
		await noMotionImages.setContent( mediaFixture() );
		await noMotionImages.addScriptTag( { content: script } );
		await noMotionImages.locator( '#media-q1' ).click();
		assert.equal( await noMotionImages.locator( '#dark-faq .rise-lp__faq-image-tile' ).count(), 0, 'Reduced motion skips the tile reveal.' );
		assert.equal( await noMotionImages.locator( '#dark-faq [data-rise-faq-media-index="1"]' ).evaluate( ( el ) => el.classList.contains( 'rise-lp__image-motion-complete' ) ), true, 'Reduced motion shows the selected image immediately.' );

		console.log( 'PASS: FAQ no-script visibility, independent preferences, single/multi mode, animations, rapid reversals, inert/focus, keyboard controls, reduced motion, repeated initialization, API fallback, image switching, fade and nine-tile presets, contrasting dividers, SVG fill, and mobile layout.' );
	} finally {
		await browser.close();
	}
}

main().catch( ( error ) => { console.error( error ); process.exitCode = 1; } );
