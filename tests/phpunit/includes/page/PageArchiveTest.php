<?php

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\ContentHandler;
use MediaWiki\Content\TextContent;
use MediaWiki\Page\PageArchive;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentityValue;

/**
 * @group Database
 * @coversDefaultClass \MediaWiki\Page\PageArchive
 * @covers ::__construct
 */
class PageArchiveTest extends MediaWikiIntegrationTestCase {

	use TempUserTestTrait;

	/**
	 * @var int
	 */
	protected $pageId;

	/**
	 * @var Title
	 */
	protected $archivedPage;

	/**
	 * A logged out user who edited the page before it was archived.
	 * @var string
	 */
	protected $ipEditor;

	/**
	 * Revision of the first (initial) edit
	 * @var RevisionRecord
	 */
	protected $firstRev;

	/**
	 * Revision of the IP edit (the second edit)
	 * @var RevisionRecord
	 */
	protected $ipRev;

	protected function setUp(): void {
		parent::setUp();
		$this->disableAutoCreateTempUser();

		// First create our dummy page
		$this->archivedPage = Title::makeTitle( NS_MAIN, 'PageArchiveTest_thePage' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $this->archivedPage );
		$content = ContentHandler::makeContent(
			'testing',
			$page->getTitle(),
			CONTENT_MODEL_WIKITEXT
		);

		$user = $this->getTestUser()->getUser();
		$page->doUserEditContent( $content, $user, 'testing', EDIT_NEW | EDIT_SUPPRESS_RC );

		$this->pageId = $page->getId();
		$this->firstRev = $page->getRevisionRecord();

		// Insert IP revision
		$this->ipEditor = '2001:DB8:0:0:0:0:0:1';

		$revisionStore = $this->getServiceContainer()->getRevisionStore();

		$ipTimestamp = wfTimestamp(
			TS_MW,
			wfTimestamp( TS_UNIX, $this->firstRev->getTimestamp() ) + 1
		);
		$rev = new MutableRevisionRecord( $page );
		$rev->setUser( UserIdentityValue::newAnonymous( $this->ipEditor ) );
		$rev->setTimestamp( $ipTimestamp );
		$rev->setContent( SlotRecord::MAIN, new TextContent( 'Lorem Ipsum' ) );
		$rev->setComment( CommentStoreComment::newUnsavedComment( 'just a test' ) );

		$dbw = $this->getDb();
		$this->ipRev = $revisionStore->insertRevisionOn( $rev, $dbw );

		$this->deletePage( $page, '', $user );
	}

	protected function getExpectedArchiveRows() {
		return [
			[
				'ar_minor_edit' => '0',
				'ar_user' => null,
				'ar_user_text' => $this->ipEditor,
				'ar_actor' => (string)$this->getServiceContainer()->getActorNormalization()
					->acquireActorId( new UserIdentityValue( 0, $this->ipEditor ), $this->getDb() ),
				'ar_len' => '11',
				'ar_deleted' => '0',
				'ar_rev_id' => strval( $this->ipRev->getId() ),
				'ar_timestamp' => $this->getDb()->timestamp( $this->ipRev->getTimestamp() ),
				'ar_sha1' => '0qdrpxl537ivfnx4gcpnzz0285yxryy',
				'ar_page_id' => strval( $this->ipRev->getPageId() ),
				'ar_comment_text' => 'just a test',
				'ar_comment_data' => null,
				'ar_comment_cid' => strval( $this->ipRev->getComment()->id ),
				'ts_tags' => null,
				'ar_id' => '2',
				'ar_namespace' => '0',
				'ar_title' => 'PageArchiveTest_thePage',
				'ar_parent_id' => strval( $this->ipRev->getParentId() ),
			],
			[
				'ar_minor_edit' => '0',
				'ar_user' => (string)$this->getTestUser()->getUser()->getId(),
				'ar_user_text' => $this->getTestUser()->getUser()->getName(),
				'ar_actor' => (string)$this->getTestUser()->getUser()->getActorId(),
				'ar_len' => '7',
				'ar_deleted' => '0',
				'ar_rev_id' => strval( $this->firstRev->getId() ),
				'ar_timestamp' => $this->getDb()->timestamp( $this->firstRev->getTimestamp() ),
				'ar_sha1' => 'pr0s8e18148pxhgjfa0gjrvpy8fiyxc',
				'ar_page_id' => strval( $this->firstRev->getPageId() ),
				'ar_comment_text' => 'testing',
				'ar_comment_data' => null,
				'ar_comment_cid' => strval( $this->firstRev->getComment()->id ),
				'ts_tags' => null,
				'ar_id' => '1',
				'ar_namespace' => '0',
				'ar_title' => 'PageArchiveTest_thePage',
				'ar_parent_id' => '0',
			],
		];
	}

	/**
	 * @covers \MediaWiki\Page\PageArchive::listPagesBySearch
	 * @covers \MediaWiki\Page\PageArchive::listPagesByPrefix
	 * @covers \MediaWiki\Page\PageArchive::listPages
	 */
	public function testListPagesBySearch() {
		$pages = PageArchive::listPagesBySearch( 'PageArchiveTest_thePage' );
		$this->assertSame( 1, $pages->numRows() );

		$page = (array)$pages->current();

		$this->assertSame(
			[
				'ar_namespace' => '0',
				'ar_title' => 'PageArchiveTest_thePage',
				'count' => '2',
			],
			$page
		);
	}

	/**
	 * @covers \MediaWiki\Page\PageArchive::listPagesByPrefix
	 * @covers \MediaWiki\Page\PageArchive::listPages
	 */
	public function testListPagesByPrefix() {
		$pages = PageArchive::listPagesByPrefix( 'PageArchiveTest' );
		$this->assertSame( 1, $pages->numRows() );

		$page = (array)$pages->current();

		$this->assertSame(
			[
				'ar_namespace' => '0',
				'ar_title' => 'PageArchiveTest_thePage',
				'count' => '2',
			],
			$page
		);
	}

}
