/** Browser geometry checks for the content-aware Hero height controls. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( 'playwright' );

const root = path.resolve( __dirname, '..' );
const frontendCss = fs.readFileSync( path.join( root, 'assets/frontend.css' ), 'utf8' );
const editorCss = fs.readFileSync( path.join( root, 'assets/editor.css' ), 'utf8' );
const chrome = process.env.RISE_TEST_CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

function fixture( { editor = false, min = 376, max = 0, contentHeight = 0 } = {} ) {
	const variables = `--rise-hero-min-height:${ min }px;--rise-hero-preview-height:${ Math.max( min, max ) }px;${ max ? `--rise-hero-max-height:${ max }px;` : '' }`;
	const content = contentHeight ? `<div id="extra" style="height:${ contentHeight }px"></div>` : '';
	const wrapper = editor ? '<div class="editor-styles-wrapper"><div class="rise-lp rise-lp-editor">' : '<div class="rise-lp">';
	return `<!doctype html><html><head><style>${ frontendCss }${ editorCss }</style></head><body style="margin:0">${ wrapper }<section id="hero" class="rise-lp__section rise-lp__hero rise-lp__hero--overlay" style="${ variables }"><div class="rise-lp__container rise-lp__hero-grid"><div class="rise-lp__hero-content"><h1 class="rise-lp__heading">Banner</h1>${ content }</div></div></section>${ editor ? '</div></div>' : '</div>' }</body></html>`;
}

( async () => {
	const browser = await chromium.launch( { headless: true, ...( fs.existsSync( chrome ) ? { executablePath: chrome } : {} ) } );
	try {
		const page = await browser.newPage( { viewport: { width: 1200, height: 900 } } );
		await page.setContent( fixture() );
		assert.equal( await page.locator( '#hero' ).evaluate( ( el ) => Math.round( el.getBoundingClientRect().height ) ), 900, 'Uncapped Hero fills the viewport.' );
		await page.setContent( fixture( { max: 420 } ) );
		assert.equal( await page.locator( '#hero' ).evaluate( ( el ) => Math.round( el.getBoundingClientRect().height ) ), 420, 'Maximum limits viewport fill to banner height.' );
		await page.setContent( fixture( { max: 420, contentHeight: 600 } ) );
		const tall = await page.evaluate( () => {
			const hero = document.querySelector( '#hero' ).getBoundingClientRect();
			const content = document.querySelector( '#extra' ).getBoundingClientRect();
			return { height: hero.height, inside: content.bottom <= hero.bottom - 79 };
		} );
		assert.ok( tall.height > 420 && tall.inside, 'Long content expands the Hero and retains its bottom spacing.' );
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.setContent( fixture( { max: 420, contentHeight: 600 } ) );
		const mobile = await page.evaluate( () => {
			const hero = document.querySelector( '#hero' ).getBoundingClientRect();
			const content = document.querySelector( '#extra' ).getBoundingClientRect();
			return { height: hero.height, inside: content.bottom <= hero.bottom - 79 };
		} );
		assert.ok( mobile.height > 420 && mobile.inside, 'The capped Hero also preserves content and spacing on mobile.' );
		await page.setViewportSize( { width: 1200, height: 900 } );
		await page.setContent( fixture( { editor: true, min: 376 } ) );
		assert.equal( await page.locator( '#hero' ).evaluate( ( el ) => Math.round( el.getBoundingClientRect().height ) ), 376, 'Editor preview uses the minimum height control.' );
		await page.setContent( fixture( { editor: true, min: 499 } ) );
		assert.equal( await page.locator( '#hero' ).evaluate( ( el ) => Math.round( el.getBoundingClientRect().height ) ), 499, 'Editor preview responds when minimum height changes.' );
		await page.setContent( fixture( { editor: true, min: 376, max: 420 } ) );
		assert.equal( await page.locator( '#hero' ).evaluate( ( el ) => Math.round( el.getBoundingClientRect().height ) ), 420, 'Editor preview responds to the banner cap.' );
		await page.setContent( `<!doctype html><html><head><style>
			.block-editor-block-list__layout.is-layout-constrained > * { margin-inline: auto; }
			${ frontendCss }${ editorCss }
		</style></head><body style="margin:0"><div class="editor-styles-wrapper"><div class="rise-lp rise-lp-editor"><section class="rise-lp__hero rise-lp__hero--overlay rise-lp__hero--left"><div class="rise-lp__container rise-lp__hero-grid"><div class="rise-lp__hero-content"><div class="block-editor-inner-blocks"><div class="block-editor-block-list__layout is-layout-constrained"><h1 class="rise-lp__heading wp-block-rise-landing-hero-part">Heading</h1><div class="rise-lp-editor rise-lp-editor--spacer"><div class="rise-lp-editor__spacer" style="height:48px"></div></div><p class="rise-lp__intro wp-block-rise-landing-hero-part">Description</p></div></div></div></div></section></div></div></body></html>` );
		const innerLayout = await page.evaluate( () => {
			const left = ( selector ) => document.querySelector( selector ).getBoundingClientRect().left;
			return {
				content: left( '.rise-lp__hero-content' ),
				heading: left( '.rise-lp__heading' ),
				spacer: left( '.rise-lp-editor--spacer' ),
				description: left( '.rise-lp__intro' ),
				gap: document.querySelector( '.rise-lp__intro' ).getBoundingClientRect().top - document.querySelector( '.rise-lp__heading' ).getBoundingClientRect().bottom,
			};
		} );
		assert.ok( [ innerLayout.heading, innerLayout.spacer, innerLayout.description ].every( ( left ) => Math.abs( left - innerLayout.content ) < 1 ), 'Inner Hero blocks share one left edge in the editor.' );
		assert.ok( Math.abs( innerLayout.gap - 48 ) < 1, 'A nested Spacer sets the full gap without stacking a title margin.' );
		await page.setContent( `<!doctype html><html><head><style>${ frontendCss }</style></head><body style="margin:0"><div class="rise-lp"><section class="rise-lp__hero rise-lp__hero--overlay"><div class="rise-lp__container rise-lp__hero-grid"><div class="rise-lp__hero-content"><h1 class="rise-lp__heading">Heading</h1><div class="rise-lp__spacer" style="--rise-spacer-height:48px"></div><div class="rise-lp__intro" style="margin-top:32px">Description</div></div></div></section></div></body></html>` );
		const publishedGap = await page.evaluate( () => document.querySelector( '.rise-lp__intro' ).getBoundingClientRect().top - document.querySelector( '.rise-lp__heading' ).getBoundingClientRect().bottom );
		assert.ok( Math.abs( publishedGap - 48 ) < 1, 'A published Hero uses the nested Spacer height without the legacy description margin.' );
		console.log( 'PASS: Hero viewport fill, banner cap, content growth, and editor preview heights.' );
	} finally {
		await browser.close();
	}
} )().catch( ( error ) => { console.error( error ); process.exitCode = 1; } );
