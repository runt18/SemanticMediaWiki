<?php
/**
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to URI data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerUri extends SMWDataItemHandler {

	const MAX_LENGTH = 255;

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_blob' => 'l', 'o_serialized' => 't' );
	}

	/**
	 * @see SMWDataItemHandler::getFetchFields()
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array( 'o_blob' => 'l', 'o_serialized' => 't' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array( 'o_serialized' => rawurldecode( $dataItem->getSerialization() ) );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {

		$serialization = rawurldecode( $dataItem->getSerialization() );
		$text = mb_strlen( $serialization ) <= self::MAX_LENGTH ? null : $serialization;

		// bytea type handling
		if ( $text !== null && $GLOBALS['wgDBtype'] === 'postgres' ) {
			$text = pg_escape_bytea( $text );
		}

		return array(
			'o_blob' => $text,
			'o_serialized' => $serialization,
		);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since 1.8
	 * @return string
	 */
	public function getIndexField() {
		return 'o_serialized';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return 'o_serialized';
	}

	/**
	 * @see SMWDataItemHandler::dataItemFromDBKeys()
	 * @since 1.8
	 * @param array|string $dbkeys expecting string here
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {

		if ( !is_array( $dbkeys ) || count( $dbkeys ) != 2 ) {
			throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
		}

		if ( $GLOBALS['wgDBtype'] === 'postgres' ) {
			$dbkeys[0] = pg_unescape_bytea( $dbkeys[0] );
		}

		return SMWDIUri::doUnserialize( $dbkeys[0] == '' ? $dbkeys[1] : $dbkeys[0] );
	}

}
