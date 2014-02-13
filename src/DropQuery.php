<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	class DropQuery extends Query
	{
		/**
		*  Represent the query to drop a table
		*
		*	$dropped = $db->drop('users')->result();
		*  
		*  @class DropQuery
		*  @extends Query
		*  @constructor
		*  @param Reference to the database
		*  @param The single table name
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
			return 'DROP TABLE ' . $this->tables;
		}
	}
}