<?php

namespace SMW\Tests;

use SMW\EmbeddedQueryResultCache;
use SMW\DIWikiPage;
use SMW\InMemoryPoolCache;

/**
 * @covers \SMW\EmbeddedQueryResultCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class EmbeddedQueryResultCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\EmbeddedQueryResultCache',
			new EmbeddedQueryResultCache( $blobStore )
		);
	}

	public function testFetchQueryResultForEmptyQuery() {

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->once() )
			->method( 'getQueryResult' )
			->with($this->identicalTo( $query ) );

		$instance = new EmbeddedQueryResultCache( $blobStore );

		$instance->fetchQueryResult( $store, $query, $queryEngine );
	}

	public function testPurgeCacheByQueryList() {

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new EmbeddedQueryResultCache( $blobStore );
		$instance->purgeCacheByQueryList( array( 'Foo' ) );
	}

	public function testPurgeCacheBySubject() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->atLeastOnce() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->atLeastOnce() )
			->method( 'get' )
			->will( $this->returnValue( array( '7832d07cd251dfb15878fc7712ae57b5' ) ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new EmbeddedQueryResultCache( $blobStore );
		$instance->purgeCacheBySubject( $subject );
	}

}
