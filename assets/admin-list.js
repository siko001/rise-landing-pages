/* Keep the native Pages table and its accessible search and bulk actions. */
document.addEventListener( 'DOMContentLoaded', () => {
	const config = window.riseLandingList;
	if ( ! config ) {
		return;
	}
	const title = document.querySelector( '.wrap h1.wp-heading-inline' );
	const add = document.querySelector( '.wrap .page-title-action' );
	if ( title ) {
		title.textContent = config.title;
	}
	if ( add ) {
		add.textContent = config.addLabel;
		add.href = config.createUrl;
	}
} );
