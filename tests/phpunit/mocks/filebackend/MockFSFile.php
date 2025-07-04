<?php
/**
 * Mock of a filesystem file.
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
 * @ingroup FileBackend
 */

use Wikimedia\FileBackend\FSFile\FSFile;

/**
 * Class representing an in-memory fake file.
 * This is intended for unit testing / development when you do not want
 * to hit the filesystem.
 *
 * It reimplements abstract methods with some hardcoded values. Might
 * not be suitable for all tests but is good enough for the parser tests.
 *
 * @ingroup FileBackend
 */
class MockFSFile extends FSFile {
	/** @inheritDoc */
	protected $sha1Base36 = null;

	/** @inheritDoc */
	public function exists() {
		return true;
	}

	/**
	 * August 22 – The theft of the Mona Lisa is discovered in the Louvre."
	 * T22281
	 * @return int
	 */
	public function getSize() {
		return 1911;
	}

	/** @inheritDoc */
	public function getTimestamp() {
		return wfTimestamp( TS_MW );
	}

	/** @inheritDoc */
	public function getProps( $ext = true ) {
		return [
			'fileExists' => $this->exists(),
			'size' => $this->getSize(),
			'file-mime' => 'text/mock',
			'sha1' => $this->getSha1Base36(),
		];
	}

	/** @inheritDoc */
	public function getSha1Base36( $recache = false ) {
		return '1234567890123456789012345678901';
	}
}
