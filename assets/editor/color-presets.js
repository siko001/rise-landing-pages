import { __ } from '@wordpress/i18n';
import { safeOutlineColor } from './outline-utils';

export function colorPresets() {
	const colors = [
		{
			name: __( 'Primary', 'rise-landing-pages' ),
			color:
				window.riseLandingEditor?.settings?.primary_color || '#006B64',
		},
		{ name: __( 'Rise red', 'rise-landing-pages' ), color: '#EC1C2B' },
		{ name: __( 'Medical blue', 'rise-landing-pages' ), color: '#002E75' },
		{ name: __( 'White', 'rise-landing-pages' ), color: '#ffffff' },
		{ name: __( 'Black', 'rise-landing-pages' ), color: '#000000' },
		...( window.riseLandingEditor?.colorPresets || [] ),
	];
	const seen = new Set();
	return colors.reduce( ( result, preset ) => {
		const color = safeOutlineColor( preset.color );
		if ( ! color || seen.has( color ) ) {
			return result;
		}
		seen.add( color );
		result.push( { ...preset, color } );
		return result;
	}, [] );
}
