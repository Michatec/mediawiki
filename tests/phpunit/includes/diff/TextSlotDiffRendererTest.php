<?php

use MediaWiki\MainConfigNames;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers TextSlotDiffRenderer
 */
class TextSlotDiffRendererTest extends MediaWikiIntegrationTestCase {

	public function testGetExtraCacheKeys() {
		$slotDiffRenderer = $this->getTextSlotDiffRenderer();
		$key = $slotDiffRenderer->getExtraCacheKeys();
		$slotDiffRenderer->setEngine( TextSlotDiffRenderer::ENGINE_WIKIDIFF2_INLINE );
		$inlineKey = $slotDiffRenderer->getExtraCacheKeys();
		$this->assertSame( [], $key );
		$this->assertSame( $inlineKey, [ phpversion( 'wikidiff2' ), 'inline' ] );
	}

	/**
	 * @dataProvider provideGetDiff
	 * @param array|null $oldContentArgs To pass to makeContent() (if not null)
	 * @param array|null $newContentArgs
	 * @param string|Exception $expectedResult
	 */
	public function testGetDiff(
		?array $oldContentArgs, ?array $newContentArgs, $expectedResult
	) {
		$this->mergeMwGlobalArrayValue( 'wgContentHandlers', [
			'testing' => DummyContentHandlerForTesting::class,
			'testing-nontext' => DummyNonTextContentHandler::class,
		] );

		$oldContent = $oldContentArgs ? self::makeContent( ...$oldContentArgs ) : null;
		$newContent = $newContentArgs ? self::makeContent( ...$newContentArgs ) : null;

		if ( $expectedResult instanceof Exception ) {
			$this->expectException( get_class( $expectedResult ) );
			$this->expectExceptionMessage( $expectedResult->getMessage() );
		}

		$slotDiffRenderer = $this->getTextSlotDiffRenderer();
		$diff = $slotDiffRenderer->getDiff( $oldContent, $newContent );
		if ( $expectedResult instanceof Exception ) {
			return;
		}
		$plainDiff = $this->getPlainDiff( $diff );
		$this->assertSame( $expectedResult, $plainDiff );
	}

	public static function provideGetDiff() {
		return [
			'same text' => [
				[ "aaa\nbbb\nccc" ],
				[ "aaa\nbbb\nccc" ],
				"",
			],
			'different text' => [
				[ "aaa\nbbb\nccc" ],
				[ "aaa\nxxx\nccc" ],
				" aaa aaa\n-bbb+xxx\n ccc ccc",
			],
			'no right content' => [
				[ "aaa\nbbb\nccc" ],
				null,
				"-aaa+ \n-bbb \n-ccc ",
			],
			'no left content' => [
				null,
				[ "aaa\nbbb\nccc" ],
				"- +aaa\n +bbb\n +ccc",
			],
			'no content' => [
				null,
				null,
				new InvalidArgumentException( '$oldContent and $newContent cannot both be null' ),
			],
			'non-text left content' => [
				[ '', 'testing-nontext' ],
				[ "aaa\nbbb\nccc" ],
				new IncompatibleDiffTypesException( 'testing-nontext', 'text' ),
			],
			'non-text right content' => [
				[ "aaa\nbbb\nccc" ],
				[ '', 'testing-nontext' ],
				new ParameterTypeException( '$newContent', 'TextContent|null' ),
			],
		];
	}

	// no separate test for getTextDiff() as getDiff() is just a thin wrapper around it

	/**
	 * @return TextSlotDiffRenderer
	 */
	private function getTextSlotDiffRenderer() {
		$slotDiffRenderer = new TextSlotDiffRenderer();
		$slotDiffRenderer->setStatsdDataFactory( new NullStatsdDataFactory() );
		$slotDiffRenderer->setLanguage(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );
		$slotDiffRenderer->setEngine( TextSlotDiffRenderer::ENGINE_PHP );
		return $slotDiffRenderer;
	}

	/**
	 * Convert a HTML diff to a human-readable format and hopefully make the test less fragile.
	 * @param string $diff
	 * @return string
	 */
	private function getPlainDiff( $diff ) {
		$replacements = [
			html_entity_decode( '&nbsp;' ) => ' ',
			html_entity_decode( '&minus;' ) => '-',
		];
		// Preserve markers when stripping tags
		$diff = str_replace( '<td class="diff-marker"></td>', ' ', $diff );
		$diff = preg_replace( '@<td colspan="2"( class="(?:diff-side-deleted|diff-side-added)")?></td>@', ' ', $diff );
		$diff = preg_replace( '/data-marker="([^"]*)">/', '>$1', $diff );
		return str_replace( array_keys( $replacements ), array_values( $replacements ),
			trim( strip_tags( $diff ), "\n" ) );
	}

	/**
	 * @param string $str
	 * @param string $model
	 * @return null|TextContent
	 */
	private static function makeContent( $str, $model = CONTENT_MODEL_TEXT ) {
		return ContentHandler::makeContent( $str, null, $model );
	}

	public static function provideGetTablePrefix() {
		return [
			'php' => [
				TextSlotDiffRenderer::ENGINE_PHP,
				[
					TextSlotDiffRenderer::INLINE_LEGEND_KEY => '<div></div>',
					TextSlotDiffRenderer::INLINE_SWITCHER_KEY => null
				]
			],
			'wikidiff2' => [
				TextSlotDiffRenderer::ENGINE_WIKIDIFF2,
				[
					TextSlotDiffRenderer::INLINE_LEGEND_KEY =>
						'class="mw-diff-inline-legend mw-diff-element-hidden"',
					TextSlotDiffRenderer::INLINE_SWITCHER_KEY => 'mw-diffPage-inlineToggle-container'
				]
			],
			'inline' => [
				TextSlotDiffRenderer::ENGINE_WIKIDIFF2_INLINE,
				[
					TextSlotDiffRenderer::INLINE_LEGEND_KEY =>
						'class="mw-diff-inline-legend".*\(diff-inline-tooltip-ins\)',
					TextSlotDiffRenderer::INLINE_SWITCHER_KEY => 'mw-diffPage-inlineToggle-container'
				]
			]
		];
	}

	/**
	 * @dataProvider provideGetTablePrefix
	 * @param string $engine
	 * @param string[] $expectedPatterns
	 */
	public function testGetTablePrefix( $engine, $expectedPatterns ) {
		OOUI\Theme::setSingleton( new OOUI\BlankTheme() );
		$this->overrideConfigValue( MainConfigNames::ShowDiffToggleSwitch, true );

		$slotDiffRenderer = $this->getTextSlotDiffRenderer();
		$slotDiffRenderer->setHookContainer( $this->createHookContainer() );
		$slotDiffRenderer->setEngine( $engine );

		$context = new RequestContext;
		$context->setLanguage( 'qqx' );

		$title = $this->getServiceContainer()->getTitleFactory()->newFromText( 'Test' );
		$result = $slotDiffRenderer->getTablePrefix( $context, $title );
		$this->assertSameSize( $expectedPatterns, $result );
		foreach ( $expectedPatterns as $key => $pattern ) {
			if ( $pattern === null ) {
				$this->assertNull( $result[$key], "\$result[$key]" );
			} else {
				$this->assertMatchesRegularExpression(
					"#$pattern#", $result[$key], "\$result[$key]" );
			}
		}
	}
}
