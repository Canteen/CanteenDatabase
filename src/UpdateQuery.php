<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	/**
	*  Query to do an update of values
	*/
	class UpdateQuery extends Query
	{
		/** Set the values */
		protected $set = '';
		
		/**
		*  Create a new update query
		*  
		*	$updated = $db->update('users')
		*		->set('first_name', 'Smith')
		*		->where('user_id=1')
		*  		->result();
		*  
		*  @class UpdateQuery
		*  @extends Query
		*  @constructor
		*  @param {Database} db Reference to the database connection
		*  @param {Array|String} tables The tables to insert into (array, string or list of arguments)
		*/
		public function __construct(Database $db, $tables)
		{
			parent::__construct($db);
			$this->setTables($tables);
		}
		
		/**
		*  Set a value or series of values
		*  @method set
		*  @param {String|Dictionary} name The name of the value or list of name/value pairs in an array
		*  @param {String|Number|int} [value=null] The value to set (if setting a single name)
		*  @return {UpdateQuery} Return instance of this query
		*/
		public function set($name, $value=null)
		{
			if (is_array($name))
			{
				foreach($name as $n=>$v)
				{
					$this->set($n, $v);
				}
			}
			else
			{
				if ($this->set != '') $this->set .= ', ';
				$this->set .= "`$name`=" . $this->prepare($value);
			}
			return $this;
		}
		
		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{
			$sql = 'UPDATE '.$this->tables . ' SET ' . $this->set;
			if ($this->where) $sql .= ' WHERE ' . $this->where;
			return $sql;
		}
	}
}