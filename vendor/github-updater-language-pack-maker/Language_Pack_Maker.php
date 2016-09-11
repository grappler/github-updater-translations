<?php
/**
 * Created by PhpStorm.
 * User: afragen
 * Date: 9/9/16
 * Time: 4:25 PM
 */

namespace Fragen\GitHub_Updater;


class Language_Pack_Maker {

	private $locales;

	private $directory_list;

	private $translations;

	private $packages;

	private $root_dir;

	private $language_files_dir;

	private $packages_dir;

	public function __construct() {
		$this->root_dir           = dirname( dirname( __DIR__ ) );
		$this->language_files_dir = $this->root_dir . '/languages';
		$this->packages_dir       = $this->root_dir . '/packages';
		@mkdir( $this->packages_dir, 0777 );

		$this->directory_list = $this->list_directory( $this->language_files_dir );
		$this->translations   = $this->process_directory( $this->directory_list );
		$this->locales        = $this->parse_locales( $this->translations );
		$this->packages       = $this->create_packages();
		$this->create_language_packs();
		$this->create_json();
	}

	public function list_directory( $dir ) {
		$dir_list = array();
		$dir_handle = @opendir( $dir ) or die( "Unable to open $dir" );
		$skip_files = array( '.', '..', '.DS_Store', '.htaccess' );

		while ( false !== ( $file = readdir( $dir_handle ) ) ) {
			if ( ! in_array( $file, $skip_files ) ) {
				if ( false !== stripos( $file, '.pot' ) ) {
					continue;
				}
				$dir_list[] = $file;
			}
		}
		closedir( $dir_handle );

		return $dir_list;
	}

	private function process_directory( $dir_list ) {
		$translation_list = array_map( function( $e ) {
			return pathinfo( $e, PATHINFO_FILENAME );
		}, $dir_list );
		$translation_list = array_unique( $translation_list );

		return $translation_list;
	}


	private function parse_locales( $translations ) {
		$locales = array();
		foreach ( $translations as $translation ) {
			$locales[] = ltrim( strrchr( $translation, '-' ), '-' );
		}

		return $locales;
	}

	private function create_packages() {
		$packages = array();
		foreach ( $this->translations as $translation ) {
			$package = array();
			foreach ( $this->directory_list as $file ) {
				if ( false !== stristr( $file, $translation ) ) {
					$package[] = $this->language_files_dir . '/' . $file;
				}
			}
			$packages[ $translation ] = $package;
		}

		return $packages;
	}

	private function create_language_packs() {
		foreach ( $this->packages as $translation => $files ) {
			$this->create_zip( $files, $this->packages_dir . '/' . $translation . '.zip', true );
		}
	}

	/* creates a compressed zip file */
	/* https://davidwalsh.name/create-zip-php */
	private function create_zip( $files = array(), $destination = '', $overwrite = true ) {
		//if the zip file already exists and overwrite is false, return false
		if ( file_exists( $destination ) && ! $overwrite ) {
			return false;
		}

		//create the archive
		$zip = new \ZipArchive();
		if ( $zip->open( $destination, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE ) !== true ) {
			return false;
		}
		//add the files
		foreach ( $files as $file ) {
			$zip->addFile( $file, basename( $file ) );
		}

		//close the zip -- done!
		$zip->close();

		//check to make sure the file exists
		if ( file_exists( $destination ) ) {
			printf( basename( $destination ) . ' created.' . "\n<br>" );
		} else {
			printf( '<span style="color:#f00">' . basename( $destination ) . ' failed.</span>' . "\n<br>" );
		}
	}

	private function create_json() {
		$packages = $this->list_directory( $this->packages_dir );
		$arr      = array();

		foreach ( $packages as $package ) {
			foreach ( $this->translations as $translation ) {
				if ( false !== stristr( $package, $translation ) ) {
					$arr[ $translation ]['slug']       = stristr( $translation, strrchr( $translation, '-' ), true );
					$arr[ $translation ]['language']   = ltrim( strrchr( $translation, '-' ), '-' );
					$arr[ $translation ]['updated']    = date( 'Y-m-d H:i:s', filemtime( $this->packages[ $translation ][0] ) );
					$arr[ $translation ]['package']    = '/packages/' . $package;
					$arr[ $translation ]['autoupdate'] = '1';
				}
			}
		}

		file_put_contents( $this->root_dir . '/language-pack.json', json_encode( $arr ) );
		printf( "\n<br>" . 'language-pack.json created.' . "\n<br>" );
	}

}

