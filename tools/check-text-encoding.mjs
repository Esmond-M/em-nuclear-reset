import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { extname, relative, resolve } from 'node:path';
import { TextDecoder } from 'node:util';

const rootDirectory = resolve( import.meta.dirname, '..' );
const textExtensions = new Set( [
	'.json',
	'.md',
	'.mjs',
	'.php',
	'.ps1',
	'.txt',
	'.xml',
	'.yaml',
	'.yml',
] );
const mojibakePattern = /[\u00c2\u00c3\u00e2][\u0080-\u00ff\u20ac\u201a-\u201e\u2020\u2021\u02c6\u2030\u0160\u2039\u0152\u017d\u2018-\u201e\u2022\u2013\u2014\u02dc\u2122\u0161\u203a\u0153\u017e\u0178]/u;
const utf8Decoder = new TextDecoder( 'utf-8', { fatal: true } );

function getFilesToCheck() {
	const [ option, filePath ] = process.argv.slice( 2 );

	if ( undefined === option ) {
		return execFileSync( 'git', [ 'ls-files', '-z' ], {
			cwd: rootDirectory,
			encoding: 'buffer',
		} )
			.toString( 'utf8' )
			.split( '\0' )
			.filter( Boolean )
			.filter( ( file ) => textExtensions.has( extname( file ) ) )
			.map( ( file ) => resolve( rootDirectory, file ) );
	}

	if ( '--file' !== option || ! filePath || 4 !== process.argv.length ) {
		throw new Error( 'Usage: node tools/check-text-encoding.mjs [--file <path>]' );
	}

	return [ resolve( filePath ) ];
}

function getLocation( text, index ) {
	const precedingText = text.slice( 0, index );
	const lastLineBreak = precedingText.lastIndexOf( '\n' );

	return {
		line: precedingText.split( '\n' ).length,
		column: index - lastLineBreak,
	};
}

const errors = [];

for ( const filePath of getFilesToCheck() ) {
	const bytes = readFileSync( filePath );
	const displayPath = relative( rootDirectory, filePath ) || filePath;

	if ( 0xef === bytes[ 0 ] && 0xbb === bytes[ 1 ] && 0xbf === bytes[ 2 ] ) {
		errors.push( `${ displayPath }:1:1 UTF-8 BOM is not allowed.` );
	}

	let text;

	try {
		text = utf8Decoder.decode( bytes );
	} catch ( error ) {
		errors.push( `${ displayPath }: Invalid UTF-8 (${ error.message }).` );
		continue;
	}

	const replacementCharacterIndex = text.indexOf( '\ufffd' );

	if ( -1 !== replacementCharacterIndex ) {
		const location = getLocation( text, replacementCharacterIndex );
		errors.push( `${ displayPath }:${ location.line }:${ location.column } Replacement character found.` );
	}

	const mojibakeMatch = mojibakePattern.exec( text );

	if ( mojibakeMatch ) {
		const location = getLocation( text, mojibakeMatch.index );
		errors.push( `${ displayPath }:${ location.line }:${ location.column } Possible mojibake sequence "${ mojibakeMatch[ 0 ] }" found.` );
	}
}

if ( errors.length ) {
	console.error( 'Text encoding validation failed:' );
	errors.forEach( ( error ) => console.error( `- ${ error }` ) );
	process.exitCode = 1;
} else {
	console.log( 'Text encoding validation passed.' );
}