<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	/**
	*  Represent the query to show tables
	*/
	class ShowQuery extends Query
	{
		/**
		*  The name of the database 
		*  @property {String} _databaseName
		*  @private
		*/
		private $_databaseName = '';
		
		/**
		*  Create a new truncate query
		*  
		*	$tables = $db->show('users')->result();
		*  
		*  @class ShowQuery
		*  @extends Query
		*  @constructor
		*  @param Reference to the database
		*  @param The optional database name, default is to use the current database
		*/
		public function __construct(Database $db, $databaseName='')
		{
			parent::__construct($db);
			$this->_databaseName = $databaseName;
		}
		
		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{
			$sql = 'SHOW TABLES';
			if ($this->_databaseName) $sql .= ' FROM ' . $this->escape($this->_databaseName);
			return $sql;
		}
		
		/**
		*  Execute the show tables query
		*  @param If we should cache the result (default is false)
		*  @return The array of table names
		*/
		public function result($cache=false)
		{
			return $this->db->getArray((string)$this, $cache);
		}
	}
}