<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	class InsertQuery extends Query
	{
		/**
		*  The collection of values  
		*  @property {Array} values
		*  @protected
		*/
		protected $values;
		
		/**
		*  The field names  
		*  @property {String} fields
		*  @protected
		*/
		protected $fields = '';
		
		/**
		*  Insert query to add a row to a table
		*  
		*	$inserted = $db->insert('user')->values(array(
		*		'first_name' => 'Jim',
		*		'last_name' => 'Smith
		*	));
		*  
		*  @class InsertQuery
		*  @extends Query
		*  @constructor
		*  @param {Database} db Reference to the database
		*  @param {Array|String} tables* The tables to insert into
		*/
		public function __construct(Database $db, $tables)
		{
			parent::__construct($db);
			$this->values = [];
			$this->setTables($tables);
		}
		
		/**
		*  The fields to insert into
		*  @method fields
		*  @param {String|Array} fields* The array of fields or n number of arguments
		*  @return {InsertQuery} The instance of this query
		*/
		public function fields($fields)
		{
			$fields = is_array($fields) ? $fields : func_get_args();
			foreach($fields as $i=>$field)
			{
				// See if the tick marks wrap the field name
				$fields[$i] = $this->escape(
					preg_match('/^\`.*\`$/', $field) ? $field : '`'.$field.'`'
				);
			}
			$this->fields = '('.implode(',', $fields).')';
			return $this;
		}
		
		/**
		*  Add values
		*  @method values
		*  @param {Dictionary} values The values to add. Add multiple row by setting the fields() then adding 
		*         sequential array. Add a single row by passing an associative array of 
		*         field names to values.
		*  @return {InsertQuery} The instance of this query
		*/
		public function values($values)
		{
			$values = is_array($values) ? $values : func_get_args();
			
			// If the array is associative, then we should add
			// the fields as well as the values
			if ($this->isAssoc($values))
			{
				$this->fields(array_keys($values));
				$this->values(array_values($values));
			}
			else
			{
				$row = [];
				foreach($values as $n=>$v)
				{
					// Clean the values
					$row[$this->escape($n)] = $this->prepare($v);
				}
				$this->values[] = '(' . implode(',', $row) . ')';
			}
			return $this;
		}
		
		/**
		*  Check to see if an array is associative
		*  @method isAssoc
		*  @private
		*  @param {Array} arr The array to check
		*  @return {Boolean} if the array is associative
		*/
		private function isAssoc($arr)
		{
		    return array_keys($arr) !== range(0, count($arr) - 1);
		}

		/**
		*  Execute the query
		*  @method result
		*  @return {int} The id of the last insert
		*/
		public function result()
		{
			return parent::result() ? $this->db->insertId() : false;
		}
		
		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{			
			return 'INSERT INTO ' 
				. $this->tables . $this->fields 
				. ' VALUES ' . implode(', ', $this->values);
		}
	}
}