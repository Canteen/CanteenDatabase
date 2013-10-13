<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	abstract class Query
	{
		/**
		*  The array collection of where properties  
		*  @property {String} where
		*  @protected
		*/
		protected $where = '';
		
		/**
		*  The limit  
		*  @property {String} limit
		*  @protected
		*/
		protected $limit = '';
		
		/**
		*  The order by  
		*  @property {String} orderBy
		*  @protected
		*/
		protected $orderBy = '';
		
		/**
		*  The table or tables to insert into  
		*  @property {String} tables
		*  @protected
		*/
		protected $tables = '';
		
		/**
		*  The reference to the database connection, required!  
		*  @property {Database} db
		*  @protected
		*/
		protected $db;
		
		/**
		*  The abstract class that all queries extend
		*  this is base API for creating database queries
		*  
		*  @class Query
		*  @constructor
		*  @param {Database} db The reference to the database connection
		*/
		protected function __construct(Database $db)
		{
			$this->db = $db;
		}
		
		/**
		*  Create and insert query
		*  @method setTables
		*  @param {Array|String} tables* The table to insert into
		*  @return {Query} The instance of this query
		*/
		protected function setTables($tables)
		{
			if (!is_array($tables)) 
				$tables = array($tables);
				
			foreach($tables as $i=>$table)
			{
				if (preg_match('/^[a-zA-Z0-9\-\_]+$/', $table))
				{
					$tables[$i] = "`$table`";
				}
			}
			$this->tables = implode(',', $tables);
			return $this;
		}
		
		/**
		*  Do a where selection
		*  @method where
		*  @param {Array|String} args* A single where statement or array of and statements, or list of arguments
		*  @return {Query} The instance of this query
		*/
		public function where($args)
		{
			if ($this->where != '') $this->where .= ' and ';
			$args = is_array($args) ? $args : func_get_args();
			$this->where .= implode(' and ', $args);
			return $this;
		}
		
		/**
		*  How to order the results by
		*  @method orderBy
		*  @param {String} prop The property name
		*  @param {String} [order='asc'] The order, either ASC or DESC
		*  @return {Query} The instance of this query
		*/
		public function orderBy($prop, $order='asc')
		{
			if ($this->orderBy != '') $this->orderBy .= ',';
			$this->orderBy .= ' ' . $prop . ' ' . $order;
			return $this;
		}
		
		/**
		*  The limit for query
		*  @method limit
		*  @param {int} lengthOrIndex Either the single limit number or index, duration
		*  @param {int} [duration=null] How many rows to fetch
		*  @return {Query} The instance of this query
		*/
		public function limit($lengthOrIndex, $duration=null)
		{
			$this->limit = $lengthOrIndex;
			if ($duration !== null)
			{
				$this->limit .= ',' . $duration;
			}
			return $this;
		}
		
		/**
		*  Execute the query
		*  @method result
		*  @return {Boolean} If result was successful
		*/
		public function result()
		{
			$this->db->flush();
			return (bool)$this->db->execute((string)$this);
		}
	}
}