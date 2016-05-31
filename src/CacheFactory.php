<?php

namespace SMW;

use ObjectCache;
use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactory {

	/**
	 * @var string|integer
	 */
	private $mainCacheType;

	/**
	 * @var CallbackInstantiator
	 */
	private $callbackInstantiator;

	/**
	 * @since 2.2
	 *
	 * @param string|integer|null $mainCacheType
	 */
	public function __construct( $mainCacheType = null ) {
		$this->callbackInstantiator = ApplicationFactory::getInstance()->getCallbackInstantiator();
		$this->mainCacheType = $mainCacheType;

		if ( $this->mainCacheType === null ) {
			$this->mainCacheType = ApplicationFactory::getInstance()->getSettings()->get( 'smwgMainCacheType' );
		}
	}

	/**
	 * @since 2.2
	 *
	 * @return string|integer
	 */
	public function getMainCacheType() {
		return $this->mainCacheType;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getCachePrefix() {
		return $GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix'];
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getFactboxCacheKeyBy( $key ) {
		return $this->getCachePrefix() . ':smw:fc:' . md5( $key );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAuPurgeCacheKeyBy( $key ) {
		return $this->getCachePrefix() . ':smw:arc:' . md5( $key );
	}

	/**
	 * @since 2.2
	 *
	 * @param array $cacheOptions
	 *
	 * @return stdClass
	 * @throws RuntimeException
	 */
	public function newCacheOptions( array $cacheOptions ) {

		if ( !isset( $cacheOptions['useCache'] ) || !isset( $cacheOptions['ttl'] ) ) {
			throw new RuntimeException( "Cache options is missing a useCache/ttl parameter" );
		}

		return (object)$cacheOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @param integer $cacheSize
	 *
	 * @return Cache
	 */
	public function newFixedInMemoryCache( $cacheSize = 500 ) {
		return OnoiCacheFactory::getInstance()->newFixedInMemoryLruCache( $cacheSize );
	}

	/**
	 * @since 2.2
	 *
	 * @return Cache
	 */
	public function newNullCache() {
		return OnoiCacheFactory::getInstance()->newNullCache();
	}

	/**
	 * @since 2.2
	 *
	 * @param integer|string $mediaWikiCacheType
	 *
	 * @return Cache
	 */
	public function newMediaWikiCompositeCache( $mediaWikiCacheType = null ) {

		$compositeCache = OnoiCacheFactory::getInstance()->newCompositeCache( array(
			$this->newFixedInMemoryCache( 500 ),
			$this->newMediaWikiCache( $mediaWikiCacheType )
		) );

		return $compositeCache;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|string $mediaWikiCacheType
	 *
	 * @return Cache
	 */
	public function newMediaWikiCache( $mediaWikiCacheType = null ) {

		$mediaWikiCache = ObjectCache::getInstance(
			( $mediaWikiCacheType === null ? $this->getMainCacheType() : $mediaWikiCacheType )
		);

		return OnoiCacheFactory::getInstance()->newMediaWikiCache( $mediaWikiCache );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|null $cacheType
	 *
	 * @return Cache
	 */
	public function newCacheByType( $cacheType = null ) {

		if ( $cacheType === CACHE_NONE || $cacheType === null ) {
			return $this->newNullCache();
		}

		return $this->newMediaWikiCache( $cacheType );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $namespace
	 * @param string|integer|null $cacheType
	 * @param integer $cacheLifetime
	 *
	 * @return BlobStore
	 */
	public function newBlobStoreWith( $namespace, $cacheType = null, $cacheLifetime = 0 ) {

		$blobStore = $this->callbackInstantiator->load( 'BlobStore', $namespace, $cacheType, $cacheLifetime );

		// If CACHE_NONE is selected, disable the usage
		$blobStore->setUsageState(
			$cacheType !== CACHE_NONE
		);

		return $blobStore;
	}

}
