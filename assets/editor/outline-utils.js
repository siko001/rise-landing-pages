const NAMED_COLORS = new Set( [
	'white',
	'black',
	'red',
	'blue',
	'green',
	'yellow',
	'orange',
	'purple',
	'pink',
	'cyan',
	'magenta',
	'gray',
	'grey',
	'navy',
	'teal',
	'aqua',
	'lime',
	'maroon',
	'olive',
	'silver',
	'fuchsia',
	'currentcolor',
] );

export function safeOutlineColor( value ) {
	const color = String( value || '' )
		.trim()
		.toLowerCase();
	return /^#[a-f0-9]{3}(?:[a-f0-9]{3})?$/.test( color ) ||
		NAMED_COLORS.has( color )
		? color
		: '';
}

/**
 * Only replace complete pairs in text; never interpret tokens in HTML attributes.
 * @param {string} html
 */
export function expandOutlineTokens( html ) {
	return String( html || '' )
		.split( /(<[^>]*>)/g )
		.map( ( part, index ) => {
			if ( index % 2 ) {
				return part;
			}
			return part.replace(
				/\[outline(?:\s+color=(?:"([^"]+)"|'([^']+)'|([^\]\s]+)))?\]([\s\S]*?)\[\/outline\]/gi,
				( match, doubleQuoted, singleQuoted, unquoted, content ) => {
					const color = safeOutlineColor(
						doubleQuoted || singleQuoted || unquoted
					);
					const colorAttributes = color
						? ` data-rise-outline-color="${ color }" style="--rise-outline-color:${ color }"`
						: '';
					return `<span class="rise-lp__outline"${ colorAttributes }>${ content }</span>`;
				}
			);
		} )
		.join( '' );
}
