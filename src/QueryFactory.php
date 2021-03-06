<?php

namespace SMW;

use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\Query\PrintRequestFactory;
use SMW\Query\ProfileAnnotatorFactory;
use SMW\Query\ConfigurableQueryCreator;
use SMWQuery as Query;
use SMWQueryParser as QueryParser;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryFactory {

	/**
	 * @since 2.5
	 *
	 * @return ProfileAnnotatorFactory
	 */
	public function newProfileAnnotatorFactory() {
		return new ProfileAnnotatorFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @param Description $description
	 * @param integer|false $context
	 *
	 * @return Query
	 */
	public function newQuery( Description $description, $context = false ) {
		return new Query( $description, $context );
	}

	/**
	 * @since 2.4
	 *
	 * @return DescriptionFactory
	 */
	public function newDescriptionFactory() {
		return new DescriptionFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @return PrintRequestFactory
	 */
	public function newPrintRequestFactory() {
		return new PrintRequestFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @return RequestOptions
	 */
	public function newRequestOptions() {
		return new RequestOptions();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $string
	 * @param integer $condition
	 * @param boolean $isDisjunctiveCondition
	 *
	 * @return StringCondition
	 */
	public function newStringCondition( $string, $condition, $isDisjunctiveCondition = false ) {
		return new StringCondition( $string, $condition, $isDisjunctiveCondition );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer|boolean $queryFeatures
	 *
	 * @return QueryParser
	 */
	public function newQueryParser( $queryFeatures = false ) {
		return new QueryParser( $queryFeatures );
	}

	/**
	 * @since 2.5
	 *
	 * @return ConfigurableQueryCreator
	 */
	public function newConfigurableQueryCreator() {

		$queryCreator = new ConfigurableQueryCreator(
			$this,
			$GLOBALS['smwgQDefaultNamespaces'],
			$GLOBALS['smwgQDefaultLimit']
		);

		$queryCreator->setQFeatures(
			$GLOBALS['smwgQFeatures']
		);

		$queryCreator->setQConceptFeatures(
			$GLOBALS['smwgQConceptFeatures']
		);

		return $queryCreator;
	}

}
