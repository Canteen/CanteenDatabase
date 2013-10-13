<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	/**
	*  Query to do a selection
	*/
	class SelectQuery extends Query
	{
		/** Group by the selection */
		protected $groupBy = '';
		
		/** The list of properties */
		protected $properties = '';
		
		/**
		*  Represents a SELECT SQL statement
		*  
		*	$users = $db->select('first_name')
		*		->from('users')
		*		->where('user_id=1')
		*		->results();
		*  
		*  @class SelectQuery
		*  @extends Query
		*  @constructor
		*  @param {Database} db Reference to the database
		*  @param {Array|String} tables* The properties as array or list of args or string
		*/
		public function __construct(Database $db, $props)
		{
			parent::__construct($db);
			$this->properties = implode(',', $props);
		}
		
		/**
		*  Select from the tables
		*  @method from
		*  @param {String|Array} args* The string of table or a collection of tables (array or n args)
		*  @return {SelectQuery} Instance of this query
		*/
		public function from($args)
		{
			$args = is_array($args) ? $args : func_get_args();
			return $this->setTables($args);
		}
		
		/**
		*  Group the selection
		*  @method groupBy
		*  @param {String|Array} args* Group by name either array, string or list of arguments
		*  @return {SelectQuery} Instance of this query
		*/
		public function groupBy($args)
		{
			$args = is_array($args) ? $args : func_get_args();
			$this->groupBy = implode(',', $args);
			return $this;
		}
		
		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{
			$sql = 'SELECT ' . $this->properties . ' FROM ' . $this->tables;
			if ($this->where) $sql .= ' WHERE ' . $this->where;
			if ($this->groupBy) $sql .= ' GROUP BY ' . $this->groupBy;
			if ($this->orderBy) $sql .= ' ORDER BY ' . $this->orderBy;
			if ($this->limit) $sql .= ' LIMIT ' . $this->limit;
			
			return $sql;
		}
		
		/**
		*  Do a selection for a specific property or a single row
		*  @param {String} [field=null] The optional field to select
		*  @param {int} [row=0] The optional row index if selecting a field
		*  @param {Boolean} [cache=false] If we should cache the result
		*  @return {mixed} Either a single row value or a associate array of the row
		*/
		public function result($field=null, $row=0, $cache=false)
		{
			if ($field !== null)
			{
				// Search for the name when the property is a select
				if (preg_match('/ as ([a-zA-Z0-9]+\.)?\`?([a-zA-Z\-_0-9]*)\`?$/', $field, $matches))
				{
					$field = $matches[2];
				}
				// Search for a property but with dashes or table name
				else if (preg_match('/([a-zA-Z0-9]+\.)?\`?([a-zA-Z\-_0-9]*)\`?/', $field, $matches))
				{
					$field = $matches[2];
				}

				return $this->db->getResult((string)$this, $field, $row, $cache);
			}
			else
			{
				$result = $this->db->getArray((string)$this, $cache);
				if ($result) return $result[0];
			}
			return null;
		}
		
		/**
		*  Get the row of data 
		*  @method row
		*  @param {Boolean} [cache=false] If we should cache the result
		*  @return {Array} The array of values for this row
		*/
		public function row($cache=false)
		{
			return $this->db->getRow((string)$this, $cache);
		}
		
		/**
		*  Select all of the results
		*  @method results
		*  @param {Boolean} [cache=false] If we should cache the result
		*  @return {Array} Get array of row items
		*/
		public function results($cache=false)
		{
			return $this->db->getArray((string)$this, $cache);
		}
		
		/**
		*  Get the number of rows
		*  @method length
		*  @param {Boolean} [cache=false] If we should cache the result
		*  @return {int} The number of rows by selection
		*/
		public function length($cache=false)
		{
			return $this->db->getLength((string)$this, $cache);
		}
	}
}