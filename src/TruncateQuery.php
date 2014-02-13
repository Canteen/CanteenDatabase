<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	class TruncateQuery extends Query
	{
		/**
		*  Represent the query to remove all contents of a table
		*  
		*	$truncated = $db->truncate('users')->result();
		*  
		*  @class TruncateQuery
		*  @extends Query
		*  @constructor
		*  @param {Database} db Reference to the database
		*  @param {String} table The single table name
		*/
		public function __construct(Database $db, $table)
		{
			parent::__construct($db);
			$this->setTables($table);
		}
		
		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{
			return 'TRUNCATE TABLE ' . $this->tables;
		}
	}
}