<?php

namespace SMW;

use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
interface QueryEngine {

	/**
	 * Returns a query result object for the provided query object containing a
	 * list of results.
	 *
	 * @note If the request was made for a debug (querymode MODE_DEBUG) query
	 * then a simple HTML-compatible string is returned.
	 *
	 * @since 2.4
	 *
	 * @param Query $query
	 *
	 * @return QueryResult|string
	 */
	public function getQueryResult( Query $query );

}
