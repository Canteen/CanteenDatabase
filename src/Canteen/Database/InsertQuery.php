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
			$this->values = array();
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
				$fields[$i] = preg_match('/^\`.*\`$/', $field) ? $field : '`'.$field.'`';
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
				$row = array();
				foreach($values as $n=>$v)
				{
					// Clean the values
					$row[$n] = $this->prepare($v);
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
		*  Take an existing value we're about to input and escape it, if needed
		*  @method prepare
		*  @private
		*  @param {String} value A statement or sql property to evaluate
		*  @return {String} A string of an escaped, prepared SQL property
		*/
		private function prepare($value)
		{
			// Don't do anything to NOW()
			// or expressions of incrementing or decrementing
			if (preg_match('/^(NOW\(\))|([a-zA-Z\_\-\.`]+ (\-|\+) [0-9]+)$/', $value)) return $value;
		
			// If our string already has single encasing quotes
			// strip them off
			$value = preg_match('/\'.*\'/', $value) ? substr($value, 1, -1) : $value;
			
			return "'".$this->db->escapeString($value)."'";
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