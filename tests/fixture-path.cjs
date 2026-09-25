const crypto = require( 'crypto' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const pluginPath = fs.realpathSync( path.join( __dirname, '..' ) ) + path.sep;
const key = crypto.createHash( 'sha256' ).update( pluginPath ).digest( 'hex' );
module.exports = ( name ) => path.join( os.tmpdir(), `rise-landing-pages-${ key }-${ name }.json` );
