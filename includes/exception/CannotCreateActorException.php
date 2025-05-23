<?php
/**
 * Exception thrown when some operation failed
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
 *
 * @since 1.31
 */

namespace MediaWiki\Exception;

use RuntimeException;
use Wikimedia\NormalizedException\INormalizedException;
use Wikimedia\NormalizedException\NormalizedExceptionTrait;

/**
 * Exception thrown when an actor can't be created.
 * @newable
 */
class CannotCreateActorException extends RuntimeException implements INormalizedException {
	use NormalizedExceptionTrait;

	public function __construct( string $normalizedMessage, array $messageContext = [] ) {
		$this->normalizedMessage = $normalizedMessage;
		$this->messageContext = $messageContext;
		parent::__construct(
			self::getMessageFromNormalizedMessage( $normalizedMessage, $messageContext )
		);
	}
}

/** @deprecated class alias since 1.44 */
class_alias( CannotCreateActorException::class, 'CannotCreateActorException' );
