<?php
/*
Script Name: WordPress Export Splitter
Description: A cheap and dirty script for making Site Exports small enough to upload and import.
Version: 0.1
Author: BrianLayman
Author URI: http://webdevstudios.com/team/brian-layman/
Script URI: http://webdevstudios.com/wordpress/vip-services-support/

Notes: 
	The first file may be larger or smaller than the rest.

Use: Change the constants in the source below, and call the site via the browser

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

define( 'ORIG_FILE', 'example.xml' );
define( 'EXPORT_PREFIX', 'example_' );
define( 'EXPORT_EXT', '.xml' );
define( 'READ_SIZE_BYTES', '100000' ); // How much to read in at a time
define( 'WRITE_SIZE_BYTES', '10000000' ); // When the data reaches this size (or more), it triggers a write.
define( 'COMMON_HEADER_END', '</wp:base_blog_url>' );
define( 'COMMON_FOOTER', '</channel></rss>' );
define( 'LENGTH_OF_CHE', strlen( COMMON_HEADER_END ) );
define( 'ITEM_START', '<item>' );
define( 'LENGTH_OF_IS', strlen( ITEM_START ) );
define( 'ITEM_END', '</item>' );
define( 'LENGTH_OF_IE', strlen( ITEM_END ) );

$header = '';
$xmlContent = '';

function write_data( $header, &$data, $filename ){
	if ( !$handle = fopen( $filename, 'w')) {
		die( "Cannot open file ( $filename)" );
	}

	// Write $somecontent to our opened file.
	if ( fwrite( $handle, $header ) === FALSE ) {
		die( "Cannot write to file ($filename)" );
	}
	if ( fwrite( $handle, $data ) === FALSE ) {
		die( "Cannot write to file ($filename)" );
	}
	if ( fwrite( $handle, COMMON_FOOTER ) === FALSE ) {
		die( "Cannot write to file ($filename)" );
	}
	fclose( $handle );
}

if ( !file_exists( ORIG_FILE ) ) die('File Not Found');

$fHandle = fopen( ORIG_FILE, 'r');
if ( !$fHandle )  die( 'Cannot Open File' );

$fileNumber = 0;
while ( !feof( $fHandle ) ) {
	$xmlContent .= fread( $fHandle, READ_SIZE_BYTES );
		
	if ( $header === '' ) {
		// This is our first time through. We need to do two things. Get the header and get the data up to the point beginnings of the items.
		if ( !$cutOff = strpos( $xmlContent, COMMON_HEADER_END ) ) {
			die('Could not find end of common header in the first file chunk. Chunk size is too small or the file format has changed.');
		}
		$cutOff += LENGTH_OF_CHE;
		
		$header = substr($xmlContent, 0, $cutOff);
		$xmlContent = substr($xmlContent, $cutOff);		
	}

	if ($fileNumber == 0) {
		if ( !$cutOff = strpos( $xmlContent, ITEM_START ) ) {
			continue;
		}
		$cutOff += -1; 

		$curChunk = substr( $xmlContent, 0, $cutOff );
		$xmlContent = substr( $xmlContent, $cutOff );
		$filename = EXPORT_PREFIX . $fileNumber . EXPORT_EXT;
		write_data( $header, $curChunk, $filename );
		echo "File Written: " . $filename . "<br/>";
		$curChunk = NULL;
		$fileNumber++;
	}
	
	while ( strlen( $curChunk ) < WRITE_SIZE_BYTES ) {
		if ( !$cutOff = strpos( $xmlContent, ITEM_END ) ) {
			continue 2;
		}
		
		$cutOff += LENGTH_OF_IE; 
		$curChunk .= substr($xmlContent, 0, $cutOff);
		$xmlContent = substr($xmlContent, $cutOff);
	}
	$filename = EXPORT_PREFIX . $fileNumber . EXPORT_EXT;
	write_data( $header, $curChunk, $filename );
	$curChunk = NULL;
	echo "File Written: " . $filename . "<br/>";
	$fileNumber++;
}

fclose($fHandle);