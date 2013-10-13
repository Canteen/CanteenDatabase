<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	/**
	*  Delete a query
	*/
	class DeleteQuery extends Query
	{
		/**
		*  Represents a DELETE sql query
		*  
		*	$deleted = $db->delete('users')->where('user_id=1')->result();
		*  
		*  @class DeleteQuery
		*  @extends Query
		*  @constructor
		*  @param {Database} db Reference to the database
		*  @param {Array|String} tables* The tables to delete from
		*/
		public function __construct(Database $db, $tables)
		{
			parent::__construct($db);
			$this->setTables($tables);
		}
		
		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{
			$sql = 'DELETE FROM ' . $this->tables;
			if ($this->where) $sql .= ' WHERE ' . $this->where;
			return $sql;
		}
	}
}