<?php
/**
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
 */

namespace MediaWiki\Content;

use LogicException;
use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * Content handler for pages with source code as content (e.g. CSS, JavaScript, or JSON).
 *
 * @stable to extend
 * @since 1.24
 * @ingroup Content
 */
abstract class CodeContentHandler extends TextContentHandler {

	/**
	 * Returns the English language, because code is English, and should be handled as such.
	 *
	 * @stable to override
	 *
	 * @param Title $title
	 * @param Content|null $content
	 *
	 * @return Language
	 *
	 * @see ContentHandler::getPageLanguage()
	 */
	public function getPageLanguage( Title $title, ?Content $content = null ) {
		return MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
	}

	/**
	 * Returns the English language, because code is English, and should be handled as such.
	 *
	 * @stable to override
	 *
	 * @param Title $title
	 * @param Content|null $content
	 *
	 * @return Language
	 *
	 * @see ContentHandler::getPageViewLanguage()
	 */
	public function getPageViewLanguage( Title $title, ?Content $content = null ) {
		return MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
	}

	/** @inheritDoc */
	protected function getContentClass() {
		throw new LogicException( 'Subclass must override' );
	}

}
/** @deprecated class alias since 1.43 */
class_alias( CodeContentHandler::class, 'CodeContentHandler' );
