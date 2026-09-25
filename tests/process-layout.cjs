const { chromium } = require( 'playwright' );
const assert = require( 'assert/strict' );
const fs = require( 'fs' );
const path = require( 'path' );
const vm = require( 'vm' );

const css = fs.readFileSync(
	path.join( __dirname, '../assets/frontend.css' ),
	'utf8'
);

// Exercise the editor's actual position helper without loading its JSX imports.
const collectionSource = fs.readFileSync(
	path.join( __dirname, '../assets/editor/Collection.js' ),
	'utf8'
);
const positionSource = collectionSource.match(
	/^const processCirclePosition = [\s\S]*?^};/m
);
assert( positionSource, 'the process position helper can be extracted' );
const processCirclePosition = new vm.Script(
	`${ positionSource[ 0 ] }\nprocessCirclePosition;`
).runInNewContext( Object.create( null ), { timeout: 1000 } );

function markup( count, { crowded = false, image = false, direction = 'clockwise' } = {} ) {
	const leftCount = Math.ceil( count / 2 );
	const items = Array.from( { length: count }, ( _, index ) => {
		const position = processCirclePosition( index, count, direction );
		const style = Object.entries( position.style ).map(
			( [ property, value ] ) => `${ property }:${ value }`
		).join( ';' );
		const title = crowded && index === count - 1
			? 'A very long test title '.repeat( 9 )
			: `Step ${ index + 1 }`;
		return `<li class="rise-lp__process-step ${ position.className }" style="${ style }"><span class="rise-lp__step-marker">${ image ? '<img class="rise-lp__step-image" src="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22%3E%3Ccircle cx=%2220%22 cy=%2220%22 r=%2210%22/%3E%3C/svg%3E" alt="">' : `<span class="rise-lp__step-number">${ String(
			index + 1
		).padStart( 2, '0' ) }</span>` }</span><h3 class="rise-lp__card-title">${ title }</h3><div class="rise-lp__card-copy">Discuss your goals and the support available.</div></li>`;
	} ).join( '' );
	return `<style>${ css }</style><div class="rise-lp" style="--rise-primary:#1760d7;--rise-secondary:#fff;--rise-text:#111;--rise-muted:#444;--rise-button-text:#fff"><section class="rise-lp__section rise-lp__process"><div class="rise-lp__container"><div class="rise-lp__section-header"><p class="rise-lp__eyebrow">How it works</p><h2 class="rise-lp__heading">A clear path from the start</h2></div><ol class="rise-lp__process-grid rise-lp__process-grid--circle${ crowded ? ' rise-lp__process-grid--crowded' : '' }" style="--rise-process-rows:${ leftCount }">${ items }</ol></div></section></div>`;
}

function screenshotPath( filename, direction ) {
	if ( direction === 'clockwise' ) {
		return filename;
	}
	const extension = path.extname( filename );
	return `${ filename.slice( 0, filename.length - extension.length ) }-${ direction }${ extension }`;
}

async function readLayout( page ) {
	return page.locator( '.rise-lp__process-grid' ).evaluate( ( grid ) => {
		const ring = getComputedStyle( grid, '::before' );
		return {
			columns: getComputedStyle( grid ).gridTemplateColumns.split( ' ' ).length,
			ring: parseFloat( ring.width ),
			ringBorder: parseFloat( ring.borderTopWidth ),
			markerBackground: getComputedStyle( grid.querySelector( '.rise-lp__step-marker' ) ).backgroundColor,
			numberColor: getComputedStyle( grid.querySelector( '.rise-lp__step-number' ) ).color,
			grid: grid.getBoundingClientRect().toJSON(),
			markers: Array.from( grid.querySelectorAll( '.rise-lp__step-marker' ) ).map(
				( marker ) => marker.getBoundingClientRect().toJSON()
			),
			steps: Array.from( grid.children ).map( ( step ) => step.getBoundingClientRect().toJSON() ),
			leftSides: Array.from( grid.children ).map(
				( step ) => step.classList.contains( 'rise-lp__process-step--left' )
			),
			connectorWidths: Array.from( grid.querySelectorAll( '.rise-lp__step-marker' ) ).map(
				( marker ) => parseFloat( getComputedStyle( marker, '::after' ).width )
			),
			numbers: Array.from( grid.querySelectorAll( '.rise-lp__step-number' ) ).map(
				( marker ) => marker.textContent.trim()
			),
			overflow: document.documentElement.scrollWidth > innerWidth,
		};
	} );
}

function assertLogicalOrder( layout, count, label ) {
	assert.deepEqual(
		layout.numbers,
		Array.from( { length: count }, ( _, index ) => String( index + 1 ).padStart( 2, '0' ) ),
		`${ label }: DOM order remains sequential`
	);
}

function assertStacked( layout, count, label ) {
	assert.equal( layout.columns, 1, `${ label }: steps stack` );
	assert.equal( layout.overflow, false, `${ label }: page does not overflow` );
	assertLogicalOrder( layout, count, label );
	for ( let index = 1; index < layout.steps.length; index++ ) {
		assert(
			layout.steps[ index ].top >= layout.steps[ index - 1 ].bottom,
			`${ label }: steps remain sequential without overlap`
		);
	}
}

function assertCircle( layout, count, direction, label ) {
	assert.equal( layout.columns, 3, `${ label }: steps use the circle grid` );
	assert( layout.ring >= 220, `${ label }: ring is visible` );
	assert.equal( layout.markerBackground, 'rgb(0, 0, 0)', 'number circles are black' );
	assert.equal( layout.numberColor, 'rgb(23, 96, 215)', 'numbers use the primary colour' );
	assert.equal( layout.markers.length, count );
	assert.equal( layout.overflow, false, `${ label }: page does not overflow` );
	assertLogicalOrder( layout, count, label );
	for ( let index = 0; index < count; index++ ) {
		const marker = layout.markers[ index ];
		const step = layout.steps[ index ];
		const connectorWidth = layout.connectorWidths[ index ];
		const gap = layout.leftSides[ index ]
			? marker.left - connectorWidth - step.right
			: step.left - marker.right - connectorWidth;
		assert(
			gap >= 5 && gap <= 12,
			`${ label }: connector reaches close to step text without crossing it`
		);
	}
	const centerX = layout.grid.x + layout.grid.width / 2;
	const centerY = layout.grid.y + layout.grid.height / 2;
	let previousAngle = null;
	let totalAngle = 0;
	for ( const marker of layout.markers ) {
		const dx = marker.x + marker.width / 2 - centerX;
		const dy = marker.y + marker.height / 2 - centerY;
		const distance = Math.hypot( dx, dy );
		const markerRadius = marker.width / 2;
		assert(
			distance + markerRadius <= layout.ring / 2 + 1 &&
				distance - markerRadius >= layout.ring / 2 - layout.ringBorder - 1,
			`${ label }: numbered circle fits inside the ring`
		);
		const angle = ( Math.atan2( dx, -dy ) * 180 / Math.PI + 360 ) % 360;
		if ( previousAngle !== null ) {
			const advance = ( ( direction === 'anticlockwise'
				? previousAngle - angle
				: angle - previousAngle ) + 360 ) % 360;
			assert( advance > 0 && advance <= 180.01, `${ label }: steps advance ${ direction }` );
			totalAngle += advance;
		}
		previousAngle = angle;
	}
	assert( totalAngle < 360, `${ label }: steps follow one pass around the circle` );
	for ( let i = 0; i < count; i++ ) {
		for ( let j = i + 1; j < count; j++ ) {
			const a = layout.markers[ i ];
			const b = layout.markers[ j ];
			assert(
				a.right <= b.left || b.right <= a.left || a.bottom <= b.top || b.bottom <= a.top,
				`${ label }: step markers do not overlap`
			);
		}
	}
	return {
		x: layout.markers[ 0 ].x - layout.grid.x,
		y: layout.markers[ 0 ].y - layout.grid.y,
	};
}

( async () => {
	const browser = await chromium.launch( { channel: 'chrome', headless: true } );
	try {
		const firstAnchors = new Map();
		for ( const direction of [ 'clockwise', 'anticlockwise' ] ) {
			for ( const count of [ 2, 3, 4, 5, 6 ] ) {
				const label = `${ count } ${ direction } steps`;
				const page = await browser.newPage( {
					viewport: { width: 1200, height: 900 },
				} );
				await page.setContent( markup( count, { direction } ) );
				await page.evaluate( () => {
					document.documentElement.classList.add( 'rise-lp-motion-ready' );
				} );
				const visibleSteps = await page.locator( '.rise-lp__process-step' ).evaluateAll(
					( steps ) => steps.every( ( step ) => getComputedStyle( step ).opacity === '1' )
				);
				assert( visibleSteps, `${ label }: labels remain visible with frontend motion enabled` );
				for ( const width of [ 1200, 1000 ] ) {
					await page.setViewportSize( { width, height: 900 } );
					const anchor = assertCircle( await readLayout( page ), count, direction, `${ label } at ${ width }px` );
					const key = `${ count }-${ width }`;
					if ( direction === 'clockwise' ) {
						firstAnchors.set( key, anchor );
					} else {
						assert.deepEqual( anchor, firstAnchors.get( key ), `${ label }: first step keeps the same anchor` );
					}
				}
				await page.setViewportSize( { width: 1200, height: 900 } );
				await page.locator( '.rise-lp' ).evaluate( ( root ) => {
					root.style.width = '780px';
				} );
				assertStacked( await readLayout( page ), count, `${ label } in a narrow editor canvas` );
				await page.locator( '.rise-lp' ).evaluate( ( root ) => {
					root.style.width = '';
				} );
				if ( count === 4 && process.env.RISE_PROCESS_QA_IMAGE ) {
					await page.locator( '.rise-lp__process' ).screenshot( {
						path: screenshotPath( process.env.RISE_PROCESS_QA_IMAGE, direction ),
					} );
				}
				await page.setViewportSize( { width: 390, height: 850 } );
				assertStacked( await readLayout( page ), count, `${ label } on mobile` );
				if ( count === 4 && process.env.RISE_PROCESS_QA_MOBILE_IMAGE ) {
					await page.locator( '.rise-lp__process' ).screenshot( {
						path: screenshotPath( process.env.RISE_PROCESS_QA_MOBILE_IMAGE, direction ),
					} );
				}
				await page.setViewportSize( { width: 1200, height: 900 } );
				await page.setContent( markup( count, { direction, crowded: true } ) );
				assertStacked( await readLayout( page ), count, `${ label } with long text` );
				if ( count === 4 && process.env.RISE_PROCESS_QA_CROWDED_IMAGE ) {
					await page.locator( '.rise-lp__process' ).screenshot( {
						path: screenshotPath( process.env.RISE_PROCESS_QA_CROWDED_IMAGE, direction ),
					} );
				}
				await page.close();
			}
		}
		const imagePage = await browser.newPage( {
			viewport: { width: 1200, height: 900 },
		} );
		await imagePage.setContent( markup( 4, { image: true } ) );
		const imageMarkers = await imagePage.locator( '.rise-lp__step-marker' ).evaluateAll(
			( markers ) => markers.map( ( marker ) => ( {
				image: !!marker.querySelector( 'img' ),
				line: parseFloat( getComputedStyle( marker, '::after' ).width ),
				background: getComputedStyle( marker ).backgroundColor,
			} ) )
		);
		assert( imageMarkers.every( ( marker ) => marker.image && marker.line > 0 ), 'image markers keep their connector lines' );
		assert( imageMarkers.every( ( marker ) => marker.background === 'rgb(0, 0, 0)' ), 'image circles default to black' );
		await imagePage.locator( '.rise-lp' ).evaluate( ( root ) => {
			root.style.setProperty( '--rise-process-image-background', '#d34040' );
		} );
		const customBackgrounds = await imagePage.locator( '.rise-lp__step-marker' ).evaluateAll(
			( markers ) => markers.map( ( marker ) => getComputedStyle( marker ).backgroundColor )
		);
		assert( customBackgrounds.every( ( color ) => color === 'rgb(211, 64, 64)' ), 'image circles use the selected background colour' );
		await imagePage.setViewportSize( { width: 390, height: 850 } );
		const imageMobile = await imagePage.locator( '.rise-lp__process-grid' ).evaluate( ( grid ) => ( {
			columns: getComputedStyle( grid ).gridTemplateColumns.split( ' ' ).length,
			lines: Array.from( grid.querySelectorAll( '.rise-lp__step-marker' ) ).every( ( marker ) => getComputedStyle( marker, '::after' ).display === 'none' ),
		} ) );
		assert.equal( imageMobile.columns, 1, 'image steps keep the mobile timeline' );
		assert( imageMobile.lines, 'mobile image steps do not show horizontal connectors' );
		await imagePage.close();
		console.log( 'Process circle layout: 2–6 clockwise and anticlockwise steps pass desktop, narrow canvas, mobile, and long-text checks.' );
	} finally {
		await browser.close();
	}
} )().catch( ( error ) => {
	console.error( error );
	process.exitCode = 1;
} );
