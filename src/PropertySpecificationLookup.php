<?php

namespace SMW;

use SMW\Query\DescriptionFactory;
use Onoi\Cache\Cache;
use SMW\Message;
use SMWDIBlob as DIBlob;
use SMWQuery as Query;

/**
 * This class should be accessed via ApplicationFactory::getPropertySpecificationLookup
 * to ensure a singleton instance.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertySpecificationLookup {

	const POOLCACHE_ID = 'property.specification.lookup';

	/**
	 * @var CachedPropertyValuesPrefetcher
	 */
	private $cachedPropertyValuesPrefetcher;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var Cache
	 */
	private $intermediaryMemoryCache;

	/**
	 * @since 2.4
	 *
	 * @param CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher
	 * @param Cache $intermediaryMemoryCache
	 */
	public function __construct( CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher, Cache $intermediaryMemoryCache ) {
		$this->cachedPropertyValuesPrefetcher = $cachedPropertyValuesPrefetcher;
		$this->intermediaryMemoryCache = $intermediaryMemoryCache;
	}

	/**
	 * @since 2.4
	 */
	public function resetCacheBy( DIWikiPage $subject ) {
		$this->cachedPropertyValuesPrefetcher->resetCacheBy( $subject );
	}

	/**
	 * @since 2.4
	 *
	 * @param string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = Localizer::asBCP47FormattedLanguageCode( $languageCode );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getPreferredPropertyLabelBy( $id ) {

		$key = 'ppl:' . $id;

		// Guard against high frequency lookup
		if ( ( $preferredPropertyLabel = $this->intermediaryMemoryCache->fetch( $key ) ) !== false ) {
			return $preferredPropertyLabel;
		}

		$preferredPropertyLabel = $this->findPreferredPropertyLabel(
			new DIProperty( str_replace( ' ', '_', $id ) )
		);

		$this->intermediaryMemoryCache->save( $key, $preferredPropertyLabel );

		return $preferredPropertyLabel;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $displayTitle
	 *
	 * @return DIProperty|false
	 */
	public function getPropertyFromDisplayTitle( $displayTitle ) {

		$descriptionFactory = new DescriptionFactory();

		$description = $descriptionFactory->newSomeProperty(
			new DIProperty( '_DTITLE' ),
			$descriptionFactory->newValueDescription( new DIBlob( $displayTitle ) )
		);

		$query = new Query( $description );
		$query->setLimit( 1 );

		$dataItems = $this->cachedPropertyValuesPrefetcher->queryPropertyValuesFor(
			$query
		);

		if ( is_array( $dataItems ) && $dataItems !== array() ) {
			$dataItem = end( $dataItems );

			// Cache results as a linked list attached to
			// the property so that it can be purged all together

			return new DIProperty( $dataItem->getDBKey() );
		}

		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function hasUniquenessConstraintBy( DIProperty $property ) {

		$hasUniquenessConstraint = false;
		$key = 'uc:'. $property->getKey();

		// Guard against high frequency lookup
		if ( $this->intermediaryMemoryCache->contains( $key ) ) {
			return $this->intermediaryMemoryCache->fetch( $key );
		}

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			new DIProperty( '_PVUC' )
		);

		if ( is_array( $dataItems ) && $dataItems !== array() ) {
			$hasUniquenessConstraint = end( $dataItems )->getBoolean();
		}

		$this->intermediaryMemoryCache->save( $key, $hasUniquenessConstraint );

		return $hasUniquenessConstraint;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return DataItem|null
	 */
	public function getExternalFormatterUriBy( DIProperty $property ) {

		$dataItem = null;

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			new DIProperty( '_PEFU' )
		);

		if ( is_array( $dataItems ) && $dataItems !== array() ) {
			$dataItem = end( $dataItems );
		}

		return $dataItem;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	public function getAllowedPatternBy( DIProperty $property ) {

		$allowsPattern = '';
		$key = 'ap:'. $property->getKey();

		// Guard against high frequency lookup
		if ( $this->intermediaryMemoryCache->contains( $key ) ) {
			return $this->intermediaryMemoryCache->fetch( $key );
		}

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			new DIProperty( '_PVAP' )
		);

		if ( is_array( $dataItems ) && $dataItems !== array() ) {
			$allowsPattern = end( $dataItems )->getString();
		}

		$this->intermediaryMemoryCache->save( $key, $allowsPattern );

		return $allowsPattern;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return integer|false
	 */
	public function getAllowedValuesBy( DIProperty $property ) {

		$allowsValues = array();
		$key = 'al:'. $property->getKey();

		// Guard against high frequency lookup
		if ( $this->intermediaryMemoryCache->contains( $key ) ) {
			return $this->intermediaryMemoryCache->fetch( $key );
		}

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			new DIProperty( '_PVAL' )
		);

		if ( is_array( $dataItems ) && $dataItems !== array() ) {
			$allowsValues = $dataItems;
		}

		$this->intermediaryMemoryCache->save( $key, $allowsValues );

		return $allowsValues;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return integer|false
	 */
	public function getDisplayPrecisionBy( DIProperty $property ) {

		$displayPrecision = false;

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			new DIProperty( '_PREC' )
		);

		if ( $dataItems !== false && $dataItems !== array() ) {
			$dataItem = end( $dataItems );
			$displayPrecision = abs( (int)$dataItem->getNumber() );
		}

		return $displayPrecision;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	public function getDisplayUnitsBy( DIProperty $property ) {

		$units = array();

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			new DIProperty( '_UNIT' )
		);

		if ( $dataItems !== false && $dataItems !== array() ) {
			foreach ( $dataItems as $dataItem ) {
				$units = array_merge( $units, preg_split( '/\s*,\s*/u', $dataItem->getString() ) );
			}
		}

		return $units;
	}

	/**
	 * We try to cache anything to avoid unnecessary store connections or DB
	 * lookups. For cases where a property was changed, the EventDipatcher will
	 * receive a 'property.spec.change' event (emitted as soon as the content of
	 * a property page was altered) with PropertySpecificationLookup::resetCacheBy
	 * being invoked to remove the cache entry for that specific property.
	 *
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param mixed|null $linker
	 *
	 * @return string
	 */
	public function getPropertyDescriptionBy( DIProperty $property, $linker = null ) {

		// Take the linker into account (Special vs. in page rendering etc.)
		$key = '--pdesc:' . $this->languageCode . ':' . ( $linker === null ? '0' : '1' );

		$blobStore = $this->cachedPropertyValuesPrefetcher->getBlobStore();

		$container = $blobStore->read(
			$this->cachedPropertyValuesPrefetcher->getRootHashFrom( $property->getCanonicalDiWikiPage() )
		);

		if ( $container->has( $key ) ) {
			return $container->get( $key );
		}

		$localPropertyDescription = $this->tryToFindLocalPropertyDescription(
			$property,
			$linker
		);

		// If a local property description wasn't available for a predefined property
		// the try to find a system translation
		if ( trim( $localPropertyDescription ) === '' && !$property->isUserDefined() ) {
			$localPropertyDescription = $this->getPredefinedPropertyDescription( $property, $linker );
		}

		$container->set( $key, $localPropertyDescription );

		$blobStore->save(
			$container
		);

		return $localPropertyDescription;
	}

	private function getPredefinedPropertyDescription( $property, $linker ) {

		$description = '';
		$key = $property->getKey();

		if ( ( $msgKey = PropertyRegistry::getInstance()->findPropertyDescriptionMsgKeyById( $key ) ) === '' ) {
			$msgKey = 'smw-pa-property-predefined' . strtolower( $key );
		}

		if ( !Message::exists( $msgKey ) ) {
			return $description;
		}

		$message = Message::get(
			array( $msgKey, $property->getLabel() ),
			$linker === null ? Message::ESCAPED : Message::PARSE,
			$this->languageCode
		);

		return $message;
	}

	private function tryToFindLocalPropertyDescription( $property, $linker ) {

		$text = '';
		$descriptionProperty = new DIProperty( '_PDESC' );

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			$descriptionProperty
		);

		if ( ( $dataValue = $this->findTextValueByLanguage( $dataItems, $descriptionProperty ) ) !== null ) {
			$text = $dataValue->getShortWikiText( $linker );
		}

		return $text;
	}

	private function findPreferredPropertyLabel( $property ) {

		$text = '';
		$preferredProperty = new DIProperty( '_PPLB' );

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$property->getCanonicalDiWikiPage(),
			$preferredProperty
		);

		if ( ( $dataValue = $this->findTextValueByLanguage( $dataItems, $preferredProperty ) ) !== null ) {
			$text = $dataValue->getShortWikiText();
		}

		return $text;
	}

	private function findTextValueByLanguage( $dataItems, $property ) {

		if ( $dataItems === null || $dataItems === array() ) {
			return null;
		}

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				$dataItem,
				$property
			);

			// Here a MonolingualTextValue was retunred therefore the method
			// can be called without validation
			$dv = $dataValue->getTextValueByLanguage( $this->languageCode );

			if ( $dv !== null ) {
				return $dv;
			}
		}

		return null;
	}

}
