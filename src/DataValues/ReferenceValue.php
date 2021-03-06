<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIContainer as DIContainer;
use SMWPropertyListValue as PropertyListValue;

/**
 * ReferenceValue allows to define additional DV to describe the state of a
 * SourceValue in terms of provenance or referential evidence. ReferenceValue is
 * stored as separate entity to the original subject in order to encapsulate the
 * SourceValue from the remaining annotations kept in reference to a subject.
 *
 * Defining which fields are required can vary and therefore is left to the user
 * to specify such requirements using the `'Has fields' property.
 *
 * For example, declaring `[[Has fields::SomeValue;Date;SomeUrl;...]]` on a
 * `SomeProperty` property page is to define:
 *
 * - a property called `SomeValue` with its own specification
 * - a date property with the Date type
 * - a property called `SomeUrl` with its own specification
 * - ... any other property the users expects to require when making a value
 *   annotation of this type
 *
 * An annotation `[[SomeProperty::Foo;12-12-1212;http://example.org]]` is expected
 * to be a concatenated string and be separated by ';' to indicate the next value
 * string which will corespondent to the index of the `Has fields` declaration.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ReferenceValue extends AbstractMultiValue {

	/**
	 * @var DIProperty[]|null
	 */
	private $properties = null;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '_ref_rec' );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function setFieldProperties( array $properties ) {
		foreach ( $properties as $property ) {
			if ( $property instanceof DIProperty ) {
				$this->properties[] = $property;
			}
		}
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getValuesFromString( $value ) {
		// #664 / T17732
		$value = str_replace( "\;", "-3B", $value );

		// Bug 21926 / T23926
		// Values that use html entities are encoded with a semicolon
		$value = htmlspecialchars_decode( $value, ENT_QUOTES );
		$values = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );

		return str_replace( "-3B", ";", $values );
	}

	/**
	 * @see DataValue::getShortWikiText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_LONG, $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getWikiValue() {
		return $this->getDataValueFormatter()->format( DataValueFormatter::VALUE );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertyDataItems() {

		if ( $this->properties === null ) {
			$this->properties = $this->findPropertyDataItems( $this->getProperty() );

			if ( count( $this->properties ) == 0 ) {
				$this->addErrorMsg( 'smw-datavalue-reference-invalid-fields-definition' );
			}
		}

		return $this->properties;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getDataItems() {
		return parent::getDataItems();
	}

	/**
	 * @note called by DataValue::setUserValue
	 * @see DataValue::parseUserValue
	 *
	 * {@inheritDoc}
	 */
	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( array( 'smw_novalues' ) );
			return;
		}

		$containerSemanticData = $this->newContainerSemanticData( $value );

		$values = $this->getValuesFromString( $value );
		$index = 0; // index in value array

		$propertyIndex = 0; // index in property list
		$empty = true;

		foreach ( $this->getPropertyDataItems() as $property ) {

			if ( !array_key_exists( $index, $values ) || $this->getErrors() !== array() ) {
				break; // stop if there are no values left
			}

			// generating the DVs:
			if ( ( $values[$index] === '' ) || ( $values[$index] == '?' ) ) { // explicit omission
				$index++;
			} else {
				$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
					$property,
					$values[$index],
					false,
					$this->getContextPage()
				);

				if ( $dataValue->isValid() ) { // valid DV: keep
					$containerSemanticData->addPropertyObjectValue( $property, $dataValue->getDataItem() );

					$index++;
					$empty = false;
				} elseif ( ( count( $values ) - $index ) == ( count( $this->properties ) - $propertyIndex ) ) {
					$containerSemanticData->addPropertyObjectValue( $property, $dataValue->getDataItem() );
					$this->addError( $dataValue->getErrors() );
					++$index;
				}
			}
			++$propertyIndex;
		}

		if ( $empty && $this->getErrors() === array()  ) {
			$this->addErrorMsg( array( 'smw_novalues' ) );
		}

		$this->m_dataitem = new DIContainer( $containerSemanticData );
	}

	/**
	 * @see DataValue::loadDataItem
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( $dataItem->getDIType() === DataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} elseif ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {
			$semanticData = new ContainerSemanticData( $dataItem );
			$semanticData->copyDataFrom( ApplicationFactory::getInstance()->getStore()->getSemanticData( $dataItem ) );
			$this->m_dataitem = new DIContainer( $semanticData );
			return true;
		}

		return false;
	}

	private function findPropertyDataItems( DIProperty $property = null ) {

		if ( $property === null ) {
			return array();
		}

		$propertyDiWikiPage = $property->getDiWikiPage();

		if ( $propertyDiWikiPage === null ) {
			return array();
		}

		$listDiProperty = new DIProperty( '_LIST' );
		$dataItems = ApplicationFactory::getInstance()->getStore()->getPropertyValues( $propertyDiWikiPage, $listDiProperty );

		if ( count( $dataItems ) == 1 ) {
			$propertyListValue = new PropertyListValue( '__pls' );
			$propertyListValue->setDataItem( reset( $dataItems ) );

			if ( $propertyListValue->isValid() ) {
				return $propertyListValue->getPropertyDataItems();
			}
		}

		return array();
	}

	private function newContainerSemanticData( $value ) {

		if ( $this->m_contextPage === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
		} else {
			$subobjectName = '_REF' . md5( $value );

			$subject = new DIWikiPage(
				$this->m_contextPage->getDBkey(),
				$this->m_contextPage->getNamespace(),
				$this->m_contextPage->getInterwiki(),
				$subobjectName
			);

			$containerSemanticData = new ContainerSemanticData( $subject );
		}

		return $containerSemanticData;
	}

}
