<?php
/**
 * Communications protocol.
 * This is used by dumpTextPass.php when the --spawn option is present.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

// @codeCoverageIgnoreStart
require_once __DIR__ . '/Maintenance.php';
// @codeCoverageIgnoreEnd

use MediaWiki\MainConfigNames;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Settings\SettingsBuilder;
use MediaWiki\Storage\BlobAccessException;
use MediaWiki\Storage\BlobStore;
use MediaWiki\Storage\SqlBlobStore;

/**
 * Maintenance script used to fetch page text in a subprocess.
 *
 * @ingroup Maintenance
 */
class FetchText extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Fetch the raw revision blob from a blob address.\n" .
			"Integer IDs are interpreted as referring to text.old_id for backwards compatibility.\n" .
			"NOTE: Export transformations are NOT applied. " .
			"This is left to dumpTextPass.php"
		);
	}

	public function finalSetup( SettingsBuilder $settingsBuilder ) {
		// This script should always try to run all db queries in the 'dump' group if such
		// a group exists, just like the BackupDumper and TextPassDumper modules.
		// To account for parts of MediaWiki that get their own db connection outside of
		// Maintenance::getDB(), we set this global variable so that they will attempt
		// to use this group.
		$settingsBuilder->putConfigValue( MainConfigNames::DBDefaultGroup, 'dump' );
		// do this last so that options can override

		parent::finalSetup( $settingsBuilder );
	}

	/**
	 * @return BlobStore
	 */
	private function getBlobStore() {
		return $this->getServiceContainer()->getBlobStore();
	}

	/**
	 * returns a string containing the following in order:
	 *   textid
	 *   \n
	 *   length of text (-1 on error = failure to retrieve/unserialize/gunzip/etc)
	 *   \n
	 *   text  (may be empty)
	 *
	 * note that the text string itself is *not* followed by newline
	 */
	public function execute() {
		$stdin = $this->getStdin();
		while ( !feof( $stdin ) ) {
			$line = fgets( $stdin );
			if ( $line === false ) {
				// We appear to have lost contact...
				break;
			}
			$blobAddress = trim( $line );

			// Plain integers are supported for backwards compatibility with pre-MCR dumps.
			if ( strpos( $blobAddress, ':' ) === false && is_numeric( $blobAddress ) ) {
				$blobAddress = SqlBlobStore::makeAddressFromTextId( intval( $blobAddress ) );
			}

			try {
				$text = $this->getBlobStore()->getBlob( $blobAddress );
				$textLen = strlen( $text );
			} catch ( BlobAccessException | InvalidArgumentException ) {
				// XXX: log $ex to stderr?
				$textLen = '-1';
				$text = '';
			}

			$this->output( $blobAddress . "\n" . $textLen . "\n" . $text );
		}
	}

}

// @codeCoverageIgnoreStart
$maintClass = FetchText::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
