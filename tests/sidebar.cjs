/* Run after browser-setup.php; verifies the landing-only sidebar resizer. */
const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );
const assert = require( 'assert/strict' );
const fixturePath = require( './fixture-path.cjs' );

const fixture = JSON.parse(
	fs.readFileSync(
		fixturePath( 'browser-fixture' )
	)
);

( async () => {
	const browser = await chromium.launch( {
		channel: 'chrome',
		headless: true,
	} );
	try {
		const context = await browser.newContext( {
			viewport: { width: 1440, height: 900 },
		} );
		await context.addCookies( fixture.cookies );
		const page = await context.newPage();
		await page.goto(
			`${ fixture.baseURL }/wp-admin/post.php?post=${ fixture.ids[ 0 ] }&action=edit`,
			{ waitUntil: 'domcontentloaded', timeout: 60000 }
		);
		const sidebar = page.locator( '.interface-interface-skeleton__sidebar' );
		await page.waitForFunction( () =>
			document.querySelector( '[data-rise-lp-sidebar-handle]' )
		);
		const handle = page.locator( '[data-rise-lp-sidebar-handle]' );
		const initial = await sidebar.evaluate(
			( element ) => element.getBoundingClientRect().width
		);
		assert.equal( Math.round( initial ), 400 );
		console.log( 'PASS Landing sidebar starts at 400px' );

		await handle.focus();
		await page.keyboard.press( 'ArrowLeft' );
		assert.equal(
			Math.round(
				await sidebar.evaluate(
					( element ) => element.getBoundingClientRect().width
				)
			),
			420
		);
		console.log( 'PASS Keyboard resizing widens the sidebar' );

		const box = await handle.boundingBox();
		await page.mouse.move( box.x + box.width / 2, box.y + 100 );
		await page.mouse.down();
		await page.mouse.move( box.x - 100, box.y + 100, { steps: 5 } );
		await page.mouse.up();
		const dragged = await sidebar.evaluate(
			( element ) => element.getBoundingClientRect().width
		);
		assert.ok( dragged > 500 && dragged < 540 );
		console.log( 'PASS Dragging the left edge widens the sidebar' );

		await page.reload( { waitUntil: 'domcontentloaded', timeout: 60000 } );
		await page.waitForFunction( () =>
			document.querySelector( '[data-rise-lp-sidebar-handle]' )
		);
		assert.equal(
			Math.round(
				await sidebar.evaluate(
					( element ) => element.getBoundingClientRect().width
				)
			),
			Math.round( dragged )
		);
		console.log( 'PASS Sidebar width persists after reload' );

		await page.goto(
			`${ fixture.baseURL }/wp-admin/post.php?post=${ fixture.ids[ 1 ] }&action=edit`,
			{ waitUntil: 'domcontentloaded', timeout: 60000 }
		);
		assert.equal( await page.locator( '[data-rise-lp-sidebar-handle]' ).count(), 0 );
		console.log( 'PASS Ordinary pages have no Rise resize handle' );
	} finally {
		await browser.close();
	}
} )().catch( ( error ) => {
	console.error( error );
	process.exitCode = 1;
} );
