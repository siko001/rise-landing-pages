/** Standalone settings-form interaction check: node tests/settings-ui.cjs */
const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const { readFileSync } = require( 'node:fs' );
const { JSDOM } = require( 'jsdom' );

const php = `define('ABSPATH', '/tmp/'); define('RISE_LP_URL', 'https://example.test/plugins/rise-landing-pages/'); function __($text, $domain = null) { return $text; } require 'src/Settings/Settings.php'; $settings = RiseLandingPages\\Settings\\Settings::class; echo json_encode(array('presets' => $settings::presets(), 'presetLogoUrls' => array('physio' => $settings::preset_logo_url('physio'), 'fitness' => $settings::preset_logo_url('fitness'), 'medical' => $settings::preset_logo_url('medical'))));`;
const fixture = JSON.parse( execFileSync( 'php', [ '-r', php ], { encoding: 'utf8' } ) );
const { presets, presetLogoUrls } = fixture;
const singleFields = [ 'address', 'map_url', 'phone', 'additional_phones', 'email', 'company_details' ];
const locationFields = [ 'name', 'address', 'map_url', 'phones', 'email', 'company_details' ];
const fields = singleFields.map( ( key ) => `<tr><td><textarea id="rise-setting-${ key }"></textarea></td></tr>` ).join( '' );
const linkFields = [ 'privacy_url', 'terms_url', 'about_url' ].map( ( key ) => `<tr><td><input id="rise-setting-${ key }"></td></tr>` ).join( '' );
const locationMarkup = `<details class="rise-settings-companies__row"><summary class="rise-settings-companies__heading"><button type="button" class="rise-settings-companies__handle" draggable="true"></button><strong class="rise-settings-companies__title"></strong></summary><div><button type="button" class="rise-settings-companies__remove">Remove</button>${ locationFields.map( ( key ) => `<label for="rise-company-__INDEX__-${ key }">${ key }</label><textarea id="rise-company-__INDEX__-${ key }" name="rise_landing_settings[companies][__INDEX__][${ key }]"></textarea>` ).join( '' ) }</div></details>`;
const mediaMarkup = `<div class="rise-settings-media" data-logo-role="primary"><input id="rise-setting-logo_preset" value=""><input class="rise-settings-media__value" id="rise-setting-logo_id" value="42"><div class="rise-settings-media__preview"><img src="https://example.test/custom.png"></div><button type="button" class="rise-settings-media__choose">Choose</button><button type="button" class="rise-settings-media__remove">Remove</button></div><div class="rise-settings-media"><input class="rise-settings-media__value" id="rise-setting-alternate_logo_id" value="72"><div class="rise-settings-media__preview"><img src="https://example.test/alternate.png"></div><button type="button" class="rise-settings-media__choose">Choose</button><button type="button" class="rise-settings-media__remove">Remove</button></div>`;
const html = `<form><details class="rise-settings-presets"><select id="rise-brand-preset"><option value="generic">Generic</option><option value="physio">Physio</option><option value="fitness">Fitness</option><option value="medical">Medical</option></select><button type="button" class="rise-settings-presets__apply">Apply</button><span class="rise-settings-presets__status"></span></details>${ mediaMarkup }<input id="rise-setting-brand_name"><input id="rise-setting-content_width"><input id="rise-setting-cta_label"><input id="rise-setting-booking_url" value="https://custom.example/book"><table><tbody><tr><td><select id="rise-setting-contact_mode"><option value="single">Single</option><option value="multiple">Multiple</option></select></td></tr>${ fields }<tr><td><div class="rise-settings-companies"><div class="rise-settings-companies__rows"></div><template class="rise-settings-companies__template">${ locationMarkup }</template><button type="button" class="rise-settings-companies__add">Add</button><span class="rise-settings-companies__status"></span></div></td></tr>${ linkFields }<tr><td><div class="rise-settings-links"><div class="rise-settings-links__rows"></div><button type="button" class="rise-settings-links__add">Add</button><span class="rise-settings-links__status"></span></div></td></tr></tbody></table></form>`;
const dom = new JSDOM( html, { runScripts: 'outside-only' } );
const { window } = dom;
window.riseLandingSettings = {
	presets,
	presetLogoUrls,
	noLogo: 'No logo selected.',
	presetApplied: 'Applied',
	companyLabel: 'Location',
	companyAdded: 'Added',
	companyRemoved: 'Removed',
	locationMoved: 'Moved',
	linkLabel: 'Link label',
	linkUrl: 'Link address',
	remove: 'Remove',
};
window.eval( readFileSync( 'assets/settings.js', 'utf8' ) );
const doc = window.document;
const select = doc.querySelector( '#rise-brand-preset' );
const apply = doc.querySelector( '.rise-settings-presets__apply' );
const rows = doc.querySelector( '.rise-settings-companies__rows' );
const mode = doc.querySelector( '#rise-setting-contact_mode' );
const footerLinks = doc.querySelector( '.rise-settings-links__rows' );
const cards = () => [ ...rows.children ];
const names = () => cards().map( ( row ) => row.querySelector( '[name$="[name]"]' ).value );

select.value = 'physio';
apply.click();
assert.equal( mode.value, 'multiple' );
assert.equal( doc.querySelector( '#rise-setting-logo_preset' ).value, 'physio' );
assert.equal( doc.querySelector( '#rise-setting-logo_id' ).value, '0' );
assert.equal( doc.querySelector( '#rise-setting-alternate_logo_id' ).value, '0' );
assert.equal( doc.querySelector( '[data-logo-role="primary"] .rise-settings-media__image' ).src, presetLogoUrls.physio );
assert.deepEqual( names(), [ 'Sliema', 'Qormi', 'Balzan' ] );
assert.ok( cards().every( ( card ) => ! card.open ) );
assert.equal( cards()[ 0 ].querySelector( '.rise-settings-companies__title' ).textContent, 'Sliema' );
assert.equal( cards()[ 1 ].querySelector( '[name$="[map_url]"]' ).value, 'https://maps.app.goo.gl/czLrS6UJ771XG7wC7' );
assert.equal( cards()[ 2 ].querySelector( '[name$="[phones]"]' ).value, '(+356) 7952 5280' );
assert.equal( doc.querySelector( '#rise-setting-content_width' ).value, '1860' );
assert.equal( doc.querySelector( '#rise-setting-cta_label' ).value, 'Book an appointment' );
assert.equal( doc.querySelector( '#rise-setting-booking_url' ).value, 'https://custom.example/book' );
assert.ok( doc.querySelector( '#rise-setting-privacy_url' ).value.includes( 'risephysio.mt/privacy-policy/' ) );
assert.equal( doc.querySelector( '#rise-setting-about_url' ).value, 'https://risephysio.mt/about-us/' );
assert.deepEqual( [ ...footerLinks.children ].map( ( row ) => row.querySelector( 'input' ).value ), [ 'Facebook', 'Instagram' ] );
assert.equal( footerLinks.children[ 0 ].querySelectorAll( 'input' )[ 1 ].value, 'https://www.facebook.com/wearerisephysio/' );

const handle = cards()[ 2 ].querySelector( '.rise-settings-companies__handle' );
const drag = new window.Event( 'dragstart', { bubbles: true } );
Object.defineProperty( drag, 'dataTransfer', { value: { setData() {}, effectAllowed: '' } } );
handle.dispatchEvent( drag );
const drop = new window.Event( 'drop', { bubbles: true, cancelable: true } );
Object.defineProperty( drop, 'clientY', { value: -1 } );
cards()[ 0 ].dispatchEvent( drop );
assert.deepEqual( names(), [ 'Balzan', 'Sliema', 'Qormi' ] );
assert.deepEqual( cards().map( ( row ) => row.querySelector( '[name$="[name]"]' ).name ), [ 0, 1, 2 ].map( ( i ) => `rise_landing_settings[companies][${ i }][name]` ) );
assert.equal( cards()[ 0 ].querySelector( 'label[for]' ).htmlFor, 'rise-company-0-name' );
assert.deepEqual( [ ...new window.FormData( doc.querySelector( 'form' ) ) ].filter( ( [ key ] ) => key.endsWith( '[name]' ) ).map( ( [ , value ] ) => value ), [ 'Balzan', 'Sliema', 'Qormi' ] );

const key = new window.KeyboardEvent( 'keydown', { key: 'ArrowDown', bubbles: true, cancelable: true } );
cards()[ 0 ].querySelector( '.rise-settings-companies__handle' ).dispatchEvent( key );
assert.deepEqual( names(), [ 'Sliema', 'Balzan', 'Qormi' ] );

select.value = 'fitness';
apply.click();
assert.equal( mode.value, 'single' );
assert.equal( doc.querySelector( '#rise-setting-logo_preset' ).value, 'fitness' );
assert.equal( doc.querySelector( '[data-logo-role="primary"] .rise-settings-media__image' ).src, presetLogoUrls.fitness );
assert.equal( doc.querySelector( '#rise-setting-map_url' ).value, 'https://maps.app.goo.gl/q1tpWDLgFPGDr2JbA' );
assert.equal( doc.querySelector( '#rise-setting-phone' ).value, '(+356) 79525235' );
assert.equal( doc.querySelector( '#rise-setting-cta_label' ).value, 'Book Now' );
assert.equal( doc.querySelector( '#rise-setting-booking_url' ).value, 'https://custom.example/book' );
assert.equal( doc.querySelector( '#rise-setting-terms_url' ).value, 'https://risefitness.mt/terms-and-conditions/' );
assert.equal( footerLinks.children[ 1 ].querySelectorAll( 'input' )[ 1 ].value, 'https://www.instagram.com/wearerisefitness/' );
assert.deepEqual( names(), [ 'Sliema', 'Balzan', 'Qormi' ] );

select.value = 'medical';
apply.click();
assert.equal( mode.value, 'single' );
assert.equal( doc.querySelector( '#rise-setting-logo_preset' ).value, 'medical' );
assert.equal( doc.querySelector( '[data-logo-role="primary"] .rise-settings-media__image' ).src, presetLogoUrls.medical );
assert.equal( doc.querySelector( '#rise-setting-map_url' ).value, 'https://maps.app.goo.gl/q1tpWDLgFPGDr2JbA' );
assert.equal( doc.querySelector( '#rise-setting-cta_label' ).value, 'BOOK MEDICAL IMAGING' );
assert.equal( doc.querySelector( '#rise-setting-booking_url' ).value, 'https://risephysio.uk1.cliniko.com/bookings?business_id=1916555807957194716' );
assert.equal( doc.querySelector( '#rise-setting-terms_url' ).value, 'https://risefitness.mt/terms-and-conditions/' );

select.value = 'generic';
apply.click();
assert.equal( doc.querySelector( '#rise-setting-content_width' ).value, '1860' );
assert.equal( doc.querySelector( '#rise-setting-logo_preset' ).value, '' );
assert.equal( doc.querySelector( '[data-logo-role="primary"] .rise-settings-media__preview' ).textContent, 'No logo selected.' );
assert.equal( doc.querySelector( '#rise-setting-terms_url' ).value, 'https://risefitness.mt/terms-and-conditions/' );

mode.value = 'multiple';
mode.dispatchEvent( new window.Event( 'change' ) );
doc.querySelector( '.rise-settings-companies__add' ).click();
assert.equal( cards()[ 3 ].open, true );
assert.equal( cards()[ 3 ].querySelector( '.rise-settings-companies__title' ).textContent, 'Location 4' );
cards()[ 3 ].querySelector( '[name$="[name]"]' ).value = 'New clinic';
cards()[ 3 ].querySelector( '[name$="[name]"]' ).dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
assert.equal( cards()[ 3 ].querySelector( '.rise-settings-companies__title' ).textContent, 'New clinic' );
cards()[ 3 ].querySelector( '.rise-settings-companies__remove' ).click();
assert.deepEqual( names(), [ 'Sliema', 'Balzan', 'Qormi' ] );
console.log( 'PASS preset logos, locations, collapsed headers, drag and keyboard order, field indices, and mode preservation' );
