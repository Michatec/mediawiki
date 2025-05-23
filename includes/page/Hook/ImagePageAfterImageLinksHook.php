<?php

namespace MediaWiki\Page\Hook;

use MediaWiki\Page\ImagePage;

/**
 * This is a hook handler interface, see docs/Hooks.md.
 * Use the hook name "ImagePageAfterImageLinks" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface ImagePageAfterImageLinksHook {
	/**
	 * This hook is called after the image links section on an image
	 * page is built.
	 *
	 * @since 1.35
	 *
	 * @param ImagePage $imagePage ImagePage object ($this)
	 * @param string &$html HTML for the hook to add
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onImagePageAfterImageLinks( $imagePage, &$html );
}
