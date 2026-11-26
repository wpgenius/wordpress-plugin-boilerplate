/**
 * Adds the direct-access guard to the build/<block>/*.asset.php files that
 * wp-scripts generates, so every PHP file the plugin ships has one (WordPress.org
 * reviewers ask for it). Runs after `npm run build` (the "postbuild" script);
 * running it again changes nothing.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const guard = "defined( 'ABSPATH' ) || exit;";
const root = path.join( __dirname, '..', 'build' );

function walk( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		return [];
	}
	return fs.readdirSync( dir, { withFileTypes: true } ).flatMap( ( entry ) => {
		const full = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			return walk( full );
		}
		return entry.name.endsWith( '.asset.php' ) ? [ full ] : [];
	} );
}

for ( const file of walk( root ) ) {
	const source = fs.readFileSync( file, 'utf8' );
	if ( source.includes( guard ) || ! source.startsWith( '<?php' ) ) {
		continue;
	}
	fs.writeFileSync( file, source.replace( /^<\?php\s*/, `<?php ${ guard } ` ) );
}
