const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );
const assert = require( 'assert/strict' );

const root = path.resolve( __dirname, '..' );
const css = fs.readFileSync( path.join( root, 'assets/frontend.css' ), 'utf8' );
const media = fs.readFileSync( path.join( root, 'assets/media.js' ), 'utf8' );
const cards = Array.from(
	{ length: 6 },
	( _, index ) =>
		`<article class="rise-lp__service-card"><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">Service ${
			index + 1
		}</h3><div class="rise-lp__card-copy">Description</div></div></article>`
).join( '' );

( async () => {
	const browser = await chromium.launch( {
		channel: 'chrome',
		headless: true,
	} );
	try {
		const page = await browser.newPage( {
			viewport: { width: 1100, height: 800 },
		} );
		await page.setContent(
			`<style>${ css }</style><div class="rise-lp"><section class="rise-lp__section rise-lp__services"><div class="rise-lp__container"><div class="rise-lp__services-grid rise-lp__services-grid--standard rise-lp__services-grid--slider" data-rise-slider data-loop="true" data-autoplay="false" tabindex="0">${ cards }</div><div class="rise-lp__slider-controls" data-slider-controls hidden><button data-slide-previous>Previous</button><span class="rise-lp__slider-status"></span><button data-slide-next>Next</button></div></div></section></div>`
		);
		await page.addScriptTag( { content: media } );
		const track = page.locator( '[data-rise-slider]' );
		await page.waitForFunction(
			() => ! document.querySelector( '[data-slider-controls]' ).hidden
		);
		assert.equal(
			await track.evaluate(
				( element ) => getComputedStyle( element ).display
			),
			'flex'
		);
		await page.locator( '[data-slide-next]' ).click();
		await page.waitForFunction(
			() => document.querySelector( '[data-rise-slider]' ).scrollLeft > 10
		);
		await page.locator( '[data-slide-previous]' ).click();
		await page.waitForFunction(
			() => document.querySelector( '[data-rise-slider]' ).scrollLeft < 10
		);
		await page.locator( '[data-slide-previous]' ).click();
		await page.waitForFunction(
			() =>
				document.querySelector( '[data-rise-slider]' ).scrollLeft > 500
		);
		assert.match(
			await page.locator( '.rise-lp__slider-status' ).textContent(),
			/\/ 6/
		);
		await page.setViewportSize( { width: 375, height: 800 } );
		assert(
			await page.evaluate(
				() => document.documentElement.scrollWidth <= innerWidth
			)
		);
		const autoplayPage = await browser.newPage( {
			viewport: { width: 1100, height: 800 },
		} );
		await autoplayPage.setContent(
			`<style>${ css }</style><div class="rise-lp"><section class="rise-lp__section rise-lp__services"><div class="rise-lp__container"><div class="rise-lp__services-grid rise-lp__services-grid--standard rise-lp__services-grid--slider" data-rise-slider data-loop="true" data-autoplay="true" data-autoplay-interval="2">${ cards }</div><div class="rise-lp__slider-controls" data-slider-controls hidden><button data-slide-previous>Previous</button><button data-slide-next>Next</button></div></div></section></div>`
		);
		await autoplayPage.addScriptTag( { content: media } );
		await autoplayPage.mouse.move( 0, 0 );
		await autoplayPage.waitForFunction(
			() =>
				document.querySelector( '[data-rise-slider]' ).scrollLeft > 10,
			null,
			{ timeout: 5000 }
		);
		const image =
			'data:image/svg+xml,' +
			encodeURIComponent(
				'<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600"><rect width="600" height="600" fill="#74756c"/><circle cx="300" cy="235" r="105" fill="#aaa99c"/><path d="M95 600q15-235 205-235t205 235" fill="#242424"/></svg>'
			);
		const layoutPage = await browser.newPage( {
			viewport: { width: 1100, height: 800 },
		} );
		await layoutPage.setContent(
			`<style>${ css }</style><div class="rise-lp" style="--rise-primary:#ec1c2b;--rise-background:#080808;--rise-text:#fff;--rise-surface:#1b1b1b;--rise-services-columns:2"><section class="rise-lp__section rise-lp__services"><div class="rise-lp__container"><div class="rise-lp__services-grid rise-lp__services-grid--pricing"><article class="rise-lp__service-card"><span class="rise-lp__service-badge">Recovery Pro</span><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">Monthly membership</h3><ul class="rise-lp__service-features"><li>Full use of our sauna</li><li>Filtration system ice baths</li><li>Recovery equipment</li></ul><div class="rise-lp__service-price-block"><div class="rise-lp__service-price-line"><span class="rise-lp__service-price">EUR 75</span><span class="rise-lp__service-price-qualifier">Per month</span></div><span class="rise-lp__service-price-note">No commitment</span></div><a class="rise-lp__button">Book now</a></div></article><article class="rise-lp__service-card"><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">Single use 60 minutes</h3><ul class="rise-lp__service-features"><li>Full use of our sauna</li><li>Filtration system ice baths</li></ul><div class="rise-lp__service-price-block"><div class="rise-lp__service-price-line"><span class="rise-lp__service-price">EUR 20</span><span class="rise-lp__service-price-qualifier">One time use</span></div></div><a class="rise-lp__button">Book now</a></div></article></div></div></section><section class="rise-lp__section rise-lp__services" style="--rise-primary:#61ffd6"><div class="rise-lp__container"><div class="rise-lp__services-grid rise-lp__services-grid--image"><article class="rise-lp__service-card"><div class="rise-lp__service-media"><img class="rise-lp__service-image" src="${ image }" alt="" /></div><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">Physiotherapy</h3><a class="rise-lp__button">Learn more</a></div></article><article class="rise-lp__service-card"><div class="rise-lp__service-media"><img class="rise-lp__service-image" src="${ image }" alt="" /></div><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">Strength & conditioning</h3><a class="rise-lp__button">Learn more</a></div></article></div></div></section></div>`
		);
		const style = await layoutPage.evaluate( () => ( {
			pricingColumns: getComputedStyle(
				document.querySelector( '.rise-lp__services-grid--pricing' )
			).gridTemplateColumns.split( ' ' ).length,
			pricingBackground: getComputedStyle(
				document.querySelector(
					'.rise-lp__services-grid--pricing .rise-lp__service-card'
				)
			).backgroundColor,
			pricingBorder: getComputedStyle(
				document.querySelector(
					'.rise-lp__services-grid--pricing .rise-lp__service-card'
				)
			).borderTopColor,
			imageBorder: getComputedStyle(
				document.querySelector(
					'.rise-lp__services-grid--image .rise-lp__service-card'
				)
			).borderTopWidth,
			imageMediaPosition: getComputedStyle(
				document.querySelector(
					'.rise-lp__services-grid--image .rise-lp__service-media'
				)
			).position,
		} ) );
		assert.deepEqual( style, {
			pricingColumns: 2,
			pricingBackground: 'rgb(8, 8, 8)',
			pricingBorder: 'rgb(255, 255, 255)',
			imageBorder: '14px',
			imageMediaPosition: 'absolute',
		} );
		const firstPricingCard = layoutPage.locator(
			'.rise-lp__services-grid--pricing .rise-lp__service-card:first-child'
		);
		await firstPricingCard.hover();
		assert.equal(
			await firstPricingCard.evaluate(
				( card ) => getComputedStyle( card ).borderTopColor
			),
			'rgb(236, 28, 43)'
		);
		await layoutPage.mouse.move( 0, 0 );
		assert.equal(
			await firstPricingCard.evaluate(
				( card ) => getComputedStyle( card ).borderTopColor
			),
			'rgb(255, 255, 255)'
		);
		const imageDescription = await layoutPage
			.locator(
				'.rise-lp__services-grid--image .rise-lp__service-card:first-child'
			)
			.evaluate( ( card ) => {
				const description = document.createElement( 'p' );
				description.className = 'rise-lp__card-copy';
				description.textContent =
					'Treatment tailored to your recovery.';
				card.querySelector( '.rise-lp__card-title' ).after(
					description
				);
				const title = card.querySelector( '.rise-lp__card-title' );
				const button = card.querySelector( '.rise-lp__button' );
				return {
					color: getComputedStyle( description ).color,
					betweenTitleAndButton:
						title.getBoundingClientRect().bottom <
							description.getBoundingClientRect().top &&
						description.getBoundingClientRect().bottom <
							button.getBoundingClientRect().top,
					withinCard:
						description.getBoundingClientRect().bottom <
						card.getBoundingClientRect().bottom,
				};
			} );
		assert.deepEqual( imageDescription, {
			color: 'rgb(255, 255, 255)',
			betweenTitleAndButton: true,
			withinCard: true,
		} );
		const imageAlignment = await layoutPage
			.locator( '.rise-lp__services-grid--image' )
			.evaluate( ( grid ) => {
				const container = grid.closest( '.rise-lp__container' );
				const offset = () =>
					Math.round(
						grid.getBoundingClientRect().left -
							container.getBoundingClientRect().left
					);
				const startOffset = offset();
				grid.classList.add( 'rise-lp__services-grid--image-centered' );
				const centerOffset = offset();
				grid.classList.remove(
					'rise-lp__services-grid--image-centered'
				);
				const card = grid.firstElementChild.getBoundingClientRect();
				return {
					startOffset,
					centerOffset,
					cardWidth: card.width,
					cardHeight: card.height,
				};
			} );
		assert.equal( imageAlignment.startOffset, 0 );
		assert( imageAlignment.centerOffset > 100 );
		assert( imageAlignment.cardHeight <= imageAlignment.cardWidth + 1 );
		const imageLayout = await layoutPage
			.locator( '.rise-lp__services-grid--image' )
			.evaluate( ( grid ) => {
				grid.style.setProperty( '--rise-services-columns', '3' );
				grid.style.setProperty( '--rise-services-grid-gap', '0px' );
				const withoutButton = grid.firstElementChild.cloneNode( true );
				withoutButton.querySelector( '.rise-lp__button' ).remove();
				grid.append( withoutButton );
				const cards = [ ...grid.children ];
				const descriptionBottom = ( card ) =>
					Math.round(
						card.getBoundingClientRect().bottom -
							card
								.querySelector( '.rise-lp__card-copy' )
								.getBoundingClientRect().bottom
					);
			return {
				columns:
					getComputedStyle( grid ).gridTemplateColumns.split(
						' '
					).length,
				gridWidth: Math.round( grid.getBoundingClientRect().width ),
				containerWidth: Math.round(
					grid.parentElement.getBoundingClientRect().width
				),
				cardWidth: Math.round(
					cards[ 0 ].getBoundingClientRect().width
				),
					gap: getComputedStyle( grid ).columnGap,
					firstBorder: getComputedStyle( cards[ 0 ] ).borderTopColor,
					withButtonDescriptionBottom: descriptionBottom(
						cards[ 0 ]
					),
					withoutButtonDescriptionBottom: descriptionBottom(
						cards[ 2 ]
					),
				};
			} );
		assert.equal( imageLayout.columns, 3 );
		assert.equal( imageLayout.gridWidth, imageLayout.containerWidth );
		assert(
			Math.abs(
				imageLayout.cardWidth * 3 + 48 - imageLayout.gridWidth
			) <= 2,
			JSON.stringify( imageLayout )
		);
		assert.equal( imageLayout.gap, '24px' );
		assert.equal( imageLayout.firstBorder, 'rgb(255, 255, 255)' );
		assert.equal(
			imageLayout.withButtonDescriptionBottom,
			imageLayout.withoutButtonDescriptionBottom
		);
		const firstImageCard = layoutPage.locator(
			'.rise-lp__services-grid--image .rise-lp__service-card:first-child'
		);
		await firstImageCard.hover();
		await layoutPage.waitForTimeout( 220 );
		assert.equal(
			await firstImageCard.evaluate(
				( card ) => getComputedStyle( card ).borderTopColor
			),
			'rgb(97, 255, 214)'
		);
		await layoutPage.mouse.move( 0, 0 );
		await layoutPage.waitForTimeout( 220 );
		assert.equal(
			await firstImageCard.evaluate(
				( card ) => getComputedStyle( card ).borderTopColor
			),
			'rgb(255, 255, 255)'
		);
		await layoutPage.setViewportSize( { width: 1920, height: 800 } );
		await layoutPage
			.locator( '.rise-lp__services-grid--image' )
			.evaluate( ( grid ) => {
				grid.closest( '.rise-lp' ).style.setProperty(
					'--rise-content-width',
					'1600px'
				);
			} );
		const imageColumns = async () =>
			layoutPage
				.locator( '.rise-lp__services-grid--image' )
				.evaluate( ( grid ) => ( {
					count: getComputedStyle( grid ).gridTemplateColumns.split(
						' '
					).length,
					cardWidth: Math.round(
						grid.firstElementChild.getBoundingClientRect().width
					),
					gridWidth: Math.round( grid.getBoundingClientRect().width ),
					containerWidth: Math.round(
						grid.parentElement.getBoundingClientRect().width
					),
				} ) );
		assert.deepEqual( await imageColumns(), {
			count: 3,
			cardWidth: 517,
			gridWidth: 1600,
			containerWidth: 1600,
		} );
		await layoutPage.setViewportSize( { width: 950, height: 800 } );
		assert.equal( ( await imageColumns() ).count, 2 );
		assert.equal(
			( await imageColumns() ).gridWidth,
			( await imageColumns() ).containerWidth
		);
		await layoutPage.setViewportSize( { width: 500, height: 800 } );
		assert.equal( ( await imageColumns() ).count, 1 );
		assert.equal(
			( await imageColumns() ).gridWidth,
			( await imageColumns() ).containerWidth
		);
		await layoutPage.setViewportSize( { width: 1920, height: 800 } );
		await layoutPage
			.locator( '.rise-lp__services-grid--image' )
			.evaluate( ( grid ) => {
				grid.classList.add( 'rise-lp__services-grid--image-four' );
				grid.append( grid.firstElementChild.cloneNode( true ) );
			} );
		assert.deepEqual( await imageColumns(), {
			count: 3,
			cardWidth: 517,
			gridWidth: 1600,
			containerWidth: 1600,
		} );
		await layoutPage
			.locator( '.rise-lp__services-grid--image' )
			.evaluate( ( grid ) => {
				grid.closest( '.rise-lp' ).style.setProperty(
					'--rise-content-width',
					'1800px'
				);
			} );
		assert.deepEqual( await imageColumns(), {
			count: 4,
			cardWidth: 432,
			gridWidth: 1800,
			containerWidth: 1800,
		} );
		await layoutPage.setViewportSize( { width: 1600, height: 800 } );
		assert.equal( ( await imageColumns() ).count, 3 );
		assert.equal(
			( await imageColumns() ).gridWidth,
			( await imageColumns() ).containerWidth
		);
		await layoutPage.setViewportSize( { width: 950, height: 800 } );
		assert.equal( ( await imageColumns() ).count, 2 );
		assert.equal(
			( await imageColumns() ).gridWidth,
			( await imageColumns() ).containerWidth
		);
		await layoutPage.setViewportSize( { width: 500, height: 800 } );
		assert.equal( ( await imageColumns() ).count, 1 );
		assert.equal(
			( await imageColumns() ).gridWidth,
			( await imageColumns() ).containerWidth
		);
		assert(
			await layoutPage.evaluate(
				() => document.documentElement.scrollWidth <= innerWidth
			)
		);
		await layoutPage.setViewportSize( { width: 1100, height: 800 } );
		const controlsPage = await browser.newPage( {
			viewport: { width: 1100, height: 800 },
		} );
		await controlsPage.setContent(
			`<style>${ css }</style><div class="rise-lp rise-lp--physio rise-lp--button-motion-slide" style="--rise-primary:#61ffd6;--rise-button-text:#000;--rise-background:#080808;--rise-surface:#202020;--rise-text:#fff;--rise-services-columns:2"><section class="rise-lp__section rise-lp__services rise-lp__section--bg-none"><div class="rise-lp__container"><div class="rise-lp__services-grid rise-lp__services-grid--pricing"><article class="rise-lp__service-card"><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">No price</h3><a class="rise-lp__button" href="#">Schedule</a></div></article><article class="rise-lp__service-card"><div class="rise-lp__service-content"><h3 class="rise-lp__card-title">With price</h3><div class="rise-lp__service-price-block"><span class="rise-lp__service-price">EUR 75</span></div><a class="rise-lp__button" href="#">Book now</a></div></article></div><div class="rise-lp__slider-controls"><button class="rise-lp__button rise-lp__button--secondary"><span class="rise-lp__button-label">Previous</span><span class="rise-lp__button-copy">Previous</span></button><button class="rise-lp__button rise-lp__button--secondary">Next</button></div></div></section></div>`
		);
		const pricingButtons = controlsPage.locator(
			'.rise-lp__services-grid--pricing .rise-lp__button'
		);
		for ( const index of [ 0, 1 ] ) {
			const gap = await pricingButtons
				.nth( index )
				.evaluate( ( button ) => {
					const card = button.closest( '.rise-lp__service-card' );
					return Math.round(
						card.getBoundingClientRect().bottom -
							button.getBoundingClientRect().bottom
					);
				} );
			assert(
				gap > 0 && gap <= 24,
				`Pricing CTA ${ index + 1 } is at the card bottom`
			);
		}
		assert.equal(
			await pricingButtons
				.first()
				.evaluate(
					( button ) => getComputedStyle( button ).backgroundColor
				),
			'rgb(97, 255, 214)'
		);
		await pricingButtons.first().hover();
		await controlsPage.waitForTimeout( 250 );
		assert.deepEqual(
			await pricingButtons
				.first()
				.evaluate( ( button ) => [
					getComputedStyle( button ).backgroundColor,
					getComputedStyle( button ).color,
				] ),
			[ 'rgba(0, 0, 0, 0)', 'rgb(97, 255, 214)' ]
		);
		const previous = controlsPage
			.locator( '.rise-lp__slider-controls button' )
			.first();
		assert.equal(
			await previous.evaluate(
				( button ) => getComputedStyle( button ).color
			),
			'rgb(97, 255, 214)'
		);
		await previous.hover();
		await controlsPage.waitForTimeout( 250 );
		assert.deepEqual(
			await previous.evaluate( ( button ) => [
				getComputedStyle( button ).backgroundColor,
				getComputedStyle( button ).color,
				getComputedStyle(
					button.querySelector( '.rise-lp__button-copy' )
				).color,
			] ),
			[ 'rgb(97, 255, 214)', 'rgb(0, 0, 0)', 'rgb(0, 0, 0)' ]
		);
		await controlsPage
			.locator( '.rise-lp__services' )
			.evaluate( ( section ) => {
				section.classList.replace(
					'rise-lp__section--bg-none',
					'rise-lp__section--bg-surface'
				);
			} );
		await controlsPage.mouse.move( 0, 0 );
		assert.equal(
			await previous.evaluate(
				( button ) => getComputedStyle( button ).color
			),
			'rgb(255, 255, 255)'
		);
		await controlsPage.locator( '.rise-lp' ).evaluate( ( rootElement ) => {
			rootElement.classList.replace(
				'rise-lp--physio',
				'rise-lp--fitness-buttons'
			);
			rootElement.style.setProperty( '--rise-primary', '#ec1c2b' );
			rootElement
				.querySelector( '.rise-lp__services' )
				.classList.replace(
					'rise-lp__section--bg-surface',
					'rise-lp__section--bg-none'
				);
		} );
		await pricingButtons.first().hover();
		await controlsPage.waitForTimeout( 250 );
		assert.deepEqual(
			await pricingButtons
				.first()
				.evaluate( ( button ) => [
					getComputedStyle( button ).backgroundColor,
					getComputedStyle( button ).color,
				] ),
			[ 'rgba(0, 0, 0, 0)', 'rgb(236, 28, 43)' ]
		);
		assert.equal(
			await previous.evaluate(
				( button ) => getComputedStyle( button ).color
			),
			'rgb(255, 255, 255)'
		);
		if ( process.argv[ 2 ] ) {
			await layoutPage.screenshot( {
				path: process.argv[ 2 ],
				fullPage: true,
			} );
		}
		console.log(
			'Services slider navigation, looping, counter, autoplay, and mobile overflow passed.'
		);
	} finally {
		await browser.close();
	}
} )().catch( ( error ) => {
	console.error( error );
	process.exitCode = 1;
} );
