<?php

namespace MediaWiki\Tests\ParamValidator\TypeDef;

use MediaWiki\ChangeTags\ChangeTagsStore;
use MediaWiki\ParamValidator\TypeDef\TagsDef;
use MediaWikiIntegrationTestCase;
use Wikimedia\Message\DataMessageValue;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\SimpleCallbacks;
use Wikimedia\ParamValidator\ValidationException;

/**
 * @group Database
 * @covers \MediaWiki\ParamValidator\TypeDef\TagsDef
 */
class TagsDefTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->getServiceContainer()->getChangeTagsStore()->defineTag( 'tag1' );
		$this->getServiceContainer()->getChangeTagsStore()->defineTag( 'tag2' );

		// Since the type def shouldn't care about the specific user,
		// remove the right from relevant groups to ensure that it's not
		// checking.
		$this->setGroupPermissions( [
			'*' => [ 'applychangetags' => false ],
			'user' => [ 'applychangetags' => false ],
		] );
	}

	/**
	 * @dataProvider provideValidate
	 * @param mixed $value Value for getCallbacks()
	 * @param mixed|ValidationException $expect Expected result from TypeDef::validate().
	 *  If a ValidationException, it is expected that a ValidationException
	 *  with matching failure code and data will be thrown. Otherwise, the return value must be equal.
	 * @param array $settings Settings array.
	 * @param array $options Options array
	 * @param array[] $expectConds Expected conditions reported. Each array is
	 *  `[ $ex->getFailureCode() ] + $ex->getFailureData()`.
	 */
	public function testValidate(
		$value, $expect, array $settings = [], array $options = [], array $expectConds = []
	) {
		$callbacks = new SimpleCallbacks( [ 'test' => $value ] );
		$typeDef = new TagsDef(
			$callbacks,
			$this->getServiceContainer()->getChangeTagsStore()
		);
		$settings = $typeDef->normalizeSettings( $settings );

		if ( $expect instanceof ValidationException ) {
			try {
				$v = $typeDef->getValue( 'test', $settings, $options );
				$typeDef->validate( 'test', $v, $settings, $options );
				$this->fail( 'Expected exception not thrown' );
			} catch ( ValidationException $ex ) {
				$this->assertSame(
					$expect->getFailureMessage()->getCode(),
					$ex->getFailureMessage()->getCode()
				);
				$this->assertSame(
					$expect->getFailureMessage()->getData(),
					$ex->getFailureMessage()->getData()
				);
			}
		} else {
			$v = $typeDef->getValue( 'test', $settings, $options );
			$this->assertEquals( $expect, $typeDef->validate( 'test', $v, $settings, $options ) );
		}

		$conditions = [];
		foreach ( $callbacks->getRecordedConditions() as $c ) {
			$conditions[] = [ 'code' => $c['message']->getCode(), 'data' => $c['message']->getData() ];
		}
		$this->assertSame( $expectConds, $conditions );
	}

	public static function provideValidate() {
		$settings = [
			ParamValidator::PARAM_TYPE => 'tags',
			ParamValidator::PARAM_ISMULTI => true,
		];
		$valuesList = [ 'values-list' => [ 'tag1', 'doesnotexist', 'doesnotexist2' ] ];

		return [
			'Basic' => [ 'tag1', [ 'tag1' ] ],
			'Bad tag' => [
				'doesnotexist',
				new ValidationException(
					DataMessageValue::new( 'paramvalidator-badtags', [], 'badtags', [
						'disallowedtags' => [ 'doesnotexist' ],
					] ),
					'test', 'doesnotexist', []
				),
			],
			'Multi' => [ 'tag1', 'tag1', $settings, [ 'values-list' => [ 'tag1', 'tag2' ] ] ],
			'Multi with bad tag (but not the tag)' => [
				'tag1', 'tag1', $settings, $valuesList
			],
			'Multi with bad tag' => [
				'doesnotexist',
				new ValidationException(
					DataMessageValue::new( 'paramvalidator-badtags', [], 'badtags', [
						'disallowedtags' => [ 'doesnotexist', 'doesnotexist2' ],
					] ),
					'test', 'doesnotexist', $settings
				),
				$settings, $valuesList
			],
			'Not a string' => [
				[ 1, 2, 3 ],
				new ValidationException(
					DataMessageValue::new( 'paramvalidator-needstring', [], 'needstring' ),
					'test', '', []
				)
			],
		];
	}

	public function testGetEnumValues() {
		$explicitlyDefinedTags = [ 'foo', 'bar', 'baz' ];
		$changeTagsStore = $this->createNoOpMock(
			ChangeTagsStore::class,
			[ 'listExplicitlyDefinedTags' ]
		);
		$changeTagsStore->method( 'listExplicitlyDefinedTags' )
			->willReturn( $explicitlyDefinedTags );

		$typeDef = new TagsDef( new SimpleCallbacks( [] ), $changeTagsStore );
		$this->assertSame(
			$explicitlyDefinedTags,
			$typeDef->getEnumValues( 'test', [], [] )
		);
	}

}
