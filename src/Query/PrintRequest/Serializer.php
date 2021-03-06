<?php

namespace SMW\Query\PrintRequest;

use SMW\Query\PrintRequest;
use SMW\Localizer;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class Serializer {

	/**
	 * @since 2.5
	 *
	 * @param PrintRequest $printRequest
	 * @param boolean $showparams that sets if the serialization should include
	 * the extra print request parameters
	 *
	 * @return string
	 */
	public static function serialize( PrintRequest $printRequest, $showparams = false ) {
		$parameters = '';

		if ( $showparams ) {
			foreach ( $printRequest->getParameters() as $key => $value ) {
				$parameters .= "|+" . $key . "=" . $value;
			}
		}

		switch ( $printRequest->getMode() ) {
			case PrintRequest::PRINT_CATS:
				return self::doSerializeCat( $printRequest, $parameters );
			case PrintRequest::PRINT_CCAT:
				return self::doSerializeCcat( $printRequest, $parameters );
			case PrintRequest::PRINT_CHAIN:
			case PrintRequest::PRINT_PROP:
				return self::doSerializeProp( $printRequest, $parameters );
			case PrintRequest::PRINT_THIS:
				return self::doSerializeThis( $printRequest, $parameters );
			default:
				return '';
		}

		return ''; // no current serialisation
	}

	private static function doSerializeCat( $printRequest, $parameters ) {

		$catlabel = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
		$result = '?' . $catlabel;

		if ( $printRequest->getLabel() != $catlabel ) {
			$result .= '=' . $printRequest->getLabel();
		}

		return $result . $parameters;
	}

	private static function doSerializeCcat( $printRequest, $parameters ) {

		$printname = $printRequest->getData()->getPrefixedText();
		$result = '?' . $printname;

		if ( $printRequest->getOutputFormat() != 'x' ) {
			$result .= '#' . $printRequest->getOutputFormat();
		}

		if ( $printRequest->getLabel() != $printname ) {
			$result .= '=' . $printRequest->getLabel();
		}

		return $result . $parameters;
	}

	private static function doSerializeProp( $printRequest, $parameters ) {

		$printname = '';

		if ( $printRequest->getData()->isVisible() ) {
			// #1564
			// Use the canonical form for predefined properties to ensure
			// that local representations are for display but points to
			// the correct property
			if ( $printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
				$printname = $printRequest->getData()->getDataItem()->getString();
			} else {
				$printname = $printRequest->getData()->getDataItem()->getCanonicalLabel();
			}
		}

		$result = '?' . $printname;

		if ( $printRequest->getOutputFormat() !== '' ) {
			$result .= '#' . $printRequest->getOutputFormat();
		}

		if ( $printname != $printRequest->getLabel() ) {
			$result .= '=' . $printRequest->getLabel();
		}

		return $result . $parameters;
	}

	private static function doSerializeThis( $printRequest, $parameters ) {

		$result = '?';

		if ( $printRequest->getLabel() !== '' ) {
			$result .= '=' . $printRequest->getLabel();
		}

		if ( $printRequest->getOutputFormat() !== '' ) {
			$result .= '#' . $printRequest->getOutputFormat();
		}

		return $result . $parameters;
	}

}
