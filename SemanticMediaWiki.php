<?php

use SMW\NamespaceManager;
use SMW\ApplicationFactory;
use SMW\Setup;

/**
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * http://doc.semantic-mediawiki.org/ is assumed to be easier this way.
 *
 * @defgroup SMW Semantic MediaWiki
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

/**
 * @codeCoverageIgnore
 */
class SemanticMediaWiki {

	/**
	 * @since 2.4
	 */
	public static function initExtension() {

		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}

		define( 'SMW_VERSION', '3.0.0-alpha' );

		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $GLOBALS['smwgIP'] . 'languages/SMW_Aliases.php';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $GLOBALS['smwgIP'] . 'languages/SMW_Magic.php';

		$GLOBALS['smwgSemanticsEnabled'] = true;

		// I'm not sure this is really necessary but to avoid
		// a potential issue
		SemanticMediaWiki::initCanonicalNamespaces();
	}

	/**
	 * CanonicalNamespaces initialization
	 *
	 * @note According to T104954 registration via wgExtensionFunctions is to late
	 * and should happen before that
	 *
	 * @see https://phabricator.wikimedia.org/T104954#2391291
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 * @Bug 34383
	 *
	 * @since 2.x
	 */
	public static function initCanonicalNamespaces() {
		$GLOBALS['wgHooks']['CanonicalNamespaces'][] = function ( &$namespaces ) {
			NamespaceManager::initCustomNamespace( $GLOBALS );
			$namespaces += NamespaceManager::getCanonicalNames();
			return true;
		};
	}

	/**
	 * Setup and initialization
	 *
	 * @note $wgExtensionFunctions variable is an array that stores
	 * functions to be called after most of MediaWiki initialization
	 * has finalized
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions
	 *
	 * @since  1.9
	 */
	public static function onExtensionFunction() {

		// 3.x reverse the order to ensure that smwgMainCacheType is used
		// as main and smwgCacheType being deprecated with 3.x
		$GLOBALS['smwgMainCacheType'] = $GLOBALS['smwgCacheType'];

		$applicationFactory = ApplicationFactory::getInstance();

		$namespace = new NamespaceManager( $GLOBALS );
		$namespace->init();

		$setup = new Setup( $applicationFactory, $GLOBALS, __DIR__ );
		$setup->run();
	}

	/**
	 * @since 2.4
	 *
	 * @return string|null
	 */
	public static function getVersion() {

		// THIS IS A HACK ...
		$extensionData = ExtensionRegistry::getInstance()->getAllThings();

		if ( isset( $extensionData['SemanticMediaWiki']['version'] ) ) {
			return $extensionData['SemanticMediaWiki']['version'];
		}

		return null;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public static function getStoreVersion() {

		$store = '';

		if ( isset( $GLOBALS['smwgDefaultStore'] ) ) {
			$store = $GLOBALS['smwgDefaultStore'] . ( strpos( $GLOBALS['smwgDefaultStore'], 'SQL' ) ? '' : ' ['. $GLOBALS['smwgSparqlDatabaseConnector'] .']' );
		};

		return array(
			'store' => $store,
			'db'    => isset( $GLOBALS['wgDBtype'] ) ? $GLOBALS['wgDBtype'] : 'N/A'
		);
	}

}
